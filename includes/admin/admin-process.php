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
use LiquidWeb\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_filter( 'removable_query_args', __NAMESPACE__ . '\filter_removable_args' );
add_action( 'admin_init', __NAMESPACE__ . '\update_existing_attribute' );

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
		'wbr-nonce',
		'wbr-error-code',
	);

	// Set the array of new args.
	$setup  = apply_filters( Core\HOOK_PREFIX . 'removable_args', $remove );

	// Include my new args and return.
	return ! empty( $setup ) ? wp_parse_args( $setup, $args ) : $args;
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

	// Confirm we're on the right place (again).
	if ( empty( $_POST['item-id'] ) || empty( $_POST['item-type'] ) || 'attribute' !== sanitize_text_field( $_POST['item-type'] ) ) {

		// Set up my redirect args.
		$redirect_args  = array(
			'success'           => false,
			'wbr-action-result' => 'failed',
			'wbr-error-code'    => 'missing-posted-args',
		);

		// Process the redirect args.
		Helpers\admin_page_redirect( $redirect_args, Core\ATTRIBUTES_ANCHOR );
	}

	// Bail if we don't have a name to check.
	if ( empty( $_POST['attribute-args'] ) ) {

		// Set up my redirect args.
		$redirect_args  = array(
			'success'           => false,
			'wbr-action-result' => 'failed',
			'wbr-error-code'    => 'missing-attribute-args',
		);

		// Process the redirect args.
		Helpers\admin_page_redirect( $redirect_args, Core\ATTRIBUTES_ANCHOR );
	}

	// Format the arguments passed for updating.
	$formatted_args = format_attribute_args( $_POST['attribute-args'] );

	// Bail without the args coming back.
	if ( empty( $formatted_args ) ) {

		// Set up my redirect args.
		$redirect_args  = array(
			'success'           => false,
			'wbr-action-result' => 'failed',
			'wbr-error-code'    => 'missing-formatted-args',
		);

		// Process the redirect args.
		Helpers\admin_page_redirect( $redirect_args, Core\ATTRIBUTES_ANCHOR );
	}

	// Run the update.
	$maybe_updated  = Database\update( 'attributes', absint( $_POST['item-id'] ), $formatted_args );

	// Check the result for a WP_Error instance.
	if ( is_wp_error( $maybe_updated ) ) {

		// Set up my redirect args.
		$redirect_args  = array(
			'success'           => false,
			'wbr-action-result' => 'failed',
			'wbr-error-code'    => $maybe_updated->get_error_code(),
		);

		// Process the redirect args.
		Helpers\admin_page_redirect( $redirect_args, Core\ATTRIBUTES_ANCHOR );
	}

	// Check for the 'updated' or 'unchanged' result.
	if ( ! in_array( $maybe_updated, array( 'updated', 'unchanged' ) ) ) {

		// Set up my redirect args.
		$redirect_args  = array(
			'success'           => false,
			'wbr-action-result' => 'failed',
			'wbr-error-code'    => 'attribute-update-failed'
		);

		// Process the redirect args.
		Helpers\admin_page_redirect( $redirect_args, Core\ATTRIBUTES_ANCHOR );
	}

	// Set up my redirect args.
	$redirect_args  = array(
		'success'           => true,
		'wbr-action-result' => 'attribute-' . $maybe_updated,
	);

	// Process the redirect args.
	Helpers\admin_page_redirect( $redirect_args, Core\ATTRIBUTES_ANCHOR );
}

/**
 * Take the posted args and format to how the DB needs them.
 *
 * @param  array  $posted_args  The args posted from the user form.
 *
 * @return array
 */
function format_attribute_args( $posted_args = array() ) {

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

	// Format the new array structure.
	$filtered_array = array(
		'attribute_name' => $attribute_name,
		'attribute_slug' => sanitize_title_with_dashes( $attribute_name, null, 'save' ),
		'attribute_desc' => $attribute_desc,
		'min_label'      => $attribute_min,
		'max_label'      => $attribute_max,
	);

	// Now return without the slashes.
	return array_filter( $filtered_array, 'sanitize_text_field' );
}
