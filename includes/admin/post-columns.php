<?php
/**
 * Load our various post column items for the admin.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\Admin\PostColumns;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'pre_get_posts', __NAMESPACE__ . '\modify_product_sort_query', 1 );
add_action( 'manage_posts_custom_column', __NAMESPACE__ . '\load_product_column_data', 10, 2 );
add_filter( 'manage_edit-product_columns', __NAMESPACE__ . '\add_product_column_display' );
add_filter( 'manage_edit-product_sortable_columns', __NAMESPACE__ . '\add_product_column_sortable' );

/**
 * Check for our review count sort request.
 *
 * @param  object $query  The existing display query.
 *
 * @return void
 */
function modify_product_sort_query( $query ) {

	// Make sure we're in the right place.
	if ( ! is_admin() || ! $query->is_main_query() || 'product' !== $query->get( 'post_type' ) ) {
		return;
	}

	// If we have one of our orderby keys, modify the query.
	if ( ! empty( $query->get( 'orderby' ) ) && in_array( $query->get( 'orderby' ), array( 'review_count', 'average_rating' ) ) ) {

		// Determine the query key.
		$query_key  = esc_attr( $query->get( 'orderby' ) );

		// Set the key itself, enforce the type, and the number key.
		$query->set( 'meta_key', $query_key );
		$query->set( 'meta_type', 'NUMERIC' );
		$query->set( 'orderby', 'meta_value_num' );
	}

	// No other changes should be required.
}

/**
 * Generate the data needed for any custom columns.
 *
 * @param  string  $column   The name of the column
 * @param  integer $post_id  The ID of the post in that row.
 *
 * @return mixed
 */
function load_product_column_data( $column, $post_id ) {

	// Begin the big column switch.
	switch ( $column ) {

		// Handle our count.
		case 'wbr-count' :

			// Get the count.
			$review_count   = Helpers\get_admin_review_count( $post_id );

			// Show it.
			echo '<span class="wbr-review-col-count">' . absint( $review_count ) . '</span>';

			// And be done.
			break;

		// Handle the average score.
		case 'wbr-average' :

			// Grab the layout (or pull the n/a).
			$maybe_average  = Helpers\get_scoring_stars_display( $post_id, 0, false );

			// Now the display.
			$maybe_display  = ! empty( $maybe_average ) ? $maybe_average : '<em>' . __( 'none','woo-better-reviews' ) . '</em>';

			// Show it.
			echo '<span class="wbr-review-col-average woo-better-reviews-admin-table-display woo-better-reviews-admin-table-review-score">' . $maybe_display . '</span>';

			// And be done.
			break;

		// End all case breaks.
	}
}

/**
 * Add a new column to show the review count.
 *
 * @param  array $columns  The existing array of columns.
 *
 * @return array $columns  The modified array of columns.
 */
function add_product_column_display( $columns ) {

	// Blank my holder first.
	$date_hold  = '';

	// Begin part of the shifting of the date column.
	if ( isset( $columns['date'] ) ) {

		// Set a holder for the date.
		$date_hold  = $columns['date'];

		// And remove the date.
		unset( $columns['date'] );
	}

	// Add our count column if it hasn't already been.
	if ( ! isset( $columns['wbr-count'] ) ) {
		$columns['wbr-count'] = __( 'Review Count','woo-better-reviews' );
	}

	// Add our average column if it hasn't already been.
	if ( ! isset( $columns['wbr-average'] ) ) {
		$columns['wbr-average'] = __( 'Average Rating','woo-better-reviews' );
	}

	// Now add back the date.
	if ( ! empty( $date_hold ) ) {
		$columns['date'] = $date_hold;
	}

	// Return our array of columns.
	return $columns;
}

/**
 * Add our new review count into the array of sortable columns.
 *
 * @param  array $sortable_columns  The existing array of columns.
 *
 * @return array $sortable_columns  The modified array of columns.
 */
function add_product_column_sortable( $sortable_columns ) {

	// Add our column if it hasn't already been.
	if ( ! isset( $sortable_columns['wbr-count'] ) ) {

		// Add our column.
		$sortable_columns['wbr-count'] = 'review_count';
	}

	// Add our column if it hasn't already been.
	if ( ! isset( $sortable_columns['wbr-average'] ) ) {

		// Add our column.
		$sortable_columns['wbr-average'] = 'average_rating';
	}

	// Return our array of columns.
	return $sortable_columns;
}
