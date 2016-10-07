<?php
/**
 * Description of sellerevents_clients
 *
 * @author Esteban Truelsegaard
 * @copyright (c) 2015, Esteban Truelsegaard
 * @package WP-Seller-Events
 * 
 */
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

include_once("sellerevents_clientsedit.php");

class sellerevents_clients {
	function __construct() {
		global $pagenow;
		new sellerevents_clientsedit();
		$this->register_cpt_wpse_client();
		add_filter(	'wpse_check_client', array( __CLASS__,'check_client'),10,1);
		add_action('save_post', array( __CLASS__ , 'save_client_data'));
		// Clients list
		add_filter('manage_edit-wpse_client_columns' , array( __CLASS__, 'set_edit_wpse_client_columns'));
		add_action('manage_wpse_client_posts_custom_column',array(__CLASS__,'custom_wpse_client_column'),10,2);
		add_filter("manage_edit-wpse_client_sortable_columns", array( __CLASS__, "sortable_columns") );
		
		if( ($pagenow == 'edit.php') && (isset($_GET['post_type']) && $_GET['post_type'] == 'wpse_client') ) {
			add_filter('post_row_actions' , array( __CLASS__, 'wpse_client_quick_actions'), 10, 2);
			add_action('pre_get_posts', array( __CLASS__, 'column_orderby') );
			add_action('pre_get_posts', array( __CLASS__, 'query_set_only_author') );

			add_action('restrict_manage_posts', array( __CLASS__, 'custom_filters') );
//			add_action('parse_query', array( __CLASS__, 'parse_query_custom_filters') );
			
//			add_action('admin_print_styles-edit.php', array(__CLASS__,'list_admin_styles'));
//			add_action('admin_print_scripts-edit.php', array(__CLASS__,'list_admin_scripts'));
		}
	}
	
	function register_cpt_wpse_client() {

		$labels = array( 
			'name' => __( 'Clients', WPSellerEvents :: TEXTDOMAIN ),
			'singular_name' => __( 'Client', WPSellerEvents :: TEXTDOMAIN ),
			'add_new' => __( 'Add New', WPSellerEvents :: TEXTDOMAIN ),
			'add_new_item' => __( 'Add New Client', WPSellerEvents :: TEXTDOMAIN ),
			'edit_item' => __( 'Edit Client', WPSellerEvents :: TEXTDOMAIN ),
			'new_item' => __( 'New Client', WPSellerEvents :: TEXTDOMAIN ),
			'view_item' => __( 'View Client', WPSellerEvents :: TEXTDOMAIN ),
			'search_items' => __( 'Search Clients', WPSellerEvents :: TEXTDOMAIN ),
			'not_found' => __( 'No clients found', WPSellerEvents :: TEXTDOMAIN ),
			'not_found_in_trash' => __( 'No clients found in Trash', WPSellerEvents :: TEXTDOMAIN ),
			'parent_item_colon' => __( 'Parent Client:', WPSellerEvents :: TEXTDOMAIN ),
			'menu_name' => __( 'Clients', WPSellerEvents :: TEXTDOMAIN ),
		);
		$capabilities = array(
			'publish_post' => 'publish_wpse_client',
			'publish_posts' => 'publish_wpse_clients',
			'read_post' => 'read_wpse_client',
			'read_private_posts' => 'read_private_wpse_clients',
			'edit_post' => 'edit_wpse_client',
			'edit_published_posts' => 'edit_published_wpse_clients',
			'edit_private_posts' => 'edit_private_wpse_clients',
			'edit_posts' => 'edit_wpse_clients',
			'edit_others_posts' => 'edit_others_wpse_clients',
			'delete_post' => 'delete_wpse_client',
			'delete_posts' => 'delete_wpse_clients',
			'delete_published_posts' => 'delete_published_wpse_clients',
			'delete_private_posts' => 'delete_private_wpse_clients',
			'delete_others_posts' => 'delete_others_wpse_clients',
			);

		$args = array( 
			'labels' => $labels,
			'hierarchical' => false,
			'description' => 'WP-Seller-Events Clients',
			'supports' => array( 'title', 'thumbnail',/* 'custom-fields' */),
			'taxonomies' => array( 'segment', 'channel', 'interest' ),
			'register_meta_box_cb' => array( 'sellerevents_clientsedit' , 'create_meta_boxes'),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => false, // 'edit.php?post_type=wpsellerevents',
			'menu_position' => 3,
			'menu_icon' => '/images/icon_20.png',
			'show_in_nav_menus' => false,
			'publicly_queryable' => false,
			'exclude_from_search' => false,
			'has_archive' => false,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => true,
			'capabilities' => $capabilities,
		);

		register_post_type( 'wpse_client', $args );
	}
	

	
	public static function custom_filters($options) {
		global $typenow, $wp_query;
		global $current_user, $pagenow;
		if($pagenow=='edit.php' && is_admin() && current_user_can('read_wpse_client') && $typenow=='wpse_client') {
			
			$taxonomy = 'segment';
			$term = get_term_by('slug', 
								(isset($wp_query->query_vars['segment'])) ? $wp_query->query_vars['segment'] : '' , 
								$taxonomy
					);
			if($term===false ) $term = (object)array('slug'=> '');
			$args = array(
				'show_option_all' =>  __("Show All Segments", WPSellerEvents :: TEXTDOMAIN),
				'taxonomy'      =>  $taxonomy,
				'name'          =>  $taxonomy,
				'orderby'       =>  'name',
				'selected'      =>  $term->slug,
				'hierarchical'  =>  true,
				'depth'         =>  3,
				'show_count'    =>  true, // Show # segment in parens
				'hide_empty'    =>  true, // Don't show clients w/o segment
				'hide_if_empty' =>  true, // Don't shows select if no items in taxonomy
				'value_field'	=> 'slug',
			);
			wp_dropdown_categories( $args );

			$taxonomy = 'interest';
			$term = get_term_by('slug', 
								(!empty($wp_query->query_vars['interest'])) ? $wp_query->query_vars['interest'] : '' , 
								$taxonomy
					);
			if($term===false or empty($term) ) $term = (object)array('slug'=> '');
			$args = array(
				'show_option_all' =>  __("Show All Interests", WPSellerEvents :: TEXTDOMAIN),
				'taxonomy'      =>  $taxonomy,
				'name'          =>  $taxonomy,
				'orderby'       =>  'name',
				'selected'      =>  $term->slug,
				'hierarchical'  =>  true,
				'depth'         =>  3,
				'show_count'    =>  true, // Show # interest in parens
				'hide_empty'    =>  true, // Don't show clients w/o interest
				'hide_if_empty' =>  true, // Don't shows select if no items in taxonomy
				'value_field'	=> 'slug',
			);
			wp_dropdown_categories( $args );
			
			$taxonomy = 'channel';
			$term = get_term_by('slug', 
								(isset($wp_query->query_vars['channel'])) ? $wp_query->query_vars['channel'] : '' , 
								$taxonomy
					);
			if($term===false ) $term = (object)array('slug'=> '');
			$args = array(
				'show_option_all' =>  __("Show All Channels", WPSellerEvents :: TEXTDOMAIN),
				'taxonomy'      =>  $taxonomy,
				'name'          =>  $taxonomy,
				'orderby'       =>  'name',
				'selected'      =>  $term->slug,
				'hierarchical'  =>  true,
				'depth'         =>  3,
				'show_count'    =>  true, // Show # channel in parens
				'hide_empty'    =>  true, // Don't show clients w/o channel
				'hide_if_empty' =>  true, // Don't shows select if no items in taxonomy
				'value_field'	=> 'slug',
			);
			wp_dropdown_categories( $args );
			
		}
	}

	
	public static function check_client($options) {
		$client_data['email']	= (!isset($options['email'])) ? '' : $options['email'];
		$client_data['address']	= (!isset($options['address'])) ? '' : $options['address'];
		$client_data['phone']	= (!isset($options['phone']))	? '' : $options['phone'];
		$client_data['cellular']= (!isset($options['cellular']))? '' : $options['cellular'];
		$client_data['facebook']= (!isset($options['facebook']))? '' : $options['facebook'];

		$user_contacts = (!isset($options['user_contacts']))? Array() : $options['user_contacts'];
		// Proceso los array sacando los que estan en blanco
//		if(!isset($options['user_contacts'])) $user_contacts = Array() ;
		if(isset($options['uc_description'])) {
			foreach($options['uc_description'] as $id => $cf_value) {       
				$uc_description = esc_attr($options['uc_description'][$id]);
				$uc_phone = esc_attr($options['uc_phone'][$id]);
				$uc_email = esc_attr($options['uc_email'][$id]);
				$uc_position = esc_attr($options['uc_position'][$id]);
				$uc_address = esc_attr($options['uc_address'][$id]);
				if(!empty($uc_description))  {
					$user_contacts['description'][]=$uc_description ;
					$user_contacts['phone'][]=$uc_phone ;
					$user_contacts['email'][]=$uc_email ;
					$user_contacts['position'][]=$uc_position ;
					$user_contacts['address'][]=$uc_address ;
				}
			}
		}
		if(!isset($user_contacts['description'])) {
			$user_contacts = array(
				'description'=>array(''),
				'phone'=>array(''),
				'email'=>array(''),
				'position'=>array(''),
				'address'=>array(''),	
			);
		}
		$client_data['user_contacts']= $user_contacts;
		$client_data['user_aseller']= (isset($options['user_aseller'])) ? $options['user_aseller'] : 0 ;

		return $client_data;
	}
		
	public static function get_client_data( $client_id = 0){
		global $post, $post_id;
		if($client_id==0) {
			if( isset( $post->ID ) ) {
				$client_id==$post->ID;
			}elseif( isset( $post_id ) ) {
				$client_id==$post_id;
			}else {
				return false;
			}
		}
		$client_data = array();
		$custom_fields = get_post_custom($client_id) ;
		foreach ( $custom_fields as $field_key => $field_values ) {
			if(!isset($field_values[0])) continue;
			//echo $field_key . '=>' . $field_values[0];
			$client_data[$field_key] = get_post_meta( $client_id, $field_key, true );
		}	
		
		$client_data = apply_filters('wpse_check_client', $client_data );
		
		return $client_data;
	}
	
	
	public static function update_client( $client_id, $client_data){
		foreach ( $client_data as $field_key => $field_values ) {
			if(!isset($field_values)) continue;
			//echo $field_key . '=>' . $field_values[0];
			add_post_meta( $client_id, $field_key, $field_values, true )  or
				update_post_meta( $client_id, $field_key, $field_values);
		}
	}
	
	public static function save_client_data( $post_id ) {
		global $post, $cfg;
		if((defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit'])) {
			//WPSellerEvents ::save_quick_edit_post($post_id);
			return $post_id;
		}
		if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']))
			return $post_id;
		if ( !wp_verify_nonce( @$_POST['wpse_client_nonce'], 'edit-client' ) )
			return $post_id;
		if($post->post_type != 'wpse_client') return $post_id;
		// Stop WP from clearing custom fields on autosave, and also during ajax requests (e.g. quick edit) and bulk edits.

		$nivelerror = error_reporting(E_ERROR | E_WARNING | E_PARSE);

		$_POST['ID']=$post_id;		
		$client = array();
		$client = apply_filters('wpse_check_client', $_POST);

		error_reporting($nivelerror);
		
		self::update_client($post_id, $client);

		return $post_id ;
	}

	
	
	public static function custom_wpse_client_column($columns) { //this function display the columns contents
		switch ( $column ) {
		  case 'status':
			//echo $event_data['event_posttype']; 
			break;
		}
		
	}
	
	public static function set_edit_wpse_client_columns($columns) { //this function display the columns headings
		//add_filter( 'editable_slug', array(__CLASS__,'inline_custom_fields'),999,1);
		$new_columns = array(
			'title' => __('Client Name', WPSellerEvents :: TEXTDOMAIN),
			'date' => __('Added', WPSellerEvents :: TEXTDOMAIN),
			'taxonomy-segment' => __('Segment', WPSellerEvents :: TEXTDOMAIN),
			'taxonomy-interest' => __('Interest', WPSellerEvents :: TEXTDOMAIN),
			'taxonomy-channel' => __('Channel', WPSellerEvents :: TEXTDOMAIN),
		);
		return $new_columns;
		//return wp_parse_args($new_columns, $columns);
	}
	// Make these columns sortable
	public static function sortable_columns($columns) {
		$custom = array(
		//	'start' => 'startdate',
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

			default:
				break;
		}
	} 
		// Show only posts and media related to logged in author
	public static function query_set_only_author( $wp_query ) {
		global $current_user;
		if( is_admin() && !current_user_can('edit_others_sellerevents') ) {
			$wp_query->set( 'author', $current_user->ID );
			add_filter('views_edit-wpse_client',  array(__CLASS__,'fix_post_counts'));
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
				'post_type'   => 'wpse_client',
				'post_status' => $type['status']
			);
			$result = new WP_Query($query);
			if( $type['status'] == NULL ):
				$class = ($wp_query->query_vars['post_status'] == NULL) ? ' class="current"' : '';
				$views['all'] = sprintf(__('<a href="%s"'. $class .'>All <span class="count">(%d)</span></a>', 'all'),
					admin_url('edit.php?post_type=wpse_client'),
					$result->found_posts);
			elseif( $type['status'] == 'publish' ):
				$class = ($wp_query->query_vars['post_status'] == 'publish') ? ' class="current"' : '';
				$views['publish'] = sprintf(__('<a href="%s"'. $class .'>Published <span class="count">(%d)</span></a>', 'publish'),
					admin_url('edit.php?post_status=publish&post_type=wpse_client'),
					$result->found_posts);
			elseif( $type['status'] == 'draft' ):
				$class = ($wp_query->query_vars['post_status'] == 'draft') ? ' class="current"' : '';
				$views['draft'] = sprintf(__('<a href="%s"'. $class .'>Draft'. ((sizeof($result->posts) > 1) ? "s" : "") .' <span class="count">(%d)</span></a>', 'draft'),
					admin_url('edit.php?post_status=draft&post_type=wpse_client'),
					$result->found_posts);
			elseif( $type['status'] == 'pending' ):
				$class = ($wp_query->query_vars['post_status'] == 'pending') ? ' class="current"' : '';
				$views['pending'] = sprintf(__('<a href="%s"'. $class .'>Pending <span class="count">(%d)</span></a>', 'pending'),
					admin_url('edit.php?post_status=pending&post_type=wpse_client'),
					$result->found_posts);
			elseif( $type['status'] == 'trash' ):
				$class = ($wp_query->query_vars['post_status'] == 'trash') ? ' class="current"' : '';
				$views['trash'] = sprintf(__('<a href="%s"'. $class .'>Trash <span class="count">(%d)</span></a>', 'trash'),
					admin_url('edit.php?post_status=trash&post_type=wpse_client'),
					$result->found_posts);
			endif;
		}
		return $views;
	}
	//change actions from custom post type list
	public static function wpse_client_quick_actions( $actions ) {
/*		global $post;
		if( $post->post_type == 'wpse_client_quick_actions' ) {
*/	//		unset( $actions['edit'] );
			unset( $actions['view'] );
	//		unset( $actions['trash'] );
			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['clone'] );
			unset( $actions['edit_as_new_draft'] );
//		}
		return $actions;
	}	
	
}
