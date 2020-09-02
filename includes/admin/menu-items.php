<?php
/**
 * Load our various menus for the admin.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\Admin\MenuItems;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Queries as Queries;
use Nexcess\WooBetterReviews\AdminPages as AdminPages;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_filter( 'plugin_action_links', __NAMESPACE__ . '\add_quick_link', 10, 2 );
add_action( 'admin_menu', __NAMESPACE__ . '\load_admin_menus', 43 );
add_action( 'admin_init', __NAMESPACE__ . '\load_review_converter' );

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
	$settings_page  = add_query_arg( array( 'tab' => 'products' ), Helpers\get_admin_menu_link( 'wc-settings' ) );
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

	// Create the link for the settings jump.
	$setslink_args  = array( 'page' => 'wc-settings', 'tab' => trim( Core\TAB_BASE ) );
	$setslink_strng = add_query_arg( $setslink_args, 'admin.php' );

	// Load my top-level admin menu.
	add_menu_page(
		__( 'Product Reviews', 'woo-better-reviews' ),
		__( 'Product Reviews', 'woo-better-reviews' ),
		$user_menu_cap,
		Core\REVIEWS_ANCHOR,
		null,
        'dashicons-feedback',
        '58.8'
    );

	// Add a secondary page so we have a better label.
	add_submenu_page(
		Core\REVIEWS_ANCHOR,
		get_menu_page_title( 'reviews' ),
		__( 'All Reviews', 'woo-better-reviews' ),
		$user_menu_cap,
		Core\REVIEWS_ANCHOR,
		__NAMESPACE__ . '\load_reviews_list_page'
	);

	// Add the attributes page.
	add_submenu_page(
		Core\REVIEWS_ANCHOR,
		get_menu_page_title( 'attributes' ),
		__( 'Review Attributes','woo-better-reviews' ),
		$user_menu_cap,
		Core\ATTRIBUTES_ANCHOR,
		__NAMESPACE__ . '\load_review_attributes_page'
	);

	// Add the traits page.
	add_submenu_page(
		Core\REVIEWS_ANCHOR,
		get_menu_page_title( 'traits' ),
		__( 'Author Traits', 'woo-better-reviews' ),
		$user_menu_cap,
		Core\CHARSTCS_ANCHOR,
		__NAMESPACE__ . '\load_author_traits_page'
	);

	// And include the settings link over to Woo.
	add_submenu_page(
		Core\REVIEWS_ANCHOR,
		__( 'Plugin Settings', 'woo-better-reviews' ),
		__( 'Plugin Settings', 'woo-better-reviews' ),
		$user_menu_cap,
		$setslink_strng,
		null
	);

	// No more menu items to include.
}

/**
 * Register WordPress based importers.
 */
function load_review_converter() {

	// Make sure the constant is being defined.
	if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
		return;
	}

	// Now load up our new importer.
	register_importer(
		'wbr-review-conversion',
		__( 'Better Product Reviews for WooCommerce', 'woocommerce' ),
		__( 'Convert any existing WooCommerce reviews to the new.', 'woo-better-reviews' ),
		__NAMESPACE__ . '\load_review_import_page'
	);

	// That's it.
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
function load_review_attributes_page() {
	AdminPages\display_review_attributes_page();
}

/**
 * Load our author characteristics page.
 *
 * @return void
 */
function load_author_traits_page() {
	AdminPages\display_author_traits_page();
}

/**
 * Load our review converter page.
 *
 * @return void
 */
function load_review_import_page() {
	AdminPages\display_review_import_page();
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
			return ! $isedit ? __( 'Product Reviews', 'woo-better-reviews' ) : __( 'Edit Product Review', 'woo-better-reviews' );
			break;

		case 'attributes' :

			// Make and return my label.
			return ! $isedit ? __( 'Review Attributes', 'woo-better-reviews' ) : __( 'Edit Review Attribute', 'woo-better-reviews' );
			break;

		case 'traits' :
		case 'characteristics' :

			// Make and return my label.
			return ! $isedit ? __( 'Review Author Traits', 'woo-better-reviews' ) : __( 'Edit Review Author Trait', 'woo-better-reviews' );
			break;

		// No more case breaks, no more menues.
	}

	// Return nothing.
	return '';
}
