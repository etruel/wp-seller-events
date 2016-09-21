<?php 
// don't load directly 
if ( !defined('ABSPATH') )  {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'sellerevents_eventedit' ) ) return;

class sellerevents_eventedit {
	public function __construct() {
		global $pagenow;
		add_action('wp_ajax_getEvenTypeAttr', array( __CLASS__, 'getEvenTypeAttr'));
		add_action('wp_ajax_getUserContacts', array( __CLASS__, 'getUserContacts'));
		add_action('wp_ajax_checkfields', array( __CLASS__, 'CheckFields'));
 		if( ($pagenow == 'post-new.php' || $pagenow == 'post.php') ) {
			add_action('admin_print_styles-post.php', array( __CLASS__ ,'admin_styles'));
			add_action('admin_print_styles-post-new.php', array( __CLASS__ ,'admin_styles'));
			add_action('admin_print_scripts-post.php', array( __CLASS__ ,'admin_scripts'));
			add_action('admin_print_scripts-post-new.php', array( __CLASS__ ,'admin_scripts'));  	
		}
	}

	public static function create_meta_boxes() {
		global $post,$event_data, $cfg;
		$event_data = WPSellerEvents :: get_event($post->ID);
		$event_data = apply_filters('wpse_check_eventdata', $event_data);
		$cfg = get_option(WPSellerEvents :: OPTION_KEY);
		$cfg = apply_filters('wpse_check_options', $cfg);
		
		// Remove Custom Fields Metabox
		//remove_meta_box( 'postcustom','wpsellerevents','normal' ); 
	//	add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
		
		if(!current_user_can('wpse_seller')){
			add_meta_box( 'seller-box', __('Salesman', WPSellerEvents :: TEXTDOMAIN ), array(  __CLASS__  ,'seller_box' ),'wpsellerevents','side', 'default' );
		}
		add_meta_box( 'status-box', __('Event Status', WPSellerEvents :: TEXTDOMAIN ), array(  __CLASS__ ,'status_box' ),'wpsellerevents','side', 'high' );
		add_meta_box( 'obs-box', __('Observations', WPSellerEvents :: TEXTDOMAIN ), array( __CLASS__  ,'obs_box' ),'wpsellerevents','normal', 'default' );
		add_meta_box( 'options-box', __('Options for this event', WPSellerEvents :: TEXTDOMAIN ), array(  __CLASS__ ,'options_box' ),'wpsellerevents','normal', 'default' );
	}		

			//*************************************************************************************
	public static function status_box( $post ) {  
		global $post, $event_data, $cfg, $current_user;
		$event_status = $event_data['event_status'];
		$allevent_status = WPSellerEvents :: $event_statuses;
		?>
		<select id="event_status" name="event_status">
		<?php
			foreach ( $allevent_status as $key => $vstatus ) {
				echo '<option '.  selected($key, $event_status, 1).' value="' . $key . '" class="event_status-item">' . $vstatus . '</option>';
			}
		?></select>
		<?php
	}
		
		
			//*************************************************************************************
	public static function seller_box( $post ) {  
		global $post, $event_data, $cfg, $current_user;
		$seller_id = $event_data['seller_id'];
		if(isset($seller_id) && ($seller_id>0) ) { // if already set takes the value 
			$seller = get_userdata( $seller_id );
		}else {
			if(!current_user_can('wpse_seller'))	 { // if current user is not a seller shows the list to choice, else shows current user
				$seller = (object)array('display_name'=> __('Select the seller from the list.', WPSellerEvents :: TEXTDOMAIN ) );
			}else{
				$seller_id = get_current_user_id();
				//$seller = get_user_by( 'ID', get_current_user_id() );
				//$seller = get_userdata( $seller_id );
				$seller = $current_user;
			}
		}
		?>
		<div class="event-seller">
			<div id="seller_ico" class="mya4_sprite <?php echo (isset($seller_id) && ($seller_id>0)) ?'tab_inactive_accSummary' : 'tab_active_accSummary' ?>" style="float: left;margin-right: 8px;"></div>
			<input type="hidden" name="seller_id" id="seller_id" value="<?php echo $seller_id; ?>">
			<div id="seller_name">
				<?php 
				echo esc_html( $seller->display_name );
				if(isset($seller_id) && ($seller_id>0) ) echo ' :: '. esc_html( $seller->user_email ); 
				?>
			</div>
		</div>
		<?php 
		if(!current_user_can('wpse_seller')) : ?>
		<label onclick="jQuery('#sellers-list').fadeToggle();" class="button add" id="add_seller"> <?php _e('Search Salesman', WPSellerEvents :: TEXTDOMAIN); ?>.</label>
		<div id="sellers-list" class="Sellers" <?php echo (isset($seller_id) && ($seller_id>0)) ?'style="display:none;"' : '' ?>>
			<div class="header-seller-list">
				<div class="right srchFilterOuter" style="width:234px;">
				<div style="float:left;margin-left:2px;">
					<input id="se_searchtext" name="se_searchtext" class="srchbdr0" type="text" value='' style="width:200px;">
				</div>
				<div class="srchSpacer"></div>
				<div id="productsearch" class="mya4_sprite searchIco" style="margin-top:6px;float: left;"></div>
			</div>

			</div><div style="clear: both;"></div>
			<?php
			$allsellers = get_users( array( 'role' => 'wpse_seller' ) );
			// Array of stdClass objects.
			?><ul><?php
			foreach ( $allsellers as $user ) {
				echo '<li id="seller-' . $user->ID . '" class="seller-item">' . esc_html( $user->display_name ) . '</li>';
			}
			?></ul>
		</div>
		<?php
		endif;
	}
	
		//*************************************************************************************
	public static function options_box( $post ) {  
		global $post, $event_data, $cfg;
		$fromdate = $event_data['fromdate'];
		$todate = $event_data['todate'];
		$quantity = $event_data['quantity'];
		$period = $event_data['period'];
		$activated = $event_data['activated'];
		$cron = $event_data['cron'];
		$cronnextrun = $event_data['cronnextrun'];
		$client_id = $event_data['customer_id'];
		if(isset($client_id) && ($client_id>0) ) {
			$customer = get_post( $client_id );
			if(is_object($customer)) $user_contacts = get_post_meta($client_id, 'user_contacts', TRUE) ;
		}else {
			$customer = (object)array('display_name'=> __('Select the customer from the list.', WPSellerEvents :: TEXTDOMAIN ) );
		}
		if(!isset($user_contacts)) {
			$user_contacts = array();
			$contact_name = __('Client contacts not defined.', WPSellerEvents :: TEXTDOMAIN );
		}else {
			$contact_name = $event_data['contact_name'];
		}
		wp_nonce_field( 'edit-event', 'wpsellerevents_nonce' ); 
		?>
		<p><b><?php echo '<label for="fromdate">' . __('From Date', WPSellerEvents :: TEXTDOMAIN ) . '</label>'; ?>: </b>
			<input class="fieldate" type="text" name="fromdate" value="<?php 
				echo date_i18n( $cfg['dateformat'] .' '.get_option( 'time_format' ), $fromdate ); 				
				?>" id="fromdate"/>&nbsp; &nbsp; <b><?php echo '<label for="todate">' . __('To Date', WPSellerEvents :: TEXTDOMAIN ) . '</label>'; ?>: </b>
			<input class="fieldate" type="text" name="todate" value="<?php 
				echo date_i18n( $cfg['dateformat'] .' '.get_option( 'time_format' ), $todate ); 				
				?>" id="todate"/><br />
			<span class="description"><?php _e('Insert the start and end dates from this event.', WPSellerEvents :: TEXTDOMAIN ); ?></span>
		</p>
		
		<p><b><?php echo __('Alarm Type', WPSellerEvents :: TEXTDOMAIN ); ?>: </b><br/>
		
		<b><label for="quantity"><?php _e( 'Quantity', WPSellerEvents :: TEXTDOMAIN ); ?></label>: </b>
			<input style="width: 60px;text-align: right; padding-right: 0px; " type="number" min="0" class="small-text" name="quantity" id="quantity" value="<?php echo esc_attr( $quantity ) ? esc_attr($quantity) : ''; ?>">&nbsp; &nbsp; 
			<b><label for="period"><?php _e( 'Period', WPSellerEvents :: TEXTDOMAIN ); ?></label>: </b>
			<?php	
			echo '<select id="period" name="period" style="display:inline;">
				<option value="minutes" '.selected( $period, 'minutes', FALSE ). '>'. __('minutes', WPSellerEvents :: TEXTDOMAIN).'</option>
				<option value="hours" '.selected( $period, 'hours', FALSE ). '>'. __('hours', WPSellerEvents :: TEXTDOMAIN).'</option>
				<option value="days" '.selected( $period, 'days', FALSE ). '>'. __('days', WPSellerEvents :: TEXTDOMAIN).'</option>
				<option value="weeks" '.selected( $period, 'weeks', FALSE ). '>'. __('weeks', WPSellerEvents :: TEXTDOMAIN).'</option>
			</select>';
			?><br />
			<span class="description">
				<?php 
					printf(
						__('Select a period for this event.  An email will be sent to the Seller on <span class="b scqty">%1s</span> <span class="b scper">%2s</span> before <span class="b scfrd">%3s</span>.', WPSellerEvents :: TEXTDOMAIN ),
							$quantity,
							$period,
							date_i18n( $cfg['dateformat'] .' '.get_option( 'time_format' ), $fromdate )
					);
				?>
			</span>
			<br />
			<br />
			<input class="checkbox" value="1" type="checkbox" <?php checked($activated,true); ?> name="activated" id="activated" /> <label for="activated"><b><?php _e('Activate Alarm', WPSellerEvents :: TEXTDOMAIN ); ?></b></label>
<?php /*		<br />
			&nbsp; &nbsp; <?php _e('Working as:', WPSellerEvents :: TEXTDOMAIN ); echo ' <span class="b">'. date_i18n( $cfg['dateformat'].' '.get_option( 'time_format' ), $fromdate ) .'</span> <i> '.$cron.'</i>'; ?>
*/ ?>			<br />
			&nbsp; &nbsp; <?php _e('Runtime:', WPSellerEvents :: TEXTDOMAIN ); echo ' <span class="b">'. date_i18n( $cfg['dateformat'] .' '.get_option( 'time_format' ), $cronnextrun).'</span>';	?>
			<br />
			&nbsp; &nbsp; <?php _e('Save event to refresh.', WPSellerEvents :: TEXTDOMAIN ); ?>
			<br />
		</p>
		
		<table id="customers-contacts">
			<thead>
			<th>
				<b><?php echo __('Client', WPSellerEvents :: TEXTDOMAIN ); ?>: </b>
			</th>
			<th>
				<b><?php echo __('Client Contact', WPSellerEvents :: TEXTDOMAIN ); ?>: </b>
			</th>
			</thead>
			<tbody>
			<tr>
				<td>  <?php // *************************  Client ?>
				<div class="event-customer">
					<div id="customer_ico" class="mya4_sprite <?php echo (isset($client_id) && ($client_id>0)) ?'tab_inactive_accSummary' : 'tab_active_accSummary' ?>" style="float: left;margin-right: 8px;"></div>
					<input type="hidden" name="customer_id" id="customer_id" value="<?php echo $client_id; ?>">
					<div id="customer_name">
						<?php 
						echo esc_html( $customer->post_title );
						if( isset($client_id) && ($client_id>0) ) echo ' :: '. esc_html( get_post_meta($client_id, 'email', true) );
						?>
					</div>
				</div>
				<label onclick="jQuery('#customers-list').fadeToggle();" class="button add" id="add_customer"> <?php _e('Search Client', WPSellerEvents :: TEXTDOMAIN); ?>.</label>
				<div id="customers-list" class="Customers" <?php echo (isset($client_id) && ($client_id>0)) ?'style="display:none;"' : '' ?>>
					<div class="header-customer-list">
						<div class="right srchFilterOuter">
						<div style="float:left;margin-left:2px;">
							<input id="psearchtext" name="psearchtext" class="srchbdr0" type="text" value=''>
						</div>
						<div class="srchSpacer"></div>
						<div id="productsearch" class="mya4_sprite searchIco" style="margin-top:6px;float: left;"></div>
					</div>

					</div><div style="clear: both;"></div>
					<?php
					$allcustomers = get_posts( array( 'post_type'=>'wpse_client', 'posts_per_page' => -1 ) );
					// Array of stdClass objects.
					?><ul><?php
					foreach ( $allcustomers as $user ) {
						$user_email = get_post_meta($user->ID, 'email', true);
						echo '<li id="user-' . $user->ID . '" class="user-item">' . esc_html( $user->post_title ) .' :: '. esc_html( $user_email ) . '</li>';
					}
					?></ul>
				</div>		
				</td>
				
				<td>  <?php // *************************  Contact ?>
				<div class="event-contact">
					<div id="contact_ico" class="mya4_sprite <?php echo (isset($contact_name) && !empty($contact_name) ) ?'tab_inactive_accSummary' : 'tab_active_accSummary' ?>" style="float: left;margin-right: 8px;"></div>
					<input type="hidden" name="contact_name" id="contact_id" value="<?php echo $event_data['contact_name']; ?>">
					<div id="contact_name">
						<?php 
						echo esc_html( $contact_name );
						?>
					</div>
				</div>
				<?php $show=true; if(!isset($user_contacts['description'])) $show=false;  ?> 
				<label onclick="jQuery('#contacts-list').fadeToggle();" class="ucshow button add" id="add_contact" <?php echo (!$show) ?'style="display:none;"' : '' ?>> <?php _e('Search Contact', WPSellerEvents :: TEXTDOMAIN); ?>.</label>
				<div id="contacts-list" class="Customers ucshow" <?php echo ((isset($contact_name) && !empty($contact_name)) or !$show ) ?'style="display:none;"' : '' ?>>
					<div class="header-contact-list">
						<div class="right srchFilterOuter">
							<div style="float:left;margin-left:2px;">
								<input id="uc_searchtext" name="uc_searchtext" class="srchbdr0" type="text" value=''>
							</div>
							<div class="srchSpacer"></div>
							<div id="productsearch" class="mya4_sprite searchIco" style="margin-top:6px;float: left;"></div>
						</div>
					</div>
					<div style="clear: both;"></div>
					<?php
					?><ul><?php
					for ($i = 0; $i < count($user_contacts['description']); $i++) {
						echo '<li id="user-' . esc_html($user_contacts['description'][$i]) . '" class="user_c-item">' . esc_html( $user_contacts['description'][$i]) .' :: '. esc_html( $user_contacts['email'][$i] ) . '</li>';
					}
					?></ul>
				</div>
				</td>
			</tr>
			</tbody>
		</table>
		
		<?php
	}
	
	public static function obs_box( $post ) {  
		global $post, $event_data, $cfg;
		$event_obs = $event_data['event_obs'];
		?>
		<table class="form-table">
			<tbody>
				<tr class="user-display-name-wrap">
					<td><style>
					#event_obs {background:#E2E2E2;}
					.sortitem{background:#fff;border:2px solid #ccc;padding-left:20px; display: flex;}
					.sortitem .sorthandle{position:absolute;top:5px;bottom:5px;left:3px;width:8px;display:none;background-image:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAYAAACp8Z5+AAAAB3RJTUUH3wIDBycZ/Cj09AAAAAlwSFlzAAALEgAACxIB0t1+/AAAAARnQU1BAACxjwv8YQUAAAAWSURBVHjaY2DABhoaGupBGMRmYiAEAKo2BAFbROu9AAAAAElFTkSuQmCC');}
					.sortitem:hover .sorthandle{display:block;}
						</style>
						<div class="event_obs_header">
							<div class="event_obs_date_column"><?php _e('Date', WPSellerEvents :: TEXTDOMAIN) ?></div>
							<div class="event_obs_text_column"><?php _e('Observation', WPSellerEvents :: TEXTDOMAIN ) ?></div>
						</div>
						<br />
						<div id="event_obs" data-callback="jQuery('#msgdrag').html('<?php _e('Update Event to save order', WPSellerEvents :: TEXTDOMAIN ); ?>').fadeIn();"> <!-- callback script to run on successful sort -->
							<?php for($i = 0; $i <= count($event_obs['text']); $i++) : ?>
								<?php $lastitem = $i == count($event_obs['text']); ?>			
								<div id="event_obs_ID<?php echo $i; ?>" class="sortitem <?php if(($i % 2) == 0) echo 'bw'; else echo 'lightblue'; ?> <?php if($lastitem) echo 'event_obs_new_field'; ?> " <?php if($lastitem) echo 'style="display:none;"'; ?> > <!-- sort item -->
									<div class="sorthandle"> </div> <!-- sort handle -->
									<div class="event_obs_date_column" id="">
										<input name="event_obs[date][<?php echo $i; ?>]" type="text" value="<?php 
										echo date_i18n( $cfg['dateformat'] .' '.get_option( 'time_format' ), 
												//(!isset($event_obs['date'][$i])) ? time()+(int)get_option( 'gmt_offset' )*3600 : $event_obs['date'][$i] 
												(!isset($event_obs['date'][$i])) ? current_time('timestamp')  : $event_obs['date'][$i] 
												); 
										?>" class="datetimepicker"/>
									</div>
									<div class="event_obs_text_column" id="">
										<textarea name="event_obs[text][<?php echo $i; ?>]" type="text" class=""/><?php echo stripslashes(@$event_obs['text'][$i]) ?></textarea>
									</div>
									<div class="" id="event_obs_actions">
										<label title="<?php _e('Delete this item', WPSellerEvents :: TEXTDOMAIN ); ?>" onclick="delete_event_obs('#event_obs_ID<?php echo $i; ?>');" class="delete"></label>
									</div>
								</div>
							<?php $a = $i;
							endfor ?>		
						</div>
						<input id="event_obs_field_max" value="<?php echo $a; ?>" type="hidden" name="event_obs_field_max">
						<div id="paging-box">		  
							<a href="JavaScript:void(0);" class="button-primary add" id="addmore_event_obs" style="font-weight: bold; text-decoration: none;"> <?php _e('Add an observation', WPSellerEvents :: TEXTDOMAIN ); ?>.</a>
							<label id="msgdrag"></label>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<?php 
	}
			
	
  	public static function admin_styles(){
		global $post;
		if($post->post_type != 'wpsellerevents') return $post->ID;
		wp_enqueue_style('wpse-sprite',WPSellerEvents :: $uri .'css/sprite.css');	
		wp_enqueue_style('jquery-datetimepicker',WPSellerEvents :: $uri .'css/jquery.datetimepicker.css');	
		add_action('admin_head', array( __CLASS__ ,'campaigns_admin_head_style'));
	}

	public static function admin_scripts(){
		global $post;
		if($post->post_type != 'wpsellerevents') return $post->ID;
		wp_register_script('jquery-datetimepicker', WPSellerEvents::$uri .'js/jquery.datetimepicker.js', array('jquery'));
		wp_enqueue_script('jquery-datetimepicker'); 
		wp_register_script('jquery-vsort', WPSellerEvents::$uri .'js/jquery.vSort.min.js', array('jquery'));
		wp_enqueue_script('jquery-vsort'); 
		add_action('admin_head', array( __CLASS__ ,'campaigns_admin_head_scripts'));
	}

	public static function getEvenTypeAttr() { //Ajax action
		if(!isset($_POST['eventype_ID'])) die('ERROR: ID no encontrado.'); 
		$t_id = $_POST['eventype_ID'];
		if($term_meta = get_option( "eventype_$t_id" ))	$term_meta['success'] = true;  else $term_meta['success'] = false;

		wp_send_json($term_meta); 
	}
	
	public static function getUserContacts() { //Ajax action
		if(!isset($_POST['user_ID'])) die('ERROR: ID no encontrado.'); 
		$userid = $_POST['user_ID'];
		$response['user_contacts'] = get_post_meta($userid, 'user_contacts', TRUE) ;
		if(!isset($response['user_contacts']) || empty($response['user_contacts']) ) $response['success'] = false;
		else $response['success'] = true;

		wp_send_json($response); 
	}
	
	public static function CheckFields() {  // Ajax Action: check required fields values before save post
		$err_message = "";
		$response = array();
		if(!isset($_POST['fromdate']) or $_POST['fromdate']=='__/__/____' ) {
			$err_message .= __('Error: Field From Date must be filled.', WPSellerEvents :: TEXTDOMAIN ).'<br />';
			$response['fields'][]='fromdate';
		}
		if(!isset($_POST['todate']) or $_POST['todate']=='__/__/____' ) {
			$err_message .= __('Error: Field To Date must be filled.', WPSellerEvents :: TEXTDOMAIN ).'<br />';
			$response['fields'][]='todate';
		}
		if( (int)$_POST['customer_id']==0 ) {
			$err_message .= __('Error: A customer must be selected from list..', WPSellerEvents :: TEXTDOMAIN ).'<br />';
			$response['fields'][]='customer_name';
		}
		if(current_user_can('wpse_seller')){  // don't shows seller meta-box because equal author
//			if( (int)$_POST['post_author']==0 ) {
//				$err_message .= __('Error: A Salesman must be selected from list.', WPSellerEvents :: TEXTDOMAIN ).'<br />';
//				$response['fields'][]='seller_name';
//			}
		}else{
			if( (int)$_POST['seller_id']==0 ) {
				$err_message .= __('Error: A Salesman must be selected from list.', WPSellerEvents :: TEXTDOMAIN ).'<br />';
				$response['fields'][]='seller_name';
			}
		}
		$response['success'] = false;
		if($err_message !="" ) $response['message'] = $err_message;
		else $response['success'] = true;

		wp_send_json($response); 
	}
	
	public static function campaigns_admin_head_style() {
		global $post;
		if($post->post_type != 'wpsellerevents') return $post_id;
			?><style type="text/css">
				.fieldate {width: 155px;}
				.b {font-weight: bold;}
				.hide {display: none;}
				.updated.notice-success a {display: none;}
				#poststuff h3 {background-color: #6EDA67;}
				
				#msgdrag {display:none;color:red;padding: 0 0 0 20px;font-weight: 600;font-size: 1em;}
				.event_obs_header {padding: 0 0 0 30px;font-weight: 600;font-size: 0.9em;}
				div.event_obs_date_column {float: left;width: 15%;}
				div.event_obs_text_column {float: left;width: 80%;margin-right: 5px;}
				div.event_obs_text_column textarea {width: 100%;}
				.event_obs_actions{margin-left: 5px;}
				.delete{color: #F88;font-size: 1.6em;}
				.delete:hover{color: red;}
				.delete:before { content: "\2718";}
				.add:before { content: "\271A";}
				
				table#customers-contacts {width: 100%;border-spacing: 0;}
				table#customers-contacts th {background-color: #6EDA67;padding: 5px 0;}
				table#customers-contacts td {background-color: honeydew;width: 50%;border: 1px solid #eee;margin: 0;padding: 10px;vertical-align: top;}

				#customer_name, #seller_name {font-weight: bold;}
				#customers-list,#contacts-list,#seller-list {border: 1px solid #BCBCBC; margin: 5px 0 0 20px;font-size: 0.9em;width: 400px;}
				.header-customer-list, .header-contact-list, .header-seller-list {
					background-color:#6EDA67;
					background:-moz-linear-gradient(center bottom,#C0FCBC 0,#6EDA67 98%,#FFFEA8 0);
					background:-webkit-gradient(linear,left top,left bottom,from(#C0FCBC),to(#6EDA67));
					-ms-filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#C0FCBC',endColorstr='#6EDA67');
					filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#C0FCBC',endColorstr='#6EDA67');
					border:1px solid #BCBCBC;border-bottom-style:none;margin-top:-1px;overflow:hidden;padding:3px 10px;
					height: 28px;
				}
				#customers-list ul,#contacts-list ul,#sellers-list ul{margin: 2px 0 0 0;max-height: 400px;overflow-y: scroll;}
				#customers-list li,#contacts-list li,#sellers-list li {border-bottom: 1px solid #BCBCBC;text-align: center;margin: 0;padding: 3px;}
				#customers-list li:hover, #contacts-list li:hover, #sellers-list li:hover {background-color: #BCBCBC;cursor: pointer;}
				.srchFilterOuter {width: 260px;background-color: #FFFFFF;border: 1px solid #BCBCBC;border-radius: 5px;margin-top: 1px;height: 24px;float: right;}
				.srchSpacer {float: left;background-color: #BCBCBC;height: 17px;margin: 3px 5px 0 5px;width: 1px;}
				.srchbdr0 {border-style: none;border: 0;height: 22px;color: #999;font-size: 13px;padding: 0px;width: 227px;}
				
				/*
				label[for=rich_editing] input { display: none; }
				label[for=rich_editing]:before { content: 'This option has been disabled (Formerly: ' }
				label[for=rich_editing]:after { content: ')'; } */
				/* form#your-profile h3#wordpress-seo,
				form#your-profile h3#wordpress-seo ~ table {display: none;	} */
			</style><?php

	}
	
	public static function campaigns_admin_head_scripts() {
		global $post, $cfg, $wp_locale, $locale;
		if($post->post_type != 'wpsellerevents') return $post->ID;
		$post->post_password = '';
		$visibility = 'public';
		$visibility_trans = __('Public');
		//$duplicate = '<button style="background-color: #EB9600;" id="vinculate" class="button button-large" type="button">'. __('Create Child Event', WPSellerEvents :: TEXTDOMAIN ) . '';
		$action = '?action=wpesellerevent_create_child&amp;post='.$post->ID;
		$create_child = '<br /><a href="'. admin_url( "admin.php". $action ).'" title="' . esc_attr(__("Create Child Event", WPSellerEvents :: TEXTDOMAIN)) . '">' .  __('Create Child Event', WPSellerEvents :: TEXTDOMAIN) . '</a>';
		//$cfg = get_option(WPSellerEvents :: OPTION_KEY);
		
		?>
		<script type="text/javascript" language="javascript">
		jQuery(document).ready(function($){
			$('#major-publishing-actions').append('<?php echo $create_child; ?>');
			// remove visibility
			$('#visibility').hide();
			
			// remove event types Most used box
			$('#eventype-tabs').remove();
			$('#eventype-pop').remove();
			// remove event types Ajax Quick Add 
			$('#eventype-adder').remove();
			
			//-----Click on event type 
			$(document).on("click", '#eventypechecklist input[type=checkbox]', function(event) { 
				var $current = $(this).prop('checked') ; //true or false
				$('#eventypechecklist input[type=checkbox]').prop('checked', false);
				$(this).prop('checked', $current );
				if( $current ){
					var data = {
						eventype_ID: $(this).val(),
						action: "getEvenTypeAttr"
					};
					$.post(ajaxurl, data, function(etattr){  //array with custom fields of term ID
						if( etattr.success ){
							var qty = etattr.quantity;
							$('#quantity').val(qty);
							$('#quantity').animate({'background-color':'red'},300).animate({'background-color':'white'},600).animate({'background-color':'pink'},400);
							var per = etattr.period;
							$('#period > option').prop("selected", false);
							$('#period').children('option[value="' + per +'"]').attr("selected", "selected"); 
							$('#period > option[value="' + per +'"]').prop("selected", true);
							$('#period').animate({'background-color':'red'},300).animate({'background-color':'white'},600).animate({'background-color':'pink'},400);
						}else{
							alert('<?php _e('Error searching Event type.', WPSellerEvents :: TEXTDOMAIN) ?>');
						}
					});
				}
			});
			
			load_user_contacts=function(user_id){
				var data = {
					user_ID: user_id,
					action: "getUserContacts"
				};
				$.post(ajaxurl, data, function(response){  //array with custom fields of term ID
					if( response.success ){
					//borrar el contacto actual al cambiar el cliente. recuperar los contactos e imprimirlos para que elija.
						var $user_contacts=response.user_contacts;
						var htmltext = '';
						for ($i = 0; $i < $user_contacts['description'].length; $i++) { 
							htmltext += '<li id="user-'+ $user_contacts['description'][$i] + '" class="user_c-item">' + $user_contacts['description'][$i] +' :: '+  $user_contacts['email'][$i] + '</li>';
						}
						$('#contacts-list ul').html(htmltext);
						$('.ucshow').fadeIn();
					}else{
						//alert('<?php _e('Error retrieving User Contacts.', WPSellerEvents :: TEXTDOMAIN) ?>');
						$('.ucshow').fadeOut();
						$( "#contact_id" ).val('');
						$( "#contact_name" ).html('<?php _e('Client contacts not defined.', WPSellerEvents :: TEXTDOMAIN ); ?>');
						$('#contacts-list ul').html('');
					}
				});	
			}
			load_user_fromlist=function(user, user_id){
				$( "#customer_id" ).val( user_id.substring(5, user_id.length ) );
				$( "#customer_name" ).html( user.substring(0,user.indexOf(' ::') ) );
				$( "#customer_ico" ).removeClass( 'tab_active_accSummary' );
				$( "#customer_ico" ).addClass( 'tab_inactive_accSummary' );
				$('#customers-list').fadeOut();
				$('#psearchtext').attr('value','');
				$('.user-item').show();
				
				load_user_contacts( user_id.substring(5, user_id.length ) );
			}
			//------------- Clients
			$('.user-item').click(function(){
				user = $(this).text();
				user_id = $(this).attr('id');
				load_user_fromlist( user, user_id );
			});
			$('#psearchtext').keypress(function(e){
				if(e.which === 13){
					e.preventDefault();
					first = $('#customers-list ul').find('.user-item:visible:first');
					user = first.text();
					user_id = first.attr('id');
					load_user_fromlist( user, user_id );
					
					return false;
				}
			});
			$('#psearchtext').keyup(function(tecla){
				if(tecla.keyCode==27) {
					$(this).attr('value','');
					$('.user-item').show();
				}else{
					var finduser = $(this).val();
					$('.user-item').each(function (el,item) {
						user = $(item).text(); //attr('value');
						if (user.toLowerCase().indexOf(finduser) >= 0) {
							$(item).show();
						}else{
							$(item).hide();
						}
					});
				}
			});
			
			//---------  Contacts
			load_contact_fromlist=function(user, user_id){
				$( "#contact_id" ).val( user_id.substring(5, user_id.length ) );
				$( "#contact_name" ).html( user.substring(0,user.indexOf(' ::') ) );
				$( "#contact_ico" ).removeClass( 'tab_active_accSummary' );
				$( "#contact_ico" ).addClass( 'tab_inactive_accSummary' );
				$('#contacts-list').fadeOut();
				$('#uc_searchtext').attr('value','');
				$('.user_c-item').show();
			}
			//$('.user_c-item').click(function(){
			$(document).on("click", '.user_c-item', function(event) { 
				user = $(this).text();
				user_id = $(this).attr('id');
				load_contact_fromlist( user, user_id );
			});
			$('#uc_searchtext').keypress(function(e){
				if(e.which === 13){
					e.preventDefault();
					first = $('#contacts-list ul').find('.user_c-item:visible:first');
					user = first.text();
					user_id = first.attr('id');
					load_contact_fromlist( user, user_id );
					
					return false;
				}
			});
			$('#uc_searchtext').keyup(function(tecla){
				if(tecla.keyCode==27) {
					$(this).attr('value','');
					$('.user_c-item').show();
				}else{
					var finduser = $(this).val();
					$('.user_c-item').each(function (el,item) {
						user = $(item).text(); //attr('value');
						if (user.toLowerCase().indexOf(finduser) >= 0) {
							$(item).show();
						}else{
							$(item).hide();
						}
					});
				}
			});

			//------------- Sellers
			load_seller_fromlist=function(user, user_id){
				$( "#seller_id" ).val( user_id.substring(7, user_id.length ) );
				$( "#seller_name" ).html( user );
				$( "#seller_ico" ).removeClass( 'tab_active_accSummary' );
				$( "#seller_ico" ).addClass( 'tab_inactive_accSummary' );
				$( '#sellers-list').fadeOut();
				$( '#se_searchtext').attr('value','');
				$( '.seller-item').show();
			}
			$('.seller-item').click(function(){
				user = $(this).text();
				user_id = $(this).attr('id');
				load_seller_fromlist( user, user_id );
			});
			$('#se_searchtext').keypress(function(e){
				if(e.which === 13){
					e.preventDefault();
					first = $('#sellers-list ul').find('.seller-item:visible:first');
					user = first.text();
					user_id = first.attr('id');
					load_seller_fromlist( user, user_id );
					
					return false;
				}
			});
			$('#se_searchtext').keyup(function(tecla){
				if(tecla.keyCode==27) {
					$(this).attr('value','');
					$('.seller-item').show();
				}else{
					var finduser = $(this).val();
					$('.seller-item').each(function (el,item) {
						user = $(item).text(); //attr('value');
						if (user.toLowerCase().indexOf(finduser) >= 0) {
							$(item).show();
						}else{
							$(item).hide();
						}
					});
				}
			});
			
			$('#post').submit( function() {		//checkfields
				$('#wpcontent .ajax-loading').attr('style',' visibility: visible;');
				$.ajaxSetup({async:false});
				error=false;
				
				var data = {
					fromdate: $("input[name='fromdate']").val(),
					todate	: $("input[name='todate']").val(),
					customer_id: $("input[name='customer_id']").val(),
					seller_id: $("input[name='seller_id']").val(),
					action: "checkfields"
				};
				$.post(ajaxurl, data, function(response){
					if( response.success ){
						error=false;  //then submit campaign
					}else{
						error=true;
						$('#fieldserror').remove();
						$.each(response.fields, function(){
							$( '#'+this.valueOf() ).animate({'background-color':'red'},1200).animate({'background-color':'white'},1000).animate({'background-color':'pink'},1000);
						});
						$("#poststuff").prepend('<div id="fieldserror" class="error fade">ERROR: '+response.message +'</div>');
						$('#wpcontent .ajax-loading').attr('style',' visibility: hidden;');
					}
				});
				if( error == true ) {
					return false;
				}else {
					return true;
				}
			});


			$('#addmore_event_obs').click(function() {
				oldval = $('#event_obs_field_max').val();
				jQuery('#event_obs_field_max').val( parseInt(jQuery('#event_obs_field_max').val(),10) + 1 );
				newval = $('#event_obs_field_max').val();
				event_obs_new= $('.event_obs_new_field').clone();
				$('div.event_obs_new_field').removeClass('event_obs_new_field');
				$('div#event_obs_ID'+oldval).fadeIn();
				$('textarea[name="event_obs[text]['+oldval+']"]').focus();
				event_obs_new.attr('id','event_obs_ID'+newval);
				$('input', event_obs_new).eq(0).attr('name','event_obs[date]['+ newval +']');
				$('textarea', event_obs_new).eq(0).attr('name','event_obs[text]['+ newval +']');
				$('.delete', event_obs_new).eq(0).attr('onclick', "delete_event_obs('#event_obs_ID"+ newval +"');");
				$('#event_obs').append(event_obs_new);
				$('#event_obs').vSort();
			});
		<?php	
			$objectL10n = (object)array(
				'lang'			=> substr($locale, 0, 2),
				'UTC'			=> get_option( 'gmt_offset' ),
				'timeFormat'    => get_option( 'time_format' ),
				'dateFormat'    => self :: date_format_php_to_js( $cfg['dateformat'] ),
				'printFormat'   => self :: date_format_php_to_js( $cfg['dateformat'] ).' '.get_option( 'time_format' ),
				'firstDay'      => get_option( 'start_of_week' ),
			);
			echo "$('.datetimepicker').datetimepicker({
				lang: '{$objectL10n->lang}',
				dayOfWeekStart: {$objectL10n->firstDay},
				formatTime:'{$objectL10n->timeFormat}',
				format:'{$objectL10n->printFormat}',
				formatDate:'{$objectL10n->dateFormat}',
				maxDate:'". date_i18n($objectL10n->dateFormat )."', // today
			});";

			echo "$('#fromdate').datetimepicker({
				lang: '{$objectL10n->lang}',
				dayOfWeekStart: {$objectL10n->firstDay},
				formatTime:'{$objectL10n->timeFormat}',
				format:'{$objectL10n->printFormat}',
				formatDate:'{$objectL10n->dateFormat}'
			});";
//				mask: true,
//				timepicker:false,
			
			echo "$('#todate').datetimepicker({
				lang: '{$objectL10n->lang}',
				dayOfWeekStart: {$objectL10n->firstDay},
				formatTime:'{$objectL10n->timeFormat}',
				format:'{$objectL10n->printFormat}',
				formatDate:'{$objectL10n->dateFormat}'
			});";
//				mask: true,
//				timepicker:false,
			?>			
			$(document).on("change",'#fromdate', function() {
				$('.scfrd').text($(this).val());
			}); 
			$(document).on("change",'#quantity', function() {
			//$('#quantity').change(function() {
				$('.scqty').text($(this).val());
			}); 
			$(document).on("change",'#period', function() {
				$('.scper').text( $(this).children('option[value="' + $(this).val() +'"]').text());
			}); 
		});   // jQuery
		function delete_event_obs(row_id){
			jQuery(row_id).fadeOut(); 
			jQuery(row_id).remove();
			jQuery('#msgdrag').html('<?php _e('Update Event to save changes.', 'wpsellerevents' ); ?>').fadeIn();
		}
		</script>
		<?php
	}
	
	public static function date_format_php_to_js( $sFormat ) {
    switch( $sFormat ) {
        //Predefined WP date formats
        case 'F j, Y':
        case 'Y/m/d':
        case 'm/d/Y':
        case 'd/m/Y':
            return $sFormat;
            break;
        default :
            return( 'm/d/Y' );
            break;
     }
	}
	
}