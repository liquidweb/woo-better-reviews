<?php
/**
 * Handle some basic display logic.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\FormFields;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Utilities as Utilities;
use Nexcess\WooBetterReviews\Queries as Queries;

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

	// Bail if we don't have the args or the key.
	if ( empty( $field_args ) || empty( $field_key ) ) {
		return;
	}

	// Set my field class, name, and ID.
	$set_field_class    = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-' . esc_attr( $field_args['type'] ) . '-field';
	$set_field_class   .= ! empty( $field_args['class'] ) ? ' ' . sanitize_html_class( $field_args['class'] ) : '';

	$set_field_id       = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_field_name     = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Make sure we have a correct field type.
	$set_field_type     = ! empty( $field_args['type'] ) && in_array( $field_args['type'], array( 'text', 'tel', 'url', 'email', 'number' ) ) ? $field_args['type'] : 'text';

	// Check for a value.
	$set_field_value    = ! empty( $field_args['value'] ) ? $field_args['value'] : '';

	// Set the parts of the input field.
	$input_args_array   = array(
		'type'  => esc_attr( $set_field_type ),
		'name'  => esc_attr( $set_field_name ),
		'id'    => esc_attr( $set_field_id ),
		'class' => esc_attr( $set_field_class ),
		'value' => esc_attr( $set_field_value ),
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

	// Include the required portion.
	if ( ! empty( $field_args['required'] ) ) {
		$field .= ' required="required"';
	}

	// Loop the field parts.
	foreach ( $input_args_array as $input_key => $input_val ) {
		$field .= ' ' . esc_attr( $input_key ) . '="' . esc_attr( $input_val ) . '"';
	}

	// And close the field.
	$field .= ' />';

	// Check for the description before we finish.
	if ( ! empty( $field_args['description'] ) ) {
		$field .= '<span class="woo-better-reviews-rating-field-description">' . esc_html( $field_args['description'] ) . '</span>';
	}

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_input_field', $field, $field_args, $field_key, $field_id, $field_name );
}

/**
 * Build and return a radio field specific to ratings.
 *
 * @param  array  $field_args  The field args array.
 * @param  string $field_key   The specific key used in the field.
 * @param  string $field_id    Optional field ID, otherwise one is generated from the key.
 * @param  string $field_name  Optional field name, otherwise one is generated from the key.
 *
 * @return HTML
 */
function get_review_form_scoring_field( $field_args = array(), $field_key = '', $field_id = '', $field_name = '' ) {

	// Bail if we don't have the args or the key.
	if ( empty( $field_args ) || empty( $field_key ) ) {
		return;
	}

	// Set my field class, name, and ID.
	$set_field_class    = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-scoring-field';
	$set_field_class   .= ! empty( $field_args['class'] ) ? ' ' . sanitize_html_class( $field_args['class'] ) : '';

	$set_field_id       = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_field_name     = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Set my field label text and title with an optional blank.
	$set_label_text     = ! empty( $field_args['label'] ) ? $field_args['label'] : '';
	$set_label_title    = ! empty( $field_args['title'] ) ? $field_args['title'] : '';

	// Set the parts of the input field.
	$input_args_array   = array(
		'name'  => esc_attr( $set_field_name ),
		'id'    => esc_attr( $set_field_id ),
		'class' => esc_attr( $set_field_class ),
		'value' => esc_attr( $field_key ),
	);

	// Add items to the array if we have them.
	$input_args_array   = ! empty( $field_args['custom'] ) ? wp_parse_args( $field_args['custom'], $input_args_array ) : $input_args_array;

	// Set my empty.
	$field  = '';

	// Now begin the actual field input.
	$field .= '<input type="radio"';

	// Check for the required and checked flags.
	$field .= empty( $field_args['required'] ) ? '' : ' required="required"';
	$field .= empty( $field_args['is-checked'] ) ? '' : ' checked="checked"';

	// Loop the field parts.
	foreach ( $input_args_array as $input_key => $input_val ) {
		$field .= ' ' . esc_attr( $input_key ) . '="' . esc_attr( $input_val ) . '"';
	}

	// And close the field.
	$field .= '>';

	// Check for a label.
	$field .= '<label for="' . esc_attr( $set_field_id ) . '" class="woo-better-reviews-rating-field-label" title="' . esc_attr( $set_label_title ) . '">' . esc_html( $set_label_text ) . '</label>';

	// Check for the span wrap.
	$setup  = ! empty( $field_args['wrap'] ) ? '<span class="woo-better-reviews-rating-score-wrap">' . $field . '</span>' : $field;

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_scoring_field', $setup, $field_args, $field_key, $field_id, $field_name );
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

	// And set a selected.
	$is_selected_option = ! empty( $field_args['selected'] ) ? $field_args['selected'] : '';

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
		$field .= '<option value="' . esc_attr( $option_value ) . '" ' . selected( $is_selected_option, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
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

	// Now run the label check.
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

	// Check for the required and checked flags.
	$field .= empty( $field_args['required'] ) ? '' : ' required="required"';
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

	// Set the parts of the input field.
	$button_args_array  = array(
		'type'  => esc_attr( $set_button_type ),
		'name'  => esc_attr( $set_field_name ),
		'id'    => esc_attr( $set_field_id ),
		'class' => esc_attr( $set_field_class ),
		'value' => esc_attr( $set_button_value ),
	);

	// Add items to the array if we have them.
	$button_args_array  = ! empty( $field_args['custom'] ) ? wp_parse_args( $field_args['custom'], $button_args_array ) : $button_args_array;

	// Set my empty.
	$field  = '';

	// Now begin the actual field input.
	$field .= '<button';

	// Loop the field parts.
	foreach ( $button_args_array as $button_key => $button_val ) {
		$field .= ' ' . esc_attr( $button_key ) . '="' . esc_attr( $button_val ) . '"';
	}

	// Now add the closing tag and our label.
	$field .= '>' . esc_html( $set_button_label ) . '</button>';

	// Check if need to wrap the span.
	$setup  = ! empty( $field_args['span'] ) ? '<span class="' . esc_attr( $field_args['span'] ) . '">' . $field . '</span>' : $field;

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_button_field', $setup, $field_args, $field_key, $field_id, $field_name );
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
function get_review_form_hidden_field( $field_args = array(), $field_key = '', $field_id = '', $field_name = '' ) {

	// Bail if we don't have the args or the key.
	if ( empty( $field_args ) || empty( $field_key ) ) {
		return;
	}

	// Set my field name, and ID.
	$set_field_id       = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_field_name     = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Check for a value.
	$set_field_value    = ! empty( $field_args['value'] ) ? $field_args['value'] : '';

	// Set the parts of the input field.
	$input_args_array   = array(
		'name'  => esc_attr( $set_field_name ),
		'id'    => esc_attr( $set_field_id ),
		'value' => esc_attr( $set_field_value ),
	);

	// Add items to the array if we have them.
	$input_args_array   = ! empty( $field_args['custom'] ) ? wp_parse_args( $field_args['custom'], $input_args_array ) : $input_args_array;

	// If for some reason someone mucked with the field type, remove it.
	unset( $input_args_array['type'] );

	// Set my empty.
	$field  = '';

	// Now begin the actual field input.
	$field .= '<input type="hidden"';

	// Loop the field parts.
	foreach ( $input_args_array as $input_key => $input_val ) {
		$field .= ' ' . esc_attr( $input_key ) . '="' . esc_attr( $input_val ) . '"';
	}

	// And close the field.
	$field .= ' />';

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_hidden_field', $field, $field_args, $field_key, $field_id, $field_name );
}

/**
 * Build and return a select dropdown field.
 *
 * @param  array  $field_args  The field args array.
 * @param  string $field_key   The specific key used in the field.
 * @param  string $field_name  Optional field name, otherwise one is generated from the key.
 *
 * @return HTML
 */
function get_review_sorting_dropdown_field( $field_args = array(), $field_key = '', $field_name = '' ) {

	// Bail if we don't have the args, options, or the key.
	if ( empty( $field_args ) || empty( $field_args['options'] ) || empty( $field_key ) ) {
		return;
	}

	// Set my field class, name, and ID.
	$set_field_class    = 'woo-better-reviews-sorting-select-field';
	$set_field_class   .= ! empty( $field_args['class'] ) ? ' ' . sanitize_html_class( $field_args['class'] ) : '';

	$set_field_id       = 'woo-better-reviews-sorting-charstcs-' . sanitize_html_class( $field_key );
	$set_field_name     = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-sorting[charstcs][' . sanitize_html_class( $field_key ) . ']';

	// Check for the empty to be included in the data array.
	$set_select_none    = '(' . $field_args['label'] . ')';
	$set_select_options = array( '0' => esc_attr( $set_select_none ) ) + $field_args['options'];

	// Attempt to check for a POSTed value.
	$set_posted_char_id = ! empty( $field_args['id'] ) ? $field_args['id'] : 0;
	$set_posted_value   = ! empty( $_POST['woo-better-reviews-sorting']['charstcs'][ $set_posted_char_id ] ) ? $_POST['woo-better-reviews-sorting']['charstcs'][ $set_posted_char_id ] : '';

	// Set my empty.
	$field  = '';

	// Check for the label first.
	if ( ! empty( $field_args['label'] ) ) {
		$field .= '<label for="' . esc_attr( $set_field_id ) . '" class="woo-better-reviews-sorting-field-label">' . esc_html( $field_args['label'] ) . '</label>';
	}

	// Now set the select tag.
	$field .= '<select id="' . esc_attr( $set_field_id ) . '" name="' . esc_attr( $set_field_name ) . '" class="' . esc_attr( $set_field_class ) . '">';

	// Loop the options.
	foreach ( $set_select_options as $option_value => $option_label ) {
		$field .= '<option value="' . esc_attr( $option_value ) . '" ' . selected(  $set_posted_value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
	}

	// Close the select tag.
	$field .= '</select>';

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_sorting_dropdown_field', $field, $field_args, $field_key, $field_name );
}
