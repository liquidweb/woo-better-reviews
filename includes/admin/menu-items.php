<?php
/**
 * Load our various menus for the admin.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Admin\MenuItems;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Admin\AdminPages as AdminPages;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'admin_menu', __NAMESPACE__ . '\load_admin_menus', 43 );

/**
 * Load our admin menu items.
 *
 * @return void
 */
function load_admin_menus() {

	// Set the user cap.
	$user_menu_cap  = apply_filters( Core\HOOK_PREFIX . 'user_menu_cap', 'manage_options' );

	// Load my top-level admin menu.
	add_menu_page(
		__( 'Product Reviews', 'woo-better-reviews' ),
		__( 'Reviews','woo-better-reviews' ),
		$user_menu_cap,
		Core\SETTINGS_ANCHOR,
		__NAMESPACE__ . '\load_primary_settings_page',
        'dashicons-feedback',
        '58.8'
    );

	// Add the attributes page.
	add_submenu_page(
		Core\SETTINGS_ANCHOR,
		__( 'Product Attributes', 'woo-better-reviews' ),
		__( 'Attributes','woo-better-reviews' ),
		$user_menu_cap,
		Core\SETTINGS_ANCHOR . '-product-attributes',
		__NAMESPACE__ . '\load_product_attributes_page'
	);

	// Add the characteristics page.
	add_submenu_page(
		Core\SETTINGS_ANCHOR,
		__( 'User Characteristics', 'woo-better-reviews' ),
		__( 'Characteristics','woo-better-reviews' ),
		$user_menu_cap,
		Core\SETTINGS_ANCHOR . '-author-characteristics',
		__NAMESPACE__ . '\load_author_characteristics_page'
	);
}

/**
 * Load our primary settings page.
 *
 * @return void
 */
function load_primary_settings_page() {
	AdminPages\display_primary_settings_page();
}

/**
 * Load our product attributes page.
 *
 * @return void
 */
function load_product_attributes_page() {
	AdminPages\display_product_attributes_page();
}

/**
 * Load our author characteristics page.
 *
 * @return void
 */
function load_author_characteristics_page() {
	AdminPages\display_author_characteristics_page();
}
