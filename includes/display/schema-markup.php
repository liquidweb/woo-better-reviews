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

	// Pull out the averages and total review count.
	$average_score  = get_post_meta( $product->get_id(), Core\META_PREFIX . 'average_rating', true );
	$review_count   = Helpers\get_admin_review_count( $product->get_id(), false );

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

		// Grab my first review.
		$first_review   = array_shift( $recent_reviews );

		// Pull out the data pieces we want.
		$first_rv_data  = Utilities\format_review_data_for_schema( $first_review );

		// Add the data if we have it.
		if ( ! empty( $first_rv_data ) ) {

			// Now add my single review data.
			$markup['review'] = array(
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
			$markup['reviews']  = $single_args;
		}

		// Nothing left for single reviews.
	}

	// Send back the markup if we have it.
	return $markup;
}
