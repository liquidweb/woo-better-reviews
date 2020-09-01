<?php
/**
 * Handle some oddball setup items.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\Admin\AdminSetup;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Queries as Queries;
use Nexcess\WooBetterReviews\AdminPages as AdminPages;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_filter( 'removable_query_args', __NAMESPACE__ . '\admin_removable_args' );
add_filter( 'views_edit-comments', __NAMESPACE__ . '\filter_comment_status_list' );
add_filter( 'comments_list_table_query_args', __NAMESPACE__ . '\filter_comment_list_args' );
add_action( 'admin_init', __NAMESPACE__ . '\register_review_converter' );

/**
 * Add our custom strings to the vars.
 *
 * @param  array $args  The existing array of args.
 *
 * @return array $args  The modified array of args.
 */
function admin_removable_args( $args ) {

	// Set an array of the args we wanna exclude.
	$remove = array(
		'wbr-item-type',
		'wbr-action-name',
		'wbr-item-id',
		'wbr-action-complete',
		'wbr-action-result',
		'wbr-action-return',
		'wbr-nonce',
		'wbr-error-code',
	);

	// Set the array of new args.
	$setup  = apply_filters( Core\HOOK_PREFIX . 'admin_removable_args', $remove );

	// Include my new args and return.
	return ! empty( $setup ) ? wp_parse_args( $setup, $args ) : $args;
}

/**
 * Filter and modify the views that are available.
 *
 * @param  array $views  The existing array of views.
 *
 * @return array         The modified array.
 */
function filter_comment_status_list( $views ) {

	// Get the converted count.
	$get_legacy_count   = Queries\get_legacy_woo_reviews( 'count' );

	// Bail if we don't have any.
	if ( empty( $get_legacy_count ) ) {
		return $views;
	}

	// Define our link and class.
	$set_view_link  = add_query_arg( 'comment_status', 'converted', admin_url( 'edit-comments.php' ) );
	$set_view_label = sprintf( __( 'Legacy Reviews <span class="count">(%d)</span>', 'woo-better-reviews' ), absint( $get_legacy_count ) );
	$set_view_class = 'wbr-admin-legacy-view-link';

	// Include the 'current' class if we are there.
	if ( ! empty( $_GET['comment_status'] ) && 'converted' === sanitize_text_field( $_GET['comment_status'] ) ) {
		$set_view_class .= ' current';
	}

	// And then add it to the array.
	$views['converted'] = '<a class="' . esc_attr( $set_view_class ) . '" href="' . $set_view_link . '">' . $set_view_label . '</a>';

	// And return them.
	return $views;
}

/**
 * Filter and modify the query list when requested.
 *
 * @param  array $query_args  The existing array of args.
 *
 * @return array         The modified array.
 */
function filter_comment_list_args( $query_args ) {

	// Only modify the query if our status is present.
	if ( ! empty( $_GET['comment_status'] ) && 'converted' === sanitize_text_field( $_GET['comment_status'] ) ) {

		// Now make sure the arg is set how we want.
		$query_args['status'] = 'converted';
		$query_args['type']   = 'legacy-review';
	}

	// And return the updated args.
	return $query_args;
}

/**
 * Register WordPress based importers.
 */
function register_review_converter() {

	// Make sure the constant is being defined.
	if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
		return;
	}

	// Attempt to first get the reviews.
	$maybe_has_reviews  = Queries\get_existing_woo_reviews( 'boolean' );

	// If no reviews exist, don't list it.
	if ( empty( $maybe_has_reviews ) ) {
		return;
	}

	// Now load up our new importer.
	register_importer(
		'wbr-review-conversion',
		__( 'Better Product Reviews for WooCommerce', 'woocommerce' ),
		__( 'Convert any existing WooCommerce reviews to the new.', 'woo-better-reviews' ),
		__NAMESPACE__ . '\load_review_import_page'
	);


}

function load_review_import_page() {
	echo 'hello';
}
