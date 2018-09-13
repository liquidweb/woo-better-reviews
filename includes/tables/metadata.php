<?php
/**
 * All functions related to the custom tables.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Tables\Metadata;

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
	$table_name = $wpdb->prefix . Core\TABLE_PREFIX .  'metadata';

	// Setup the SQL syntax.
	//
	// This is sort of a catch-all for what may come as this
	// whole system matures. The `object_id` in this case could
	// be an author, product, or attribute.
	//
	$table_args = "
		CREATE TABLE {$table_name} (
			meta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext,
		PRIMARY KEY  (meta_id),
		KEY `object_id` (`object_id`),
		KEY `meta_key` (`meta_key`)
		) $char_coll;
	";

	// Create the actual table.
	dbDelta( $table_args );

	// And return true because it exists.
	return true;
}
