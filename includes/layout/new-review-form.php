<?php
/**
 * Handle the parts of the form.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\Layout\NewReviewForm;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Utilities as Utilities;
use Nexcess\WooBetterReviews\Queries as Queries;
use Nexcess\WooBetterReviews\FormData as FormData;
use Nexcess\WooBetterReviews\FormFields as FormFields;

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

		// Wrap all the stars in a div.
		$display_view  .= '<div class="woo-better-reviews-rating-form-stars-row">';

			// Wrap the 7 stars in a fieldset.
			$display_view  .= '<fieldset class="woo-better-reviews-rating-form-stars-fieldset">';

			// Add our legend title.
			$display_view  .= '<legend class="woo-better-reviews-rating-fieldset-intro woo-better-reviews-rating-form-stars-intro">';

				// First do the actual legend.
				$display_view  .= esc_html__( 'Overall Rating', 'woo-better-reviews' );

				// Include the required portion.
				$display_view  .= '<span class="woo-better-reviews-field-required" aria-label="' . esc_attr( __( 'This is a required field', 'woo-better-reviews' ) ) . '">&#8727;</span>';

			// Close up the legend.
			$display_view  .= '</legend>';

			// Set (and reverse) my score range.
			$initial_range  = range( 1, 7, 1 );
			$setscore_range = array_reverse( $initial_range );

			// Loop my scoring range.
			foreach ( $setscore_range as $setscore ) {

				// Set the field ID and name.
				$field_id   = 'woo-better-reviews-rating-content-score-' . absint( $setscore );
				$field_name = 'woo-better-reviews-rating[score]';

				// Set my field args.
				$field_args = array(
					'title'    => sprintf( __( 'Select a %d star rating', 'woo-better-reviews' ), absint( $setscore ) ),
					'class'    => 'woo-better-reviews-single-star',
					'required' => true,
					'wrap'     => false,
				);

				// And output the field view.
				$display_view  .= FormFields\get_review_form_scoring_field( $field_args, $setscore, $field_id, $field_name );
			}

			// Close the fieldset.
			$display_view  .= '</fieldset>';

		// Close the star wrapping div.
		$display_view  .= '</div>';

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

	// Attempt to get our attributes based on the global setting.
	$attributes = Helpers\get_product_attributes_for_form( $product_id );

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

			// Set my field wrapper class.
			$wrapper_id     = 'woo-better-reviews-rating-attribute-' . sanitize_html_class( $attribute_args['slug'] );
			$wrapper_class  = 'woo-better-reviews-rating-attribute-single woo-better-reviews-rating-attribute-' . sanitize_html_class( $attribute_args['slug'] ) . '-wrap';

			// Set my min and max labels.
			$set_min_label  = ! empty( $attribute_args['min_label'] ) ? esc_attr( $attribute_args['min_label'] ) : __( 'Min.', 'woo-better-reviews' );
			$set_max_label  = ! empty( $attribute_args['max_label'] ) ? esc_attr( $attribute_args['max_label'] ) : __( 'Max.', 'woo-better-reviews' );
			$min_max_class  = 'woo-better-reviews-rating-attribute-label woo-better-reviews-rating-attribute-label-';

			// Wrap the whole thing in a big div. Yes, this is many divs.
			$display_view  .= '<div class="woo-better-reviews-rating-attribute-field-block">';

				// Wrap the attribute set in it's own div.
				$display_view  .= '<div id="' . esc_attr( $wrapper_id ) . '" class="' . esc_attr( $wrapper_class ) . '">';

					// Wrap the attribute boxes in a fieldset.
					$display_view  .= '<fieldset class="woo-better-reviews-rating-form-single-attribute-fieldset">';

						// Add our legend title.
						$display_view  .= '<legend class="woo-better-reviews-rating-fieldset-intro woo-better-reviews-rating-form-single-attribute-intro">';

							// First do the actual legend.
							$display_view  .= esc_html( trim( $attribute_args['name'] ) );

							// Include the required portion.
							$display_view  .= '<span class="woo-better-reviews-field-required" aria-label="' . esc_attr( __( 'This is a required field', 'woo-better-reviews' ) ) . '">&#8727;</span>';

						// Close up the legend.
						$display_view  .= '</legend>';

						// Loop my scoring range.
						for ( $setscore = 7; $setscore >= 1; $setscore-- ) {

							// Set the field ID and name.
							$field_id   = 'woo-better-reviews-rating-content-attributes-' . esc_attr( $attribute_args['slug'] ) . '-' . absint( $setscore );
							$field_name = 'woo-better-reviews-rating[attributes][' . absint( $attribute_args['id'] ) . ']';

							// Set my field args.
							$field_args = array(
								'title'    => sprintf( __( 'Select a %d rating for this attribute', 'woo-better-reviews' ), absint( $setscore ) ),
								'class'    => 'woo-better-reviews-single-attribute',
								'required' => true,
								'wrap'     => false,
							);

							// And output the field view.
							$display_view  .= FormFields\get_review_form_scoring_field( $field_args, $setscore, $field_id, $field_name );
						}

					// Close the fieldset.
					$display_view  .= '</fieldset>';

				// Close the div for the individual attribute set.
				$display_view  .= '</div>';

				// Handle my min-max labeling.
				$display_view  .= '<div class="woo-better-reviews-rating-attribute-label-group">';

					// Include a paragraph tag.
					$display_view  .= '<p>';

						// Set the min and max.
						$display_view  .= '<span class="' . esc_attr( $min_max_class . 'min' ) . '">' . $set_min_label . '</span>';
						$display_view  .= '<span class="' . esc_attr( $min_max_class . 'max' ) . '">' . $set_max_label . '</span>';

					// Close the paragraph tag.
					$display_view  .= '</p>';

				// Close the label group.
				$display_view  .= '</div>';

			// Close up the grouping div.
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
	$fieldset_data  = FormData\get_review_content_form_fields();

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

	// Get my form fields.
	$fieldset_data  = FormData\get_review_author_form_fields( $author_id );

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
					$field_name = ! empty( $field_args['charstcs-id'] ) ? 'woo-better-reviews-rating[author-charstcs][' . absint( $field_args['charstcs-id'] ) . ']' : '';

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
function set_review_form_submit_action_fields_view( $product_id = 0 ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) ) {
		return;
	}

	// Set my button field array.
	$fieldset_data  = FormData\get_review_action_buttons_fields();

	// Bail without buttons.
	if ( empty( $fieldset_data ) ) {
		return apply_filters( Core\HOOK_PREFIX . 'review_form_submit_actions_fields_view', '', null, $product_id );
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

			// Add the class to the button args.
			$button_args    = wp_parse_args( array( 'span' => $wrapper_class ), $button_args );

			// Output our button field.
			$display_view  .= FormFields\get_review_form_button_field( $button_args, $button_key, $button_id, $button_name );
		}

		// Close my paragraph.
		$display_view  .= '</p>';

	// Close the div.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_submit_actions_fields_view', $display_view, $fieldset_data, $product_id );
}

/**
 * Set up the portion displaying the hidden meta entry fields.
 *
 * @param  integer $product_id  The product ID we are displaying for.
 * @param  integer $author_id   The possible author ID we are displaying for.
 *
 * @return HTML
 */
function set_review_form_hidden_meta_fields_view( $product_id = 0, $author_id = 0 ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) ) {
		return;
	}

	// Set my hidden field array.
	$fieldset_data  = FormData\get_review_hidden_meta_fields( $product_id, $author_id );

	// Bail without buttons.
	if ( empty( $fieldset_data ) ) {
		return apply_filters( Core\HOOK_PREFIX . 'review_form_hidden_meta_fields_view', '', null, $product_id, $author_id );
	}

	// First set the empty.
	$display_view   = '';

	// Loop the buttons we have.
	foreach ( $fieldset_data as $field_id => $field_args ) {

		// Make sure we have a name.
		$field_name = ! empty( $field_args['name'] ) ? $field_args['name'] : $field_id;
		$field_val  = ! empty( $field_args['value'] ) ? $field_args['value'] : 0;

		// And show the field.
		$display_view  .= '<input type="hidden" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_val ) . '">';
	}

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_hidden_meta_fields_view', $display_view, $fieldset_data, $product_id, $author_id );
}
