<?php
/**
 * Our table setup for the handling the data pieces.
 *
 * @package WooBetterReviews
 */

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;
use LiquidWeb\WooBetterReviews\Database as Database;

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

		// Make sure we have the status change.
		$this->process_status_change();

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
			'review_author'  => __( 'Author', 'woo-better-reviews' ),
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
			$link_class = $this->get_current_status_class( $status_key );

			// Now set up the link args.
			if ( ! empty( $status_data['setup'] ) && 'single' === sanitize_text_field( $status_data['setup'] ) ) {

				// Our phandom single link args.
				$link_args  = array(
					'wbr-review-filter' => 1,
					'wbr-product-id'    => absint( $_GET['wbr-product-id'] ),
				);

			} else {

				// Our standard link args.
				$link_args  = array(
					'wbr-review-filter' => 1,
					'wbr-review-status' => esc_attr( $status_key ),
				);
			}

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
			'count' => count( $status_ids ),
			'setup' => 'status',
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
				'setup' => 'status',
			);
		}

		// If we have a single product, show that.
		if ( ! empty( $_GET['wbr-review-filter'] ) && ! empty( $_GET['wbr-product-id'] ) ) {

			// Set my product ID.
			$product_id = absint( $_GET['wbr-product-id'] );

			// Handle the individual count by using the array keys and status.
			$setup['filtered'] = array(
				'label' => get_the_title( $product_id ),
				'count' => Queries\get_review_count_for_product( $product_id ),
				'setup' => 'single',
			);
		}

		// Return it, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'status_counts_data', $setup );
	}

	/**
	 * Determine the proper class item.
	 *
	 * @param  string $status_key  The specific status key.
	 *
	 * @return string
	 */
	private function get_current_status_class( $status_key = '' ) {

		// Check for a current.
		$now_status = ! empty( $_GET['wbr-review-status'] ) ? sanitize_text_field( $_GET['wbr-review-status'] ) : '';

		// Set the initial (empty) class.
		$link_class = '';

		// First check for the "all" class.
		if ( empty( $_GET['wbr-product-id'] ) && empty( $now_status ) && 'all' === $status_key ) {
			return 'current';
		}

		// Check for the single product ID.
		if ( ! empty( $_GET['wbr-review-filter'] ) && ! empty( $_GET['wbr-product-id'] ) && 'filtered' === $status_key ) {
			return 'current';
		}

		// Check for the status matching.
		if ( ! empty( $_GET['wbr-review-filter'] ) && ! empty( $now_status ) && $now_status === $status_key ) {
			return 'current';
		}

		// Return what we have.
		return $link_class;
	}

	/**
	 * Add extra markup in the toolbars before or after the list.
	 *
	 * @param  string $which  Which markup area after (bottom) or before (top) the list.
	 *
	 * @return HTML
	 */
	protected function extra_tablenav( $which ) {

		// Get my dropdown values.
		$status_dropdown    = $this->set_status_change_dropdown();

		// Set our empty.
		$build  = '';

		// Set up the top bar.
		if ( ! empty( $status_dropdown ) && 'top' === $which ) {
			$build .= '<div class="alignleft actions">' . $status_dropdown . '</div>';
		}

		// Echo out the filtered version.
		echo apply_filters( Core\HOOK_PREFIX . 'review_table_extra_tablenav', $build, $which );
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
			'wbr_bulk_approve' => __( 'Approve Pending', 'woo-better-reviews' ),
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

		// Make sure we have the page we want.
		if ( empty( $_GET['page'] ) || Core\REVIEWS_ANCHOR !== sanitize_text_field( $_GET['page'] ) ) {
			return;
		}

		// Bail if we aren't on the doing our requested action.
		if ( empty( $this->current_action() ) || ! in_array( $this->current_action(), array_keys( $this->get_bulk_actions() ) ) ) {
			return;
		}

		// Handle the nonce check.
		if ( empty( $_POST['wbr_list_reviews_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_list_reviews_nonce'], 'wbr_list_reviews_action' ) ) {
			wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
		}

		// Check for the array of review IDs being passed.
		if ( empty( $_POST['review-ids'] ) ) {

			// Set my error return args.
			$redirect_args  = array(
				'success'           => false,
				'wbr-action-result' => 'failed',
				'wbr-error-code'    => 'missing-review-ids',
			);

			// And redirect.
			Helpers\admin_page_redirect( $redirect_args, Core\REVIEWS_ANCHOR );
		}

		// Set my review IDs.
		$review_ids = array_map( 'absint', $_POST['review-ids'] );

		// Set an empty array for updating.
		$tochange   = array();

		// Now loop my IDs and attempt to update each one.
		foreach ( $review_ids as $review_id ) {

			// Get my single review data.
			$single_review  = Queries\get_single_review( $review_id );

			// Check the status so we don't change unneeded.
			if ( 'approved' === $single_review->review_status ) {
				continue;
			}

			// Run the update.
			$maybe_updated  = Database\update( 'content', absint( $review_id ), array( 'review_status' => 'approved' ) );

			// Check for some error return or blank.
			if ( empty( $maybe_updated ) || false === $maybe_updated || is_wp_error( $maybe_updated ) ) {

				// Figure out the error code.
				$error_code     = is_wp_error( $maybe_updated ) ? $maybe_updated->get_error_code() : 'review-update-failed';

				// Set my error return args.
				$redirect_args  = array(
					'success'           => false,
					'wbr-action-result' => 'failed',
					'wbr-error-code'    => $error_code,
				);

				// And redirect.
				Helpers\admin_page_redirect( $redirect_args, Core\REVIEWS_ANCHOR );
			}

			// Add the ID to the update.
			$tochange[] = $single_review->product_id;

			// Handle the transient purging.
			Utilities\purge_transients( null, 'reviews' );

			// Nothing left in the loop to do.
		}

		// Run the change loop if we have items.
		if ( ! empty( $tochange ) ) {

			// Get just the individual unique IDs.
			$update_ids = array_unique( $tochange );

			// Update all my counts.
			Utilities\update_product_review_count( $update_ids );

			// Recalculate the total score on each.
			foreach ( $update_ids as $update_id ) {
				Utilities\calculate_total_review_scoring( $update_id );
			}

			// Nothing left for the changed items.
		}

		// Set my success args.
		$redirect_args  = array(
			'success'           => 1,
			'wbr-action-result' => 'reviews-approved-bulk',
		);

		// And redirect.
		Helpers\admin_page_redirect( $redirect_args, Core\REVIEWS_ANCHOR );
	}

	/**
	 * Handle the specific status type changes.
	 *
	 * @return void
	 */
	protected function process_status_change() {

		// Make sure we have the page we want.
		if ( empty( $_GET['page'] ) || Core\REVIEWS_ANCHOR !== sanitize_text_field( $_GET['page'] ) ) {
			return;
		}

		// Bail if we aren't on the doing our requested action.
		if ( ! isset( $_POST['wbr-change-selected-reviews'] ) || ! empty( $this->current_action() ) ) {
			return;
		}

		// Handle the nonce check.
		if ( empty( $_POST['wbr_list_reviews_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_list_reviews_nonce'], 'wbr_list_reviews_action' ) ) {
			wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
		}

		// Check for the new status being provided.
		if ( empty( $_POST['wbr-change-reviews-new-status'] ) ) {

			// Set my error return args.
			$redirect_args  = array(
				'success'           => false,
				'wbr-action-result' => 'failed',
				'wbr-error-code'    => 'missing-review-status',
			);

			// And redirect.
			Helpers\admin_page_redirect( $redirect_args, Core\REVIEWS_ANCHOR );
		}

		// Set my new status.
		$new_status = sanitize_text_field( $_POST['wbr-change-reviews-new-status'] );

		// Confirm the new status is a valid one.
		if ( ! in_array( $new_status, Helpers\get_review_statuses( true ) ) ) {

			// Set my error return args.
			$redirect_args  = array(
				'success'           => false,
				'wbr-action-result' => 'failed',
				'wbr-error-code'    => 'invalid-review-status',
			);

			// And redirect.
			Helpers\admin_page_redirect( $redirect_args, Core\REVIEWS_ANCHOR );
		}

		// Check for the array of review IDs being passed.
		if ( empty( $_POST['review-ids'] ) ) {

			// Set my error return args.
			$redirect_args  = array(
				'success'           => false,
				'wbr-action-result' => 'failed',
				'wbr-error-code'    => 'missing-review-ids',
			);

			// And redirect.
			Helpers\admin_page_redirect( $redirect_args, Core\REVIEWS_ANCHOR );
		}

		// Set my review IDs.
		$review_ids = array_map( 'absint', $_POST['review-ids'] );

		// Set an empty array for updating.
		$tochange   = array();

		// Now loop my IDs and attempt to update each one.
		foreach ( $review_ids as $review_id ) {

			// Get my single review data.
			$single_review  = Queries\get_single_review( $review_id );

			// Check the status so we don't change unneeded.
			if ( $new_status === $single_review->review_status ) {
				continue;
			}

			// Run the update.
			$maybe_updated  = Database\update( 'content', absint( $review_id ), array( 'review_status' => $new_status ) );

			// Check for some error return or blank.
			if ( empty( $maybe_updated ) || false === $maybe_updated || is_wp_error( $maybe_updated ) ) {

				// Figure out the error code.
				$error_code     = is_wp_error( $maybe_updated ) ? $maybe_updated->get_error_code() : 'review-update-failed';

				// Set my error return args.
				$redirect_args  = array(
					'success'           => false,
					'wbr-action-result' => 'failed',
					'wbr-error-code'    => $error_code,
				);

				// And redirect.
				Helpers\admin_page_redirect( $redirect_args, Core\REVIEWS_ANCHOR );
			}

			// Add the ID to the update.
			$tochange[] = $single_review->product_id;

			// Handle the transient purging.
			Utilities\purge_transients( Core\HOOK_PREFIX . 'single_review_' . $review_id, 'reviews' );

			// Nothing left in the loop to do.
		}

		// Run the change loop if we have items.
		if ( ! empty( $tochange ) ) {

			// Get just the individual unique IDs.
			$update_ids = array_unique( $tochange );

			// Update all my counts.
			Utilities\update_product_review_count( $update_ids );

			// Recalculate the total score on each.
			foreach ( $update_ids as $update_id ) {
				Utilities\calculate_total_review_scoring( $update_id );
			}

			// Nothing left for the changed items.
		}

		// Set my success args.
		$redirect_args  = array(
			'success'           => 1,
			'wbr-action-result' => 'status-changed-bulk',
		);

		// And redirect.
		Helpers\admin_page_redirect( $redirect_args, Core\REVIEWS_ANCHOR );
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
		return '<input type="checkbox" name="review-ids[]" class="woo-better-reviews-admin-checkbox" id="cb-' . $id . '" value="' . $id . '" /><label for="cb-' . $id . '" class="screen-reader-text">' . __( 'Select review', 'woo-better-reviews' ) . '</label>';
	}

	/**
	 * The visible name column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_review_title( $item ) {

		// Get the edit link.
		$edit_link  = $this->get_single_review_action_link( $item['id'], 'edit' );

		// Build my markup.
		$setup  = '';

		// Set the display name.
		$setup .= '<span class="woo-better-reviews-admin-table-display woo-better-reviews-admin-table-review-title">';
			$setup .= '<a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $item['review_title'] ) . '</a>';
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
	 * The review author column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_review_author( $item ) {

		// Determine the author name.
		$author_name    = ! empty( $item['author_name'] ) ? $item['author_name'] : __( 'Unknown Reviewer', 'woo-better-reviews' );

		// Build my markup.
		$setup  = '';

		// Set the product name.
		$setup .= '<span class="woo-better-reviews-admin-table-display woo-better-reviews-admin-table-review-author">';

		// Look for an author ID.
		if ( empty( $item['author_id'] ) ) {

			// Make the name.
			$setup .= '<em>' . esc_html( $author_name ) . '</em>';

		} else {

			// Get the edit link based on the ID.
			$edit_link  = get_edit_user_link( absint( $item['author_id'] ) );

			// Set some text.
			$edit_text  = __( 'View the user profile', 'woo-better-reviews' );

			// And output.
			$setup .= '<a title="' . esc_attr( $edit_text ) . '" href="' . esc_url( $edit_link ) . '">' . esc_html( $author_name ) . '</a>';
		}

		// Close the span.
		$setup .= '</span>';

		// Return my formatted product name.
		return apply_filters( Core\HOOK_PREFIX . 'review_table_column_review_author', $setup, $item );
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
		$review_objects = Queries\get_reviews_for_admin();

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

		// Check for the status filter query.
		if ( ! empty( $_GET['wbr-review-status'] ) ) {

			// Get my status.
			$status = sanitize_text_field( $_GET['wbr-review-status'] );

			// And return the dataset.
			return $this->filter_dataset_by_status( $dataset, $status );
		}

		// Check for the product ID filter query.
		if ( ! empty( $_GET['wbr-product-id'] ) ) {

			// Get my ID.
			$product_id = absint( $_GET['wbr-product-id'] );

			// And return the dataset.
			return $this->filter_dataset_by_id( $dataset, $product_id );
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
			return $dataset;
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
	 * Filter out the dataset by ID.
	 *
	 * @param  array   $dataset     The dataset we wanna filter.
	 * @param  integer $product_id  The product ID we are checking for.
	 *
	 * @return array
	 */
	private function filter_dataset_by_id( $dataset = array(), $product_id = 0 ) {

		// Bail without a dataset or product ID.
		if ( empty( $dataset ) || empty( $product_id ) ) {
			return $dataset;
		}

		// Loop the dataset.
		foreach ( $dataset as $index => $values ) {

			// If we do not have a match, unset it and go about our day.
			if ( empty( $values['product_id'] ) || absint( $product_id ) !== absint( $values['product_id'] ) ) {
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
			case 'review_author' :
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

		// Grab our settings page admin link.
		$settings_link  = Helpers\get_admin_menu_link( Core\REVIEWS_ANCHOR );

		// Set my review ID.
		$review_id      = absint( $item['id'] );

		// Create the array of action items.
		$action_dataset = $this->get_row_action_dataset( $review_id );

		// Set an empty array.
		$setup  = array();

		// Now loop and create the links.
		foreach ( $action_dataset as $action_name => $args ) {

			// Make my action link.
			$action_link    = $this->get_single_review_action_link( $review_id, $action_name );

			// Bail without a link.
			if ( empty( $action_link ) ) {
				continue;
			}

			// Set the classes.
			$action_class   = 'woo-better-reviews-action-link woo-better-reviews-single-action-link woo-better-reviews-single-' . esc_attr( $action_name ) . '-action-link';

			// Set an empty.
			$build  = '';

			// Now set up the markup.
			$build .= '<a class="' . esc_attr( $action_class ) . '"';

			// Check for a title.
			if ( ! empty( $args['title'] ) ) {
				$build .= ' title="' . esc_attr( $args['title'] ) . '"';
			}

			// Check for data items.
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

		// Return the table row.
		return apply_filters( Core\HOOK_PREFIX . 'review_table_row_actions', $setup, $item, $review_id );
	}

	/**
	 * Get the dataset for the action links.
	 *
	 * @param  integer $review_id      The individual review we are making links for.
	 * @param  string  $single_action  Request one action from the entire array.
	 *
	 * @return array
	 */
	protected function get_row_action_dataset( $review_id = 0, $single_action = '' ) {

		// Create the array of action items.
		$action_dataset = array(
			'edit' => array(
				'nonce'  => wp_create_nonce( 'wbr_edit_single_' . $review_id ),
				'label'  => __( 'Edit', 'woo-better-reviews' ),
				'title'  => __( 'Edit Review', 'woo-better-reviews' ),
				'data'   => array(
					'item-id'   => $review_id,
					'item-type' => 'review',
					'nonce'     => wp_create_nonce( 'wbr_edit_single_' . $review_id ),
				),
			),

			'delete' => array(
				'nonce'  => wp_create_nonce( 'wbr_delete_single_' . $review_id ),
				'label'  => __( 'Delete', 'woo-better-reviews' ),
				'title'  => __( 'Delete Review', 'woo-better-reviews' ),
				'data'   => array(
					'item-id'   => $review_id,
					'item-type' => 'review',
					'nonce'     => wp_create_nonce( 'wbr_delete_single_' . $review_id ),
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
	 * Create the raw URL for a single review action.
	 *
	 * @param  integer $review_id     The individual review we are making links for.
	 * @param  string  $action_name   The name of the action we want.
	 *
	 * @return string
	 */
	protected function get_single_review_action_link( $review_id = 0, $action_name = '' ) {

		// Bail without the review ID or action name.
		if ( empty( $review_id ) || empty( $action_name ) ) {
			return;
		}

		// Fetch the dataset for an edit link.
		$action_dataset = $this->get_row_action_dataset( $review_id, $action_name );
		// preprint( $action_dataset, true );

		// Bail without the action dataset.
		if ( empty( $action_dataset ) ) {
			return;
		}

		// Get our primary settings link.
		$settings_link  = Helpers\get_admin_menu_link( Core\REVIEWS_ANCHOR );

		// Set the action link args.
		$action_linkset = array(
			'wbr-action-name' => $action_name,
			'wbr-item-id'     => absint( $review_id ),
			'wbr-item-type'   => 'review',
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
		_e( 'No reviews avaliable.', 'woo-better-reviews' );
	}

	/**
	 * Set up the dropdown of statuses.
	 */
	private function set_status_change_dropdown() {

		// First check for statuses.
		$statuses   = Helpers\get_review_statuses();

		// Bail without the statuses.
		if ( empty( $statuses ) ) {
			return;
		}

		// Set up my empty.
		$setup  = '';

		// Set my label.
		$setup .= '<label for="wbr-change-reviews-new-status" class="screen-reader-text">' . __( 'Change Selected Reviews', 'woo-better-reviews' ) . '</label>';

		// Now our select dropdown.
		$setup .= '<select name="wbr-change-reviews-new-status" id="wbr-change-reviews-new-status">';

			// Our blank value.
			$setup .= '<option value="0">(' . __( 'Select Status', 'woo-better-reviews' ) . ')</option>';

			// Now loop them.
			foreach ( $statuses as $type => $label ) {
				$setup .= '<option value="' . esc_attr( $type ) . '">' . esc_attr( $label ) . '</option>';
			}

		// Close out my select.
		$setup .= '</select>';

		// Add the input button.
		$setup .= '<button type="submit" name="wbr-change-selected-reviews" class="button" value="1">' . __( 'Change Selected Reviews', 'woo-better-reviews' ) . '</button>';

		// Return the setup.
		return $setup;
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
