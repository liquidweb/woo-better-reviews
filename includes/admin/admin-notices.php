<?php
/**
 * Handle the various admin notices.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Admin\AdminNotices;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;

/**
 * Start our engines.
 */
add_action( 'admin_notices', __NAMESPACE__ . '\attribute_result_notices' );

/**
 * Check for the result of adding or editing an attribute.
 *
 * @return void
 */
function attribute_result_notices() {

	// Make sure we have the completed flag.
	if ( empty( $_GET['wbr-action-complete'] ) || empty( $_GET['wbr-action-result'] ) ) {
		return;
	}

	// Determine the message type.
	$result = ! empty( $_GET['success'] ) ? 'success' : 'error';

	// Handle dealing with an error return.
	if ( 'error' === $result ) {

		// Figure out my error code.
		$error_code = ! empty( $_GET['wbr-error-code'] ) ? $_GET['wbr-error-code'] : 'unknown';

		// Handle my error text retrieval.
		$error_text = Helpers\get_admin_notice_text( $error_code );

		// And handle the display.
		display_admin_notice_markup( $error_text, 'error' );

		// And be done.
		return;
	}

	// Go get my text to display.
	$notice = Helpers\get_admin_notice_text( sanitize_text_field( $_GET['wbr-action-result'] ) );

	// And handle the display.
	display_admin_notice_markup( $notice, $result );
}

/**
 * Build the markup for an admin notice.
 *
 * @param  string  $notice       The actual message to display.
 * @param  string  $result       Which type of message it is.
 * @param  boolean $dismiss      Whether it should be dismissable.
 * @param  boolean $show_button  Show the dismiss button (for Ajax calls).
 * @param  boolean $echo         Whether to echo out the markup or return it.
 *
 * @return HTML
 */
function display_admin_notice_markup( $notice = '', $result = 'error', $dismiss = true, $show_button = false, $echo = true ) {

	// Bail without the required message text.
	if ( empty( $notice ) ) {
		return;
	}

	// Set my base class.
	$class  = 'notice notice-' . esc_attr( $result ) . ' wbr-admin-message';

	// Add the dismiss class.
	if ( $dismiss ) {
		$class .= ' is-dismissible';
	}

	// Set an empty.
	$field  = '';

	// Start the notice markup.
	$field .= '<div class="' . esc_attr( $class ) . '">';

		// Display the actual message.
		$field .= '<p><strong>' . wp_kses_post( $notice ) . '</strong></p>';

		// Show the button if we set dismiss and button variables.
		$field .= $dismiss && $show_button ? '<button type="button" class="notice-dismiss">' . screen_reader_text() . '</button>' : '';

	// And close the div.
	$field .= '</div>';

	// Echo it if requested.
	if ( ! empty( $echo ) ) {
		echo $field; // WPCS: XSS ok.
	}

	// Just return it.
	return $field;
}
