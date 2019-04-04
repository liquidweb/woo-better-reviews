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

function rkv_test_review_conversions() {

	// Check the key.
	if ( empty( $_GET['cnv-rvs'] ) ) {
		return;
	}

	// Set my lookup args.
	$setup_args = array(
		'status'    => 'approved',
		'type'      => 'comment',
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

	// Loop and dig into the individual comment objects.
	foreach ( $maybe_cmns as $comment_object ) {
		# code...

		// $verified = get_comment_meta( $comment_id, 'verified', true );
	}


}

/**
 * Format my data.
 * @param  [type] $review_object [description]
 * @return [type]                [description]
 */
function rkv_format_convert_data_content( $review_object ) {

	// Set the timestamp and date formatting that we're gonna use.
	$set_timestamp  = strtotime( $review_object->comment_date );
	$review_title   = sprintf( __( 'Review on %s', 'woo-better-reviews' ), date( 'F jS, Y at g:i a', $set_timestamp ) );

	// Set some variables based on what was passed.
	$author_name    = ! empty( $review_object->comment_author ) ? $review_object->comment_author : '';
	$author_email   = ! empty( $review_object->comment_author_email ) ? $review_object->comment_author_email : '';
	$author_wp_id   = ! empty( $review_object->user_id ) ? $review_object->user_id : 0;

	// Check the verification.
	$maybe_verified = get_comment_meta( $review_object->comment_ID, 'verified', true );

	// Get my empty rating
	$original_score = get_comment_meta( $review_object->comment_ID, 'rating', true );

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
		'rating_total_score' => '',
		'rating_attributes'  => '',
		'author_charstcs'    => '',
	);

}
