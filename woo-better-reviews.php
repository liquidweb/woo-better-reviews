<?php
/**
 * Plugin Name: Better Reviews For WooCommerce
 * Plugin URI:  https://github.com/liquidweb/woo-better-reviews
 * Description: Like reviews, only way better.
 * Version:     0.2.0-dev
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
define( __NAMESPACE__ . '\VERS', '0.2.0-dev' );

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

// Set the prefixes for meta and option keys.
define( __NAMESPACE__ . '\OPTION_PREFIX', 'wbr_setting_' );
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
require_once __DIR__ . '/includes/db-tables/data-content.php';
require_once __DIR__ . '/includes/db-tables/data-ratings.php';
require_once __DIR__ . '/includes/db-tables/data-authormeta.php';
require_once __DIR__ . '/includes/db-tables/tax-attributes.php';
require_once __DIR__ . '/includes/db-tables/tax-characteristics.php';

// Load my query logic plugin-wide.
require_once __DIR__ . '/includes/queries.php';

// Load the admin specific files.
if ( is_admin() ) {
	require_once __DIR__ . '/includes/admin/menu-items.php';
	require_once __DIR__ . '/includes/admin/post-columns.php';
	require_once __DIR__ . '/includes/admin/admin-assets.php';
	require_once __DIR__ . '/includes/admin/admin-notices.php';
	require_once __DIR__ . '/includes/admin/admin-pages.php';
	require_once __DIR__ . '/includes/admin/product-meta.php';
	require_once __DIR__ . '/includes/list-tables/list-reviews.php';
	require_once __DIR__ . '/includes/list-tables/list-attributes.php';
	require_once __DIR__ . '/includes/list-tables/list-charstcs.php';
	require_once __DIR__ . '/includes/woo/settings.php';
	require_once __DIR__ . '/includes/process/admin-process.php';
}

// Load the front-end specific files.
if ( ! is_admin() ) {
	require_once __DIR__ . '/includes/front-end.php';
	require_once __DIR__ . '/includes/display/form-data.php';
	require_once __DIR__ . '/includes/display/form-fields.php';
	require_once __DIR__ . '/includes/display/view-output.php';
	require_once __DIR__ . '/includes/display/schema-markup.php';
	require_once __DIR__ . '/includes/layout/review-list.php';
	require_once __DIR__ . '/includes/layout/review-aggregate.php';
	require_once __DIR__ . '/includes/layout/single-review.php';
	require_once __DIR__ . '/includes/layout/new-review-form.php';
	require_once __DIR__ . '/includes/woo/filters.php';
	require_once __DIR__ . '/includes/process/form-process.php';
}

// Load our converting and potentially export logic.
require_once __DIR__ . '/includes/process/convert-existing.php';

// Load the triggered file loads.
require_once __DIR__ . '/includes/activate.php';
require_once __DIR__ . '/includes/deactivate.php';
require_once __DIR__ . '/includes/uninstall.php';

// Check that we have the constant available.
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	// Load our commands file.
	require_once __DIR__ . '/includes/process/cli-commands.php';

	// And add our command.
	WP_CLI::add_command( 'woo-better-reviews', WBR_Commands::class );
}
