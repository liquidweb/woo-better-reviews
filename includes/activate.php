<?php
/**
 * Our activation call
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Activate;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Database as Database;

/**
 * Our inital setup function when activated.
 *
 * @return void
 */
function activate() {

	// Run the check on the DB table.
	Database\maybe_install_tables();

	// Include our action so that we may add to this later.
	do_action( Core\HOOK_PREFIX . 'activate_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( Core\FILE, __NAMESPACE__ . '\activate' );
