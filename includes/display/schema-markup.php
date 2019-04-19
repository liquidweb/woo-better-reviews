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
use LiquidWeb\WooBetterReviews\Queries as Queries;

/**
 * Start our engines.
 */
add_filter( 'woocommerce_structured_data_product', __NAMESPACE__ . '\append_woo_structured_data', 10, 2 );

/**
 * Add our data to the existing Woo generated structured data.
 *
 * @param  array  $markup   The existing markup data array.
 * @param  object $product  The WC_Product_Simple object.
 *
 * @return mixed
 */
function append_woo_structured_data( $markup, $product ) {

	// Run the check.
	$maybe_disable  = apply_filters( Core\HOOK_PREFIX . 'disable_review_schema', false, $product );

	// Bail if we aren't enabled.
	if ( false !== $maybe_disable ) {
		return;
	}

	// Attempt to get the aggregate data.
	$get_aggregate  = setup_data_for_aggregate( $product->get_id() );

	// If we have the data, add it.
	if ( false !== $get_aggregate ) {

		// Unset the existing aggregate schema data.
		unset( $markup['aggregateRating'] );

		// Now add in our aggregate data.
		$markup['aggregateRating'] = $get_aggregate;
	}

	// Attempt to get the data for singles.
	$get_singles    = setup_data_for_singles( $product->get_id() );

	// If we do not have the data, just return the markup we have.
	if ( false === $get_singles ) {
		return $markup;
	}

	// First add the single review.
	if ( ! empty( $get_singles['review'] ) ) {

		// Unset the existing schema data.
		unset( $markup['review'] );

		// Now add my single review data.
		$markup['review'] = $get_singles['review'];
	}

	// Now add the rest of the reviews.
	if ( ! empty( $get_singles['reviews'] ) ) {

		// Unset the existing schema data.
		unset( $markup['reviews'] );

		// Now add my single review data.
		$markup['reviews'] = $get_singles['reviews'];
	}

	// Send back the markup if we have it.
	return $markup;
}

/**
 * Set up the data piece for the aggregate review.
 *
 * @param  integer $product_id  The product ID we're adding to.
 *
 * @return array
 */
function setup_data_for_aggregate( $product_id = 0 ) {

	// Bail without the product ID.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Pull out the averages and total review count.
	$average_score  = get_post_meta( $product_id, Core\META_PREFIX . 'average_rating', true );
	$review_count   = Helpers\get_admin_review_count( $product_id, false );

	// If we're missing either, bail.
	if ( empty( $average_score ) || empty( $review_count ) ) {
		return false;
	}

	// Set the array of data.
	$aggregate_args = array(
		'@type'       => 'AggregateRating',
		'ratingValue' => esc_attr( $average_score ),
		'bestRating'  => '7',
		'worstRating' => '1',
		'ratingCount' => esc_attr( $review_count ),
	);

	// Return the data, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'schema_aggregate_data_setup', $aggregate_args, $product_id );
}

/**
 * Set up the data piece for the aggregate review.
 *
 * @param  integer $product_id  The product ID we're adding to.
 *
 * @return array
 */
function setup_data_for_singles( $product_id = 0 ) {

	// Bail without the product ID.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Get some recent reviews.
	$recent_reviews = Queries\get_recent_reviews_for_product( $product_id );

	// Bail without any reviews.
	if ( empty( $recent_reviews ) ) {
		return false;
	}

	// Set my empty array to return.
	$grouped_args   = array();

	// Grab my first review.
	$first_review   = array_shift( $recent_reviews );

	// Pull out the data pieces we want.
	$first_rv_data  = Utilities\format_review_data_for_schema( $first_review );

	// Add the data if we have it.
	if ( ! empty( $first_rv_data ) ) {

		// Now add my single review data.
		$grouped_args['review'] = array(
			'@context'      => 'http://schema.org/',
			'@type'         => 'Review',
			'name'          => $first_rv_data['title'],
			'reviewBody'    => $first_rv_data['content'],
			'datePublished' => $first_rv_data['date'],
			'reviewRating'  => array(
				'@type'       => 'Rating',
				'ratingValue' => $first_rv_data['score'],
				'bestRating'  => '7',
				'worstRating' => '1',
			),
			'author'       => array(
				'@type' => 'Person',
				'name'  => $first_rv_data['author'],
			),
		);

		// Done with the first review dataset.
	}

	// Now loop each remaining review and pull out what we want.
	foreach ( $recent_reviews as $single_review ) {

		// Pull out the data pieces we want.
		$single_rv_data = Utilities\format_review_data_for_schema( $single_review );

		// Skip if we have no data.
		if ( empty( $single_rv_data ) ) {
			continue;
		}

		// Set the args for a single review.
		$single_args[]  = array(
			'@context'      => 'http://schema.org/',
			'@type'         => 'Review',
			'name'          => $single_rv_data['title'],
			'reviewBody'    => $single_rv_data['content'],
			'datePublished' => $single_rv_data['date'],
			'reviewRating'  => array(
				'@type'       => 'Rating',
				'ratingValue' => $single_rv_data['score'],
				'bestRating'  => '7',
				'worstRating' => '1',
			),
			'author'       => array(
				'@type' => 'Person',
				'name'  => $single_rv_data['author'],
			),
		);
	}

	// Now add my data.
	if ( ! empty( $single_args ) ) {
		$grouped_args['reviews']  = $single_args;
	}

	// Return the data, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'schema_singles_data_setup', $grouped_args, $product_id );
}
