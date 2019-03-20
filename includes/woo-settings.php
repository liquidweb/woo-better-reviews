<?php
/**
 * Load our WooCommerce specific actions and filters.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\WooSettings;

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
//add_action( 'load-post.php', __NAMESPACE__ . '\update_product_review_count_meta', 88 );
add_action( 'add_meta_boxes_product', __NAMESPACE__ . '\filter_default_review_metaboxes', 11 );
add_filter( 'woocommerce_products_general_settings', __NAMESPACE__ . '\filter_woo_admin_review_settings', 99 );

/**
 * Make sure the review count stored in the post meta key is up to date.
 *
 * @return void
 */
function update_product_review_count_meta() {

	// Make sure we have the product ID and it exists.
	if ( empty( $_GET['post'] ) || 'product' !== get_post_type( $_GET['post'] ) ) {
		return;
	}

	// Set my product ID.
	$product_id = absint( $_GET['post'] );

	// Get the total count of reviews we have, making sure to purge.
	$total_num  = Queries\get_review_count_for_product( $product_id, true );

	// Set the count with some error checking.
	$count_num  = ! empty( $total_num ) && ! is_wp_error( $total_num ) ? absint( $total_num ) : 0;

	// Update the Woo postmeta key.
	update_post_meta( $product_id, '_wc_review_count', $count_num );

	// Update our own post meta key as well.
	update_post_meta( $product_id, Core\META_PREFIX . 'review_count', $count_num );

	// And return.
	return;
}

/**
 * Removes the default reviews metabox in leiu of our own.
 *
 * @param object $post  The entire WP_Post object.
 *
 * @return void
 */
function filter_default_review_metaboxes( $post ) {

	// This is the box being removed.
	remove_meta_box( 'commentsdiv', 'product', 'normal' );

	// @@todo this will eventually load ours.
}

/**
 * Remove the default review settings to use our own.
 *
 * @return void
 */
function filter_woo_admin_review_settings( $settings ) {

	// If we have no settings (somehow), bail.
	if ( empty( $settings ) ) {
		return $settings;
	}
	// preprint( $settings, true );

	// Set my removes.
	$removals   = array( 'woocommerce_enable_review_rating', 'woocommerce_review_rating_required' );

	// Now loop our settings and modify the items we want.
	foreach ( $settings as $index => $field_args ) {

		// Since we only care about IDs to check, skip if none is there.
		if ( empty( $field_args['id'] ) ) {
			continue;
		}

		// Remove the question about stars.
		if ( in_array( sanitize_text_field( $field_args['id'] ), $removals ) ) {
			unset( $settings[ $index ] );
		}

		// Change the label for enabling reviews.
		if ( 'woocommerce_enable_reviews' === sanitize_text_field( $field_args['id'] ) ) {
			$settings[ $index ]['desc'] = esc_html__( 'Enable reviews using Woo Better Reviews', 'woo-better-reviews' );
		}
	}

	// Set the attributes for the product global.
	$prod_args  = array(
		'title'           => __( 'Product Attributes', 'woo-better-reviews' ),
		'desc'            => __( 'Apply attributes to every product.', 'woo-better-reviews' ),
		'id'              => 'woocommerce_wbr_global_attributes',
		'default'         => 'no',
		'type'            => 'checkbox',
		'checkboxgroup'   => '',
		'show_if_checked' => 'yes',
	);

	// Add our custom setting for the global attributes.
	$settings   = Utilities\array_insert_after( 11, $settings, 'attrib', $prod_args );

	// Return our settings, resetting the indexes.
	return array_values( $settings );
}
