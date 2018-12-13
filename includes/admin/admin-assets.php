<?php
/**
 * Handle the various assets being loaded in the admin.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Admin\AdminAssets;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;

/**
 * Start our engines.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_attribute_assets' );

/**
 * Load our admin side JS and CSS.
 *
 * @param $hook  Admin page hook we are current on.
 *
 * @return void
 */
function load_attribute_assets( $hook ) {

	// Confirm we are on the correct hook.
	if ( 'reviews_page_woo-better-reviews-product-attributes' !== esc_attr( $hook ) ) {
		return;
	}

	// Set my handle.
	$handle = 'woo-better-reviews-admin';

	// Set a file suffix structure based on whether or not we want a minified version.
	$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $handle : $handle . '.min';

	// Set a version for whether or not we're debugging.
	$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : Core\VERS;

	// Load our CSS file.
	wp_enqueue_style( $handle, Core\ASSETS_URL . '/css/' . $file . '.css', false, $vers, 'all' );
}
