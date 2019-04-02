<?php
/**
 * Handle the overall list layout parts.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display\LayoutReviewList;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;
use LiquidWeb\WooBetterReviews\Display\FormData as FormData;
use LiquidWeb\WooBetterReviews\Display\FormFields as FormFields;

/**
 * Set up the messages portion of the review list.
 *
 * @param  integer $product_id  The product ID we are displaying for.
 *
 * @return HTML
 */
function set_review_list_messages_view( $product_id = 0 ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) ) {
		return;
	}

	// If we don't have the success flag at all, bail.
	if ( ! isset( $_REQUEST['wbr-success'] ) ) {
		return;
	}

	// Determine if it was a success.
	$maybe_success  = ! empty( $_REQUEST['wbr-success'] ) ? true : false;

	// If we have a success, set up those items.
	if ( false !== $maybe_success ) {

		// Set the class.
		$message_class  = 'woo-better-reviews-message-text woo-better-reviews-message-text-success';

		// Get the message text.
		$message_words  = Helpers\get_error_notice_text( 'review-posted' );

	} else {

		// Get the error code we (hopefully) have.
		$get_error_code = ! empty( $_REQUEST['wbr-error-code'] ) ? sanitize_text_field( $_REQUEST['wbr-error-code'] ) : 'review-post-failed';

		// Set the class.
		$message_class  = 'woo-better-reviews-message-text woo-better-reviews-message-text-error';

		// Get the message text.
		$message_words  = Helpers\get_error_notice_text( $get_error_code );
	}

	// Wrap the text in a paragraph with our class.
	$display_view   = '<p class="' . esc_attr( $message_class ) . '">' . esc_html( $message_words ) . '</p>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_list_messages_view', $display_view, $product_id );
}

/**
 * Set up the header portion of the review list.
 *
 * @param  integer $product_id  The product ID we are displaying for.
 *
 * @return HTML
 */
function set_review_list_header_view( $product_id = 0 ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) ) {
		return;
	}

	// Get some variables based on the product ID.
	$leave_review   = Helpers\get_review_action_link( $product_id, 'review_form_wrapper' );
	$product_title  = get_the_title( $product_id );
	$review_count   = Helpers\get_front_review_count( $product_id );

	// Set some text strings.
	$count_wrapper  = '<span class="woo-better-reviews-template-title-review-count">' . esc_html( $review_count ) . '</span>';
	$title_wrapper  = '<span class="woo-better-reviews-template-title-product-name">' . esc_html( $product_title ) . '</span>';

	// First set the empty.
	$display_view   = '';

	// Set the div wrapper.
	$display_view  .= '<div class="woo-better-reviews-list-title-wrapper">';

		// Wrap the title with our H2.
		$display_view  .= '<h2 class="woocommerce-Reviews-title woo-better-reviews-template-title">';

			/* translators: 1: reviews count 2: product name */
			$display_view  .= sprintf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $review_count, 'woo-better-reviews' ) ), $count_wrapper, $title_wrapper );

			// Include the "leave a review" inline link if we have reviews.
			if ( ! empty( $review_count ) ) {
				$display_view  .= ' <a class="woo-better-reviews-template-title-form-link" href="' . esc_url( $leave_review ) . '">' . esc_html__( 'Leave a review', 'woo-better-reviews' ) . '</a>';
			}

		// Close up the H2 tag.
		$display_view  .= '</h2>';

	// Close up the div tag.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_list_header_view', $display_view, $product_id );
}

/**
 * Set up the sorting portion of the review list.
 *
 * @param  integer $product_id  The product ID we are displaying for.
 *
 * @return HTML
 */
function set_review_list_sorting_view( $product_id = 0 ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) ) {
		return;
	}

	// Check the review count.
	$review_count   = Helpers\get_front_review_count( $product_id );

	// Bail without reviews.
	if ( empty( $review_count ) ) {
		return;
	}

	// Get all the characteristics we have.
	$all_charstcs   = Queries\get_all_charstcs( 'display' );

	// Show nothing if we have no characteristics.
	if ( empty( $all_charstcs ) ) {
		return;
	}

	// Get some variables based on the product ID.
	$action_link    = Helpers\get_review_action_link( $product_id, 'tab-reviews' );

	// First set the empty.
	$display_view   = '';

	// Set the div wrapper.
	$display_view  .= '<div class="woo-better-reviews-list-sorting-wrapper">';

		// Set our form wrapper.
		$display_view  .= '<form class="woo-better-reviews-sorting-container" name="woo-better-reviews-sorting-form" action="' . esc_url( $action_link ) . '" method="post">';

			// Set these in an unordered list.
			$display_view  .= '<ul class="woo-better-reviews-list-group">';

				// Set the title / intro piece.
				$display_view  .= '<li class="woo-better-reviews-list-single woo-better-reviews-list-single-intro">' . __( 'Sort By:', 'woo-better-reviews' ) . '</li>';

				// Now loop each characteristic and make a dropdown.
				foreach ( $all_charstcs as $single_charstc ) {

					// Set my field name and slug.
					$field_name = 'woo-better-reviews-sorting[charstcs][' . absint( $single_charstc['id'] ) . ']';
					$field_slug = esc_attr( $single_charstc['slug'] );

					// Set up my field args.
					$field_args = array(
						'id'      => $single_charstc['id'],
						'slug'    => $field_slug,
						'label'   => $single_charstc['name'],
						'options' => $single_charstc['values'],
					);

					// And set the markup.
					$display_view  .= '<li class="woo-better-reviews-list-single woo-better-reviews-list-single-field">';
					$display_view  .= FormFields\get_review_sorting_dropdown_field( $field_args, $field_slug, $field_name );
					$display_view  .= '</li>';
				}

				// Set the submit piece.
				$display_view  .= '<li class="woo-better-reviews-list-single woo-better-reviews-list-single-submit">';
					$display_view  .= '<button name="wbr-single-sort-submit" type="submit" class="button woo-better-reviews-single-button" value="1">' . __( 'Filter', 'woo-better-reviews' ) . '</button>';
				$display_view  .= '</li>';

			// Close up the list.
			$display_view  .= '</ul>';

			// Include some hidden fields for this.
			$display_view  .= '<input type="hidden" name="wbr-single-sort-product-id" value="' . absint( $product_id ) . '">';
			$display_view  .= wp_nonce_field( 'wbr_sort_reviews_action', 'wbr_sort_reviews_nonce', true, false );

		// Close out the form.
		$display_view  .= '</form>';

	// Close up the div tag.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_list_sorting_view', $display_view, $product_id );
}

/**
 * Set up the pagination portion of the review list.
 *
 * @param  array   $review_list_args  The array of review data.
 * @param  integer $product_id        The product ID we are displaying for.
 *
 * @return HTML
 */
function set_review_list_pagination_view( $review_list_args = array(), $product_id = 0 ) {

	// Bail without the parts we want.
	if ( empty( $review_list_args ) || empty( $product_id ) ) {
		return;
	}

	// Parse out my increment.
	$increment  = '<span class="woo-better-reviews-paginate-increment">' . absint( $review_list_args['increment'] ) . '</span>';

	// Set my base link for the pagination with the format replacer.
	$setup_link = get_permalink( $product_id ) . '%_%';

	// Set my pagination args.
	$paginate_args  = array(
		'base'         => esc_url_raw( $setup_link ),
		'format'       => '?wbr-paged=%#%',
		'current'      => absint( $review_list_args['current'] ),
		'total'        => absint( $review_list_args['total'] ),
		'type'         => 'list',
		'echo'         => false,
		'add_fragment' => '#tab-reviews',
		'prev_text'    => sprintf( __( '&laquo; Previous %s Reviews', 'woo-better-reviews' ), $increment ),
		'next_text'    => sprintf( __( 'Next %s Reviews &raquo;', 'woo-better-reviews' ), $increment ),
	);

	// Filter my pagination args.
	$paginate_args  = apply_filters( Core\HOOK_PREFIX . 'review_list_paginate_args', $paginate_args, $review_list_args, $product_id );

	// Bail if we don't have args to pass.
	if ( empty( $paginate_args ) ) {
		return;
	}

	// Attempt to get my paginated links.
	$paginate_links = paginate_links( $paginate_args );

	// Bail if we don't have the link setup returned.
	if ( empty( $paginate_links ) || is_wp_error( $paginate_links ) ) {
		return;
	}

	// If we requested the array, return that.
	if ( 'array' === esc_attr( $paginate_args['type'] ) ) {
		return $paginate_links;
	}

	// Handle the plain output.
	if ( 'plain' === esc_attr( $paginate_args['type'] ) ) {
		$paginate_links = '<p class="single-page-links">' . $paginate_links . '</p>';
	}

	// Set up the wrapper and return.
	$display_view   = '<div class="woo-better-reviews-display-pagination-wrapper">' . $paginate_links . '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_list_pagination_view', $display_view, $review_list_args, $product_id );
}
