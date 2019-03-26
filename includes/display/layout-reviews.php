<?php
/**
 * Handle some basic display logic.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display\LayoutReviews;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;
use LiquidWeb\WooBetterReviews\Display\FormFields as FormFields;

/**
 * [set_single_review_scoring_total_view description]
 */
function set_single_review_scoring_total_view() {

}

/**
 * Set the display view for the review post date.
 *
 * @param  array $review  The data tied to the review.
 *
 * @return HTML
 */
function set_single_review_title_view( $review = array() ) {

	// Bail without the parts we want.
	if ( empty( $review ) ) {
		return;
	}

	// First set the empty.
	$display_view   = '';

	// Now set up our date view display.
	if ( ! empty( $review['title'] ) ) {
		$display_view  .= '<h4 class="woo-better-reviews-single-title">' . esc_html( $review['title'] ) . '</h4>';
	}

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'single_review_title_view', $display_view, $review );
}

/**
 * Set the display view for the review scoring info.
 *
 * @param  array $review  The data tied to the review.
 *
 * @return HTML
 */
function set_single_review_ratings_view( $review = array() ) {

	// Bail without the parts we want.
	if ( empty( $review ) || empty( $review['total_score'] ) && empty( $review['rating_attributes'] ) ) {
		return;
	}

	// First set the empty.
	$display_view   = '';

	// Output the total score part.
	if ( ! empty( $review['total_score'] ) ) {
		$display_view  .= Helpers\get_scoring_stars_display( $review['total_score'], 0, false );
	}

	//  Handle displaying each attribute.
	if ( ! empty( $review['rating_attributes'] ) ) {

		// Set an unordered list.
		$display_view  .= '<ul class="woo-better-reviews-single-rating-attributes">';

		// Loop my characteristics.
		foreach ( $review['rating_attributes'] as $attribute ) {

			// Set the list item.
			$display_view  .= '<li class="woo-better-reviews-single-rating-attribute">';

				// Set the label.
				$display_view  .= '<span class="woo-better-reviews-rating-attribute-item woo-better-reviews-rating-attribute-label">' . esc_html( $attribute['label'] ) . ': </span>';

				// Set the value.
				$display_view  .= '<span class="woo-better-reviews-rating-attribute-item woo-better-reviews-rating-attribute-value">' . esc_html( $attribute['value'] ) . ' / <small>7</small></span>';

			// Close the list item.
			$display_view  .= '</li>';
		}

		// Close up the unordered list.
		$display_view  .= '</ul>';
	}

	// Wrap it in a div tag.
	$display_wrap   = '<div class="woo-better-reviews-single-scoring-wrap">' . $display_view . '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'single_review_ratings_view', $display_wrap, $review );
}

/**
 * Set the display view for the review content.
 *
 * @param  array $review  The data tied to the review.
 *
 * @return HTML
 */
function set_single_review_content_view( $review = array() ) {

	// Bail without the parts we want.
	if ( empty( $review ) || empty( $review['review'] ) ) {
		return;
	}

	// Texturize our content.
	$texturize_text = wptexturize( $review['review'] );

	// First set the empty.
	$display_view   = '';

	// Set a div around it.
	$display_view  .= '<div class="woo-better-reviews-single-content-wrap">';

	// Output.
	$display_view  .= wpautop( wp_kses_post( $texturize_text ) );

	// Close up the div.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'single_review_content_view', $display_view, $review );
}

/**
 * Set the display view for the review post date.
 *
 * @param  array $review  The data tied to the review.
 *
 * @return HTML
 */
function set_single_review_date_author_view( $review = array() ) {

	// Bail without the parts we want.
	if ( empty( $review ) ) {
		return;
	}

	// Set my class prefix.
	$class_prefix   = 'woo-better-reviews-inline-item woo-better-reviews-inline';

	// First set the empty.
	$display_view   = array();

	// Now set up our date view display.
	if ( ! empty( $review['date'] ) ) {

		// Format my date.
		$formatted_date = date( get_option( 'date_format' ), strtotime( $review['date'] ) );

		// And add it to my view.
		$display_view[] = sprintf( __( 'Posted on %s', 'woo-better-reviews' ), '<span class="' . esc_attr( $class_prefix ) . '-review-date">' . esc_attr( $formatted_date ) . '</span>' );
	}

	// Now set up our author view display.
	if ( ! empty( $review['author_name'] ) ) {
		$display_view[] = sprintf( __( 'by %s', 'woo-better-reviews' ), '<span class="' . esc_attr( $class_prefix ) . '-author-name">' . esc_attr( $review['author_name'] ) . '</span>' );
	}

	// Check for the verified part to add our icon.
	if ( ! empty( $review['verified'] ) ) {
		$display_view[] = '<span aria-label="' . esc_attr__( 'This review is verified.', 'woo-better-reviews' ) . '" class="' . esc_attr( $class_prefix ) . '-verified-check"></span>';
	}

	// Return the empty filtered version.
	if ( empty( $display_view ) ) {
		return apply_filters( Core\HOOK_PREFIX . 'single_review_date_author_view', '', $review );
	}

	// Wrap it in a paragraph tag.
	$display_wrap   = '<p class="woo-better-reviews-single-date-author">' . implode( ' ', $display_view ) . '</p>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'single_review_date_author_view', $display_wrap, $review );
}

/**
 * Set the display view for the review author charstcs.
 *
 * @param  array $review  The data tied to the review.
 *
 * @return HTML
 */
function set_single_review_author_charstcs_view( $review = array() ) {

	// Bail without the parts we want.
	if ( empty( $review ) || empty( $review['author_charstcs'] ) ) {
		return;
	}

	// First set the empty.
	$display_view   = '';

	// Set an unordered list.
	$display_view  .= '<ul class="woo-better-reviews-author-charstcs">';

	// Loop my characteristics.
	foreach ( $review['author_charstcs'] as $charstc ) {

		// Set the list item.
		$display_view  .= '<li class="woo-better-reviews-single-author-charstc">';

			// Set the label.
			$display_view  .= '<span class="woo-better-reviews-author-charstc-item woo-better-reviews-author-charstc-label">' . esc_html( $charstc['label'] ) . ': </span>';

			// Set the value.
			$display_view  .= '<span class="woo-better-reviews-author-charstc-item woo-better-reviews-author-charstc-value">' . esc_html( $charstc['value'] ) . '</span>';

		// Close the list item.
		$display_view  .= '</li>';
	}

	// Close up the unordered list.
	$display_view  .= '</ul>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'single_review_author_charstcs_view', $display_view, $review );
}

