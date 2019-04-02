<?php
/**
 * Handle display related Woo stuff.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display\WooFilters;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;

// woocommerce_product_tabs

/**
 * Start our engines.
 */
add_filter( 'woocommerce_product_reviews_tab_title', __NAMESPACE__ . '\modify_review_count_title', 99, 2 );

/**
 * Check if we have a sorted review list and modify the count.
 *
 * @param  string $title  The current tab title.
 * @param  string $key    What key we're on.
 *
 * @return array
 */
function modify_review_count_title( $title, $key ) {

	// Bail without the reviews tab.
	if ( 'reviews' !== sanitize_text_field( $key ) ) {
		return $title;
	}

	// Set my global.
	global $product;

	// Set the count of filtered or the total.
	$review_count   = Helpers\get_front_review_count( $product->get_id() );

	// If we have filtered IDs, change my title.
	return sprintf( __( 'Reviews (%s)', 'woocommerce' ), '<span class="wbr-review-tab-count">' . absint( $review_count ) . '</span>' );
}
