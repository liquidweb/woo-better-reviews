<?php
/**
 * Our utility functions to use across the plugin.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Utilities;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;

/**
 * Take the review object array and merge the taxonomies.
 *
 * @param  array $reviews  The review objects from the query.
 *
 * @return array
 */
function merge_review_object_taxonomies( $reviews ) {

	// Bail with no reviews.
	if ( empty( $reviews ) ) {
		return false;
	}

	// Set the initial empty.
	$merged = array();

	// Now loop and do the things.
	foreach ( $reviews as $object ) {

		// Set the ID.
		$id = $object->con_id;

		// Set the key to use in our transient.
		$ky = Core\HOOK_PREFIX . 'reviews_for_product_' . absint( $id );

		// If we don't want the cache'd version, delete the transient first.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			delete_transient( $ky );
		}

		// Attempt to get the reviews from the cache.
		$cached_dataset = get_transient( $ky );

		// If we have none, do the things.
		if ( false === $cached_dataset ) {

			// Cast it as an array.
			$review = (array) $object;
			// preprint( $review, true );

			// Pass it into the content setup.
			$review = format_review_content_data( $review );
			// preprint( $review, true );

			// Pass it into the scoring setup.
			$review = format_review_scoring_data( $review );
			// preprint( $review, true );

			// Pass it into the author setup.
			$review = format_review_author_data( $review );
			// preprint( $review, true );

			// Pass it into the meta setup.
			$review = format_review_meta_data( $review );
			// preprint( $review, true );

			// Set our transient with our data.
			set_transient( $ky, $review, DAY_IN_SECONDS );

			// And change the variable to do the things.
			$cached_dataset = $review;
		}

		// And now merge the data.
		$merged[ $id ] = $cached_dataset;

		// Should be nothing left inside the loop.
	}

	// Return our large merged data.
	return $merged;
}

/**
 * Take the potentially values and format a nice list.
 *
 * @param  mixed  $values   The values, perhaps serialized.
 * @param  string $display  How to display the values.
 *
 * @return HTML
 */
function format_array_values_display( $values, $display = 'breaks' ) {

	// Bail without values to work with.
	if ( empty( $values ) ) {
		return false;
	}

	// Set up the array to begin.
	$setup_format   = maybe_unserialize( $values );

	// Bail without formatted to work with.
	if ( empty( $setup_format ) || ! is_array( $setup_format ) ) {
		return false;
	}

	// Sanitize each one.
	$setup_values   = array_map( 'esc_attr', $setup_format );

	// Handle my different error codes.
	switch ( esc_attr( $display ) ) {

		case 'breaks' :

			// Return them, imploded with a line break.
			return implode( '<br>', $setup_values );
			break;

		case 'list' :

			// Return them, imploded in a nice list.
			return '<ul class="woo-better-reviews-admin-table-list"><li>' . implode( '</li><li>', $setup_values ) . '</li></ul>';
			break;

		case 'inline' :

			// Return them, imploded with a comma.
			return implode( ', ', $setup_values );
			break;

		// End all case breaks.
	}

	// Nothing remaining on the formatting.
}

/**
 * Take the array of labels and make save-able keys.
 *
 * @param  mixed   $labels     The value labels.
 * @param  boolean $serialize  Whether we return it serialized.
 *
 * @return mixed
 */
function format_string_values_array( $labels, $serialize = true ) {

	// Make sure we have labels.
	if ( empty( $labels ) ) {
		return false;
	}

	// Make sure it's an array.
	$label_args = ! is_array( $labels ) ? explode( ',', $labels ) : $labels;

	// Set an empty.
	$dataset    = array();

	// Now loop the labels and do some cleanup.
	foreach ( $label_args as $label ) {

		// Set the key.
		$ky = sanitize_title_with_dashes( trim( $label ), '', 'save' );

		// And make some data.
		$dataset[ $ky ] = sanitize_text_field( $label );
	}

	// Return it one way or the other.
	return ! $serialize ? $dataset : maybe_serialize( $dataset );
}

/**
 * Pull out the data inside an attribute and make it nice.
 *
 * @param  array $attribute_array  The attributes from the query.
 *
 * @return array
 */
function format_attribute_display_data( $attribute_array ) {

	// Make sure we have args.
	if ( empty( $attribute_array ) ) {
		return;
	}

	// Set the empty.
	$setup  = array();

	// Loop and check.
	foreach ( $attribute_array as $index => $attribute_args ) {
		// preprint( $attribute_args, true );

		// Now we loop each attribute.
		foreach ( $attribute_args as $attribute_key => $attribute_value ) {
			// preprint( $attribute_key, true );

			// First check for labels.
			if ( in_array( $attribute_key, array( 'min_label', 'max_label' ) ) ) {

				// A placeholder for now until I figure out how to merge them.
				$array_key  = $attribute_key;

			} else {

				// Set my new array key.
				$array_key  = str_replace( 'attribute_', '', $attribute_key );
			}

			// Now set our array.
			$setup[ $index ][ $array_key ] = $attribute_value;
		}

		// Nothing else (I think?) inside this array.
	}

	// Return the array.
	return $setup;
}

/**
 * Make the labeled key/value pair.
 *
 * @param  array $attribute_args  The arguments for single attributes.
 *
 * @return array
 */
function format_attribute_dropdown_data( $attribute_args = array() ) {

	// Make sure we have args.
	if ( empty( $attribute_args ) ) {
		return;
	}

	// Set our empty array.
	$field_options  = array();

	// Make the one-seven check.
	for ( $i = 1; $i <= 7; $i++ ) {

		// Create the label.
		switch ( $i ) {

			case 1 :

				// Return them, imploded with a line break.
				$display_label  = ! empty( $attribute_args['min_label'] ) ? $i . ' (' . esc_attr( $attribute_args['min_label'] ) . ')' : $i;
				break;

			case 7 :

				// Set the label if we have a max-label.
				$display_label  = ! empty( $attribute_args['max_label'] ) ? $i . ' (' . esc_attr( $attribute_args['max_label'] ) . ')' : $i;
				break;

			default :

				// Set the basic label.
				$display_label  = $i;
				break;

			// End all case breaks.
		}

		// Make the option.
		$field_options[ $i ] = $display_label;
	}

	// And return it.
	return $field_options;
}

/**
 * Get the various options for a textarea.
 *
 * @param  array $field_args  The arguments for single attributes.
 *
 * @return array
 */
function format_review_textarea_data( $field_args = array() ) {

	// Make sure we have args.
	if ( empty( $field_args ) ) {
		return;
	}

	// Set our initial array.
	$field_options  = array( 'spellcheck="true"' );

	// Check for required.
	if ( ! empty( $field_args['required'] ) ) {
		$field_options[] = 'required="required"';
	}

	// Check for minimum length.
	if ( ! empty( $field_args['min-count'] ) ) {
		$field_options[] = 'minlength="' . absint( $field_args['min-count'] ) . '"';
	}

	// Check for maximum length.
	if ( ! empty( $field_args['max-count'] ) ) {
		$field_options[] = 'maxlength="' . absint( $field_args['max-count'] ) . '"';
	}

	// And return it.
	return $field_options;
}

/**
 * Pull out the content data and make it nice.
 *
 * @param  array $review  The review from the query.
 *
 * @return array
 */
function format_review_content_data( $review ) {

	// Set the empty.
	$setup  = array();

	// Set the array of what to check.
	$checks = array(
		'review_date',
		'review_slug',
		'review_title',
		'review_summary',
		'review_content',
	);

	// Loop and check.
	foreach ( $checks as $check ) {

		// Skip if not there.
		if ( ! isset( $review[ $check ] ) ) {
			continue;
		}

		// Make my array key.
		$array_key  = 'review_content' !== $check ? str_replace( 'review_', '', $check ) : 'review';

		// Add the item.
		$setup[ $array_key ] = $review[ $check ];

		// And unset the review parts.
		unset( $review[ $check ] );
	}

	// Return the array.
	return wp_parse_args( $setup, $review );
}

/**
 * Pull out the scoring data and make it nice.
 *
 * @param  array $review  The review from the query.
 *
 * @return array
 */
function format_review_scoring_data( $review ) {

	// Set the empty for scoring.
	$setup  = array();

	// Check and modify the overall total.
	if ( isset( $review['rating_total_score'] ) ) {

		// Add the item.
		$setup['total-score'] = $review['rating_total_score'];

		// And unset the old.
		unset( $review['rating_total_score'] );
	}

	// Our scoring data has 3 pieces.
	for ( $i = 1; $i <= 3; $i++ ) {

		// Set the key to pull out the attribute.
		$array_arg  = 'rating_' . absint( $i ) . '_attrib';
		$array_key  = $review[ $array_arg ];

		// Set the key for getting the value.
		$value_arg  = 'rating_' . absint( $i ) . '_score';

		// Now set the array accordingly.
		$setup['rating-attributes'][ $i ] = array(
			'label' => $array_key,
			'value' => $review[ $value_arg ],
		);

		// And unset the review parts.
		unset( $review[ $array_arg ] );
		unset( $review[ $value_arg ] );
	}

	// Return the array.
	return wp_parse_args( $setup, $review );
}

/**
 * Pull out the author data and make it nice.
 *
 * @param  array $review  The review from the query.
 *
 * @return array
 */
function format_review_author_data( $review ) {

	// Set the empty.
	$setup  = array();

	// Check and modify the overall total.
	if ( isset( $review['author_id'] ) ) {

		// Add the item.
		$setup['author-id'] = $review['author_id'];

		// Include some nice author info.
		$setup['author-name']   = get_the_author_meta( 'display_name', absint( $review['author_id'] ) );
		$setup['author-email']  = get_the_author_meta( 'user_email', absint( $review['author_id'] ) );
		$setup['author-avatar'] = get_avatar_url( absint( $review['author_id'] ) );

		// And unset the old.
		unset( $review['author_id'] );
	}

	// Our author data has 4 pieces.
	for ( $i = 1; $i <= 4; $i++ ) {

		// Set the key to pull out the attribute.
		$array_arg  = 'author_char_' . absint( $i ) . '_label';
		$array_key  = $review[ $array_arg ];

		// Set the key for getting the value.
		$value_arg  = 'author_char_' . absint( $i ) . '_value';

		// Now set the array accordingly.
		$setup['author-charstcs'][ $i ] = array(
			'label' => $array_key,
			'value' => $review[ $value_arg ],
		);

		// And unset the parts.
		unset( $review[ $array_arg ] );
		unset( $review[ $value_arg ] );
	}

	// Return the array.
	return wp_parse_args( $setup, $review );
}

/**
 * Pull out the meta data and make it nice.
 *
 * @param  array $review  The review from the query.
 *
 * @return array
 */
function format_review_meta_data( $review ) {

	// Set the empty.
	$setup  = array();

	// Set the array of what to check.
	$checks = array(
		'con_id',
		'review_id',
		'product_id',
		'review_status',
		'is_verified',
	);

	// Loop and check.
	foreach ( $checks as $check ) {

		// Skip if not there.
		if ( ! isset( $review[ $check ] ) ) {
			continue;
		}

		// Make my array key.
		$array_key  = str_replace( $checks, array( 'consolidated-id', 'review-id', 'product-id', 'status', 'verified' ), $check );

		// Add the item.
		$setup[ $array_key ] = $review[ $check ];

		// And unset the review parts.
		unset( $review[ $check ] );
	}

	// Return the array.
	return wp_parse_args( $setup, $review );
}

/**
 * Set a div class for each of our displayed reviews.
 *
 * @param  array   $review  The data tied to the review.
 * @param  integer $index   What index order (count) we are in the list.
 *
 * @return string
 */
function set_single_review_div_class( $review = array(), $index = 0 ) {

	// Set our base class, which is also the prefix for all the others.
	$class_prefix   = 'woo-better-reviews-single-review';

	// Return the default if no review object exists.
	if ( empty( $review ) ) {
		return $class_prefix;
	}

	// Start by setting our default class and classes based on static items in the object.
	$classes    = array(
		$class_prefix,
		$class_prefix . '-display-block',
		$class_prefix . '-author-' . absint( $review['author-id'] ),
		$class_prefix . '-product-' . absint( $review['product-id'] ),
		$class_prefix . '-rating-' . absint( $review['total-score'] ),
		$class_prefix . '-status-' . esc_attr( $review['status'] ),
	);

	// Check for verified.
	if ( ! empty( $review['verified'] ) ) {
		$classes[]  = 'woo-better-reviews-single-review-verified';
	}

	// Check the index for even / odd.
	$classes[]  = absint( $index ) & 1 ? $class_prefix . '-odd' : $class_prefix . '-even';

	// Now pass them through a filter before we implode.
	$array_args = apply_filters( Core\HOOK_PREFIX . 'single_review_div_classes', $classes, $review, $index );

	// If they are an idiot and blanked it out, return the original.
	if ( empty( $array_args ) ) {
		return $class_prefix;
	}

	// Now sanitize each piece.
	$array_args = array_map( 'sanitize_html_class', $array_args );

	// Return, imploded.
	return implode( ' ', $array_args );
}
