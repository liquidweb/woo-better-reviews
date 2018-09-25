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
use LiquidWeb\WooBetterReviews\Tables\AuthorMeta as AuthorMeta;
use LiquidWeb\WooBetterReviews\Tables\Ratings as Ratings;
use LiquidWeb\WooBetterReviews\Tables\Attributes as Attributes;
use LiquidWeb\WooBetterReviews\Tables\Characteristics as Characteristics;
use LiquidWeb\WooBetterReviews\Tables\ProductSetup as ProductSetup;

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

	// Set the data focused tables.
	$wpdb->wc_better_rvs_content      = $wpdb->prefix . Core\TABLE_PREFIX . 'content';
	$wpdb->wc_better_rvs_authormeta   = $wpdb->prefix . Core\TABLE_PREFIX . 'authormeta';
	$wpdb->wc_better_rvs_ratings      = $wpdb->prefix . Core\TABLE_PREFIX . 'ratings';

	// Set the taxonomy focused tables.
	$wpdb->wc_better_rvs_attributes   = $wpdb->prefix . Core\TABLE_PREFIX . 'attributes';
	$wpdb->wc_better_rvs_charstcs     = $wpdb->prefix . Core\TABLE_PREFIX . 'charstcs';

	// Set the grouping focused tables.
	$wpdb->wc_better_rvs_productsetup = $wpdb->prefix . Core\TABLE_PREFIX . 'productsetup';
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
	$tables = Helpers\get_table_args( true );

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

	// Handle the table install based on the provided name.
	switch ( sanitize_text_field( $table_name ) ) {

		case 'content' :
			return Content\install_table();
			break;

		case 'authormeta' :
			return AuthorMeta\install_table();
			break;

		case 'ratings' :
			return Ratings\install_table();
			break;

		case 'attributes' :
			return Attributes\install_table();
			break;

		case 'charstcs' :
			return Characteristics\install_table();
			break;

		case 'productsetup' :
			return ProductSetup\install_table();
			break;

		// No more case breaks, no more tables.
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
function drop_single_table( $table_name = '' ) {

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
function drop_tables() {

	// Get my array of table names.
	$table_names    = Helpers\get_table_args( true );

	// Bail without tables.
	if ( empty( $table_names ) ) {
		return false; // @@todo figure out if better error is needed.
	}

	// Now loop my tables and check.
	foreach ( $table_names as $table_name ) {

		// Attempt to delete the table.
		$table_dropped  = drop_single_table( $table_name );

		// If it worked, carry on.
		if ( $table_dropped ) {
			continue;
		}

		// Failed? That's a false.
		return false;
	}

	// Return that we're done.
	return true;
}

/**
 * Insert a new record into our database.
 *
 * @param  string $table_name   Which table we want to insert to.
 * @param  array  $insert_args  The individual bits we wanna include.
 *
 * @return boolean
 */
function insert( $table_name = '', $insert_args = array() ) {

	// Make sure we have a table name.
	if ( empty( $table_name ) ) {
		return new WP_Error( 'missing_table_name', __( 'The required table name is missing.', 'woo-better-reviews' ) );
	}

	// Check to make sure the table provided is approved.
	$table_valid   = Helpers\maybe_valid_table( $table_name );

	// Throw an error if it's not a valid table.
	if ( ! $table_valid ) {
		return new WP_Error( 'invalid_table_name', __( 'The provided table name is not valid.', 'woo-better-reviews' ) );
	}

	// Make sure we have args.
	if ( empty( $insert_args ) || ! is_array( $insert_args ) ) {
		return new WP_Error( 'missing_insert_args', __( 'The required database arguments are missing or invalid.', 'woo-better-reviews' ) );
	}

	// Run the action before doing anything.
	do_action( Core\HOOK_PREFIX . 'before_insert', $table_name, $insert_args );

	// Handle the database insert based on the provided name.
	switch ( sanitize_text_field( $table_name ) ) {

		case 'content' :
			return Content\insert_row( $insert_args );
			break;

		case 'authormeta' :
			return AuthorMeta\insert_row( $insert_args );
			break;

		case 'ratings' :
			return Ratings\insert_row( $insert_args );
			break;

		case 'attributes' :
			return Attributes\insert_row( $insert_args );
			break;

		case 'charstcs' :
			return Characteristics\insert_row( $insert_args );
			break;

		case 'productsetup' :
			return ProductSetup\insert_row( $insert_args );
			break;

		// No more case breaks, no more tables.
	}

	// Run the action after doing everything.
	do_action( Core\HOOK_PREFIX . 'after_insert', $table_name, $insert_args );

	// Return true.
	return true;
}

/**
 * Update an existing record in our database.
 *
 * @param  string $table_name   Which table we want to update in.
 * @param  array  $update_args  The individual bits we wanna include.
 *
 * @return boolean
 */
function update( $table_name = '', $update_args = array() ) {

	// Make sure we have a table name.
	if ( empty( $table_name ) ) {
		return new WP_Error( 'missing_table_name', __( 'The required table name is missing.', 'woo-better-reviews' ) );
	}

	// Check to make sure the table provided is approved.
	$table_valid   = Helpers\maybe_valid_table( $table_name );

	// Throw an error if it's not a valid table.
	if ( ! $table_valid ) {
		return new WP_Error( 'invalid_table_name', __( 'The provided table name is not valid.', 'woo-better-reviews' ) );
	}

	// Make sure we have args.
	if ( empty( $update_args ) || ! is_array( $update_args ) ) {
		return new WP_Error( 'missing_update_args', __( 'The required database arguments are missing or invalid.', 'woo-better-reviews' ) );
	}

	// Run the action before doing anything.
	do_action( Core\HOOK_PREFIX . 'before_update', $table_name, $update_args );

	// Handle the database insert based on the provided name.
	switch ( sanitize_text_field( $table_name ) ) {

		case 'content' :
			return Content\update_row( $update_args );
			break;

		case 'authormeta' :
			return AuthorMeta\update_row( $update_args );
			break;

		case 'ratings' :
			return Ratings\update_row( $update_args );
			break;

		case 'attributes' :
			return Attributes\update_row( $update_args );
			break;

		case 'charstcs' :
			return Characteristics\update_row( $update_args );
			break;

		case 'productsetup' :
			return ProductSetup\update_row( $update_args );
			break;

		// No more case breaks, no more tables.
	}

	// Run the action after doing everything.
	do_action( Core\HOOK_PREFIX . 'after_update', $table_name, $update_args );

	// Return true.
	return true;
}

/**
 * Delete an existing record in our database.
 *
 * @param  string  $table_name   Which table we want to delete from.
 * @param  integer $delete_id    The ID of the thing we are deleting.
 *
 * @return boolean
 */
function delete( $table_name = '', $delete_id = 0 ) {

	// Make sure we have a table name.
	if ( empty( $table_name ) ) {
		return new WP_Error( 'missing_table_name', __( 'The required table name is missing.', 'woo-better-reviews' ) );
	}

	// Check to make sure the table provided is approved.
	$table_valid   = Helpers\maybe_valid_table( $table_name );

	// Throw an error if it's not a valid table.
	if ( ! $table_valid ) {
		return new WP_Error( 'invalid_table_name', __( 'The provided table name is not valid.', 'woo-better-reviews' ) );
	}

	// Make sure we have args.
	if ( empty( $delete_id ) ) {
		return new WP_Error( 'missing_delete_id', __( 'The required ID was missing or invalid.', 'woo-better-reviews' ) );
	}

	// Run the action before doing anything.
	do_action( Core\HOOK_PREFIX . 'before_delete', $table_name, $delete_id );

	// Handle the database insert based on the provided name.
	switch ( sanitize_text_field( $table_name ) ) {

		case 'content' :
			return Content\delete_row( $delete_id );
			break;

		case 'authormeta' :
			return AuthorMeta\delete_row( $delete_id );
			break;

		case 'ratings' :
			return Ratings\delete_row( $delete_id );
			break;

		case 'attributes' :
			return Attributes\delete_row( $delete_id );
			break;

		case 'charstcs' :
			return Characteristics\delete_row( $delete_id );
			break;

		case 'productsetup' :
			return ProductSetup\delete_row( $delete_id );
			break;

		// No more case breaks, no more tables.
	}

	// Run the action after doing everything.
	do_action( Core\HOOK_PREFIX . 'after_delete', $table_name, $delete_id );

	// Return true.
	return true;
}
