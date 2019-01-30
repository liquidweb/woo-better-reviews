<?php
/**
 * Load our WooCommerce specific actions and filters.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Admin\WooSettings;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_filter( 'woocommerce_products_general_settings', __NAMESPACE__ . '\filter_woo_review_settings', 99 );

/**
 * Remove the default review settings to use our own.
 *
 * @return void
 */
function filter_woo_review_settings( $settings ) {

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
