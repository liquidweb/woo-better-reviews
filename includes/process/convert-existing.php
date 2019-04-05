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
use LiquidWeb\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;


/**
 * Take the entire review (comment) object and get it into our format.
 *
 * @param  object  $review_object  The entire review object.
 * @param  ingeger $original_id    The original comment ID.
 *
 * @return array
 */
function format_existing_review_data( $review_object, $original_id = 0 ) {

	// Bail with missing items.
	if ( empty( $review_object ) || ! is_object( $review_object ) || empty( $original_id ) ) {
		return false;
	}

	// Set the timestamp and date formatting that we're gonna use.
	$set_timestamp  = strtotime( $review_object->comment_date );
	$review_title   = sprintf( __( 'Review on %s', 'woo-better-reviews' ), date( 'F jS, Y at g:i a', $set_timestamp ) );

	// Set some variables based on what was passed.
	$author_name    = ! empty( $review_object->comment_author ) ? $review_object->comment_author : '';
	$author_email   = ! empty( $review_object->comment_author_email ) ? $review_object->comment_author_email : '';
	$author_wp_id   = ! empty( $review_object->user_id ) ? $review_object->user_id : 0;

	// Check the verification.
	$maybe_verified = get_comment_meta( $original_id, 'verified', true );

	// Figure out the recaluclated score.
	$recalced_score = rebase_existing_review_score( $original_id );

	// preprint( $recalced_score, true );

	// Get all my attribute arguments.
	$attribute_args = rkv_testing_setup_product_attributes();

	// preprint( $attribute_args, true );

	// Set up the insert data array.
	$insert_setup   = array(
		'author_id'          => absint( $author_wp_id ),
		'author_name'        => sanitize_text_field( $author_name ),
		'author_email'       => sanitize_email( $author_email ),
		'product_id'         => absint( $review_object->comment_post_ID ),
		'review_date'        => date( 'Y-m-d H:i:s', $set_timestamp ),
		'review_title'       => sanitize_text_field( $review_title ),
		'review_slug'        => sanitize_title_with_dashes( $review_title, null, 'save' ),
		'review_content'     => wp_kses_post( $review_object->comment_content ),
		'review_status'      => 'approved',
		'is_verified'        => $maybe_verified,
		'rating_total_score' => $recalced_score,
		'rating_attributes'  => $attribute_args,
		'author_charstcs'    => '',
	);

	preprint( $insert_setup );
}

/**
 * Take our existing 5 point scoring and convert to 7.
 *
 * @param  integer $original_id  My original comment ID.
 *
 * @return integer
 */
function rebase_existing_review_score( $original_id = 0 ) {

	// Bail without the ID.
	if ( empty( $original_id ) ) {
		return;
	}

	// Check for a rating.
	$original_score = get_comment_meta( $original_id, 'rating', true );

	// For now, return 4 if we don't have it.
	if ( empty( $original_score ) ) {
		return 4;
	}

	// Now we do the maths.
	$raw_ratio_calc = ( $original_score / 5 ) * 7;

	// Round it.
	$review_round   = round( $raw_ratio_calc, 0 );

	// Make sure the average is not zero.
	return absint( $review_round ) < 1 ? 1 : absint( $review_round );
}

/**
 * Get all the attribute stuff.
 *
 * @param  integer $product_id  The product ID we are gonna get the attributes for.
 *
 * @return integer
 */
function get_formatted_attribute_data( $product_id = 0, $format = 'content' ) {

	// Get all my attribute arguments.
	$attribute_data = get_option( 'rkv_test_attribs', '' );

	// Pull out my IDs.
	$attribute_list = wp_list_pluck( $attribute_data, 'id' );

	// Set my empty.
	$scoring_array  = array();

	// Add the first score if we have it.
	if ( ! empty( $score ) && 'scoring' === sanitize_text_field( $format ) ) {
		$scoring_array[0] = absint( $score );
	}

	// Set up my array with the static 4.
	foreach ( $attribute_list as $attribute_id ) {
		$scoring_array[ $attribute_id  ] = 4;
	}

	// Return the entire scoring array.
	return $scoring_array;

}
