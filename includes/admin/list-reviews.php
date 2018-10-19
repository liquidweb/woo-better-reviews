<?php
/**
 * Our table setup for the handling the data pieces.
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
class WooBetterReviews_ListReviews extends WP_List_Table {

	/**
	 * WooBetterReviews_ListReviews constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {

		// Set parent defaults.
		parent::__construct( array(
			'singular' => __( 'Product Review', 'woo-better-reviews' ),
			'plural'   => __( 'Product Reviews', 'woo-better-reviews' ),
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
			'cb'           => '<input type="checkbox" />',
			'review_title' => __( 'Review Title', 'woo-better-reviews' ),
			'review_date'  => __( 'Review Date', 'woo-better-reviews' ),
		);

		// Return filtered.
		return apply_filters( Core\HOOK_PREFIX . 'review_table_column_items', $setup );
	}

	/**
	 * Display all the things.
	 *
	 * @return void
	 */
	public function display() {

		// Add a nonce for the bulk action.
		wp_nonce_field( 'wbr_list_reviews_action', 'wbr_list_reviews_nonce' );

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
		return apply_filters( Core\HOOK_PREFIX . 'review_table_extra_tablenav', '', $which );
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
		return apply_filters( Core\HOOK_PREFIX . 'review_table_row_actions', '', $item, $column_name, $primary );
	}

	/**
	 * Define the sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		// Build our array of sortable columns.
		$setup  = array(
			'review_title' => array( 'review_title', false ),
			'review_date'  => array( 'review_date', true ),
		);

		// Return it, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'review_table_sortable_columns', $setup );
	}

	/**
	 * Define which columns are hidden.
	 *
	 * @return array
	 */
	public function get_hidden_columns() {

		// Return a blank array, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'review_table_hidden_columns', array() );
	}

	/**
	 * Return available bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {

		// Make a basic array of the actions we wanna include.
		$setup  = array(
			'woo_better_reviews_action' => __( 'Some Action', 'woo-better-reviews' ),
		);

		// Return it filtered.
		return apply_filters( Core\HOOK_PREFIX . 'review_table_bulk_actions', $setup );
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
		return '<input type="checkbox" name="review-id[]" class="woo-better-reviews-admin-checkbox" id="cb-' . $id . '" value="' . $id . '" /><label for="cb-' . $id . '" class="screen-reader-text">' . __( 'Select review', 'woo-better-reviews' ) . '</label>';
	}

	/**
	 * The visible name column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_review_title( $item ) {

		// Build my markup.
		$setup  = '';

		// Set the display name.
		$setup .= '<span class="woo-better-reviews-admin-table-display woo-better-reviews-admin-table-review-title">';
			$setup .= esc_html( $item['review_title'] );
		$setup .= '</span>';

		// Create my formatted date.
		$setup  = apply_filters( Core\HOOK_PREFIX . 'review_table_column_review_title', $setup, $item );

		// Return, along with our row actions.
		return $setup . $this->row_actions( $this->setup_row_action_items( $item ) );
	}

	/**
	 * The review date column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_review_date( $item ) {

		// Build my markup.
		$setup  = '';

		// Set the product name.
		$setup .= '<span class="woo-better-reviews-admin-table-display woo-better-reviews-admin-table-review-date">';
			$setup .= esc_html( $item['review_date'] );
		$setup .= '</span>';

		// Return my formatted product name.
		return apply_filters( Core\HOOK_PREFIX . 'review_table_column_review_date', $setup, $item );
	}

	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data() {

		// Get all the review data.
		$review_objects = Queries\get_consolidated_reviews();

		// Bail with no data.
		if ( ! $review_objects ) {
			return array();
		}

		// Set my empty.
		$data   = array();

		// Now loop each customer info.
		foreach ( $review_objects as $review_object ) {

			// Set the array of the data we want.
			$setup  = array(
				'id'           => absint( $review_object->review_id ),
				'review_title' => esc_attr( $review_object->review_title ),
				'review_date'  => esc_attr( $review_object->review_date ),
			);

			// Run it through a filter.
			$data[] = apply_filters( Core\HOOK_PREFIX . 'review_table_data_item', $setup, $review_object );
		}

		// Return our data.
		return apply_filters( Core\HOOK_PREFIX . 'review_table_data_array', $data, $review_objects );
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

			case 'review_title' :
			case 'review_date' :
				return ! empty( $dataset[ $column_name ] ) ? $dataset[ $column_name ] : '';

			default :
				return apply_filters( Core\HOOK_PREFIX . 'review_table_column_default', '', $dataset, $column_name );
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
		return apply_filters( Core\HOOK_PREFIX . 'review_table_row_actions', array(), $item );
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @return Mixed
	 */
	private function sort_data( $a, $b ) {

		// Set defaults and check for query strings.
		$ordby  = ! empty( $_GET['orderby'] ) ? $_GET['orderby'] : 'review_date';
		$order  = ! empty( $_GET['order'] ) ? $_GET['order'] : 'desc';

		// Set my result up.
		$result = strcmp( $a[ $ordby ], $b[ $ordby ] );

		// Return it one way or the other.
		return 'desc' === $order ? -$result : $result;
	}
}
