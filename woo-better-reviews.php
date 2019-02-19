<?php
/**
 * Plugin Name: Better Reviews For WooCommerce
 * Plugin URI:  https://github.com/liquidweb/woo-better-reviews
 * Description: Like reviews, only way better.
 * Version:     0.0.1
 * Author:      Liquid Web
 * Author URI:  https://www.liquidweb.com
 * Text Domain: woo-better-reviews
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews;

// Call our CLI namespace.
use WP_CLI;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Define our plugin version.
define( __NAMESPACE__ . '\VERS', '0.0.1' );

// Plugin root file.
define( __NAMESPACE__ . '\FILE', __FILE__ );

// Define our file base.
define( __NAMESPACE__ . '\BASE', plugin_basename( __FILE__ ) );

// Plugin Folder URL.
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );

// Set our assets URL constant.
define( __NAMESPACE__ . '\ASSETS_URL', URL . 'assets' );

// Set our template path constant.
define( __NAMESPACE__ . '\TEMPLATE_PATH', __DIR__ . '/templates/' );

// Set the prefix for our actions and filters.
define( __NAMESPACE__ . '\HOOK_PREFIX', 'wc_better_reviews_' );

// Set our custom table prefix.
define( __NAMESPACE__ . '\TABLE_PREFIX', 'woocommerce_better_reviews_' );

// Set the prefix for our meta keys.
define( __NAMESPACE__ . '\META_PREFIX', '_wbr_meta_' );

// Set the name for our various menu page anchors.
define( __NAMESPACE__ . '\REVIEWS_ANCHOR', 'woo-better-reviews' );
define( __NAMESPACE__ . '\ATTRIBUTES_ANCHOR', 'woo-better-reviews-product-attributes' );
define( __NAMESPACE__ . '\CHARSTCS_ANCHOR', 'woo-better-reviews-author-characteristics' );

// Set the option key and DB versions used to store the schemas.
define( __NAMESPACE__ . '\DB_VERS', '1' );
define( __NAMESPACE__ . '\SCHEMA_KEY', HOOK_PREFIX . 'db_version' );

// Load the multi-use files first.
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/utilities.php';

// Load the database and custom table items.
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/tables/data-content.php';
require_once __DIR__ . '/includes/tables/data-ratings.php';
require_once __DIR__ . '/includes/tables/data-authormeta.php';
require_once __DIR__ . '/includes/tables/tax-attributes.php';
require_once __DIR__ . '/includes/tables/tax-characteristics.php';
require_once __DIR__ . '/includes/tables/group-productsetup.php';
require_once __DIR__ . '/includes/tables/query-consolidated.php';

// Load the files loaded plugin-wide.
require_once __DIR__ . '/includes/queries.php';
require_once __DIR__ . '/includes/woo-settings.php';
require_once __DIR__ . '/includes/woo-class.php';

// Load the admin specific files.
if ( is_admin() ) {
	require_once __DIR__ . '/includes/admin/menu-items.php';
	require_once __DIR__ . '/includes/admin/post-columns.php';
	require_once __DIR__ . '/includes/admin/admin-assets.php';
	require_once __DIR__ . '/includes/admin/admin-notices.php';
	require_once __DIR__ . '/includes/admin/admin-pages.php';
	require_once __DIR__ . '/includes/admin/admin-process.php';
	require_once __DIR__ . '/includes/admin/list-reviews.php';
	require_once __DIR__ . '/includes/admin/list-attributes.php';
	require_once __DIR__ . '/includes/admin/list-charstcs.php';
}

// Load the front-end specific files.
if ( ! is_admin() ) {
	require_once __DIR__ . '/includes/display.php';
}

// Load the triggered file loads.
require_once __DIR__ . '/includes/activate.php';
require_once __DIR__ . '/includes/deactivate.php';
require_once __DIR__ . '/includes/uninstall.php';

// And my testing.
require_once __DIR__ . '/includes/testing.php';
