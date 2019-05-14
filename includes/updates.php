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
use LiquidWeb\WooBetterReviews\Helpers as Helpers;

/**
 * Start our engines.
 */
add_action( 'upgrader_process_complete', __NAMESPACE__ . '\run_update_functions', 10, 2 );

/**
 * Run this after all the WP updates are done to see if we need to run anything.
 *
 * @param  WP_Upgrader $upgrader_object  The entire WP_Upgrader instance.
 * @param  array       $hook_extra       The options being passed, which we care about.
 *
 * @return void
 */
function run_update_functions( $upgrader_object, $hook_extra ) {

	// Make sure we have an action, and we are indeed updating something.
	if ( empty( $hook_extra['action'] ) || 'update' !== sanitize_text_field( $hook_extra['action'] ) ) {
		return;
	}

	// Make sure we're dealing with plugins.
	if ( empty( $hook_extra['plugins'] ) || empty( $hook_extra['type'] ) || 'plugin' !== sanitize_text_field( $hook_extra['type'] ) ) {
		return;
	}

	// Now loop through the plugins being updated and check if ours is there.
	foreach ( $hook_extra['plugins'] as $plugin ) {

		// If we don't match the file, skip it.
		if ( $plugin !== Core\BASE ) {
			continue;
		}

		// Pull the current stored version of the plugin.
		$stored_version = Helpers\get_stored_plugin_version();

		// If we have no stored version, this is like version 0.0.3.
		if ( empty( $stored_version ) ) {
			update_version_zero_point_zero_point_three();
		}

		// Now check the versions as they come in.
		if ( ! empty( $stored_version ) ) {

			// Do the 0.0.3 check.
			if ( version_compare( $stored_version, '0.0.3', '<=' ) ) {
				update_version_zero_point_zero_point_three();
			}
		}

		// No more looping of the plugins.
	}

	// Nothing left inside this action.
}

/**
 * Run our functions when upgrading to version 0.0.3 of the plugin.
 *
 * @return void
 */
function update_version_zero_point_zero_point_three() {

	// Set our actual version key.
	update_option( Core\OPTION_PREFIX . 'plugin_version', Core\VERS );

	// We are enabling the review reminders.
	update_option( Core\OPTION_PREFIX . 'send_reminders', 'yes' );
	update_option( Core\OPTION_PREFIX . 'reminder_wait', array( 'number' => 2, 'unit' => 'weeks' ) );
}
