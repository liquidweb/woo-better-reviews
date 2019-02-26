<?php
/**
 * Handle some basic display logic.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display\LayoutItems;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;
use LiquidWeb\WooBetterReviews\Display\FormFields as FormFields;

/**
 * Set up the portion displaying the 'leave a review' title.
 *
 * @param  integer $product_id  The product ID we are displaying reviews for.
 *
 * @return HTML
 */
function set_review_form_rating_title_view( $product_id = 0 ) {

	// Output the title portion.
	$display_view   = '<h2 class="woo-better-reviews-rating-form-stars-intro">' . esc_html__( 'Leave a Review', 'woo-better-reviews' ) . '</h2>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_title_view', $display_view, $product_id );
}

/**
 * Set up the portion displaying the scoring options.
 *
 * @param  integer $product_id  The product ID we are displaying reviews for.
 *
 * @return HTML
 */
function set_review_form_rating_stars_view( $product_id = 0 ) {

	// First set the empty.
	$display_view   = '';

	// Output the title portion.
	$display_view  .= '<p class="woo-better-reviews-rating-form-stars-intro">' . esc_html__( 'Overall Rating:', 'woo-better-reviews' ) . '</p>';

	// Wrap the 7 stars in an unordered list.
	$display_view  .= '<ul class="woo-better-reviews-rating-form-stars-row">';

		// Output the 7 stars.
		$display_view  .= str_repeat( '<li class="woo-better-reviews-rating-form-star">&#9733;</li>', 7 );

	// Close the list.
	$display_view  .= '</ul>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_rating_stars_view', $display_view, $product_id );
}

/**
 * Set up the portion displaying the rating attributes.
 *
 * @param  integer $product_id  The product ID we are displaying reviews for.
 *
 * @return HTML
 */
function set_review_form_rating_attributes_view( $product_id = 0 ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) ) {
		return;
	}

	// Attempt to get our attributes.
	$attributes = Queries\get_attributes_for_product( $product_id, 'display' );
	// preprint( $attributes, true );

	// Bail without the attributes to display.
	if ( empty( $attributes ) ) {
		return apply_filters( Core\HOOK_PREFIX . 'review_form_rating_attributes_view', '', null, $product_id );
	}

	// First set the empty.
	$display_view   = '';

	// Wrap the attribute sets in an unordered list.
	$display_view  .= '<ul class="woo-better-reviews-rating-attributes-row">';

	// Loop the attributes to break out each item.
	foreach ( $attributes as $attribute_args ) {
		// preprint( $attribute_args, true );

		// Set the field ID and name.
		$field_id   = 'woo-better-reviews-rating-content-attributes-' . esc_attr( $attribute_args['slug'] );
		$field_name = 'woo-better-reviews-rating[attributes][' . esc_attr( $attribute_args['slug'] ) . ']';

		// Set the field args to pass.
		$field_args = array(
			'label'         => esc_attr( $attribute_args['name'] ),
			'type'          => 'dropdown',
			'required'      => true,
			'include-empty' => true,
			'options'       => Utilities\format_attribute_dropdown_data( $attribute_args ),
		);

		// Wrap it in a list item.
		$display_view  .= '<li class="woo-better-reviews-rating-attribute-choice">';
			$display_view  .= FormFields\get_review_form_dropdown_field( $field_args, $attribute_args['slug'], $field_id, $field_name );
		$display_view  .= '</li>';
	}

	// Close the list.
	$display_view  .= '</ul>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_rating_attributes_view', $display_view, $attributes, $product_id );
}

/**
 * Set up the portion displaying the content entry fields.
 *
 * @param  integer $product_id  The product ID we are displaying reviews for.
 *
 * @return HTML
 */
function set_review_form_content_fields_view( $product_id = 0 ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) ) {
		return;
	}

	// Get my form fields.
	$fieldset_data  = FormFields\get_review_form_fields();
	// preprint( $fieldset_data, true );

	// Bail without the fields to display.
	if ( empty( $fieldset_data ) ) {
		return apply_filters( Core\HOOK_PREFIX . 'review_form_content_fields_view', '', null, $product_id );
	}

	// First set the empty.
	$display_view   = '';

	// Wrap the fields inside a div.
	$display_view  .= '<div class="woo-better-reviews-rating-content-fields">';

	// Loop my form fields and output each one.
	foreach ( $fieldset_data as $field_key => $field_args ) {

		// Skip if no type is declared.
		if ( empty( $field_args['type'] ) ) {
			continue;
		}

		// Set my field wrapper class.
		$wrapper_class  = 'woo-better-reviews-rating-content-field-wrap woo-better-reviews-rating-' . sanitize_html_class( $field_args['type'] ) . '-field-wrap';

		// Wrap the field in a paragraph tag.
		$display_view  .= '<p id="woo-better-reviews-rating-' . sanitize_html_class( $field_key ) . '" class="' . esc_attr( $wrapper_class ) . '">';

		// Output the field.
		switch ( esc_attr( $field_args['type'] ) ) {

			// Handle text and text-like.
			case 'input' :
			case 'text' :
			case 'tel' :
			case 'url' :
			case 'email' :
			case 'number' :

				// Handle the standard input field.
				$display_view  .= FormFields\get_review_form_input_field( $field_args, $field_key );
				break;

			// Do the textarea.
			case 'textarea' :

				// Render the field.
				$display_view  .= FormFields\get_review_form_textarea_field( $field_args, $field_key );
				break;

			// Render the minimal editor.
			case 'editor-minimal' :

				// Render the field.
				$display_view  .= FormFields\get_review_form_editor_minimal_field( $field_args, $field_key );
				break;

			//

			// End all case breaks.
		}

		// Close up the paragraph tag.
		$display_view  .= '</p>';
	}

	// Close the list.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_content_fields_view', $display_view, $fieldset_data, $product_id );
}

/**
 * Set the display view for the review post date.
 *
 * @param  array $review  The data tied to the review.
 *
 * @return HTML
 */
function set_single_review_title_summary_view( $review = array() ) {

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

	// Now set up our summary.
	if ( ! empty( $review['summary'] ) ) {
		$display_view  .= '<p class="woo-better-reviews-single-summary">' . wptexturize( $review['summary'] ) . '</p>';
	}

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'single_review_title_summary_view', $display_view, $review );
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
	if ( empty( $review ) || empty( $review['total-score'] ) && empty( $review['rating-attributes'] ) ) {
		return;
	}

	// First set the empty.
	$display_view   = '';

	// Output the total score part.
	if ( ! empty( $review['total-score'] ) ) {

		// Determine the score parts.
		$score_had  = absint( $review['total-score'] );
		$score_left = $score_had < 7 ? 7 - $score_had : 0;

		// Set the aria label.
		$aria_label = sprintf( __( 'Overall Score: %s', 'woo-better-reviews' ), absint( $score_had ) );

		// Wrap it in a span.
		$display_view  .= '<span class="woo-better-reviews-single-total-score" aria-label="' . esc_attr( $aria_label ) . '">';

			// Output the full stars.
			$display_view  .= str_repeat( '<span class="woo-better-reviews-single-star woo-better-reviews-single-star-full">&#9733;</span>', $score_had );

			// Output the empty stars.
			if ( $score_left > 0 ) {
				$display_view  .= str_repeat( '<span class="woo-better-reviews-single-star woo-better-reviews-single-star-empty">&#9734;</span>', $score_left );
			}

		// Close the span.
		$display_view  .= '</span>';
	}

	//  Handle displaying each attribute.
	if ( ! empty( $review['rating-attributes'] ) ) {

		// Set an unordered list.
		$display_view  .= '<ul class="woo-better-reviews-single-rating-attributes">';

		// Loop my characteristics.
		foreach ( $review['rating-attributes'] as $attribute ) {

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
	if ( ! empty( $review['author-name'] ) ) {
		$display_view[] = sprintf( __( 'by %s', 'woo-better-reviews' ), '<span class="' . esc_attr( $class_prefix ) . '-author-name">' . esc_attr( $review['author-name'] ) . '</span>' );
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
	if ( empty( $review ) || empty( $review['author-charstcs'] ) ) {
		return;
	}

	// First set the empty.
	$display_view   = '';

	// Set an unordered list.
	$display_view  .= '<ul class="woo-better-reviews-author-charstcs">';

	// Loop my characteristics.
	foreach ( $review['author-charstcs'] as $charstc ) {

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

