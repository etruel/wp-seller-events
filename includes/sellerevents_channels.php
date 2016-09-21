<?php
/**
 * Description of sellerevents_channels
 * Registers the 'channel' taxonomy for clients. 
 * @category taxonomies * 
 * @author esteban
 */
class sellerevents_channels {
	function __construct() {
		global $pagenow;
		$this->create_tax_channels(); //for clients
		//columns 
		add_action("manage_edit-channel_columns", array( __CLASS__, 'channel_taxonomy_columns') ); 
		add_action('manage_channel_custom_column', array(__CLASS__,'manage_channel_column'), 10, 3 );
		if( ($pagenow == 'edit-tags.php') && (isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'channel') ) {
			add_action('parent_file',  array( __CLASS__, 'channel_tax_menu_correction'));
			//Javascripts 
			add_action('admin_enqueue_scripts', array( __CLASS__, 'channel_add_admin_scripts'), 10, 1);
		}

	}
	
	function create_tax_channels(){
		 register_taxonomy(
			'channel',
			array('wpse_client'),
			array(
				'public' => false,  //just use in admin?
				'show_ui' => true,
				'show_in_menu' => false,
				'meta_box_cb'	=> 'post_categories_meta_box',
				'hierarchical' => true,
				'labels' => array(
					'name' => __( 'Channels', WPSellerEvents :: TEXTDOMAIN ),
					'singular_name' => __( 'Channel', WPSellerEvents :: TEXTDOMAIN ),
					'menu_name' => __( 'Channels', WPSellerEvents :: TEXTDOMAIN ),
					'search_items' => __( 'Search Channels', WPSellerEvents :: TEXTDOMAIN ),
					'popular_items' => __( 'Popular Channels', WPSellerEvents :: TEXTDOMAIN ),
					'all_items' => __( 'All Channels', WPSellerEvents :: TEXTDOMAIN ),
					'edit_item' => __( 'Edit Channel', WPSellerEvents :: TEXTDOMAIN ),
					'update_item' => __( 'Update Channel', WPSellerEvents :: TEXTDOMAIN ),
					'add_new_item' => __( 'Add New Channel', WPSellerEvents :: TEXTDOMAIN ),
					'new_item_name' => __( 'New Channel Name', WPSellerEvents :: TEXTDOMAIN ),
					'separate_items_with_commas' => __( 'Separate channels with commas', WPSellerEvents :: TEXTDOMAIN ),
					'add_or_remove_items' => __( 'Add or remove channels', WPSellerEvents :: TEXTDOMAIN ),
					'choose_from_most_used' => __( 'Choose from the most popular channels', WPSellerEvents :: TEXTDOMAIN ),
				),
				'rewrite' => array(
					'with_front' => true,
					'slug' => 'client/channel' // Use 'author' (default WP user slug).
				),
				'capabilities' => array(
					'manage_terms' => 'edit_wpse_clients', // Using 'edit_wpse_clients' cap to keep this simple.
					'edit_terms'   => 'edit_wpse_clients',
					'delete_terms' => 'edit_wpse_clients',
					'assign_terms' => 'read',
				),
				//'update_count_callback' => 'users_tax_count' // Use a custom function to update the count.
			)
		);
	}
	
		// highlight the proper top level menu
	public static function channel_tax_menu_correction($parent_file) {
		global $current_screen;
		$taxonomy = $current_screen->taxonomy;
		if ($taxonomy == 'channel') {
			$parent_file = 'edit.php?post_type=wpsellerevents';
		}
		return $parent_file;
	}
	
	
	public static function channel_add_admin_scripts(){
		global $pagenow;
		if( ($pagenow == 'edit-tags.php') && (isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'channel') && !isset($_GET['action'])) {
			wp_register_script('channel-quick-edit-js',	WPSellerEvents ::$uri .'/js/channel_quick_edit.js',	array('jquery')	);
			wp_enqueue_script('channel-quick-edit-js');
			add_action('admin_head', array( __CLASS__ ,'channel_add_head'));
		}
	}
	public static function channel_add_head(){
		global $wp_version;
		if ( version_compare( $wp_version, '4.3', '>=' ) ) : ?>
		<style type="text/css">.form-field.term-parent-wrap,.form-field.term-slug-wrap, .form-field label[for="parent"], .form-field #parent {display: none;}</style>
		<?php else : ?>
		<script type="text/javascript">jQuery('#parent').parent('.form-field').hide(); jQuery('#tag-slug').parent('.form-field').hide();</script>
		<?php endif; ?>
		<?php
	}

	/**
	 * Sets & Unsets columns on the manage channel admin page.
	 *
	 * @param array $columns An array of columns to be shown in the manage terms table.
	 */
	static function channel_taxonomy_columns( $columns ) {

		unset( $columns['posts'] );
		unset( $columns['slug'] );

		$columns['clients'] = __( 'Clients', 'wpsellerevents' );

		return $columns;
	}

	/**
	 * Displays content for custom columns on the manage channels page in the admin.
	 *
	 * @param string $display WP just passes an empty string here.
	 * @param string $column The name of the custom column.
	 * @param int $term_id The ID of the term being displayed in the table.
	 */
	static function manage_channel_column( $display, $column, $term_id ) {

		if ( 'clients' === $column ) {
			$term = get_term( $term_id, 'channel' );
			$link = admin_url('edit.php?post_type=wpse_client&channel='.$term->slug );
			echo '<a href="'.$link.'">'.$term->count.'</a>';
		}
	}
	

}
