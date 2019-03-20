<?php
/**
 * Handle some basic display logic.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display\ViewOutput;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;
use LiquidWeb\WooBetterReviews\Display\LayoutForm as LayoutForm;
use LiquidWeb\WooBetterReviews\Display\LayoutReviews as LayoutReviews;

// And pull in any other namespaces.
use WP_Error;

/**
 * Build and display the 'leave a review' form.
 *
 * @param  integer $product_id  The product ID we are leaving a review for.
 * @param  boolean $echo        Whether to echo it out or return it.
 *
 * @return HTML
 */
function display_new_review_form( $product_id = 0, $echo = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Check for an override.
	$maybe_override = apply_filters( Core\HOOK_PREFIX . 'display_new_review_form_override', null, $product_id );

	// Return the override if we have it.
	if ( ! empty( $maybe_override ) ) {

		// Return if requested.
		if ( empty( $echo ) ) {
			return $maybe_override;
		}

		// Just echo it.
		echo $maybe_override;

		// And be done, since we skipped it.
		return;
	}

	// Set my action link.
	$action_link    = get_permalink( $product_id );

	// Set our empty.
	$build  = '';

	// Wrap the entire thing in our div.
	$build .= '<div id="review_form_wrapper" class="woo-better-reviews-display-block woo-better-reviews-form-block">';

		// Set our form wrapper.
		$build .= '<form class="woo-better-reviews-form-container" name="woo-better-reviews-rating-form" action="' . esc_url( $action_link ) . '" method="post">';

			// Add filterable fields.
			$build .= apply_filters( Core\HOOK_PREFIX . 'before_display_new_review_form', null, $product_id );

			// Add the title.
			$build .= LayoutForm\set_review_form_rating_title_view( $product_id );

			// Output the rating input.
			$build .= LayoutForm\set_review_form_rating_stars_view( $product_id );

			// Set the attributes.
			$build .= LayoutForm\set_review_form_rating_attributes_view( $product_id );

			// Handle the inputs themselves.
			$build .= LayoutForm\set_review_form_content_fields_view( $product_id );

			// Now get the author fields.
			$build .= LayoutForm\set_review_form_author_fields_view( get_current_user_id() );

			// Output the submit actions.
			$build .= LayoutForm\set_review_form_submit_action_fields_view( $product_id );

			// Output the hidden stuff.
			$build .= LayoutForm\set_review_form_hidden_meta_fields_view( $product_id, get_current_user_id() );

			// Add filterable fields.
			$build .= apply_filters( Core\HOOK_PREFIX . 'after_new_review_form_after', null, $product_id );

		// Close out the form.
		$build .= '</form>';

	// Close up the div.
	$build .= '</div>';

	// Return if requested.
	if ( empty( $echo ) ) {
		return $build;
	}

	// Just echo it.
	echo $build;
}

/**
 * Build and display the header.
 *
 * @param  integer $product_id  The product ID we are leaving a review for.
 * @param  boolean $echo        Whether to echo it out or return it.
 *
 * @return HTML
 */
function display_review_template_header( $product_id = 0, $echo = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Check for an override.
	$maybe_override = apply_filters( Core\HOOK_PREFIX . 'display_review_template_header_override', null, $product_id );

	// Return the override if we have it.
	if ( ! empty( $maybe_override ) ) {

		// Return if requested.
		if ( empty( $echo ) ) {
			return $maybe_override;
		}

		// Just echo it.
		echo $maybe_override;

		// And be done, since we skipped it.
		return;
	}

	// Get some variables based on the product ID.
	$leave_review   = get_permalink( $product_id ) . '#review_form_wrapper';
	$product_title  = get_the_title( $product_id );

	// Get the total count of reviews we have.
	$review_count   = Queries\get_review_count_for_product( $product_id );

	// Set our empty.
	$build  = '';

	// Set the div wrapper.
	$build .= '<div class="woo-better-reviews-list-title-wrapper">';

		// Wrap the title with our H2.
		$build .= '<h2 class="woocommerce-Reviews-title woo-better-reviews-template-title">';

			/* translators: 1: reviews count 2: product name */
			$build .= sprintf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $review_count, 'woo-better-reviews' ) ), esc_html( $review_count ), '<span class="woo-better-reviews-template-title-product-name">' . esc_html( $product_title ) . '</span>' );

			// Include the "leave a review" inline link if we have reviews.
			if ( ! empty( $review_count ) ) {
				$build .= ' <a class="woo-better-reviews-template-title-form-link" href="' . esc_url( $leave_review ) . '">' . esc_html__( 'Leave a review', 'woo-better-reviews' ) . '</a>';
			}

		// Close up the H2 tag.
		$build .= '</h2>';

	// Close up the div tag.
	$build .= '</div>';

	// Return if requested.
	if ( empty( $echo ) ) {
		return $build;
	}

	// Just echo it.
	echo $build;
}

/**
 * Build and display the our existing reviews.
 *
 * @param  integer $product_id  The product ID we are displaying reviews for.
 * @param  boolean $echo        Whether to echo it out or return it.
 *
 * @return HTML
 */
function display_existing_reviews( $product_id = 0, $echo = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Check for an override.
	$maybe_override = apply_filters( Core\HOOK_PREFIX . 'display_existing_reviews_override', null, $product_id );

	// Return the override if we have it.
	if ( ! empty( $maybe_override ) ) {

		// Return if requested.
		if ( empty( $echo ) ) {
			return $maybe_override;
		}

		// Just echo it.
		echo $maybe_override;

		// And be done, since we skipped it.
		return;
	}

	// Fetch any existing reviews we may have.
	$fetch_reviews  = Queries\get_reviews_for_product( $product_id, 'display' );
	// preprint( $fetch_reviews, true );

	// Set my content.
	if ( empty( $fetch_reviews ) ) {

		// Set our single line return.
		$notext = '<p class="woocommerce-noreviews woo-better-reviews-no-reviews">' . esc_html__( 'There are no reviews yet. Be the first!', 'woo-better-reviews' ) . '</p>';

		// Return if requested.
		if ( empty( $echo ) ) {
			return $notext;
		}

		// Just echo it.
		echo $notext;

		// And be done.
		return;
	}

	// Set a simple counter.
	$i  = 0;

	// Set our empty.
	$build  = '';

	// Set the div wrapper.
	$build .= '<div class="woo-better-reviews-list-display-wrapper">';

	// Now begin to loop the reviews and do the thing.
	foreach ( $fetch_reviews as $single_review ) {
		// preprint( $single_review, true );

		// Skip the non-approved ones for now.
		if ( empty( $single_review['status'] ) || 'approved' !== sanitize_text_field( $single_review['status'] ) ) {
			continue;
		}
		// preprint( $single_review, true );

		// Get my div class.
		$class  = Utilities\set_single_review_div_class( $single_review, $i );

		// Now open a div for the individual review.
		$build .= '<div id="' . sanitize_html_class( 'woo-better-reviews-single-' . absint( $single_review['review_id'] ) ) . '" class="' . esc_attr( $class ) . '">';

			// Output the title.
			$build .= LayoutReviews\set_single_review_title_summary_view( $single_review );

			// Output our date and author view.
			$build .= LayoutReviews\set_single_review_date_author_view( $single_review );

			// Output the actual content of the review.
			$build .= LayoutReviews\set_single_review_content_view( $single_review );

			// Do the scoring output.
			$build .= LayoutReviews\set_single_review_ratings_view( $single_review );

			// Output the author characteristics.
			$build .= LayoutReviews\set_single_review_author_charstcs_view( $single_review );

		// Close the single review div.
		$build .= '</div>';

		// And increment the counter.
		$i++;
	}

	// Close the large div wrapper.
	$build .= '</div>';

	// Return if requested.
	if ( empty( $echo ) ) {
		return $build;
	}

	// Just echo it.
	echo $build;
}
