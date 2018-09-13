<?php
/**
 * All functions related to the custom tables.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Tables\Content;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;

/**
 * Create our custom table to store the review data.
 *
 * @return void
 */
function install_table() {

	// Pull in the upgrade functions.
	if ( ! function_exists( 'dbDelta' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	// Load the WPDB global.
	global $wpdb;

	// Pull our character set and collating.
	$char_coll  = $wpdb->get_charset_collate();

	// Set our table name.
	$table_name = $wpdb->prefix . Core\TABLE_PREFIX .  'content';

	// Setup the SQL syntax.
	//
	// Here, the bulk of the individual review data will
	// be stored. This will be constructed similar to the
	// WP_Posts table.
	//
	$table_args = "
		CREATE TABLE {$table_name} (
			review_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			author_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			product_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			review_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			review_content longtext NOT NULL,
			review_title text NOT NULL,
			review_status varchar(20) NOT NULL DEFAULT 'pending',
			review_name varchar(200) NOT NULL DEFAULT '',
			review_score varchar(8) NOT NULL DEFAULT 0,
			verified_purchase int(1) NOT NULL DEFAULT 0,
		PRIMARY KEY  (review_id),
		KEY `author_id` (`author_id`),
		KEY `product_id` (`product_id`)
		) $char_coll;
	";

	// Create the actual table.
	dbDelta( $table_args );

	// And return true because it exists.
	return true;
}
