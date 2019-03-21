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
add_filter( 'removable_query_args', __NAMESPACE__ . '\admin_removable_args' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_admin_stylesheet' );

/**
 * Add our custom strings to the vars.
 *
 * @param  array $args  The existing array of args.
 *
 * @return array $args  The modified array of args.
 */
function admin_removable_args( $args ) {

	// Set an array of the args we wanna exclude.
	$remove = array(
		'wbr-item-type',
		'wbr-action-complete',
		'wbr-action-result',
		'wbr-action-return',
		'wbr-nonce',
		'wbr-error-code',
	);

	// Set the array of new args.
	$setup  = apply_filters( Core\HOOK_PREFIX . 'admin_removable_args', $remove );

	// Include my new args and return.
	return ! empty( $setup ) ? wp_parse_args( $setup, $args ) : $args;
}

/**
 * Load our admin side CSS.
 *
 * @param $hook  Admin page hook we are current on.
 *
 * @return void
 */
function load_admin_stylesheet( $hook ) {

	// Set an array of allowed hooks.
	$allowed_hooks  = array(
		'edit.php',
		'toplevel_page_' . Core\REVIEWS_ANCHOR,
		'reviews_page_' . Core\ATTRIBUTES_ANCHOR,
		'reviews_page_' . Core\CHARSTCS_ANCHOR,
	);

	// Confirm we are on an allowed hook.
	if ( ! in_array( $hook, $allowed_hooks ) ) {
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
