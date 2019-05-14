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
add_action( 'upgrader_process_complete', __NAMESPACE__ . '\set_pending_updates', 10, 2 );
add_action( 'admin_notices', __NAMESPACE__ . '\run_pending_updates' );

/**
 * Run this after all the WP updates are done to see if we need to run anything.
 *
 * @param  WP_Upgrader $upgrader_object  The entire WP_Upgrader instance.
 * @param  array       $hook_extra       The options being passed, which we care about.
 *
 * @return void
 */
function set_pending_updates( $upgrader_object, $hook_extra ) {

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

			// Set the transient to handle the update.
			set_transient( Core\OPTION_PREFIX . 'update_transient', '0.0.3' );

			// And break.
			break;
		}

		// Now check the versions as they come in.
		if ( ! empty( $stored_version ) ) {

			// Do the 0.0.3 check.
			if ( version_compare( $stored_version, '0.0.3', '<=' ) ) {

				// Set the transient to handle the update.
				set_transient( Core\OPTION_PREFIX . 'update_transient', '0.0.3' );

				// And break.
				break;
			}

			// Additional updates will go here later.
		}

		// No more looping of the plugins.
	}

	// Nothing left inside this action.
}

/**
 * Check for our update transients and act accordingly.
 *
 * @return void
 */
function run_pending_updates() {

	// First check for the transient.
	$maybe_updated  = get_transient( Core\OPTION_PREFIX . 'update_transient' );

	// Bail if we have no transient at all.
	if ( empty( $maybe_updated ) ) {
		return;
	}

	// Set our settings tab link since we may need it.
	$settings_tab   = Helpers\get_admin_tab_link();

	// Set a blank notice text (since not all will require).
	$notice_message = '';

	// Switch through and handle the updates we have.
	switch ( esc_attr( $maybe_updated ) ) {

		// Run the first upgrade function we have.
		case '0.0.3' :

			// Run the update.
			update_version_zero_point_zero_point_three();

			// Set our notice text.
			$notice_message = sprintf( __( 'Better Reviews for WooCommerce has been updated and review reminder emails have been activated! <a href="%s">Click here</a> to change the settings or disable the feature.', 'woo-better-reviews' ), esc_url( $settings_tab ) );

			// And break.
			break;

		// More update cases will run here.
	}

	// Update the plugin version key.
	update_option( Core\OPTION_PREFIX . 'plugin_version', Core\VERS );

	// Delete the transient we had just set.
	delete_transient( Core\OPTION_PREFIX . 'update_transient' );

	// Now show the message if we have one.
	if ( ! empty( $notice_message ) ) {

		// Start the notice markup.
		echo '<div class="notice notice-info is-dismissible">';

			// Display the actual message.
			echo '<p><strong>' . wp_kses_post( $notice_message ) . '</strong></p>';

		// And close the div.
		echo '</div>';
	}
}

/**
 * Run our functions when upgrading to version 0.0.3 of the plugin.
 *
 * @return void
 */
function update_version_zero_point_zero_point_three() {

	// We are enabling the review reminders.
	update_option( Core\OPTION_PREFIX . 'send_reminders', 'yes' );
	update_option( Core\OPTION_PREFIX . 'reminder_wait', array( 'number' => 2, 'unit' => 'weeks' ) );
}
