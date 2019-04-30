<?php
/**
 * Run the review reminders.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\CronTasks;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Database as Database;

/**
 * Start our engines.
 */
add_action( Core\CRON_NAME, __NAMESPACE__ . '\maybe_send_reminders' );

/**
 * Our cron job to check for pending reminders.
 *
 * @return void
 */
function maybe_send_reminders() {

}
