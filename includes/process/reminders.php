<?php
/**
 * Run the review reminders.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Reminders;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'wc_better_reviews_trigger_after_purchase_order_line_items', __NAMESPACE__ . '\maybe_set_reminder', 10, 3 );

/**
 * Check to see if we need to set reminders for purchased products.
 *
 * @param  array   $product_ids  The product ID of each item in the order.
 * @param  array   $order_data   The entire order data.
 * @param  integer $order_id     The order ID being run.
 *
 * @return void
 */
function maybe_set_reminder( $product_ids, $order_data, $order_id ) {

	// Bail if no product IDs or order status came through.
	if ( empty( $product_ids ) || empty( $order_data['status'] ) ) {
		die('missing stuff');
		return;
	}

	// Run the main check for being enabled.
	$maybe_enabled  = Helpers\maybe_reminders_enabled();

	// Bail if not enabled.
	if ( ! $maybe_enabled ) {
		die('not enabled');
		return;
	}

	// Check if we have an allowed status.
	$maybe_allowed  = allowed_reminder_status( $order_data['status'] );

	// Bail if not allowed.
	if ( ! $maybe_allowed ) {
		die('not allowed');
		return;
	}

	// Set some empty variable.
	$reminder_arr   = array();

	// Now loop the product IDs and check each one.
	foreach ( $product_ids as $product_id ) {

		// Get the meta key.
		$meta_check = get_post_meta( $product_id, Core\META_PREFIX . 'send_reminder', true );

		// If we have a specific "no", then skip.
		if ( ! empty( $meta_check ) && 'no' === sanitize_text_field( $meta_check ) ) {
			continue;
		}

		// Add my product ID and the date stamp.
		$reminder_arr[] = array(
			'product_id' => absint( $product_id ),
			'timestamp' => Utilities\calculate_relative_date( $product_id ),
		);
	}

	// If all were set to 'no', then bail.
	if ( empty( $reminder_arr ) ) {
		die('all empty');
		return;
	}

	preprint( $reminder_arr, true );

	// Set the array of products to set reminders to.
	update_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_status', 'pending' );
	update_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_data', $reminder_arr );

	// Core\META_PREFIX . 'reminder_wait'
}

/**
 * Check the order status against the ones we will allow.
 *
 * @param  string $order_status  The status being checked.
 *
 * @return boolean
 */
function allowed_reminder_status( $order_status ) {

	// Bail without a status to check.
	if ( empty( $order_status ) ) {
		return false;
	}

	// Set our allowed statuses.
	$allowed_statuses   = apply_filters( Core\AFTER_PURCHASE_TRIGGER . 'reminder_order_statuses', array( 'completed' ) );

	// Return the boolean based on the match.
	return empty( $allowed_statuses ) || ! in_array( $order_status, $allowed_statuses ) ? false : true;
}
