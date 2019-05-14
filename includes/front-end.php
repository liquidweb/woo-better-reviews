<?php
/**
 * Handle some front-end functionality.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\FrontEnd;

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
add_action( 'comments_template', __NAMESPACE__ . '\load_review_template', 99 );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\load_review_front_stylesheet' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\load_review_front_javascript' );

/**
 * Load our own review template from the plugin.
 *
 * @param  string $default_template  The file currently set to load.
 *
 * @return string
 */
function load_review_template( $default_template ) {

	// Bail if this isn't a product.
	if ( ! is_singular( 'product' ) ) {
		return $default_template;
	}

	// Set our template file, allowing themes and plugins to set their own.
	$custom_template    = apply_filters( Core\HOOK_PREFIX . 'review_template_file', Core\TEMPLATE_PATH . '/single-product-reviews.php' );

	// Return ours (if it exists) or whatever we had originally.
	return ! empty( $custom_template ) && file_exists( $custom_template ) ? $custom_template : $default_template;
}

/**
 * Load our front-end side CSS.
 *
 * @return void
 */
function load_review_front_stylesheet() {

	// First check if we wanna bypass the entire thing.
	if ( false !== $disable_css = apply_filters( Core\HOOK_PREFIX . 'disable_stylesheet_load', false ) ) {
		return;
	}

	// Bail if we aren't on a single product.
	if ( ! is_singular( 'product' ) ) {
		return;
	}

	// Run the check if we're enabled or not.
	$maybe_enabled  = Helpers\maybe_reviews_enabled( get_the_ID() );

	// Bail if we aren't enabled.
	if ( ! $maybe_enabled ) {
		return;
	}

	// Set my handle.
	$handle = 'woo-better-reviews-front';

	// Set a file suffix structure based on whether or not we want a minified version.
	$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $handle : $handle . '.min';

	// Set a version for whether or not we're debugging.
	$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : Core\VERS;

	// Load our CSS file.
	wp_enqueue_style( $handle, Core\ASSETS_URL . '/css/' . $file . '.css', array( 'dashicons' ), $vers, 'all' );

	// Include our action let others load things.
	do_action( Core\HOOK_PREFIX . 'after_front_stylesheet_load', $handle );
}

/**
 * Load our front-end side JS.
 *
 * @return void
 */
function load_review_front_javascript() {

	// First check if we wanna bypass the entire thing.
	if ( false !== $disable_js = apply_filters( Core\HOOK_PREFIX . 'disable_javascript_load', false ) ) {
		return;
	}

	// Bail if we aren't on a single product.
	if ( ! is_singular( 'product' ) ) {
		return;
	}

	// Run the check if we're enabled or not.
	$maybe_enabled  = Helpers\maybe_reviews_enabled( get_the_ID() );

	// Bail if we aren't enabled.
	if ( ! $maybe_enabled ) {
		return;
	}

	// Set my handle.
	$handle = 'woo-better-reviews-front';

	// Set a file suffix structure based on whether or not we want a minified version.
	$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $handle : $handle . '.min';

	// Set a version for whether or not we're debugging.
	$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : Core\VERS;

	// And our JS.
	wp_enqueue_script( $handle, Core\ASSETS_URL . '/js/' . $file . '.js', array( 'jquery' ), $vers, true );

	// Include our action let others load things.
	do_action( Core\HOOK_PREFIX . 'after_front_javascript_load', $handle );
}
