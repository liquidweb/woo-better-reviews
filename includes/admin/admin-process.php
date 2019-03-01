<?php
/**
 * Checks for and runs the various admin-side processes.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Admin\AdminProcess;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_filter( 'removable_query_args', __NAMESPACE__ . '\filter_removable_args' );

add_action( 'admin_init', __NAMESPACE__ . '\add_new_attribute' );
add_action( 'admin_init', __NAMESPACE__ . '\update_existing_attribute' );
add_action( 'admin_init', __NAMESPACE__ . '\delete_existing_attribute' );
add_action( 'admin_init', __NAMESPACE__ . '\add_new_charstcs' );
add_action( 'admin_init', __NAMESPACE__ . '\update_existing_charstcs' );
add_action( 'admin_init', __NAMESPACE__ . '\delete_existing_charstcs' );

/**
 * Add our custom strings to the vars.
 *
 * @param  array $args  The existing array of args.
 *
 * @return array $args  The modified array of args.
 */
function filter_removable_args( $args ) {

	// Set an array of the args we wanna exclude.
	$remove = array(
		'wbr-item-type',
		'wbr-action-complete',
		'wbr-action-result',
		'wbr-action-return',
		'wbr-nonce',
		'wbr-error-code',
	);

	// Set the array of new args.
	$setup  = apply_filters( Core\HOOK_PREFIX . 'removable_args', $remove );

	// Include my new args and return.
	return ! empty( $setup ) ? wp_parse_args( $setup, $args ) : $args;
}

/**
 * Check for the add new function of an attribute.
 *
 * @return void
 */
function add_new_attribute() {

	// Confirm we're on the right place.
	if ( ! isset( $_POST['add-new-attribute'] ) || empty( $_POST['action'] ) || 'add-new' !== sanitize_text_field( $_POST['action'] ) ) {
		return;
	}

	// Handle the nonce check.
	if ( empty( $_POST['wbr_add_attribute_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_add_attribute_nonce'], 'wbr_add_attribute_action' ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Create my base redirect link.
	$base_redirect  = Helpers\get_admin_menu_link( Core\ATTRIBUTES_ANCHOR );

	// Check for the item type.
	if ( empty( $_POST['item-type'] ) || 'attribute' !== sanitize_text_field( $_POST['item-type'] ) ) {
		redirect_admin_action_result( $base_redirect, 'missing-required-args' );
	}

	// Bail if we don't have a name to check.
	if ( empty( $_POST['new-attribute'] ) ) {
		redirect_admin_action_result( $base_redirect, 'missing-attribute-args' );
	}

	// Format the arguments passed for updating.
	$formatted_args = format_attribute_db_args( $_POST['new-attribute'] );

	// Bail without the args coming back.
	if ( empty( $formatted_args ) ) {
		redirect_admin_action_result( $base_redirect, 'missing-formatted-args' );
	}

	// Run the update.
	$maybe_inserted = Database\insert( 'attributes', $formatted_args );

	// Check for the boolean true result.
	if ( empty( $maybe_inserted ) || false === $maybe_inserted ) {
		redirect_admin_action_result( $base_redirect, 'attribute-insert-failed' );
	}

	// Check the result for a WP_Error instance.
	if ( is_wp_error( $maybe_inserted ) ) {
		redirect_admin_action_result( $base_redirect, $maybe_inserted->get_error_code() );
	}

	// Redirect a happy one.
	redirect_admin_action_result( $base_redirect, false, 'attribute-added', true );
}

/**
 * Check for the add new function of an charstcs.
 *
 * @return void
 */
function add_new_charstcs() {

	// Confirm we're on the right place.
	if ( ! isset( $_POST['add-new-charstc'] ) || empty( $_POST['action'] ) || 'add-new' !== sanitize_text_field( $_POST['action'] ) ) {
		return;
	}

	// Handle the nonce check.
	if ( empty( $_POST['wbr_add_charstcs_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_add_charstcs_nonce'], 'wbr_add_charstcs_action' ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Create my base redirect link.
	$base_redirect  = Helpers\get_admin_menu_link( Core\CHARSTCS_ANCHOR );

	// Check for the item type.
	if ( empty( $_POST['item-type'] ) || 'charstc' !== sanitize_text_field( $_POST['item-type'] ) ) {
		redirect_admin_action_result( $base_redirect, 'missing-required-args' );
	}

	// Bail if we don't have a name to check.
	if ( empty( $_POST['new-charstc'] ) ) {
		redirect_admin_action_result( $base_redirect, 'missing-charstcs-args' );
	}

	// Format the arguments passed for updating.
	$formatted_args = format_charstcs_db_args( $_POST['new-charstc'] );

	// Bail without the args coming back.
	if ( empty( $formatted_args ) ) {
		redirect_admin_action_result( $base_redirect, 'missing-formatted-args' );
	}

	// Run the update.
	$maybe_inserted = Database\insert( 'charstcs', $formatted_args );

	// Check for the boolean true result.
	if ( empty( $maybe_inserted ) || false === $maybe_inserted ) {
		redirect_admin_action_result( $base_redirect, 'charstcs-insert-failed' );
	}

	// Check the result for a WP_Error instance.
	if ( is_wp_error( $maybe_inserted ) ) {
		redirect_admin_action_result( $base_redirect, $maybe_inserted->get_error_code() );
	}

	// Redirect a happy one.
	redirect_admin_action_result( $base_redirect, false, 'charstcs-added', true );
}

/**
 * Check for the editing function of an attribute.
 *
 * @return void
 */
function update_existing_attribute() {

	// Confirm we're on the right place.
	if ( ! isset( $_POST['edit-existing-attribute'] ) || empty( $_POST['action'] ) || 'update' !== sanitize_text_field( $_POST['action'] ) ) {
		return;
	}

	// Handle the nonce check.
	if ( empty( $_POST['wbr_edit_attribute_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_edit_attribute_nonce'], 'wbr_edit_attribute_action' ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Confirm we have an ID, which is sorta critical.
	if ( empty( $_POST['item-id'] ) ) {
		redirect_admin_action_result( Core\ATTRIBUTES_ANCHOR, 'missing-item-id' );
	}

	// Make my edit link.
	$edit_redirect  = create_item_action_link( $_POST['item-id'] );

	// Check for the remainder of items.
	if ( empty( $_POST['item-type'] ) || 'attribute' !== sanitize_text_field( $_POST['item-type'] ) ) {
		redirect_admin_action_result( $edit_redirect, 'missing-posted-args' );
	}

	// Bail if we don't have a name to check.
	if ( empty( $_POST['attribute-args'] ) ) {
		redirect_admin_action_result( $edit_redirect, 'missing-attribute-args' );
	}

	// Format the arguments passed for updating.
	$formatted_args = format_attribute_db_args( $_POST['attribute-args'] );

	// Bail without the args coming back.
	if ( empty( $formatted_args ) ) {
		redirect_admin_action_result( $edit_redirect, 'missing-formatted-args' );
	}

	// Run the update.
	$maybe_updated  = Database\update( 'attributes', absint( $_POST['item-id'] ), $formatted_args );

	// Check for the boolean true result.
	if ( empty( $maybe_updated ) || false === $maybe_updated ) {
		redirect_admin_action_result( $edit_redirect, 'attribute-update-failed' );
	}

	// Check the result for a WP_Error instance.
	if ( is_wp_error( $maybe_updated ) ) {
		redirect_admin_action_result( $edit_redirect, $maybe_updated->get_error_code() );
	}

	// Redirect a happy one.
	redirect_admin_action_result( $edit_redirect, false, 'attribute-updated', true, Core\ATTRIBUTES_ANCHOR );
}

/**
 * Check for the editing function of an charstcs.
 *
 * @return void
 */
function update_existing_charstcs() {

	// Confirm we're on the right place.
	if ( ! isset( $_POST['edit-existing-charstcs'] ) || empty( $_POST['action'] ) || 'update' !== sanitize_text_field( $_POST['action'] ) ) {
		return;
	}

	// Handle the nonce check.
	if ( empty( $_POST['wbr_edit_charstcs_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_edit_charstcs_nonce'], 'wbr_edit_charstcs_action' ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Confirm we have an ID, which is sorta critical.
	if ( empty( $_POST['item-id'] ) ) {
		redirect_admin_action_result( Core\CHARSTCS_ANCHOR, 'missing-item-id' );
	}

	// Make my edit link.
	$edit_redirect  = create_item_action_link( $_POST['item-id'], 'charstcs' );

	// Check for the remainder of items.
	if ( empty( $_POST['item-type'] ) || 'charstcs' !== sanitize_text_field( $_POST['item-type'] ) ) {
		redirect_admin_action_result( $edit_redirect, 'missing-posted-args' );
	}

	// Bail if we don't have a name to check.
	if ( empty( $_POST['charstcs-args'] ) ) {
		redirect_admin_action_result( $edit_redirect, 'missing-charstcs-args' );
	}

	// Format the arguments passed for updating.
	$formatted_args = format_charstcs_db_args( $_POST['charstcs-args'] );

	// Bail without the args coming back.
	if ( empty( $formatted_args ) ) {
		redirect_admin_action_result( $edit_redirect, 'missing-formatted-args' );
	}

	// Run the update.
	$maybe_updated  = Database\update( 'charstcs', absint( $_POST['item-id'] ), $formatted_args );

	// Check for the boolean true result.
	if ( empty( $maybe_updated ) || false === $maybe_updated ) {
		redirect_admin_action_result( $edit_redirect, 'charstcs-update-failed' );
	}

	// Check the result for a WP_Error instance.
	if ( is_wp_error( $maybe_updated ) ) {
		redirect_admin_action_result( $edit_redirect, $maybe_updated->get_error_code() );
	}

	// Redirect a happy one.
	redirect_admin_action_result( $edit_redirect, false, 'charstcs-updated', true, Core\CHARSTCS_ANCHOR );
}

/**
 * Check for the delete function of an attribute.
 *
 * @return void
 */
function delete_existing_attribute() {

	// Confirm we're on the right place.
	if ( empty( $_GET['page'] ) || empty( $_GET['wbr-action-name'] ) || Core\ATTRIBUTES_ANCHOR !== sanitize_text_field( $_GET['page'] ) || 'delete' !== sanitize_text_field( $_GET['wbr-action-name'] ) ) {
		return;
	}

	// Create my base redirect link.
	$base_redirect  = Helpers\get_admin_menu_link( Core\ATTRIBUTES_ANCHOR );

	// Confirm we have an ID, which is sorta critical.
	if ( empty( $_GET['wbr-item-id'] ) || empty( $_GET['wbr-item-type'] ) || 'attribute' !== sanitize_text_field( $_GET['wbr-item-type'] ) ) {
		redirect_admin_action_result( $base_redirect, 'missing-item-id' );
	}

	// Set my attribute item ID.
	$attribute_id   = absint( $_GET['wbr-item-id'] );

	// Handle the nonce check.
	if ( empty( $_GET['wbr-nonce'] ) || ! wp_verify_nonce( $_GET['wbr-nonce'], 'lw_woo_delete_single_' . $attribute_id ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Run the delete.
	$maybe_deleted  = Database\delete( 'attributes', $attribute_id );

	// Check for the boolean true result.
	if ( empty( $maybe_deleted ) || false === $maybe_deleted ) {
		redirect_admin_action_result( $base_redirect, 'attribute-delete-failed' );
	}

	// Check the result for a WP_Error instance.
	if ( is_wp_error( $maybe_deleted ) ) {
		redirect_admin_action_result( $base_redirect, $maybe_deleted->get_error_code() );
	}

	// Redirect a happy one.
	redirect_admin_action_result( $base_redirect, false, 'attribute-deleted', true );
}

/**
 * Check for the delete function of an charstcs.
 *
 * @return void
 */
function delete_existing_charstcs() {

	// Confirm we're on the right place.
	if ( empty( $_GET['page'] ) || empty( $_GET['wbr-action-name'] ) || Core\CHARSTCS_ANCHOR !== sanitize_text_field( $_GET['page'] ) || 'delete' !== sanitize_text_field( $_GET['wbr-action-name'] ) ) {
		return;
	}

	// Create my base redirect link.
	$base_redirect  = Helpers\get_admin_menu_link( Core\CHARSTCS_ANCHOR );

	// Confirm we have an ID, which is sorta critical.
	if ( empty( $_GET['wbr-item-id'] ) || empty( $_GET['wbr-item-type'] ) || 'charstcs' !== sanitize_text_field( $_GET['wbr-item-type'] ) ) {
		redirect_admin_action_result( $base_redirect, 'missing-item-id' );
	}

	// Set my charstc item ID.
	$charstc_id = absint( $_GET['wbr-item-id'] );

	// Handle the nonce check.
	if ( empty( $_GET['wbr-nonce'] ) || ! wp_verify_nonce( $_GET['wbr-nonce'], 'lw_woo_delete_single_' . $charstc_id ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Run the delete.
	$maybe_deleted  = Database\delete( 'charstcs', $charstc_id );

	// Check for the boolean true result.
	if ( empty( $maybe_deleted ) || false === $maybe_deleted ) {
		redirect_admin_action_result( $base_redirect, 'charstcs-delete-failed' );
	}

	// Check the result for a WP_Error instance.
	if ( is_wp_error( $maybe_deleted ) ) {
		redirect_admin_action_result( $base_redirect, $maybe_deleted->get_error_code() );
	}

	// Redirect a happy one.
	redirect_admin_action_result( $base_redirect, false, 'charstcs-deleted', true );
}

/**
 * Take the posted args and format to how the DB needs them.
 *
 * @param  array  $posted_args  The args posted from the user form.
 *
 * @return array
 */
function format_attribute_db_args( $posted_args = array() ) {

	// Bail if we don't have args.
	if ( empty( $posted_args ) ) {
		return false;
	}

	// Clean up each posted arg.
	$stripped_args  = stripslashes_deep( $posted_args ); // array_filter( $posted_args, 'sanitize_text_field' );

	// Start figuring out each part.
	$attribute_name = ! empty( $stripped_args['name'] ) ? trim( $stripped_args['name'] ) : '';
	$attribute_desc = ! empty( $stripped_args['desc'] ) ? trim( $stripped_args['desc'] ) : '';
	$attribute_min  = ! empty( $stripped_args['min-label'] ) ? trim( $stripped_args['min-label'] ) : '';
	$attribute_max  = ! empty( $stripped_args['max-label'] ) ? trim( $stripped_args['max-label'] ) : '';

	// Format the new array structure and return it.
	return array(
		'attribute_name' => $attribute_name,
		'attribute_slug' => sanitize_title_with_dashes( $attribute_name, null, 'save' ),
		'attribute_desc' => $attribute_desc,
		'min_label'      => $attribute_min,
		'max_label'      => $attribute_max,
	);
}

/**
 * Take the posted args and format to how the DB needs them.
 *
 * @param  array  $posted_args  The args posted from the user form.
 *
 * @return array
 */
function format_charstcs_db_args( $posted_args = array() ) {

	// Bail if we don't have args.
	if ( empty( $posted_args ) ) {
		return false;
	}

	// Clean up each posted arg.
	$stripped_args  = stripslashes_deep( $posted_args ); // array_filter( $posted_args, 'sanitize_text_field' );

	// Start figuring out each part.
	$charstcs_name   = ! empty( $stripped_args['name'] ) ? trim( $stripped_args['name'] ) : '';
	$charstcs_desc   = ! empty( $stripped_args['desc'] ) ? trim( $stripped_args['desc'] ) : '';
	$charstcs_values = ! empty( $stripped_args['values'] ) ? Utilities\format_string_values_array( $stripped_args['values'] ) : '';
	$charstcs_type   = ! empty( $stripped_args['type'] ) ? trim( $stripped_args['type'] ) : '';

	// Format the new array structure and return it.
	return array(
		'charstcs_name'   => $charstcs_name,
		'charstcs_slug'   => sanitize_title_with_dashes( $charstcs_name, null, 'save' ),
		'charstcs_desc'   => $charstcs_desc,
		'charstcs_values' => $charstcs_values,
		'charstcs_type'   => $charstcs_type,
	);
}

/**
 * Create the link to redirect on an edit.
 *
 * @param  integer $item_id   The ID of the item we are editing.
 * @param  string  $type      The item type we are handling. Defaults to 'attribute'.
 * @param  string  $action    The action being taken. Defaults to 'edit'.
 * @param  string  $redirect  Our base link to build off of.
 *
 * @return string
 */
function create_item_action_link( $item_id = 0, $type = 'attribute', $action = 'edit', $redirect = '' ) {

	// Bail if we don't have the required pieces.
	if ( empty( $item_id ) || empty( $action ) || empty( $type ) ) {
		return new WP_Error( 'missing-required-args', __( 'The required arguments to create a link were not provided.', 'woo-better-reviews' ) );
	}

	// Determine which thing we're editing.
	switch ( esc_attr( $type ) ) {

		case 'reviews' :

			// Set my proper anchor.
			$redirect = Helpers\get_admin_menu_link( Core\REVIEWS_ANCHOR );
			break;

		case 'attribute' :

			// Set my proper anchor.
			$redirect = Helpers\get_admin_menu_link( Core\ATTRIBUTES_ANCHOR );
			break;

		case 'charstcs' :

			// Set my proper anchor.
			$redirect = Helpers\get_admin_menu_link( Core\CHARSTCS_ANCHOR );
			break;

		// End all case breaks.
	}

	// Bail without a base redirect link.
	if ( empty( $redirect ) ) {
		return new WP_Error( 'missing-base-redirect', __( 'The base link for editing this item could not be determined.', 'woo-better-reviews' ) );
	}

	// Set the edit action link args.
	$edit_args  = array(
		'wbr-action-name' => esc_attr( $action ),
		'wbr-item-id'     => absint( $item_id ),
		'wbr-item-type'   => esc_attr( $type ),
	);

	// Create the edit link we can redirect to.
	return add_query_arg( $edit_args, $redirect );
}

/**
 * Redirect based on an edit action result.
 *
 * @param  string  $redirect  The link to redirect to.
 * @param  string  $error     Optional error code.
 * @param  string  $result    What the result of the action was.
 * @param  boolean $success   Whether it was successful.
 * @param  string  $return    Slug of the menu page to add a return link for.
 *
 * @return void
 */
function redirect_admin_action_result( $redirect = '', $error = '', $result = 'failed', $success = false, $return = '' ) {

	// Set up my redirect args.
	$redirect_args  = array(
		'success'             => $success,
		'wbr-action-complete' => 1,
		'wbr-action-result'   => esc_attr( $result ),
	);

	// Add the error code if we have one.
	$redirect_args  = ! empty( $error ) ? wp_parse_args( $redirect_args, array( 'wbr-error-code' => esc_attr( $error ) ) ) : $redirect_args;

	// Now check to see if we have a return, which means a return link.
	$redirect_args  = ! empty( $return ) ? wp_parse_args( $redirect_args, array( 'wbr-action-return' => $return ) ) : $redirect_args;

	// Now set my redirect link.
	$redirect_link  = add_query_arg( $redirect_args, $redirect );

	// Do the redirect.
	wp_safe_redirect( $redirect_link );
	exit;
}
