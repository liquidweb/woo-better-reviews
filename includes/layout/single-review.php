<?php
/**
 * Handle some basic display logic.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Layout\SingleReview;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;
use LiquidWeb\WooBetterReviews\FormFields as FormFields;

/**
 * Build and return the header portion for a single review.
 *
 * @param  array $review  The data tied to the review.
 *
 * @return HTML
 */
function set_single_review_header_view( $review = array() ) {

	// Bail without the parts we want.
	if ( empty( $review ) ) {
		return;
	}

	// First set the empty.
	$display_view   = '';

	// Set a div around it.
	$display_view  .= '<div class="woo-better-reviews-single-header-wrap">';

	// Output the stars.
	if ( ! empty( $review['total_score'] ) ) {
		$display_view  .= '<span class="woo-better-reviews-single-stars-wrap">' . Helpers\get_scoring_stars_display( 0, $review['total_score'], false ) . '</span>';
	}

	// Output the actual title.
	if ( ! empty( $review['title'] ) ) {
		$display_view  .= '<h4 class="woo-better-reviews-single-title">' . esc_html( $review['title'] ) . '</h4>';
	}

	// Now set up our date view display.
	if ( ! empty( $review['date'] ) ) {

		// Format my date.
		$formatted_date = date( get_option( 'date_format' ), strtotime( $review['date'] ) );

		// And add it to my view.
		$display_view  .= '<p class="woo-better-reviews-single-date">' . sprintf( __( 'Posted on %s', 'woo-better-reviews' ), '<span class="woo-better-reviews-single-date-val">' . esc_attr( $formatted_date ) . '</span>' ) . '</p>';
	}

	// Close out the div.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'single_review_header_view', $display_view, $review );
}

/**
 * Set the display view for the review scoring info.
 *
 * @param  array $review  The data tied to the review.
 *
 * @return HTML
 */
function set_single_review_attributes_scoring_view( $review = array() ) {

	// Bail without the parts we want.
	if ( empty( $review ) || empty( $review['rating_attributes'] ) ) {
		return;
	}

	// Get all my attributes.
	$all_attributes = Queries\get_all_attributes( 'indexed' );

	// First set the empty.
	$display_view   = '';

	// Loop my characteristics.
	foreach ( $review['rating_attributes'] as $attribute_data ) {

		// Set my attribute ID, and grab that part of the data.
		$attribute_id   = absint( $attribute_data['id'] );

		// Now get my matching setup.
		$attribute_item = $all_attributes[ $attribute_id ];

		// Set my attribute score.
		$single_score   = ! empty( $attribute_data['value'] ) ? absint( $attribute_data['value'] ) : 0;

		// Set my various classes and labels.
		$set_min_label  = ! empty( $attribute_item->min_label ) ? esc_attr( $attribute_item->min_label ) : __( 'Min.', 'woo-better-reviews' );
		$set_max_label  = ! empty( $attribute_item->max_label ) ? esc_attr( $attribute_item->max_label ) : __( 'Max.', 'woo-better-reviews' );
		$min_max_class  = 'woo-better-reviews-list-attribute-summary-label woo-better-reviews-list-attribute-summary-label-';

		// Set it inside a div.
		$display_view  .= '<div class="woo-better-reviews-single-attribute-scoring-block">';

			// Output the title portion.
			$display_view  .= '<p class="woo-better-reviews-list-attribute-summary-title">' . esc_html( $attribute_data['label'] ) . '</p>';

			// Wrap the span blocks in a paragraph.
			$display_view  .= '<p class="woo-better-reviews-list-attribute-summary-squares">';

			// Now output each count block with a class applied on the match.
			for ( $i = 1; $i <= 7; $i++ ) {

				// Set a class if it matches.
				$square_class   = 'woo-better-reviews-list-attribute-summary-square woo-better-reviews-list-attribute-summary-square-' . absint( $i );
				$square_class  .= absint( $single_score ) === absint( $i ) ? ' woo-better-reviews-list-attribute-summary-square-fill' : ' woo-better-reviews-list-attribute-summary-square-empty';

				// Make my span.
				$display_view  .= '<span class="' . esc_attr( $square_class ) . '"></span>';
			}

			// Close the paragraph.
			$display_view  .= '</p>';

			// Handle my min-max labeling.
			$display_view  .= '<p class="woo-better-reviews-list-attribute-summary-labelset">';

				// Set the min and max.
				$display_view  .= '<span class="' . esc_attr( $min_max_class . 'min' ) . '">' . $set_min_label . '</span>';
				$display_view  .= '<span class="' . esc_attr( $min_max_class . 'max' ) . '">' . $set_max_label . '</span>';

			// Close the label group.
			$display_view  .= '</p>';

		// Close up the div.
		$display_view  .= '</div>';
	}

	// Wrap it in a div tag.
	$display_wrap   = '<div class="woo-better-reviews-single-attributes-scoring-wrap">' . $display_view . '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'single_review_attribute_scoring_view', $display_wrap, $review );
}

/**
 * Set the display view for the review content.
 *
 * @param  array $review  The data tied to the review.
 *
 * @return HTML
 */
function set_single_review_content_body_view( $review = array() ) {

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
		$display_view  .= wpautop( wp_kses_post( $texturize_text ) );
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'single_review_content_body_view', $display_view, $review );
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
	if ( empty( $review ) ) {
		return;
	}

	// First set the empty.
	$display_view   = '';

	// Now set up our author view display.
	if ( ! empty( $review['author_name'] ) ) {

		// Check for the verified part to add our icon.
		$show_verified  = ! empty( $review['is_verified'] ) ? '<span aria-label="' . esc_attr__( 'This review is verified.', 'woo-better-reviews' ) . '" class="woo-better-reviews-single-verified-check"></span>' : '';

		// And output.
		$display_view  .= '<p class="woo-better-reviews-single-author">' . sprintf( __( 'by %s', 'woo-better-reviews' ), '<span class="woo-better-reviews-single-author-val">' . esc_attr( $review['author_name'] ) . '</span>' ) . $show_verified . '</p>';
	}

	// Set the list of characteristics if we have them.
	if ( ! empty( $review['author_charstcs'] ) ) {

		// Set an unordered list.
		$display_view  .= '<ul class="woo-better-reviews-author-charstcs">';

		// Loop my characteristics.
		foreach ( $review['author_charstcs'] as $charstc ) {

			// Set a base class.
			$charstc_class  = 'woo-better-reviews-author-charstc-item woo-better-reviews-author-charstc';

			// Set the list item.
			$display_view  .= '<li class="woo-better-reviews-single-author-charstc">';

				// Set the label.
				$display_view  .= '<span class="' . esc_attr( $charstc_class ) . '-label">' . esc_html( $charstc['label'] ) . ': </span>';

				// Set the value.
				$display_view  .= '<span class="' . esc_attr( $charstc_class ) . '-value">' . esc_html( $charstc['value'] ) . '</span>';

			// Close the list item.
			$display_view  .= '</li>';
		}

		// Close up the unordered list.
		$display_view  .= '</ul>';
	}

	// Set the whole thing inside a div.
	$display_wrap   = '<div class="woo-better-reviews-single-charstcs-wrap">' . $display_view . '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'single_review_author_charstcs_view', $display_wrap, $review );
}
