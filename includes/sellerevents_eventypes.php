<?php
/**
 * Description of sellerevents_eventypes
 *
 * @author esteban
 */
class sellerevents_eventypes {
	function __construct() {
		$this->create_tax_eventype(); //for events
		global $pagenow;
		//Saving
		add_action( 'edited_eventype', array( __CLASS__, 'save_eventype_custom_meta'), 10, 2 );  
		add_action( 'create_eventype', array( __CLASS__, 'save_eventype_custom_meta'), 10, 2 );
		//columns 
		add_filter("manage_edit-eventype_columns", array( __CLASS__, 'theme_columns') ); 
		add_filter("manage_eventype_custom_column", array( __CLASS__, 'manage_theme_columns'), 10, 3);
		//New & edit forms
		add_action( 'eventype_add_form_fields', array( __CLASS__, 'eventype_add_new_meta_field'), 10, 2 );
		add_action( 'eventype_edit_form_fields', array( __CLASS__, 'eventype_edit_meta_field'), 10, 2 );
		if( ($pagenow == 'edit-tags.php') && (isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'eventype') ) {
			add_action('parent_file',  array( __CLASS__, 'eventype_tax_menu_correction'));
			//ajax quick edit forms just in eventype
			add_action('quick_edit_custom_box',  array( __CLASS__, 'eventype_quick_edit_custom_box'), 10, 3); 
			//Javascripts 
			add_action('admin_enqueue_scripts', array( __CLASS__, 'eventype_add_admin_scripts'), 10, 1);
		}

	}
	
	function create_tax_eventype(){
		// Add new taxonomy, NOT hierarchical (like tags)
		$labels = array(
			'name'              => __( 'Event types', WPSellerEvents :: TEXTDOMAIN ),
			'singular_name'     => __( 'Event type', WPSellerEvents :: TEXTDOMAIN ),
			'search_items'      => __( 'Search Event Types', WPSellerEvents :: TEXTDOMAIN ),
			'all_items'         => __( 'All Event Types', WPSellerEvents :: TEXTDOMAIN ),
			'parent_item'       => __( 'Parent Event Type' , WPSellerEvents :: TEXTDOMAIN),
			'parent_item_colon' => __( 'Parent Event Type:', WPSellerEvents :: TEXTDOMAIN ),
			'edit_item'         => __( 'Edit Event Type', WPSellerEvents :: TEXTDOMAIN ),
			'update_item'       => __( 'Update Event Type', WPSellerEvents :: TEXTDOMAIN ),
			'add_new_item'      => __( 'Add New Event Type', WPSellerEvents :: TEXTDOMAIN ),
			'new_item_name'     => __( 'New Event Type Name', WPSellerEvents :: TEXTDOMAIN ),
			'menu_name'         => __( 'Event Types', WPSellerEvents :: TEXTDOMAIN ),
			'separate_items_with_commas' => __( 'Separate Event Types with commas', WPSellerEvents :: TEXTDOMAIN ),
			'add_or_remove_items' => __( 'Add or remove Event Types', WPSellerEvents :: TEXTDOMAIN ),
			'choose_from_most_used' => __( 'Choose from the most popular Event Types', WPSellerEvents :: TEXTDOMAIN ),
		);
		$capabilities = array(
			'manage_terms' => 'manage_eventypes',
			'edit_terms' => 'manage_eventypes',
			'delete_terms' => 'manage_eventypes',
			'assign_terms' => 'edit_sellerevents',
		);
		$args = array(
			'public'			=> false,
			'hierarchical'      => true,
			'meta_box_cb'		=> 'post_categories_meta_box',
			'labels'            => $labels,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_in_nav_menus' => null,
			'show_tagcloud'     => null,
			'show_in_quick_edit'=> null,
			'show_admin_column' => false,
			'query_var'         => true,
			'rewrite'           => array(
				'with_front' => true,
				'slug' => 'eventype' 
			),
			'capabilities'      => $capabilities,
			'update_count_callback' => array(__CLASS__, 'update_eventype_count') // Use a custom function to update the count.
		);
		register_taxonomy( 'eventype', array('wpsellerevents'), $args );
		
	}
	
	/*
	 * ********* Taxonomy Event Type   ****************
	 * Columns, custom fields, styles, scripts
	 * 
	 */

	// highlight the proper top level menu
	public static function eventype_tax_menu_correction($parent_file) {
		global $current_screen;
		$taxonomy = $current_screen->taxonomy;
		if ($taxonomy == 'eventype')
			$parent_file = 'edit.php?post_type=wpsellerevents';
		return $parent_file;
	}

	public static function eventype_add_admin_scripts(){
		global $pagenow;
		if( ($pagenow == 'edit-tags.php') && (isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'eventype') && !isset($_GET['action'])) {
			wp_register_script('eventype-quick-edit-js',	WPSellerEvents ::$uri .'/js/eventype_quick_edit.js',	array('jquery')	);
			wp_enqueue_script('eventype-quick-edit-js');
		}
	}


	public static function theme_columns($columns) {
				$new_columns = array(
					'cb' => '<input type="checkbox" />',
					'name' => __('Name', WPSellerEvents :: TEXTDOMAIN),
					'description' => __('Description', WPSellerEvents :: TEXTDOMAIN),
					'quantity' => __('Quantity', WPSellerEvents :: TEXTDOMAIN),
					'period' => __('Period', WPSellerEvents :: TEXTDOMAIN),
					//'slug' => __('Slug'),
					'events' => __('Events', WPSellerEvents :: TEXTDOMAIN),
				);
				return $new_columns;
	}

	public static function manage_theme_columns($out, $column_name, $term_id) {
		//$theme = get_term($id, 'shiba_theme');
		$t_id = $term_id;
		$term_meta = get_option( "eventype_$t_id" );
		switch ($column_name) {
			case 'quantity': 
				$out = esc_attr( $term_meta['quantity'] ) ? esc_attr( $term_meta['quantity'] ) : '';
				break;

			case 'period': 
				$out = esc_attr( $term_meta['period'] ) ? esc_attr( $term_meta['period'] ) : '';
				break;

			case 'events': 
				$term = get_term( $term_id, 'eventype' );
				$out = $term->count;
				break;

			default:
				break;
		}
		return $out;    
	}
	/**
	 * Function for updating the 'eventype' taxonomy count.  What this does is update the count of a specific term 
	 * by the number of events that have been given the term.  We're not doing any checks for eventype specifically here. 
	 * We're just updating the count with no specifics for simplicity.
	 *
	 * See the _update_post_term_count() function in WordPress for more info.
	 *
	 * @param array $terms List of Term taxonomy IDs
	 * @param object $taxonomy Current taxonomy object of terms
	 */
	public static function update_eventype_count( $terms, $taxonomy ) {
		global $wpdb;

		foreach ( (array) $terms as $term ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term ) );

			do_action( 'edit_term_taxonomy', $term, $taxonomy );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
			do_action( 'edited_term_taxonomy', $term, $taxonomy );
		}
	}


	/**
	 * Add term page
	 * Function for add the custom meta field to the add new term page.<br>
	 * For 'eventype' taxonomy.<br>
	 * Deletes the 'Parent()' & 'slug' fields here by CSS to get same<br>
	 * behavior of categories but not hierarchical.
	 * 
	 */
	public static function eventype_add_new_meta_field() {
		global $wp_version;
		if ( version_compare( $wp_version, '4.3', '>=' ) ) : ?>
		<style type="text/css">.form-field.term-parent-wrap,.form-field.term-slug-wrap, .form-field label[for="parent"], .form-field #parent {display: none;}</style>
		<?php else : ?>
		<script type="text/javascript">jQuery('#parent').parent('.form-field').hide(); jQuery('#tag-slug').parent('.form-field').hide();</script>
		<?php endif; ?>
		<div class="form-field" id="qty_div">
			<label for="term_meta[quantity]"><?php _e( 'Quantity', WPSellerEvents :: TEXTDOMAIN ); ?></label>
			<input style="width: 60px;text-align: right; padding-right: 0px; " type="number" min="0" name="term_meta[quantity]" id="term_meta[quantity]" value="">
			<p class="description"><?php _e( 'Enter a value for this field',WPSellerEvents :: TEXTDOMAIN ); ?></p>
		</div>
		<div class="form-field">
			<label for="term_meta[period]"><?php _e( 'Period', WPSellerEvents :: TEXTDOMAIN ); ?></label>
		<?php /* <input type="text" name="term_meta[period]" id="term_meta[period]" value=""> */
		echo '<select id="term_meta[period]" name="term_meta[period]" style="display:inline;">
			<option value="minutes">'. __('minutes', WPSellerEvents :: TEXTDOMAIN).'</option>
			<option value="hours">'. __('hours', WPSellerEvents :: TEXTDOMAIN).'</option>
			<option value="days">'. __('days', WPSellerEvents :: TEXTDOMAIN).'</option>
			<option value="weeks">'. __('weeks', WPSellerEvents :: TEXTDOMAIN).'</option>
		</select>';
		?>
			<p class="description"><?php _e( 'Select a period for this field',WPSellerEvents :: TEXTDOMAIN ); ?></p>
		</div>
	<?php
	}

	// Edit term page
	public static function eventype_edit_meta_field($term) {
		// put the term ID into a variable
		$t_id = $term->term_id;
		// retrieve the existing value(s) for this meta field. This returns an array
		$term_meta = get_option( "eventype_$t_id" ); ?>
		<style type="text/css">.form-field.term-parent-wrap, .form-field.term-slug-wrap {display: none;}</style>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="term_meta[quantity]"><?php _e( 'Quantity', WPSellerEvents :: TEXTDOMAIN ); ?></label>
			</th>
			<td>
				<input style="width: 60px;text-align: right; padding-right: 0px; " type="number" min="0" class="small-text" name="term_meta[quantity]" id="term_meta[quantity]" value="<?php echo esc_attr( $term_meta['quantity'] ) ? esc_attr( $term_meta['quantity'] ) : ''; ?>">
				<p class="description"><?php _e( 'Enter a value for this field',WPSellerEvents :: TEXTDOMAIN ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="term_meta[period]"><?php _e( 'Period', WPSellerEvents :: TEXTDOMAIN ); ?></label>
			</th>
			<td>
				<?php	
			/*  <input type="text" class="small-text" name="term_meta[period]" id="term_meta[period]" value="<?php echo esc_attr( $term_meta['period'] ) ? esc_attr( $term_meta['period'] ) : ''; ?>">  */
				$period = esc_attr( $term_meta['period'] );
				echo '<select id="term_meta[period]" name="term_meta[period]" style="display:inline;">
					<option value="minutes" '.selected( $period, 'minutes', FALSE ). '>'. __('minutes', WPSellerEvents :: TEXTDOMAIN).'</option>
					<option value="hours" '.selected( $period, 'hours', FALSE ). '>'. __('hours', WPSellerEvents :: TEXTDOMAIN).'</option>
					<option value="days" '.selected( $period, 'days', FALSE ). '>'. __('days', WPSellerEvents :: TEXTDOMAIN).'</option>
					<option value="weeks" '.selected( $period, 'weeks', FALSE ). '>'. __('weeks', WPSellerEvents :: TEXTDOMAIN).'</option>
				</select>';
				?>
				<p class="description"><?php _e( 'Select a period for this field',WPSellerEvents :: TEXTDOMAIN ); ?></p>
			</td>
		</tr>
	<?php
	}


	// QuicEdit term page
	/**
	 * 
	 * @param type $column_name the key for the value(s) added in the my_column_header function
	 * @param type $screen the current screen
	 * @param type $name name of the current taxonomy
	 * @return boolean
	 */
	public static function eventype_quick_edit_custom_box($column_name, $screen, $name) {
		if($name != 'eventype') return false;
		switch ($column_name) {
			case 'quantity': 
				?>
				<fieldset>
					<div id="Quantity-content" class="inline-edit-col">
						<label>
							<span class="title"><?php _e( 'Quantity', WPSellerEvents :: TEXTDOMAIN ); ?></span>
							<span class="input-text-wrap"><input type="number" min="0" class="ptitle small-text" name="term_meta[quantity]" id="term_meta[quantity]" value=""></span>
						</label>
					</div>
				</fieldset>
				<script>
				jQuery('#the-list').on('click', 'a.editinline', function(){
				  var now = jQuery(this).closest('tr').find('td.column-quantity').text();
				  jQuery('.inline-edit-col :input[name="term_meta[quantity]"]').val(now);
				});
				</script>
				<?php
				break;

			case 'period': 
				?>
				<fieldset>
					<div id="period-content" class="inline-edit-col">
						<label>
							<span class="title"><?php _e( 'Period', WPSellerEvents :: TEXTDOMAIN ); ?></span>
							<span class="input-text-wrap"><?php
								echo '<select id="term_meta[period]" name="term_meta[period]" style="display:inline;">
									<option value="minutes">'. __('minutes', WPSellerEvents :: TEXTDOMAIN).'</option>
									<option value="hours">'. __('hours', WPSellerEvents :: TEXTDOMAIN).'</option>
									<option value="days">'. __('days', WPSellerEvents :: TEXTDOMAIN).'</option>
									<option value="weeks">'. __('weeks', WPSellerEvents :: TEXTDOMAIN).'</option>
								</select>';
							?></span>
						</label>
					</div>
				</fieldset>
				<script>
				jQuery('#the-list').on('click', 'a.editinline', function(){
				  var now = jQuery(this).closest('tr').find('td.column-period').text();
				  jQuery('.inline-edit-col select[name="term_meta[period]"]').children('option[value="' + now +'"]').attr("selected", "selected");
				});
				</script>

				<?php
				break;

			default:
				return false;
		}

	}


	// Save extra eventype fields callback function.
	public static function save_eventype_custom_meta( $term_id, $tt_id ) {
		if ( isset( $_POST['term_meta'] ) ) {
			$t_id = $term_id;
			$term_meta = get_option( "eventype_$t_id" );
			$cat_keys = array_keys( $_POST['term_meta'] );
			foreach ( $cat_keys as $key ) {
				if ( isset ( $_POST['term_meta'][$key] ) ) {
					$term_meta[$key] = $_POST['term_meta'][$key];
				}
			}
			// Save the option array.
			update_option( "eventype_$t_id", $term_meta );
		}
	}
	
}
