<?php
/**
 * All functions related to the custom tables.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Tables\Attributes;

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
	$table_name = $wpdb->prefix . Core\TABLE_PREFIX .  'attributes';

	// Setup the SQL syntax.
	//
	// Here, we are setting attributes, acting like a taxonomy. The
	// attribute_type will be set to either "product" or "author", which
	// will make them available to be set in the review form.
	//
	$table_args = "
		CREATE TABLE {$table_name} (
			attribute_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			attribute_name varchar(200) NOT NULL DEFAULT '',
			attribute_desc text NOT NULL DEFAULT '',
			attribute_slug varchar(200) NOT NULL DEFAULT '',
			attribute_type varchar(50) NOT NULL DEFAULT '',
		PRIMARY KEY  (attribute_id)
		) $char_coll;
	";

	// Create the actual table.
	dbDelta( $table_args );

	// And return true because it exists.
	return true;
}
