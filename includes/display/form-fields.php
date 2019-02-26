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
 * Set and return the array of possible review statuses.
 *
 * @param  boolean $keys  Whether to return just the keys.
 *
 * @return array
 */
function get_review_form_fields( $keys = false ) {

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
			'min-count'   => 300,
			'max-count'   => 0,
			'description' => '',
		),
	);

	// Set the fields filtered.
	$fields = apply_filters( Core\HOOK_PREFIX . 'review_form_fields', $setup );

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

	// Set my field name and ID.
	$set_id = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_nm = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Set the parts of the input field.
	$iparts = array(
		'type'  => esc_attr( $field_args['type'] ),
		'name'  => esc_attr( $set_nm ),
		'id'    => esc_attr( $set_id ),
		'class' => 'woo-better-reviews-rating-input-field woo-better-reviews-rating-' . esc_attr( $field_args['type'] ) . '-field',
	);

	// Add items to the array if we have them.
	$iparts = ! empty( $field_args['custom'] ) ? wp_parse_args( $field_args['custom'], $iparts ) : $iparts;

	// Set my empty.
	$field  = '';

	// Check for the label first.
	if ( ! empty( $field_args['label'] ) ) {

		// Open the label.
		$field .= '<label for="' . esc_attr( $set_id ) . '" class="woo-better-reviews-rating-field-label">' . esc_html( $field_args['label'] );

		// Include the required portion.
		if ( ! empty( $field_args['required'] ) ) {
			$field .= '<span aria-label="' . esc_attr( __( 'This is a required field', 'woo-better-reviews' ) ) . '">&#8727;</span>';
		}

		// Close my label.
		$field .= '</label>';
	}

	// Now begin the actual field input.
	$field .= '<input';

	// Loop the field parts.
	foreach ( $iparts as $input_key => $input_val ) {
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
	return apply_filters( Core\HOOK_PREFIX . 'review_form_input_field', $field, $field_args, $field_id, $field_name );
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

	// Set my field class, name and ID.
	$set_cl = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-textarea-field';
	$set_id = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_nm = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Get the data pieces for the textarea.
	$pieces = Utilities\format_review_textarea_data( $field_args );
	// preprint( $pieces, true );
	// Set my empty.
	$field  = '';

	// Check for the label first.
	if ( ! empty( $field_args['label'] ) ) {

		// Open the label.
		$field .= '<label for="' . esc_attr( $set_id ) . '" class="woo-better-reviews-rating-field-label">' . esc_html( $field_args['label'] );

		// Include the required portion.
		if ( ! empty( $field_args['required'] ) ) {
			$field .= '<span aria-label="' . esc_attr( __( 'This is a required field', 'woo-better-reviews' ) ) . '">&#8727;</span>';
		}

		// Close my label.
		$field .= '</label>';
	}

	// Now do the actual field.
	$field .= '<textarea id="' . esc_attr( $set_id ) . '" name="' . esc_attr( $set_nm ) . '" class="' . esc_attr( $set_cl ) . '" ' . implode( ' ', $pieces ) . '></textarea>';

	// Check for the description before we finish.
	if ( ! empty( $field_args['description'] ) ) {
		$field .= '<span class="woo-better-reviews-rating-field-description">' . esc_html( $field_args['description'] ) . '</span>';
	}

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_textarea_field', $field, $field_args, $field_id, $field_name );
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

	// Set my field class, name and ID.
	$set_cl = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-dropdown-field';
	$set_id = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_nm = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Check for the empty to be included in the data array.
	$items  = ! empty( $field_args['include-empty'] ) ? array( '0' => __( '(Select)', 'woo-better-reviews' ) ) + $field_args['options'] : $field_args['options'];

	// Set my empty.
	$field  = '';

	// Check for the label first.
	if ( ! empty( $field_args['label'] ) ) {

		// Open the label.
		$field .= '<label for="' . esc_attr( $set_id ) . '" class="woo-better-reviews-rating-field-label">' . esc_html( $field_args['label'] );

		// Include the required portion.
		if ( ! empty( $field_args['required'] ) ) {
			$field .= '<span aria-label="' . esc_attr( __( 'This is a required field', 'woo-better-reviews' ) ) . '">&#8727;</span>';
		}

		// Close my label.
		$field .= '</label>';
	}

	// Now set the select tag.
	$field .= '<select id="' . esc_attr( $set_id ) . '" name="' . esc_attr( $set_nm ) . '" class="' . esc_attr( $set_cl ) . '">';

	// Loop the options.
	foreach ( $items as $option_value => $option_label ) {
		$field .= '<option value="' . esc_attr( $option_value ) . '">' . esc_html( $option_label ) . '</option>';
	}

	// Close the select tag.
	$field .= '</select>';

	// Check for the description before we finish.
	if ( ! empty( $field_args['description'] ) ) {
		$field .= '<span class="woo-better-reviews-rating-field-description">' . esc_html( $field_args['description'] ) . '</span>';
	}

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_dropdown_field', $field, $field_args, $field_id, $field_name );
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

	// Set my field class, name and ID.
	$set_cl = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-radio-field';
	$set_id = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_nm = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Set my empty.
	$field  = '';

	// Do.
	$field .= '';

	// Check for the description before we finish.
	if ( ! empty( $field_args['description'] ) ) {
		$field .= '<span class="woo-better-reviews-rating-field-description">' . esc_html( $field_args['description'] ) . '</span>';
	}

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_radio_field', $field, $field_args, $field_id, $field_name );
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

	// Set my field class, name and ID.
	$set_cl = 'woo-better-reviews-rating-input-field woo-better-reviews-rating-editor-minimal-field';
	$set_id = ! empty( $field_id ) ? $field_id : 'woo-better-reviews-rating-field-' . sanitize_html_class( $field_key );
	$set_nm = ! empty( $field_name ) ? $field_name : 'woo-better-reviews-rating[' . sanitize_html_class( $field_key ) . ']';

	// Set my empty.
	$field  = '';

	// Do.
	$field .= '';

	// Check for the description before we finish.
	if ( ! empty( $field_args['description'] ) ) {
		$field .= '<span class="woo-better-reviews-rating-field-description">' . esc_html( $field_args['description'] ) . '</span>';
	}

	// Return the field, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_form_editor_minimal_field', $field, $field_args, $field_id, $field_name );
}
