<?php
/**
 * Our functions to run at upgrade (updates).
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Updates;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Database as Database;

/**
 * Start our engines.
 */
add_action( 'upgrader_process_complete', __NAMESPACE__ . '\run_update_functions', 10, 2 );

/**
 * Run this after all the WP updates are done to see if we need to run anything.
 *
 * @param  array $upgrader_object  The entire update object.
 * @param  array $options          The options being passed, which we care about.
 *
 * @return void
 */
function run_update_functions( $upgrader_object, $options ) {

	// Make sure we have an action, and we are indeed updating something.
	if ( empty( $options['action'] ) || 'update' !== sanitize_text_field( $options['action'] ) ) {
		return;
	}

	// Make sure we're dealing with plugins.
	if ( empty( $options['plugins'] ) || empty( $options['type'] ) || 'plugin' !== sanitize_text_field( $options['type'] ) ) {
		return;
	}

	// Now loop through the plugins being updated and check if ours is there.
	foreach ( $options['plugins'] as $plugin ) {

		// If we don't match the file, skip it.
		if ( $plugin !== Core\BASE ) {
			continue;
		}

		// now what?
	}

	// Nothing left inside this action.
}
