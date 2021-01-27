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
	$maybe_cmns = Queries\get_reviews_for_admin();

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

		//preprint( $review_object, true );

		// Pull out the product and author ID.
		$product_id     = ! empty( $review_object->product_id ) ? absint( $review_object->product_id ) : 0;
		$author_id      = ! empty( $review_object->author_id ) ? absint( $review_object->author_id ) : 0;

		// If we don't have a product ID or it's invalid, bail.
		if ( empty( $product_id ) || 'product' !== get_post_type( $product_id ) ) {
			return new WP_Error( 'invalid-product-id', __( 'The product ID provided was empty or invalid.', 'woo-better-reviews' ) );
		}

		// Set my original comment ID.
		$original_id    = absint( $review_object->review_id );

		// Figure out the recaluclated score.
		$rebased_score  = rebase_custom_review_score( $original_id, $review_object );

		// Bail if we don't have a score to use.
		if ( empty( $rebased_score ) || is_wp_error( $rebased_score ) ) {
			return new WP_Error( 'no-rebased-score', __( 'The new review score could not be calculated properly.', 'woo-better-reviews' ) );
		}

		// Get my formatted content.
		$content_format = format_custom_review_data( $review_object, $rebased_score, $product_id, $author_id );

		// Bail if we don't have content to use.
		if ( empty( $content_format ) || is_wp_error( $content_format ) ) {
			return new WP_Error( 'missing-formatting-data', __( 'The required formatted data could not be generated.', 'woo-better-reviews' ) );
		}

		// Attempt to insert the review content and get a review ID back.
		$attempt_insert = wp_insert_comment( $content_format );

		// Bail on a failed insert.
		if ( empty( $attempt_insert ) || is_wp_error( $attempt_insert ) ) {
			return new WP_Error( 'content-insert-fail', __( 'The formatted review content could not be inserted into the database.', 'woo-better-reviews' ) );
		}

		// Include the product ID in the array.
		$pids[] = $product_id;

		// Increment the counter.
		$ccount++;
	}

	// Set and sanitize my product ID array.
	$product_id_array   = array_unique( $pids );

	// Handle our functions related to the product ID array.
	update_products_post_conversion( $product_id_array );

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
 * Take our custom 7 point scoring and convert to 5.
 *
 * @param  integer $original_id    My original comment ID.
 * @param  object  $review_object  The entire review object.
 *
 * @return integer
 */
function rebase_custom_review_score( $original_id = 0, $review_object ) {

	// Bail without the ID.
	if ( empty( $original_id ) ) {
		return;
	}

	// Allow for a different calculation method.
	$custom_score   = apply_filters( Core\HOOK_PREFIX . 'custom_rebase_custom_review_score', null, $original_id, $review_object );

	// If we have a custom score, return that.
	if ( ! empty( $custom_score ) ) {
		return $custom_score;
	}

	// Check for a rating.
	$original_score = $review_object->rating_total_score;

	// For now, set a 3 if we don't have it.
	if ( empty( $original_score ) ) {
		return apply_filters( Core\HOOK_PREFIX . 'converted_custom_review_default_rating_score', 3, $original_id, $review_object );
	}

	// Now we do the maths.
	$raw_ratio_calc = ( $original_score / 7 ) * 5;

	// Round it.
	$score_rounded  = round( $raw_ratio_calc, 0 );

	// Confirm we have a valid score and it isn't zero.
	$score_validate = absint( $score_rounded ) < 1 ? 1 : absint( $score_rounded );

	// Return our filtered validated score.
	return apply_filters( Core\HOOK_PREFIX . 'rebase_converted_custom_review_score', $score_validate, $original_id, $review_object );
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
function format_custom_review_data( $review_object, $rebased_score = 0, $product_id = 0, $author_id = 0 ) {

	// Bail with missing items.
	if ( empty( $review_object ) || ! is_object( $review_object ) || empty( $rebased_score ) || empty( $product_id ) ) {
		return false;
	}

	// Set the timestamp and review name / slugs.
	$set_timestamp  = strtotime( $review_object->review_date );
	$set_date_local = date( 'Y-m-d H:i:s', $set_timestamp );
	$set_date_gmt   = gmdate( 'Y-m-d H:i:s', $set_timestamp );

	// Pull out the author name and email.
	$author_name    = ! empty( $review_object->author_name ) ? $review_object->author_name : __( 'Review Author', 'woo-better-reviews' );
	$author_email   = ! empty( $review_object->author_email ) ? $review_object->author_email : '';

	// Check the verification.
	$maybe_verified = ! empty( $review_object->is_verified ) ? 1 : 0;

	// Build out the content data array.
	$content_setup  = array(
		'comment_approved'     => 1,
		'comment_type'         => 'review',
		'comment_post_ID'      => absint( $product_id ),
		'comment_author'       => sanitize_text_field( $author_name ),
		'comment_author_email' => sanitize_email( $author_email ),
		'comment_date'         => $set_date_local,
		'comment_date_gmt'     => $set_date_gmt,
		'comment_content'      => $review_object->review_content,
		'user_id'              => $author_id,
	);

	// Go ahead and de-slash the content.
	$content_setup  = array_map( 'stripslashes_deep', $content_setup );

	// And add the meta content.
	$content_setup['comment_meta'] = array(
		'rating'    => $rebased_score,
		'verified'  => $maybe_verified,
		'_from_wbr' => 1,
	);

	// Return our setup for inserting.
	return apply_filters( Core\HOOK_PREFIX . 'format_custom_review_data', $content_setup, $review_object, $rebased_score, $product_id, $author_id );
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

		// Recalculate the total score on each.
		Utilities\calculate_total_review_scoring( $product_id );
	}

	// Include an action for all the product IDs.
	do_action( Core\HOOK_PREFIX . 'after_update_products_post_conversion', $product_ids );
}
