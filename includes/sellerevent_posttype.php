<?php
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

include_once("sellerevents_eventedit.php");

if ( class_exists( 'sellerevent_posttype' ) ) return;
class sellerevent_posttype {
	public function __construct() {
		global $pagenow;
		$this->create_cpt_wpsellerevents();		
		new sellerevents_eventedit();
		
		add_action('wp_ajax_runnowx', array( __CLASS__, 'runnow_actions'));
		//QUICK ACTIONS
		add_action('admin_action_wpesellerevent_create_child', array( __CLASS__, 'wpesellerevent_create_child'));
		//add_action('admin_action_wpematico_toggle_campaign', array(&$this, 'wpematico_toggle_campaign'));

		//add_action('admin_init', array( __CLASS__ ,'disable_autosave' ));
		//check & save Events
		add_filter( 'wp_insert_post_data', array( 'WPSellerEvents' , 'filter_handler'), '99', 2 );
		add_action('save_post', array( 'WPSellerEvents' , 'save_eventdata'));
		
		if( ($pagenow == 'edit.php') && (isset($_GET['post_type']) && $_GET['post_type'] == 'wpsellerevents') ) {
			add_filter('manage_posts_extra_tablenav', array( __CLASS__, 'button_todayevents') );
			// Events list
			add_filter('manage_edit-wpsellerevents_columns' , array( __CLASS__, 'set_edit_wpsellerevents_columns'));
			add_action('manage_wpsellerevents_posts_custom_column',array(__CLASS__,'custom_wpsellerevents_column'),10,2);
			add_filter('page_row_actions' , array( __CLASS__, 'wpsellerevents_quick_actions'), 10, 2);
			add_filter("manage_edit-wpsellerevents_sortable_columns", array( __CLASS__, "sortable_columns") );
			add_action( 'pre_get_posts', array( __CLASS__, 'column_orderby') );
			
			add_action('pre_get_posts', array( __CLASS__, 'query_set_only_author') );
			
			add_filter('disable_months_dropdown', array( __CLASS__, 'disable_months_dropdown'),999,2 );
			
			add_action('restrict_manage_posts', array( __CLASS__, 'custom_filters') );
			add_action('pre_get_posts', array( __CLASS__, 'query_set_custom_filters') );
			
			add_action('admin_print_styles-edit.php', array(__CLASS__,'list_admin_styles'));
			add_action('admin_print_scripts-edit.php', array(__CLASS__,'list_admin_scripts'));
		}
	}
	
	public static function disable_autosave() {
		global $post_type;
		if($post_type != 'wpsellerevents') return ;
		wp_deregister_script( 'autosave' );
	}

	public static function create_cpt_wpsellerevents( ) {
		$labels = array(
			'name' => __('Events',  WPSellerEvents :: TEXTDOMAIN ),
			'singular_name' => __('Event',  WPSellerEvents :: TEXTDOMAIN ),
			'add_new' => __('Add New Event', WPSellerEvents :: TEXTDOMAIN ),
			'add_new_item' => __('Add New Event', WPSellerEvents :: TEXTDOMAIN ),
			'edit_item' => __('Edit Event', WPSellerEvents :: TEXTDOMAIN ),
			'new_item' => __('New Event', WPSellerEvents :: TEXTDOMAIN ),
			'all_items' => __('Events List', WPSellerEvents :: TEXTDOMAIN ),
			'view_item' => __('View Event', WPSellerEvents :: TEXTDOMAIN ),
			'search_items' => __('Search Event', WPSellerEvents :: TEXTDOMAIN ),
			'not_found' =>  __('No event found', WPSellerEvents :: TEXTDOMAIN ),
			'not_found_in_trash' => __('No Event found in Trash', WPSellerEvents :: TEXTDOMAIN ), 
			'parent_item_colon' => '',
			'menu_name' => 'Seller Events',
		);
		$capabilities = array(
			'publish_posts' => 'publish_sellerevents',
			'read_post' => 'read_sellerevents',
			'read_private_posts' => 'read_private_sellerevents',
			'edit_post' => 'edit_sellerevent',
			'edit_published_posts' => 'edit_published_sellerevents',
			'edit_private_posts' => 'edit_private_sellerevents',
			'edit_posts' => 'edit_sellerevents',
			'edit_others_posts' => 'edit_others_sellerevents',
			'delete_post' => 'delete_sellerevent',
			'delete_posts' => 'delete_sellerevents',
			'delete_published_posts' => 'delete_published_sellerevents',
			'delete_private_posts' => 'delete_private_sellerevents',
			'delete_others_posts' => 'delete_others_sellerevents',
		);
		  $args = array(
			'labels' => $labels,
			'public' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'show_ui' => true, 
			'show_in_menu' => true, 
			'query_var' => true,
			'rewrite' => false,
			'capability_type' => 'page',
			'has_archive' => false, 
			'hierarchical' => true,
			'menu_position' => 7,
			'menu_icon' => WPSellerEvents :: $uri.'images/icon_20.png',
			'register_meta_box_cb' => array( 'sellerevents_eventedit', 'create_meta_boxes'),
			'map_meta_cap' => true,
			'capability_type' => array('sellerevent','sellerevents'),
			'capabilities' => $capabilities,
			'taxonomies' => array( 'eventype' ),
			'supports' => array( 'title' )  // removed 'editor'
		); 
		  register_post_type('wpsellerevents',$args);

	}

        // SELLER EVENTS LIST
        // SELLER EVENTS COLUMNS
        public static function set_edit_wpsellerevents_columns($columns) { //this function display the columns headings
            //add_filter( 'editable_slug', array(__CLASS__,'inline_custom_fields'),999,1);
            $new_columns = array(
                'cb' => '<input type="checkbox" />',
                'title' => __('Title', WPSellerEvents :: TEXTDOMAIN),
                'date' => __('Published', WPSellerEvents :: TEXTDOMAIN),
                'start' => __('Start Date', WPSellerEvents :: TEXTDOMAIN),
                'cron' => __('Scheduled', WPSellerEvents :: TEXTDOMAIN),
//                'active' => __('Active', WPSellerEvents :: TEXTDOMAIN),
                'event_status' => __('Status', WPSellerEvents :: TEXTDOMAIN),
                'seller' => __('Salesman', WPSellerEvents :: TEXTDOMAIN),
                'client' => __('Client', WPSellerEvents :: TEXTDOMAIN),
                'taxonomy-eventype' => __('Event Type', WPSellerEvents :: TEXTDOMAIN),
            );
            return $new_columns;
        }
        // Make these columns sortable
        public static function sortable_columns($columns) {
			$custom = array(
				'start' => 'startdate',
                'cron' => 'cron',
				//'active' => 'active',
				//'event_status' => 'event_status',
                'seller' => 'seller',
                'client' => 'client',
				'taxonomy-eventype' => 'eventype'
			);
			return wp_parse_args($custom, $columns);
        }
		public static function column_orderby($query ) {
			global $pagenow, $post_type;
			$orderby = $query->get( 'orderby');
			if( 'edit.php' != $pagenow || empty( $orderby ) )
				return;
			switch($orderby) {
				case 'startdate':
					$meta_group = array('key' => 'fromdate','type' => 'numeric');
					$query->set( 'meta_query', array( 'sort_column'=>'startdate', $meta_group ) );
					$query->set( 'meta_key','fromdate' );
					$query->set( 'orderby','meta_value_num' );

					break;
				case 'cron':
					$meta_group = array('key' => 'cronnextrun','type' => 'numeric');
					$query->set( 'meta_query', array( 'sort_column'=>'cron', $meta_group ) );
					$query->set( 'meta_key','cronnextrun' );
					$query->set( 'orderby','meta_value_num' );

					break;
				case 'event_status':
					$meta_group = array('key' => 'event_status','type' => 'string');
					$query->set( 'meta_query', array( 'sort_column'=>'event_status', $meta_group ) );
					$query->set( 'meta_key','event_status' );
					$query->set( 'orderby','meta_value' );

					break;
				case 'seller':
					$meta_group = array('key' => 'seller','type' => 'string');
					$query->set( 'meta_query', array( 'sort_column'=>'seller', $meta_group ) );
					$query->set( 'meta_key','seller' );
					$query->set( 'orderby','meta_value' );

					break;

				case 'client':
					$meta_group = array('key' => 'client','type' => 'string');
					$query->set( 'meta_query', array( 'sort_column'=>'client', $meta_group ) );
					$query->set( 'meta_key','client' );
					$query->set( 'orderby','meta_value' );

					break;

				default:
					break;
			}
		} 
		
	public static function custom_wpsellerevents_column( $column, $post_id ) {
		$wpsecfg = get_option( WPSellerEvents :: OPTION_KEY);
		$event_data = WPSellerEvents :: get_event ( $post_id );
		switch ( $column ) {
		  case 'status':
			echo $event_data['event_posttype']; 
			break;
		  case 'start':
			echo date_i18n(  $wpsecfg['dateformat'] .' '.get_option( 'time_format' ), $event_data['fromdate'] ); 
			if(current_user_can('administrator')) {
				echo '<br>'.$event_data['fromdate'] ; 
			}
			break;
		  case 'cron':
			$cronnextrun = $event_data['cronnextrun'];
			$activated = $event_data['activated'];
			if ($activated) {
				// status = Alarm Active
				if ( $cronnextrun <= current_time('timestamp') ) {
					if( 0 < (int)$event_data['runtime'] ) {
						$status = __('Sent', WPSellerEvents :: TEXTDOMAIN );
					}elseif( 0 < (int)$event_data['starttime']) {
						$status = __('Sending', WPSellerEvents :: TEXTDOMAIN );
					}elseif( 0 == (int)$event_data['runtime'] && 0 == (int)$event_data['starttime'] ) {
						$status = __('Missed', WPSellerEvents :: TEXTDOMAIN );
					}				
				}else{
					$status = __('Alert on', WPSellerEvents :: TEXTDOMAIN ) .' '. date_i18n( $wpsecfg['dateformat'] .' '.get_option( 'time_format' ), $cronnextrun);
				}

			} else {
				if ( $cronnextrun <= current_time('timestamp') && 0 < (int)$event_data['runtime'] ) {  
					$status = __('Sent', WPSellerEvents :: TEXTDOMAIN );
				}else{
					$status = __('Inactive', WPSellerEvents :: TEXTDOMAIN );
				}
			}
			//$status = $event_data['cronnextrun'].'-'.current_time('timestamp').'--'.$event_data['starttime'];

			echo $status;
			break;
		  case 'event_status':
			echo WPSellerEvents :: $event_statuses[ $event_data['event_status'] ];
			break;
		  case 'seller':
			echo get_post_meta($post_id, 'seller',true); 
			if(current_user_can('administrator')) {
				echo '<br>Author ID: '; 
				the_author_meta('ID');
			}
			break;
		  case 'client':
			$name = get_post_meta($post_id, 'client',true); 
			$client = get_page_by_title($name, OBJECT, 'wpse_client');
			echo "<a href='".get_edit_post_link($client->ID)."'>".$name."</a>";
			break;
		}
	}
    
	public static function disable_months_dropdown($disabled, $typenow) {
		return true;
	}
	


	public static function button_todayevents($which) {
		global $post_type, $typenow, $wp_query, $current_user, $pagenow, $wpsecfg;
		if($post_type != 'wpsellerevents') return;
		if ( 'top' === $which && !is_singular() ) {
		//count events today
		$current_user = wp_get_current_user();
		$user_info = get_userdata($current_user->ID);
		$my_role = implode(', ',$user_info->roles);

		$date_now = date_i18n($wpsecfg['dateformat']);
		$date_temp = 0;
		$total_events_today = 0;
		$args=array(
			'order' => 'ASC', 
			'orderby' => 'title', 
			'post_type' => 'wpsellerevents',
			'post_status' => 'publish'
		);
		$my_query = null;
		$my_query = new WP_Query($args);
		if( $my_query->have_posts() ) {
			while ($my_query->have_posts()) : $my_query->the_post(); 
					$event_data = WPSellerEvents :: get_event (get_the_ID()); 
					$date_temp = date_i18n($wpsecfg['dateformat'], $event_data['fromdate']);
					//if( ($my_role == 'wpse_seller' && $current_user->ID == $event_data['seller_id']) || $my_role == 'administrator' || $my_role=='wpse_manager'){
					if( (current_user_can('wpse_seller') && $current_user->ID == $event_data['seller_id']) || current_user_can('administrator') || current_user_can('wpse_manager') ){
						//if(substr($date_temp,0,10) == substr($date_now,0,10)){
						if($date_temp == $date_now){
								$total_events_today+=1;

						}								
					}
			endwhile;
		}

		?>
		<!--FILFER EVENTS TODAY-->
		<div class="alignleft actions">
<?php 	if( current_user_can('administrator') ) : ?>
			<input type="submit" name="filter_action"  todaydate="<?php echo date_i18n($wpsecfg['dateformat']); ?>"  style="background-color:red; color:white;" id="filter_today_event" class="button" value="<?php echo __('There are', WPSellerEvents :: TEXTDOMAIN). ' ' . $total_events_today.' '. __('events for today', WPSellerEvents :: TEXTDOMAIN); ?>">
<?php	else: ?>
			<input type="submit" name="filter_action"  todaydate="<?php echo date_i18n($wpsecfg['dateformat']); ?>"  style="background-color:red; color:white;" id="filter_today_event" class="button" value="Eventos para Hoy">
<?php	endif; ?>
		<input type="hidden" name="filter_action_todaydate" value="no" id="filter_action_todaydate">
		</div>
<?php
		}
	}
	
	public static function custom_filters($options) {	
		global $typenow, $wp_query, $current_user, $pagenow, $wpsecfg;
		if(is_null($wpsecfg)) $wpsecfg = get_option( WPSellerEvents :: OPTION_KEY);
		if($pagenow=='edit.php' && is_admin() && current_user_can('edit_sellerevents') && $typenow=='wpsellerevents') {
			if(!current_user_can('wpse_seller')) : ?>
				<?php
				$args= array(
					'key'		=> 'seller', 
					'type'		=> 'wpsellerevents', 
					'status'	=> 'publish', 
					'filter'	=> true, 
					'order_by'	=> 'name',
					'order'		=> 'ASC',
				);
				$allsellers = WPSellerEvents::get_meta_values($args);
				//$allsellers = get_users( array( 'role' => 'wpse_seller' ) );
				// Array of stdClass objects.
				//register script
					
				?>
				<!--<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.0.272/jspdf.min.js"></script>
				<script type="text/javascript" src="https://rawgit.com/someatoms/jsPDF-AutoTable/master/dist/jspdf.plugin.autotable.js"></script>-->			
				<!--<script></script>-->
				<div style="display: inline-block;"><select id="seller" name="seller">
						<option value="0" class="seller-item"><?php _e('All Sellers', WPSellerEvents :: TEXTDOMAIN ); ?></option>
					<?php
				$seller_id = (isset($_GET['seller']) && !empty($_GET['seller']) ) ? $_GET['seller'] : '';
				if(!empty($seller_id)) $seller_id = sanitize_text_field($seller_id);
				foreach ( $allsellers as $user ) {
					echo '<option '.  selected($user, $seller_id, 1).' value="' . $user . '" class="seller-item">' . esc_html( $user ) . '</option>';
				}
				?></select>
				</div>
			<?php
			endif;
		}
		
		$args= array(
			'key'		=> 'client', 
			'type'		=> 'wpsellerevents', 
			'status'	=> 'publish', 
			'filter'	=> true, 
			'order_by'	=> 'name',
			'order'		=> 'ASC',
		);
		$allcustomers = WPSellerEvents::get_meta_values($args);
		//$allcustomers = get_posts( array( 'post_type'=>'wpse_client', 'posts_per_page' => -1 ) );
		// Array of stdClass objects.
		?><div style="display: inline-block;"><select id="client" name="client">
			<option value="0" class="client-item"><?php _e('All Clients', WPSellerEvents :: TEXTDOMAIN ); ?></option>
		<?php
		$client_id = (isset($_GET['client']) && !empty($_GET['client']) ) ? $_GET['client'] : '';
		if(!empty($client_id)) $client_id = sanitize_text_field($client_id);
		foreach ( $allcustomers as $user ) {
			echo '<option '.  selected($user, $client_id, 1).' value="' . $user . '" class="user-item">' . esc_html( $user ) . '</option>';
		}
			?></select>
		</div>	
		<div style="display: inline-block;">
		<?php   // event_status filter
		$allevent_status = WPSellerEvents :: $event_statuses;
		$event_status = (isset($_GET['event_status']) && !empty($_GET['event_status']) ) ? $_GET['event_status'] : '';
		$statuses_select = '<select id="event_status" name="event_status">';
		$statuses_select .= '<option '.  selected('', $event_status,0).' value="" class="event_status-item">' . __('All Statuses', WPSellerEvents :: TEXTDOMAIN ) . '</option>';
		foreach ( $allevent_status as $key => $vstatus ) {
			$statuses_select .= '<option '.  selected($key, $event_status,0).' value="' . $key . '" class="event_status-item">' . $vstatus . '</option>';
		}
		$statuses_select .= '</select>';
		echo $statuses_select;
		?>
		</div>
		<?php
		$byrange = (isset($_GET['byrange']) && !empty($_GET['byrange']) ) ? $_GET['byrange'] : 'no';
		$datestart	= (!isset($_GET['datestart']) ) ? current_time('timestamp')  : $_GET['datestart'];
		$datestart	= (is_int( $datestart) ) ? $datestart : WPSellerEvents::date2time($datestart, $wpsecfg['dateformat'].' '.get_option('time_format') );
		$dateend	= (!isset($_GET['dateend']) ) ? current_time('timestamp')  : $_GET['dateend'];
		$dateend	= (is_int( $dateend) ) ? $dateend : WPSellerEvents::date2time($dateend, $wpsecfg['dateformat'].' '.get_option('time_format') );

		?><div style="display: inline-block;vertical-align: top;margin: 1px 8px 0px 0px;">
			<input name="range_action" id="queryrange" class="button" value="<?php _e('Date Range', WPSellerEvents :: TEXTDOMAIN ); ?>" type="button"><br/>
			<input name="byrange" id="byrange" value="<?php echo $byrange; ?>" type="hidden">
			<span style="position: absolute; background: #5D9F81; padding: 3px; width: 152px;" class="<?php echo ($byrange=='yes' && $_GET['filter_action_todaydate']!="yes") ? '' : 'hidden'; ?> daterange">
			<input style="width: 100%;" name="datestart" id="datestart" class="fieldate" value="<?php echo date_i18n( $wpsecfg['dateformat'] .' '.get_option( 'time_format' ), $datestart ); 	?>" type="text"><br/>
			<input style="width: 100%;" name="dateend" id="dateend" class="fieldate" value="<?php echo date_i18n( $wpsecfg['dateformat'] .' '.get_option( 'time_format' ), $dateend ); 	?>" type="text">
			</span>

		</div>
		<!--pdf jspdf-->
		<input type="button" id="printButtonPDF" class="button right" value="<?php _e('Print PDF',WPSellerEvents :: TEXTDOMAIN); ?>">
		<?php

	}
	

	// Show only posts and media related to logged in author
	public static function query_set_custom_filters( $wp_query ) {
		global $current_user, $pagenow, $typenow, $wpsecfg;
		if(is_null($wpsecfg)) $wpsecfg = get_option( WPSellerEvents :: OPTION_KEY);
		if($pagenow=='edit.php' && is_admin() && $typenow=='wpsellerevents') {
			$seller_id = (isset($_GET['seller']) && !empty($_GET['seller']) ) ? $_GET['seller'] : '';
			$filtering = false;
			if(!empty($seller_id)) { 
				$filtering = true;				
				$seller_id = sanitize_text_field($seller_id);
				$meta_query[] =	
					array(
						'key' => 'seller',
						'value' =>  $seller_id
					);
			}

			$client_id = (isset($_GET['client']) && !empty($_GET['client']) ) ? $_GET['client'] : '';
			if(!empty($client_id)) {
				$filtering = true;				
				$client_id = sanitize_text_field($client_id);
				$meta_query[] =	
					array(
						'key' => 'client',
						'value' =>  $client_id
					);
			}

			$event_status = (isset($_GET['event_status']) && !empty($_GET['event_status']) ) ? $_GET['event_status'] : '';
			if(!empty($event_status)) {
				$filtering = true;				
				$event_status = sanitize_text_field($event_status);
				$meta_query[] =	
					array(
						'key' => 'event_status',
						'value' =>  $event_status
					);
			}

			$byrange = (isset($_GET['byrange']) && !empty($_GET['byrange']) ) ? $_GET['byrange'] : 'no';
			if($byrange == 'yes'){
				$datestart = (isset($_GET['datestart']) && !empty($_GET['datestart']) ) ? WPSellerEvents::date2time($_GET['datestart'], $wpsecfg['dateformat'].' '.get_option('time_format') ) : 0;
				$dateend = (isset($_GET['dateend']) && !empty($_GET['dateend']) ) ? WPSellerEvents::date2time($_GET['dateend'], $wpsecfg['dateformat'].' '.get_option('time_format') ) : current_time('timestamp');
				if(!empty($datestart)) {
					$filtering = true;				
					$datestart = sanitize_text_field($datestart);
					$meta_query[] =	
						array(
							'key' => 'fromdate',
							'value' =>  (int)$datestart,
							'compare' => '>=',
							'type' => 'NUMERIC'
						);
					$meta_query[] =	
						array(
							'key' => 'fromdate',
							'value' =>  (int)$dateend,
							'compare' => '<=',
							'type' => 'NUMERIC'
					);
				}
			}
			if(	$filtering ) {
				$wp_query->set( 'meta_query', $meta_query);
//				add_filter('views_edit-wpsellerevents',  array(__CLASS__,'fix_post_counts'));				
			}
		}
	}

	

	// Show only posts and media related to logged in author
	public static function query_set_only_author( $wp_query ) {
		global $current_user;
		if( is_admin() && !current_user_can('edit_others_sellerevents') ) {
			$wp_query->set( 'author', $current_user->ID );
			add_filter('views_edit-wpsellerevents',  array(__CLASS__,'fix_post_counts'));
		}
	}

	// Fix post counts
	public static function fix_post_counts($views) {
		global $current_user, $wp_query;
		unset($views['mine']);
		$types = array(
			array( 'status' =>  NULL ),
			array( 'status' => 'publish' ),
			array( 'status' => 'draft' ),
			array( 'status' => 'pending' ),
			array( 'status' => 'trash' )
		);
		foreach( $types as $type ) {
			$query = array(
				'author'      => $current_user->ID,
				'post_type'   => 'wpsellerevents',
				'post_status' => $type['status']
			);
			$result = new WP_Query($query);
			if( $type['status'] == NULL ):
				$class = ($wp_query->query_vars['post_status'] == NULL) ? ' class="current"' : '';
				$views['all'] = sprintf(__('<a href="%s"'. $class .'>All <span class="count">(%d)</span></a>', 'all'),
					admin_url('edit.php?post_type=wpsellerevents'),
					$result->found_posts);
			elseif( $type['status'] == 'publish' ):
				$class = ($wp_query->query_vars['post_status'] == 'publish') ? ' class="current"' : '';
				$views['publish'] = sprintf(__('<a href="%s"'. $class .'>Published <span class="count">(%d)</span></a>', 'publish'),
					admin_url('edit.php?post_status=publish&post_type=wpsellerevents'),
					$result->found_posts);
			elseif( $type['status'] == 'draft' ):
				$class = ($wp_query->query_vars['post_status'] == 'draft') ? ' class="current"' : '';
				$views['draft'] = sprintf(__('<a href="%s"'. $class .'>Draft'. ((sizeof($result->posts) > 1) ? "s" : "") .' <span class="count">(%d)</span></a>', 'draft'),
					admin_url('edit.php?post_status=draft&post_type=wpsellerevents'),
					$result->found_posts);
			elseif( $type['status'] == 'pending' ):
				$class = ($wp_query->query_vars['post_status'] == 'pending') ? ' class="current"' : '';
				$views['pending'] = sprintf(__('<a href="%s"'. $class .'>Pending <span class="count">(%d)</span></a>', 'pending'),
					admin_url('edit.php?post_status=pending&post_type=wpsellerevents'),
					$result->found_posts);
			elseif( $type['status'] == 'trash' ):
				$class = ($wp_query->query_vars['post_status'] == 'trash') ? ' class="current"' : '';
				$views['trash'] = sprintf(__('<a href="%s"'. $class .'>Trash <span class="count">(%d)</span></a>', 'trash'),
					admin_url('edit.php?post_status=trash&post_type=wpsellerevents'),
					$result->found_posts);
			endif;
		}
		return $views;
	}

	
    public static function list_admin_styles(){
		wp_enqueue_style('jquery-datetimepicker',WPSellerEvents :: $uri .'css/jquery.datetimepicker.css');	
//		add_action('admin_head', array( &$this ,'events_admin_head_style'));
	}
	public static function list_admin_scripts(){
		$slug = 'wpsellerevents';
		if ( ( isset( $_GET['page'] ) && $_GET['page'] == $slug ) || ( isset( $_GET['post_type'] ) && $_GET['post_type'] == $slug ) ) {
			wp_register_script('jquery-datetimepicker', WPSellerEvents::$uri .'js/jquery.datetimepicker.js', array('jquery'));
			wp_enqueue_script('jquery-datetimepicker');

			//register jspdf and autotable
			wp_register_script('seller_events_jspdf',WPSellerEvents::$uri.'js/jspdf.min.js',array('jquery'));
			wp_enqueue_script('seller_events_jspdf');

			wp_register_script('seller_events_autotable',WPSellerEvents::$uri.'js/jspdf.plugin.autotable.js',array('jquery','seller_events_jspdf'));
			wp_enqueue_script('seller_events_autotable');

			wp_register_script('seller_print_report_wp',WPSellerEvents::$uri.'js/print_report_wp.js',array('jquery','seller_events_jspdf','seller_events_autotable'));
			wp_enqueue_script('seller_print_report_wp');


				
    		add_action('admin_head', array( __CLASS__ ,'events_list_admin_head'));
		}
	}

    
	public static function runnow_actions() {
		$err_message = "";
		$response = array();
		$response['success'] = false;
		if(!isset($_POST['nowaction'])) {
            $err_message .= __('The action must exist.', WPSellerEvents :: TEXTDOMAIN ).'<br />';
        }else{
            $nowaction = $_POST['nowaction'];
        }
		if(!isset($_POST['event_ID'])) {
            $err_message .= __('Event ID must exist.', WPSellerEvents :: TEXTDOMAIN ).'<br />';
        }else{
            $event_ID = $_POST['event_ID'];
        }
		
        switch ($nowaction) {
            case 'toggle':
				$eventdata = WPSellerEvents :: get_event( $event_ID );		
				$eventdata['activated']	= !$eventdata['activated'];
				WPSellerEvents :: update_event ( $event_ID, $eventdata );
				$response['message'] = ($eventdata['activated']) ? __('Event activated',WPSellerEvents :: TEXTDOMAIN) : __('Event Deactivated',  WPSellerEvents :: TEXTDOMAIN);
                break;

            case 'create_child':
                break;

            default:
                break;
        }
		if($err_message !="" ) $response['message'] = $err_message;
		else $response['success'] = true;

		wp_send_json($response); 
    }
    
	public static function events_list_admin_head() {
		global $post, $post_type, $wpsecfg, $locale;
		if($post_type != 'wpsellerevents') return $post->ID;
		if(is_null($wpsecfg)) $wpsecfg = get_option( WPSellerEvents :: OPTION_KEY);
		?><style type="text/css">br {display: inherit !important;}</style>
		<script type="text/javascript" language="javascript">
			jQuery(document).ready(function($){
			run_now = function(nowaction, event_ID) {
				$('html').css('cursor','wait');
				$("div[id=fieldserror]").remove();
				msgdev="<p><img width='16' src='<?php echo admin_url('/images/wpspin_light.gif'); ?>'> <span style='vertical-align: top;margin: 10px;'><?php _e('Wait Please...', WPSellerEvents :: TEXTDOMAIN ); ?></span></p>";
				$(".subsubsub").before('<div id="fieldserror" class="updated fade">'+msgdev+'</div>');
				var data = {
					event_ID: event_ID ,
					nowaction: nowaction,
					action: "runnowx"
				};
				$.post(ajaxurl, data, function(response) {  //si todo ok devuelve LOG sino 0
					$('#fieldserror').remove();
					if( !response.success ){
						$(".subsubsub").before('<div id="fieldserror" class="error fade">'+response.message+'</div>');
					}else{
						$(".subsubsub").before('<div id="fieldserror" class="updated fade">'+response.message+'</div>');
					}
					$('html').css('cursor','auto');
				});
			}
			$(document).on("click", '#queryrange', function(event) { 
				if($('.daterange').is(':hidden')){
					$('#byrange').val('yes');
					$('.daterange').fadeIn();
				}else{
					$('#byrange').val('no');
					$('.daterange').fadeOut();
				}
			});

			//click event filter_today_event
			$(document).on('click','#filter_today_event',function(event){
				mydate = $(this).attr('todaydate');
				$("#datestart").val(mydate+" 0:00 am");
				$("#dateend").val(mydate+" 11:59 pm");
				$('#byrange').val('yes');
				$("#filter_action_todaydate").val("yes");
			});
			
			<?php
			$objectL10n = (object)array(
				'lang'			=> substr($locale, 0, 2),
				'UTC'			=> get_option( 'gmt_offset' ),
				'timeFormat'    => get_option( 'time_format' ),
				'dateFormat'    => sellerevents_eventedit :: date_format_php_to_js( $wpsecfg['dateformat'] ),
				'printFormat'   => sellerevents_eventedit :: date_format_php_to_js( $wpsecfg['dateformat'] ).' '.get_option( 'time_format' ),
				'firstDay'      => get_option( 'start_of_week' ),
			);
			echo "$('#datestart').datetimepicker({
				lang: '{$objectL10n->lang}',
				dayOfWeekStart: {$objectL10n->firstDay},
				formatTime:'{$objectL10n->timeFormat}',
				format:'{$objectL10n->printFormat}',
				formatDate:'{$objectL10n->dateFormat}'
			});";
			echo "$('#dateend').datetimepicker({
				lang: '{$objectL10n->lang}',
				dayOfWeekStart: {$objectL10n->firstDay},
				formatTime:'{$objectL10n->timeFormat}',
				format:'{$objectL10n->printFormat}',
				formatDate:'{$objectL10n->dateFormat}'
			});";
			?>
			});			
 		</script>
		<?php
	}	

	public static function copy_duplicate_event($post, &$status = '', $parent_id = '') {
		// We don't want to clone revisions
		if ($post->post_type != 'wpsellerevents') return;
		$prefix = "";
		$suffix = __("(Child)",  WPSellerEvents :: TEXTDOMAIN) ;
		if(!empty($prefix))    $prefix.= " ";
		if(!empty($suffix))    $suffix = " ".$suffix;
		if (empty($status))	   $status = 'publish';
		if (empty($parent_id)) $parent_id = $post->parent_id;
		
		$new_post = array(
		'menu_order' => $post->menu_order,
		'guid' => $post->guid,
		'comment_status' => $post->comment_status,
		'ping_status' => $post->ping_status,
		'pinged' => $post->pinged,
		'post_author' => @$post->author,
		'post_content' => $post->post_content,
		'post_excerpt' => $post->post_excerpt,
		'post_mime_type' => $post->post_mime_type,
		'post_parent' => $parent_id,
		'post_password' => $post->post_password,
		'post_status' => $status,
		'post_title' => $prefix.$post->post_title.$suffix,
		'post_type' => $post->post_type,
		'to_ping' => $post->to_ping, 
		'post_date' => $post->post_date,
		'post_date_gmt' => get_gmt_from_date($post->post_date)
		);	

		$new_post_id = wp_insert_post($new_post);

		$post_meta_keys = get_post_custom_keys($post->ID);
		if (!empty($post_meta_keys)) {
			foreach ($post_meta_keys as $meta_key) {
				$meta_values = get_post_custom_values($meta_key, $post->ID);
				foreach ($meta_values as $meta_value) {
					$meta_value = maybe_unserialize($meta_value);
					add_post_meta($new_post_id, $meta_key, $meta_value);
				}
			}
		}
		$eventdata = WPSellerEvents :: get_event( $new_post_id );		
		//reset some custom fields
		$eventdata['fromdate']	= current_time('timestamp');
		$eventdata['todate']	= current_time('timestamp')+(3600);  //3600*24*7 = 7 dias
		$eventdata['event_obs'] = array( 'text'=>array(),'date'=>array() );

		$event = apply_filters('wpse_check_eventdata', $_POST);
		WPSellerEvents :: update_event( $new_post_id, $eventdata );

		return $new_post_id;
	}

	public static function wpesellerevent_create_child($status = ''){
		if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'wpesellerevent_create_child' == $_REQUEST['action'] ) ) ) {
			wp_die(__('No event ID has been supplied!',  WPSellerEvents :: TEXTDOMAIN));
		}

		// Get the original post
		$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
		$post = get_post($id);

		// Copy the post and insert it with this parent_id
		if (isset($post) && $post!=null) {
			$new_id = self :: copy_duplicate_event($post, $status, $post->ID ); 

			if ($status == ''){
				// Redirect to the post list screen
				wp_redirect( admin_url( 'edit.php?post_type='.$post->post_type) );
			} else {
				// Redirect to the edit screen for the new draft post
				wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
			}
			exit;

		} else {
			$post_type_obj = get_post_type_object( $post->post_type );
			wp_die(esc_attr(__('Copy event failed, could not find original:',  WPSellerEvents :: TEXTDOMAIN)) . ' ' . $id);
		}
	}

	public static function wpesellerevent_send_now($status = ''){
		if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'wpesellerevent_create_child' == $_REQUEST['action'] ) ) ) {
			wp_die(__('No event ID has been supplied!',  WPSellerEvents :: TEXTDOMAIN));
		}
		// Get the original post
		$post_id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
		//$post = get_post($id);
		$event = WPSellerEvents :: get_event( $post_id );
		$activated = $event['activated'];
		$cronnextrun = $event['cronnextrun'];
		
		WPSellerEvents :: wpsellerevents_dojob( $post_id );
		
		if ( $cronnextrun <= current_time('timestamp') ) 
			$event['runtime'] = $event['starttime'] = 0;   // reset vars to allow send mail with cron
		WPSellerEvents :: update_event( $post_id, $event );

	}
	
	//change actions from custom post type list
	public static function wpsellerevents_quick_actions( $actions ) {
		global $post;
		$wpsecfg = get_option(WPSellerEvents :: OPTION_KEY);
		$wpsecfg = apply_filters('wpse_check_options', $wpsecfg);
		if( $post->post_type == 'wpsellerevents' ) {
			$post_type_object = get_post_type_object( $post->post_type );
			$can_edit_post = current_user_can( 'edit_post', $post->ID );
//	//		unset( $actions['edit'] );
//			unset( $actions['view'] );
//	//		unset( $actions['trash'] );
//	//		unset( $actions['inline hide-if-no-js'] );
//			unset( $actions['clone'] );
//			unset( $actions['edit_as_new_draft'] );
			$actions = array();
			if ( $can_edit_post && 'trash' != $post->post_status ) {
				$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '" title="' . esc_attr( __( 'Edit this item' ) ) . '">' . __( 'Edit' ) . '</a>';
//                    $actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="' . esc_attr( __( 'Edit this item inline' ) ) . '">' . __( 'Quick&nbsp;Edit' ) . '</a>';
			}
			if ( current_user_can( 'delete_post', $post->ID ) ) {
				if ( 'trash' == $post->post_status )
					$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash' ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ) . "'>" . __( 'Restore' ) . "</a>";
				elseif ( EMPTY_TRASH_DAYS )
					$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash' ) . "</a>";
				if ( 'trash' == $post->post_status || !EMPTY_TRASH_DAYS )
					$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently' ) . "</a>";
			}
			if ( 'trash' != $post->post_status ) {
				$event_data = WPSellerEvents :: get_event( $post->ID );
				
				//++++++Toggle
/*				$acnow = (bool)$event_data['activated'];
				$atitle = ( $acnow ) ? esc_attr(__("Deactivate this event", WPSellerEvents :: TEXTDOMAIN)) : esc_attr(__("Activate schedule", WPSellerEvents :: TEXTDOMAIN));
				$alink = ($acnow) ? __("Deactivate", WPSellerEvents :: TEXTDOMAIN): __("Activate",WPSellerEvents :: TEXTDOMAIN);
				$actions['toggle'] = '<a href="JavaScript:run_now(\'toggle\',' . $post->ID . ');" title="' . $atitle . '">' .  $alink . '</a>';
*/
				//++++++Create Child
				$action = '?action=wpesellerevent_create_child&amp;post='.$post->ID;
				$actions['wpesellerevent_create_child'] = '<a href="'. admin_url( "admin.php". $action ).'" title="' . esc_attr(__("Create Child Event", WPSellerEvents :: TEXTDOMAIN)) . '">' .  __('Create Child Event', WPSellerEvents :: TEXTDOMAIN) . '</a>';
			}
		}
		return $actions;
	}
	//END SELLER EVENTS LIST

}