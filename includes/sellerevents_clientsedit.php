<?php 
// don't load directly 
if ( !defined('ABSPATH') )  {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'sellerevents_clientsedit' ) ) return;

class sellerevents_clientsedit {
	
	public function __construct( $hook_in = FALSE ) {
		global $pagenow;
 		if( ($pagenow == 'post-new.php' || $pagenow == 'post.php') ) {
			add_action('parent_file',  array( __CLASS__, 'client_tax_menu_correction'));
			add_filter('enter_title_here', array( __CLASS__,'client_name_placeholder'),10,2);
			add_action('admin_print_styles-post.php', array( __CLASS__ ,'admin_styles'));
			add_action('admin_print_styles-post-new.php', array( __CLASS__ ,'admin_styles'));
			add_action('admin_print_scripts-post.php', array( __CLASS__ ,'admin_scripts'));
			add_action('admin_print_scripts-post-new.php', array( __CLASS__ ,'admin_scripts'));
		}
	}
	
			// highlight the proper top level menu
	static function client_tax_menu_correction($parent_file) {
		global $current_screen;
		if ($current_screen->post_type == "wpse_client") {
			$parent_file = 'edit.php?post_type=wpsellerevents';
		}
		return $parent_file;
	}
	
	static function client_name_placeholder( $title_placeholder , $post ) {
		if($post->post_type == 'wpse_client')
			$title_placeholder = __('Enter Client name here', WPSellerEvents :: TEXTDOMAIN );
		return $title_placeholder;
	}
	
	public static function create_meta_boxes() {
		global $post,$client_data, $cfg;
		$client_data = sellerevents_clients :: get_client_data($post->ID);
		$cfg = get_option(WPSellerEvents :: OPTION_KEY);
		$cfg = apply_filters('wpse_check_options', $cfg);
		
		// Remove Custom Fields Metabox
		//remove_meta_box( 'postcustom','wpse_client','normal' ); 
		//	add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
		remove_meta_box( 'postimagediv', 'wpse_client', 'side' );
		add_meta_box('postimagediv', __('Client Image', WPSellerEvents :: TEXTDOMAIN ), 'post_thumbnail_meta_box', 'wpse_client', 'side', 'high');
		add_meta_box( 'seller-box', __('Assign Seller', WPSellerEvents :: TEXTDOMAIN ), array(  __CLASS__ ,'seller_box' ),'wpse_client','side', 'high' );
		add_meta_box( 'data-box', __('Complete Client Data', WPSellerEvents :: TEXTDOMAIN ), array(  __CLASS__ ,'data_box' ),'wpse_client','normal', 'default' );
		add_meta_box( 'options-box', __('Client Contacts', WPSellerEvents :: TEXTDOMAIN ), array(  __CLASS__ ,'options_box' ),'wpse_client','normal', 'default' );
	}
	
	public static function seller_box( $post ) {  
		global $post, $client_data, $cfg, $current_user;
		$user_aseller = $client_data['user_aseller'];
		?>
		<table class="form-table">
		<tbody>
		<tr class="user-display-name-wrap" id="row_user_aseller">
			<td>
			<?php
			if(!current_user_can('wpse_seller'))	 {
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
		</tbody>
		</table>
		<?php
	}

		
	public static function data_box( $post ) {  
		global $post, $client_data, $cfg;		
		?>
		<table class="form-table">
		<tbody><tr class="user-email-wrap">
			<th><label for="email"><?php _e("E-mail", WPSellerEvents :: TEXTDOMAIN ) ?></label></th>
			<td><input type="email" name="email" id="email" value="<?php echo $client_data['email'] ?>" class="regular-text ltr"></td>
		</tr>
		<tr class="user-address-wrap">
			<th><label for="address"><?php _e("Address", WPSellerEvents :: TEXTDOMAIN ) ?>	</label></th>
			<td><input type="text" name="address" id="address" value="<?php echo $client_data['address'] ?>" class="regular-text"></td>
		</tr>
		<tr class="user-phone-wrap">
			<th><label for="phone"><?php _e("Telephone", WPSellerEvents :: TEXTDOMAIN ) ?>	</label></th>
			<td><input type="text" name="phone" id="phone" value="<?php echo $client_data['phone'] ?>" class="regular-text"></td>
		</tr>
		<tr class="user-cellular-wrap">
			<th><label for="cellular"><?php _e("Cellular", WPSellerEvents :: TEXTDOMAIN ) ?>	</label></th>
			<td><input type="text" name="cellular" id="cellular" value="<?php echo $client_data['cellular'] ?>" class="regular-text"></td>
		</tr>
		<tr class="user-facebook-wrap">
			<th><label for="facebook"><?php _e("Facebook URL", WPSellerEvents :: TEXTDOMAIN ) ?>	</label></th>
			<td><input type="text" name="facebook" id="facebook" value="<?php echo $client_data['facebook'] ?>" class="regular-text"></td>
		</tr>
		</tbody></table>
		<?php
	}
	
	
	public static function options_box( $post ) {  
		global $post, $client_data, $cfg;
		wp_nonce_field( 'edit-client', 'wpse_client_nonce' ); 
		$user_contacts = $client_data['user_contacts'];

		?>
	<table class="form-table">
		<tbody>
		<tr class="user-display-name-wrap">
			<td><style>
			#user_contacts {background:#E2E2E2;}
			.sortitem{background:#fff;border:2px solid #ccc;padding-left:20px; display: flex;}
			.sortitem .sorthandle{position:absolute;top:5px;bottom:5px;left:3px;width:8px;display:none;background-image:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAYAAACp8Z5+AAAAB3RJTUUH3wIDBycZ/Cj09AAAAAlwSFlzAAALEgAACxIB0t1+/AAAAARnQU1BAACxjwv8YQUAAAAWSURBVHjaY2DABhoaGupBGMRmYiAEAKo2BAFbROu9AAAAAElFTkSuQmCC');}
			.sortitem:hover .sorthandle{display:block;}
				</style>
				<div class="uc_header">
				<div class="uc_column"><?php _e('Description', WPSellerEvents :: TEXTDOMAIN  ) ?></div>
				<div class="uc_column"><?php _e('Phone', WPSellerEvents :: TEXTDOMAIN  ) ?></div>
				<div class="uc_column"><?php _e('Email', WPSellerEvents :: TEXTDOMAIN  ) ?></div>
				<div class="uc_column"><?php _e('Position', WPSellerEvents :: TEXTDOMAIN  ) ?></div>
				<div class="uc_column"><?php _e('Address', WPSellerEvents :: TEXTDOMAIN  ) ?></div>
				</div>
				<br />
				<div id="user_contacts" data-callback="jQuery('#msgdrag').html('<?php _e('Update Client to save Contacts order', WPSellerEvents :: TEXTDOMAIN  ); ?>').fadeIn();"> <!-- callback script to run on successful sort -->
					<?php for ($i = 0; $i <= count(@$user_contacts['description']); $i++) : ?>
						<?php $lastitem = $i==count(@$user_contacts['description']); ?>			
						<div id="uc_ID<?php echo $i; ?>" class="sortitem <?php if(($i % 2) == 0) echo 'bw'; else echo 'lightblue'; ?> <?php if($lastitem) echo 'uc_new_field'; ?> " <?php if($lastitem) echo 'style="display:none;"'; ?> > <!-- sort item -->
							<div class="sorthandle"> </div> <!-- sort handle -->
							<div class="uc_column" id="">
								<input name="uc_description[<?php echo $i; ?>]" type="text" value="<?php echo stripslashes(@$user_contacts['description'][$i]) ?>" class="large-text"/>
							</div>
							<div class="uc_column" id="">
								<input name="uc_phone[<?php echo $i; ?>]" type="text" value="<?php echo stripslashes(@$user_contacts['phone'][$i]) ?>" class="large-text"/>
							</div>
							<div class="uc_column" id="">
								<input name="uc_email[<?php echo $i; ?>]" type="text" value="<?php echo stripslashes(@$user_contacts['email'][$i]) ?>" class="large-text"/>
							</div>
							<div class="uc_column" id="">
								<input name="uc_position[<?php echo $i; ?>]" type="text" value="<?php echo stripslashes(@$user_contacts['position'][$i]) ?>" class="large-text"/>
							</div>
							<div class="uc_column" id="">
								<input name="uc_address[<?php echo $i; ?>]" type="text" value="<?php echo stripslashes(@$user_contacts['address'][$i]) ?>" class="large-text"/>
							</div>
							<div class="" id="uc_actions">
								<label title="<?php _e('Delete this item',  WPSellerEvents :: TEXTDOMAIN  ); ?>" onclick="delete_user_contact('#uc_ID<?php echo $i; ?>');" class="delete"></label>
							</div>
						</div>
						<?php $a=$i;endfor ?>		
				</div>
				<input id="ucfield_max" value="<?php echo $a; ?>" type="hidden" name="ucfield_max">
				<div id="paging-box">		  
					<a href="JavaScript:void(0);" class="button-primary add" id="addmoreuc" style="font-weight: bold; text-decoration: none;"> <?php _e('Add User Contact', WPSellerEvents :: TEXTDOMAIN  ); ?>.</a>
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
		if($post->post_type != 'wpse_client') return $post->ID;
		wp_enqueue_style('wpse-sprite',WPSellerEvents :: $uri .'css/sprite.css');	
		add_action('admin_head', array( __CLASS__ ,'clients_admin_head_style'));
	}

	public static function admin_scripts(){
		global $post;
		if($post->post_type != 'wpse_client') return $post->ID;
		wp_register_script('jquery-vsort', WPSellerEvents::$uri .'js/jquery.vSort.min.js', array('jquery'));
		wp_enqueue_script('jquery-vsort'); 
		add_action('admin_head', array( __CLASS__ ,'clients_admin_head_scripts'));
	}

	
	public static function clients_admin_head_style() {
		global $post;
		if($post->post_type != 'wpse_client') return $post->ID;
			?><style type="text/css">
				.fieldate {width: 135px !important;}
				.b {font-weight: bold;}
				.hide {display: none;}
				.updated.notice-success a {display: none;}
				#edit-slug-box, #post-preview{display: none;}
			#poststuff h3 {background-color: #6EDA67;}
				
			#msgdrag {display:none;color:red;padding: 0 0 0 20px;font-weight: 600;font-size: 1em;}
			.uc_header {padding: 0 0 0 30px;font-weight: 600;font-size: 0.9em;}
			div.uc_column {float: left;width: 19%;}
			.uc_actions{margin-left: 5px;}
			.delete{color: #F88;font-size: 1.6em;}
			.delete:hover{color: red;}
			.delete:before { content: "\2718";}
			.add:before { content: "\271A";}

			</style><?php

	}
	
	public static function clients_admin_head_scripts() {
		global $post, $cfg, $wp_locale, $locale;
		if($post->post_type != 'wpse_client') return $post->ID;
		$post->post_password = '';
		$visibility = 'public';
		$visibility_trans = __('Public');
		//$cfg = get_option(WPSellerEvents :: OPTION_KEY);
		
		?>
		<script type="text/javascript" language="javascript">
		jQuery(document).ready(function($){
			$('#publish').val('<?php _e('Save Client', WPSellerEvents :: TEXTDOMAIN ); ?>');
			$('#submitdiv h3 span').text('<?php _e('Update', WPSellerEvents :: TEXTDOMAIN ); ?>');
			// remove visibility
			$('#visibility').hide();
			
			// remove channels Most used box
			$('#channel-tabs').remove();
			$('#channel-pop').remove();
			// remove channels Ajax Quick Add 
			$('#channel-adder').remove();
			//-----Click on channel  (Allows just one)
			$(document).on("click", '#channelchecklist input[type=checkbox]', function(event) { 
				var $current = $(this).prop('checked') ; //true or false
				$('#channelchecklist input[type=checkbox]').prop('checked', false);
				$(this).prop('checked', $current );
				//if( $current ){ }
			});
			
			// remove segments Most used box
			$('#segment-tabs').remove();
			$('#segment-pop').remove();
			// remove segments Ajax Quick Add 
			$('#segment-adder').remove();
			//-----Click on segment (Allows just one)
			$(document).on("click", '#segmentchecklist input[type=checkbox]', function(event) { 
				var $current = $(this).prop('checked') ; //true or false
				$('#segmentchecklist input[type=checkbox]').prop('checked', false);
				$(this).prop('checked', $current );
				//if( $current ){ }
			});
			
			// remove interests Most used box
			$('#interest-tabs').remove();
			$('#interest-pop').remove();
			// remove interests Ajax Quick Add 
			$('#interest-adder').remove();
			//-----Click on interest (Allows just one)
//			$(document).on("click", '#interestchecklist input[type=checkbox]', function(event) { 
//				var $current = $(this).prop('checked') ; //true or false
//				$('#interestchecklist input[type=checkbox]').prop('checked', false);
//				$(this).prop('checked', $current );
//				//if( $current ){ }
//			});
			
			$('#addmoreuc').click(function() {
				oldval = $('#ucfield_max').val();
				jQuery('#ucfield_max').val( parseInt(jQuery('#ucfield_max').val(),10) + 1 );
				newval = $('#ucfield_max').val();
				uc_new= $('.uc_new_field').clone();
				$('div.uc_new_field').removeClass('uc_new_field');
				$('div#uc_ID'+oldval).fadeIn();
				$('input[name="uc_description['+oldval+']"]').focus();
				uc_new.attr('id','uc_ID'+newval);
				$('input', uc_new).eq(0).attr('name','uc_description['+ newval +']');
				$('input', uc_new).eq(1).attr('name','uc_phone['+ newval +']');
				$('.delete', uc_new).eq(0).attr('onclick', "delete_user_contact('#uc_ID"+ newval +"');");
				$('#user_contacts').append(uc_new);
				$('#user_contacts').vSort();
			});
		});		// jQuery
		function delete_user_contact(row_id){
			jQuery(row_id).fadeOut(); 
			jQuery(row_id).remove();
			jQuery('#msgdrag').html('<?php _e('Update Client to save changes.', WPSellerEvents :: TEXTDOMAIN ); ?>').fadeIn();
		}
		</script>
		<?php
	}
	
	
}