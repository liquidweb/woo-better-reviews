<?php
/**
 * All functions related to the custom tables.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Tables\Ratings;

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
	$table_name = $wpdb->prefix . Core\TABLE_PREFIX .  'ratings';

	// Setup the SQL syntax.
	//
	// This stores the individual rating
	// scores on a single review.
	//
	$table_args = "
		CREATE TABLE {$table_name} (
			rating_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			review_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			attribute_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			rating_score varchar(8) NOT NULL DEFAULT '',
		PRIMARY KEY  (meta_id),
		KEY `review_id` (`review_id`),
		KEY `attribute_id` (`attribute_id`)
		) $char_coll;
	";

	// Create the actual table.
	dbDelta( $table_args );

	// And return true because it exists.
	return true;
}

/**
 * Insert a single item into the database.
 *
 * @param  array  $insert_args  The data we are inserting.
 *
 * @return boolean
 */
function insert_row( $insert_args = array() ) {
	// @@todo things here
}

/**
 * Update an existing item in the database.
 *
 * @param  array  $update_args  The data we are updating.
 *
 * @return boolean
 */
function update_row( $update_args = array() ) {
	// @@todo things here
}

/**
 * Delete an existing item in the database.
 *
 * @param  integer $delete_id  The ID we are deleting.
 *
 * @return boolean
 */
function delete_row( $delete_id = 0 ) {
	// @@todo things here
}
