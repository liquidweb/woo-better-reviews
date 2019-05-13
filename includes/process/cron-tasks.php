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
use LiquidWeb\WooBetterReviews\Queries as Queries;
// use LiquidWeb\WooBetterReviews\Reminders as Reminders;

/**
 * Start our engines.
 */
add_action( Core\REMINDER_CRON, __NAMESPACE__ . '\maybe_send_reminders' );

/**
 * Our cron job to check for pending reminders.
 *
 * @return void
 */
function maybe_send_reminders() {

	// Run the main check for being enabled.
	$maybe_enabled  = Helpers\maybe_reminders_enabled();

	// Bail if not enabled.
	if ( ! $maybe_enabled ) {
		return;
	}

	// Now fetch the dataset (possibly).
	$reminder_batch = Queries\get_reminder_order_data();

	// Bail without any data to handle.
	if ( empty( $reminder_batch ) ) {
		return;
	}

	// Pull in the file functions.
	if ( ! class_exists( 'WC_Email_Customer_Review_Reminder' ) ) {
		require_once Core\INCLUDES_PATH . '/woo/email-class.php';
	}

	// Call our review reminder email class.
	$email_class    = new WC_Email_Customer_Review_Reminder();

	// Now loop the reminder data and
	foreach ( $reminder_batch as $order_id => $reminder_data ) {

		// Bail if no products or customer data.
		if ( empty( $reminder_data['products'] ) || empty( $reminder_data['customer'] ) ) {
			continue;
		}

		// If the order ID is missing, add it back.
		if ( empty( $reminder_data['order_id'] ) ) {
			$reminder_data['order_id'] = absint( $order_id );
		}

		// Send the email (maybe).
		$trigger_email  = $email_class->trigger( $reminder_data );

		// Confirm the trigger was successful.
		if ( false !== $trigger_email ) {
			// need something to say "done".
		}

		// Maybe do an error return, but it's cron so not sure what.
	}

}
