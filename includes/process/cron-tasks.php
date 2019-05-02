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

	// Now loop the reminder data and
	foreach ( $reminder_batch as $order_id => $reminder_data ) {

		// Bail if no products.
		if ( empty( $reminder_data['products'] ) ) {
			continue;
		}
		/*
		// Set a subject.
		$email_intro    = lcl_better_rvs_reminder_email_intro_build( $filtered_data );

		// Now build the body.
		$email_content  = lcl_better_rvs_reminder_email_content_build( $filtered_data );

		// Set some email headers.
		$email_headers  = lcl_better_rvs_reminder_email_headers_build( $filtered_data );

		// Send mail.
		$send_email = wp_mail( $email_intro['send-to'], $email_intro['subject'], $email_content, $email_headers );

		// Die on a bad email.
		if ( ! $send_email ) {
			die( 'email failed' );
		}
		*/
	}

}
