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
		add_action('wp_ajax_add_ajaxclient', array( __CLASS__, 'add_ajaxclient'));
 		if( ($pagenow == 'post-new.php' || $pagenow == 'post.php') ) {
			add_action('admin_print_styles-post.php', array( __CLASS__ ,'admin_styles'));
			add_action('admin_print_styles-post-new.php', array( __CLASS__ ,'admin_styles'));
			add_action('admin_print_scripts-post.php', array( __CLASS__ ,'admin_scripts'));
			add_action('admin_print_scripts-post-new.php', array( __CLASS__ ,'admin_scripts'));
			
			add_filter('attribute_escape', array( __CLASS__, 'change_button_texts'), 10, 2);
		}
	}

	public static function change_button_texts($safe_text, $text ){
		global $post, $current_screen, $screen;
		
		if (isset($post) && $post->post_type == 'wpsellerevents') {
			switch( $safe_text ) {
/*				case __('Save Draft');
					$safe_text = __('Save as Pendient', WPSellerEvents :: TEXTDOMAIN );
					break;
*/
				case __('Publish');
					$safe_text = __('Create Event', WPSellerEvents :: TEXTDOMAIN );
					break;

				default:
					break;
			}
		}
		return $safe_text;
	}

	public static function add_ajaxclient() {
		$response['success'] = false;
		$response['message'] = __('Error creating the client.');

		if ( !wp_verify_nonce( @$_POST['wpaddclient_nonce'], 'edit-event' ) )
			wp_send_json($response); 

		$args = array(
			'post_title' 	          => apply_filters('wpse_parse_title', $_POST['client_title']),
			'post_status' 	          => 'publish',
			'post_type' 	          => 'wpse_client',
			'comment_status'          => "closed",
			'ping_status'             => "closed"
		);
		
		$isclient = get_page_by_title( $args['post_title'], 'OBJECT', 'wpse_client' );
		if ( !is_null($isclient->ID) ) { // already exists
			$response['success'] = false;
			$response['message'] = __('The client already exists.');
			wp_send_json($response); 
		}
		$post_id = wp_insert_post( $args );

		if($post_id > 0 ) {
			$response['message'] = __('Client added.');
			$response['client_id'] = "$post_id";
			$response['success'] = true;
			$client = array();
			$client = apply_filters('wpse_check_client', $_POST);

			sellerevents_clients::update_client($post_id, $client);
		}
		wp_send_json($response); 

	}
	
	public static function create_meta_boxes() {
		global $post,$event_data, $wpsecfg;
		if(isset($_GET['action']) && $_GET['action']=='edit'){
			$event_data = WPSellerEvents :: get_event($post->ID);
		}
		$event_data = apply_filters('wpse_check_eventdata', $event_data);
		$wpsecfg = get_option(WPSellerEvents :: OPTION_KEY);
		$wpsecfg = apply_filters('wpse_check_options', $wpsecfg);
		
		//Remove Custom Fields Metabox
		//remove_meta_box( 'postcustom','wpsellerevents','normal' ); 
		//add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
		
		//if(current_user_can('wpse_seller')){
		add_meta_box( 'seller-box', __('Salesman', WPSellerEvents :: TEXTDOMAIN ), array(  __CLASS__  ,'seller_box' ),'wpsellerevents','side', 'default' );
		//}
		
		if($wpsecfg['editor_type']=="Basic"){
			add_action('post_submitbox_minor_actions', array( __CLASS__ ,'options_box'));
		}

		add_meta_box( 'status-box', __('Event Status', WPSellerEvents :: TEXTDOMAIN ), array(  __CLASS__ ,'status_box' ),'wpsellerevents','side', 'high' );
		add_meta_box( 'obs-box', __('Observations', WPSellerEvents :: TEXTDOMAIN ), array( __CLASS__  ,'obs_box' ),'wpsellerevents','normal', 'default' );
		
		if($wpsecfg['editor_type']!="Basic"){
			add_meta_box( 'options-box', __('Options for this event', WPSellerEvents :: TEXTDOMAIN ), array(  __CLASS__ ,'options_box' ),'wpsellerevents','normal', 'default' );
		}
	}		

			//*************************************************************************************
	public static function status_box( $post ) {  
		global $post, $event_data, $wpsecfg, $current_user;
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
		global $post, $event_data, $wpsecfg, $current_user;
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
		global $post, $event_data, $wpsecfg;
		$fromdate = $event_data['fromdate'];
		$todate = $event_data['todate'];
		$quantity = $event_data['quantity'];
		$period = $event_data['period'];
		$activated = $event_data['activated'];
		$cron = $event_data['cron'];
		$cronnextrun = $event_data['cronnextrun'];
		wp_nonce_field( 'edit-event', 'wpsellerevents_nonce' ); 
		?>
		<div class="clear"></div>
		<div style="text-align:left;">
		<p><b><?php echo '<label for="fromdate">' . __('Date', WPSellerEvents :: TEXTDOMAIN ) . '</label>'; ?>: </b>
			<input class="fieldate" type="text" name="fromdate" value="<?php echo date_i18n( $wpsecfg['dateformat'] .' '.get_option( 'time_format' ), $fromdate ); ?>" id="fromdate"/>&nbsp; &nbsp; 
		<?php if($wpsecfg['editor_type']!="Basic") : ?>
			<b><?php echo '<label for="todate">' . __('To Date', WPSellerEvents :: TEXTDOMAIN ) . '</label>'; ?>: </b>
			<input class="fieldate" type="text" name="todate" value="<?php 
				echo date_i18n( $wpsecfg['dateformat'] .' '.get_option( 'time_format' ), $todate );
				?>" id="todate"/>
			 <br />		 
			<span class="description"><?php _e('Insert the start and end dates from this event.', WPSellerEvents :: TEXTDOMAIN ); ?></span>
		 <?php else: ?>
			<br />		 
			<span class="description"><?php _e('Insert the date for this event.', WPSellerEvents :: TEXTDOMAIN ); ?></span>
		 <?php endif; ?>
		</p>
<div <?php echo ($wpsecfg['editor_type']=="Basic") ? 'style="display:none;"' : ''; ?>>
		<b><?php echo __('Alarm Type', WPSellerEvents :: TEXTDOMAIN ); ?>: </b><br/>
		
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
							date_i18n( $wpsecfg['dateformat'] .' '.get_option( 'time_format' ), $fromdate )
					);
				?>
			</span>
			<br />
</div>
		<p>
			<input class="checkbox" value="1" type="checkbox" <?php checked($activated,true); ?> name="activated" id="activated" /> <label for="activated"><b><?php _e('Activate Alarm', WPSellerEvents :: TEXTDOMAIN ); ?></b></label>
			<br />
			&nbsp; &nbsp; <?php echo ' <span class="b" id="alertdate">'. date_i18n( $wpsecfg['dateformat'] .' '.get_option( 'time_format' ), $cronnextrun).'</span>';	?>
			<br />
			&nbsp; &nbsp; <?php _e('Save event to refresh.', WPSellerEvents :: TEXTDOMAIN ); ?>
			<br />
		</p>
		</div>
		
		<?php
	}//closed option box
	
	public static function obs_box( $post ) {  
		global $post, $event_data, $wpsecfg;

		$client_id = $event_data['customer_id'];
		if(isset($client_id) && ($client_id>0) ) {
			$customer = get_post( $client_id );
			if(is_object($customer)) $user_contacts = get_post_meta($client_id, 'user_contacts', TRUE) ;
		}else {
			$customer = (object)array('post_title'=> __('Select the customer from the list.', WPSellerEvents :: TEXTDOMAIN ) );
		}
		if(!isset($user_contacts)) {
			$user_contacts = array();
			$contact_name = __('Client contacts not defined.', WPSellerEvents :: TEXTDOMAIN );
		}else {
			$contact_name = $event_data['contact_name'];
		}

		$event_obs = $event_data['event_obs'];
		?>
		
		<div id="popup_addclient_background" style="display:none;"></div> 
		<div id="addclient_popup" style="display:none;">
			<div id="content_popup_addclient">
				<table class="addclient_table form-table">
				<tbody>
				<tr class="client_title-wrap">
					<th><label for="client_title"><?php _e("Client Name", WPSellerEvents :: TEXTDOMAIN ) ?></label></th>
					<td><input type="text" readonly="true" name="client_title" id="client_title" value="" class="regular-text"></td>
				</tr>
				<tr class="user-email-wrap">
					<th><label for="email"><?php _e("E-mail", WPSellerEvents :: TEXTDOMAIN ) ?></label></th>
					<td><input type="email" name="email" id="email" value="" class="regular-text ltr"></td>
				</tr>
				<tr class="user-address-wrap">
					<th><label for="address"><?php _e("Address", WPSellerEvents :: TEXTDOMAIN ) ?>	</label></th>
					<td><input type="text" name="address" id="address" value="" class="regular-text"></td>
				</tr>
				<tr class="user-phone-wrap">
					<th><label for="phone"><?php _e("Telephone", WPSellerEvents :: TEXTDOMAIN ) ?>	</label></th>
					<td><input type="text" name="phone" id="phone" value="" class="regular-text"></td>
				</tr>
				<tr class="user-cellular-wrap">
					<th><label for="cellular"><?php _e("Cellular", WPSellerEvents :: TEXTDOMAIN ) ?>	</label></th>
					<td><input type="text" name="cellular" id="cellular" value="" class="regular-text"></td>
				</tr>
				<tr class="user-facebook-wrap">
					<th><label for="facebook"><?php _e("Facebook URL", WPSellerEvents :: TEXTDOMAIN ) ?>	</label></th>
					<td><input type="text" name="facebook" id="facebook" value="" class="regular-text"></td>
				</tr>
				<tr class="user-display-name-wrap" id="row_user_aseller">
					<th><label for="facebook"><?php _e('Salesman', WPSellerEvents :: TEXTDOMAIN ) ?>	</label></th>
					<td>
					<?php
					if(!current_user_can('wpse_seller'))	 {
						$user_aseller = get_current_user_id();
						$allsellers = get_users( array( 'role' => 'wpse_seller' ) );
						// Array of stdClass objects.
						$select = '<select name="user_aseller" id="user_aseller">';
						if( !isset( $user_aseller ) || $user_aseller == '' ) {
							$select .='<option value="" selected="selected">'. __('Choose a Salesman', WPSellerEvents :: TEXTDOMAIN  ) . '</option>';
						}
						foreach ( $allsellers as $suser ) {
							$select .='<option value="' . $suser->ID . '" ' . selected($user_aseller, $suser->ID, false) . '>' . esc_html( $suser->display_name ) . '</option>';
						}
						$select .= '</select>';
						echo $select;
					}else{
						//$user = get_user_by( 'ID', get_current_user_id() );
						echo $current_user->display_name;
						echo '<input type="hidden" name="user_aseller" id="user_aseller" value="'. get_current_user_id() .'" class="regular-text ltr">';
					}
					?>
					</td>
				</tr>
				
				</table>
			</div>
			<div id="buttons_addclient_popup">
				<a href="#" class="button-primary add" id="accept_client" style="margin:3px;"><?php _e('Accept'); ?></a> 
				<a href="#" class="button" id="btn_cancel_client_popup" style="margin:3px;"><?php _e('Cancel'); ?></a>
			</div>
		</div>
				
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
					
				<label onclick="jQuery('#customers-list').fadeToggle();" class="button" id="add_customer"><span class="mya4_sprite searchIco">&nbsp;&nbsp;&nbsp;</span> <?php _e('Search Client', WPSellerEvents :: TEXTDOMAIN); ?>.</label>
				<div id="customers-list" class="Customers" <?php echo (isset($client_id) && ($client_id>0)) ?'style="display:none;"' : '' ?>>
					<div class="header-customer-list">
						<label class="button-primary add right" id="add_newclient"> <?php _e('Add New', WPSellerEvents :: TEXTDOMAIN); ?>.</label>
						<div class="srchFilterOuter left">
							<div style="float:left;margin-left:2px;">
								<input id="psearchtext" name="psearchtext" class="srchbdr0" type="text" value='' placeholder="<?php _e('Type Client Name Here', WPSellerEvents :: TEXTDOMAIN) ?>">
							</div>
							<div class="srchSpacer"></div>
							<div id="productsearch" class="mya4_sprite searchIco" style="margin-top:6px;float: left;"></div>
						</div>
						
					</div>
					<div style="clear: both;"></div>
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
				<div id="contacts-list" class="Customers ucshow" style="display:none;">
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
							<?php for($i = 0; $i <= count($event_obs['text']); $i++) : // agregar +1 al count para mostrar 1 en blanco?>
								<?php $lastitem = $i == count($event_obs['text']); ?>
								<div id="event_obs_ID<?php echo $i; ?>" class="sortitem <?php if(($i % 2) == 0) echo 'bw'; else echo 'lightblue'; ?> <?php if($lastitem) echo 'event_obs_new_field'; ?> " <?php if($lastitem) echo 'style="display:none;"'; ?> > <!-- sort item -->
									<div class="sorthandle"> </div> <!-- sort handle -->
									<div class="event_obs_date_column" id="">
										<input name="event_obs[date][<?php echo $i; ?>]" type="text" value="<?php
										echo date_i18n( $wpsecfg['dateformat'] .' '.get_option( 'time_format' ),
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
		$wpsecfg = get_option(WPSellerEvents :: OPTION_KEY);
		$wpsecfg = apply_filters('wpse_check_options', $wpsecfg);

		$t_id = $_POST['eventype_ID'];
		$fromdate = WPSellerEvents::date2time($_POST['fromdate'], $wpsecfg['dateformat'].' '.get_option('time_format') );
		if($term_meta = get_option( "eventype_$t_id" ))	{
			switch($term_meta['period']) {
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
		    $cronseconds = $term_meta['quantity'] * $seconds;
		    $cronnextrun = $fromdate - $cronseconds ;
		    $term_meta['alertdate'] = date_i18n( $wpsecfg['dateformat'] .' '.get_option( 'time_format' ), $cronnextrun);
		    if ( $cronnextrun <= current_time('timestamp') )
			    $term_meta['alertdate'] = '---';   // reset vars to allow send mail with cron

			$term_meta['success'] = true;
		}
		else {
			$term_meta['success'] = false;
		}

		wp_send_json($term_meta); 
	}
	
	public static function getUserContacts() { //Ajax action
		if(!isset($_POST['user_ID'])) die('ERROR: ID no encontrado.'); 
		$userid = $_POST['user_ID'];
		$response['user_contacts'] = get_post_meta($userid, 'user_contacts', TRUE) ;
		if($response['user_contacts']['description'][0]=='') $response['user_contacts']='';
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
/*		if(!isset($_POST['todate']) or $_POST['todate']=='__/__/____' ) {
			$err_message .= __('Error: Field To Date must be filled.', WPSellerEvents :: TEXTDOMAIN ).'<br />';
			$response['fields'][]='todate';
		}
*/		if( (int)$_POST['customer_id']==0 ) {
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
				.fieldate {width: 160px;}
				.b {font-weight: bold;}
				.hide {display: none;}
				.updated.notice-success a {display: none;}
				#misc-publishing-actions {display: none;}
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
				
				#addclient_popup {
					position:absolute;
					left: 50%;
					margin-left: -250px;
					width:500px;
					min-height:200px;
					height:auto;
					background-color:white;
					z-index:5;
					border:5px solid #d9d0d0;
					border-radius:4px;
					box-shadow: 0 0 15px rgba(0,0,0,.1);
				}
				#popup_addclient_background {
					position:fixed;
					left: 0%;
					top: 0%;
					width:100%;
					height:100%;
					background-color:#000;
					opacity:0.8;
					z-index:3;
					margin-left:160px;
				}
				#content_popup_addclient {
					min-height:165px;
					height:auto;
					padding:4px;
				}
				#buttons_addclient_popup {
					border-top:1px solid #d9d0d0;
					height:35px;
					text-align:right;
				}
				.addclient_table {
					width:100%;
				}
				.addclient_td_name {
					width: 55%;
					text-align: right;
				}
				.addclient_td_input {
					width:65%;
				}

				
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
				.srchFilterOuter {width: 260px;background-color: #FFFFFF;border: 1px solid #BCBCBC;border-radius: 5px;margin-top: 1px;height: 24px;}
				.srchSpacer {float: left;background-color: #BCBCBC;height: 17px;margin: 3px 5px 0 5px;width: 1px;}
				.srchbdr0 {border-style: none;border: 0;height: 22px;color: #999;font-size: 13px;padding: 0px 0 0 2px;width: 227px;}
				
				/*
				label[for=rich_editing] input { display: none; }
				label[for=rich_editing]:before { content: 'This option has been disabled (Formerly: ' }
				label[for=rich_editing]:after { content: ')'; } */
				/* form#your-profile h3#wordpress-seo,
				form#your-profile h3#wordpress-seo ~ table {display: none;	} */
			</style><?php

	}
	
	public static function campaigns_admin_head_scripts() {
		global $post, $wpsecfg, $wp_locale, $locale;
		if($post->post_type != 'wpsellerevents') return $post->ID;
		$post->post_password = '';
		$visibility = 'public';
		$visibility_trans = __('Public');
		//$duplicate = '<button style="background-color: #EB9600;" id="vinculate" class="button button-large" type="button">'. __('Create Child Event', WPSellerEvents :: TEXTDOMAIN ) . '';
		$action = '?action=wpesellerevent_create_child&amp;post='.$post->ID;
		$create_child = '<br /><a href="'. admin_url( "admin.php". $action ).'" title="' . esc_attr(__("Create Child Event", WPSellerEvents :: TEXTDOMAIN)) . '">' .  __('Create Child Event', WPSellerEvents :: TEXTDOMAIN) . '</a>';
		//$wpsecfg = get_option(WPSellerEvents :: OPTION_KEY);
		
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
						fromdate: $("input[name='fromdate']").val(),
						action: "getEvenTypeAttr"
					};
					$.post(ajaxurl, data, function(etattr){  //array with custom fields of term ID
						if( etattr.success ){
							$('#alertdate').html(etattr.alertdate);
							$('#alertdate').animate({'background-color':'red'},300).animate({'background-color':'white'},600).animate({'background-color':'pink'},400);
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
			
			load_user_contacts = function(user_id){
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
					var finduser = $(this).val().toLowerCase();
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
					var finduser = $(this).val().toLowerCase();
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
					var finduser = $(this).val().toLowerCase();
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
				'dateFormat'    => self :: date_format_php_to_js( $wpsecfg['dateformat'] ),
				'printFormat'   => self :: date_format_php_to_js( $wpsecfg['dateformat'] ).' '.get_option( 'time_format' ),
				'firstDay'      => get_option( 'start_of_week' ),
			);
			echo "$('.datetimepicker').datetimepicker({
				lang: '{$objectL10n->lang}',
				dayOfWeekStart: '{$objectL10n->firstDay}',
				formatTime:'{$objectL10n->timeFormat}',
				format:'{$objectL10n->printFormat}',
				formatDate:'{$objectL10n->dateFormat}',
				maxDate:'". date_i18n($objectL10n->dateFormat )."', // today
			});";

			echo "$('#fromdate').datetimepicker({
				lang: '{$objectL10n->lang}',
				dayOfWeekStart: '{$objectL10n->firstDay}',
				formatTime:'{$objectL10n->timeFormat}',
				format:'{$objectL10n->printFormat}',
				formatDate:'{$objectL10n->dateFormat}'
			});";
//				mask: true,
//				timepicker:false,
			
			echo "$('#todate').datetimepicker({
				lang: '{$objectL10n->lang}',
				dayOfWeekStart: '{$objectL10n->firstDay}',
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
			
			$(document).on("click",'#add_newclient', function(e) {
				if( jQuery('#psearchtext').val()!='' ){
					openAddClientPopPup();
				}else{
					alert('<?php _e('Type the client name in the search field before Add New Client;', 'wpsellerevents' ) ?>');
				}
				e.preventDefault();
				return false;
			});	
		});   // jQuery
		
		function delete_event_obs(row_id){
			jQuery(row_id).fadeOut(); 
			jQuery(row_id).remove();
			jQuery('#msgdrag').html('<?php _e('Update Event to save changes.', 'wpsellerevents' ); ?>').fadeIn();
		}
		
		function openAddClientPopPup() {
//			jQuery('#addclient_popup').html(newHtml);
			jQuery('#addclient_popup').fadeIn();
			jQuery('#client_title').val( jQuery('#psearchtext').val() );
			jQuery('#email').focus();
			jQuery('#popup_addclient_background').fadeIn();

			jQuery('#btn_cancel_client_popup').click(function(e){
				jQuery('#addclient_popup').fadeOut();
				jQuery('#popup_addclient_background').fadeOut();
//				jQuery('#addclient_popup').html('');
				e.preventDefault();
				return false;
			});
			jQuery('#accept_client').click(function(e){
				var error = false;
				if (!error && jQuery('#client_title').val() == '') {
					alert('<?php _e('Type the client name in the search field before Add New Client;') ?>');
					jQuery('#client_title').focus();
					error = true;
				}

				if (!error) {	// Add client to database!! AJAX					
					jQuery.ajaxSetup({async:false});
					var data = {
						wpaddclient_nonce : jQuery("input[name='wpsellerevents_nonce']").val(),
						client_title: jQuery("input[name='client_title']").val(),
						email		: jQuery("input[name='email']").val(),
						address		: jQuery("input[name='address']").val(),
						phone		: jQuery("input[name='phone']").val(),
						cellular	: jQuery("input[name='cellular']").val(),
						facebook	: jQuery("input[name='facebook']").val(),
						user_aseller: jQuery("input[name='user_aseller']").val(),
						action		: "add_ajaxclient"
					};
					jQuery.post(ajaxurl, data, function(response){
						if( response.success ){
							status='success';  //then submit campaign
							jQuery('#fieldserror').remove();
							jQuery("#poststuff").prepend('<div id="fieldserror" class="updated fade"><p>'+response.message +'</p></div>');
							jQuery("#customers-list").append('<li id="'+response.client_id +'">'+data.client_title +' :: '+data.email +'</div>');
							load_user_fromlist( data.client_title +' :: '+data.email, response.client_id );
							jQuery("input[name='client_title']").val('');
							jQuery("input[name='email']").val('');
							jQuery("input[name='address']").val('');
							jQuery("input[name='phone']").val('');
							jQuery("input[name='cellular']").val('');
							jQuery("input[name='facebook']").val('');
							jQuery("input[name='user_aseller']").val('');
						}else{
							status='error';
							jQuery('#fieldserror').remove();
							jQuery("#poststuff").prepend('<div id="fieldserror" class="error fade"><p>ERROR: '+response.message +'</p></div>');
							jQuery('#wpcontent .ajax-loading').attr('style',' visibility: hidden;');
						}
					});					
					if(status == 'error') {
						e.preventDefault();
					} else {
						//return true; 
					}
					
					jQuery('#addclient_popup').fadeOut();
					jQuery('#popup_addclient_background').fadeOut();
				}
				e.preventDefault();
				return false;
			});

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