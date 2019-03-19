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
use LiquidWeb\WooBetterReviews\Queries as Queries;

/**
 * Increment the review count by 1.
 *
 * @param  integer $product_id  The product ID we are adding.
 *
 * @return void
 */
function increment_product_review_count( $product_id = 0 ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Get the count.
	$current_count  = get_post_meta( $product_id, Core\META_PREFIX . 'review_count', true );

	// Do the increment.
	$update_count   = ! empty( $current_count ) ? absint( $current_count ) + 1 : 1;

	// Update the Woo postmeta key.
	update_post_meta( $product_id, '_wc_review_count', $update_count );

	// Update our own post meta key as well.
	update_post_meta( $product_id, Core\META_PREFIX . 'review_count', $update_count );
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
 * Pull out the data inside an charstcs and make it nice.
 *
 * @param  array $charstcs_array  The attributes from the query.
 *
 * @return array
 */
function format_charstcs_display_data( $charstcs_array ) {

	// Make sure we have args.
	if ( empty( $charstcs_array ) ) {
		return;
	}

	// Set the empty.
	$setup  = array();

	// Loop and check.
	foreach ( $charstcs_array as $index => $charstcs_args ) {
		// preprint( $charstcs_args, true );

		// Now we loop each attribute.
		foreach ( $charstcs_args as $charstcs_key => $charstcs_value ) {

			// Set my new array key.
			$array_key  = str_replace( 'charstcs_', '', $charstcs_key );

			// Now set our array.
			$setup[ $index ][ $array_key ] = maybe_unserialize( $charstcs_value );
		}

		// Nothing else (I think?) inside this array.
	}

	// Return the array.
	return $setup;
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

	// Bail without a review.
	if ( empty( $review ) ) {
		return;
	}

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

	// Bail without a review.
	if ( empty( $review ) ) {
		return;
	}

	// Set the empty for scoring.
	$setup  = array();

	// Check and modify the overall total.
	if ( isset( $review['rating_total_score'] ) ) {

		// Add the item.
		$setup['total_score'] = $review['rating_total_score'];

		// And unset the old.
		unset( $review['rating_total_score'] );
	}

	// Check for the attributes kept.
	if ( isset( $review['rating_attributes'] ) ) {

		// Pull out the attributes.
		$attributes = maybe_unserialize( $review['rating_attributes'] );
		// preprint( $attributes, true );

		// Our scoring data has 3 pieces.
		foreach ( $attributes as $attribute_id => $attribute_score ) {

			// Pull my attribute data.
			$attribute_data = Queries\get_single_attribute( $attribute_id );
			// preprint( $attribute_data, true );

			// Now set the array accordingly.
			$setup['rating_attributes'][ $attribute_id ] = array(
				'label' => $attribute_data['attribute_name'],
				'value' => $attribute_score,
			);

			// Nothing left with each attribute.
		}

		// And unset the old.
		unset( $review['rating_attributes'] );
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
function format_review_author_charstcs( $review ) {

	// Bail without a review.
	if ( empty( $review ) || empty( $review['author_charstcs'] ) ) {
		return;
	}

	// Set the empty.
	$setup  = array();

	// Pull out the charstcs.
	$charstcs   = maybe_unserialize( $review['author_charstcs'] );
	// preprint( $charstcs, true );

	// Our scoring data has 3 pieces.
	foreach ( $charstcs as $charstcs_id => $charstcs_slug ) {

		// Pull my charstcs data.
		$charstcs_data  = Queries\get_single_charstcs( $charstcs_id );
		$charstcs_vals  = maybe_unserialize( $charstcs_data['charstcs_values'] );

		// Now set the array accordingly.
		$setup['author_charstcs'][ $charstcs_id ] = array(
			'label' => $charstcs_data['charstcs_name'],
			'value' => $charstcs_vals[ $charstcs_slug ],
		);

		// Nothing left with each attribute.
	}

	// And unset the old.
	unset( $review['author_charstcs'] );

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
		$array_key  = str_replace( $checks, array( 'consolidated_id', 'review_id', 'product_id', 'status', 'verified' ), $check );

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

	// Bail without a review.
	if ( empty( $review ) ) {
		return;
	}

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
		$class_prefix . '-author-' . absint( $review['author_id'] ),
		$class_prefix . '-product-' . absint( $review['product_id'] ),
		$class_prefix . '-rating-' . absint( $review['total_score'] ),
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

/**
 * Set a buffered editor output.
 *
 * @param string  $editor_id     The ID of the editor form.
 * @param string  $editor_name   The field name for the editor.
 * @param string  $editor_class  Optional class to include.
 * @param array   $custom_args   Any other custom args.
 *
 * @return HTML
 */
function set_review_form_editor( $editor_id = '', $editor_name = '', $editor_class = '', $custom_args = array() ) {

	// Bail if we're missing anything.
	if ( empty( $editor_id ) || empty( $editor_name ) ) {
		return;
	}

	// Set the editor args.
	$setup_args = array(
		'wpautop'          => false,
		'media_buttons'    => false,
		'tinymce'          => false,
		'teeny'            => true,
		'textarea_rows'    => 10,
		'textarea_name'    => $editor_name,
		'editor_class'     => $editor_class,
		'drag_drop_upload' => false,
		'quicktags'        => array(
			'buttons' => 'strong,em,ul,ol,li'
		),
	);

	// Pass our custom args if we have them.
	$setup_args = ! empty( $custom_args ) ? wp_parse_args( $custom_args, $setup_args ) : $setup_args;

	// We have to buffer the editor output.
	ob_start();

	// Now handle the editor getting buffered.
	wp_editor( '', $editor_id, $setup_args );

	// Now just return it.
	return ob_get_clean();
}

/**
 * Purge one or many transients based on what's happening.
 *
 * @param  string $key     A single transient key.
 * @param  string $group   A group of transients.
 * @param  array  $custom  Any custom args tied to a group.
 *
 * @return void
 */
function purge_transients( $key = '', $group = '', $custom = array() ) {

	// Allow others to pop in before.
	do_action( Core\HOOK_PREFIX . 'before_transient_purge', $key, $group, $custom );

	// If we have a single key, handle it.
	if ( ! empty( $key ) ) {
		delete_transient( $key );
	}

	// Handle groups.
	if ( ! empty( $group ) ) {

		// Now switch between my return types.
		switch ( sanitize_text_field( $group ) ) {

			// Handle attributes.
			case 'attributes' :

				// Check the custom args.
				$attrib_id  = ! empty( $custom['attribute-id'] ) ? absint( $custom['attribute-id'] ) : 0;
				$product_id = ! empty( $custom['product-id'] ) ? absint( $custom['product-id'] ) : 0;

				// Start deleting.
				delete_transient( Core\HOOK_PREFIX . 'all_attributes' );
				delete_transient( Core\HOOK_PREFIX . 'single_attribute_' . absint( $attrib_id ) );
				delete_transient( Core\HOOK_PREFIX . 'attributes_product' . absint( $product_id ) );

				// And done.
				break;

			// Handle characteristics.
			case 'charstcs' :

				// Check the custom args.
				$author_id  = ! empty( $custom['author-id'] ) ? absint( $custom['author-id'] ) : 0;
				$charstc_id = ! empty( $custom['charstcs-id'] ) ? absint( $custom['charstcs-id'] ) : 0;

				// Start deleting.
				delete_transient( Core\HOOK_PREFIX . 'all_charstcs' );
				delete_transient( Core\HOOK_PREFIX . 'charstcs_author' . absint( $author_id ) );
				delete_transient( Core\HOOK_PREFIX . 'single_charstcs_' . absint( $charstc_id ) );

				// And done.
				break;

			// Handle reviews.
			case 'reviews' :

				// Check the custom args.
				$author_id  = ! empty( $custom['author-id'] ) ? absint( $custom['author-id'] ) : 0;
				$product_id = ! empty( $custom['product-id'] ) ? absint( $custom['product-id'] ) : 0;

				// Start deleting.
				delete_transient( Core\HOOK_PREFIX . 'verifed_reviews' );
				delete_transient( Core\HOOK_PREFIX . 'cnsldtd_reviews' );
				delete_transient( Core\HOOK_PREFIX . 'legacy_review_counts' );
				delete_transient( Core\HOOK_PREFIX . 'reviews_for_author_' . absint( $author_id ) );
				delete_transient( Core\HOOK_PREFIX . 'reviews_for_product_' . absint( $product_id ) );
				delete_transient( Core\HOOK_PREFIX . 'cnsldtd_reviews_for_product_' . absint( $product_id ) );
				delete_transient( Core\HOOK_PREFIX . 'review_count_product' . absint( $product_id ) );

				// And done.
				break;

			// No more case breaks, no more return types.
		}

		// Do an action after the group break.
		do_action( Core\HOOK_PREFIX . 'transient_purge_group', $group, $custom );
	}

	// Allow others to pop in after.
	do_action( Core\HOOK_PREFIX . 'after_transient_purge', $key, $group, $custom );
}
