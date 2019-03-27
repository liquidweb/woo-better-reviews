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
add_action( 'save_post_product', __NAMESPACE__ . '\save_product_attributes', 10, 2 );

/**
 * Load the metabox for applying review attributes.
 *
 * @param  object $post  The WP_Post object.
 *
 * @return void
 */
function load_attribute_metabox( $post ) {

	// Run the check if we're enabled or not.
	$maybe_enabled  = Helpers\maybe_reviews_enabled( $post->ID );

	// Bail if we aren't enabled.
	if ( ! $maybe_enabled ) {
		return;
	}

	// Create some easy setup args to pass.
	$setup_args = array(
		'global'     => Helpers\maybe_attributes_global(),
		'attributes' => Queries\get_all_attributes( 'names' ),
		'selected'   => Helpers\get_selected_product_attributes( $post->ID ),
	);

	// Call the actual metabox.
	add_meta_box( 'wbr-attribute-metabox', __( 'Review Attributes', 'woo-better-reviews' ), __NAMESPACE__ . '\attribute_metabox', 'product', 'side', 'core', $setup_args );
}

/**
 * Build and display the metabox for applying review attributes.
 *
 * @param  object $post      The WP_Post object.
 * @param  array  $callback  The custom callback args.
 *
 * @return void
 */
function attribute_metabox( $post, $callback ) {

	// If none exist, show the message and bail.
	if ( empty( $callback['args']['attributes'] ) ) {

		// Do the message.
		echo '<p class="description">' . __( 'No product attributes have been created yet.', 'woo-better-reviews' ) . '</p>';

		// And be done.
		return;
	}

	// If they are global, just message.
	if ( ! empty( $callback['args']['global'] ) ) {

		// Do the message.
		echo '<p class="description">' . __( 'Product attributes have been enabled globally by the site administrator.', 'woo-better-reviews' ) . '</p>';

		// And be done.
		return;
	}

	// Get my selected items.
	$selected   = ! empty( $callback['args']['selected'] ) ? $callback['args']['selected'] : array();

	// Begin the markup for an unordered list.
	echo '<ul class="woo-better-reviews-product-attribute-list">';

	// Now loop my attributes to create my checkboxes.
	foreach ( $callback['args']['attributes'] as $attribute_id => $attribute_name ) {

		// Set the field name and ID.
		$field_name = 'wbr-product-attributes[]';
		$field_id   = 'wbr-product-attributes-' . absint( $attribute_id );

		// Determine if it's checked or not.
		$is_checked = in_array( $attribute_id, (array) $selected ) ? 'checked="checked"' : '';

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

	// Include a blank trigger field.
	echo '<input type="hidden" name="wbr-product-meta-trigger" value="1">';

	// Gimme some sweet nonce action.
	echo wp_nonce_field( 'wbr_save_product_meta_action', 'wbr_save_product_meta_nonce', false, false );
}

/**
 * Save the assigned product attributes.
 *
 * @param  integer $post_id  The individual post ID.
 * @param  object  $post     The entire post object.
 *
 * @return void
 */
function save_product_attributes( $post_id, $post ) {

	// Do the constants check.
	$check_constant = Utilities\check_constants_for_process();

	// Bail out if we hit a constant.
	if ( false === $check_constant ) {
		return;
	}

	// Run the check if we're enabled or not.
	$maybe_enabled  = Helpers\maybe_reviews_enabled( $post_id );

	// Bail if we aren't enabled.
	if ( false === $maybe_enabled ) {
		return;
	}

	// Check for the global setting.
	$maybe_global   = Helpers\maybe_attributes_global();

	// If we are global, send the whole bunch.
	if ( false !== $maybe_global ) {
		return;
	}

	// Check for the triggr.
	if ( empty( $_POST['wbr-product-meta-trigger'] ) ) {
		return;
	}

	// Do our nonce check. ALWAYS A NONCE CHECK.
	if ( empty( $_POST['wbr_save_product_meta_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_save_product_meta_nonce'], 'wbr_save_product_meta_action' ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Make sure we have the cap.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( __( 'You do not have the capability to perform this action.', 'woo-better-reviews' ) );
	}

	// Run the before action.
	do_action( Core\HOOK_PREFIX . 'before_product_meta_save' );

	// Check for the attributes being posted.
	$maybe_attributes   = ! empty( $_POST['wbr-product-attributes'] ) ? array_map( 'absint', $_POST['wbr-product-attributes'] ) : array();

	// Now update the array.
	update_post_meta( $post_id, Core\META_PREFIX . 'product_attributes', $maybe_attributes );

	// Handle some transient purging.
	Utilities\purge_transients( Core\HOOK_PREFIX . 'attributes_product' . $post_id );

	// Run the after action.
	do_action( Core\HOOK_PREFIX . 'after_product_meta_save' );
}
