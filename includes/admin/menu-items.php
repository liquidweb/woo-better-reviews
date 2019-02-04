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
add_filter( 'plugin_action_links', __NAMESPACE__ . '\add_quick_link', 10, 2 );
add_action( 'admin_menu', __NAMESPACE__ . '\load_admin_menus', 43 );

/**
 * Add our "reviews" links to the plugins page.
 *
 * @param  array  $links  The existing array of links.
 * @param  string $file   The file we are actually loading from.
 *
 * @return array  $links  The updated array of links.
 */
function add_quick_link( $links, $file ) {

	// Bail without caps.
	if ( ! current_user_can( 'manage_options' ) ) {
		return $links;
	}

	// Set the static var.
	static $this_plugin;

	// Check the base if we aren't paired up.
	if ( ! $this_plugin ) {
		$this_plugin = Core\BASE;
	}

	// Check to make sure we are on the correct plugin.
	if ( $file != $this_plugin ) {
		return $links;
	}

	// Fetch our setting links.
	$settings_page  = add_query_arg( array( 'tab' => 'products' ), Helpers\get_admin_menu_link( 'wc-settings' ) );;
	$reviews_page   = Helpers\get_admin_menu_link( Core\REVIEWS_ANCHOR );

	// Now create the link markup.
	$settings_link  = '<a href="' . esc_url( $settings_page ) . ' ">' . esc_html__( 'Settings', 'woo-better-reviews' ) . '</a>';
	$reviews_link   = '<a href="' . esc_url( $reviews_page ) . ' ">' . esc_html__( 'Reviews', 'woo-better-reviews' ) . '</a>';

	// Add it to the array.
	array_push( $links, $settings_link, $reviews_link );

	// Return the resulting array.
	return $links;
}

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
		get_menu_page_title( 'reviews' ),
		__( 'Reviews', 'woo-better-reviews' ),
		$user_menu_cap,
		Core\REVIEWS_ANCHOR,
		__NAMESPACE__ . '\load_reviews_list_page',
        'dashicons-feedback',
        '58.8'
    );

	// Add the attributes page.
	add_submenu_page(
		Core\REVIEWS_ANCHOR,
		get_menu_page_title( 'attributes' ),
		__( 'Attributes','woo-better-reviews' ),
		$user_menu_cap,
		Core\ATTRIBUTES_ANCHOR,
		__NAMESPACE__ . '\load_product_attributes_page'
	);

	// Add the characteristics page.
	add_submenu_page(
		Core\REVIEWS_ANCHOR,
		get_menu_page_title( 'characteristics' ),
		__( 'Characteristics', 'woo-better-reviews' ),
		$user_menu_cap,
		Core\CHARSTCS_ANCHOR,
		__NAMESPACE__ . '\load_author_characteristics_page'
	);
}

/**
 * Load our primary settings page.
 *
 * @return void
 */
function load_reviews_list_page() {
	AdminPages\display_reviews_list_page();
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

/**
 * Determine the page title for each menu.
 *
 * @param  string $menu  Which menu we're checking.
 *
 * @return string
 */
function get_menu_page_title( $menu = '' ) {

	// Check to see if we are editing something or not.
	$isedit = ! empty( $_GET['wbr-action-name'] ) && 'edit' === sanitize_text_field( $_GET['wbr-action-name'] ) ? 1 : 0;

	// Handle our title creation based on the menu.
	switch ( sanitize_text_field( $menu ) ) {

		case 'reviews' :

			// Make and return my label.
			return __( 'Reviews','woo-better-reviews' );
			break;

		case 'attributes' :

			// Make and return my label.
			return ! $isedit ? __( 'Product Attributes', 'woo-better-reviews' ) : __( 'Edit Attribute', 'woo-better-reviews' );
			break;

		case 'characteristics' :

			// Make and return my label.
			return ! $isedit ? __( 'Review Author Characteristics', 'woo-better-reviews' ) : __( 'Edit Characteristic', 'woo-better-reviews' );
			break;

		// No more case breaks, no more menues.
	}

}
