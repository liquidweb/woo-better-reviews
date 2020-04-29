<?php
/**
 * Handle the various assets being loaded in the admin.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\AdminAssets;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;

/**
 * Start our engines.
 */
add_filter( 'removable_query_args', __NAMESPACE__ . '\admin_removable_args' );
add_filter( 'admin_body_class', __NAMESPACE__ . '\load_admin_body_class' );
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
		'wbr-action-name',
		'wbr-item-id',
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
 * Include a custom body class on our admin tab.
 *
 * @param  string $classes  The current string of body classes.
 *
 * @return string $classes  The potentially modified string of body classes.
 */
function load_admin_body_class( $classes ) {

	// Check if we're allowed.
	$maybe_load = Helpers\maybe_admin_settings_tab();

	// Confirm we are on an allowed hook.
	if ( false !== $maybe_load ) {
		$classes .= ' woo-better-reviews-admin-body-class';
	}

	// And send back the string.
	return $classes;
}

/**
 * Load our admin side CSS.
 *
 * @param $hook  Admin page hook we are current on.
 *
 * @return void
 */
function load_admin_stylesheet( $hook ) {

	// Check if we're allowed.
	$maybe_load = Helpers\maybe_admin_settings_tab( $hook );

	// Confirm we are on an allowed hook.
	if ( empty( $maybe_load ) ) {
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

	// And our JS.
	wp_enqueue_script( $handle, Core\ASSETS_URL . '/js/' . $file . '.js', array( 'jquery' ), $vers, true );
}
