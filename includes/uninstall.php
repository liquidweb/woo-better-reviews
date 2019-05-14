<?php
/**
 * Our uninstall call.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Uninstall;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Database as Database;

/**
 * Delete various options when uninstalling the plugin.
 *
 * @return void
 */
function uninstall() {

	// Run the database table deletes.
	Database\drop_tables();

	// Delete the options we set.
	delete_initial_options();

	// Pull in our scheduled cron and unschedule it.
	Utilities\modify_reminder_cron( true, false );

	// Include our action so that we may add to this later.
	do_action( Core\HOOK_PREFIX . 'uninstall_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_uninstall_hook( Core\FILE, __NAMESPACE__ . '\uninstall' );

/**
 * Delete the initial options we set.
 *
 * @return void
 */
function delete_initial_options() {
	delete_option( Core\OPTION_PREFIX . 'plugin_version' );
	delete_option( Core\OPTION_PREFIX . 'allow_anonymous' );
	delete_option( Core\OPTION_PREFIX . 'global_attributes' );
	delete_option( Core\OPTION_PREFIX . 'send_reminders' );
	delete_option( Core\OPTION_PREFIX . 'reminder_wait' );
}
