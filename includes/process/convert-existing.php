<?php
/**
 * Handle the conversion process.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display\ConvertExisting;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;
use LiquidWeb\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

/**
 * Convert the existing comment-based reviews to our new ones.
 *
 * @return mixed
 */
function process_existing_review_conversion() {

	// Set my lookup args.
	$setup_args = array(
		'status'    => 'approve',
		'post_type' => 'product',
		'orderby'   => 'comment_post_ID',
	);

	// Now fetch my reviews.
	$maybe_cmns = get_comments( $setup_args );
	// preprint( $maybe_cmns, true );

	// Bail and fail without.
	if ( empty( $maybe_cmns ) ) {
		die( 'No reviews exist' );
	}

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

		// Bail if I couldn't parse the attribute data.
		if ( empty( $attribute_args ) || is_wp_error( $attribute_args ) ) {
			return new WP_Error( 'invalid-attribute-formatting', __( 'The required product attribute data could not be generated.', 'woo-better-reviews' ) );
		}

		// Attempt to update the review with the scoring data.
		$maybe_update   = Database\update( 'content', absint( $new_review_id ), array( 'rating_attributes' => $attribute_args ) );

		// Bail on a failed insert.
		if ( empty( $maybe_update ) || is_wp_error( $maybe_update ) ) {
			return new WP_Error( 'scoring-update-fail', __( 'The formatted review scoring could not be updated in the database.', 'woo-better-reviews' ) );
		}

		// Handle purging product and author related transients.
		Utilities\purge_transients( null, 'products', array( 'ids' => (array) $product_id ) );
		Utilities\purge_transients( null, 'authors', array( 'ids' => (array) $product_id ) );

		// Store the legacy review IDs in the product postmeta.
		store_legacy_review_ids( $original_id, $product_id );

		// Increment the counter.
		$ccount++;
	}

	// Now delete my big list of review data cached.
	Utilities\purge_transients( null, 'reviews' );

	// Set up the return message.
	$return_msg = sprintf( _n( '%d review converted.', '%d reviews converted.', absint( $ccount ), 'woo-better-reviews' ), absint( $ccount ) );

	die( $return_msg ); // @@todo what are we gonna do here?
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
	if ( isset( $custom_score ) ) {
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
	$review_slug    = sanitize_title_with_dashes( $review_title, null, 'save' ) . '-' . time();

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
		'rating_attributes'  => '', // This will get inserted later.
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

	// Get all my attribute arguments.
	// @@ todo replace with actual query.
	// $attribute_data = get_option( 'rkv_test_attribs', '' );
	$attribute_data = Helpers\get_product_attributes_for_conversion( $product_id );
	// preprint( $attribute_data, true );

	// Bail without the data.
	if ( empty( $attribute_data ) ) {
		return;
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
		$setup_args[ $array_key ] = $array_val;

		// This finishes out the scoring data.
	}

	// Return the args, serialized.
	return maybe_serialize( $setup_args );
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

	// Set a postmeta flag to indicate we have converted reviews.
	update_post_meta( $product_id, Core\META_PREFIX . 'has_converted_reviews', true );

	// Get my existing items.
	$existing_ids   = get_post_meta( $product_id, Core\META_PREFIX . 'legacy_review_ids', true );

	// Check for the IDs and merge, or create a new array.
	$updated_ids    = ! empty( $existing_ids ) ? wp_parse_args( (array) $original_id, $existing_ids ) : (array) $original_id;

	// Make sure we're unique with the IDs.
	$set_stored_ids = array_unique( $updated_ids );

	// Update the array.
	update_post_meta( $product_id, Core\META_PREFIX . 'legacy_review_ids', $set_stored_ids );
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

	// Check for the IDs and merge, or create a new array.
	$updated_ids    = ! empty( $existing_ids ) ? wp_parse_args( (array) $original_id, $existing_ids ) : (array) $original_id;

	// Make sure we're unique with the IDs.
	$set_stored_ids = array_unique( $updated_ids );

	// Update the array.
	update_post_meta( $product_id, Core\META_PREFIX . 'legacy_review_ids', $set_stored_ids );
}
