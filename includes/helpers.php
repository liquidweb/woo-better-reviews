<?php
/**
 * Our helper functions to use across the plugin.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Helpers;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Queries as Queries;

/**
 * Set the key / value pair for all our custom tables.
 *
 * @param  boolean $keys  Whether to return just the keys.
 *
 * @return array
 */
function get_table_args( $keys = false ) {

	// Set up our array.
	$tables = array(
		'content'      => __( 'Review Content', 'woo-better-reviews' ),
		'authormeta'   => __( 'Author Meta', 'woo-better-reviews' ),
		'ratings'      => __( 'Review Ratings', 'woo-better-reviews' ),
		'attributes'   => __( 'Product Attributes', 'woo-better-reviews' ),
		'charstcs'     => __( 'Author Characteristics', 'woo-better-reviews' ),
		'authorsetup'  => __( 'Author Setup', 'woo-better-reviews' ),
	);

	// Either return the full array, or just the keys if requested.
	return ! $keys ? $tables : array_keys( $tables );
}

/**
 * Compare the table name to our allowed items.
 *
 * @param  string $table_name  The name (slug) of the table.
 *
 * @return boolean
 */
function maybe_valid_table( $table_name = '' ) {

	// Make sure we have a table name.
	if ( empty( $table_name ) ) {
		return false;
	}

	// Fetch my tables.
	$tables = get_table_args( true );

	// Return the result.
	return in_array( $table_name, $tables ) ? true : false;
}

/**
 * Check to see if reviews are enabled.
 *
 * @param  integer $product_id  The ID of the individual product.
 *
 * @return boolean
 */
function maybe_reviews_enabled( $product_id = 0 ) {

	// Check the Woo setting first.
	$woo_enable = get_option( 'woocommerce_enable_reviews', 0 );

	// Return a basic boolean if no product ID was provided.
	if ( empty( $product_id ) ) {
		return ! empty( $woo_enable ) && 'yes' === sanitize_text_field( $woo_enable ) ? true : false;
	}

	// Now check the single product.
	$is_enabled = comments_open( $product_id );

	// Return this boolean.
	return false !== $is_enabled ? true : false;
}

/**
 * Check to see if schema markup is enabled.
 *
 * @param  integer $product_id  The ID of the individual product.
 *
 * @return boolean
 */
function maybe_schema_enabled( $product_id = 0 ) {

	// Check the setting.
	$set_option = get_option( Core\OPTION_PREFIX . 'enable_schema', 'no' );

	// Set the enabled boolean.
	$is_enabled = ! empty( $set_option ) && 'yes' === sanitize_text_field( $set_option ) ? true : false;

	// Return this boolean.
	return apply_filters( Core\HOOK_PREFIX . 'enabled_schema_markup', $is_enabled, $product_id );
}

/**
 * Check to see if a review is verified.
 *
 * @param  integer $author_id     The ID of the author posting the review.
 * @param  string  $author_email  The email address that the author provided.
 * @param  integer $product_id    The product the review is being left on.
 *
 * @return boolean
 */
function maybe_review_verified( $author_id = 0, $author_email = '', $product_id = 0 ) {

	// Return false if either part is missing.
	if ( empty( $author_id ) && empty( $author_email ) || empty( $product_id ) ) {
		return false;
	}

	// Set the args for getting orders.
	if ( ! empty( $author_email ) ) {
		$set_order_args = array( 'return' => 'ids', 'customer' => sanitize_email( $author_email ) );
	} else {
		$set_order_args = array( 'return' => 'ids', 'customer_id' => absint( $author_id ) );
	}

	// Look up to see if orders exist.
	$maybe_orders   = wc_get_orders( $set_order_args );

	// Bail if no orders exist at all.
	if ( empty( $maybe_orders ) ) {
		return false;
	}

	// Set an empty array.
	$setup  = array();

	// Set a basic counter.
	$i  = 0;

	// Loop my found order IDs.
	foreach ( $maybe_orders as $order_id ) {

		// Pull the order object.
		$order_object   = wc_get_order( $order_id );

		// Try to pull out the items.
		$order_items    = $order_object->get_items();

		// Skip if we don't have items.
		if ( empty( $order_items ) ) {
			continue;
		}

		// Loop the items inside the order data.
		foreach ( $order_items as $item_id => $item_values ) {

			// Pull out the product ID.
			$order_prod_id  = $item_values->get_product_id();

			// Add the single ID.
			$setup[ $i ][]  = $order_prod_id;
		}

		// Increment the counter.
		$i++;

		// Nothing left inside.
	}

	// Bail if no product IDs set up at all.
	if ( empty( $setup ) ) {
		return false;
	}

	// Pull out and flatten my unique product IDs.
	$product_ids    = Queries\merge_order_product_ids( $setup );

	// Bail if no product IDs exist at all.
	if ( empty( $product_ids ) ) {
		return false;
	}

	// Now return if we have it or not.
	return in_array( $product_id, $product_ids ) ? true : false;
}

/**
 * Check to see if product attributes are globally enabled.
 *
 * @return boolean
 */
function maybe_attributes_global() {

	// Check the Woo setting first.
	$are_global = get_option( Core\OPTION_PREFIX . 'global_attributes', 'no' );

	// Return a basic boolean.
	return ! empty( $are_global ) && 'yes' === sanitize_text_field( $are_global ) ? true : false;
}

/**
 * Check to see if there is a search term and return it.
 *
 * @param  string $return  The return type we wanna have. Boolean or string.
 *
 * @return mixed.
 */
function maybe_search_term( $return = 'string' ) {

	// Determine which thing we're returning.
	switch ( esc_attr( $return ) ) {

		case 'string' :

			return isset( $_REQUEST['s'] ) ? wp_unslash( $_REQUEST['s'] ) : '';
			break;

		case 'bool' :
		case 'boolean' :

			return isset( $_REQUEST['s'] ) ? true : false;
			break;

		// End all case breaks.
	}
}

/**
 * Determine if a person is attempting to sort.
 *
 * @return mixed
 */
function maybe_sorted_reviews() {

	// Check for the sort trigger.
	if ( empty( $_POST['wbr-single-sort-submit'] ) ) {
		return false;
	}

	// Handle the nonce check.
	if ( empty( $_POST['wbr_sort_reviews_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_sort_reviews_nonce'], 'wbr_sort_reviews_action' ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Check for the sorting flags.
	if ( empty( $_POST['woo-better-reviews-sorting']['charstcs'] ) ) {
		return false;
	}

	// Check for a product ID.
	if ( empty( $_POST['wbr-single-sort-product-id'] ) ) {
		return false;
	}

	// Check for any actual values.
	$charstcs_unique    = array_filter( $_POST['woo-better-reviews-sorting']['charstcs'] );

	// If they don't have unique items (meaning all zeros), bail.
	if ( empty( $charstcs_unique ) ) {
		return false;
	}

	// Set my single product ID.
	$single_product_id  = absint( $_POST['wbr-single-sort-product-id'] );

	// Set an empty for our return.
	$requested_ids      = array();

	// Set an array of the non-empty.
	$passed_charstcs    = array_map( 'sanitize_text_field', $_POST['woo-better-reviews-sorting']['charstcs'] );

	// Now filter them.
	$sorting_charstcs   = array_filter( $passed_charstcs );

	// Now loop and fetch the review IDs.
	foreach ( $sorting_charstcs as $charstcs_id => $charstcs_value ) {

		// Attempt reviews.
		$maybe_found_items = Queries\get_reviews_for_sorting( $single_product_id, $charstcs_id, $charstcs_value );

		// If no items are found, just bail because we don't have a match.
		if ( empty( $maybe_found_items ) || is_wp_error( $maybe_found_items ) ) {
			return 'none';
		}

		// Get the related review IDs.
		$requested_ids[] = $maybe_found_items;
	}

	// Confirm we have IDs before going forward.
	if ( empty( $requested_ids ) ) {
		return 'none';
	}

	// Now pull my matching reviews, if we have more than one array. Otherwise, send the first.
	$matching_reviews   = isset( $requested_ids[1] ) ? call_user_func_array( 'array_intersect', $requested_ids ) : $requested_ids[0];

	// Return the IDs we have.
	return ! empty( $matching_reviews ) ? $matching_reviews : 'none';
}

/**
 * Check for the query paramaters to paginate the reviews.
 *
 * @param  array   $reviews     The entire set of reviews.
 * @param  integer $product_id  The product ID tied to the reviews.
 *
 * @return array
 */
function maybe_paginate_reviews( $reviews = array(), $product_id = 0 ) {

	// Bail without our reviews.
	if ( empty( $reviews ) ) {
		return false;
	}

	// Set the per-page number.
	$items_per_page = apply_filters( Core\HOOK_PREFIX . 'reviews_per_page', 10, $reviews, $product_id );

	// First reset the array keys.
	$reviews_reset  = array_values( $reviews );

	// If we have equal or less, return the array not chunked.
	if ( count( $reviews_reset ) <= absint( $items_per_page ) ) {

		// Set and return the array values.
		return array(
			'paged'     => false,
			'current'   => false,
			'total'     => 1,
			'increment' => absint( $items_per_page ),
			'items'     => $reviews_reset,
		);
	}

	// Chunk out the reviews.
	$reviews_chunkd = array_chunk( $reviews_reset, absint( $items_per_page ) );

	// Determine the page count we are on.
	if ( empty( $_REQUEST['wbr-paged'] ) || absint( $_REQUEST['wbr-paged'] ) === 1 ) {

		// Set the current page and array chunk key.
		$current_paged  = 1;
		$current_chunk  = 0;

	} else {

		// Set the current page and array chunk key.
		$current_paged  = absint( $_REQUEST['wbr-paged'] );
		$current_chunk  = absint( $_REQUEST['wbr-paged'] ) - 1;
	}

	// Set and return my return args.
	return array(
		'paged'     => true,
		'current'   => absint( $current_paged ),
		'total'     => count( $reviews_chunkd ),
		'increment' => absint( $items_per_page ),
		'items'     => $reviews_chunkd[ $current_chunk ],
	);
}

/**
 * Set and return the array of possible review statuses.
 *
 * @param  boolean $array_keys  Return just the array keys.
 *
 * @return array
 */
function get_review_statuses( $array_keys = false ) {

	// Set up the possible statuses.
	$statuses   = array(
		'approved' => __( 'Approved', 'woo-better-reviews' ),
		'pending'  => __( 'Pending Approval', 'woo-better-reviews' ),
		'rejected' => __( 'Rejected', 'woo-better-reviews' ),
		'hidden'   => __( 'Hidden', 'woo-better-reviews' ),
	);

	// Include via filtered.
	$statuses   = apply_filters( Core\HOOK_PREFIX . 'reviews_statuses', $statuses );

	// Return the array keys or the whole thing.
	return false !== $array_keys ? array_keys( $statuses ) : $statuses;
}

/**
 * Get the attributes the product has assigned.
 *
 * @param  integer $product_id  The product ID we are checking attributes for.
 *
 * @return mixed
 */
function get_selected_product_attributes( $product_id = 0 ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Get the selected attributes (if any).
	$maybe_attributes   = get_post_meta( $product_id, Core\META_PREFIX . 'product_attributes', true );

	// Return false if none are stored.
	return empty( $maybe_attributes ) ? false : $maybe_attributes;
}

/**
 * Get the attributes the product has assigned, potentially global.
 *
 * @param  integer $product_id  The product ID we are checking attributes for.
 *
 * @return mixed
 */
function get_product_attributes_for_conversion( $product_id = 0 ) {

	// First check for the global setting.
	$are_global = maybe_attributes_global();

	// If we are global, send the whole bunch.
	if ( false !== $are_global ) {
		return Queries\get_all_attributes( 'ids' );
	}

	// Now confirm we have a product ID.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Attempt to get our attributes based on the global setting.
	$maybe_has  = Queries\get_attributes_for_product( $product_id, 'ids' );

	// Return the applied items, or return false.
	return ! empty( $maybe_has ) && ! is_wp_error( $maybe_has ) ? $maybe_has : false;

}

/**
 * Construct and return the link for a form in a review.
 *
 * @param  integer $product_id    The product ID being viewed.
 * @param  string  $include_hash  Whether to append a hash to the URL.
 *
 * @return string
 */
function get_review_action_link( $product_id = 0, $include_hash = '' ) {

	// Bail without the product ID.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Get my permalink from the product ID.
	$permalink  = get_permalink( $product_id );

	// Remove any trailing slash.
	$link_setup = trailingslashit( $permalink );

	// Now return the link, with or with a hash.
	return ! empty( $include_hash ) ? $link_setup . '#' . esc_attr( $include_hash ) : $link_setup;
}

/**
 * Get the attributes to display on a form.
 *
 * @param  integer $product_id  The product ID being viewed.
 *
 * @return array
 */
function get_product_attributes_for_form( $product_id = 0 ) {

	// First check for the global setting.
	$are_global = maybe_attributes_global();

	// If we are global, send the whole bunch.
	if ( false !== $are_global ) {
		return Queries\get_all_attributes( 'display' );
	}

	// Now confirm we have a product ID.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Attempt to get our attributes based on the global setting.
	$maybe_has  = Queries\get_attributes_for_product( $product_id, 'display' );

	// Return the applied items, or return false.
	return ! empty( $maybe_has ) && ! is_wp_error( $maybe_has ) ? $maybe_has : false;
}

/**
 * Get the review count from post meta, and optionally set 0.
 *
 * @param  integer $product_id  The product ID we are checking review counts for.
 * @param  boolean $set_zero    Whether to set the zero for meta.
 *
 * @return integer
 */
function get_admin_review_count( $product_id = 0, $set_zero = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Get the count.
	$review_count   = get_post_meta( $product_id, Core\META_PREFIX . 'review_count', true );

	// If we have the count, return it and be done.
	if ( ! empty( $review_count ) ) {
		return $review_count;
	}

	// Set my zero count.
	$review_count   = 0;

	// Set the zero value.
	if ( ! empty( $set_zero ) ) {
		update_post_meta( $product_id, Core\META_PREFIX . 'review_count', $review_count );
	}

	// And return the count.
	return $review_count;
}

/**
 * Get the review count from query count, or post meta.
 *
 * @param  integer $product_id  The product ID we are checking review counts for.
 *
 * @return integer
 */
function get_front_review_count( $product_id = 0 ) {

	// Check for a sorting request.
	$filtered_ids   = maybe_sorted_reviews();

	// Return the count of filtered or the total.
	return false !== $filtered_ids ? count( $filtered_ids ) : get_admin_review_count( $product_id, false );
}

/**
 * Get the review score from post meta.
 *
 * @param  integer $product_id    The product ID we are checking review counts for.
 * @param  integer $review_score  An absolute score to use instead of a product.
 * @param  boolean $include_div   Wrap the div on it (or not).
 *
 * @return integer
 */
function get_scoring_stars_display( $product_id = 0, $review_score = 0, $include_div = true ) {

	// Bail without a product ID and score.
	if ( empty( $product_id ) && empty( $review_score ) ) {
		return false;
	}

	// Attempt to get a score if we don't have one.
	if ( empty( $review_score ) ) {
		$review_score   = get_post_meta( $product_id, Core\META_PREFIX . 'average_rating', true );
	}

	// Bail with no score.
	if ( empty( $review_score ) ) {
		return;
	}

	// Determine the score parts.
	$score_show = absint( $review_score );
	$score_left = $score_show < 7 ? 7 - $score_show : 0;

	// Set the aria label.
	$aria_label = sprintf( __( 'Overall Score: %s', 'woo-better-reviews' ), absint( $score_show ) );

	// Set the base class for a star.
	$star_class = 'dashicons dashicons-star-filled woo-better-reviews-single-star';

	// Set the empty.
	$setup  = '';

	// Wrap the whole thing in a div.
	$setup .= false !== $include_div ? '<div class="woo-better-reviews-list-title-score-wrapper">' : '';

		// Wrap it in a span.
		$setup  .= '<span class="woo-better-reviews-list-total-score" aria-label="' . esc_attr( $aria_label ) . '">';

			// Output the full stars.
			$setup  .= str_repeat( '<i class="' . esc_attr( $star_class ) . ' woo-better-reviews-single-star-full"></i>', $score_show );

			// Output the empty stars.
			if ( $score_left > 0 ) {
				$setup  .= str_repeat( '<i class="' . esc_attr( $star_class ) . ' woo-better-reviews-single-star-empty"></i>', $score_left );
			}

		// Close the span.
		$setup  .= '</span>';

	// Close the div.
	$setup .= false !== $include_div ? '</div>' : '';

	// Return the setup.
	return $setup;
}

/**
 * Return our base link, with function fallbacks.
 *
 * @param  string $menu_slug  Which menu slug to use. Defaults to the primary.
 *
 * @return string
 */
function get_admin_menu_link( $menu_slug = '' ) {

	// Bail if we aren't on the admin side.
	if ( ! is_admin() ) {
		return false;
	}

	// Set my slug.
	$menu_slug  = ! empty( $menu_slug ) ? trim( $menu_slug ) : trim( Core\REVIEWS_ANCHOR );

	// Build out the link if we don't have our function.
	if ( ! function_exists( 'menu_page_url' ) ) {

		// Set up my args.
		$setup  = array( 'page' => $menu_slug );

		// Return the link with our args.
		return add_query_arg( $setup, admin_url( 'admin.php' ) );
	}

	// Return using the function.
	return menu_page_url( $menu_slug, false );
}

/**
 * Handle our redirect within the admin settings page.
 *
 * @param  array   $custom_args  The query args to include in the redirect.
 * @param  string  $menu_slug    Which menu slug to use. Defaults to the primary.
 * @param  boolean $response     Whether to include a response code.
 *
 * @return void
 */
function admin_page_redirect( $custom_args = array(), $menu_slug = '', $response = true ) {

	// Don't redirect if we didn't pass any args.
	if ( empty( $custom_args ) ) {
		return;
	}

	// Set my slug.
	$redirect_slug  = ! empty( $menu_slug ) ? trim( $menu_slug ) : trim( Core\REVIEWS_ANCHOR );

	// Handle the setup.
	$redirect_base  = get_admin_menu_link( $redirect_slug );

	// Set our redirect args.
	$redirect_args  = false !== $response ? wp_parse_args( array( 'wbr-action-complete' => 1 ), $custom_args ) : $custom_args;

	// Now set my redirect link.
	$redirect_link  = add_query_arg( $redirect_args, $redirect_base );

	// Do the redirect.
	wp_safe_redirect( $redirect_link );
	exit;
}

/**
 * Get the various parts of a product for the reviews list.
 *
 * @param  integer $product_id  The product ID we want.
 *
 * @return array
 */
function get_admin_product_data( $product_id = 0 ) {

	// Make sure we have valid ID.
	if ( empty( $product_id ) || 'product' !== get_post_type( $product_id ) ) {
		return false;
	}

	// Set up and return the data.
	return array(
		'title'     => get_the_title( $product_id ),
		'permalink' => get_permalink( $product_id ),
		'edit-link' => get_edit_post_link( $product_id, 'raw' ),
	);
}

/**
 * Create and return the available field type array.
 *
 * @return array
 */
function get_available_field_types() {

	// Build our array of column setups.
	$field_args = array(
		'dropdown' => __( 'Dropdown', 'woo-better-reviews' ),
		'radio'    => __( 'Radio', 'woo-better-reviews' ),
		'boolean'  => __( 'Boolean (Yes / No)', 'woo-better-reviews' ),
	);

	// Return filtered.
	return apply_filters( Core\HOOK_PREFIX . 'charstcs_field_types', $field_args );
}

/**
 * Check an code and (usually an error) return the appropriate text.
 *
 * @param  string $return_code  The code provided.
 *
 * @return string
 */
function get_error_notice_text( $return_code = '' ) {

	// Handle my different error codes.
	switch ( esc_attr( $return_code ) ) {

		case 'review-posted' :
			return __( 'Your review has been submitted and is pending approval.', 'woo-better-reviews' );
			break;

		case 'review-post-failed' :
			return __( 'There was an error attempting to save your review.', 'woo-better-reviews' );
			break;

		case 'review-updated' :
			return __( 'The selected review has been updated.', 'woo-better-reviews' );
			break;

		case 'review-deleted' :
			return __( 'The selected review has been deleted.', 'woo-better-reviews' );
			break;

		case 'attribute-added' :
			return __( 'The new attribute has been added.', 'woo-better-reviews' );
			break;

		case 'attribute-updated' :
			return __( 'The selected attribute has been updated.', 'woo-better-reviews' );
			break;

		case 'attribute-deleted' :
			return __( 'The selected attribute has been deleted.', 'woo-better-reviews' );
			break;

		case 'attribute-deleted-bulk' :
			return __( 'The selected attributes have been deleted.', 'woo-better-reviews' );
			break;

		case 'missing-attribute-args' :
			return __( 'The required attribute arguments were not provided.', 'woo-better-reviews' );
			break;

		case 'attribute-update-failed' :
			return __( 'The attribute could not be updated at this time.', 'woo-better-reviews' );
			break;

		case 'attribute-delete-failed' :
			return __( 'The selected attribute could not be deleted at this time.', 'woo-better-reviews' );
			break;

		case 'charstcs-added' :
			return __( 'The new characteristic has been added.', 'woo-better-reviews' );
			break;

		case 'charstcs-updated' :
			return __( 'The selected characteristic has been updated.', 'woo-better-reviews' );
			break;

		case 'charstcs-deleted' :
			return __( 'The selected characteristic has been deleted.', 'woo-better-reviews' );
			break;

		case 'charstcs-deleted-bulk' :
			return __( 'The selected characteristics have been deleted.', 'woo-better-reviews' );
			break;

		case 'missing-charstcs-args' :
			return __( 'The required characteristic arguments were not provided.', 'woo-better-reviews' );
			break;

		case 'charstcs-update-failed' :
			return __( 'The characteristic could not be updated at this time.', 'woo-better-reviews' );
			break;

		case 'charstcs-delete-failed' :
			return __( 'The selected characteristic could not be deleted at this time.', 'woo-better-reviews' );
			break;

		case 'missing-item-id' :
			return __( 'The required ID was not posted.', 'woo-better-reviews' );
			break;

		case 'missing-posted-args' :
			return __( 'The required arguments were not posted.', 'woo-better-reviews' );
			break;

		case 'missing-formatted-args' :
			return __( 'The required arguments could not be formatted.', 'woo-better-reviews' );
			break;

		case 'reviews-approved-bulk' :
			return __( 'The selected reviews have been updated.', 'woo-better-reviews' );
			break;

		case 'review-approved-single' :
			return __( 'The selected review has been approved.', 'woo-better-reviews' );
			break;

		case 'reviews-deleted-bulk' :
			return __( 'The selected reviews have been deleted.', 'woo-better-reviews' );
			break;

		case 'status-changed-bulk' :
			return __( 'The selected review statuses have been updated.', 'woo-better-reviews' );
			break;

		case 'unknown' :
		case 'unknown-error' :
			return __( 'There was an unknown error with your request.', 'woo-better-reviews' );
			break;

		default :
			return __( 'There was an error with your request.', 'woo-better-reviews' );
			break;

		// End all case breaks.
	}
}
