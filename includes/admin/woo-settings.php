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
add_action( 'add_meta_boxes_product', __NAMESPACE__ . '\filter_default_review_metaboxes', 11 );
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
	$removals   = array(
		'woocommerce_enable_review_rating',
		'woocommerce_review_rating_verification_required',
		'woocommerce_review_rating_required',
	);

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

	// Set the anonymous flag for leaving reviews.
	$anon_args  = array(
		'title'           => __( 'Anonymous Reviews', 'woo-better-reviews' ),
		'desc'            => __( 'Allow non-logged in users to leave product reviews.', 'woo-better-reviews' ),
		'id'              => 'woocommerce_wbr_allow_anonymous',
		'default'         => 'yes',
		'type'            => 'checkbox',
		'checkboxgroup'   => '',
		'show_if_checked' => 'yes',
	);

	// Add our custom setting for the anonymous option.
	$settings   = Utilities\array_insert_after( 11, $settings, 'anons', $anon_args );

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
	$settings   = Utilities\array_insert_after( 12, $settings, 'attrib', $prod_args );

	// Return our settings, resetting the indexes.
	return array_values( $settings );
}
