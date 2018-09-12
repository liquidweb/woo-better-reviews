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

// Define our database version.
define( __NAMESPACE__ . '\DB_VERS', '1' );
