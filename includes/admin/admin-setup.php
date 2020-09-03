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
add_action( 'pre_get_posts', __NAMESPACE__ . '\modify_product_sort_query', 1 );
add_filter( 'removable_query_args', __NAMESPACE__ . '\admin_removable_args' );
add_filter( 'views_edit-comments', __NAMESPACE__ . '\filter_comment_status_list' );
add_filter( 'comments_list_table_query_args', __NAMESPACE__ . '\filter_comment_list_args' );

/**
 * Check for our review count sort request.
 *
 * @param  object $query  The existing display query.
 *
 * @return void
 */
function modify_product_sort_query( $query ) {

	// Make sure we're in the right place.
	if ( ! is_admin() || ! $query->is_main_query() || 'product' !== $query->get( 'post_type' ) ) {
		return;
	}

	// If we have one of our orderby keys, modify the query.
	if ( ! empty( $query->get( 'orderby' ) ) && in_array( $query->get( 'orderby' ), array( 'review_count', 'average_rating' ) ) ) {

		// Determine the query key.
		$query_key  = esc_attr( $query->get( 'orderby' ) );

		// Set the key itself, enforce the type, and the number key.
		$query->set( 'meta_key', $query_key );
		$query->set( 'meta_type', 'NUMERIC' );
		$query->set( 'orderby', 'meta_value_num' );
	}

	// No other changes should be required.
}

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
 * Set up the dropdown some data.
 *
 * @return HTML
 */
function set_admin_data_dropdown( $dropdown_data = array(), $field_name = '', $field_id = '', $selected ) {

	// Bail without the dropdown.
	if ( empty( $dropdown_data ) ) {
		return;
	}

	// Set up my empty.
	$setup  = '';

	// Now our select dropdown.
	$setup .= '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" class="postform">';

		// Our blank value.
		$setup .= '<option value="0">' . esc_html__( '(select)', 'woo-better-reviews' ) . '</option>';

		// Now loop them.
		foreach ( $dropdown_data as $key => $label ) {
			$setup .= '<option value="' . esc_attr( $key ) . '" ' . selected( $selected, $key, false ) . ' >' . esc_html( $label ) . '</option>';
		}

	// Close out my select.
	$setup .= '</select>';

	// Return the setup.
	return $setup;
}

/**
 * Set the admin stars to show.
 *
 * @param integer $total_score  The total score applied.
 *
 * @return HTML
 */
function set_admin_star_display( $total_score = 0 ) {

	// Determine the score parts.
	$score_had  = absint( $total_score );
	$score_left = $score_had < 7 ? 7 - $score_had : 0;

	// Set the aria label.
	$aria_label = sprintf( __( 'Overall Score: %s', 'woo-better-reviews' ), absint( $score_had ) );

	// Set up my empty.
	$setup  = '';

	// Wrap it in a span.
	$setup .= '<span class="woo-better-reviews-single-total-score" aria-label="' . esc_attr( $aria_label ) . '">';

		// Output the full stars.
		$setup .= str_repeat( '<span class="woo-better-reviews-single-star woo-better-reviews-single-star-full">&#9733;</span>', $score_had );

		// Output the empty stars.
		if ( $score_left > 0 ) {
			$setup .= str_repeat( '<span class="woo-better-reviews-single-star woo-better-reviews-single-star-empty">&#9734;</span>', $score_left );
		}

	// Close the span.
	$setup .= '</span>';

	// Return the setup.
	return $setup;
}
