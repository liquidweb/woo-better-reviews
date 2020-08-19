<?php
/**
 * Handle filtered related Woo stuff.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\WooFilters;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Utilities as Utilities;
use Nexcess\WooBetterReviews\Queries as Queries;

// woocommerce_product_tabs

/**
 * Start our engines.
 */
add_filter( 'woocommerce_product_reviews_tab_title', __NAMESPACE__ . '\modify_review_count_title', 99, 2 );
add_filter( 'woocommerce_product_get_rating_html', __NAMESPACE__ . '\filter_review_stars_html', 20, 3 );
add_filter( 'woocommerce_email_classes', __NAMESPACE__ . '\load_review_reminder_email_class', 10 );
add_filter( 'wc_get_template', __NAMESPACE__ . '\load_review_reminder_email_templates', 99, 5 );

/**
 * Check if we have a sorted review list and modify the count.
 *
 * @param  string $title  The current tab title.
 * @param  string $key    What key we're on.
 *
 * @return array
 */
function modify_review_count_title( $title, $key ) {

	// Bail without the reviews tab.
	if ( 'reviews' !== sanitize_text_field( $key ) ) {
		return $title;
	}

	// Set my global.
	global $product;

	// Set the count of filtered or the total.
	$review_count   = Helpers\get_front_review_count( $product->get_id() );

	// If we have filtered IDs, change my title.
	return sprintf( __( 'Reviews (%s)', 'woocommerce' ), '<span class="wbr-review-tab-count">' . absint( $review_count ) . '</span>' );
}

/**
 * Modify the HTML for the star output to match our 7 point scale.
 *
 * @param  HTML    $html    The existing markup, which we're gonna replace.
 * @param  integer $rating  The rating value we're using.
 * @param  integer $count   Total number of ratings.
 *
 * @return HTML
 */
function filter_review_stars_html( $html, $rating, $count ) {

	// If we don't have a count, return whatever we had.
	if ( empty( $count ) ) {
		return $html;
	}

	// Fetch our markup for making stars.
	$average_stars  = Helpers\get_scoring_stars_display( 0, $rating, false );

	// Return our stars in a separate div that we can target, or the original HTML if we don't have ours.
	return ! empty( $average_stars ) ? '<div class="woo-better-reviews-stars-html-wrapper">' . $average_stars . '</div>' : $html;
}

/**
 * Add our review reminder email class loader to the existing setup.
 *
 * @param  array $email_classes  All the currently available email classes.
 *
 * @return array                 Our modified setup.
 */
function load_review_reminder_email_class( $email_classes ) {

	// Call in our email class, filtered.
	// You should REALLY know what you're doing here.
	$custom_class   = apply_filters( Core\HOOK_PREFIX . 'reminder_email_class', Core\INCLUDES_PATH . '/woo/email-class.php' );

	// Bail if we don't have the file we said.
	if ( ! file_exists( $custom_class ) ) {
		return $email_classes;
	}

	// Load the template file.
	require_once( $custom_class );

	// Add our review reminder class.
	$email_classes['WC_Email_Customer_Review_Reminder'] = new \WC_Email_Customer_Review_Reminder();

	// Return the new array of classes.
	return $email_classes;
}

/**
 * Filter out template stuff that we have to do because Woo.
 *
 * @param  string $template       The currently set template file.
 * @param  string $template_name  Template name.
 * @param  array  $args           Arguments. (default: array).
 * @param  string $template_path  Template path. (default: '').
 * @param  string $default_path   Default path. (default: '').
 *
 * @return string
 */
function load_review_reminder_email_templates( $template, $template_name, $args, $template_path, $default_path ) {

	// Switch between the template names.
	switch ( esc_attr( $template_name ) ) {

		// Send the HTML.
		case 'customer-review-reminder-html.php' :
			return Core\TEMPLATE_PATH . '/emails/customer-review-reminder-html.php';
			break;

		// Send the plain.
		case 'customer-review-reminder-plain.php' :
			return Core\TEMPLATE_PATH . '/emails/customer-review-reminder-plain.php';
			break;

		// End all case breaks.
	}

	// Nothing custom, so return what we had.
	return $template;
}
