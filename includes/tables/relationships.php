<?php
/**
 * All functions related to the custom tables.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Tables\Relationships;

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
	$table_name = $wpdb->prefix . Core\TABLE_PREFIX .  'relationships';

	// Setup the SQL syntax.
	//
	// Here, the `object_id` will be either a product ID or
	// an author ID, depending on what the attribute is being
	// applied to.
	//
	$table_args = "
		CREATE TABLE {$table_name} (
			relationship_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			object_id BIGINT UNSIGNED NOT NULL,
			attribute_id BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY  (relationship_id),
		KEY `object_id` (`object_id`),
		KEY `attribute_id` (`attribute_id`)
		) $char_coll;
	";

	// Create the actual table.
	dbDelta( $table_args );

	// And return true because it exists.
	return true;
}
