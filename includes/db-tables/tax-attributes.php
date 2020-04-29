<?php
/**
 * All functions related to the custom tables.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\Tables\Attributes;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

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
	$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'attributes';

	// Setup the SQL syntax.
	//
	// This stores the individual review attributes that can
	// be applied to a product. The results are stored in the
	// data-ratings table.
	//
	$table_args = "
		CREATE TABLE {$table_name} (
			attribute_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			attribute_name varchar(200) NOT NULL DEFAULT '',
			attribute_slug varchar(200) NOT NULL DEFAULT '',
			attribute_desc longtext NOT NULL DEFAULT '',
			min_label varchar(100) NOT NULL DEFAULT '',
			max_label varchar(100) NOT NULL DEFAULT '',
		PRIMARY KEY  (attribute_id)
		) $char_coll;
	";

	// Create the actual table.
	dbDelta( $table_args );

	// And return true because it exists.
	return true;
}

/**
 * Set each required item for a database insert.
 *
 * @param  boolean $format_args  Whether to include the formatting arg.
 *
 * @return array
 */
function required_args( $format_args = 'columns' ) {

	// Set up the basic array.
	$insert_setup   = array(
		'attribute_name' => '%s',
		'attribute_slug' => '%s',
		'attribute_desc' => '%s',
		'min_label'      => '%s',
		'max_label'      => '%s',
	);

	// Handle my different formatting args.
	switch ( esc_attr( $format_args ) ) {

		case 'formats' :
			return array_values( $insert_setup );
			break;

		case 'columns' :
			return array_keys( $insert_setup );
			break;

		case 'dataset' :
			return $insert_setup;
			break;

		// End all case breaks.
	}

	// Return a false if we didn't request the right kind.
	return false;
}

/**
 * Insert a single item into the database.
 *
 * @param  array  $insert_args  The data we are inserting.
 *
 * @return boolean
 */
function insert_row( $insert_args = array() ) {

	// Make sure we have args.
	if ( empty( $insert_args ) || ! is_array( $insert_args ) ) {
		return new WP_Error( 'missing-insert-args', __( 'The required database arguments are missing or invalid.', 'woo-better-reviews' ) );
	}

	// Do the validations.
	$validate_args  = Database\validate_insert_args( 'attributes', $insert_args ); // @@todo better return?

	// Return the WP_Error return.
	if ( is_wp_error( $validate_args ) ) {
		return $validate_args;
	}

	// Call the global DB.
	global $wpdb;

	// Set our table formatting.
	$table_format   = required_args( 'formats' );

	// Run my insert function.
	$wpdb->insert( $wpdb->wc_better_rvs_attributes, $insert_args, $table_format );

	// Check for the ID and throw an error if we don't have it.
	if ( ! $wpdb->insert_id ) {
		return new WP_Error( 'database-insert-error', __( 'The data could not be written to the database.', 'woo-better-reviews' ) );
	}

	// Return the new ID.
	return $wpdb->insert_id;
}

/**
 * Update an existing item in the database.
 *
 * @param  integer $update_id    The ID we are updating.
 * @param  array   $update_args  The data we are updating.
 * @param  boolean $return_bool  Whether to return a boolean or string.
 *
 * @return mixed
 */
function update_row( $update_id = 0, $update_args = array(), $return_bool = true ) {

	// Make sure we have an ID.
	if ( empty( $update_id ) ) {
		return new WP_Error( 'missing-update-id', __( 'The required ID is missing.', 'woo-better-reviews' ) );
	}

	// Make sure we have args.
	if ( empty( $update_args ) || ! is_array( $update_args ) ) {
		return new WP_Error( 'missing-update-args', __( 'The required database arguments are missing or invalid.', 'woo-better-reviews' ) );
	}

	// Do the validations.
	$validate_args  = Database\validate_update_args( 'attributes', $update_args ); // @@todo better return?

	// Return the WP_Error return.
	if ( is_wp_error( $validate_args ) ) {
		return $validate_args;
	}

	// Call the global DB.
	global $wpdb;

	// Set our table formatting.
	$table_format   = Database\set_update_format( 'attributes', $update_args );

	// Run the update process.
	$wpdb->update( $wpdb->wc_better_rvs_attributes, $update_args, array( 'attribute_id' => absint( $update_id ) ), $table_format, array( '%d' ) );

	// Return the error if we got one.
	if ( ! empty( $wpdb->last_error ) ) {
		return new WP_Error( 'wpdb-error-return', $wpdb->last_error );
	}

	// If we want a boolean, return that.
	if ( false !== $return_bool ) {
		return true;
	}

	// Return a boolean based on the rows affected count.
	return ! empty( $wpdb->rows_affected ) ? 'updated' : 'unchanged';
}

/**
 * Delete an existing item in the database.
 *
 * @param  integer $delete_id  The ID we are deleting.
 *
 * @return boolean
 */
function delete_row( $delete_id = 0 ) {

	// Make sure we have an ID.
	if ( empty( $delete_id ) ) {
		return new WP_Error( 'missing-delete_id', __( 'The required ID is missing.', 'woo-better-reviews' ) );
	}

	// Call the global DB.
	global $wpdb;

	// Run my delete function.
	$wpdb->delete( $wpdb->wc_better_rvs_attributes, array( 'attribute_id' => absint( $delete_id ) ) );

	// Return the error if we got one.
	if ( ! empty( $wpdb->last_error ) ) {
		return new WP_Error( 'wpdb-error-return', $wpdb->last_error );
	}

	// Return a boolean based on the rows affected count.
	return ! empty( $wpdb->rows_affected ) ? true : false;
}
