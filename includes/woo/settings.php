<?php
/**
 * Load our WooCommerce specific actions and filters.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\WooSettings;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_filter( 'woocommerce_products_general_settings', __NAMESPACE__ . '\filter_woo_review_settings', 99 );
add_filter( 'woocommerce_settings_tabs_array', __NAMESPACE__ . '\add_review_settings_tab', 50 );
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
		$tabs[ Core\TAB_BASE ] = __( 'Reviews', 'woo-better-reviews' );
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
	woocommerce_update_options( get_settings() );
}

/**
 * Create the array of opt-ins we are going to display.
 *
 * @return array $settings  The array of settings data.
 */
function get_settings() {

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

		'header' => array(
			'title' => __( 'Product Reviews', 'woo-better-reviews' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => Core\OPTION_PREFIX . 'settings_header',
		),

		'enable' => array(
			'name'     => __( 'Enable Reviews', 'woo-better-reviews' ),
			'desc'     => __( 'Use the Better Reviews for WooCommerce.', 'woo-better-reviews' ),
			'id'       => 'woocommerce_enable_reviews',
			'type'     => 'checkbox',
			'default'  => 'yes',
			'class'    => 'woo-better-reviews-settings-checkbox',
			'desc_tip' => __( 'Unchecking this box will disable reviews completely.', 'woo-better-reviews' ),
		),

		'anonymous' => array(
			'name'    => __( 'Anonymous Reviews', 'woo-better-reviews' ),
			'desc'    => __( 'Allow non-logged in users to leave product reviews.', 'woo-better-reviews' ),
			'id'      => Core\OPTION_PREFIX . 'allow_anonymous',
			'type'    => 'checkbox',
			'default' => 'no',
			'class'   => 'woo-better-reviews-settings-checkbox',
			'desc_tip' => __( 'User accounts must be enabled for this feature.', 'woo-better-reviews' ),
		),

		'gloablattrib' => array(
			'name'    => __( 'Product Attributes', 'woo-better-reviews' ),
			'desc'    => __( 'Apply each created attribute to every product.', 'woo-better-reviews' ),
			'id'      => Core\OPTION_PREFIX . 'global_attributes',
			'type'    => 'checkbox',
			'default' => 'yes',
			'class'   => 'woo-better-reviews-settings-checkbox',
			'desc_tip' => sprintf( __( '<a href="%s">Click here</a> to view and edit your product review attributes.', 'woo-better-reviews' ), Helpers\get_admin_menu_link( Core\ATTRIBUTES_ANCHOR ) ),
		),

		// Include my section end.
		'section_end' => array( 'type' => 'sectionend', 'id' => Core\TAB_BASE . '_section_end' ),
	);

	// Return our set of fields with a filter.
	return apply_filters( Core\HOOK_PREFIX . 'settings_data_array', $setup_args );
}
