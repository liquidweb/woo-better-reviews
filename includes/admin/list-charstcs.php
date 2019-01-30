<?php
/**
 * Our table setup for the handling the characteristics pieces.
 *
 * @package WooBetterReviews
 */

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Database as Database;
use LiquidWeb\WooBetterReviews\Queries as Queries;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Create a new table class that will extend the WP_List_Table.
 */
class WooBetterReviews_ListCharstcs extends WP_List_Table {

	/**
	 * WooBetterReviews_ListReviews constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {

		// Set parent defaults.
		parent::__construct( array(
			'singular' => __( 'Product Characteristic', 'woo-better-reviews' ),
			'plural'   => __( 'Product Characteristics', 'woo-better-reviews' ),
			'ajax'     => false,
		) );
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @return void
	 */
	public function prepare_items() {

		// Roll out each part.
		$columns    = $this->get_columns();
		$hidden     = $this->get_hidden_columns();
		$sortable   = $this->get_sortable_columns();
		$dataset    = $this->table_data();

		// Check for the action key value to filter.
		if ( ! empty( $_POST['wbr-action-filter'] ) ) { // WPCS: CSRF ok.
			$dataset    = $this->maybe_filter_dataset( $dataset );
		}

		// Handle our sorting.
		usort( $dataset, array( $this, 'sort_data' ) );

		// Load up the pagination settings.
		$paginate   = 20;
		$item_count = count( $dataset );
		$current    = $this->get_pagenum();

		// Set my pagination args.
		$this->set_pagination_args( array(
			'total_items' => $item_count,
			'per_page'    => $paginate,
			'total_pages' => ceil( $item_count / $paginate ),
		));

		// Slice up our dataset.
		$dataset    = array_slice( $dataset, ( ( $current - 1 ) * $paginate ), $paginate );

		// Do the column headers.
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Make sure we have the single action running.
		$this->process_single_action();

		// Make sure we have the bulk action running.
		$this->process_bulk_action();

		// And the result.
		$this->items = $dataset;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @return array
	 */
	public function get_columns() {

		// Build our array of column setups.
		$setup  = array(
			'cb'              => '<input type="checkbox" />',
			'charstcs_name'   => __( 'Name', 'woo-better-reviews' ),
			'charstcs_slug'   => __( 'Slug', 'woo-better-reviews' ),
			'charstcs_desc'   => __( 'Description', 'woo-better-reviews' ),
			'charstcs_values' => __( 'Values', 'woo-better-reviews' ),
			'charstcs_type'   => __( 'Type', 'woo-better-reviews' ),
		);

		// Return filtered.
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_column_items', $setup );
	}

	/**
	 * Display all the things.
	 *
	 * @return void
	 */
	public function display() {

		// Handle our search output.
		$this->search_box( __( 'Search Attributes', 'woo-better-reviews' ), 'attributes' );

		// And handle the display.
		echo '<form class="woo-better-reviews-admin-form" id="woo-better-reviews-admin-attributes-form" method="post">';

			// Add a nonce for the bulk action.
			wp_nonce_field( 'wbr_bulk_charstcs_action', 'wbr_bulk_charstcs_nonce' );

			// And the parent display (which is most of it).
			parent::display();

		// And close the form.
		echo '</form>';
	}

	/**
	 * Displays the search box.
	 *
	 * @param string $text      The 'submit' button label.
	 * @param string $input_id  ID attribute value for the search input field.
	 */
	public function search_box( $text, $input_id ) {

		// Do the quick check to make sure it's ok to be here.
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		// Fetch the action link.
		$search_action  = Helpers\get_admin_menu_link( Core\CHARSTCS_ANCHOR );

		// Set our actual input IDs.
		$input_field_id = $input_id . '-search-input';
		$submt_field_id = $input_id . 'search-submit';

		// Check for the search query.
		$search_query   = Helpers\maybe_search_term( 'string' );

		// Wrap our search in a form itself.
		echo '<form class="search-form wp-clearfix" action="' . esc_url( $search_action ) . '" method="post">';

			// Handle some hidden fields.
			echo '<input type="hidden" name="wbr-action-filter" value="1">';
			echo '<input type="hidden" name="wbr-action-name" value="search">';

			// Wrap our search in a paragraph tag.
			echo '<p class="search-box attributes-search-box">';

				// Handle the label for screen readers.
				echo '<label class="screen-reader-text" for="' . esc_attr( $input_field_id ) . '">' . esc_attr( $text ) . ':</label>';

				// Output the search field.
				echo '<input type="search" id="' . esc_attr( $input_field_id ) . '" name="s" value="' . esc_attr( $search_query ) . '" />';

				// And the button.
				echo get_submit_button( esc_attr( $text ), 'secondary', '', false, array( 'id' => esc_attr( $submt_field_id ) ) );

			// Close up the paragraph tag.
			echo '</p>';

		// Close up my form.
		echo '</form>';
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {

		// Wrap it in a div.
		echo '<div class="tablenav ' . esc_attr( $which ) . '">';

			// Loop and handle the items if they exist.
			if ( $this->has_items() ) {

				// Wrap the left aligned tablenav.
				echo '<div class="alignleft actions bulkactions">';
				echo $this->bulk_actions( $which );
				echo '</div>';
			}

			// Handle the extras.
			$this->extra_tablenav( $which );

			// Output the pagination.
			$this->pagination( $which );

			// Throw a clear in there.
			echo '<br class="clear" />';

		// And close the entire div.
		echo '</div>';
	}

	/**
	 * Add extra markup in the toolbars before or after the list.
	 *
	 * @param  string $which  Which markup area after (bottom) or before (top) the list.
	 *
	 * @return HTML
	 */
	protected function extra_tablenav( $which ) {
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_extra_tablenav', '', $which );
	}

	/**
	 * Return null for our table, since no row actions exist.
	 *
	 * @param  object $item         The item being acted upon.
	 * @param  string $column_name  Current column name.
	 * @param  string $primary      Primary column name.
	 *
	 * @return null
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_row_actions', '', $item, $column_name, $primary );
	}

	/**
	 * Define the sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		// Build our array of sortable columns.
		$setup  = array(
			'charstcs_name' => array( 'charstcs_name', true ),
		);

		// Return it, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_sortable_columns', $setup );
	}

	/**
	 * Define which columns are hidden.
	 *
	 * @return array
	 */
	public function get_hidden_columns() {

		// Build our array of hidden columns.
		$setup  = array(
			'charstcs_slug',
		);

		// Return a blank array, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_hidden_columns', $setup );
	}

	/**
	 * Return available bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {

		// Make a basic array of the actions we wanna include.
		$setup  = array(
			'wbr_bulk_delete' => __( 'Delete Characteristics', 'woo-better-reviews' ),
		);

		// Return it filtered.
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_bulk_actions', $setup );
	}

	/**
	 * Handle bulk actions.
	 *
	 * @see $this->prepare_items()
	 */
	protected function process_bulk_action() {

		// Make sure we have the page we want.
		if ( empty( $_GET['page'] ) || Core\CHARSTCS_ANCHOR !== sanitize_text_field( $_GET['page'] ) ) {
			return;
		}

		// Bail if we aren't on the doing our requested action.
		if ( empty( $this->current_action() ) || ! in_array( $this->current_action(), array_keys( $this->get_bulk_actions() ) ) ) {
			return;
		}

		// Handle the nonce check.
		if ( empty( $_POST['wbr_bulk_charstcs_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_bulk_charstcs_nonce'], 'wbr_bulk_charstcs_action' ) ) {
			wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
		}

		// Check for the array of charstcs IDs being passed.
		if ( empty( $_POST['charstcs-ids'] ) ) {

			// Set my error return args.
			$redirect_args  = array(
				'success'           => false,
				'wbr-action-result' => 'failed',
				'wbr-error-code'    => 'missing-charstcs-ids',
			);

			// And redirect.
			Helpers\admin_page_redirect( $redirect_args, Core\CHARSTCS_ANCHOR );
		}

		// Set my charstcs IDs.
		$charstcs_ids   = array_map( 'absint', $_POST['charstcs-ids'] );

		// Now loop my IDs and attempt to delete each one.
		foreach ( $charstcs_ids as $charstcs_id ) {

			// Attempt to delete the attribute.
			$maybe_deleted  = Database\delete( 'charstcs', $charstcs_id );

			// Check for the boolean true result.
			if ( false !== $maybe_deleted ) {
				continue;
			}

			// First check for empty or just false.
			if ( empty( $maybe_deleted ) || false === $maybe_deleted || is_wp_error( $maybe_deleted ) ) {

				// Determine the error code.
				$get_error_code = is_wp_error( $maybe_deleted ) ? $maybe_deleted->get_error_code() : 'charstcs-delete-failed';

				// Set my error return args.
				$redirect_args  = array(
					'success'           => false,
					'wbr-action-result' => 'failed',
					'wbr-error-code'    => esc_attr( $get_error_code ),
				);

				// And redirect.
				Helpers\admin_page_redirect( $redirect_args, Core\CHARSTCS_ANCHOR );
			}

			// Nothing left in the loop to do.
		}

		// Set my success args.
		$redirect_args  = array(
			'success'           => 1,
			'wbr-action-result' => 'charstcs-deleted-bulk',
		);

		// And redirect.
		Helpers\admin_page_redirect( $redirect_args, Core\CHARSTCS_ANCHOR );
	}

	/**
	 * Checkbox column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_cb( $item ) {

		// Set my ID.
		$id = absint( $item['id'] );

		// Return my checkbox.
		return '<input type="checkbox" name="charstcs-ids[]" class="woo-better-reviews-admin-checkbox" id="cb-' . $id . '" value="' . $id . '" /><label for="cb-' . $id . '" class="screen-reader-text">' . __( 'Select characteristic', 'woo-better-reviews' ) . '</label>';
	}

	/**
	 * The visible name column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_charstcs_name( $item ) {

		// Get my edit link.
		$edit_link  = $this->get_single_charstcs_action_link( absint( $item['id'] ), 'edit' );

		// Set up my ARIA label.
		$aria_label = sprintf( __( '"%s" (Edit)', 'woo-better-reviews' ), $item['charstcs_name'] );

		// Set my empty.
		$build  = '';

		// Put a strong tag on it.
		$build .= '<strong>';

			// Set the link markup.
			$build .= '<a class="row-title" href="' . esc_url( $edit_link ) . '" aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $item['charstcs_name'] ) . '</a>';

		// Close the strong tag.
		$build .= '</strong>';

		// Create my formatted date.
		$setup  = apply_filters( Core\HOOK_PREFIX . 'charstcs_table_column_charstcs_name', $build, $item );

		// Return, along with our row actions.
		return $setup . $this->row_actions( $this->setup_row_action_items( $item ) );
	}

	/**
	 * The visible description column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_charstcs_desc( $item ) {

		// Handle the setup based on a description being there.
		$setup  = ! empty( $item['charstcs_desc'] ) ? esc_html( $item['charstcs_desc'] ) : $this->empty_column_text( __( 'No description', 'woo-better-reviews' ) );

		// Return my formatted product name.
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_column_charstcs_desc', $setup, $item );
	}

	/**
	 * The visible values column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_charstcs_values( $item ) {

		// Handle the setup based on values being there.
		$setup  = ! empty( $item['charstcs_values'] ) ? Utilities\format_array_values_display( $item['charstcs_values'] ) : $this->empty_column_text( __( 'No values', 'woo-better-reviews' ) );

		// Return my formatted product name.
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_column_charstcs_values', $setup, $item );
	}

	/**
	 * The visible type column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_charstcs_type( $item ) {

		// Handle the setup based on a type being there.
		$setup  = ! empty( $item['charstcs_type'] ) ? esc_html( $item['charstcs_type'] ) : $this->empty_column_text( __( 'No type', 'woo-better-reviews' ) );

		// Return my formatted product name.
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_column_charstcs_type', $setup, $item );
	}

	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data() {

		// Get all the attribute data.
		$charstcs_objects   = Queries\get_all_charstcs();
		//preprint( $charstcs_objects, true );

		// Bail with no data.
		if ( ! $charstcs_objects ) {
			return array();
		}

		// Set my empty.
		$data   = array();

		// Now loop each customer info.
		foreach ( $charstcs_objects as $charstcs_object ) {

			// Set the array of the data we want.
			$setup  = array(
				'id'              => absint( $charstcs_object->charstcs_id ),
				'charstcs_name'   => esc_attr( $charstcs_object->charstcs_name ),
				'charstcs_slug'   => esc_attr( $charstcs_object->charstcs_slug ),
				'charstcs_desc'   => esc_textarea( $charstcs_object->charstcs_desc ),
				'charstcs_values' => $charstcs_object->charstcs_values, // this is not sanitized on purpose because it's an array
				'charstcs_type'   => esc_attr( $charstcs_object->charstcs_type ),
			);

			// Run it through a filter.
			$data[] = apply_filters( Core\HOOK_PREFIX . 'charstcs_table_data_item', $setup, $charstcs_object );
		}

		// Return our data.
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_data_array', $data, $charstcs_objects );
	}

	/**
	 * Take the default dataset and filter it.
	 *
	 * @param  array  $dataset  The current dataset we have.
	 *
	 * @return array
	 */
	protected function maybe_filter_dataset( $dataset = array() ) {

		// Check for the search query.
		if ( ! empty( $_POST['wbr-action-name'] ) && 'search' === sanitize_text_field( $_POST['wbr-action-name'] ) ) {

			// Fetch the search term.
			$search = Helpers\maybe_search_term( 'string' );

			// And return the dataset.
			return $this->filter_search_dataset( $dataset, $search );
		}

		// And return the dataset, however we have it.
		return $dataset;
	}

	/**
	 * Get our search fields.
	 *
	 * @return array.
	 */
	protected function get_search_fields() {

		// Set our array of search fields.
		$fields = array(
			'charstcs_name',
			'charstcs_desc',
		);

		// Return the fields, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_search_fields', $fields );
	}

	/**
	 * Filter out the dataset by search terms.
	 *
	 * @param  array  $dataset  The dataset we wanna filter.
	 * @param  string $search   What we're searching for.
	 *
	 * @return array
	 */
	private function filter_search_dataset( $dataset = array(), $search = '' ) {

		// Bail without a dataset or search term.
		if ( empty( $dataset ) || empty( $search ) ) {
			return $dataset;
		}

		// Set our array of search fields.
		$fields = $this->get_search_fields();

		// Bail without fields to search.
		if ( empty( $fields ) ) {
			return $dataset;
		}

		// Set our empty search results.
		$result = array();

		// First set a loop of the dataset to pull each set of values.
		foreach ( $dataset as $index => $values ) {

			// Don't bother with anything empty.
			if ( empty( $values ) ) {
				continue;
			}

			// Loop the fields and check the dataset for each.
			foreach ( $fields as $field ) {

				// Don't bother searching an empty field.
				if ( empty( $values[ $field ] ) ) {
					continue;
				}

				// Check to see if we have a value.
				$maybe  = stripos( $values[ $field ], $search );

				// If we have it, add it to the new results.
				if ( $maybe !== false ) {

					// Add the dataset.
					$result[] = $values;

					// And break the loop.
					break;
				}
			}

			// Finish the loop of the dataset.
		}

		// Return the new resulting dataset.
		return ! empty( $result ) ? $result : array();
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  array  $dataset      Our entire dataset.
	 * @param  string $column_name  Current column name
	 *
	 * @return mixed
	 */
	public function column_default( $dataset, $column_name ) {

		// Run our column switch.
		switch ( $column_name ) {

			case 'charstcs_name' :
			case 'charstcs_slug' :
			case 'charstcs_desc' :
			case 'charstcs_values' :
			case 'charstcs_type' :
				return ! empty( $dataset[ $column_name ] ) ? $dataset[ $column_name ] : '';
				break;

			default :
				return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_column_default', '', $dataset, $column_name );
		}
	}

	/**
	 * Handle the single row action.
	 *
	 * @return void
	 */
	protected function process_single_action() {
		// There will likely be something here.
	}

	/**
	 * Create the row actions we want.
	 *
	 * @param  array $item  The item from the dataset.
	 *
	 * @return array
	 */
	private function setup_row_action_items( $item ) {

		// Grab our settings page admin link.
		$settings_link  = Helpers\get_admin_menu_link( Core\CHARSTCS_ANCHOR );

		// Set my charstcs ID.
		$charstcs_id    = absint( $item['id'] );

		// Create the array of action items.
		$action_dataset = $this->get_row_action_dataset( $charstcs_id );

		// Set an empty array.
		$setup  = array();

		// Now loop and create the links.
		foreach ( $action_dataset as $action_name => $args ) {

			// Make my action link.
			$action_link    = $this->get_single_charstcs_action_link( $charstcs_id, $action_name );

			// Set the classes.
			$action_class   = 'woo-better-reviews-action-link woo-better-reviews-charstcs-action-link woo-better-reviews-charstcs-' . esc_attr( $action_name ) . '-action-link';

			// Set an empty.
			$build  = '';

			// Now set up the markup.
			$build .= '<a class="' . esc_attr( $action_class ) . '"';

			// Check for a title.
			if ( ! empty( $args['title'] ) ) {
				$build .= ' title="' . esc_attr( $args['title'] ) . '"';
			}

			// Check for data attributes.
			if ( ! empty( $args['data'] ) ) {

				// Loop and add.
				foreach ( $args['data'] as $data_key => $data_val ) {

					// Add each one to the build string.
					$build .= ' data-' . esc_attr( $data_key ) . '="' . esc_attr( $data_val ) . '"';
				}
			}

			// Now add the actual link and text to finish it.
			$build .= ' href="' . esc_url( $action_link ) . '">' . esc_html( $args['label'] ) . '</a>';

			// Add it to the array.
			$setup[ $action_name ] = $build;
		}

		// Return our row actions.
		return apply_filters( Core\HOOK_PREFIX . 'charstcs_table_row_actions', $setup, $item );
	}

	/**
	 * Get the dataset for the action links.
	 *
	 * @param  integer $charstcs_id    The individual charstcs we are making links for.
	 * @param  string  $single_action  Request one action from the entire array.
	 *
	 * @return array
	 */
	protected function get_row_action_dataset( $charstcs_id = 0, $single_action = '' ) {

		// Create the two nonces.
		$edit_nonce     = wp_create_nonce( 'lw_woo_edit_single_' . $charstcs_id );
		$delete_nonce   = wp_create_nonce( 'lw_woo_delete_single_' . $charstcs_id );

		// Create the array of action items.
		$action_dataset = array(
			'edit' => array(
				'nonce'  => $edit_nonce,
				'label'  => __( 'Edit', 'woo-better-reviews' ),
				'title'  => __( 'Edit Characteristic', 'woo-better-reviews' ),
				'data'   => array(
					'item-id'   => $charstcs_id,
					'item-type' => 'charstcs',
					'nonce'     => $edit_nonce,
				),
			),

			'delete' => array(
				'nonce'  => $delete_nonce,
				'label'  => __( 'Delete', 'woo-better-reviews' ),
				'title'  => __( 'Delete Characteristic', 'woo-better-reviews' ),
				'data'   => array(
					'item-id'   => $charstcs_id,
					'item-type' => 'charstcs',
					'nonce'     => $delete_nonce,
				),
			),
		);

		// Return the array of data if no single was requested.
		if ( empty( $single_action ) ) {
			return $action_dataset;
		}

		// Now return the single or false.
		return isset( $action_dataset[ $single_action ] ) ? $action_dataset[ $single_action ] : $single_action;
	}

	/**
	 * Create the raw URL for a single charstcs action.
	 *
	 * @param  integer $charstcs_id  The individual charstcs we are making links for.
	 * @param  string  $action_name  The name of the action we want.
	 *
	 * @return string
	 */
	protected function get_single_charstcs_action_link( $charstcs_id = 0, $action_name = '' ) {

		// Bail without the attribute ID or action name.
		if ( empty( $charstcs_id ) || empty( $action_name ) ) {
			return;
		}

		// Fetch the dataset for an edit link.
		$action_dataset = $this->get_row_action_dataset( $charstcs_id, $action_name );
		// preprint( $action_dataset, true );

		// Bail without the action dataset.
		if ( empty( $action_dataset ) ) {
			return;
		}

		// Get our primary settings link.
		$settings_link  = Helpers\get_admin_menu_link( Core\CHARSTCS_ANCHOR );

		// Set the action link args.
		$action_linkset = array(
			'wbr-action-name' => $action_name,
			'wbr-item-id'     => absint( $charstcs_id ),
			'wbr-item-type'   => 'charstcs',
			'wbr-nonce'       => $action_dataset['nonce'],
		);

		// Create and return the string of the URL.
		return add_query_arg( $action_linkset, $settings_link );
	}

	/**
	 * Handle the display text for when no items exist.
	 *
	 * @return string
	 */
	public function no_items() {
		_e( 'No characteristics found.', 'woo-better-reviews' );
	}

	/**
	 * Return the markup for when a column has no value.
	 *
	 * @param  string $text  The text to show.
	 *
	 * @return HTML
	 */
	protected function empty_column_text( $text = '' ) {
		return '<span aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html( $text ) . '</span>';
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @return Mixed
	 */
	private function sort_data( $a, $b ) {

		// Set defaults and check for query strings.
		$ordby  = ! empty( $_GET['orderby'] ) ? $_GET['orderby'] : 'charstcs_name';
		$order  = ! empty( $_GET['order'] ) ? $_GET['order'] : 'asc';

		// Set my result up.
		$result = strcmp( $a[ $ordby ], $b[ $ordby ] );

		// Return it one way or the other.
		return 'asc' === $order ? $result : -$result;
	}
}
