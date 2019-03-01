<?php
/**
 * Handle some front-end functionality.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\FrontEnd;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'comments_template', __NAMESPACE__ . '\load_review_template', 99 );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\load_review_front_assets' );
add_action( 'init', __NAMESPACE__ . '\add_new_review' );

/**
 * Load our own review template from the plugin.
 *
 * @param  string $default_template  The file currently set to load.
 *
 * @return string
 */
function load_review_template( $default_template ) {

	// Bail if this isn't a product.
	if ( ! is_singular( 'product' ) ) {
		return $default_template;
	}

	// Set our template file, allowing themes and plugins to set their own.
	$custom_template    = apply_filters( Core\HOOK_PREFIX . 'review_template_file', Core\TEMPLATE_PATH . 'single-product-reviews.php' );

	// Return ours (if it exists) or whatever we had originally.
	return ! empty( $custom_template ) && file_exists( $custom_template ) ? $custom_template : $default_template;
}

/**
 * Load our front-end side CSS and JS.
 *
 * @return void
 */
function load_review_front_assets() {

	// Run the check if we're enabled or not.
	$maybe_enabled  = Helpers\maybe_reviews_enabled();

	// Bail if we aren't on a single product, or we aren't enabled.
	if ( ! is_singular( 'product' ) || ! $maybe_enabled ) {
		return;
	}

	// Set my handle.
	$handle = 'woo-better-reviews-front';

	// Set a file suffix structure based on whether or not we want a minified version.
	$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $handle : $handle . '.min';

	// Set a version for whether or not we're debugging.
	$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : Core\VERS;

	// Load our CSS file.
	wp_enqueue_style( $handle, Core\ASSETS_URL . '/css/' . $file . '.css', false, $vers, 'all' );

	// And our JS.
	wp_enqueue_script( $handle, Core\ASSETS_URL . '/js/' . $file . '.js', array( 'jquery' ), $vers, true );

	// Include our action let others load things.
	do_action( Core\HOOK_PREFIX . 'after_front_assets_load' );
}

/**
 * Process a submitted review.
 */
function add_new_review() {

	// Run the check if we're enabled or not.
	$maybe_enabled  = Helpers\maybe_reviews_enabled();

	// Bail if we aren't aren't enabled, on admin, or don't have our posted key.
	if ( is_admin() || empty( $_POST['woo-better-reviews-add-new'] ) || ! $maybe_enabled ) {
		return;
	}

	// Handle the nonce check.
	if ( empty( $_POST['wbr_new_review_submit_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_new_review_submit_nonce'], 'wbr_new_review_submit_action' ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Check my admin refer.
	$check_referer  = check_admin_referer( 'wbr_new_review_submit_action', 'wbr_new_review_submit_nonce' );

	// Confirm the refer check.
	if ( empty( $check_referer ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Check for a product ID first.
	if ( empty( $_POST['woo-better-reviews-product-id'] ) ) {

		// Get my shop ID.
		$shop_page  = get_option( 'woocommerce_shop_page_id' );

		// Now make my redirect.
		$shop_redir = ! empty( $shop_page ) ? get_permalink( absint( $shop_page ) ) : site_url();

		// And handle the redirect.
		redirect_front_submit_result( $shop_redir, 'missing-product-id' );
	}

	// Create my base redirect link.
	$base_redirect  = get_permalink( absint( $_POST['woo-better-reviews-product-id'] ) );

	// If we don't have the author ID, bail.
	if ( empty( $_POST['woo-better-reviews-author-id'] ) ) {
		redirect_front_submit_result( $base_redirect, 'missing-author-id' );
	}

	// If we don't have the data pieces, bail.
	if ( empty( $_POST['woo-better-reviews-rating'] ) ) {
		redirect_front_submit_result( $base_redirect, 'missing-required-args' );
	}

	preprint( $_POST['woo-better-reviews-rating'], true );
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
function redirect_front_submit_result( $redirect = '', $error = '', $result = 'failed', $success = false, $return = '' ) {

	// Set up my redirect args.
	$redirect_args  = array(
		'success'             => $success,
		'wbr-submit-complete' => 1,
		'wbr-submit-result'   => esc_attr( $result ),
	);

	// Add the error code if we have one.
	$redirect_args  = ! empty( $error ) ? wp_parse_args( $redirect_args, array( 'wbr-error-code' => esc_attr( $error ) ) ) : $redirect_args;

	// Now check to see if we have a return, which means a return link.
	$redirect_args  = ! empty( $return ) ? wp_parse_args( $redirect_args, array( 'wbr-submit-return' => $return ) ) : $redirect_args;

	// Now set my redirect link.
	$redirect_link  = add_query_arg( $redirect_args, $redirect );

	// Do the redirect.
	wp_safe_redirect( $redirect_link );
	exit;
}
