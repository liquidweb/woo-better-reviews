<?php
/**
 * Handle our review form processing.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display\FormProcess;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'init', __NAMESPACE__ . '\process_review_submission' );

/**
 * Process a submitted review.
 */
function process_review_submission() {

	// Run the check if we're enabled or not.
	$maybe_enabled  = Helpers\maybe_reviews_enabled();

	// Bail if we aren't aren't enabled, on admin, or don't have our posted key.
	if ( is_admin() || empty( $_POST['woo-better-reviews-add-new'] ) || ! $maybe_enabled ) {
		return;
	}

	// Handle the nonce check.
	if ( empty( $_POST['woo-better-reviews-add-new-nonce'] ) || ! wp_verify_nonce( $_POST['woo-better-reviews-add-new-nonce'], 'woo-better-reviews-add-new-action' ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}
	// preprint( $_POST, true );
	// Check for a product ID first.
	if ( empty( $_POST['woo-better-reviews-product-id'] ) ) {

		// Get my shop ID.
		$shop_page  = get_option( 'woocommerce_shop_page_id', 0 );

		// Now make my redirect.
		$shop_redir = ! empty( $shop_page ) ? get_permalink( absint( $shop_page ) ) : site_url();

		// And handle the redirect.
		redirect_front_submit_result( $shop_redir, 'missing-product-id' );
	}

	// Set my product ID as a variable.
	$product_id = absint( $_POST['woo-better-reviews-product-id'] );

	// Create my base redirect link.
	$base_redirect  = get_permalink( $product_id );

	// If we don't have the data pieces, bail.
	if ( empty( $_POST['woo-better-reviews-rating'] ) ) {
		redirect_front_submit_result( $base_redirect, 'missing-required-args' );
	}

	// Set my author ID as a variable.
	// @@todo figure out how to handle author IDs when it's not present.
	$author_id  = ! empty( $_POST['woo-better-reviews-author-id'] ) ? absint( $_POST['woo-better-reviews-author-id'] ) : 0;

	// Set our submitted data as it's own variable.
	$form_data  = $_POST['woo-better-reviews-rating'];
	// preprint( $form_data, true );

	// If we don't have the overall score, bail.
	if ( empty( $form_data['score'] ) ) {
		redirect_front_submit_result( $base_redirect, 'missing-total-rating' );
	}

	// Set my total score as a variable.
	$total_score    = absint( $form_data['score'] );

	// Format my content.
	$content_format = format_submitted_review_content( $form_data, $product_id, $author_id );
	// preprint( $content_format, true );

	// Bail without my content formatted.
	if ( empty( $content_format ) || is_wp_error( $content_format ) ) {

		// Determine the error code if we have one.
		$error_code = ! is_wp_error( $content_format ) ? 'invalid-content-formatting' : $content_format->get_error_code();

		// And run the redirect.
		redirect_front_submit_result( $base_redirect, $error_code );
	}

	// Attempt to insert the review content and get a review ID back.
	$attempt_insert = Database\insert( 'content', $content_format );

	// Bail on a failed insert.
	if ( empty( $attempt_insert ) || is_wp_error( $attempt_insert ) ) {

		// Determine the error code if we have one.
		$error_code = ! is_wp_error( $attempt_insert ) ? 'content-insert-fail' : $attempt_insert->get_error_code();

		// And run the redirect.
		redirect_front_submit_result( $base_redirect, $error_code );
	}

	// Set and sanitize the new review ID.
	$new_review_id  = absint( $attempt_insert );

	// Format my scoring.
	$scoring_format = format_submitted_review_scoring( $form_data, $new_review_id, $product_id, $author_id );
	// preprint( $scoring_format ,true );

	// Bail without my scoring formatted.
	if ( empty( $scoring_format ) || is_wp_error( $scoring_format ) ) {

		// Determine the error code if we have one.
		$error_code = ! is_wp_error( $scoring_format ) ? 'invalid-scoring-formatting' : $scoring_format->get_error_code();

		// And run the redirect.
		redirect_front_submit_result( $base_redirect, $error_code );
	}

	// Now loop and attempt each insert.
	foreach ( $scoring_format as $single_scoring_array ) {

		// Attempt to insert the review scoring and get a review ID back.
		$insert_scoring = Database\insert( 'ratings', $single_scoring_array );

		// Bail on a failed insert.
		if ( empty( $insert_scoring ) || is_wp_error( $insert_scoring ) ) {

			// Determine the error code if we have one.
			$error_code = ! is_wp_error( $insert_scoring ) ? 'scoring-insert-fail' : $insert_scoring->get_error_code();

			// And run the redirect.
			redirect_front_submit_result( $base_redirect, $error_code );
		}

		// Scoring inserts are done.
	}

	// Set up the author formatting insert.
	$author_format  = format_submitted_review_author( $form_data, $new_review_id, $author_id );
	// preprint( $author_format, true );

	// Bail without my author formatted.
	if ( empty( $author_format ) || is_wp_error( $author_format ) ) {

		// Determine the error code if we have one.
		$error_code = ! is_wp_error( $author_format ) ? 'invalid-author-formatting' : $author_format->get_error_code();

		// And run the redirect.
		redirect_front_submit_result( $base_redirect, $error_code );
	}

	// Now loop and attempt each insert.
	foreach ( $author_format as $single_author_array ) {

		// Attempt to insert the review author.
		$insert_author  = Database\insert( 'authormeta', $single_author_array );

		// Bail on a failed insert.
		if ( empty( $insert_scoring ) || is_wp_error( $insert_scoring ) ) {

			// Determine the error code if we have one.
			$error_code = ! is_wp_error( $insert_scoring ) ? 'scoring-insert-fail' : $insert_scoring->get_error_code();

			// And run the redirect.
			redirect_front_submit_result( $base_redirect, $error_code );
		}

		// Author inserts are done.
	}

	// Now we run our consolidation.
	$maybe_consolidated = consolidate_single_review_data( $new_review_id, $total_score, $content_format, $scoring_format, $author_format );

	// If we have the good ID coming back, redirect with it.
	if ( ! empty( $maybe_consolidated ) && ! is_wp_error( $maybe_consolidated ) ) {

		// Set my custom args for transients.
		$purging_args = array(
			'author-id'  => $author_id,
			'product-id' => $product_id,
		);

		// Handle the transient purging.
		Utilities\purge_transients( null, 'reviews', $purging_args );

		// Increment the count of reviews we have.
		Utilities\increment_product_review_count( $product_id );

		// The review has been successfully entered, so redirect.
		redirect_front_submit_result( $base_redirect, '', true, array( 'wbr-new-review' => $maybe_consolidated ) );
	}

	// Determine the error code if we have one.
	$error_code = ! is_wp_error( $maybe_consolidated ) ? 'consolidated-insert-fail' : $maybe_consolidated->get_error_code();

	// And run the redirect.
	redirect_front_submit_result( $base_redirect, $error_code );
}

/**
 * Redirect based on an edit action result.
 *
 * @param  string  $redirect  The link to redirect to.
 * @param  string  $error     Optional error code.
 * @param  boolean $success   Whether it was successful.
 * @param  array   $custom    Any custom args to add.
 * @param  string  $return    Slug of the menu page to add a return link for.
 *
 * @return void
 */
function redirect_front_submit_result( $redirect = '', $error = '', $success = false, $custom = array(), $return = '' ) {

	// Set up my results based on the success flag.
	$check_results  = ! empty( $success ) ? 'added' : 'failed';

	// Set up my redirect args.
	$redirect_args  = array(
		'success'             => $success,
		'wbr-submit-complete' => 1,
		'wbr-submit-result'   => esc_attr( $check_results ),
	);

	// Add the error code if we have one.
	$redirect_args  = ! empty( $error ) ? wp_parse_args( $redirect_args, array( 'wbr-error-code' => esc_attr( $error ) ) ) : $redirect_args;

	// Now check to see if we have a return, which means a return link.
	$redirect_args  = ! empty( $return ) ? wp_parse_args( $redirect_args, array( 'wbr-submit-return' => $return ) ) : $redirect_args;

	// Add any custom item.
	$redirect_args  = ! empty( $custom ) ? wp_parse_args( $custom, $redirect_args ) : $redirect_args;

	// Now set my redirect link.
	$redirect_link  = add_query_arg( $redirect_args, $redirect );

	// Do the redirect.
	wp_safe_redirect( $redirect_link );
	exit;
}

/**
 * Format the content portion of a review.
 *
 * @param  array   $form_data   The total form data passed.
 * @param  integer $product_id  The product ID tied to the review.
 * @param  integer $author_id   The potential author ID.
 *
 * @return array
 */
function format_submitted_review_content( $form_data = array(), $product_id = 0, $author_id = 0 ) {

	// Bail without the data needed.
	if ( empty( $form_data ) || empty( $product_id ) || empty( $form_data['review-content'] ) ) {
		return new WP_Error( 'missing-formatting-data', __( 'The required data to format.', 'woo-better-reviews' ) );
	}

	// Set the timestamp and date formatting that we're gonna use.
	$set_timestamp  = current_time( 'timestamp', false );
	$default_title  = sprintf( __( 'Review on %s', 'woo-better-reviews' ), date( 'F jS, Y at g:i a', $set_timestamp ) );

	// Set some variables based on what was passed.
	$author_name    = ! empty( $form_data['author-name'] ) ? $form_data['author-name'] : '';
	$author_email   = ! empty( $form_data['author-email'] ) ? $form_data['author-email'] : '';
	$review_title   = ! empty( $form_data['review-title'] ) ? $form_data['review-title'] : $default_title;
	$review_summary = ! empty( $form_data['review-summary'] ) ? $form_data['review-summary'] : '';

	// Set up the insert data array.
	$insert_setup   = array(
		'author_id'      => absint( $author_id ),
		'author_name'    => sanitize_text_field( $author_name ),
		'author_email'   => sanitize_email( $author_email ),
		'product_id'     => absint( $product_id ),
		'review_date'    => date( 'Y-m-d H:i:s', $set_timestamp ),
		'review_title'   => sanitize_text_field( $review_title ),
		'review_slug'    => sanitize_title_with_dashes( $review_title, null, 'save' ),
		'review_summary' => esc_textarea( $review_summary ),
		'review_content' => wp_kses_post( $form_data['review-content'] ),
		'review_status'  => 'approved' , // 'pending',
		'is_verified'    => 0,
	);

	// Go ahead and de-slash the content.
	$insert_setup   = array_map( 'stripslashes_deep', $insert_setup );

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'format_submitted_review_content', $insert_setup, $form_data, $product_id, $author_id );
}

/**
 * Format the scoring portion of a review.
 *
 * @param  array   $form_data   The total form data passed.
 * @param  integer $review_id   The review ID tied to the review.
 * @param  integer $product_id  The product ID tied to the review.
 * @param  integer $author_id   The potential author ID.
 *
 * @return array
 */
function format_submitted_review_scoring( $form_data = array(), $review_id = 0, $product_id = 0, $author_id = 0 ) {

	// Bail without the review ID.
	if ( empty( $review_id ) ) {
		return new WP_Error( 'missing-review-id', __( 'The required review ID was not provided.', 'woo-better-reviews' ) );
	}

	// Bail without the data needed.
	if ( empty( $form_data ) || empty( $product_id ) || empty( $form_data['score'] ) ) {
		return new WP_Error( 'missing-formatting-data', __( 'The required data to format.', 'woo-better-reviews' ) );
	}

	// Set up the first insert data array for the
	// total, which has an attribute ID of zero.
	$insert_setup[] = array(
		'review_id'    => $review_id,
		'author_id'    => $author_id,
		'product_id'   => $product_id,
		'attribute_id' => 0,
		'rating_score' => $form_data['score'],
	);

	// Now loop through the attributes.
	if ( ! empty( $form_data['attributes'] ) ) {

		// Set up our key => value pair.
		foreach ( $form_data['attributes'] as $attribute_id => $attribute_score ) {

			// Add to the array using this attribute.
			$insert_setup[] = array(
				'review_id'    => $review_id,
				'author_id'    => $author_id,
				'product_id'   => $product_id,
				'attribute_id' => $attribute_id,
				'rating_score' => $attribute_score,
			);

			// Nothing remains for this.
		 }
	}

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'format_submitted_scoring_content', $insert_setup, $form_data, $review_id, $product_id, $author_id );
}

/**
 * Format the author meta portion of a review.
 *
 * @param  array   $form_data   The total form data passed.
 * @param  integer $review_id   The review ID tied to the review.
 * @param  integer $author_id   The author ID we are storing for.
 *
 * @return array
 */
function format_submitted_review_author( $form_data = array(), $review_id = 0, $author_id = 0 ) {

	// Bail without the review ID.
	if ( empty( $review_id ) ) {
		return new WP_Error( 'missing-review-id', __( 'The required review ID was not provided.', 'woo-better-reviews' ) );
	}

	// Bail without the data needed.
	if ( empty( $form_data ) || empty( $form_data['author-charstcs'] ) ) {
		return new WP_Error( 'missing-formatting-data', __( 'The required data to format.', 'woo-better-reviews' ) );
	}

	// Set a blank for the insert.
	$insert_setup = array();

	// Now loop through the author characteristics.
	foreach ( $form_data['author-charstcs'] as $charstcs_id => $charstcs_value ) {

		// Add to the array using this attribute.
		$insert_setup[] = array(
			'author_id'      => $author_id,
			'review_id'      => $review_id,
			'charstcs_id'    => $charstcs_id,
			'charstcs_value' => $charstcs_value,
		);

		// Nothing remains for this.
	 }

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'format_submitted_scoring_content', $insert_setup, $form_data, $review_id, $author_id );
}

/**
 * Take our new review data and create the consolidated values.
 *
 * @param  integer $review_id      The ID of our new review.
 * @param  integer $total_score    The total review score.
 * @param  array   $content_array  The array of review content data.
 * @param  array   $scoring_array  The array of review scoring data.
 * @param  array   $author_array   The array of review author data.
 *
 * @return mixed
 */
function consolidate_single_review_data( $review_id = 0, $total_score = 0, $content_array = array(), $scoring_array = array(), $author_array = array() ) {

	// Bail without the review ID.
	if ( empty( $review_id ) ) {
		return new WP_Error( 'missing-review-id', __( 'The required review ID was not provided.', 'woo-better-reviews' ) );
	}

	// @@todo check for an existing consolidated review. update? bail?

	// Bail without the data needed.
	if ( empty( $content_array ) || empty( $scoring_array ) || empty( $author_array ) ) {
		return new WP_Error( 'missing-formatting-data', __( 'The required data is missing.', 'woo-better-reviews' ) );
	}

	// Cast our review content as an array while adding the review ID.
	$consolidated_setup = wp_parse_args( $content_array, array( 'review_id' => absint( $review_id ) ) );

	// We know this key is static so we can enter it.
	$consolidated_setup['rating_total_score'] = absint( $total_score );

	// Add the attributes and charstcs.
	$consolidated_setup['rating_attributes'] = parse_attributes_for_consolidated( $scoring_array );
	$consolidated_setup['author_charstcs']   = parse_charstcs_for_consolidated( $author_array );

	// Attempt to insert the review author.
	$maybe_insert   = Database\insert( 'consolidated', $consolidated_setup );

	// Bail on a failed insert.
	if ( empty( $maybe_insert ) || is_wp_error( $maybe_insert ) ) {

		// Determine the error text and code if we have one.
		$error_code = ! is_wp_error( $maybe_insert ) ? 'consolidated-insert-fail' : $maybe_insert->get_error_code();
		$error_text = ! is_wp_error( $maybe_insert ) ? __( 'The consolidated review could not be inserted.', 'woo-better-reviews' ) : $maybe_insert->get_error_message();

		// And return the new WP_Error item.
		return new WP_Error( $error_code, $error_text );
	}

	// Return the new consolidated ID.
	return absint( $maybe_insert );
}

/**
 * Take the scoring attributes data and match it up to our consolidated table.
 *
 * @param  array  $scoring_array  Our scoring data.
 *
 * @return array
 */
function parse_attributes_for_consolidated( $scoring_array = array() ) {

	// Bail without the array of data.
	if ( empty( $scoring_array ) ) {
		return;
	}

	// Set a blank item.
	$setup_args = array();

	// Now loop the scoring array and add to the consolidated.
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
 * Take the author charstcs data and match it up to our consolidated table.
 *
 * @param  array  $author_array  Our author data.
 *
 * @return array
 */
function parse_charstcs_for_consolidated( $author_array = array() ) {

	// Bail without the array of data.
	if ( empty( $author_array ) ) {
		return;
	}

	// Set a blank item.
	$setup_args = array();

	// Now loop the author array and add to the consolidated.
	foreach ( $author_array as $single_author ) {

		// Set up my two column keys.
		$array_key  = $single_author['charstcs_id'];
		$array_val  = $single_author['charstcs_value'];

		// Handle our two dynamic key names.
		$setup_args[ $array_key ] = $array_val;

		// This finishes out the author data.
	}

	// Return the args, serialized.
	return maybe_serialize( $setup_args );
}