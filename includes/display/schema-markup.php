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
	$confirm_enable = confirm_schema_before_insert( $product->get_id() );

	// Bail if we aren't enabled.
	if ( ! $confirm_enable ) {
		return;
	}

	// Pull out the averages and total review count.
	$average_score  = get_post_meta( $product->get_id(), Core\META_PREFIX . 'average_rating', true );
	$review_count   = Helpers\get_admin_review_count( $product->get_id(), false );

	// Set an empty array.
	$single_args    = array();

	// If we have both, add our stuff.
	if ( ! empty( $average_score ) && ! empty( $review_count ) ) {

		// Unset the existing aggregate schema data.
		unset( $markup['aggregateRating'] );

		// Now add in our aggregate data.
		$markup['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => esc_attr( $average_score ),
			'bestRating'  => '7',
			'worstRating' => '1',
			'ratingCount' => esc_attr( $review_count ),
		);

		// Nothing left to do inside the aggregate setup.
	}

	// Get some recent reviews.
	$recent_reviews = Queries\get_recent_reviews_for_product( $product->get_id() );

	// Add the reviews if we have any.
	if ( ! empty( $recent_reviews ) ) {

		// Unset the existing schema data.
		unset( $markup['reviews'] );
		unset( $markup['review'] );

		// First grab my first review.
		$first_review   = $recent_reviews[0];

		// Trim down the review.
		$review_trimmed = wp_trim_words( $first_review->review_content, 20, '...' );

		// Now add my single review data.
		$markup['review']  = array(
			'@context'      => 'http://schema.org/',
			'@type'         => 'Review',
			'name'          => $first_review->review_title,
			'reviewBody'    => wp_strip_all_tags( $review_trimmed, true ),
			'datePublished' => date( 'Y-m-d', strtotime( $first_review->review_date ) ),
			'reviewRating'  => array(
				'@type'       => 'Rating',
				'ratingValue' => $first_review->rating_total_score,
				'bestRating'  => '7',
				'worstRating' => '1',
			),
			'author'       => array(
				'@type' => 'Person',
				'name'  => $first_review->author_name,
			),
		);

		// Now loop each review and pull out what we want.
		foreach ( $recent_reviews as $single_review ) {

			// Trim down the review.
			$single_trimmed = wp_trim_words( $single_review->review_content, 20, '...' );

			// Set the args for a single review.
			$single_args[]  = array(
				'@context'      => 'http://schema.org/',
				'@type'         => 'Review',
				'name'          => $single_review->review_title,
				'reviewBody'    => wp_strip_all_tags( $single_trimmed, true ),
				'datePublished' => date( 'Y-m-d', strtotime( $single_review->review_date ) ),
				'reviewRating'  => array(
					'@type'       => 'Rating',
					'ratingValue' => $single_review->rating_total_score,
					'bestRating'  => '7',
					'worstRating' => '1',
				),
				'author'       => array(
					'@type' => 'Person',
					'name'  => $single_review->author_name,
				),
			);
		}

		// Now add my data.
		$markup['reviews']  = $single_args;

		// Nothing left for single reviews.
	}

	// Send back the markup if we have it.
	return $markup;
}

/**
 * Confirm we should be doing the schema before.
 *
 * @param  integer $product_id  The individual product ID.
 *
 * @return boolean
 */
function confirm_schema_before_insert( $product_id = 0 ) {

	// Bail if we aren't on a product.
	if ( empty( $product_id ) || 'product' !== get_post_type( $product_id ) || ! comments_open( $product_id ) ) {
		return false;
	}

	// Run the check.
	$maybe_enabled  = Helpers\maybe_schema_enabled( $product_id );

	// Return our result.
	return false !== $maybe_enabled ? true : false;
}
