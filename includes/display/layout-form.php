<?php
/**
 * Handle the parts of the form.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display\LayoutForm;

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
	$display_view   = '<h2 class="woo-better-reviews-rating-form-title">' . esc_html__( 'Leave a Review', 'woo-better-reviews' ) . '</h2>';

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

	// Wrap the attribute sets in a div.
	$display_view  .= '<div class="woo-better-reviews-rating-new-review-fields woo-better-reviews-rating-stars-fields">';

		// Output the title portion.
		$display_view  .= '<p class="woo-better-reviews-rating-form-stars-intro">' . esc_html__( 'Overall Rating:', 'woo-better-reviews' ) . '</p>';

		// Wrap the 7 stars in an unordered list.
		$display_view  .= '<ul class="woo-better-reviews-rating-form-stars-row">';

		// Loop my stars.
		for ( $i = 1; $i <= 7; $i++ ) {

			// Set my star args.
			$field_args = array(
				'label'       => '&#9733;',
				'description' => absint( $i ),
				'required'    => true,
				'class'       => 'woo-better-reviews-single-star-radio',
			);

			// Set the field ID and name.
			$field_id   = 'woo-better-reviews-rating-content-score-' . absint( $i );
			$field_name = 'woo-better-reviews-rating[score]';

			// Output the star.
			$display_view  .= '<li class="woo-better-reviews-rating-form-star">';
				$display_view  .= FormFields\get_review_form_radio_field( $field_args, 'rating-' . $i, $field_id, $field_name );
			$display_view  .= '</li>';
		}

		// Close the list.
		$display_view  .= '</ul>';

	// Close the div.
	$display_view  .= '</div>';

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

	// Wrap the attribute sets in a div.
	$display_view  .= '<div class="woo-better-reviews-rating-new-review-fields woo-better-reviews-rating-attributes-fields">';

	// Loop the attributes to break out each item.
	foreach ( $attributes as $attribute_args ) {
		// preprint( $attribute_args, true );

		// Set my field wrapper class.
		$wrapper_id     = 'woo-better-reviews-rating-attribute-' . sanitize_html_class( $attribute_args['slug'] );
		$wrapper_class  = 'woo-better-reviews-rating-attribute-single woo-better-reviews-rating-attribute-' . sanitize_html_class( $attribute_args['slug'] ) . '-wrap';

		// Set my min and max labels.
		$set_min_label  = ! empty( $attribute_args['min_label'] ) ? esc_attr( $attribute_args['min_label'] ) : __( 'Min.', 'woo-better-reviews' );
		$set_max_label  = ! empty( $attribute_args['max_label'] ) ? esc_attr( $attribute_args['max_label'] ) : __( 'Max.', 'woo-better-reviews' );
		$min_max_class  = 'woo-better-reviews-rating-attribute-label woo-better-reviews-rating-attribute-label-';

		// Wrap the attribute set in it's own div.
		$display_view  .= '<div id="' . esc_attr( $wrapper_id ) . '" class="' . esc_attr( $wrapper_class ) . '">';

			// Output the title portion.
			$display_view  .= '<p class="woo-better-reviews-rating-attribute-intro">' . esc_html( $attribute_args['name'] ) . '</p>';

			// Wrap the attribute scale with an unordered list.
			$display_view  .= '<ul class="woo-better-reviews-rating-attribute-row">';

			// Count on the scale.
			for ( $i = 1; $i <= 7; $i++ ) {

				// Set a list item class.
				$list_class = 'woo-better-reviews-rating-form-attribute woo-better-reviews-rating-form-attribute-' . absint( $i );

				// Set my field args.
				$field_args = array(
					'label'       => absint( $i ),
					'description' => '',
					'class'       => 'woo-better-reviews-single-attribute-radio',
					'required'    => true,
				);

				// Set the field ID and name.
				$field_id   = 'woo-better-reviews-rating-content-attributes-' . esc_attr( $attribute_args['slug'] ) . '-' . absint( $i );
				$field_name = 'woo-better-reviews-rating[attributes][' . esc_attr( $attribute_args['slug'] ) . ']';

				// Output the star.
				$display_view  .= '<li class="' . esc_attr( $list_class ) . '">';
					$display_view  .= FormFields\get_review_form_radio_field( $field_args, $i, $field_id, $field_name );
				$display_view  .= '</li>';
			}

			// Close the list.
			$display_view  .= '</ul>';

			// Handle my min-max labeling.
			$display_view  .= '<p class="woo-better-reviews-rating-attribute-label-group">';

				// Set the min and max.
				$display_view  .= '<span class="' . esc_attr( $min_max_class . 'min' ) . '">' . $set_min_label . '</span>';
				$display_view  .= '<span class="' . esc_attr( $min_max_class . 'max' ) . '">' . $set_max_label . '</span>';

			// Close the label group.
			$display_view  .= '</p>';

		// Close the div for the individual attribute set.
		$display_view  .= '</div>';
	}

	// Close the div.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_rating_attributes_view', $display_view, $attributes, $product_id );
}

/**
 * Set up the portion displaying the content entry fields.
 *
 * @param  integer $product_id  The product ID we are displaying for.
 *
 * @return HTML
 */
function set_review_form_content_fields_view( $product_id = 0 ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) ) {
		return;
	}

	// Get my form fields.
	$fieldset_data  = FormFields\get_review_content_form_fields();
	// preprint( $fieldset_data, true );

	// Bail without the fields to display.
	if ( empty( $fieldset_data ) ) {
		return apply_filters( Core\HOOK_PREFIX . 'review_form_content_fields_view', '', null, $product_id );
	}

	// First set the empty.
	$display_view   = '';

	// Wrap the fields inside a div.
	$display_view  .= '<div class="woo-better-reviews-rating-new-review-fields woo-better-reviews-rating-content-fields">';

	// Loop my form fields and output each one.
	foreach ( $fieldset_data as $field_key => $field_args ) {

		// Skip if no type is declared.
		if ( empty( $field_args['type'] ) ) {
			continue;
		}

		// Set my field wrapper class.
		$wrapper_class  = 'woo-better-reviews-rating-content-field-wrap woo-better-reviews-rating-' . sanitize_html_class( $field_args['type'] ) . '-field-wrap';

		// Wrap the field in a second div tag.
		$display_view  .= '<div id="woo-better-reviews-rating-' . sanitize_html_class( $field_key ) . '" class="' . esc_attr( $wrapper_class ) . '">';

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

			// Do the dropdown.
			case 'select' :
			case 'dropdown' :

				// Render the field.
				$display_view  .= FormFields\get_review_form_dropdown_field( $field_args, $field_key );
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
		$display_view  .= '</div>';
	}

	// Close the list.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_content_fields_view', $display_view, $fieldset_data, $product_id );
}

/**
 * Set up the portion displaying the author input entry fields.
 *
 * @param  integer $author_id  The author ID we are displaying this for.
 *
 * @return HTML
 */
function set_review_form_author_fields_view( $author_id = 0 ) {

	// Bail without the parts we want.
	if ( empty( $author_id ) ) {
		return;
	}

	// Get my form fields.
	$fieldset_data  = FormFields\get_review_author_form_fields();
	// preprint( $fieldset_data, true );

	// Bail without the fields to display.
	if ( empty( $fieldset_data ) ) {
		return apply_filters( Core\HOOK_PREFIX . 'review_form_author_fields_view', '', null, $author_id );
	}

	// Set my author field title.
	$author_view_h3 = apply_filters( Core\HOOK_PREFIX . 'review_form_author_fields_title', __( 'Tell us about yourself', 'woo-better-reviews' ), $author_id );

	// First set the empty.
	$display_view   = '';

	// Wrap the fields inside a div.
	$display_view  .= '<div class="woo-better-reviews-rating-new-review-fields woo-better-reviews-rating-author-fields">';

		// Set an actual title.
		$display_view  .= '<h3 class="woo-better-reviews-rating-author-fields-title">' . esc_html( $author_view_h3 ) . '</h3>';

		// Loop my form fields and output each one.
		foreach ( $fieldset_data as $field_key => $field_args ) {

			// Skip if no type is declared.
			if ( empty( $field_args['type'] ) ) {
				continue;
			}

			// Set my field wrapper class.
			$wrapper_class  = 'woo-better-reviews-rating-content-field-wrap woo-better-reviews-rating-' . sanitize_html_class( $field_args['type'] ) . '-field-wrap';
			$wrapper_class .= ! empty( $field_args['is-charstcs'] ) ? ' woo-better-reviews-rating-charstcs-field-wrap' : '';

			// Wrap the field in a second div tag.
			$display_view  .= '<div id="woo-better-reviews-rating-' . sanitize_html_class( $field_key ) . '" class="' . esc_attr( $wrapper_class ) . '">';

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

				// Do the dropdown.
				case 'select' :
				case 'dropdown' :

					// Set the field ID and name.
					$field_id   = ! empty( $field_args['is-charstcs'] ) ? 'woo-better-reviews-rating-content-charstcs-' . esc_attr( $field_key ) : '';
					$field_name = ! empty( $field_args['is-charstcs'] ) ? 'woo-better-reviews-rating[charstcs][' . esc_attr( $field_key ) . ']' : '';

					// Render the field.
					$display_view  .= FormFields\get_review_form_dropdown_field( $field_args, $field_key, $field_id, $field_name );
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
			$display_view  .= '</div>';
		}

	// Close the list.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_author_fields_view', $display_view, $fieldset_data, $author_id );
}

/**
 * Set up the portion displaying the content entry fields.
 *
 * @param  integer $product_id  The product ID we are displaying for.
 *
 * @return HTML
 */
function set_review_form_submit_meta_fields_view( $product_id = 0 ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) ) {
		return;
	}

	// Set my button array.
	$fieldset_data  = FormFields\get_review_action_buttons_fields();
	// preprint( $fieldset_data, true );

	// Bail without buttons.
	if ( empty( $fieldset_data ) ) {
		return apply_filters( Core\HOOK_PREFIX . 'review_form_submit_meta_fields_view', '', null, $product_id );
	}

	// First set the empty.
	$display_view   = '';

	// Wrap the fields inside a div.
	$display_view  .= '<div class="woo-better-reviews-rating-new-review-fields woo-better-reviews-rating-submit-meta-fields">';

		// Wrap the buttons we have.
		$display_view  .= '<p class="woo-better-reviews-rating-button-wrap">';

		// Loop the buttons we have.
		foreach ( $fieldset_data as $button_key => $button_args ) {

			// Set the wrapper class.
			$wrapper_class  = 'woo-better-reviews-rating-single-button-span woo-better-reviews-rating-' . sanitize_html_class( $button_key ) . '-span';

			// Set my button field ID and name.
			$button_id      = 'woo-better-reviews-rating-' . sanitize_html_class( $button_key ) . '-button';
			$button_name    = 'woo-better-reviews-' . sanitize_html_class( $button_key );

			// And handle the button.
			$display_view  .= '<span class="' . esc_attr( $wrapper_class ) . '">';

				// Output our button field.
				$display_view  .= FormFields\get_review_form_button_field( $button_args, $button_key, $button_id, $button_name );

			// Close up the span.
			$display_view  .= '</span>';
		}

			/*
			// Handle the actual submit button.
			$display_view  .= '<span class="woo-better-reviews-rating-single-button">';


				$display_view  .= '<button class="woo-better-reviews-rating-button woo-better-reviews-rating-submit-button" type="submit">' . __( 'Submit Review', 'woo-better-reviews' ) . '</button>';


			$display_view  .= '</span>';

			// Handle my reset button.
			$display_view  .= '<span class="woo-better-reviews-rating-single-button">';

				$display_view  .= '<button class="woo-better-reviews-rating-button woo-better-reviews-rating-reset-button"" type="reset">' . __( 'Reset', 'woo-better-reviews' ) . '</button>';

			$display_view  .= '</span>';
			*/
		// Close my paragraph.
		$display_view  .= '</p>';

		// Handle some hidden fields.
		$display_view  .= '<input type="hidden" name="woo-better-reviews-product-id" value="' . absint( $product_id ) . '">';
		$display_view  .= '<input type="hidden" name="woo-better-reviews-author-id" value="' . get_current_user_id() . '">';
		$display_view  .= '<input type="hidden" name="woo-better-reviews-add-new" value="1">';

		// And of course our nonce.
		$display_view  .= wp_nonce_field( 'wbr_new_review_submit_action', 'wbr_new_review_submit_nonce', true, false );

	// Close the div.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_submit_meta_fields_view', $display_view, $fieldset_data, $product_id );
}
