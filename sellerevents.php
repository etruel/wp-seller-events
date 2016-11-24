<?php
/*
 Plugin Name: WP-Seller Events
 Plugin URI: http://etruel.com
 Description: Customer Relationship Management. Follow your salesmen to get a good workgroup and better results.
 Version: 1.4
 Author: etruel <esteban@netmdp.com>
 Author URI: http://www.netmdp.com
 */
# @charset utf-8
if ( ! function_exists( 'add_filter' ) )
	return;

if (is_admin()) {
		define( 'ADMIN_DIR' , ABSPATH . basename(admin_url()) ) ;
		//wp_die( ABSPATH . basename(admin_url()) . '/includes/plugin.php') ;
		include_once(ADMIN_DIR . '/includes/plugin.php' );
}
include_once('includes/sellerevents_functions.php');
include_once('includes/sellerevent_posttype.php');
include_once('includes/sellerevents_clients.php');
include_once('includes/sellerevents_eventypes.php');
include_once('includes/sellerevents_segments.php');
include_once('includes/sellerevents_channels.php');
include_once('includes/sellerevents_interests.php');
include_once('includes/WPeUsuarios.php');
include_once('includes/event_run.php');
if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	include( 'includes/Plugin_Updater.php' );
	include( 'includes/sellerevents_licenser.php' );
}
add_action( 'init', array( 'WPSellerEvents', 'init' ) );
add_filter('login_redirect', array( 'WPSellerEvents','wpse_login_redirect'), 10, 3);

add_filter('cron_schedules', array( 'WPSellerEvents', 'wpse_intervals' ) ); //add cron intervals
add_action('wpsecronhook', array( 'WPSellerEvents', 'wpsecron' ) );  //Actions for Cron job

load_plugin_textdomain( 'wpsellerevents', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
register_activation_hook( plugin_basename( __FILE__ ), array( 'WPSellerEvents', 'activate' ) );
register_deactivation_hook( plugin_basename( __FILE__ ), array( 'WPSellerEvents', 'deactivate' ) );
register_uninstall_hook( plugin_basename( __FILE__ ), array( 'WPSellerEvents', 'uninstall' ) );

if ( !class_exists( 'WPSellerEvents' ) ) {
	class WPSellerEvents extends WPSellerEvents_functions {
		const STORE_URL = 'https://etruel.com';
		const TEXTDOMAIN = 'wpsellerevents';
		const AUTHOR = 'Esteban Truelsegaard';
		const OPTION_KEY = 'WPSe_Options';
		public static $name = '';
		public static $version = '';
		public static $basen;		/** Plugin basename * @var string	 */
		public static $uri = '';
		public static $dir = '';		/** filesystem path to the plugin with trailing slash */
		public static $event_statuses = array();

		public $options = array();

		//Plugin capabilities
		private static $wpse_manager_caps = array (
			'publish_sellerevents' => true,
			'read_sellerevents' => true,
			'read_private_sellerevents' => true,
			'edit_sellerevent' => true,
			'edit_sellerevents' => true,
			'edit_published_sellerevents' => true,
			'edit_private_sellerevents' => true,
			'edit_others_sellerevents' => true,
			'delete_sellerevent' => true,
			'delete_sellerevents' => true,
			'delete_published_sellerevents' => true,
			'delete_private_sellerevents' => true,
			'delete_others_sellerevents' => true,
			// clients capabilities here
			'publish_wpse_clients' => true,
			'read_wpse_clients' => true,
			'read_private_wpse_clients' => true,
			'edit_wpse_client' => true,
			'edit_wpse_clients' => true,
			'edit_published_wpse_clients' => true,
			'edit_private_wpse_clients' => true,
			'edit_others_wpse_clients' => true,
			'delete_wpse_client' => true,
			'delete_wpse_clients' => true,
			'delete_published_wpse_clients' => true,
			'delete_private_wpse_clients' => true,
			'delete_others_wpse_clients' => true,
			// more capabilities here
			'edit_wpse_eventypes' => true,
			'edit_wpse_settings' => true,
			//'edit_wpse_report_interests' =>true,
			'edit_wpse_segments' => true,
			'edit_wpse_interests' => true,
			'edit_wpse_channels' => true, 
			// more standard capabilities here
			'read' => true,
			'upload_files' => true,
			'edit_files' => true,
			'manage_eventypes' => true,
//			'manage_options' => true,
//			'promote_users' => true,
			'remove_users' => true,
			'add_users' => true,
			'edit_users' => true,
			'list_users' => true,
			'create_users' => true,
			'delete_users' => true,
			);
		private static $wpse_seller_caps = array (
			'publish_sellerevents' => true,
			'read_sellerevents' => true,
//			'read_private_sellerevents' => true,
			'edit_sellerevent' => true,
			'edit_sellerevents' => true,
			'edit_published_sellerevents' => true,
//			'edit_private_sellerevents' => true,
//			'edit_others_sellerevents' => true,
			'delete_sellerevent' => true,
			'delete_sellerevents' => true,
//			'delete_published_sellerevents' => true,
//			'delete_private_sellerevents' => true,
//			'delete_others_sellerevents' => true,
			// clients capabilities here
			'publish_wpse_clients' => true,
			'read_wpse_clients' => true,
//			'read_private_wpse_clients' => true,
			'edit_wpse_client' => true,
			'edit_wpse_clients' => true,
			'edit_published_wpse_clients' => true,
//			'edit_private_wpse_clients' => true,
//			'edit_others_wpse_clients' => true,
			'delete_wpse_client' => true,
			'delete_wpse_clients' => true,
//			'delete_published_wpse_clients' => true,
//			'delete_private_wpse_clients' => true,
//			'delete_others_wpse_clients' => true,
			// more capabilities here
			'edit_wpse_eventypes' => true,
//			'edit_wpse_settings' => true,
			//'edit_wpse_report_interests'=>true,
			'edit_wpse_segments' => true,
			'edit_wpse_interests' => true,
			'edit_wpse_channels' => true, 
			// more standard capabilities here
			'read' => true,
			'upload_files' => true,
			'edit_files' => true,
			'manage_eventypes' => true,
//			'manage_options' => true,
//			'promote_users' => true,
//			'remove_users' => true,
//			'add_users' => true,
//			'edit_users' => true,
			'list_users' => true,
//			'create_users' => true,
//			'delete_users' => true,
			'MailPress_manage_subscriptions' => false,
			);
//		private static $wpse_customer_caps = array ('read' => true);

		
		public static function init() {
			global $wp;
			if(is_admin()) $plugin_data = get_plugin_data( __FILE__ );
			@self :: $name = $plugin_data['Name'];
			@self :: $version = $plugin_data['Version'];
			self :: $uri = plugin_dir_url( __FILE__ );
			self :: $dir = plugin_dir_path( __FILE__ );
			self :: $basen = plugin_basename(__FILE__);
			
			self :: $event_statuses = array(
				'open'	  => __('Open', WPSellerEvents :: TEXTDOMAIN ),
				'success' => __('Successfully Operation', WPSellerEvents :: TEXTDOMAIN ),
				'closed'  => __('Closed', WPSellerEvents :: TEXTDOMAIN ),
			);
			
			new self( TRUE );


			$current_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			if (admin_url('edit.php?post_type=wpsellerevents') == $current_url) {
				$wpsecfg = get_option( WPSellerEvents :: OPTION_KEY);
				$startt = date($wpsecfg['dateformat']);
				$endt = date($wpsecfg['dateformat'], strtotime($startt . "+1 days"));
				wp_redirect(admin_url('edit.php?post_type=wpsellerevents&datestart='.$startt.'&dateend='.$endt.'&byrange=yes'));
				exit;
			}

		}
		
		/**
		 * constructor
		 *
		 * @access public
		 * @param bool $hook_in
		 * @return void
		 */
		public function __construct( $hook_in = FALSE ) {
			//Admin message
			//add_action('admin_notices', array( &$this, 'wpsellerevents_admin_notice' ) ); 
			if ( ! $this->wpsellerevents_env_checks() )	return;
			$this->load_options();
			new sellerevent_posttype(); //Create post_type with taxonomies and metaboxes
			new sellerevents_eventypes();
			new sellerevents_clients(); 
			new sellerevents_segments();
			new sellerevents_interests();
			new sellerevents_channels();
			//new WPeUsuarios(WPSellerEvents :: TEXTDOMAIN);
			
			if ( $hook_in ) {
				add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

				wp_register_style( 'wpseStylesheet', self :: $uri .'css/wpse_styles.css' );
				wp_register_script( 'jquerytiptip', self :: $uri .'js/jquery.tipTip.minified.js','jQuery' );
				wp_register_style( 'oplugincss',  self :: $uri .'css/oplugins.css');
				wp_register_script( 'opluginjs',  self :: $uri .'js/oplugins.js');

				//Additional links on the plugin page
				add_filter(	'plugin_row_meta',	array(	__CLASS__, 'init_row_meta'),10,2);
				add_filter(	'plugin_action_links_' . self :: $basen, array( __CLASS__,'init_action_links'));
				//sanitize variables
				add_filter(	'wpse_check_eventdata', array( __CLASS__ ,'check_eventdata'),10,1);
				add_filter(	'wpse_check_options', array( __CLASS__,'check_options'),10,1);
								
				//add Dashboard widget
				if (!$this->options['disabledashboard']){
                    global $current_user;      
                    wp_get_current_user();	
                    $user_object = new WP_User($current_user->ID);
                    $roles = $user_object->roles;
                    $display = false;
                    if (!is_array($this->options['roles_widget'])) $this->options['roles_widget']= array( "administrator" => "administrator" );
                    foreach( $roles as $cur_role ) {
                            if ( array_search($cur_role, $this->options['roles_widget']) ) {
                                    $display = true;
                            }
                    }	
					if ( $current_user->ID && ( $display == true ) )
						add_action('wp_dashboard_setup', array( $this, 'wpsellerevents_add_dashboard'));
				}
				//Remove Wordpress core dashboard widgets
                if ( $current_user->ID && ( current_user_can('wpse_manager') ||  current_user_can('wpse_seller') ) ) {
					add_action('wp_dashboard_setup',  array( $this, 'remove_dashboard_widgets' ));
					add_action( 'admin_menu', array( $this, 'remove_menus' ) );
				}
			}
			//Disable WP_Cron
			if ($this->options['disablewpcron']) {
				define('DISABLE_WP_CRON',true);
			} //else{
			
			//test if cron active
			if ( wp_next_scheduled('wpsecronhook')===false ) {
				wp_schedule_event(0, 'wpse_5min', 'wpsecronhook');
			}
		}

		public static function wpse_login_redirect($redirect_url, $POST_redirect_url, $user) {
			if ( isset($user->ID) and ( user_can($user, 'wpse_manager') || user_can($user, 'wpse_seller') ) ) {
				return admin_url('edit.php?post_type=wpsellerevents&event_status=open');
			}
			return $redirect_url;
		}
		
		/**
		* Add cron interval
		*
		* @access protected
		* @param array $schedules
		* @return array
		*/
		static function wpse_intervals($schedules) {
			$intervals['wpse_5min'] = array('interval' => '300', 'display' => __('WPSellerEvents'));
			$schedules = array_merge( $intervals, $schedules);
			return $schedules;
		}
		//cron work  (fuera de la clase hasta que wp lo soporte)
		static function wpsecron() {
			$args = array( 'post_type' => 'wpsellerevents', 'orderby' => 'ID', 'order' => 'ASC', 'numberposts' => -1 );
			$events = get_posts( $args );
			$mensaje = "";
			foreach( $events as $post ) {
				$event = WPSellerEvents :: get_event( $post->ID );
				$activated = $event['activated'];
				$cronnextrun = $event['cronnextrun'];
				if ( !$activated )
					continue;
				if ( $cronnextrun <= current_time('timestamp') && ( 0 == (int)$event['runtime']) && ( 0 == (int)$event['starttime']) ) {
					WPSellerEvents :: wpsellerevents_dojob( $post->ID );
				}
			}
		}

		static function remove_menus() {
			remove_menu_page( 'upload.php' );			
		}
		public static function remove_dashboard_widgets() {
			global $wp_meta_boxes;
			unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_drafts']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
			unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
			unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
			unset($wp_meta_boxes['dashboard']['normal']['core']["dashboard_activity"]);
		}
		//add dashboard widget
		function wpsellerevents_add_dashboard() {
			wp_add_dashboard_widget( 'wpsellerevents_widget', self :: $name , array( $this, 'wpsellerevents_dashboard_widget') );
		}

		 //Dashboard widget
		function wpsellerevents_dashboard_widget() {
			$events= $this->get_events();
			echo '<div style="background-color: #6EDA67;border: 1px solid #DDDDDD; height: 20px; margin: -10px -10px 2px; padding: 5px 10px 0px;';
			echo "background: -moz-linear-gradient(center bottom,#C0FCBC 0,#6EDA67 98%,#FFFEA8 0);
				background: -webkit-gradient(linear,left top,left bottom,from(#C0FCBC),to(#6EDA67));
				-ms-filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#C0FCBC',endColorstr='#6EDA67');
				filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#C0FCBC',endColorstr='#6EDA67');\">";
			echo '<strong>'.__('Last Processed Events:', self :: TEXTDOMAIN ).'</strong></div>';
			@$events2 = $this->filter_by_value($events, 'runtime', '');  
			$this->array_sort($events2,'cronnextrun');
			//				echo "<pre>".print_r($events, true)."</pre>";
			if (is_array($events2)) {
				$count=0;
				//http://localhost/wordpress/wp-admin/post.php?post=9&action=edit
				foreach ($events2 as $_id => $event_data) {
					echo '<a href="'.wp_nonce_url('post.php?post='.$event_data['event_id'].'&action=edit', 'edit').'" title="'.__('Edit Event', self :: TEXTDOMAIN ).'">';
						if ($event_data['runtime']>0 ) {
							echo " <i><strong>".$event_data['event_title']."</i></strong>, ";
							echo date_i18n($this->options['dateformat'].' '.get_option('time_format'), $event_data['fromdate']).', <i>'; 
						} 
					echo '</i></a><br />';
					$count++;
					if ($count>=5)
						break;
				}		
			}
			unset($events2);
			echo '<br />';
			echo '<div style="background-color: #6EDA67;border: 1px solid #DDDDDD; height: 20px; margin: -10px -10px 2px; padding: 5px 10px 0px;';
			echo "background: -moz-linear-gradient(center bottom,#C0FCBC 0,#6EDA67 98%,#FFFEA8 0);
				background: -webkit-gradient(linear,left top,left bottom,from(#C0FCBC),to(#6EDA67));
				-ms-filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#C0FCBC',endColorstr='#6EDA67');
				filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#C0FCBC',endColorstr='#6EDA67');\">";
			echo '<strong>'.__('Next Scheduled Events:', self :: TEXTDOMAIN ).'</strong>';
			echo '</div>';
			echo '<ul style="list-style: circle inside none; margin-top: 2px; margin-left: 9px;">';
			foreach ($events as $event_id => $event_data) {
				if ($event_data['activated'] && ( 0 == (int)$event_data['runtime'] )) {
					echo '<li><a href="'.wp_nonce_url('post.php?post='.$event_data['event_id'].'&action=edit', 'edit').'" title="'.__('Edit Event', self :: TEXTDOMAIN ).'">';
					echo '<strong>'.$event_data['event_title'].'</strong>, ';
					echo date_i18n($this->options['dateformat']/*.' '.get_option('time_format')*/, $event_data['fromdate']).'. ';
					if ( (int)$event_data['starttime']>0 ) {
						$runtime=current_time('timestamp')-$event_data['starttime'];
						echo __('Running since:', self :: TEXTDOMAIN ).' '.$runtime.' '.__('sec.', self :: TEXTDOMAIN );
					} elseif ($event_data['activated']) {
						echo 'Alarm: '.date_i18n($this->options['dateformat'].' '.get_option('time_format'), $event_data['cronnextrun']);
					}
					echo '</a></li>';
				}
			}
			$events=$this->filter_by_value($events, 'activated', '');
			if (empty($events)) 
				echo '<i>'.__('None', self :: TEXTDOMAIN ).'</i><br />';
			echo '</ul>';

		}
		
		/**
		* Actions-Links del Plugin
		*
		* @param   array   $data  Original Links
		* @return  array   $data  modified Links
		*/
		public static function init_action_links($data)	{
			if ( !current_user_can('manage_options') ) {
				return $data;
			}
			return array_merge(
				$data,
				array(
					'<a href="edit.php?post_type=wpsellerevents&page=wpse_settings" title="' . __('Load WPSellerEvents Settings Page', self :: TEXTDOMAIN ) . '">' . __('Settings', self :: TEXTDOMAIN ) . '</a>',
				)
			);
		}


		/**
		* Meta-Links del Plugin
		*
		* @param   array   $data  Original Links
		* @param   string  $page  plugin actual
		* @return  array   $data  modified Links
		*/

		public static function init_row_meta($data, $page)	{
			if ( $page != self::$basen ) {
				return $data;
			}
			return array_merge(
				$data,
				array(
				'<a href="http://wordpress.org/extend/plugins/wpsellerevents/faq/" target="_blank">' . __('FAQ') . '</a>',
				'<a href="http://www.etruel.com/" target="_blank">' . __('Support') . '</a>',
//				'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=B8V39NWK3NFQU" target="_blank">' . __('Donate', self :: TEXTDOMAIN ) . '</a>'
				)
			);
		}		
		
	
		/**
		 * admin menu
		 *
		 * @access public
		 * @return void
		 */
		public function admin_menu() {	
			//Clientss
			$page = add_submenu_page(
				'edit.php?post_type=wpsellerevents',
				__( 'Clients', self :: TEXTDOMAIN ),
				__( 'Clients List', self :: TEXTDOMAIN ),
				'edit_wpse_client',
				'edit.php?post_type=wpse_client'
			);
			add_action( 'admin_print_styles-' . $page, array(&$this, 'wpse_admin_styles') );

			$page = add_submenu_page(
				'edit.php?post_type=wpsellerevents',
				__( 'Add Client', self :: TEXTDOMAIN ),
				__( 'Add Client', self :: TEXTDOMAIN ),
				'edit_wpse_client',
				'post-new.php?post_type=wpse_client'
			);
			add_action( 'admin_print_styles-' . $page, array(&$this, 'wpse_admin_styles') );

			//Event Types
			$page = add_submenu_page(
				'edit.php?post_type=wpsellerevents',
				__( 'Event Types', self :: TEXTDOMAIN ),
				__( 'Event Types', self :: TEXTDOMAIN ),
				'edit_wpse_client',
				'edit-tags.php?taxonomy=eventype'
			);
			add_action( 'admin_print_styles-' . $page, array(&$this, 'wpse_admin_styles') );

			//Segments
			$page = add_submenu_page(
				'edit.php?post_type=wpsellerevents',
				__( 'Client Segments', self :: TEXTDOMAIN ),
				__( 'Segments', self :: TEXTDOMAIN ),
				'edit_wpse_client',
				'edit-tags.php?taxonomy=segment'
			);
			add_action( 'admin_print_styles-' . $page, array(&$this, 'wpse_admin_styles') );

		
			//Interests
			$page = add_submenu_page(
				'edit.php?post_type=wpsellerevents',
				__( 'Client Interests', self :: TEXTDOMAIN ),
				__( 'Interests', self :: TEXTDOMAIN ),
				'edit_wpse_client',
				'edit-tags.php?taxonomy=interest'
			);
			add_action( 'admin_print_styles-' . $page, array(&$this, 'wpse_admin_styles') );

			//channels
			$page = add_submenu_page(
				'edit.php?post_type=wpsellerevents',
				__( 'Client Channels', self :: TEXTDOMAIN ),
				__( 'Channels', self :: TEXTDOMAIN ),
				'edit_wpse_client',
				'edit-tags.php?taxonomy=channel'
			);
			add_action( 'admin_print_styles-' . $page, array(&$this, 'wpse_admin_styles') );

			// Settings
			$page = add_submenu_page(
				'edit.php?post_type=wpsellerevents',
				__( 'Settings', self :: TEXTDOMAIN ),
				__( 'Settings', self :: TEXTDOMAIN ),
				'edit_wpse_settings',
				'wpse_settings',
				array( &$this, 'add_admin_submenu_page' )
			);
			add_action( 'admin_print_styles-' . $page, array(&$this, 'wpse_admin_styles') );

			$page = add_submenu_page(
				'edit.php?post_type=wpsellerevents',
				__( 'Report Interests', self :: TEXTDOMAIN ),
				__( 'Report Interests', self :: TEXTDOMAIN ),
				'edit_wpse_client',
				'wpse_report_interests',
				array( &$this, 'add_report_interest_page' )
			);
			add_action( 'admin_print_styles-' . $page, array(&$this, 'wpse_admin_styles') );

			//License
			$page = add_submenu_page(
				'edit.php?post_type=wpsellerevents',
				__( 'License', self :: TEXTDOMAIN ),
				__( 'License', self :: TEXTDOMAIN ),
				'edit_wpse_settings',
				'wpse_license',
				array( &$this, 'add_license_page' )
			);
			add_action( 'admin_print_styles-' . $page, array(&$this, 'wpse_admin_styles') );

		}

		function wpse_admin_styles() {
			wp_enqueue_style( 'wpseStylesheet' );
			//wp_enqueue_style( 'oplugincss' );			
			//wp_enqueue_script( 'jquerytiptip' );
			//wp_enqueue_script( 'opluginjs' );
		}

		
		/**
		 * the interest report
		 *
		 * @access public
		 * @return void
		 */

		public function add_report_interest_page(){
			include_once("includes/report_interests_page.php");
		}

		/**
		 * the license page
		 *
		 * @access public
		 * @return void
		 */
		public function add_license_page () {
			global $pagenow;
			?>
			<div id="licenses">
				<div class="postbox ">
				<div class="inside">
				<?php
				/**
				 * Display license page
				 */
				settings_errors();
				if(!has_action('wpse_licenses_forms')) {
					echo '<div class="msg"><p>', __('This is where you would enter the license keys for one of our premium plugins, should you activate one.', self :: TEXTDOMAIN), '</p></div>';
				}else {
					do_action('wpse_licenses_forms');
				}
				?>
				</div>
				</div>
			</div>			
			<?php			
		}
		/**
		 * an admin submenu page
		 *
		 * @access public
		 * @return void
		 */
		public function add_admin_submenu_page () {
			global $pagenow;
			$currenttab = (isset($_GET['tab']) ) ? $_GET['tab'] : 'homepage' ;
			$tabs = array( 'homepage' => 'Settings' );  // Agregar pestañas aca
			if ( 'POST' === $_SERVER[ 'REQUEST_METHOD' ] ) {
				if ( get_magic_quotes_gpc() ) {
					$_POST = array_map( 'stripslashes_deep', $_POST );
				}
				# evaluation goes here
				check_admin_referer('wpsellerevents-settings');
				$errlev = error_reporting();
				error_reporting(E_ALL & ~E_NOTICE);  // desactivo los notice que aparecen con los _POST
				$wpsecfg = $this->options;
				if($pagenow=='edit.php' && $_GET['post_type']=='wpsellerevents' && $_GET['page']=='wpse_settings' ){
					switch ( $currenttab ){
					case 'homepage' :
						$wpsecfg = apply_filters('wpse_check_options',$_POST);
						break;
					case 'extensions' :
						break;
					}
				}
				// Roles 
				global $wp_roles, $current_user;    
				get_currentuserinfo();
				$role_conf = array();
				foreach ( $_POST['role_name'] as $role_id => $role_val ) {
					$role_conf["$role_val"]= $role_val;
				}
				$wpsecfg['roles_widget'] = $role_conf; 
				
				error_reporting($errlev);
				
				$this->options = $wpsecfg;
				# saving
				if ( $this->update_options() ) {
					?><div class="updated"><p> <?php _e( 'Settings saved.', self :: TEXTDOMAIN );?></p></div><?php
				}else{
				/*	?><div class="error"><p> <?php _e( 'Settings NOT saved.', self :: TEXTDOMAIN );?></p></div><?php  */
				}
			}
//			add_action('admin_head', array( &$this,'wpesettings_admin_head'));
			include_once( self :: $dir . "includes/settings_page.php");
		}
		
		function wpesettings_admin_head() {
			?>
			<?php
		}
		

		/**
		 * load_options
		 *
		 * @access protected
		 * @return void
		 */
		public function load_options() {
			$wpsecfg= get_option( self :: OPTION_KEY );
			if ( !$wpsecfg ) {
				$this->options = $this->check_options( array() );
				add_option( self :: OPTION_KEY, $this->options , '', 'yes' );
			}else {
				$this->options = $this->check_options( $wpsecfg );
//              $this->options = apply_filters('wpse_check_options',$wpsecfg );
			}
		}

		public static function check_options($options) {
			$wpsecfg['mailmethod']	= (!isset($options['mailmethod'])) ?'mail':$options['mailmethod'];
			$wpsecfg['mailsndemail']	= (!isset($options['mailsndemail'])) ? '':sanitize_email($options['mailsndemail']);
			$wpsecfg['mailsndname']	= (!isset($options['mailsndname'])) ? '':$options['mailsndname'];
			$wpsecfg['mailsendmail']	= (!isset($options['mailsendmail'])) ? '': untrailingslashit(str_replace('//','/',str_replace('\\','/',stripslashes($options['mailsendmail']))));
			$wpsecfg['mailsecure']	= (!isset($options['mailsecure'])) ? '': $options['mailsecure'];
			$wpsecfg['mailhost']	= (!isset($options['mailhost'])) ? '': $options['mailhost'];
			$wpsecfg['mailport']	= (!isset($options['mailport'])) ? '': $options['mailport'];
			$wpsecfg['mailuser']	= (!isset($options['mailuser'])) ? '': $options['mailuser'];
			$wpsecfg['mailpass']	= (!isset($options['mailpass'])) ? '': base64_encode($options['mailpass']);
			$wpsecfg['disabledashboard']= (!isset($options['disabledashboard']) || empty($options['disabledashboard'])) ? false : ($options['disabledashboard']==1) ? true : false;
			$wpsecfg['roles_widget']	= (!isset($options['roles_widget']) || !is_array($options['roles_widget'])) ? array( "administrator" => "administrator", "wpse_manager" => "wpse_manager" ): $options['roles_widget'];
			$wpsecfg['disablewpcron']	= (!isset($options['disablewpcron']) || empty($options['disablewpcron'])) ? false: ($options['disablewpcron']==1) ? true : false;
			$wpsecfg['dateformat']	= (!isset($options['dateformat'])) ? 'm/d/Y': $options['dateformat'];
			$wpsecfg['consideration_days'] = (!isset($options['consideration_days'])) ? '': $options['consideration_days'];
			$wpsecfg['editor_type'] =  (!isset($options['editor_type'])) ? '': $options['editor_type'];
			return $wpsecfg;
		}
		
		/**
		 * update_options
		 *
		 * @access protected
		 * @return bool True, if option was changed
		 */
		public function update_options() {
			return update_option( self :: OPTION_KEY, $this->options );
		}

		/**
		 * activation
		 *
		 * @access public
		 * @static
		 * @return void
		 */
		public static function activate() {
			global $wp_roles;
			$menupage = new sellerevent_posttype();
			add_role( 'wpse_manager', __( 'Manager', self :: TEXTDOMAIN ), self::$wpse_manager_caps );
			add_role( 'wpse_seller', __( 'Salesman', self :: TEXTDOMAIN ), self::$wpse_seller_caps  );
//			add_role( 'wpse_customer', __( 'Customer', self :: TEXTDOMAIN ), self::$wpse_customer_caps );
			
			//Add capabilities to admin (if don't want to allow admins to edits Seller events can be disabled from Settings ;-)
			foreach(self::$wpse_manager_caps as $key => $value) {
				$wp_roles->add_cap( 'administrator', $key, $value );
			}
			
			// ATTENTION: This is *only* done during plugin activation hook // You should *NEVER EVER* do this on every page load!!
			flush_rewrite_rules();			
	
			//remove old cron jobs
			$args = array( 'post_type' => 'wpsellerevents', 'orderby' => 'ID', 'order' => 'ASC' );
			$events = get_posts( $args );
			foreach( $events as $post ) {
				$event = self :: get_event( $post->ID );	
				$activated = $event['activated'];
				if ($time=wp_next_scheduled('wpsecronhook',array('event_id'=>$post->ID )))
					wp_unschedule_event($time,'wpsecronhook',array('event_id'=>$post->ID ));	
			}
			wp_clear_scheduled_hook('wpsecronhook');
			//make schedule
			wp_schedule_event(time(), 'wpse_intervals', 'wpsecronhook'); 
		}

		/**
		 * deactivation
		 *
		 * @access public
		 * @static
		 * @return void
		 */
		public static function deactivate() {
			global $wp_roles;
			 remove_role( 'wpse_manager' ); 
			 remove_role( 'wpse_seller' ); 
			 remove_role( 'wpse_customer' ); 
			foreach(self::$wpse_manager_caps as $key => $value) {
				$adm_cap = array('read','upload_files','edit_files','manage_eventypes',
					'manage_options','promote_users','remove_users','add_users','edit_users',
					'list_users','create_users','delete_users',);
				if(!in_array($key, $adm_cap ))
					$wp_roles->remove_cap( 'administrator', $key, $value );
			}
			//remove old cron jobs
			$args = array( 'post_type' => 'wpsellerevents', 'orderby' => 'ID', 'order' => 'ASC' );
			$events = get_posts( $args );
			foreach( $events as $post ) {
				$event = self :: get_event( $post->ID );
				$activated = $event['activated'];
				if ($time=wp_next_scheduled('wpsecronhook',array('event_id'=>$post->ID)))
					wp_unschedule_event($time,'wpsecronhook',array('event_id'=>$post->ID));	
			}
			wp_clear_scheduled_hook('wpsecronhook');
			// NO borro opciones ni campañas
		}

		/**
		 * uninstallation
		 *
		 * @access public
		 * @static
		 * @global $wpdb, $blog_id
		 * @return void
		 */
		public static function uninstall() {
			global $wpdb, $blog_id;
			if ( is_network_admin() ) {
				if ( isset ( $wpdb->blogs ) ) {
					$blogs = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT blog_id ' .
							'FROM ' . $wpdb->blogs . ' ' .
							"WHERE blog_id <> '%s'",
							$blog_id
						)
					);
					foreach ( $blogs as $blog ) {
						delete_blog_option( $blog->blog_id, self :: OPTION_KEY );
					}
				}
			}
			delete_option( self :: OPTION_KEY );
			//self :: delete_events();
		}
	}
}


