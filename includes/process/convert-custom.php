<?php
/**
 * Handle the custom conversion process.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\ConvertCustom;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Utilities as Utilities;
use Nexcess\WooBetterReviews\Queries as Queries;
use Nexcess\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

/**
 * Convert our reviews back to the native WooCommerce comment-based system.
 *
 * @return mixed
 */
function attempt_custom_review_conversion() {

	// Go and fetch my existing reviews.
	$maybe_cmns = Queries\get_existing_woo_reviews();

	// Bail with a 'no-reviews' string to look for later.
	if ( empty( $maybe_cmns ) ) {
		return 'no-reviews';
	}

	// Attempt to install the tables as a fallback.
	$add_tables = Database\maybe_install_tables();

	// Bail if the tables couldn't be made.
	if ( ! $add_tables ) {
		return new WP_Error( 'missing-required-tables', __( 'The required database tables for the plugin are missing. Please deactivate and reactivate the plugin.', 'woo-better-reviews' ) );
	}

	// Set an empty array for the product IDs.
	$pids   = array();

	// Set a converted counter.
	$ccount = 0;

	// Loop and dig into the individual review (comment) objects.
	foreach ( $maybe_cmns as $review_object ) {

		// Pull out the product and author ID.
		$product_id     = ! empty( $review_object->comment_post_ID ) ? absint( $review_object->comment_post_ID ) : 0;
		$author_id      = ! empty( $review_object->user_id ) ? $review_object->user_id : 0;

		// If we don't have a product ID or it's invalid, bail.
		if ( empty( $product_id ) || 'product' !== get_post_type( $product_id ) ) {
			return new WP_Error( 'invalid-product-id', __( 'The product ID provided was empty or invalid.', 'woo-better-reviews' ) );
		}

		// Set my original comment ID.
		$original_id    = absint( $review_object->comment_ID );

		// Figure out the recaluclated score.
		$rebased_score  = rebase_existing_review_score( $original_id, $review_object );

		// Bail if we don't have a score to use.
		if ( empty( $rebased_score ) || is_wp_error( $rebased_score ) ) {
			return new WP_Error( 'no-rebased-score', __( 'The new review score could not be calculated properly.', 'woo-better-reviews' ) );
		}

		// Get my formatted content.
		$content_format = format_existing_review_data( $review_object, $rebased_score, $product_id, $author_id );

		// Bail if we don't have content to use.
		if ( empty( $content_format ) || is_wp_error( $content_format ) ) {
			return new WP_Error( 'missing-formatting-data', __( 'The required formatted data could not be generated.', 'woo-better-reviews' ) );
		}

		// Attempt to insert the review content and get a review ID back.
		$attempt_insert = Database\insert( 'content', $content_format );

		// Bail on a failed insert.
		if ( empty( $attempt_insert ) || is_wp_error( $attempt_insert ) ) {
			return new WP_Error( 'content-insert-fail', __( 'The formatted review content could not be inserted into the database.', 'woo-better-reviews' ) );
		}

		// Set and sanitize the new review ID.
		$new_review_id  = absint( $attempt_insert );

		// Format my scoring.
		$scoring_format = format_converted_scoring_data( $new_review_id, $rebased_score, $product_id, $author_id );

		// Bail without my scoring formatted.
		if ( empty( $scoring_format ) || is_wp_error( $scoring_format ) ) {
			return new WP_Error( 'invalid-scoring-formatting', __( 'The required scoring data could not be generated.', 'woo-better-reviews' ) );
		}

		// Now loop and attempt each insert.
		foreach ( $scoring_format as $single_scoring_array ) {

			// Attempt to insert the review scoring and get a review ID back.
			$insert_scoring = Database\insert( 'ratings', $single_scoring_array );

			// Bail on a failed insert.
			if ( empty( $insert_scoring ) || is_wp_error( $insert_scoring ) ) {
				return new WP_Error( 'scoring-insert-fail', __( 'The formatted review scoring could not be inserted into the database.', 'woo-better-reviews' ) );
			}

			// Scoring inserts are done.
		}

		// Now pull my attribute data out to add back into the review.
		$attribute_args = parse_converted_attributes_for_scoring( $scoring_format );

		// Only proceed with the attribute data if we have some.
		if ( ! empty( $attribute_args ) && ! is_wp_error( $attribute_args ) ) {

			// Attempt to update the review with the scoring data.
			$maybe_update   = Database\update( 'content', absint( $new_review_id ), array( 'rating_attributes' => $attribute_args ) );

			// Bail on a failed insert.
			if ( empty( $maybe_update ) || is_wp_error( $maybe_update ) ) {
				return new WP_Error( 'scoring-update-fail', __( 'The formatted review scoring could not be updated in the database.', 'woo-better-reviews' ) );
			}
		}

		// Include the product ID in the array.
		$pids[] = $product_id;

		// Store the legacy review IDs in the product postmeta.
		process_legacy_review_ids( $original_id, $product_id );

		// Increment the counter.
		$ccount++;
	}

	// Set and sanitize my product ID array.
	$product_id_array   = array_unique( $pids );

	// Handle our functions related to the product ID array.
	update_products_post_conversion( $product_id_array, $convert_type, $purge_existing );

	// Now delete my big list of review data cached.
	Utilities\purge_transients( null, 'reviews' );

	// Update the option that we have run the conversion.
	update_option( Core\OPTION_PREFIX . 'imported_woo_reviews', current_time( 'timestamp', false ) );

	// Set up the return message.
	$return_msg = sprintf( _n( '%d review converted.', '%d reviews converted.', absint( $ccount ), 'woo-better-reviews' ), absint( $ccount ) );

	// Return an array with a success flag included.
	return array(
		'success'     => 1,
		'message'     => $return_msg,
		'count'       => $ccount,
		'product-ids' => $product_id_array,
	);
}
