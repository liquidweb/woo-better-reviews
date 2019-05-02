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

		// Set up our arguments needed in the email.
		$email_args = array(
			'order_id'  => $order_id,
			'customer'  => $reminder_data['customer'],
			'products'  => $reminder_data['products'],
		);

		// Send the email (maybe).
		$email_class->send_reminder( $email_args );
	}

}
