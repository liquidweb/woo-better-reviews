<?php
/**
 * Handle the product attribute meta assignments.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Admin\ProductMeta;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'add_meta_boxes_product', __NAMESPACE__ . '\load_attribute_metabox' );

/**
 * Load the metabox for applying review attributes.
 *
 * @param  object $post  The WP_Post object.
 *
 * @return void
 */
function load_attribute_metabox( $post ) {

	// Call the actual metabox.
	add_meta_box( 'wbr-product-attributes', __( 'Review Attributes', 'woo-better-reviews' ), __NAMESPACE__ . '\attribute_metabox', 'product', 'side', 'core' );
}

/**
 * Build and display the metabox for applying review attributes.
 *
 * @param  object $post  The WP_Post object.
 *
 * @return void
 */
function attribute_metabox( $post ) {

	// Get my attributes first.
	$all_attributes     = Queries\get_all_attributes( 'names' );
	// preprint( $all_attributes, true );

	// If none exist, show the message and bail.
	if ( empty( $all_attributes ) ) {

		// Do the message.
		echo '<p>' . __( 'No product attributes have been created yet.', 'woo-better-reviews' ) . '</p>';

		// And be done.
		return;
	}

	// Get my selected items.
	$maybe_attributes   = Helpers\get_selected_product_attributes( $post->ID );

	// Begin the markup for an unordered list.
	echo '<ul class="woo-better-reviews-product-attribute-list">';

	// Now loop my attributes to create my checkboxes.
	foreach ( $all_attributes as $attribute_id => $attribute_name ) {

		// Set the field name and ID.
		$field_name = 'wbr-product-attributes[]';
		$field_id   = 'wbr-product-attributes-' . absint( $attribute_id );

		// Determine if it's checked or not.
		$is_checked = in_array( $attribute_id, (array) $maybe_attributes ) ? 'checked="checked"' : '';

		// Echo the markup.
		echo '<li class="woo-better-reviews-single-product-attribute">';

			// Do the label.
			echo '<label for="' . esc_attr( $field_id ) . '">';

				// Output the checkbox.
				echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . absint( $attribute_id ) . '" ' . $is_checked . ' />';

				// Output the actual text.
				echo '&nbsp;' . esc_html( $attribute_name );

			// And close the label.
			echo  '</label>';

		echo '</li>';
	}

	// Close up the markup.
	echo '</ul>';

	// Gimme some sweet nonce action.
}
