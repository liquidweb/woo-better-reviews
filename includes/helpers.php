<?php
/**
 * Our helper functions to use across the plugin.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\Helpers;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Queries as Queries;

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
 * Check to see if reviews are enabled.
 *
 * @return mixed
 */
function get_stored_plugin_version() {

	// Pull out the stored version.
	$stored_version = get_option( Core\OPTION_PREFIX . 'plugin_version', false );

	// If no version exists, then it hasn't been run.
	if ( empty( $stored_version ) ) {

		// Set the option itself.
		update_option( Core\OPTION_PREFIX . 'plugin_version', Core\VERS );

		// And return the version.
		return Core\VERS;
	}

	// Return the stored version.
	return $stored_version;
}

/**
 * Check to see if WooCommerce is installed and active.
 *
 * @return boolean
 */
function maybe_woo_activated() {
	return class_exists( 'woocommerce' ) ? true : false;
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
 * Check to see if reminders are enabled.
 *
 * @param  integer $product_id   The ID of the individual product.
 * @param  string  $return_type  How to return the result. Boolean or string.
 *
 * @return boolean
 */
function maybe_reminders_enabled( $product_id = 0, $return_type = 'boolean' ) {

	// Check the base setting first.
	$all_reminders  = get_option( Core\OPTION_PREFIX . 'send_reminders', 0 );

	// Set the boolean and string returns.
	$return_boolean = ! empty( $all_reminders ) && 'yes' === sanitize_text_field( $all_reminders ) ? true : false;
	$return_strings = ! empty( $all_reminders ) && 'yes' === sanitize_text_field( $all_reminders ) ? 'yes' : 'no';

	// Return right away if no product ID was passed.
	if ( empty( $product_id ) ) {
		return 'strings' === sanitize_text_field( $return_type ) ? $return_strings : $return_boolean;
	}

	// First get all the meta keys for the product.
	$all_metadata   = get_post_meta( $product_id );

	// Set our meta key as a variable.
	$single_metakey = Core\META_PREFIX . 'send_reminder';

	// If no keys exist at all, or our single meta key isn't, return the global.
	if ( empty( $all_metadata ) || ! isset( $all_metadata[ $single_metakey ] ) ) {
		return 'strings' === sanitize_text_field( $return_type ) ? $return_strings : $return_boolean;
	}

	// Now pull the single product meta.
	$one_reminder   = $all_metadata[ $single_metakey ][0];

	// Set the boolean and string returns.
	$single_boolean = ! empty( $one_reminder ) && 'yes' === sanitize_text_field( $one_reminder ) ? true : false;
	$single_strings = ! empty( $one_reminder ) && 'yes' === sanitize_text_field( $one_reminder ) ? 'yes' : 'no';

	// Now return the results.
	return 'strings' === sanitize_text_field( $return_type ) ? $single_strings : $single_boolean;
}

/**
 * Check the order status against the ones we will allow.
 *
 * @param  string $order_status  The status being checked.
 *
 * @return boolean
 */
function maybe_allowed_status( $order_status ) {

	// Bail without a status to check.
	if ( empty( $order_status ) ) {
		return false;
	}

	// Set our allowed statuses.
	$allowed_statuses   = apply_filters( Core\OPTION_PREFIX . 'reminder_order_statuses', array( 'completed' ) );

	// Return the boolean based on the match.
	return empty( $allowed_statuses ) || ! in_array( $order_status, $allowed_statuses ) ? false : true;
}

/**
 * Check if non logged in users are allowed to leave a review.
 *
 * @param  integer $product_id  The product the review is being left on.
 *
 * @return boolean
 */
function maybe_review_form_allowed( $product_id = 0 ) {

	// Check the stored setting first.
	$allow_anon = get_option( Core\OPTION_PREFIX . 'allow_anonymous', 'no' );

	// If we allow anonymous, we can return true.
	if ( ! empty( $allow_anon ) && 'yes' === sanitize_text_field( $allow_anon ) ) {
		return true;
	}

	// If the user isn't logged in, we bail at this point.
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Check to see if we have the customer-only rule.
	$only_custm = get_option( 'woocommerce_review_rating_verification_required', 'yes' );

	// If we don't force customer purchase, and they are logged in, return true.
	if ( ! empty( $only_custm ) && 'no' === sanitize_text_field( $only_custm ) ) {
		return true;
	}

	// Get my current user ID.
	$set_user   = wp_get_current_user();

	// Bail without user data to work with.
	if ( empty( $set_user ) || is_wp_error( $set_user ) ) {
		return false;
	}

	// Return based on the purchase status or not.
	return false !== wc_customer_bought_product( $set_user->user_email, $set_user->ID, $product_id ) ? true : false;
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
 * Check to see if review author characteristics are globally enabled.
 *
 * @return boolean
 */
function maybe_charstcs_global() {

	// Check the Woo setting first.
	$are_global = get_option( Core\OPTION_PREFIX . 'global_charstcs', 'no' );

	// Return a basic boolean.
	return ! empty( $are_global ) && 'yes' === sanitize_text_field( $are_global ) ? true : false;
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
 * @param  string $return_type  The return type we wanna have. Boolean or string.
 *
 * @return mixed.
 */
function maybe_search_term( $return_type = 'string' ) {

	// Determine which thing we're returning.
	switch ( esc_attr( $return_type ) ) {

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
 * Check if we are supposed to drop the tables on delete.
 *
 * @return boolean
 */
function maybe_preserve_on_delete() {

	// Check the setting first.
	$maybe_preserve = get_option( Core\OPTION_PREFIX . 'preserve_on_delete', 'yes' );

	// Return a basic boolean.
	return ! empty( $maybe_preserve ) && 'yes' === sanitize_text_field( $maybe_preserve ) ? true : false;
}

/**
 * Check if the first install was run.
 *
 * @return boolean
 */
function maybe_first_install() {

	// Check the setting first.
	$is_run = get_option( Core\OPTION_PREFIX . 'first_install_complete', false );

	// Return a basic boolean.
	return empty( $is_run ) ? true : false;
}

/**
 * Check for the default star count value.
 *
 * @param  string  $return_type  The return type we wanna have.
 * @param  integer $compare      If doing a comparison, what to compare against.
 *
 * @return mixed.
 */
function get_default_stars( $return_type = 'integer', $compare = 0 ) {

	// Check the setting first.
	$default_stars  = get_option( Core\OPTION_PREFIX . 'default_stars', '7' );

	// Determine which thing we're returning.
	switch ( esc_attr( $return_type ) ) {

		case 'integer' :

			return absint( $default_stars );
			break;

		case 'bool' :
		case 'boolean' :
		case 'compare' :

			return absint( $default_stars ) === absint( $compare ) ? true : false;
			break;

		// End all case breaks.
	}
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
 * Get the customer data by checking WP user stuff, then order meta.
 *
 * @param  integer $customer_id  The customer ID being checked.
 * @param  integer $order_id     The order ID this is tied to.
 *
 * @return mixed
 */
function get_potential_customer_data( $customer_id = 0, $order_id = 0 ) {

	// Bail if we don't have a customer ID or an order ID.
	if ( empty( $customer_id ) && empty( $order_id ) ) {
		return false;
	}

	// Try to get the customer ID if we have an order ID.
	if ( empty( $customer_id ) && ! empty( $order_id ) ) {

		// Get the customer ID.
		$customer_id    = get_post_meta( $order_id, '_customer_user', true );
	}

	// Try to get the user object first.
	$user_object    = get_user_by( 'id', absint( $customer_id ) );

	// If we have no user object, return what we have.
	if ( ! $user_object ) {

		// Pull the info.
		$customer_email = get_post_meta( $order_id, '_billing_email', true );

		// Get the name stuff.
		$customer_first = get_post_meta( $order_id, '_billing_first_name', true );
		$customer_last  = get_post_meta( $order_id, '_billing_last_name', true );
		$customer_name  = $customer_first . ' ' . $customer_last;

		// Return the array.
		return array(
			'user-id' => $customer_id,
			'email'   => $customer_email,
			'first'   => $customer_first,
			'last'    => $customer_last,
			'name'    => esc_attr( $customer_name ),
			'is-wp'   => false,
		);
	}

	// Get the name stuff.
	$customer_first = $user_object->first_name;
	$customer_last  = $user_object->last_name;
	$customer_name  = ! empty( $user_object->display_name ) ? $user_object->display_name : $customer_first . ' ' . $customer_last;

	// Since we have a user object, return the pieces.
	return array(
		'user-id' => $customer_id,
		'email'   => $user_object->user_email,
		'name'    => esc_attr( $customer_name ),
		'first'   => esc_attr( $customer_first ),
		'last'    => esc_attr( $customer_last ),
		'is-wp'   => true,
	);
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
	$maybe_has_meta = get_post_meta( $product_id, Core\META_PREFIX . 'product_attributes', true );

	// Return false if none are stored.
	return empty( $maybe_has_meta ) ? false : $maybe_has_meta;
}

/**
 * Get the review author traits the product has assigned.
 *
 * @param  integer $product_id  The product ID we are checking attributes for.
 *
 * @return mixed
 */
function get_selected_product_charstcs( $product_id = 0 ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Get the selected characteristics / traits (if any).
	$maybe_has_meta = get_post_meta( $product_id, Core\META_PREFIX . 'product_author_charstcs', true );

	// Return false if none are stored.
	return empty( $maybe_has_meta ) ? false : $maybe_has_meta;
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
 * @param  integer $product_id   The product ID being viewed.
 * @param  string  $return_type  What format we want the data returned in.
 *
 * @return array
 */
function get_review_attributes_for_form( $product_id = 0, $return_type = 'display' ) {

	// First check for the global setting.
	$are_global = maybe_attributes_global();

	// If we are global, send the whole bunch.
	if ( false !== $are_global ) {
		return Queries\get_all_attributes( $return_type );
	}

	// Now confirm we have a product ID.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Attempt to get our attributes based on the global setting.
	$maybe_has  = Queries\get_attributes_for_product( $product_id, $return_type );

	// Return the applied items, or return false.
	return ! empty( $maybe_has ) && ! is_wp_error( $maybe_has ) ? $maybe_has : false;
}

/**
 * Get the attributes to display on a form.
 *
 * @param  integer $product_id   The product ID being viewed.
 * @param  string  $return_type  What format we want the data returned in.
 *
 * @return array
 */
function get_author_traits_for_form( $product_id = 0, $return_type = 'display' ) {

	// First check for the global setting.
	$are_global = maybe_charstcs_global();

	// If we are global, send the whole bunch.
	if ( false !== $are_global ) {
		return Queries\get_all_charstcs( $return_type );
	}

	// Now confirm we have a product ID.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Attempt to get our attributes based on the global setting.
	$maybe_has  = Queries\get_charstcs_for_product( $product_id, $return_type );

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
	$aria_label = sprintf( __( 'Rated %s out of 7 stars', 'woo-better-reviews' ), absint( $score_show ) );

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
 * Check if we are on the admin settings tab.
 *
 * @param  string $hook  Optional hook sent from some actions.
 *
 * @return boolean
 */
function maybe_admin_settings_tab( $hook = '' ) {

	// Can't be the admin tab if we aren't admin.
	if ( ! is_admin() ) {
		return false;
	}

	// Set an array of allowed hooks.
	$allowed_hooks  = array(
		'edit.php',
		'post.php',
		'toplevel_page_' . Core\REVIEWS_ANCHOR,
		'reviews_page_' . Core\ATTRIBUTES_ANCHOR,
		'product-reviews_page_' . Core\ATTRIBUTES_ANCHOR,
		'reviews_page_' . Core\CHARSTCS_ANCHOR,
		'product-reviews_page_' . Core\CHARSTCS_ANCHOR,
	);

	// Check the hook if we passed one.
	if ( ! empty( $hook ) && in_array( $hook, $allowed_hooks ) ) {
		return true;
	}

	// Check the tab portion and return true if it matches.
	if ( ! empty( $_GET['tab'] ) && Core\TAB_BASE === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
		return true;
	}

	// Nothing left to check, so go false.
	return false;
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
 * Return our base link, with function fallbacks.
 *
 * @param  string $menu_slug  Which tab slug to use. Defaults to the primary.
 * @param  string $section    Add a secondary section ID to the query.
 *
 * @return string
 */
function get_admin_tab_link( $tab_slug = '', $section = '' ) {

	// Bail if we aren't on the admin side.
	if ( ! is_admin() ) {
		return false;
	}

	// Set my slug.
	$tab_slug   = ! empty( $tab_slug ) ? trim( $tab_slug ) : trim( Core\TAB_BASE );

	// Set up my args.
	$setup_args = array( 'page' => 'wc-settings', 'tab' => esc_attr( $tab_slug ) );

	// Add the optional section.
	if ( ! empty( $section ) ) {
		$setup_args['section'] = esc_attr( $section );
	}

	// Return the link with our args.
	return add_query_arg( $setup_args, admin_url( 'admin.php' ) );
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
			return __( 'The new review author trait has been added.', 'woo-better-reviews' );
			break;

		case 'charstcs-updated' :
			return __( 'The selected review author trait has been updated.', 'woo-better-reviews' );
			break;

		case 'charstcs-deleted' :
			return __( 'The selected review author trait has been deleted.', 'woo-better-reviews' );
			break;

		case 'charstcs-deleted-bulk' :
			return __( 'The selected review author traits have been deleted.', 'woo-better-reviews' );
			break;

		case 'missing-charstcs-args' :
			return __( 'The required review author trait arguments were not provided.', 'woo-better-reviews' );
			break;

		case 'charstcs-update-failed' :
			return __( 'The review author trait could not be updated at this time.', 'woo-better-reviews' );
			break;

		case 'charstcs-delete-failed' :
			return __( 'The selected review author trait could not be deleted at this time.', 'woo-better-reviews' );
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
