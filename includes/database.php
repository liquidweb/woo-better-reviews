<?php
/**
 * All functions related to the custom tables.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\Database;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;

// Set the various table aliases.
use Nexcess\WooBetterReviews\Tables\Content as Content;
use Nexcess\WooBetterReviews\Tables\AuthorMeta as AuthorMeta;
use Nexcess\WooBetterReviews\Tables\Ratings as Ratings;
use Nexcess\WooBetterReviews\Tables\Attributes as Attributes;
use Nexcess\WooBetterReviews\Tables\Characteristics as Characteristics;

// And pull in any other namespaces.
use WP_Error;

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
}

/**
 * Check if a given ID exists in a column on a table.
 *
 * @param  integer $lookup_id   The ID of the thing we are checking.
 * @param  string  $table_slug  Which table we want to look in.
 * @param  string  $column      The column we want (if we wanna check it specific).
 *
 * @return boolean
 */
function maybe_id_exists( $lookup_id = 0, $table_slug = '', $column = '' ) {

	// Bail if we don't have the required pieces.
	if ( empty( $lookup_id ) || empty( $table_slug ) ) {
		return false; // @@todo check each item separate and return.
	}

	// If we didn't get a column name, set the variable based on the table.
	if ( empty( $column ) ) {
		$column = get_primary_keys( $table_slug );
	}

	// Make sure we have a column.
	if ( empty( $column ) ) {
		return new WP_Error( 'missing_column_name', __( 'The required column name is missing.', 'woo-better-reviews' ) );
	}

	// Call the global class.
	global $wpdb;

	// Set my table name.
	$table_name = $wpdb->prefix . Core\TABLE_PREFIX . esc_attr( $table_slug );

	// Set up our query.
	$query_args = $wpdb->prepare("
		SELECT   COUNT(*)
		FROM     $table_name
		WHERE    $column = '%d'
	", absint( $lookup_id ) );

	// Process the query.
	$query_run  = $wpdb->get_col( $query_args );

	// Return the result.
	return ! empty( $query_run[0] ) ? true : false; // @@todo return string instead?
}

/**
 * Return the primary key name for each table.
 *
 * @param  integer $lookup_id    The ID of the thing we are checking.
 * @param  string  $table_slug   Which table we want to look in.
 * @param  string  $column_name  The column we want (if we wanna check it specific).
 *
 * @return mixed
 */
function get_primary_keys( $table_name = '' ) {

	// Set up the array.
	$primary_keys   = array(
		'content'      => 'review_id',
		'authormeta'   => 'ameta_id',
		'ratings'      => 'rating_id',
		'attributes'   => 'attribute_id',
		'charstcs_id'  => 'charstcs_id',
	);

	// If we didn't specify a table name, return the whole thing.
	if ( empty( $table_name ) ) {
		return $primary_keys;
	}

	// Now return the single item or false if it doesn't exist.
	return ! empty( $primary_keys[ $table_name ] ) ? $primary_keys[ $table_name ] : false;
}

/**
 * Include the required items when making DB changes.
 *
 * @param  string $table_name   The name of the table we are working with.
 * @param  string $return_type  What return type is requested.
 *
 * @return array
 */
function get_required_args( $table_name = '', $return_type = 'columns' ) {

	// Bail if we don't have a name to check.
	if ( empty( $table_name ) ) {
		return new WP_Error( 'missing_table_name', __( 'The required table name was not provided.', 'woo-better-reviews' ) );
	}

	// Fetch the required args based on the table requested.
	switch ( sanitize_text_field( $table_name ) ) {

		case 'content' :
			return Content\required_args( $return_type );
			break;

		case 'authormeta' :
			return AuthorMeta\required_args( $return_type );
			break;

		case 'ratings' :
			return Ratings\required_args( $return_type );
			break;

		case 'attributes' :
			return Attributes\required_args( $return_type );
			break;

		case 'charstcs' :
			return Characteristics\required_args( $return_type );
			break;

		// No more case breaks, no more tables.
	}

	// Got none, say none.
	return false;
}

/**
 * Check that we have the required args for a DB insert.
 *
 * @param  string $table_name   What table we are doing, which will determine the checks.
 * @param  array  $insert_args  What the specific args are.
 *
 * @return mixed
 */
function validate_insert_args( $table_name = '', $insert_args = array() ) {

	// Bail if we don't have a name to check.
	if ( empty( $table_name ) ) {
		return new WP_Error( 'no_table_name', __( 'The required table name was not provided.', 'woo-better-reviews' ) );
	}

	// Bail if we don't have args to check.
	if ( empty( $insert_args ) ) {
		return new WP_Error( 'missing_insert_args', __( 'The required arguments were was not provided.', 'woo-better-reviews' ) );
	}

	// Get the requirements for the table.
	$required_args  = get_required_args( $table_name );

	// Bail without the args to check.
	if ( ! $required_args ) {
		return new WP_Error( 'no_required_args', __( 'No required arguments could be found.', 'woo-better-reviews' ) );
	}

	// Loop our requirements and check.
	foreach ( $required_args as $required_arg ) {

		// Check if it is in the array.
		if ( array_key_exists( $required_arg, $insert_args ) ) {
			continue;
		}

		// Set a variable for the arg display if returned.
		$arg_formatted  = '<code>' . esc_attr( $required_arg ) . '</code>';

		// Not in the array? Return the error.
		return new WP_Error( 'missing_required_arg', sprintf( __( 'The required %s argument is missing.', 'woo-better-reviews' ), $arg_formatted ) );
	}

	// We good!
	return true;
}

/**
 * Check that we have the required args for a DB update.
 *
 * @param  string $table_name   What table we are doing, which will determine the checks.
 * @param  array  $update_args  What the specific args are.
 *
 * @return mixed
 */
function validate_update_args( $table_name = '', $update_args = array() ) {

	// Bail if we don't have a name to check.
	if ( empty( $table_name ) ) {
		return new WP_Error( 'no_table_name', __( 'The required table name was not provided.', 'woo-better-reviews' ) );
	}

	// Bail if we don't have args to check.
	if ( empty( $update_args ) ) {
		return new WP_Error( 'missing_update_args', __( 'The required arguments were not provided.', 'woo-better-reviews' ) );
	}

	// Get the requirements for the table.
	$required_args  = get_required_args( $table_name );

	// Bail without the args to check.
	if ( ! $required_args ) {
		return new WP_Error( 'no_required_args', __( 'No required arguments could be found.', 'woo-better-reviews' ) );
	}

	// Loop the args we have present and check.
	foreach ( $update_args as $update_key => $update_value ) {

		// Check if it is in the array.
		if ( in_array( $update_key, $required_args ) ) {
			continue;
		}

		// Set a variable for the arg display if returned.
		$arg_formatted  = '<code>' . esc_attr( $update_key ) . '</code>';

		// Not in the array? Return the error.
		return new WP_Error( 'invalid_arg_provided', sprintf( __( 'The %s argument is not valid for this table.', 'woo-better-reviews' ), $arg_formatted ) );
	}

	// We good!
	return true;
}

/**
 * Format the args we are updating.
 *
 * @param  string $table_name   What table we are doing, which will determine the checks.
 * @param  array  $update_args  What the specific args are.
 *
 * @return mixed
 */
function set_update_format( $table_name = '', $update_args = array() ) {

	// Bail if we don't have a name to check.
	if ( empty( $table_name ) ) {
		return new WP_Error( 'no_table_name', __( 'The required table name was not provided.', 'woo-better-reviews' ) );
	}

	// Bail if we don't have args to check.
	if ( empty( $update_args ) ) {
		return new WP_Error( 'missing_update_args', __( 'The required arguments were not provided.', 'woo-better-reviews' ) );
	}

	// Get the formats for the table.
	$table_dataset  = get_required_args( $table_name, 'dataset' );

	// Bail without the args to check.
	if ( ! $table_dataset ) {
		return new WP_Error( 'no_table_datasets', __( 'No argument formatting could be found.', 'woo-better-reviews' ) );
	}

	// Set an empty.
	$formats    = array();

	// Loop the args we have present and check.
	foreach ( $table_dataset as $column_key => $column_format ) {

		// If we don't have the column, skip it.
		if ( ! array_key_exists( $column_key, $update_args ) ) {
			continue;
		}

		// Now set up the array of formats.
		$formats[]  = $column_format;
	}

	// We good!
	return ! empty( $formats ) ? $formats : false;
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
		return false;
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

	// And return true.
	return true;
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
	// We don't actually have one right now.
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

		// No more case breaks, no more tables.
	}

	// Hit the end, so false.
	return false;
}

/**
 * Check the settings and then drop on delete.
 *
 * @return mixed / boolean
 */
function maybe_drop_tables() {

	// Check to see if we are supposed to purge.
	$maybe_preserve = Helpers\maybe_preserve_on_delete();

	// Bail if we didn't return a hard false.
	if ( false !== $maybe_preserve ) {
		return;
	}

	// If we are supposed to purge, DO IT.
	return drop_all_tables();
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
 * Actually delete all our tables, without the check function.
 *
 * @return boolean
 */
function drop_all_tables() {

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
		return new WP_Error( 'missing-table-name', __( 'The required table name is missing.', 'woo-better-reviews' ) );
	}

	// Make sure we have args.
	if ( empty( $insert_args ) || ! is_array( $insert_args ) ) {
		return new WP_Error( 'missing_insert_args', __( 'The required database arguments are missing or invalid.', 'woo-better-reviews' ) );
	}

	// Check to make sure the table provided is approved.
	$table_valid   = Helpers\maybe_valid_table( $table_name );

	// Throw an error if it's not a valid table.
	if ( ! $table_valid ) {
		return new WP_Error( 'invalid-table-name', __( 'The provided table name is not valid.', 'woo-better-reviews' ) );
	}

	// Make sure we have args.
	if ( empty( $insert_args ) || ! is_array( $insert_args ) ) {
		return new WP_Error( 'missing-insert-args', __( 'The required database arguments are missing or invalid.', 'woo-better-reviews' ) );
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
 * @param  string  $table_name   Which table we want to update in.
 * @param  array   $update_args  The individual bits we wanna include.
 * @param  integer $update_id    The ID of the thing we are updating.
 *
 * @return boolean
 */
function update( $table_name = '', $update_id = 0, $update_args = array() ) {

	// Make sure we have a table name.
	if ( empty( $table_name ) ) {
		return new WP_Error( 'missing_table_name', __( 'The required table name is missing.', 'woo-better-reviews' ) );
	}

	// Make sure we have args.
	if ( empty( $update_args ) || ! is_array( $update_args ) ) {
		return new WP_Error( 'missing_update_args', __( 'The required database arguments are missing or invalid.', 'woo-better-reviews' ) );
	}

	// Make sure we have an ID.
	if ( empty( $update_id ) ) {
		return new WP_Error( 'missing_update_id', __( 'The required ID was missing or invalid.', 'woo-better-reviews' ) );
	}

	// Check to make sure the table provided is approved.
	$table_valid   = Helpers\maybe_valid_table( $table_name );

	// Throw an error if it's not a valid table.
	if ( ! $table_valid ) {
		return new WP_Error( 'invalid_table_name', __( 'The provided table name is not valid.', 'woo-better-reviews' ) );
	}

	// Get my primary key.
	$primary_key    = get_primary_keys( $table_name );

	// Make sure it exists.
	$maybe_exists   = maybe_id_exists( $update_id, $table_name, $primary_key );

	// If the ID doesn't exist, bail.
	if ( ! $maybe_exists ) {
		return new WP_Error( 'invalid_update_id', __( 'The provided ID does not exist in the database.', 'woo-better-reviews' ) );
	}

	// Run the action before doing anything.
	do_action( Core\HOOK_PREFIX . 'before_update', $table_name, $update_id, $update_args );

	// Handle the database insert based on the provided name.
	switch ( sanitize_text_field( $table_name ) ) {

		case 'content' :
			return Content\update_row( $update_id, $update_args );
			break;

		case 'authormeta' :
			return AuthorMeta\update_row( $update_id, $update_args );
			break;

		case 'ratings' :
			return Ratings\update_row( $update_id, $update_args );
			break;

		case 'attributes' :
			return Attributes\update_row( $update_id, $update_args );
			break;

		case 'charstcs' :
			return Characteristics\update_row( $update_id, $update_args );
			break;

		// No more case breaks, no more tables.
	}

	// Run the action after doing everything.
	do_action( Core\HOOK_PREFIX . 'after_update', $table_name, $update_id, $update_args );

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

	// Get my primary key.
	$primary_key    = get_primary_keys( $table_name );

	// Make sure it exists.
	$maybe_exists   = maybe_id_exists( $delete_id, $table_name, $primary_key );

	// If the ID doesn't exist, bail.
	if ( ! $maybe_exists ) {
		return new WP_Error( 'invalid_delete_id', __( 'The provided ID does not exist in the database.', 'woo-better-reviews' ) );
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

		// No more case breaks, no more tables.
	}

	// Run the action after doing everything.
	do_action( Core\HOOK_PREFIX . 'after_delete', $table_name, $delete_id );

	// Return true.
	return true;
}
