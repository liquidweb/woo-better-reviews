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
use LiquidWeb\WooBetterReviews\Queries as Queries;
use LiquidWeb\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'admin_init', __NAMESPACE__ . '\update_existing_review' );
add_action( 'admin_init', __NAMESPACE__ . '\delete_existing_review' );
add_action( 'admin_init', __NAMESPACE__ . '\add_new_attribute' );
add_action( 'admin_init', __NAMESPACE__ . '\update_existing_attribute' );
add_action( 'admin_init', __NAMESPACE__ . '\delete_existing_attribute' );
add_action( 'admin_init', __NAMESPACE__ . '\add_new_charstcs' );
add_action( 'admin_init', __NAMESPACE__ . '\update_existing_charstcs' );
add_action( 'admin_init', __NAMESPACE__ . '\delete_existing_charstcs' );

/**
 * Check for the editing function of an attribute.
 *
 * @return void
 */
function update_existing_review() {

	// Confirm we're on the right place.
	if ( ! isset( $_POST['edit-existing-review'] ) || empty( $_POST['action'] ) || 'update' !== sanitize_text_field( $_POST['action'] ) ) {
		return;
	}

	// Handle the nonce check.
	if ( empty( $_POST['wbr_edit_review_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_edit_review_nonce'], 'wbr_edit_review_action' ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Confirm we have an ID, which is sorta critical.
	if ( empty( $_POST['item-id'] ) ) {
		redirect_admin_action_result( Core\REVIEWS_ANCHOR, 'missing-item-id' );
	}

	// Make my edit link.
	$edit_redirect  = create_item_action_link( $_POST['item-id'], 'reviews' );

	// Check for the remainder of items.
	if ( empty( $_POST['item-type'] ) || 'review' !== sanitize_text_field( $_POST['item-type'] ) ) {
		redirect_admin_action_result( $edit_redirect, 'missing-posted-args' );
	}

	// Bail if we don't have a name to check.
	if ( empty( $_POST['review-args'] ) ) {
		redirect_admin_action_result( $edit_redirect, 'missing-review-args' );
	}

	// Format the arguments passed for updating.
	$formatted_args = format_review_db_args( $_POST['review-args'] );

	// Bail without the args coming back.
	if ( empty( $formatted_args ) ) {
		redirect_admin_action_result( $edit_redirect, 'missing-formatted-args' );
	}

	// Run the update.
	$maybe_updated  = Database\update( 'content', absint( $_POST['item-id'] ), $formatted_args );

	// Check for some error return or blank.
	if ( empty( $maybe_updated ) || false === $maybe_updated || is_wp_error( $maybe_updated ) ) {

		// Figure out the error code.
		$error_code = is_wp_error( $maybe_updated ) ? $maybe_updated->get_error_code() : 'review-update-failed';

		// And redirect.
		redirect_admin_action_result( $base_redirect, $error_code );
	}

	// Purge my related transients.
	Utilities\purge_transients( Core\HOOK_PREFIX . 'single_review_' . absint( $_POST['item-id'] ), 'reviews' );

	// Recalculate the values.
	if ( ! empty( $_POST['product-id'] ) ) {

		// Set my ID.
		$update_id  = absint( $_POST['product-id'] );

		// Update the product review count.
		Utilities\update_product_review_count( $update_id );

		// Update the overall score.
		Utilities\calculate_total_review_scoring( $update_id );
	}

	// Redirect a happy one.
	redirect_admin_action_result( $edit_redirect, false, 'review-updated', true, Core\REVIEWS_ANCHOR );
}

/**
 * Check for the delete function of an charstcs.
 *
 * @return void
 */
function delete_existing_review() {

	// Confirm we're on the right place.
	if ( empty( $_GET['page'] ) || empty( $_GET['wbr-action-name'] ) || Core\REVIEWS_ANCHOR !== sanitize_text_field( $_GET['page'] ) || 'delete' !== sanitize_text_field( $_GET['wbr-action-name'] ) ) {
		return;
	}

	// Create my base redirect link.
	$base_redirect  = Helpers\get_admin_menu_link( Core\REVIEWS_ANCHOR );

	// Confirm we have an ID, which is sorta critical.
	if ( empty( $_GET['wbr-item-id'] ) || empty( $_GET['wbr-item-type'] ) || 'review' !== sanitize_text_field( $_GET['wbr-item-type'] ) ) {
		redirect_admin_action_result( $base_redirect, 'missing-item-id' );
	}

	// Set my review item ID.
	$review_id  = absint( $_GET['wbr-item-id'] );

	// Handle the nonce check.
	if ( empty( $_GET['wbr-nonce'] ) || ! wp_verify_nonce( $_GET['wbr-nonce'], 'wbr_delete_single_' . $review_id ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Get my product ID before deleting.
	$old_product_id = Queries\get_single_review( $review_id, 'product' );

	// Run the delete.
	$maybe_deleted  = Database\delete( 'content', $review_id );

	// Check for some error return or blank.
	if ( empty( $maybe_deleted ) || false === $maybe_deleted || is_wp_error( $maybe_deleted ) ) {

		// Figure out the error code.
		$error_code = is_wp_error( $maybe_deleted ) ? $maybe_deleted->get_error_code() : 'review-delete-failed';

		// And redirect.
		redirect_admin_action_result( $base_redirect, $error_code );
	}

	// Run my related cleanup.
	delete_related_review_data( $review_id );

	// Purge my related transients.
	Utilities\purge_transients( Core\HOOK_PREFIX . 'single_review_' . absint( $_POST['item-id'] ), 'reviews' );
	Utilities\purge_transients( null, 'taxonomies' );

	// Run the relcalculations with the product ID.
	if ( ! empty( $old_product_id ) ) {

		// Update the product review count.
		Utilities\update_product_review_count( $old_product_id );

		// Update the overall score.
		Utilities\calculate_total_review_scoring( $old_product_id );
	}

	// Redirect a happy one.
	redirect_admin_action_result( $base_redirect, false, 'review-deleted', true );
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

	// Check for some error return or blank.
	if ( empty( $maybe_inserted ) || false === $maybe_inserted || is_wp_error( $maybe_inserted ) ) {

		// Figure out the error code.
		$error_code = is_wp_error( $maybe_inserted ) ? $maybe_inserted->get_error_code() : 'attribute-insert-failed';

		// And redirect.
		redirect_admin_action_result( $base_redirect, $error_code );
	}

	// Purge my related transients.
	Utilities\purge_transients( null, 'taxonomies' );

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

	// Check for some error return or blank.
	if ( empty( $maybe_inserted ) || false === $maybe_inserted || is_wp_error( $maybe_inserted ) ) {

		// Figure out the error code.
		$error_code = is_wp_error( $maybe_inserted ) ? $maybe_inserted->get_error_code() : 'charstcs-insert-failed';

		// And redirect.
		redirect_admin_action_result( $base_redirect, $error_code );
	}

	// Purge my related transients.
	Utilities\purge_transients( null, 'taxonomies' );

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
	$edit_redirect  = create_item_action_link( $_POST['item-id'], 'attributes' );

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

	// Check for some error return or blank.
	if ( empty( $maybe_updated ) || false === $maybe_updated || is_wp_error( $maybe_updated ) ) {

		// Figure out the error code.
		$error_code = is_wp_error( $maybe_updated ) ? $maybe_updated->get_error_code() : 'attribute-update-failed';

		// And redirect.
		redirect_admin_action_result( $base_redirect, $error_code );
	}

	// Purge my related transients.
	Utilities\purge_transients( Core\HOOK_PREFIX . 'single_attribute_' . absint( $_POST['item-id'] ), 'taxonomies' );

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

	// Check for some error return or blank.
	if ( empty( $maybe_updated ) || false === $maybe_updated || is_wp_error( $maybe_updated ) ) {

		// Figure out the error code.
		$error_code = is_wp_error( $maybe_updated ) ? $maybe_updated->get_error_code() : 'charstcs-update-failed';

		// And redirect.
		redirect_admin_action_result( $base_redirect, $error_code );
	}

	// Purge my related transients.
	Utilities\purge_transients( Core\HOOK_PREFIX . 'single_charstcs_' . absint( $_POST['item-id'] ), 'taxonomies' );

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
	if ( empty( $_GET['wbr-nonce'] ) || ! wp_verify_nonce( $_GET['wbr-nonce'], 'wbr_delete_single_' . $attribute_id ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Run the delete.
	$maybe_deleted  = Database\delete( 'attributes', $attribute_id );

	// Check for some error return or blank.
	if ( empty( $maybe_deleted ) || false === $maybe_deleted || is_wp_error( $maybe_deleted ) ) {

		// Figure out the error code.
		$error_code = is_wp_error( $maybe_deleted ) ? $maybe_deleted->get_error_code() : 'attribute-delete-failed';

		// And redirect.
		redirect_admin_action_result( $base_redirect, $error_code );
	}

	// Purge my related transients.
	Utilities\purge_transients( Core\HOOK_PREFIX . 'single_attribute_' . absint( $_POST['item-id'] ), 'taxonomies' );

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
	if ( empty( $_GET['wbr-nonce'] ) || ! wp_verify_nonce( $_GET['wbr-nonce'], 'wbr_delete_single_' . $charstc_id ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Run the delete.
	$maybe_deleted  = Database\delete( 'charstcs', $charstc_id );

	// Check for some error return or blank.
	if ( empty( $maybe_deleted ) || false === $maybe_deleted || is_wp_error( $maybe_deleted ) ) {

		// Figure out the error code.
		$error_code = is_wp_error( $maybe_deleted ) ? $maybe_deleted->get_error_code() : 'attribute-delete-failed';

		// And redirect.
		redirect_admin_action_result( $base_redirect, $error_code );
	}

	// Purge my related transients.
	Utilities\purge_transients( Core\HOOK_PREFIX . 'single_charstcs_' . absint( $_POST['item-id'] ), 'taxonomies' );

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
function format_review_db_args( $posted_args = array() ) {

	// Bail if we don't have args.
	if ( empty( $posted_args ) ) {
		return false;
	}

	// Clean up each posted arg.
	$stripped_args  = stripslashes_deep( $posted_args ); // array_filter( $posted_args, 'sanitize_text_field' );

	// Start figuring out each part.
	$review_date    = ! empty( $stripped_args['date'] ) ? trim( $stripped_args['date'] ) : '';
	$review_title   = ! empty( $stripped_args['title'] ) ? trim( $stripped_args['title'] ) : '';
	$review_slug    = ! empty( $review_title ) ? sanitize_title_with_dashes( $review_title, null, 'save' ) : '';
	$review_summary = ! empty( $stripped_args['summary'] ) ? trim( $stripped_args['summary'] ) : '';
	$review_content = ! empty( $stripped_args['content'] ) ? trim( $stripped_args['content'] ) : '';
	$review_status  = ! empty( $stripped_args['status'] ) ? trim( $stripped_args['status'] ) : '';

	// Format the new array structure and return it.
	$update_setup   = array(
		'review_date'         => $review_date,
		'review_title'        => $review_title,
		'review_slug'         => $review_slug,
		'review_summary'      => $review_summary,
		'review_content'      => $review_content,
		'review_status'       => $review_status,
	);

	// Return it cleaned up.
	return array_filter( $update_setup );
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
 * @param  string  $type      The item type we are handling. Defaults to 'attributes'.
 * @param  string  $action    The action being taken. Defaults to 'edit'.
 * @param  string  $redirect  Our base link to build off of.
 *
 * @return string
 */
function create_item_action_link( $item_id = 0, $type = 'attributes', $action = 'edit', $redirect = '' ) {

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

		case 'attributes' :

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

/**
 * Review the related items tied to a review.
 *
 * @param  integer $review_id  The review ID we are checking.
 *
 * @return void
 */
function delete_related_review_data( $review_id = 0 ) {

	// Bail if we don't have a review ID.
	if ( empty( $review_id ) ) {
		return;
	}

	// Call the global DB.
	global $wpdb;

	// Run my delete functions.
	$wpdb->delete( $wpdb->wc_better_rvs_ratings, array( 'review_id' => absint( $review_id ) ), array( '%d' ) );
	$wpdb->delete( $wpdb->wc_better_rvs_authormeta, array( 'review_id' => absint( $review_id ) ), array( '%d' ) );
}
