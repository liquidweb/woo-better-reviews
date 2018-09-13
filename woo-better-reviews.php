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

// Set the prefix for our actions and filters.
define( __NAMESPACE__ . '\HOOK_PREFIX', 'wc_better_reviews_' );

// Set our custom table prefix.
define( __NAMESPACE__ . '\TABLE_PREFIX', 'woocommerce_better_reviews_' );

// Set the option key and DB versions used to store the schemas.
define( __NAMESPACE__ . '\DB_VERS', '1' );
define( __NAMESPACE__ . '\SCHEMA_KEY', HOOK_PREFIX . 'db_version' );

// Load the multi-use files first.
require_once __DIR__ . '/includes/helpers.php';

// Load the database and custom table items.
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/tables/content.php';
require_once __DIR__ . '/includes/tables/authors.php';
require_once __DIR__ . '/includes/tables/attributes.php';
require_once __DIR__ . '/includes/tables/metadata.php';
require_once __DIR__ . '/includes/tables/relationships.php';

// Load the triggered file loads.
require_once __DIR__ . '/includes/activate.php';
require_once __DIR__ . '/includes/deactivate.php';
require_once __DIR__ . '/includes/uninstall.php';
