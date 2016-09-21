<?php 
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
if( class_exists( 'WPSellerEvents_functions') ) return;

class WPSellerEvents_functions {
	public static function wpsellerevents_env_checks() {
		global $wp_version,$user_ID; //,$wpse_admin_message;
		$message = $wpse_admin_message = '';
		$checks=true;
		if(!is_admin()) return false;
//		if( !is_plugin_active( dirname(WPSellerEvents :: $basen) . '/wpse-user-taxonomies.php') ) {
//			$message.= __('Plugin <b>WP-Seller Events User Taxonomies</b> must be activated!', WPSellerEvents :: TEXTDOMAIN );
//			$message.= ' <a href="'.admin_url('plugins.php').'#wp-seller-events"> '. __('Go to Activate Now', WPSellerEvents :: TEXTDOMAIN ). '</a>';
//			$message.= '<script type="text/javascript">jQuery(document).ready(function($){$("#wp-seller-events-user-taxonomies").css("backgroundColor","yellow");});</script>';
//			$checks=false;
//		}
		if (version_compare($wp_version, '3.9', '<')) { // check WP Version
			$message.=__('- WordPress 3.9 or higher needed!', WPSellerEvents :: TEXTDOMAIN ) . '<br />';
			$checks=false;
		}
		if (version_compare(phpversion(), '5.3.0', '<')) { // check PHP Version
			$message.=__('- PHP 5.3.0 or higher needed!', WPSellerEvents :: TEXTDOMAIN ) . '<br />';
			$checks=false;
		}
		if (wp_next_scheduled('wpsellerevents_cron')!=0  && wp_next_scheduled('wpsellerevents_cron')>(time()+360)) {  //check cron jobs work
			$message.=__("- WP-Cron don't working please check it!", WPSellerEvents :: TEXTDOMAIN ) .'<br />';
		}
		if (!empty($message))
			$wpse_admin_message = '<div id="message" class="error fade"><strong>WPSellerEvents:</strong><br />'.$message.'</div>';

		$notice = get_option('wpse_notices');
		if (!empty($notice)) {
			foreach($notice as $key => $mess) {
				if($mess['user_ID'] == $user_ID) {
					$class = ($mess['error']) ? "error fade" : "update";
					$wpse_admin_message .= '<div id="message" class="'.$class.'"><p>'.$mess['text'].'</p></div>';
					unset( $notice[$key] );
				}
			}
			update_option('wpse_notices',$notice);
		}
		
		if (!empty($wpse_admin_message)) {
			//send response to admin notice : ejemplo con la función dentro del add_action
			add_action('admin_notices', function() use ($wpse_admin_message) {
				//echo '<div class="error"><p>', esc_html($wpse_admin_message), '</p></div>';
				echo $wpse_admin_message;
			});
		}
		return $checks;
	}

	//************************* Check event data *************************************
    /**
    * Check event data
    * Required @param $eventdata array with event data values
    * 
    * @return an array with event data fixed all empty values
    **/	
	/************** CHECK DATA *************************************************/
	public static function check_eventdata($post_data) { // initialize event or parses current data
		global $post, $cfg;
		if(is_null($cfg)) $cfg = get_option( WPSellerEvents :: OPTION_KEY);
		if(  (isset($post->ID) && $post->ID > 0) && (!isset($post_data['ID']) || $post_data['ID'] == 0 ) ) {
			$eventdata['ID']=$post->ID;
		}else{
			$eventdata['ID']=$post_data['ID'];
		}
		//subject = get_the_title();
		//detail = get_the_content();
		$eventypes = array();
		if(isset($eventdata['ID']) && $eventdata['ID'] > 0) {
			$terms = get_the_terms( $eventdata['ID'], 'eventype' );
			if($terms && !is_wp_error($terms)) { 
				foreach ( $terms as $term ) {
					$eventypes[] = $term->name;
				}
			}
		}
		
		$eventdata['eventype']	= $eventypes; // empty array or term IDs (taxonomy )

//		$eventdata['fromdate']	= (!isset($post_data['fromdate']) ) ?  time()+(int)get_option( 'gmt_offset' )*3600 : $post_data['fromdate'];
//		$eventdata['todate']	= (!isset($post_data[ 'todate' ]) ) ?  time()+((int)get_option( 'gmt_offset' )*3600)+(3600*24*7) : $post_data['todate'];
		$post_data['fromdate']	= (!isset($post_data['fromdate']) ) ? current_time('timestamp')  : $post_data['fromdate'];
		$post_data['todate']	= (!isset($post_data[ 'todate' ]) ) ? current_time('timestamp')+(3600) : $post_data['todate'];  //3600*24*7 = 7 dias
		$eventdata['fromdate']	= (is_int( @$post_data['fromdate']) ) ? $post_data['fromdate'] : self::date2time($post_data['fromdate'], $cfg['dateformat'].' '.get_option('time_format') );
		$eventdata['todate']	= (is_int( @$post_data['todate'] ) )  ? $post_data['todate'] : self::date2time($post_data['todate'],$cfg['dateformat'].' '.get_option('time_format') );

		$eventdata['event_obs'] = array( 'text'=>array(),'date'=>array() );
		if (!empty($post_data['event_obs']['text']) )
			foreach($post_data['event_obs']['text'] as $key => $value) {
				if( isset( $post_data['event_obs']['text'][$key]) && !empty( $post_data['event_obs']['text'][$key] ) ) {
					$eventdata['event_obs']['text'][] = addslashes($post_data['event_obs']['text'][$key]);
					$eventdata['event_obs']['date'][] = (is_int($post_data['event_obs']['date'][$key])) ? $post_data['event_obs']['date'][$key] : self::date2time($post_data['event_obs']['date'][$key], $cfg['dateformat'].' '.get_option('time_format') );
				}
			}

		$eventdata['quantity']	= (!isset($post_data['quantity']) ) ? 0 : $post_data['quantity'];
		$eventdata['period']	= (!isset($post_data['period']) ) ? 'days': $post_data['period'];
		
		$eventdata['customer_id']	= (!isset($post_data['customer_id']) ) ? '0': $post_data['customer_id'];
		$eventdata['contact_name']	= (!isset($post_data['contact_name']) ) ? '': $post_data['contact_name'];

		$eventdata['seller_id']	= (!isset($post_data['seller_id']) ) ? 0: (int)$post_data['seller_id'];

		$eventdata['event_status']	= (!isset($post_data['event_status']) ) ? 'open' : $post_data['event_status'];

		if ($eventdata['event_status']=='closed' ) $post_data['activated']=false; /*******/
		
		if (!isset($post_data['activated']) ) $eventdata['activated']=true;
			else $eventdata['activated'] = (bool)$post_data['activated'];

		if (!isset($post_data['starttime'])) $eventdata['starttime']= 0;
			else $eventdata['starttime'] = (int)$post_data['starttime'];
		
		if (!isset($post_data['runtime'])) $eventdata['runtime']= 0;
			else $eventdata['runtime'] = (int)$post_data['runtime'];
		
		if (!isset($post_data['cron']) || !is_string($post_data['cron'])) $eventdata['cron']='-1 day';
			else $eventdata['cron'] = '-'.$eventdata['quantity'].' '.$eventdata['period'] ;
		
		switch($eventdata['period']) {
		 case 'minutes':
			 $seconds = 60;
			 break;
		 case 'hours':
			 $seconds = 3600;
			 break;
		 case 'weeks': 
			 $seconds = 3600*24*7;
			 break;
		 default: //days
			 $seconds = 3600*24;
			 break;
		}
		$cronseconds = $eventdata['quantity']*$seconds;
		$eventdata['cronnextrun']= $eventdata['fromdate'] - $cronseconds ;
		//$aa =date_i18n( $cfg['dateformat'] .' '.get_option( 'time_format' ), $eventdata['cronnextrun']);
		if ( $eventdata['cronnextrun'] <= current_time('timestamp') ) 
			$eventdata['runtime'] = $eventdata['starttime'] = 0;   // reset vars to allow send mail with cron
		
		return $eventdata;
		
	}
		
		//************************* GRABA CAMPAÑA *******************************************************
	public static function filter_handler(  $data , $postarr  ) {
		global $post, $cfg;
		//echo print_r($data,1)."<br>".	print_r($postarr,1)."<br>". print_r($post,1)."<br>";
		if($postarr['post_type'] != 'wpsellerevents') return $data;
		if($postarr['post_status'] == 'auto-draft') return $data;
		if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($postarr['bulk_edit']))
			return $data;		

		if(!current_user_can('wpse_seller')){ 
			$data['post_author'] = (int)$_POST['seller_id'];
			$_POST['post_author'] = (int)$_POST['seller_id'];
		}else {
			$_POST['seller_id']	= $data['post_author'];
		}
		return $data;
	}
	
	public static function save_eventdata( $post_id ) {
		global $post, $cfg;
		if((defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit'])) {
			//WPSellerEvents ::save_quick_edit_post($post_id);
			return $post_id;
		}
		if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']))
			return $post_id;
		if ( !wp_verify_nonce( @$_POST['wpsellerevents_nonce'], 'edit-event' ) )
			return $post_id;
		if($post->post_type != 'wpsellerevents') return $post_id;
		// Stop WP from clearing custom fields on autosave, and also during ajax requests (e.g. quick edit) and bulk edits.

		$nivelerror = error_reporting(E_ERROR | E_WARNING | E_PARSE);

//		if(current_user_can('wpse_manager')){
//			$_POST['post_author'] = $_POST['seller_id'];
//		}else {
//			$_POST['seller_id']	= $_POST['post_author'];
//		}
		$_POST['ID']=$post_id;		
		$event = array();
		$event = apply_filters('wpse_check_eventdata', $_POST);

		error_reporting($nivelerror);
		
		self :: update_event($post_id, $event);

		return $post_id ;
	}
	

    /**
    * save event data
    * Required @param   integer  $post_id    Event ID to load
    * 		  @param   boolean  $getfromdb  if set to true run get_post($post_ID) and retuirn object post
    * 
    * @return an array with event data 
    **/	
	public static function update_event( $post_id , $event = array() ) {
		//$event = apply_filters('wpse_check_eventdata', $event);
		
		add_post_meta( $post_id, 'event_data', $event, true )  or
          update_post_meta( $post_id, 'event_data', $event );
		
		//Custom fields for columns order
		add_post_meta( $post_id, 'fromdate', $event['fromdate'], true )  or
          update_post_meta( $post_id, 'fromdate', $event['fromdate'] );
		  
		add_post_meta( $post_id, 'cronnextrun', $event['cronnextrun'], true )  or
          update_post_meta( $post_id, 'cronnextrun', $event['cronnextrun'] );
		  
		add_post_meta( $post_id, 'activated', $event['activated'], true )  or
          update_post_meta( $post_id, 'activated', $event['activated'] );
		  
		add_post_meta( $post_id, 'event_status', $event['event_status'], true )  or
          update_post_meta( $post_id, 'event_status', $event['event_status'] );
		  
		add_post_meta( $post_id, 'eventype', $event['eventype'], true )  or
          update_post_meta( $post_id, 'eventype', $event['eventype'] );

		if(!current_user_can('wpse_seller')){ // if a seller the author is ok
//			$the_post = array();
//			$the_post['ID'] = $post_id;
//			$the_post['post_author'] = $event['seller_id'];
//			wp_insert_post( $the_post );
		}else{
			
		}

		$seller = get_userdata( $event['seller_id'] );
		add_post_meta( $post_id, 'seller', $seller->display_name, true )  or
          update_post_meta( $post_id, 'seller', $seller->display_name );

		$client = get_the_title($event['customer_id']); ;
		add_post_meta( $post_id, 'client', $client, true )  or
          update_post_meta( $post_id, 'client', $client );


	}

	/**
	 * public static get_meta_values
	 * @global type $wpdb
	 * @param type $key
	 * @param type $type wordpress posttype to query
	 * @param type $status the status for the posts queried
	 * @param type $filter to allow repeated values in array
	 * @param type $order_by
	 * @param type $order
	 * @return type array of values
	 */
	public static function get_meta_values( $args = null ){
		global $wpdb;
		$defaults= array(
			'key'		=> '', 
			'type'		=> 'post', 
			'status'	=> 'publish', 
			'filter'	=> false, 
			'order_by'	=> 'name',
			'order'		=> 'ASC',
		);
		$p = wp_parse_args( $args, $defaults );
		if( empty( $p['key'] ) )	return false;

		$sql = sprintf("SELECT %s pm.meta_value FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '%s' 
			AND p.post_status = '%s' 
			AND p.post_type = '%s'
			ORDER BY '%s' %s
		", ($p['filter']) ? 'DISTINCT' : '', $p['key'], $p['status'], $p['type'], $p['order_by'], $p['order'] );

		$r = $wpdb->get_col( $sql );

		return $r;
	}

	//************************* CARGA CAMPAÑASS *******************************************************
	/**
	* Load all events data
	* 
	* @return an array with all events data 
	**/	
	public static function get_events() {
		$events_data = array();
		$args = array(
			'orderby'	 => 'ID',
			'order'      => 'ASC',
			'post_type'  => 'wpsellerevents',
			'numberposts'=> -1
		);
		$events = get_posts( $args );
		foreach( $events as $post ):
			$events_data[] = self::get_event( $post->ID );	
		endforeach; 
		return $events_data;
	}
 

	//************************* CARGA CAMPAÑA *******************************************************
	/**
    * Load event data
    * Required @param   integer  $post_id    Event ID to load
    * 		  @param   boolean  $getfromdb  if set to true run get_post($post_ID) and retuirn object post
    * 
    * @return an array with event data 
    **/	
	public static function get_event( $post_id , $getfromdb = false ) {
		if ( $getfromdb ){
			$event = get_post($post_id);
		}
		$event_data = get_post_meta( $post_id , 'event_data', true );
		$event_data['event_id'] = $post_id;
		$event_data['event_title'] = get_the_title($post_id);
		return $event_data;
	}
	

	/*********** 	 Funciones para procesar campañas ******************/
	//DoJob
	public static function wpsellerevents_dojob($jobid) {
		global $event_log_message;
		$event_log_message = "";
		if (empty($jobid))
			return false;
		require_once(dirname(__FILE__).'/event_run.php');
		$fetched= new wpsellerevents_event_run($jobid);
		unset($fetched);
		return $event_log_message;
	}

	// Processes all events
 	public static function processAll() {
		$args = array( 'post_type' => 'wpsellerevents', 'orderby' => 'ID', 'order' => 'ASC' );
		$eventsid = get_posts( $args );
		$msglogs = "";
		foreach( $eventsid as $eventid ) {
			@set_time_limit(0);    
			$msglogs .= WPSellerEvents :: wpsellerevents_dojob( $eventid->ID ); 
		}
		return $msglogs;
	}
	
  
	################### DATE FUNCS
	/* function date2time (also datetime to time)
	 * @param $value	str date or date time as '22-09-2008' or '22-09-2008 15:35:00' 
	 * @param $format	str format of the date in $value, as 'm-d-Y' or 'd-m-Y' 
	 * 
	 * @return int timestamp or false if error
	 */
    public static function date2time($value ,  $dateformat = 'd-m-Y' ){
		$date = date_parse_from_format( $dateformat , $value);
		$timestamp = mktime($date['hour'], $date['minute'], $date['second'], $date['month'], $date['day'], $date['year']);
		if($timestamp['error_count'] !=0 ) $timestamp=false;  // if error return false
		return $timestamp; 
	}
	################### ARRAYS FUNCS
	/* * filtering an array   */
    public static function filter_by_value ($array, $index, $value){
		$newarray=array();
        if(is_array($array) && count($array)>0){
            foreach(array_keys($array) as $key) {
                $temp[$key] = $array[$key][$index];                
                if ($temp[$key] != $value){
                    $newarray[$key] = $array[$key];
                }
            }
        }
      return $newarray;
    } 
	 //Example: array_sort($my_array,'!group','surname');
	//Output: sort the array DESCENDING by group and then ASCENDING by surname. Notice the use of ! to reverse the sort order. 
	public static function array_sort_func($a,$b=NULL) {
		static $keys;
		if($b===NULL) return $keys=$a;
		foreach($keys as $k) {
			if(@$k[0]=='!') {
				$k=substr($k,1);
				if(@$a[$k]!==@$b[$k]) {
					return strcmp(@$b[$k],@$a[$k]);
				}
			}
			else if(@$a[$k]!==@$b[$k]) {
				return strcmp(@$a[$k],@$b[$k]);
			}
		}
		return 0;
	}

	public static function array_sort(&$array) {
		if(!$array) return false;
		$keys=func_get_args();
		array_shift($keys);
		self::array_sort_func($keys);
		usort($array, array(__CLASS__,"array_sort_func"));
	} 
	################### END ARRAYS FUNCS



	
	/**
	 * Remove Wordpress SEO Metabox of some post types 
	 * @global type $wpseo_admin 
	 * 
	 */
	public static function remove_WP_SEO_meta(){
		global $wpseo_admin;
		//wordpress-seo/wp-seo.php"get_option( 'active_plugins', array() )
		# this removes when editing 'YOUR PROFILE'
		remove_action( 'show_user_profile', array( $wpseo_admin, 'user_profile' ) );
		# this removes when editing 'EDIT PROFILE'
		remove_action( 'edit_user_profile', array( $wpseo_admin, 'user_profile' ) );

		// get rid of irritating WordPress SEO Columns - http://yoast.com/wordpress/seo/api/#filters
		add_filter( 'wpseo_use_page_analysis', '__return_false' );

		// get rid of WordPress SEO metabox - http://wordpress.stackexchange.com/a/91184/2015
		//if ( ! is_super_admin() ) {  //SOLO SUPERADMIN LO VE
			function so_remove_wp_seo_meta_box() {
				//remove_meta_box( 'wpseo_meta', 'page', 'normal' );
				//remove_meta_box( 'wpseo_meta', 'post', 'normal' );
				remove_meta_box( 'wpseo_meta', 'sellerevents', 'normal' );
			}
			add_action( 'add_meta_boxes', 'so_remove_wp_seo_meta_box', 100000 );
		//}
	}

	
} //Class


