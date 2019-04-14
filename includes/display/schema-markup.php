<?php
/**
 * Construct and render the various schema inserts.
 *
 * @see https://search.google.com/structured-data/testing-tool
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\SchemaMarkup;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;

/**
 * Start our engines.
 */
add_action( 'wp_head', __NAMESPACE__ . '\insert_aggregate_review_schema' );
add_filter( 'wc_better_reviews_before_single_review_output', __NAMESPACE__ . '\insert_single_review_schema', 10, 3 );

/**
 * Insert our schema for the agreegate review data.
 *
 * @return JSON-LD
 */
function insert_aggregate_review_schema() {

	// Bail if we aren't on a product.
	if ( ! is_singular( 'product' ) || ! comments_open() ) {
		return;
	}

	// Run the check.
	$maybe_enabled  = Helpers\maybe_schema_enabled( get_the_ID() );

	// Bail if we aren't enabled.
	if ( ! $maybe_enabled ) {
		return;
	}

	// Query the schema display data.
	$schema_display = Utilities\format_aggregate_review_schema( get_the_ID() );

	// Bail if no display data came back.
	if ( empty( $schema_display ) ) {
		return;
	}

	// Echo the display schema.
	echo $schema_display;
}

/**
 * Insert our schema in the opening part of each review.
 *
 * @param  mixed   $display_view  The existing display in the filter.
 * @param  array   $review_array  The entire review array.
 * @param  integer $product_id    The individual product ID for all the reviews.
 *
 * @return mixed
 */
function insert_single_review_schema( $display_view, $review_array, $product_id ) {

	// Bail without our array data.
	if ( empty( $review_array ) ) {
		return $display_view;
	}

	// Query the schema display data.
	$schema_display = Utilities\format_single_review_schema( $review_array );

	// Bail if no display data came back.
	if ( empty( $schema_display ) ) {
		return $display_view;
	}

	// Return the schema with the display schema.
	return $display_view . $schema_display;
}
