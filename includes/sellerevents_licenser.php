<?php
// don't load directly 
if ( !defined('ABSPATH') || !defined('WP_ADMIN') ) {
	return;
}

$WPSE_Licenser = new WPSE_Licenser();
class WPSE_Licenser {
	const STORE_URL = 'http://etruel.com';
	const AUTHOR = 'Esteban Truelsegaard';

	private static $name = '';
	private static $version = '';
		
	function __construct() {
		//add_action( 'init', array( $this, 'init' ), 20 );
		add_action( 'admin_init', array( $this, 'admin_plugin_updater'), 0 );
		add_action( 'admin_init', array( $this, 'register_option') );
		add_action('admin_init', array( $this, 'wpse_activate_license') );
		add_action('admin_init', array( $this, 'wpse_deactivate_license') );
		add_action( 'wpse_licenses_forms', array( $this, 'license_page' ) );
	}

	/** Make sure the needed scripts are loaded for admin pages */
//	function init() {
//		add_action( 'wpse_licenses_forms', array( $this, 'license_page' ) );
//	}
	
	function admin_plugin_updater() {
		self :: $name = WPSellerEvents :: $name;
		self :: $version = WPSellerEvents :: $version;

		// retrieve our license key from the DB
		$license_key = trim( get_option( 'wpse_license_key' ) );

		// setup the updater
		$edd_updater = new EDD_SL_Plugin_Updater( self::STORE_URL, WPSellerEvents :: $dir . "sellerevents.php", array(
			'version' 	=> self :: $version, 	// current version number
			'license' 	=> $license_key, 		// license key (used get_option above to retrieve from DB)
			'item_name' => self::$name,		// name of this plugin
			'author' 	=> self::AUTHOR			// author of this plugin
		));
	}
	
	function register_option() {	
		register_setting('wpse_license', 'wpse_license_key', array( $this, 'sanitize_license') );
	}
	function sanitize_license( $new ) {
		$old = get_option( 'wpse_license_key' );
		if( $old && $old != $new ) {
			delete_option( 'wpse_license_status' ); // new license has been entered, so must reactivate
		}
		return $new;
	}
	function license_page() {
		error_reporting(E_ALL);
		$license 	= get_option( 'wpse_license_key' );
		$status 	= get_option( 'wpse_license_status' );
		?>
		<div class="wrap">
			<img src="<?php echo WPSellerEvents :: $uri; ?>images/logo.jpg" alt="" style="float: left;">
			<div style="float: left;padding: 30px;">
				<h2><?php _e('Plugin License Options', 'wpsellerevents'); ?></h2>
				<form method="post" action="options.php">
					<?php settings_fields('wpse_license'); ?>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row" valign="top">
									<?php _e('License Key', 'wpsellerevents'); ?>
								</th>
								<td>
									<input id="wpse_license_key" name="wpse_license_key" type="text" class="regular-text" value="<?php esc_attr_e($license); ?>" /><br />
									<label class="description" for="wpse_license_key"><?php _e('Enter your license key', 'wpsellerevents'); ?></label>
								</td>
							</tr>
							<?php if(false !== $license) { ?>
								<tr valign="top">
									<th scope="row" valign="top">
										<?php _e('Activate License', 'wpsellerevents'); ?>
									</th>
									<td>
										<?php if($status !== false && $status == 'valid') { ?>
											<span style="color:green;"><?php _e('active', 'wpsellerevents'); ?></span>
											<?php wp_nonce_field('wpse_nonce', 'wpse_nonce'); ?>
											<input type="submit" class="button-secondary" name="wpse_license_deactivate" value="<?php _e('Deactivate License', 'wpsellerevents'); ?>"/>
										<?php }else {
											wp_nonce_field('wpse_nonce', 'wpse_nonce');
											?>
											<input type="submit" class="button-secondary" name="wpse_license_activate" value="<?php _e('Activate License', 'wpsellerevents'); ?>"/>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
			<div class="clear"></div>
		</div>
		<?php
	}


	function wpse_activate_license() {
		// listen for our activate button to be clicked
		if( isset( $_POST['wpse_license_activate'] ) ) {

			// run a quick security check
			if( ! check_admin_referer( 'wpse_nonce', 'wpse_nonce' ) )
				return; // get out if we didn't click the Activate button

			// retrieve the license from the database
			$license = trim( get_option( 'wpse_license_key' ) );


			// data to send in our API request
			$api_params = array(
				'edd_action'=> 'activate_license',
				'license' 	=> $license,
				'item_name' => urlencode( self :: $name ), // the name of our product in EDD
				'url'       => home_url()
			);

			// Call the custom API.
			//$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, self::STORE_URL ) ), array( 'timeout' => 15, 'sslverify' => false ) );
			$response = wp_remote_post( self::STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			//die('<pre>'.home_url().'<br/>'.  print_r($response,1).'<br/>'.  print_r($_POST,1).'</pre>');

			// $license_data->license will be either "valid" or "invalid"

			update_option( 'wpse_license_status', $license_data->license );

		}
	}

	function wpse_deactivate_license() {
		// listen for our activate button to be clicked
		if( isset( $_POST['wpse_license_deactivate'] ) ) {
			// run a quick security check
			if( ! check_admin_referer( 'wpse_nonce', 'wpse_nonce' ) )
				return; // get out if we didn't click the Activate button
			// retrieve the license from the database
			$license = trim( get_option( 'wpse_license_key' ) );

			// data to send in our API request
			$api_params = array(
				'edd_action'=> 'deactivate_license',
				'license' 	=> $license,
				'item_name' => urlencode( self :: $name ), // the name of our product in EDD
				'url'       => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( self::STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
 			if ( is_wp_error( $response ) )  return false;

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "deactivated" or "failed"
			if( $license_data->license == 'deactivated' )
				delete_option( 'wpse_license_status' );
		}
	}
	

} //class
?>
