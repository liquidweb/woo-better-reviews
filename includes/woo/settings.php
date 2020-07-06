<?php
/**
 * Load our WooCommerce specific actions and filters.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\WooSettings;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Utilities as Utilities;
use Nexcess\WooBetterReviews\Queries as Queries;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_filter( 'woocommerce_products_general_settings', __NAMESPACE__ . '\filter_woo_review_settings', 99 );
add_filter( 'woocommerce_settings_tabs_array', __NAMESPACE__ . '\add_review_settings_tab', 50 );
add_action( 'woocommerce_admin_field_linkedtext', __NAMESPACE__ . '\output_settings_linkedtext' );
add_action( 'woocommerce_settings_tabs_wbr_settings', __NAMESPACE__ . '\display_settings_tab' );
add_action( 'woocommerce_update_options_wbr_settings', __NAMESPACE__ . '\update_review_settings' );

/**
 * Remove the default review settings to use our own.
 *
 * @return void
 */
function filter_woo_review_settings( $settings ) {

	// If we have no settings (somehow), bail.
	if ( empty( $settings ) ) {
		return $settings;
	}

	// Set an array of the items we want to remove.
	$removals   = array(
		'woocommerce_enable_reviews',
		'woocommerce_review_rating_verification_label',
		'woocommerce_review_rating_verification_required',
		'woocommerce_enable_review_rating',
		'woocommerce_review_rating_required',
	);

	// Now loop our settings and modify the items we want.
	foreach ( $settings as $field_index => $field_args ) {

		// Since we only care about IDs to check, skip if none is there.
		if ( empty( $field_args['id'] ) ) {
			continue;
		}

		// Swap out the description text to point to the new tab.
		if ( 'product_rating_options' === sanitize_text_field( $field_args['id'] ) && 'title' === sanitize_text_field( $field_args['type'] ) ) {

			// Set up the text.
			$new_settings_text  = sprintf( __( 'All settings related to product reviews have been <a href="%s">moved here</a>.', 'woo-better-reviews' ), Helpers\get_admin_tab_link() );

			// Add our new description text.
			$settings[ $field_index ]['desc'] = $new_settings_text;
		}

		// Remove the item from the settings array if it matches.
		if ( in_array( sanitize_text_field( $field_args['id'] ), $removals ) ) {
			unset( $settings[ $field_index ] );
		}
	}

	// Return the resulting array, resetting the indexes.
	return array_values( $settings );
}

/**
 * Add a new settings tab to the WooCommerce settings tabs array.
 *
 * @param  array $tabs  The current array of WooCommerce setting tabs.
 *
 * @return array $tabs  The modified array of WooCommerce setting tabs.
 */
function add_review_settings_tab( $tabs ) {

	// Confirm we don't already have the tab.
	if ( ! isset( $tabs[ Core\TAB_BASE ] ) ) {
		$tabs[ Core\TAB_BASE ] = __( 'Product Reviews', 'woo-better-reviews' );
	}

	// And return the entire array.
	return $tabs;
}

/**
 * Uses the WooCommerce admin fields API to output settings.
 *
 * @see  woocommerce_admin_fields() function.
 *
 * @uses woocommerce_admin_fields()
 * @uses self::get_settings()
 */
function display_settings_tab() {
	woocommerce_admin_fields( get_settings() );
}

/**
 * Uses the WooCommerce options API to save settings.
 *
 * @see woocommerce_update_options() function.
 *
 * @uses woocommerce_update_options()
 * @uses self::get_settings()
 */
function update_review_settings() {

	// Check out the cron adjustment.
	maybe_adjust_reminder_cron();

	// Now save as normal.
	woocommerce_update_options( get_settings() );
}

/**
 * Check the POST value and handle the reminder cron.
 *
 * @return void
 */
function maybe_adjust_reminder_cron() {

	// Set our reminder key.
	$reminder_key   = Core\OPTION_PREFIX . 'send_reminders';

	// Pull in our scheduled cron and unschedule it if disabled.
	if ( empty( $_POST[ $reminder_key ] ) ) {
		Utilities\modify_reminder_cron( true, false );
	}

	// Check for the reminders being turned on or off and handle the cron.
	if ( ! empty( $_POST[ $reminder_key ] ) && ! wp_next_scheduled( Core\REMINDER_CRON ) ) {
		Utilities\modify_reminder_cron( false, 'twicedaily' );
	}
}

/**
 * Create the array of opt-ins we are going to display.
 *
 * @return array $settings  The array of settings data.
 */
function get_settings() {

	// Set the text for the global product attributes, since it has some markup.
	$setup_global_attributes_label  = __( 'Make reviewable attributes global.', 'woo-better-reviews' );
	$setup_global_attributes_label .= ' <span class="woo-better-reviews-inline-label-text">' . __( '(Apply each created attribute to every product)', 'woo-better-reviews' ) . '</span>';

	// Set up our array, including default Woo items.
	$setup_args = array(

		/*
		'option_name' => array(
			'title' => 'Title for your option shown on the settings page',
			'description' => 'Description for your option shown on the settings page',
			'type' => 'text|password|textarea|checkbox|select|multiselect',
			'default' => 'Default value for the option',
			'class' => 'Class for the input',
			'css' => 'CSS rules added line to the input',
			'label' => 'Label', // checkbox only
			'options' => array(
				'key' => 'value'
			) // array of options for select/multiselects only
		)
		*/

		'mainheader' => array(
			'title' => __( 'Product Review Settings', 'woo-better-reviews' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => Core\OPTION_PREFIX . 'main_settings_header',
		),

		'enable' => array(
			'title'    => __( 'Enable Reviews', 'woo-better-reviews' ),
			'desc'     => __( 'Use the Better Reviews for WooCommerce plugin', 'woo-better-reviews' ),
			'id'       => 'woocommerce_enable_reviews', // @@todo figure out if setting key should be different.
			'type'     => 'checkbox',
			'default'  => 'yes',
			'class'    => 'woo-better-reviews-settings-checkbox',
			'desc_tip' => __( 'Unchecking this box will disable reviews completely.', 'woo-better-reviews' ),
		),

		'anonymous' => array(
			'title'    => __( 'Anonymous Reviews', 'woo-better-reviews' ),
			'desc'     => __( 'Allow non-logged in users to leave product reviews', 'woo-better-reviews' ),
			'id'       => Core\OPTION_PREFIX . 'allow_anonymous',
			'type'     => 'checkbox',
			'default'  => 'no',
			'class'    => 'woo-better-reviews-settings-checkbox',
			'desc_tip' => __( 'User accounts must be enabled for this feature.', 'woo-better-reviews' ),
		),

		'doverified' => array(
			'title'           => __( 'Verified Reviews', 'woo-better-reviews' ),
			'desc'            => __( 'Show "verified owner" label on customer reviews', 'woo-better-reviews' ),
			'id'              => 'woocommerce_review_rating_verification_label',
			'default'         => 'yes',
			'type'            => 'checkbox',
			'checkboxgroup'   => 'start',
			'show_if_checked' => 'yes',
			'class'           => 'woo-better-reviews-settings-checkbox',
			'autoload'        => false,
		),

		'onlyverified' => array(
			'desc'            => __( 'Reviews can only be left by "verified owners"', 'woo-better-reviews' ),
			'id'              => 'woocommerce_review_rating_verification_required',
			'default'         => 'no',
			'type'            => 'checkbox',
			'checkboxgroup'   => 'end',
			'show_if_checked' => 'yes',
			'class'           => 'woo-better-reviews-settings-checkbox',
			'autoload'        => false,
		),

		'gloablattributes' => array(
			'title'    => __( 'Product Attributes', 'woo-better-reviews' ),
			'desc'     => $setup_global_attributes_label,
			'id'       => Core\OPTION_PREFIX . 'global_attributes',
			'type'     => 'checkbox',
			'default'  => 'yes',
			'class'    => 'woo-better-reviews-settings-checkbox',
			'desc_tip' => sprintf( __( '<a href="%s">Click here</a> to view and edit your product review attributes.', 'woo-better-reviews' ), Helpers\get_admin_menu_link( Core\ATTRIBUTES_ANCHOR ) ),
		),

		// Include my section end.
		'mainsection_end' => array( 'type' => 'sectionend', 'id' => Core\TAB_BASE . '_main_settings_section_end' ),

		// Now start the reminders.
		'remindheader' => array(
			'title' => __( 'Reminders and Follow Up', 'woo-better-reviews' ),
			'type'  => 'title',
			'desc'  => __( 'Send reminders to customers to leave reviews.', 'woo-better-reviews' ),
			'id'    => Core\OPTION_PREFIX . 'remind_settings_header',
		),

		'doreminders' => array(
			'title'   => __( 'Enable Reminders', 'woo-better-reviews' ),
			'desc'    => __( 'Send an email reminder for customers to leave product reviews', 'woo-better-reviews' ),
			'id'      => Core\OPTION_PREFIX . 'send_reminders',
			'type'    => 'checkbox',
			'default' => 'yes',
			'class'   => 'woo-better-reviews-settings-checkbox',
		),

		'sendreminder' => array(
			'title'    => __( 'Reminder Delay', 'woo-better-reviews' ),
			'desc'     => '<span class="woo-better-reviews-settings-block-desc">' . __( 'Set the amount of time from purchase to send the reminder.', 'woo-better-reviews' ) . '</span>',
			'id'       => Core\OPTION_PREFIX . 'reminder_wait',
			'type'     => 'relative_date_selector',
			'default'  => array(
				'number' => 2,
				'unit'   => 'weeks',
			),
			'autoload' => false,
			'class'   => 'woo-better-reviews-settings-date-group',
		),

		'templateshow' => array(
			'title'   => __( 'Email Template', 'woo-better-reviews' ),
			'linked'  => sprintf( __( '<a href="%s">Click here</a> to view and edit the email template.', 'woo-better-reviews' ), Helpers\get_admin_tab_link( 'email', 'wc_email_customer_review_reminder' ) ),
			'id'      => Core\OPTION_PREFIX . 'template_show',
			'type'    => 'linkedtext',
		),

		// Close up the reminders section.
		'remindsection_end' => array( 'type' => 'sectionend', 'id' => Core\TAB_BASE . '_remind_settings_section_end' ),
	);

	// Return our set of fields with a filter, resetting the keys again.
	return apply_filters( Core\HOOK_PREFIX . 'settings_data_array', array_values( $setup_args ) );
}

/**
 * Output our custom linked text section.
 *
 * @param  array $args  The field args we set up.
 *
 * @return HTML
 */
function output_settings_linkedtext( $args ) {

	// Set our args up.
	$set_title  = ! empty( $args['title'] ) ? $args['title'] : '';
	$set_linked = ! empty( $args['linked'] ) ? $args['linked'] : '';

	// Do the table stuff.
	echo '<tr valign="top">';

		// Output the title.
		echo '<th scope="row" class="titledesc">' . esc_html( $set_title ) . '</th>';

		// Do the link text.
		echo '<td>' . wp_kses_post( $set_linked ) . '</td>';

	// Close the table.
	echo '</tr>';
}
