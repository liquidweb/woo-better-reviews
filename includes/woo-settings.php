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
use LiquidWeb\WooBetterReviews\Queries as Queries;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'add_meta_boxes_product', __NAMESPACE__ . '\filter_default_review_metaboxes', 11 );
add_action( 'save_post_product', __NAMESPACE__ . '\update_product_review_count_meta', 88, 3 );
add_filter( 'woocommerce_products_general_settings', __NAMESPACE__ . '\filter_woo_admin_review_settings', 99 );

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
 * Make sure the review count stored in the post meta key is up to date.
 *
 * @param integer  $post_id  The product ID being saved.
 * @param object   $post     The entire WP_Post object.
 * @param boolean  $update   Whether this is an existing post being updated or not.
 *
 * @return null
 */
function update_product_review_count_meta( $post_id, $post, $update ) {

	// Make sure we have the product ID and it exists.
	if ( empty( $post_id ) || 'product' !== get_post_type( $post_id ) ) {
		return;
	}

	// Get the total count of reviews we have.
	$total  = Queries\get_review_count_for_product( $post_id );

	// Set the count with some error checking.
	$count  = ! empty( $total ) && ! is_wp_error( $total ) ? absint( $total ) : 0;

	// Update the Woo postmeta key.
	update_post_meta( $post_id, '_wc_review_count', $count );

	// @@todo include our own key as well?

	// And return.
	return;
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
	$remove = array( 'woocommerce_enable_review_rating', 'woocommerce_review_rating_required' );

	// Now loop our settings and modify the items we want.
	foreach ( $settings as $index => $field_args ) {

		// Since we only care about IDs to check, skip if none is there.
		if ( empty( $field_args['id'] ) ) {
			continue;
		}

		// Remove the question about stars.
		if ( in_array( sanitize_text_field( $field_args['id'] ), $remove ) ) {
			unset( $settings[ $index ] );
		}

		// Change the label for enabling reviews.
		if ( 'woocommerce_enable_reviews' === sanitize_text_field( $field_args['id'] ) ) {
			$settings[ $index ]['desc'] = esc_html__( 'Enable reviews using Woo Better Reviews', 'woo-better-reviews' );
		}
	}

	// Return our settings, resetting the indexes.
	return array_values( $settings );
}
