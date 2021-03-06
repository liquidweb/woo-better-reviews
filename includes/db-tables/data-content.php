<?php
/**
 * All functions related to the custom tables.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\Tables\Content;

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
	$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'content';

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
			author_name varchar(250) NOT NULL DEFAULT '',
			author_email varchar(100) NOT NULL DEFAULT '',
			product_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			review_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			review_title text NOT NULL,
			review_slug varchar(200) NOT NULL DEFAULT '',
			review_content longtext NOT NULL,
			review_status varchar(20) NOT NULL DEFAULT 'pending',
			is_verified int(1) NOT NULL DEFAULT 0,
			rating_total_score varchar(8) NOT NULL DEFAULT '',
			rating_attributes longtext NOT NULL DEFAULT '',
			author_charstcs longtext NOT NULL DEFAULT '',
		PRIMARY KEY  (review_id),
		KEY `author_id` (`author_id`),
		KEY `author_email` (`author_email`),
		KEY `product_id` (`product_id`)
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
 * @param  string $return_type  What return type is requested.
 *
 * @return array
 */
function required_args( $return_type = '' ) {

	// Set up the basic array.
	$insert_setup   = array(
		'author_id'           => '%d',
		'author_name'         => '%s',
		'author_email'        => '%s',
		'product_id'          => '%d',
		'review_date'         => '%s',
		'review_title'        => '%s',
		'review_slug'         => '%s',
		'review_content'      => '%s',
		'review_status'       => '%s',
		'is_verified'         => '%d',
		'rating_total_score'  => '%d',
		'rating_attributes'   => '%s',
		'author_charstcs'     => '%s',
	);

	// Return the requested setup.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'formats' :
			return array_values( $insert_setup );
			break;

		case 'columns' :
			return array_keys( $insert_setup );
			break;

		case 'dataset' :
			return $insert_setup;
			break;

		// No more case breaks, no more tables.
	}

	// Bail even though we shouldn't be here.
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
		return new WP_Error( 'missing_insert_args', __( 'The required database arguments are missing or invalid.', 'woo-better-reviews' ) );
	}

	// Do the validations.
	$validate_args  = Database\validate_insert_args( 'content', $insert_args ); // @@todo better return?

	// Return if we're a WP_Error.
	if ( is_wp_error( $validate_args ) ) {
		return $validate_args;
	}

	// Call the global DB.
	global $wpdb;

	// Set our table formatting.
	$table_format   = required_args( 'formats' );

	// Run my insert function.
	$wpdb->insert( $wpdb->wc_better_rvs_content, $insert_args, $table_format );

	// Check for the ID and throw an error if we don't have it.
	if ( ! $wpdb->insert_id ) {
		return new WP_Error( 'database_insert_error', __( 'The data could not be written to the database.', 'woo-better-reviews' ) );
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
		return new WP_Error( 'missing_update_id', __( 'The required ID is missing.', 'woo-better-reviews' ) );
	}

	// Make sure we have args.
	if ( empty( $update_args ) || ! is_array( $update_args ) ) {
		return new WP_Error( 'missing_update_args', __( 'The required database arguments are missing or invalid.', 'woo-better-reviews' ) );
	}

	// Do the validations.
	Database\validate_update_args( 'content', $update_args ); // @@todo better return?

	// Call the global DB.
	global $wpdb;

	// Set our table formatting.
	$table_format   = Database\set_update_format( 'content', $update_args );

	// Run the update process.
	$wpdb->update( $wpdb->wc_better_rvs_content, $update_args, array( 'review_id' => absint( $update_id ) ), $table_format, array( '%d' ) );

	// Return the error if we got one.
	if ( ! empty( $wpdb->last_error ) ) {
		return new WP_Error( 'wpdb_error_return', $wpdb->last_error );
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
		return new WP_Error( 'missing_delete_id', __( 'The required ID is missing.', 'woo-better-reviews' ) );
	}

	// Call the global DB.
	global $wpdb;

	// Run my delete function.
	$wpdb->delete( $wpdb->wc_better_rvs_content, array( 'review_id' => absint( $delete_id ) ) );

	// Return the error if we got one.
	if ( ! empty( $wpdb->last_error ) ) {
		return new WP_Error( 'wpdb_error_return', $wpdb->last_error );
	}

	// Return a boolean based on the rows affected count.
	return ! empty( $wpdb->rows_affected ) ? true : false;
}
