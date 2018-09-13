<?php
/**
 * All functions related to the custom tables.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Database;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;

// Set the various table aliases.
use LiquidWeb\WooBetterReviews\Tables\Content as Content;
use LiquidWeb\WooBetterReviews\Tables\Authors as Authors;
use LiquidWeb\WooBetterReviews\Tables\Attributes as Attributes;
use LiquidWeb\WooBetterReviews\Tables\Metadata as Metadata;
use LiquidWeb\WooBetterReviews\Tables\Relationships as Relationships;

/**
 * Start our engines.
 */
add_action( 'plugins_loaded', __NAMESPACE__ . '\register_tables', 11 );

/**
 * Registers the tables with $wpdb so the metadata api can find it.
 *
 * @return void
 */
function register_tables() {

	// Load the global DB.
	global $wpdb;

	// Set the table variables.
	$wpdb->wc_better_rvs_content       = $wpdb->prefix . Core\TABLE_PREFIX . 'content';
	$wpdb->wc_better_rvs_authors       = $wpdb->prefix . Core\TABLE_PREFIX . 'authors';
	$wpdb->wc_better_rvs_attributes    = $wpdb->prefix . Core\TABLE_PREFIX . 'attributes';
	$wpdb->wc_better_rvs_metadata      = $wpdb->prefix . Core\TABLE_PREFIX . 'metadata';
	$wpdb->wc_better_rvs_relationships = $wpdb->prefix . Core\TABLE_PREFIX . 'relationships';
}

/**
 * Confirm that the table itself actually exists.
 *
 * @param  string $table_name  The name of the specific table.
 *
 * @return boolean
 */
function maybe_table_exists( $table_name = '' ) {

	// Bail if we don't have a name to check.
	if ( empty( $table_name ) ) {
		return false;
	}

	// Call the global class.
	global $wpdb;

	// Set my table name.
	$table  = $wpdb->prefix . Core\TABLE_PREFIX . esc_attr( $table_name );

	// Run the lookup.
	$lookup = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) );

	// We have the table, no need to install it.
	$wpdb->get_var( $lookup ) === $table ? true : false;
}

/**
 * Check to see if we need to run the initial install on tables.
 *
 * @return boolean
 */
function maybe_install_tables() {

	// Get my array of tables.
	$tables = Helpers\get_table_names( true );

	// Bail without tables.
	if ( empty( $tables ) ) {
		return false; // @@todo figure out if better error is needed.
	}

	// Now loop my tables and check.
	foreach ( $tables as $table_name ) {

		// Check if the table exists.
		$table_exists   = maybe_table_exists( $table_name );

		// If it exists, skip it.
		if ( $table_exists ) {
			continue;
		}

		// Now run the actual install.
		install_single_table( $table_name );
	}

	// Run the update schema keys.
	update_option( Core\SCHEMA_KEY, Core\DB_VERS );
}

/**
 * Compare the stored version of the database schema.
 *
 * @return boolean
 */
function maybe_update_tables() {

	// We're already updated and current, so nothing here.
	if ( (int) get_option( Core\SCHEMA_KEY ) === (int) Core\DB_VERS ) {
		return;
	}

	// Run the install setups.
	// @@todo figure out what upgrading looks like.
}

/**
 * Install a single table, as needed.
 *
 * @param  string $table_name  The table name we wanna install.
 *
 * @return boolean
 */
function install_single_table( $table_name = '' ) {

	// Bail if we don't have a name to check.
	if ( empty( $table_name ) ) {
		return false;
	}

	// Handle my different action types.
	switch ( sanitize_text_field( $table_name ) ) {

		case 'content' :
			return Content\install_table();
			break;

		case 'authors' :
			return Authors\install_table();
			break;

		case 'attributes' :
			return Attributes\install_table();
			break;

		case 'metadata' :
			return Metadata\install_table();
			break;

		case 'relationships' :
			return Relationships\install_table();
			break;
	}

	// Hit the end, so false.
	return false;
}

/**
 * Delete a single table, as needed.
 *
 * @param  string $table_name  The table name we wanna delete.
 *
 * @return boolean
 */
function delete_single_table( $table_name = '' ) {

	// Bail if we don't have a name to check.
	if ( empty( $table_name ) ) {
		return false;
	}

	// Call the global class.
	global $wpdb;

	// Set my table name.
	$table  = $wpdb->prefix . Core\TABLE_PREFIX . esc_attr( $table_name );

	// Run the query and return the result.
	return $wpdb->query( "DROP TABLE IF EXISTS $table" );
}

/**
 * Delete all our tables.
 *
 * @return boolean
 */
function delete_all_tables() {

	// Get my array of tables.
	$tables = Helpers\get_table_names( true );

	// Bail without tables.
	if ( empty( $tables ) ) {
		return false; // @@todo figure out if better error is needed.
	}

	// Now loop my tables and check.
	foreach ( $tables as $table_name ) {

		// Attempt to delete the table.
		$table_deleted  = delete_single_table( $table_name );

		// If it worked, carry on.
		if ( $table_deleted ) {
			continue;
		}

		// Failed? That's a false.
		return false;
	}

	// Return that we're done.
	return true;
}

/**
 * Insert a new record into our database
 *
 * @param  string $table_name   Which table we want to insert.
 * @param  array  $insert_args  The individual bits we wanna include.
 *
 * @return boolean
 */
function insert( $table_name = '', $insert_args = array() ) {

	// Make sure we have a table name.
	if ( empty( $table_name ) ) {
		return new WP_Error( 'missing_table_name', __( 'The required table name is missing.', 'woo-better-reviews' ) );
	}

	// Make sure we have args.
	if ( empty( $insert_args ) || ! is_array( $insert_args ) ) {
		return new WP_Error( 'missing_insert_args', __( 'The required database arguments are missing or invalid.', 'woo-better-reviews' ) );
	}

	// Run the action before doing anything.
	do_action( Core\HOOK_PREFIX . 'before_insert', $table_name, $insert_args );

	// Run the action after doing everything.
	do_action( Core\HOOK_PREFIX . 'after_insert', $table_name, $insert_args );

	// Return true.
	return true;
}
