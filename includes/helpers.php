<?php
/**
 * Our helper functions to use across the plugin.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Helpers;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;

/**
 * Set the key / value pair for all our custom tables.
 *
 * @param  boolean $keys  Whether to return just the keys.
 *
 * @return array
 */
function get_table_args( $keys = false ) {

	// Set up our array.
	$tables = array(
		'content'      => __( 'Review Content', 'woo-better-reviews' ),
		'authormeta'   => __( 'Author Meta', 'woo-better-reviews' ),
		'ratings'      => __( 'Review Ratings', 'woo-better-reviews' ),
		'attributes'   => __( 'Product Attributes', 'woo-better-reviews' ),
		'charstcs'     => __( 'Author Characteristics', 'woo-better-reviews' ),
		'authorsetup'  => __( 'Author Setup', 'woo-better-reviews' ),
		'productsetup' => __( 'Product Setup', 'woo-better-reviews' ),
	);

	// Either return the full array, or just the keys if requested.
	return ! $keys ? $tables : array_keys( $tables );
}

/**
 * Compare the table name to our allowed items.
 *
 * @param  string $table_name  The name (slug) of the table.
 *
 * @return boolean
 */
function maybe_valid_table( $table_name = '' ) {

	// Make sure we have a table name.
	if ( empty( $table_name ) ) {
		return false;
	}

	// Fetch my tables.
	$tables = get_table_args( true );

	// Return the result.
	return in_array( $table_name, $tables ) ? true : false;
}

/**
 * Set and return the array of possible review statuses.
 *
 * @return array
 */
function get_review_statuses() {

	// Set up the possible statuses.
	$statuses   = array(
		'approved' => __( 'Approved', 'woo-better-reviews' ),
		'pending'  => __( 'Pending Approval', 'woo-better-reviews' ),
		'rejected' => __( 'Rejected', 'woo-better-reviews' ),
		'hidden'   => __( 'Hidden', 'woo-better-reviews' ),
	);

	// Return via filtered.
	return apply_filters( Core\HOOK_PREFIX . 'reviews_statuses', $statuses );
}

/**
 * Check to see if there is a search term and return it.
 *
 * @param  string $return  The return type we wanna have. Boolean or string.
 *
 * @return mixed.
 */
function maybe_search_term( $return = 'string' ) {

	// Determine which thing we're returning.
	switch ( esc_attr( $return ) ) {

		case 'string' :

			return isset( $_REQUEST['s'] ) ? wp_unslash( $_REQUEST['s'] ) : '';
			break;

		case 'bool' :
		case 'boolean' :

			return isset( $_REQUEST['s'] ) ? true : false;
			break;

		// End all case breaks.
	}
}

/**
 * Return our base link, with function fallbacks.
 *
 * @param  string $menu_slug  Which menu slug to use. Defaults to the primary.
 *
 * @return string
 */
function get_admin_menu_link( $menu_slug = '' ) {

	// Bail if we aren't on the admin side.
	if ( ! is_admin() ) {
		return false;
	}

	// Set my slug.
	$menu_slug  = ! empty( $menu_slug ) ? trim( $menu_slug ) : trim( Core\REVIEWS_ANCHOR );

	// Build out the link if we don't have our function.
	if ( ! function_exists( 'menu_page_url' ) ) {

		// Set up my args.
		$setup  = array( 'page' => $menu_slug );

		// Return the link with our args.
		return add_query_arg( $setup, admin_url( 'admin.php' ) );
	}

	// Return using the function.
	return menu_page_url( $menu_slug, false );
}

/**
 * Handle our redirect within the admin settings page.
 *
 * @param  array   $custom_args  The query args to include in the redirect.
 * @param  string  $menu_slug    Which menu slug to use. Defaults to the primary.
 * @param  boolean $response     Whether to include a response code.
 *
 * @return void
 */
function admin_page_redirect( $custom_args = array(), $menu_slug = '', $response = true ) {

	// Don't redirect if we didn't pass any args.
	if ( empty( $custom_args ) ) {
		return;
	}

	// Set my slug.
	$redirect_slug  = ! empty( $menu_slug ) ? trim( $menu_slug ) : trim( Core\REVIEWS_ANCHOR );

	// Handle the setup.
	$redirect_base  = get_admin_menu_link( $redirect_slug );

	// Set our redirect args.
	$redirect_args  = false !== $response ? wp_parse_args( array( 'wbr-action-complete' => 1 ), $custom_args ) : $custom_args;

	// Now set my redirect link.
	$redirect_link  = add_query_arg( $redirect_args, $redirect_base );

	// Do the redirect.
	wp_safe_redirect( $redirect_link );
	exit;
}

/**
 * Get the various parts of a product for the reviews list.
 *
 * @param  integer $product_id  The product ID we want.
 *
 * @return array
 */
function get_admin_product_data( $product_id = 0 ) {

	// Make sure we have valid ID.
	if ( empty( $product_id ) || 'product' !== get_post_type( $product_id ) ) {
		return false;
	}

	// Set up and return the data.
	return array(
		'title'     => get_the_title( $product_id ),
		'permalink' => get_permalink( $product_id ),
		'edit-link' => get_edit_post_link( $product_id, 'raw' ),
	);
}

/**
 * Check an code and (usually an error) return the appropriate text.
 *
 * @param  string $return_code  The code provided.
 *
 * @return string
 */
function get_admin_notice_text( $return_code = '' ) {

	// Handle my different error codes.
	switch ( esc_attr( $return_code ) ) {

		case 'attribute-added' :
			return __( 'The new attribute has been added.', 'woo-better-reviews' );
			break;

		case 'attribute-updated' :
			return __( 'The selected attribute has been updated.', 'woo-better-reviews' );
			break;

		case 'attribute-deleted' :
			return __( 'The selected attribute has been deleted.', 'woo-better-reviews' );
			break;

		case 'attribute-deleted-bulk' :
			return __( 'The selected attributes have been deleted.', 'woo-better-reviews' );
			break;

		case 'missing-posted-args' :
			return __( 'The required attribute arguments were not posted.', 'woo-better-reviews' );
			break;

		case 'missing-attribute-args' :
			return __( 'The required attribute arguments were not provided.', 'woo-better-reviews' );
			break;

		case 'missing-formatted-args' :
			return __( 'The attribute arguments could not be formatted.', 'woo-better-reviews' );
			break;

		case 'attribute-update-failed' :
			return __( 'The attribute could not be updated at this time.', 'woo-better-reviews' );
			break;

		case 'unknown' :
		case 'unknown-error' :
			return __( 'There was an unknown error with your request.', 'woo-better-reviews' );
			break;

		default :
			return __( 'There was an error with your request.', 'woo-better-reviews' );
			break;

		// End all case breaks.
	}
}
