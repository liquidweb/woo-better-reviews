<?php
/**
 * Our table setup for the handling the attributes pieces.
 *
 * @package WooBetterReviews
 */

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
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
class WooBetterReviews_ListAttributes extends WP_List_Table {

	/**
	 * WooBetterReviews_ListReviews constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {

		// Set parent defaults.
		parent::__construct( array(
			'singular' => __( 'Product Attribute', 'woo-better-reviews' ),
			'plural'   => __( 'Product Attributes', 'woo-better-reviews' ),
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
			'cb'             => '<input type="checkbox" />',
			'attribute_name' => __( 'Name', 'woo-better-reviews' ),
			'attribute_slug' => __( 'Slug', 'woo-better-reviews' ),
			'attribute_desc' => __( 'Description', 'woo-better-reviews' ),
			'min_label'      => __( 'Min Label', 'woo-better-reviews' ),
			'max_label'      => __( 'Max Label', 'woo-better-reviews' ),
		);

		// Return filtered.
		return apply_filters( Core\HOOK_PREFIX . 'attributes_table_column_items', $setup );
	}

	/**
	 * Display all the things.
	 *
	 * @return void
	 */
	public function display() {

		// Add a nonce for the bulk action.
		wp_nonce_field( 'wbr_list_attributes_action', 'wbr_list_attributes_nonce' );

		// And the parent display (which is most of it).
		parent::display();
	}

	/**
	 * Add extra markup in the toolbars before or after the list.
	 *
	 * @param  string $which  Which markup area after (bottom) or before (top) the list.
	 *
	 * @return HTML
	 */
	protected function extra_tablenav( $which ) {
		return apply_filters( Core\HOOK_PREFIX . 'attributes_table_extra_tablenav', '', $which );
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
		return apply_filters( Core\HOOK_PREFIX . 'attributes_table_row_actions', '', $item, $column_name, $primary );
	}

	/**
	 * Define the sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		// Build our array of sortable columns.
		$setup  = array(
			'attribute_name' => array( 'attribute_name', true ),
		);

		// Return it, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'attributes_table_sortable_columns', $setup );
	}

	/**
	 * Define which columns are hidden.
	 *
	 * @return array
	 */
	public function get_hidden_columns() {

		// Build our array of hidden columns.
		$setup  = array(
			'attribute_slug',
		);

		// Return a blank array, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'attributes_table_hidden_columns', $setup );
	}

	/**
	 * Return available bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {

		// Make a basic array of the actions we wanna include.
		$setup  = array(
			'wbr_bulk_delete' => __( 'Delete Attributes', 'woo-better-reviews' ),
		);

		// Return it filtered.
		return apply_filters( Core\HOOK_PREFIX . 'attributes_table_bulk_actions', $setup );
	}

	/**
	 * Handle bulk actions.
	 *
	 * @see $this->prepare_items()
	 */
	protected function process_bulk_action() {
		return;
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
		return '<input type="checkbox" name="attribute-id[]" class="woo-better-reviews-admin-checkbox" id="cb-' . $id . '" value="' . $id . '" /><label for="cb-' . $id . '" class="screen-reader-text">' . __( 'Select attribute', 'woo-better-reviews' ) . '</label>';
	}

	/**
	 * The visible name column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_attribute_name( $item ) {

		// Get my edit link.
		$edit_link  = $this->get_single_attribute_action_link( absint( $item['id'] ), 'edit' );

		// Set up my ARIA label.
		$aria_label = sprintf( __( '"%s" (Edit)', 'woo-better-reviews' ), $item['attribute_name'] );

		// Set my empty.
		$build  = '';

		// Put a strong tag on it.
		$build .= '<strong>';

			// Set the link markup.
			$build .= '<a class="row-title" href="' . esc_url( $edit_link ) . '" aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $item['attribute_name'] ) . '</a>';

		// Close the strong tag.
		$build .= '</strong>';

		// Create my formatted date.
		$setup  = apply_filters( Core\HOOK_PREFIX . 'attributes_table_column_attribute_name', $build, $item );

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
	protected function column_attribute_desc( $item ) {

		// Handle the setup based on a description being there.
		$setup  = ! empty( $item['attribute_desc'] ) ? esc_html( $item['attribute_desc'] ) : $this->empty_column_text( __( 'No description', 'woo-better-reviews' ) );

		// Return my formatted product name.
		return apply_filters( Core\HOOK_PREFIX . 'attributes_table_column_attribute_desc', $setup, $item );
	}

	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data() {

		// Get all the attribute data.
		$attribute_objects  = Queries\get_all_attributes();
		//preprint( $attribute_objects, true );

		// Bail with no data.
		if ( ! $attribute_objects ) {
			return array();
		}

		// Set my empty.
		$data   = array();

		// Now loop each customer info.
		foreach ( $attribute_objects as $attribute_object ) {

			// Set the array of the data we want.
			$setup  = array(
				'id'             => absint( $attribute_object->attribute_id ),
				'attribute_name' => esc_attr( $attribute_object->attribute_name ),
				'attribute_slug' => esc_attr( $attribute_object->attribute_slug ),
				'attribute_desc' => esc_textarea( $attribute_object->attribute_desc ),
				'min_label'      => esc_attr( $attribute_object->min_label ),
				'max_label'      => esc_attr( $attribute_object->max_label ),
			);

			// Run it through a filter.
			$data[] = apply_filters( Core\HOOK_PREFIX . 'attributes_table_data_item', $setup, $attribute_object );
		}

		// Return our data.
		return apply_filters( Core\HOOK_PREFIX . 'attributes_table_data_array', $data, $attribute_objects );
	}

	/**
	 * Take the default dataset and filter it.
	 *
	 * @param  array  $dataset  The current dataset we have.
	 *
	 * @return array
	 */
	protected function maybe_filter_dataset( $dataset = array() ) {

		// And return the dataset, however we have it.
		return $dataset;
	}

	/**
	 * Filter out the dataset by ID.
	 *
	 * @param  array   $dataset  The dataset we wanna filter.
	 * @param  integer $id       The specific ID we wanna check for.
	 * @param  string  $type     Which ID type. Either 'product_id', 'customer_id', or 'id'.
	 *
	 * @return array
	 */
	private function filter_dataset_by_id( $dataset = array(), $id = 0, $type = '' ) {

		// Bail without a dataset, ID, or type.
		if ( empty( $dataset ) || empty( $id ) || empty( $type ) ) {
			return;
		}

		// Loop the dataset.
		foreach ( $dataset as $index => $values ) {

			// If we do not have a match, unset it and go about our day.
			if ( absint( $id ) !== absint( $values[ $type ] ) ) {
				unset( $dataset[ $index ] );
			}
		}

		// Return thge dataset, with the array keys reset.
		return ! empty( $dataset ) ? array_values( $dataset ) : array();
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

			case 'attribute_name' :
			case 'attribute_slug' :
			case 'attribute_desc' :
			case 'min_label' :
			case 'max_label' :
				return ! empty( $dataset[ $column_name ] ) ? $dataset[ $column_name ] : '';
				break;

			default :
				return apply_filters( Core\HOOK_PREFIX . 'attributes_table_column_default', '', $dataset, $column_name );
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
		$settings_link  = Helpers\get_admin_menu_link( 'woo-better-reviews-product-attributes' );

		// Set my attribute ID.
		$attribute_id   = absint( $item['id'] );

		// Create the array of action items.
		$action_dataset = $this->get_row_action_dataset( $attribute_id );

		// Set an empty array.
		$setup  = array();

		// Now loop and create the links.
		foreach ( $action_dataset as $action_name => $args ) {

			// Make my action link.
			$action_link    = $this->get_single_attribute_action_link( $attribute_id, $action_name );

			// Set the classes.
			$action_class   = 'woo-better-reviews-action-link woo-better-reviews-attribute-action-link woo-better-reviews-attribute-' . esc_attr( $action_name ) . '-action-link';

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
		return apply_filters( Core\HOOK_PREFIX . 'attributes_table_row_actions', $setup, $item );
	}

	/**
	 * Get the dataset for the action links.
	 *
	 * @param  integer $attribute_id   The individual attribute we are making links for.
	 * @param  string  $single_action  Request one action from the entire array.
	 *
	 * @return array
	 */
	protected function get_row_action_dataset( $attribute_id = 0, $single_action = '' ) {

		// Create the two nonces.
		$edit_nonce     = wp_create_nonce( 'lw_woo_edit_single_' . $attribute_id );
		$delete_nonce   = wp_create_nonce( 'lw_woo_delete_single_' . $attribute_id );

		// Create the array of action items.
		$action_dataset = array(
			'edit' => array(
				'nonce'  => $edit_nonce,
				'label'  => __( 'Edit', 'woo-better-reviews' ),
				'title'  => __( 'Edit Attribute', 'woo-better-reviews' ),
				'data'   => array(
					'item-id'   => $attribute_id,
					'item-type' => 'attribute',
					'nonce'     => $edit_nonce,
				),
			),

			'delete' => array(
				'nonce'  => $delete_nonce,
				'label'  => __( 'Delete', 'woo-better-reviews' ),
				'title'  => __( 'Delete Attribute', 'woo-better-reviews' ),
				'data'   => array(
					'item-id'   => $attribute_id,
					'item-type' => 'attribute',
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
	 * Create the raw URL for a single attribute action.
	 *
	 * @param  integer $attribute_id  The individual attribute we are making links for.
	 * @param  string  $action_name   The name of the action we want.
	 *
	 * @return string
	 */
	protected function get_single_attribute_action_link( $attribute_id = 0, $action_name = '' ) {

		// Bail without the attribute ID or action name.
		if ( empty( $attribute_id ) || empty( $action_name ) ) {
			return;
		}

		// Fetch the dataset for an edit link.
		$action_dataset = $this->get_row_action_dataset( $attribute_id, $action_name );
		// preprint( $action_dataset, true );

		// Bail without the action dataset.
		if ( empty( $action_dataset ) ) {
			return;
		}

		// Get our primary settings link.
		$settings_link  = Helpers\get_admin_menu_link( 'woo-better-reviews-product-attributes' );

		// Set the action link args.
		$action_linkset = array(
			'wbr-action-name' => $action_name,
			'wbr-item-id'     => absint( $attribute_id ),
			'wbr-item-type'   => 'attribute',
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
		_e( 'No attributes exist', 'woo-better-reviews' );
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
		$ordby  = ! empty( $_GET['orderby'] ) ? $_GET['orderby'] : 'attribute_name';
		$order  = ! empty( $_GET['order'] ) ? $_GET['order'] : 'asc';

		// Set my result up.
		$result = strcmp( $a[ $ordby ], $b[ $ordby ] );

		// Return it one way or the other.
		return 'asc' === $order ? $result : -$result;
	}
}
