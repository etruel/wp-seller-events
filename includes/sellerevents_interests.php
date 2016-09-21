<?php
/**
 * Description of sellerevents_interests
 * type of interest of client. ie: house, department, office, others
 * @category taxonomies * 
 * @author esteban
 */
class sellerevents_interests {
	function __construct() {
		global $pagenow;
		$this->create_tax_interests(); //for clients
		//columns 
		add_action("manage_edit-interest_columns", array( __CLASS__, 'interest_taxonomy_columns') ); 
		add_action('manage_interest_custom_column', array(__CLASS__,'manage_interest_column'), 10, 3 );
		if( ($pagenow == 'edit-tags.php') && (isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'interest') ) {
			add_action('parent_file',  array( __CLASS__, 'interest_tax_menu_correction'));
			//Javascripts 
			add_action('admin_enqueue_scripts', array( __CLASS__, 'interest_add_admin_scripts'), 10, 1);
		}

	}
	
	function create_tax_interests(){
		// Add new taxonomy, NOT hierarchical (like tags)
		register_taxonomy(
			'interest',
			array('wpse_client'),
			array(
				'public' => false,  //just use in admin?
				'show_ui' => true,
				'show_in_menu' => false,
				'meta_box_cb'	=> 'post_categories_meta_box',
				'hierarchical' => true,
				'labels' => array(
					'name' => __( 'Interests', WPSellerEvents :: TEXTDOMAIN ),
					'singular_name' => __( 'Interest', WPSellerEvents :: TEXTDOMAIN ),
					'menu_name' => __( 'Interests', WPSellerEvents :: TEXTDOMAIN ),
					'search_items' => __( 'Search Interests', WPSellerEvents :: TEXTDOMAIN ),
					'popular_items' => __( 'Popular Interests', WPSellerEvents :: TEXTDOMAIN ),
					'all_items' => __( 'All Interests', WPSellerEvents :: TEXTDOMAIN ),
					'edit_item' => __( 'Edit Interest', WPSellerEvents :: TEXTDOMAIN ),
					'update_item' => __( 'Update Interest', WPSellerEvents :: TEXTDOMAIN ),
					'add_new_item' => __( 'Add New Interest', WPSellerEvents :: TEXTDOMAIN ),
					'new_item_name' => __( 'New Interest Name', WPSellerEvents :: TEXTDOMAIN ),
					'separate_items_with_commas' => __( 'Separate interests with commas', WPSellerEvents :: TEXTDOMAIN ),
					'add_or_remove_items' => __( 'Add or remove interests', WPSellerEvents :: TEXTDOMAIN ),
					'choose_from_most_used' => __( 'Choose from the most popular interests', WPSellerEvents :: TEXTDOMAIN ),
				),
				'rewrite' => array(
					'with_front' => true,
					'slug' => 'client/interest' // Use 'author' (default WP user slug).
				),
				'capabilities' => array(
					'manage_terms' => 'edit_wpse_clients', // Using 'edit_wpse_clients' cap to keep this simple.
					'edit_terms'   => 'edit_wpse_clients',
					'delete_terms' => 'edit_wpse_clients',
					'assign_terms' => 'read',
				),
				'update_count_callback' => array(__CLASS__, 'update_interest_count') // Use a custom function to update the count.
			)
		);
	}
	
		// highlight the proper top level menu
	public static function interest_tax_menu_correction($parent_file) {
		global $current_screen;
		$taxonomy = $current_screen->taxonomy;
		if ($taxonomy == 'interest') {
			$parent_file = 'edit.php?post_type=wpsellerevents';
		}
		return $parent_file;
	}
	
	public static function interest_add_admin_scripts(){
		global $pagenow;
		if( ($pagenow == 'edit-tags.php') && (isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'interest') && !isset($_GET['action'])) {
			wp_register_script('interest-quick-edit-js',	WPSellerEvents ::$uri .'/js/interest_quick_edit.js',	array('jquery')	);
			wp_enqueue_script('interest-quick-edit-js');
			add_action('admin_head', array( __CLASS__ ,'interest_add_head'));
		}
	}
	public static function interest_add_head(){
		global $wp_version;
		if ( version_compare( $wp_version, '4.3', '>=' ) ) : ?>
		<style type="text/css">.form-field.term-parent-wrap,.form-field.term-slug-wrap, .form-field label[for="parent"], .form-field #parent {display: none;}</style>
		<?php else : ?>
		<script type="text/javascript">jQuery('#parent').parent('.form-field').hide(); jQuery('#tag-slug').parent('.form-field').hide();</script>
		<?php endif; ?>
		<?php
	}
	/**
	 * Sets & Unsets columns on the manage interest admin page.
	 *
	 * @param array $columns An array of columns to be shown in the manage terms table.
	 */
	static function interest_taxonomy_columns( $columns ) {

		unset( $columns['posts'] );
		unset( $columns['slug'] );

		$columns['clients'] = __( 'Clients', WPSellerEvents :: TEXTDOMAIN );

		return $columns;
	}

	/**
	 * Displays content for custom columns on the manage interests page in the admin.
	 *
	 * @param string $display WP just passes an empty string here.
	 * @param string $column The name of the custom column.
	 * @param int $term_id The ID of the term being displayed in the table.
	 */
	static function manage_interest_column( $display, $column, $term_id ) {
		if ( 'clients' === $column ) {
			$term = get_term( $term_id, 'interest' );
			$link = admin_url('edit.php?post_type=wpse_client&interest='.$term->slug );
			echo '<a href="'.$link.'">'.$term->count.'</a>';
		}
	}
	/**
	 * Function for update the count of a specific term 
	 * by the number of clients that have been given the term. 
	 * We're just updating the count with no specifics for simplicity.
	 * @param array $terms List of Term taxonomy IDs
	 * @param object $taxonomy Current taxonomy object of terms
	 */
	public static function update_interest_count( $terms, $taxonomy ) {
		global $wpdb;

		foreach ( (array) $terms as $term ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term ) );

			do_action( 'edit_term_taxonomy', $term, $taxonomy );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
			do_action( 'edited_term_taxonomy', $term, $taxonomy );
		}
	}		
}
