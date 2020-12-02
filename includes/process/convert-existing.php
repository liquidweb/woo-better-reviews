<?php
/**
 * Handle the conversion process.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\ConvertExisting;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Utilities as Utilities;
use Nexcess\WooBetterReviews\Queries as Queries;
use Nexcess\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

/**
 * Convert the existing WooCommerce comment-based reviews to our new ones.
 *
 * @param  boolean $convert_type    Whether to convert the comment type of the existing reviews.
 * @param  boolean $purge_existing  Whether to actually purge the existing reviews once they have been converted.
 *
 * @return mixed
 */
function attempt_existing_woo_review_conversion( $convert_type = true, $purge_existing = false ) {

	// If both the convert AND purge flags are set to "false", error out.
	if ( false === $convert_type && false === $purge_existing ) {
		return new WP_Error( 'missing-conversion-args', __( 'The existing reviews must either be converted or purged. Please select one.', 'woo-better-reviews' ) );
	}

	// If both the convert AND purge flags are set to "true", error out.
	if ( false !== $convert_type && false !== $purge_existing ) {
		return new WP_Error( 'invalid-conversion-args', __( 'The existing reviews can either be converted or purged, not both. Please choose one.', 'woo-better-reviews' ) );
	}

	// Go and fetch my existing reviews.
	$maybe_cmns = Queries\get_existing_woo_reviews();

	// Bail with a 'no-reviews' string to look for later.
	if ( empty( $maybe_cmns ) ) {
		return 'no-reviews';
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
		store_legacy_review_ids( $original_id, $product_id );

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
	update_option( Core\OPTION_PREFIX . 'converted_woo_reviews', current_time( 'timestamp', false ) );

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

/**
 * Take our existing 5 point scoring and convert to 7.
 *
 * @param  integer $original_id    My original comment ID.
 * @param  object  $review_object  The entire review object.
 *
 * @return integer
 */
function rebase_existing_review_score( $original_id = 0, $review_object ) {

	// Bail without the ID.
	if ( empty( $original_id ) ) {
		return;
	}

	// Allow for a different calculation method.
	$custom_score   = apply_filters( Core\HOOK_PREFIX . 'custom_rebase_existing_review_score', null, $original_id, $review_object );

	// If we have a custom score, return that.
	if ( ! empty( $custom_score ) ) {
		return $custom_score;
	}

	// Check for a rating.
	$original_score = get_comment_meta( $original_id, 'rating', true );

	// For now, set a 4 (middle of 7) if we don't have it.
	if ( empty( $original_score ) ) {
		return apply_filters( Core\HOOK_PREFIX . 'converted_review_default_rating_score', 4, $original_id, $review_object );
	}

	// Now we do the maths.
	$raw_ratio_calc = ( $original_score / 5 ) * 7;

	// Round it.
	$score_rounded  = round( $raw_ratio_calc, 0 );

	// Confirm we have a valid score and it isn't zero.
	$score_validate = absint( $score_rounded ) < 1 ? 1 : absint( $score_rounded );

	// Return our filtered validated score.
	return apply_filters( Core\HOOK_PREFIX . 'rebase_existing_review_score', $score_validate, $original_id, $review_object );
}

/**
 * Take the entire review (comment) object and get it into our format.
 *
 * @param  object  $review_object  The entire review object.
 * @param  integer $rebased_score  Our new rating score.
 * @param  integer $product_id     The ID of the product we're adding the review to.
 * @param  integer $author_id      The potential user ID of the reviewer.
 *
 * @return array
 */
function format_existing_review_data( $review_object, $rebased_score = 0, $product_id = 0, $author_id = 0 ) {

	// Bail with missing items.
	if ( empty( $review_object ) || ! is_object( $review_object ) || empty( $rebased_score ) || empty( $product_id ) ) {
		return false;
	}

	// Set the timestamp and review name / slugs.
	$set_timestamp  = strtotime( $review_object->comment_date );
	$review_title   = sprintf( __( 'Product review for "%s"', 'woo-better-reviews' ), get_the_title( $product_id ) );
	$review_slug    = 'wbr-legacy-review-' . absint( $review_object->comment_ID );

	// Pull out the author name and email.
	$author_name    = ! empty( $review_object->comment_author ) ? $review_object->comment_author : __( 'Review Author', 'woo-better-reviews' );
	$author_email   = ! empty( $review_object->comment_author_email ) ? $review_object->comment_author_email : '';

	// Check the verification.
	$maybe_verified = get_comment_meta( $review_object->comment_ID, 'verified', true );

	// Build out the content data array.
	$content_setup  = array(
		'author_id'          => absint( $author_id ),
		'author_name'        => sanitize_text_field( $author_name ),
		'author_email'       => sanitize_email( $author_email ),
		'product_id'         => absint( $product_id ),
		'review_date'        => date( 'Y-m-d H:i:s', $set_timestamp ),
		'review_title'       => sanitize_text_field( $review_title ),
		'review_slug'        => sanitize_text_field( $review_slug ),
		'review_content'     => wp_kses_post( $review_object->comment_content ),
		'review_status'      => 'approved',
		'is_verified'        => $maybe_verified,
		'rating_total_score' => $rebased_score,
		'rating_attributes'  => '', // This has to be empty, since we never collected it.
		'author_charstcs'    => '', // This has to be empty, since we never collected it.
	);

	// Go ahead and de-slash the content.
	$content_setup  = array_map( 'stripslashes_deep', $content_setup );

	// Return our setup for inserting.
	return apply_filters( Core\HOOK_PREFIX . 'format_existing_review_data', $content_setup, $review_object, $rebased_score, $product_id, $author_id );
}

/**
 * Handle all the scoring data for the reviews.
 *
 * @param  integer $review_id      The ID of our new review.
 * @param  integer $rebased_score  Our new rating score.
 * @param  integer $product_id     The ID of the product we're adding the review to.
 * @param  integer $author_id      The potential user ID of the reviewer.
 *
 * @return array
 */
function format_converted_scoring_data( $review_id = 0, $rebased_score = 0, $product_id = 0, $author_id = 0 ) {

	// Bail without requirements.
	if ( empty( $review_id ) || empty( $rebased_score ) || empty( $product_id ) ) {
		return false;
	}

	// Set up the first insert data array for the
	// total, which has an attribute ID of zero.
	$scores_setup[] = array(
		'review_id'    => $review_id,
		'author_id'    => $author_id,
		'product_id'   => $product_id,
		'attribute_id' => 0,
		'rating_score' => $rebased_score,
	);

	// Get all my attribute arguments.
	$attribute_data = Helpers\get_product_attributes_for_conversion( $product_id );

	// If we have some (which we should not) use it.
	if ( ! empty( $attribute_data ) ) {

		// Now loop through the attributes.
		foreach ( $attribute_data as $attribute_id ) {

			// Add to the array using this attribute.
			$scores_setup[] = array(
				'review_id'    => $review_id,
				'author_id'    => $author_id,
				'product_id'   => $product_id,
				'attribute_id' => $attribute_id,
				'rating_score' => apply_filters( Core\HOOK_PREFIX . 'converted_review_default_attribute_score', 4 ),
			);

			// Nothing remains for this.
		}
	}

	// Return the entire scoring array, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'format_converted_scoring_data', $scores_setup, $review_id, $product_id, $rebased_score, $product_id, $author_id );
}

/**
 * Take the scoring attributes data and roll it into our content table.
 *
 * @param  array  $scoring_array  Our scoring data.
 *
 * @return array
 */
function parse_converted_attributes_for_scoring( $scoring_array = array() ) {

	// Bail without the array of data.
	if ( empty( $scoring_array ) ) {
		return;
	}

	// Set a blank item.
	$setup_args = array();

	// Now loop the scoring array and add to the rolled up content.
	foreach ( $scoring_array as $single_score ) {

		// Check for the zero attribute ID, which is our total.
		if ( empty( $single_score['attribute_id'] ) ) {
			continue;
		}

		// Set up my two column keys.
		$array_key  = $single_score['attribute_id'];
		$array_val  = $single_score['rating_score'];

		// Handle our two dynamic key names.
		$setup_args[ $array_key ] = (string) $array_val;

		// This finishes out the scoring data.
	}

	// Return the args, serialized.
	return ! empty( $setup_args ) ? maybe_serialize( $setup_args ) : array();
}

/**
 * Take the original comment ID and store it in an array.
 *
 * @param  integer $original_id  The original ID of the comment.
 * @param  integer $product_id   The product ID being related to.
 *
 * @return void
 */
function store_legacy_review_ids( $original_id = 0, $product_id = 0 ) {

	// Bail if parts are missing.
	if ( empty( $original_id ) || empty( $product_id ) ) {
		return;
	}

	// Get my existing items.
	$existing_ids   = get_post_meta( $product_id, Core\META_PREFIX . 'legacy_review_ids', true );

	// If we have none, make a new array and set the flag.
	if ( empty( $existing_ids ) ) {

		// Set a postmeta flag to indicate we have converted reviews.
		update_post_meta( $product_id, Core\META_PREFIX . 'has_legacy_reviews', true );

		// And set my postmeta IDs.
		update_post_meta( $product_id, Core\META_PREFIX . 'legacy_review_ids', (array) $original_id );

		// And be done.
		return;
	}

	// Check for the IDs and merge, or create a new array.
	$updated_ids    = wp_parse_args( (array) $original_id, $existing_ids );

	// Make sure we're unique with the IDs.
	$set_stored_ids = array_unique( $updated_ids );

	// Update the array.
	update_post_meta( $product_id, Core\META_PREFIX . 'legacy_review_ids', $set_stored_ids );
}

/**
 * Run all the various cleanup and recalculating functions on products.
 *
 * @param  array   $product_ids     All the product IDs.
 * @param  boolean $convert_type    Whether to convert the comment type.
 * @param  boolean $purge_existing  Whether to actually purge the existing.
 *
 * @return void
 */
function update_products_post_conversion( $product_ids = array(), $convert_type = true, $purge_existing = false ) {

	// Bail if we don't have product IDs.
	if ( empty( $product_ids ) ) {
		return false;
	}

	// Handle purging product and author related transients.
	Utilities\purge_transients( null, 'products', array( 'ids' => (array) $product_ids ) );
	Utilities\purge_transients( null, 'authors', array( 'ids' => (array) $product_ids ) );

	// Update all my counts.
	Utilities\update_product_review_count( $product_ids );

	// Loop the product IDs and run our functions.
	foreach ( $product_ids as $product_id ) {

		// If we don't have the correct product type, skip.
		if ( 'product' !== get_post_type( $product_id ) ) {
			continue;
		}

		// Convert the existing comment review IDs.
		if ( false !== $convert_type ) {
			convert_legacy_review_ids( $product_id );
		}

		// Purge the existing review IDs.
		if ( false !== $purge_existing ) {
			purge_legacy_review_ids( $product_id );
		}

		// Recalculate the total score on each.
		Utilities\calculate_total_review_scoring( $product_id );
	}

	// Include an action for all the product IDs.
	do_action( Core\HOOK_PREFIX . 'after_update_products_post_conversion', $product_ids );
}

/**
 * Get all the stored legacy IDs and update the comment type to "hide" them.
 *
 * @param  integer $product_id   The product ID being related to.
 *
 * @return void
 */
function convert_legacy_review_ids( $product_id = 0 ) {

	// Bail if parts are missing.
	if ( empty( $product_id ) ) {
		return;
	}

	// Get my existing items.
	$existing_ids   = get_post_meta( $product_id, Core\META_PREFIX . 'legacy_review_ids', true );

	// Bail if no legacy IDs exist.
	if ( empty( $existing_ids ) ) {
		return;
	}

	// Set an update count.
	$update = 0;

	// Loop and set our update args.
	foreach ( $existing_ids as $existing_id ) {

		// Include a 'before' hook.
		do_action( Core\HOOK_PREFIX . 'before_legacy_review_converted', $existing_id );

		// Set the individual update args. This odd
		// approval flag hides them without deleting.
		$setup_args = array(
			'comment_ID'       => absint( $existing_id ),
			'comment_approved' => 'converted',
			'comment_type'     => 'legacy-review',
		);

		// Filter the args.
		$setup_args = apply_filters( Core\HOOK_PREFIX . 'convert_legacy_review_args', $setup_args, $existing_id );

		// Run the actual comment update.
		$run_update = wp_update_comment( $setup_args );

		// Increment the count if we had success.
		if ( false !== $run_update ) {
			$update++;
		}

		// Include an 'after' hook.
		do_action( Core\HOOK_PREFIX . 'after_legacy_review_converted', $existing_id );

		// @@todo maybe do some error checking here.
	}

	// Return the total updated count.
	return $update;
}

/**
 * Get all the stored legacy IDs and purge the original comment.
 *
 * @param  integer $product_id   The product ID being related to.
 *
 * @return void
 */
function purge_legacy_review_ids( $product_id = 0 ) {

	// Bail if parts are missing.
	if ( empty( $product_id ) ) {
		return;
	}

	// Get my existing items.
	$existing_ids   = get_post_meta( $product_id, Core\META_PREFIX . 'legacy_review_ids', true );

	// Bail if no legacy IDs exist.
	if ( empty( $existing_ids ) ) {
		return;
	}

	// Set a delete count.
	$delete = 0;

	// Loop and set our update args.
	foreach ( $existing_ids as $existing_id ) {

		// Include a 'before' hook.
		do_action( Core\HOOK_PREFIX . 'before_legacy_review_purge', $existing_id );

		// Run the actual comment delete.
		$run_update = wp_delete_comment( $existing_id, true );

		// Increment the count if we had success.
		if ( false !== $run_update ) {
			$delete++;
		}

		// Include an 'after' hook.
		do_action( Core\HOOK_PREFIX . 'after_legacy_review_purge', $existing_id );

		// @@todo maybe do some error checking here.
	}

	// Return the total deleted count.
	return $delete;
}
