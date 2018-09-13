<?php
/**
 * All functions related to the custom tables.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Tables\Authors;

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
	$table_name = $wpdb->prefix . Core\TABLE_PREFIX .  'authors';

	// Setup the SQL syntax.
	//
	// Here, we store the data relating to the review author. If
	// they are an actual customer, we store that ID as well so
	// we can do various queries on it.
	//
	$table_args = "
		CREATE TABLE {$table_name} (
			author_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			author_name varchar(50) NOT NULL,
			author_email varchar(100) NOT NULL,
			customer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY  (author_id)
		) $char_coll;
	";

	// Create the actual table.
	dbDelta( $table_args );

	// And return true because it exists.
	return true;
}
