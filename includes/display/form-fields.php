<?php
/**
 * Handle some basic display logic.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display\FormFields;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;

/**
 * Filter my markup if I say so.
 *
 * @param  HTML $markup  The existing HTML.
 *
 * @return HTML
 */
function set_review_editor_required( $editor_markup ) {
	return str_replace( '<textarea ', '<textarea required="required" ', $editor_markup );
}

/**
 * Set and return the array of content fields.
 *
 * @param  boolean $keys  Whether to return just the keys.
 *
 * @return array
 */
function get_review_content_form_fields( $keys = false ) {

	// Set up our array.
	$setup  = array(

		'review-title' => array(
			'label'       => __( 'Review Title', 'woo-better-reviews' ),
			'type'        => 'text',
			'required'    => true,
			'description' => __( 'Example: This product has great features!', 'woo-better-reviews' ),
		),

		'review-summary' => array(
			'label'       => __( 'Review Summary', 'woo-better-reviews' ),
			'type'        => 'textarea',
			'required'    => true,
			'min-count'   => 50,
			'max-count'   => 300,
			'description' => '',
		),

		'review-content' => array(
			'label'       => __( 'Full Review', 'woo-better-reviews' ),
			'type'        => 'editor-minimal',
			'required'    => true,
			'description' => '',
		),

	);

	// Set the fields filtered.
	$fields = apply_filters( Core\HOOK_PREFIX . 'review_form_content_fields', $setup );

	// Either return the full array, or just the keys if requested.
	return ! $keys ? $fields : array_keys( $fields );
}

/**
 * Set and return the array of author entry fields.
 *
 * @param  boolean $keys  Whether to return just the keys.
 *
 * @return array
 */
function get_review_author_form_fields( $keys = false ) {

	// Set up our initial array.
	$setup  = array(

		'author-name' => array(
			'label'    => __( 'Your Name', 'woo-better-reviews' ),
			'type'     => 'text',
			'required' => true,
		),

		'author-email' => array(
			'label'    => __( 'Your Email', 'woo-better-reviews' ),
			'type'     => 'email',
			'required' => true,
		),

	);

	// Get all my characteristics.
	$fetch_charstcs = Queries\get_all_charstcs( 'display' );
	// preprint( $fetch_charstcs, true );

	// If we have the characteristics, add them.
	if ( ! empty( $fetch_charstcs ) ) {

		// Loop and add each one to the array.
		foreach ( $fetch_charstcs as $charstcs ) {

			// Skip if no values exist.
			if ( empty( $charstcs['values'] ) ) {
				continue;
			}

			// Set our array key.
			$array_key  = 'charstcs-' . sanitize_html_class( $charstcs['slug'] );

			// And add it.
			$setup[ $array_key ] = array(
				'label'         => esc_html( $charstcs['name'] ),
				'type'          => 'dropdown',
				'required'      => false,
				'include-empty' => true,
				'is-charstcs'   => true,
				'options'       => $charstcs['values'],
			);
		}

		// Nothing left for characteristics.
	}

	// Set the fields filtered.
	$fields = apply_filters( Core\HOOK_PREFIX . 'review_author_form_fields', $setup );

	// Either return the full array, or just the keys if requested.
	return ! $keys ? $fields : array_keys( $fields );
}

/**
 * Set and return the array of action buttons.
 *
 * @param  boolean $keys  Whether to return just the keys.
 *
 * @return array
 */
function get_review_action_buttons_fields( $keys = false ) {

	// Set my button array.
	$setup  = array(

		// Set up the submit button items.
		'submit-review' => array(
			'label' => __( 'Submit Review', 'woo-better-reviews' ),
			'class' => 'woo-better-reviews-rating-submit-button',
			'type'  => 'submit',
			'value' => true,
		),

		// Set up the reset button items.
		'reset-fields'  => array(
			'label' => __( 'Reset', 'woo-better-reviews' ),
			'class' => 'woo-better-reviews-rating-reset-button',
			'type'  => 'reset',
		),
	);

	// Set the fields filtered.
	$fields = apply_filters( Core\HOOK_PREFIX . 'review_form_action_buttons_fields', $setup );

	// Either return the full array, or just the keys if requested.
	return ! $keys ? $fields : array_keys( $fields );
}

/**
 * Build and return a text field.
 *
 * @param  array  $field_args  The field args array.
 * @param  string $field_key   The specific key used in the field.
 * @param  string $field_id    Optional field ID, otherwise one is generated from the key.
 * @param  string $field_name  Optional field name, otherwise one is generated from the key.
 *
 * @return HTML
 */
function get_review_form_input_field( $field_args = array(), $field_key = '', $field_id = '', $field_name = '' ) {
	// preprint( $field_args, true );

	// Bail if we don't have the args or the key.
	if ( empty( $field_args ) || empty( $field_key ) ) {
		return;
	}

	// Set my field class, name, and ID.
	$set_field_class    = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-' . esc_attr( $field_args['type'] ) . '-field';
	$set_field_class   .= ! empty( $field_args['class'] ) ? ' ' . sanitize_html_class( $field_args['class'] ) : '';

	$set_field_id       = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_field_name     = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Set the parts of the input field.
	$input_args_array   = array(
		'type'  => esc_attr( $field_args['type'] ),
		'name'  => esc_attr( $set_field_name ),
		'id'    => esc_attr( $set_field_id ),
		'class' => esc_attr( $set_field_class ),
	);

	// Add items to the array if we have them.
	$input_args_array   = ! empty( $field_args['custom'] ) ? wp_parse_args( $field_args['custom'], $input_args_array ) : $input_args_array;

	// Set my empty.
	$field  = '';

	// Check for the label first.
	if ( ! empty( $field_args['label'] ) ) {

		// Open the label.
		$field .= '<label for="' . esc_attr( $set_field_id ) . '" class="woo-better-reviews-rating-field-label">' . esc_html( $field_args['label'] );

		// Include the required portion.
		if ( ! empty( $field_args['required'] ) ) {
			$field .= '<span class="woo-better-reviews-field-required" aria-label="' . esc_attr( __( 'This is a required field', 'woo-better-reviews' ) ) . '">&#8727;</span>';
		}

		// Close my label.
		$field .= '</label>';
	}

	// Now begin the actual field input.
	$field .= '<input';

	// Loop the field parts.
	foreach ( $input_args_array as $input_key => $input_val ) {
		$field .= ' ' . esc_attr( $input_key ) . '="' . esc_attr( $input_val ) . '"';
	}

	// Include the required portion.
	if ( ! empty( $field_args['required'] ) ) {
		$field .= ' required="required"';
	}

	// Now add the empty value field and close it.
	$field .= ' value="" />';

	// Check for the description before we finish.
	if ( ! empty( $field_args['description'] ) ) {
		$field .= '<span class="woo-better-reviews-rating-field-description">' . esc_html( $field_args['description'] ) . '</span>';
	}

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_input_field', $field, $field_args, $field_key, $field_id, $field_name );
}

/**
 * Build and return a textarea field.
 *
 * @param  array  $field_args  The field args array.
 * @param  string $field_key   The specific key used in the field.
 * @param  string $field_id    Optional field ID, otherwise one is generated from the key.
 * @param  string $field_name  Optional field name, otherwise one is generated from the key.
 *
 * @return HTML
 */
function get_review_form_textarea_field( $field_args = array(), $field_key = '', $field_id = '', $field_name = '' ) {
	// preprint( $field_args, true );

	// Bail if we don't have the args or the key.
	if ( empty( $field_args ) || empty( $field_key ) ) {
		return;
	}

	// Set my field class, name, and ID.
	$set_field_class    = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-textarea-field';
	$set_field_class   .= ! empty( $field_args['class'] ) ? ' ' . sanitize_html_class( $field_args['class'] ) : '';

	$set_field_id       = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_field_name     = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Get the data pieces for the textarea.
	$set_textarea_data  = Utilities\format_review_textarea_data( $field_args );
	// preprint( $set_textarea_data, true );

	// Set my empty.
	$field  = '';

	// Check for the label first.
	if ( ! empty( $field_args['label'] ) ) {

		// Open the label.
		$field .= '<label for="' . esc_attr( $set_field_id ) . '" class="woo-better-reviews-rating-field-label">' . esc_html( $field_args['label'] );

		// Include the required portion.
		if ( ! empty( $field_args['required'] ) ) {
			$field .= '<span class="woo-better-reviews-field-required" aria-label="' . esc_attr( __( 'This is a required field', 'woo-better-reviews' ) ) . '">&#8727;</span>';
		}

		// Close my label.
		$field .= '</label>';
	}

	// Now do the actual field.
	$field .= '<textarea id="' . esc_attr( $set_field_id ) . '" name="' . esc_attr( $set_field_name ) . '" class="' . esc_attr( $set_field_class ) . '" ' . implode( ' ', $set_textarea_data ) . '></textarea>';

	// Check for the description before we finish.
	if ( ! empty( $field_args['description'] ) ) {
		$field .= '<span class="woo-better-reviews-rating-field-description">' . esc_html( $field_args['description'] ) . '</span>';
	}

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_textarea_field', $field, $field_args, $field_key, $field_id, $field_name );
}

/**
 * Build and return a select dropdown field.
 *
 * @param  array  $field_args  The field args array.
 * @param  string $field_key   The specific key used in the field.
 * @param  string $field_id    Optional field ID, otherwise one is generated from the key.
 * @param  string $field_name  Optional field name, otherwise one is generated from the key.
 *
 * @return HTML
 */
function get_review_form_dropdown_field( $field_args = array(), $field_key = '', $field_id = '', $field_name = '' ) {
	// preprint( $field_args, true );

	// Bail if we don't have the args, options, or the key.
	if ( empty( $field_args ) || empty( $field_args['options'] ) || empty( $field_key ) ) {
		return;
	}

	// Set my field class, name, and ID.
	$set_field_class    = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-dropdown-field';
	$set_field_class   .= ! empty( $field_args['class'] ) ? ' ' . sanitize_html_class( $field_args['class'] ) : '';

	$set_field_id       = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_field_name     = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Check for the empty to be included in the data array.
	$set_select_options = ! empty( $field_args['include-empty'] ) ? array( '0' => __( '(Select)', 'woo-better-reviews' ) ) + $field_args['options'] : $field_args['options'];

	// Set my empty.
	$field  = '';

	// Check for the label first.
	if ( ! empty( $field_args['label'] ) ) {

		// Open the label.
		$field .= '<label for="' . esc_attr( $set_field_id ) . '" class="woo-better-reviews-rating-field-label">' . esc_html( $field_args['label'] );

		// Include the required portion.
		if ( ! empty( $field_args['required'] ) ) {
			$field .= '<span class="woo-better-reviews-field-required" aria-label="' . esc_attr( __( 'This is a required field', 'woo-better-reviews' ) ) . '">&#8727;</span>';
		}

		// Close my label.
		$field .= '</label>';
	}

	// Now set the select tag.
	$field .= '<select id="' . esc_attr( $set_field_id ) . '" name="' . esc_attr( $set_field_name ) . '" class="' . esc_attr( $set_field_class ) . '">';

	// Loop the options.
	foreach ( $set_select_options as $option_value => $option_label ) {
		$field .= '<option value="' . esc_attr( $option_value ) . '">' . esc_html( $option_label ) . '</option>';
	}

	// Close the select tag.
	$field .= '</select>';

	// Check for the description before we finish.
	if ( ! empty( $field_args['description'] ) ) {
		$field .= '<span class="woo-better-reviews-rating-field-description">' . esc_html( $field_args['description'] ) . '</span>';
	}

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_dropdown_field', $field, $field_args, $field_key, $field_id, $field_name );
}

/**
 * Build and return a radio button field.
 *
 * @param  array  $field_args  The field args array.
 * @param  string $field_key   The specific key used in the field.
 * @param  string $field_id    Optional field ID, otherwise one is generated from the key.
 * @param  string $field_name  Optional field name, otherwise one is generated from the key.
 *
 * @return HTML
 */
function get_review_form_radio_field( $field_args = array(), $field_key = '', $field_id = '', $field_name = '' ) {
	// preprint( $field_args, true );

	// Bail if we don't have the args or the key.
	if ( empty( $field_args ) || empty( $field_key ) ) {
		return;
	}

	// Set my field class, name, and ID.
	$set_field_class    = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-radio-field';
	$set_field_class   .= ! empty( $field_args['class'] ) ? ' ' . sanitize_html_class( $field_args['class'] ) : '';

	$set_field_id       = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_field_name     = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Set my empty.
	$field  = '';

	// Check for the label first.
	if ( ! empty( $field_args['label'] ) ) {

		// Open the label.
		$field .= '<label for="' . esc_attr( $set_field_id ) . '" class="woo-better-reviews-rating-field-label">' . esc_html( $field_args['label'] );

		// Include the required portion.
		if ( ! empty( $field_args['required'] ) ) {
			$field .= '<span class="woo-better-reviews-field-required" aria-label="' . esc_attr( __( 'This is a required field', 'woo-better-reviews' ) ) . '">&#8727;</span>';
		}

		// Close my label.
		$field .= '</label>';
	}

	// Do the actual radio.
	$field .= '<input type="radio" id="' . esc_attr( $set_field_id ) . '" class="' . esc_attr( $set_field_class ) . '" name="' . esc_attr( $set_field_name ) . '"';

	// Add the required portion.
	$field .= empty( $field_args['required'] ) ? '' : ' required="required"';

	// Check for the checked flag.
	$field .= empty( $field_args['is-checked'] ) ? '' : ' checked="checked"';

	// And close the radio with our value and potential check.
	$field .= ' value="' . esc_attr( $field_key ) . '">';

	// Check for the description before we finish.
	if ( ! empty( $field_args['description'] ) ) {
		$field .= '<span class="woo-better-reviews-rating-field-description">' . esc_html( $field_args['description'] ) . '</span>';
	}

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_radio_field', $field, $field_args, $field_key, $field_id, $field_name );
}

/**
 * Build and return a minimal editor field.
 *
 * @param  array  $field_args  The field args array.
 * @param  string $field_key   The specific key used in the field.
 * @param  string $field_id    Optional field ID, otherwise one is generated from the key.
 * @param  string $field_name  Optional field name, otherwise one is generated from the key.
 *
 * @return HTML
 */
function get_review_form_editor_minimal_field( $field_args = array(), $field_key = '', $field_id = '', $field_name = '' ) {
	// preprint( $field_args, true );

	// Bail if we don't have the args or the key.
	if ( empty( $field_args ) || empty( $field_key ) ) {
		return;
	}

	// Set my field class, name, and ID.
	$set_field_class    = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-editor-minimal-field';
	$set_field_class   .= ! empty( $field_args['class'] ) ? ' ' . sanitize_html_class( $field_args['class'] ) : '';
	$set_field_id       = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_field_name     = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// If we set the required, hack our markup.
	if ( ! empty( $field_args['required'] ) ) {
		add_filter( 'the_editor', __NAMESPACE__ . '\set_review_editor_required', 11 );
	}

	// Set my empty.
	$field  = '';

	// Check for the label first.
	if ( ! empty( $field_args['label'] ) ) {

		// Open the label.
		$field .= '<label for="' . esc_attr( $set_field_id ) . '" class="woo-better-reviews-rating-field-label">' . esc_html( $field_args['label'] );

		// Include the required portion.
		if ( ! empty( $field_args['required'] ) ) {
			$field .= '<span class="woo-better-reviews-field-required" aria-label="' . esc_attr( __( 'This is a required field', 'woo-better-reviews' ) ) . '">&#8727;</span>';
		}

		// Close my label.
		$field .= '</label>';
	}

	// Do the editor.
	$field .= Utilities\set_review_form_editor( $set_field_id, $set_field_name, $set_field_class );

	// Check for the description before we finish.
	if ( ! empty( $field_args['description'] ) ) {
		$field .= '<span class="woo-better-reviews-rating-field-description">' . esc_html( $field_args['description'] ) . '</span>';
	}

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_editor_minimal_field', $field, $field_args, $field_key, $field_id, $field_name );
}


/**
 * Build and return a button field.
 *
 * @param  array  $field_args  The field args array.
 * @param  string $field_key   The specific key used in the field.
 * @param  string $field_id    Optional field ID, otherwise one is generated from the key.
 * @param  string $field_name  Optional field name, otherwise one is generated from the key.
 *
 * @return HTML
 */
function get_review_form_button_field( $field_args = array(), $field_key = '', $field_id = '', $field_name = '' ) {
	//  preprint( $field_args, true );

	// Bail if we don't have the args or the key.
	if ( empty( $field_args ) || empty( $field_key ) ) {
		return;
	}

	// Set my field class, name, and ID.
	$set_field_class    = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-button-field';
	$set_field_class   .= ! empty( $field_args['class'] ) ? ' ' . sanitize_html_class( $field_args['class'] ) : '';
	$set_field_id       = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_field_name     = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Set (and confirm) my button type.
	$set_button_type    = ! empty( $field_args['type'] ) && in_array( $field_args['type'], array( 'submit', 'reset', 'button' ) ) ? $field_args['type'] : 'button';

	// Check for a label and value before using the defaults.
	$set_button_value   = ! empty( $field_args['value'] ) ? $field_args['value'] : $field_key;
	$set_button_label   = ! empty( $field_args['label'] ) ? $field_args['label'] : __( 'Click Here', 'woo-better-reviews' );

	// Set my empty.
	$field  = '';

	// Do the actual field.
	$field .= '<button type="' . esc_attr( $set_button_type ) . '" id="' . esc_attr( $set_field_id ) . '" class="' . esc_attr( $set_field_class ) . '" name="' . esc_attr( $set_field_name ) . '" value="' . esc_attr( $set_button_value ) . '">' . esc_html( $set_button_label ) . '</button>';

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_button_field', $field, $field_args, $field_key, $field_id, $field_name );
}
