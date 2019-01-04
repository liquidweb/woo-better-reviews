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

		// Check for the action key value to filter.
		if ( ! empty( $_REQUEST['wbr-review-filter'] ) ) { // WPCS: CSRF ok.
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
			'cb'             => '<input type="checkbox" />',
			'review_title'   => __( 'Title', 'woo-better-reviews' ),
			'review_product' => __( 'Product', 'woo-better-reviews' ),
			'review_date'    => __( 'Date', 'woo-better-reviews' ),
			'review_status'  => __( 'Status', 'woo-better-reviews' ),
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

		// Include our views row.
		$this->views();

		// Handle our search output.
		$this->search_box( __( 'Search Reviews', 'woo-better-reviews' ), 'reviews' );

		// Wrap the display in a form.
		echo '<form class="woo-better-reviews-admin-form" id="woo-better-reviews-admin-reviews-form" method="post">';

			// Add a nonce for the bulk action.
			wp_nonce_field( 'wbr_list_reviews_action', 'wbr_list_reviews_nonce' );

			// And the parent display (which is most of it).
			parent::display();

		// Close up the form.
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
		$search_action  = Helpers\get_admin_menu_link( Core\REVIEWS_ANCHOR );

		// Set our actual input IDs.
		$input_field_id = $input_id . '-search-input';
		$submt_field_id = $input_id . 'search-submit';

		// Check for the search query.
		$search_query   = Helpers\maybe_search_term( 'string' );

		// Wrap our search in a form itself.
		echo '<form class="search-form" action="' . esc_url( $search_action ) . '" method="post">';

			// Handle some hidden fields.
			echo '<input type="hidden" name="wbr-action-filter" value="1">';
			echo '<input type="hidden" name="wbr-action-name" value="search">';

			// Wrap our search in a paragraph tag.
			echo '<p class="search-box reviews-search-box">';

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
	 * Handle displaying the unordered list of views.
	 *
	 * @return HTML
	 */
	public function views() {

		// Get our views to display.
		$views  = $this->get_status_filter_views();

		// Bail without any views to render.
		if ( empty( $views ) ) {
			return;
		}

		// Wrap it in an unordered list.
		echo '<ul class="subsubsub">' . "\n";

		// Loop the views we creatred and output them.
		foreach ( $views as $class => $view ) {
			$views[ $class ] = "\t" . '<li class="' . esc_attr( $class ) . '">' . wp_kses_post( $view );
		}

		// Blow out and implode my list.
		echo implode( ' |</li>' . "\n", $views ) . '</li>' . "\n";

		// Close the list.
		echo '</ul>';
	}

	/**
	 *
	 * Get the data for outputing the views list of links.
	 *
	 * @return array
	 */
	protected function get_status_filter_views() {

		// Get our status counts data.
		$status_dataset = $this->get_status_counts_data();

		// Bail without a dataset.
		if ( empty( $status_dataset ) ) {
			return;
		}

		// Get our basic link for reviews.
		$reviews_link   = Helpers\get_admin_menu_link( Core\REVIEWS_ANCHOR );

		// Set an empty array to begin.
		$status_links   = array();

		// Now loop the status dataset we have and create links.
		foreach ( $status_dataset as $status_key => $status_data ) {

			// Set up the markup we want in the link.
			$link_text  = sprintf(
				_nx(
					$status_data['label'] . ' <span class="count">(%s)</span>',
					$status_data['label'] . ' <span class="count">(%s)</span>',
					absint( $status_data['count'] ),
					'reviews'
				),
				number_format_i18n( absint( $status_data['count'] ) )
			);

			// Determine the link class.
			$link_class = empty( $_GET['wbr-review-status'] ) && 'all' === $status_key ? 'current' : '';
			$link_class = ! empty( $_GET['wbr-review-status'] ) && sanitize_text_field( $_GET['wbr-review-status'] ) === $status_key ? 'current' : $link_class;

			// Now set up the link args.
			$link_args  = array(
				'wbr-review-filter' => 1,
				'wbr-review-status' => esc_attr( $status_key )
			);

			// And finally make the actual link.
			$link_href  = add_query_arg( $link_args, $reviews_link );

			// Now create the array bit.
			$status_links[ $status_key ] = '<a class="' . esc_attr( $link_class ) . '" href="' . esc_url( $link_href ) . '"> ' . $link_text . '</a>';
		}

		// Return it, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'status_links_markup', $status_links );
	}

	/**
	 * Get our total dataset and return the counts of each status.
	 *
	 * @return array
	 */
	private function get_status_counts_data() {

		// Get my complete dataset.
		$dataset    = $this->table_data();

		// Return false without any data.
		if ( empty( $dataset ) ) {
			return false;
		}

		// Trim my list to just the status.
		$status_ids = wp_list_pluck( $dataset, 'review_status' );

		// Set up the return structure.
		$setup['all'] = array(
			'label' => __( 'All', 'woo-better-reviews' ),
			'count' => count( $status_ids )
		);

		// Get my statuses.
		$statuses   = Helpers\get_review_statuses();

		// If we have no statuses, just return the 'all'.
		if ( empty( $statuses ) ) {
			return $setup;
		}

		// Now loop the statuses we have and handle the counts.
		foreach ( $statuses as $status_key => $status_label ) {

			// Handle the individual count by using the array keys and status.
			$setup[ $status_key ] = array(
				'label' => $status_label,
				'count' => count( array_keys( $status_ids, $status_key ) ),
			);
		}

		// Return it, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'status_counts_data', $setup );
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
			'review_title'   => array( 'review_title', false ),
			'review_product' => array( 'review_product', false ),
			'review_date'    => array( 'review_date', true ),
			'review_status'  => array( 'review_status', true ),
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
	 * The visible name column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_review_product( $item ) {

		// Build my markup.
		$setup  = '';

		// Assuming we have a product ID, do the stuff.
		if ( ! empty( $item['product_data'] ) ) {

			// Set the product name.
			$setup .= '<span class="woo-better-reviews-admin-table-display woo-better-reviews-admin-table-review-product">';

				// First output the title.
				$setup .= '<span class="woo-better-reviews-admin-table-product-name">' . esc_html( $item['product_data']['title'] ) . '</span>';

				// Now the wrapper for two links.
				$setup .= '<span class="woo-better-reviews-admin-table-product-links">';

					// The two links themselves.
					$setup .= '<a class="woo-better-reviews-admin-table-link woo-better-reviews-admin-table-product-view-link" href="' . esc_url( $item['product_data']['permalink'] ) . '">' . esc_html__( 'View', 'woo-better-reviews' ) . '</a>';
					$setup .= '|';
					$setup .= '<a class="woo-better-reviews-admin-table-link woo-better-reviews-admin-table-product-edit-link" href="' . esc_url( $item['product_data']['edit-link'] ) . '">' . esc_html__( 'Edit', 'woo-better-reviews' ) . '</a>';

				// Close the links span.
				$setup .= '</span>';

			// Close the span.
			$setup .= '</span>';
		}

		// Return my formatted product name.
		return apply_filters( Core\HOOK_PREFIX . 'review_table_column_review_product', $setup, $item );
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
			$setup .= '<abbr title="' . date( 'Y/m/d g:i:s a', $item['review_stamp'] ) . '">' . date( 'Y/m/d', $item['review_stamp'] ) . '</abbr>';
		$setup .= '</span>';

		// Return my formatted product name.
		return apply_filters( Core\HOOK_PREFIX . 'review_table_column_review_date', $setup, $item );
	}

	/**
	 * The review status column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_review_status( $item ) {

		// Create my label.
		$label  = ucwords( $item['review_status'] ); // esc_html( $label )

		// Build my markup.
		$setup  = '';

		// Set the product name.
		$setup .= '<span class="woo-better-reviews-admin-table-display woo-better-reviews-admin-table-review-status">';
			$setup .= '<mark class="review-status status-' . esc_attr( $item['review_status'] ) . '"><span>' . esc_html( $label ) . '</span></mark>';
		$setup .= '</span>';

		// Return my formatted product name.
		return apply_filters( Core\HOOK_PREFIX . 'review_table_column_review_status', $setup, $item );
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
		foreach ( $review_objects as $index => $review_object ) {

			// Set up some custom args to include.
			$custom = array(
				'id'             => absint( $review_object->review_id ),
				'review_stamp'   => strtotime( $review_object->review_date ),
				'review_product' => get_post_field( 'post_name', absint( $review_object->product_id ), 'raw' ),
				'product_data'   => Helpers\get_admin_product_data( absint( $review_object->product_id ) ),
			);

			// Set the base array of the data we want.
			$setup  = wp_parse_args( (array) $review_object, $custom );

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

		// One check for the string.
		if ( ! isset( $_REQUEST['wbr-review-filter'] ) ) {
			return $dataset;
		}

		// Check for the filter query.
		if ( ! empty( $_GET['wbr-review-status'] ) ) {

			// Get my status.
			$status = sanitize_text_field( $_GET['wbr-review-status'] );

			// And return the dataset.
			return $this->filter_dataset_by_status( $dataset, $status );
		}

		// And return the dataset, however we have it.
		return $dataset;
	}

	/**
	 * Filter out the dataset by status.
	 *
	 * @param  array  $dataset  The dataset we wanna filter.
	 * @param  string $status   Which ID type. Either 'product_id', 'customer_id', or 'id'.
	 *
	 * @return array
	 */
	private function filter_dataset_by_status( $dataset = array(), $status = '' ) {

		// Bail without a dataset or status.
		if ( empty( $dataset ) || empty( $status ) ) {
			return;
		}

		// Loop the dataset.
		foreach ( $dataset as $index => $values ) {

			// If we do not have a match, unset it and go about our day.
			if ( empty( $values['review_status'] ) || esc_attr( $status ) !== esc_attr( $values['review_status'] ) ) {
				unset( $dataset[ $index ] );
			}
		}

		// Return thge dataset, with the array keys reset.
		return ! empty( $dataset ) ? array_values( $dataset ) : array();
	}

	/**
	 * Define what data to show on each column of the table.
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
			case 'review_product' :
			case 'review_date' :
			case 'review_status' :
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
	 * Handle the display text for when no items exist.
	 *
	 * @return string
	 */
	public function no_items() {
		_e( 'No reviews avaliable.', 'woo-better-reviews' );
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