<?php
/**
 * Handle some basic display logic.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display\ViewOutput;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;
use LiquidWeb\WooBetterReviews\Display\FormFields as FormFields;
use LiquidWeb\WooBetterReviews\Display\LayoutNewReviewForm as LayoutNewReviewForm;
use LiquidWeb\WooBetterReviews\Display\LayoutSingleReview as LayoutSingleReview;
use LiquidWeb\WooBetterReviews\Display\LayoutReviewAggregate as LayoutReviewAggregate;

// And pull in any other namespaces.
use WP_Error;

/**
 * Build and display the visual aggregated review data.
 *
 * @param  integer $product_id  The product ID we are leaving a review for.
 * @param  boolean $echo        Whether to echo it out or return it.
 *
 * @return HTML
 */
function display_review_template_title( $product_id = 0, $echo = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Check for an override.
	$maybe_override = apply_filters( Core\HOOK_PREFIX . 'display_review_template_title', null, $product_id );

	// Return the override if we have it.
	if ( ! empty( $maybe_override ) ) {
		return render_final_output( $maybe_override, $echo );
	}

	// Get some variables based on the product ID.
	$leave_review   = Helpers\get_review_action_link( $product_id, 'review_form_wrapper' );
	$product_title  = get_the_title( $product_id );
	$review_count   = Helpers\get_front_review_count( $product_id );

	// Set some text strings.
	$count_wrapper  = '<span class="woo-better-reviews-template-title-review-count">' . esc_html( $review_count ) . '</span>';
	$title_wrapper  = '<span class="woo-better-reviews-template-title-product-name">' . esc_html( $product_title ) . '</span>';

	// Set our empty.
	$build  = '';

	// Set the div wrapper.
	$build .= '<div class="woo-better-reviews-list-title-wrapper">';

		// Wrap the title with our H2.
		$build .= '<h2 class="woocommerce-Reviews-title woo-better-reviews-template-title">';

			/* translators: 1: reviews count 2: product name */
			$build .= sprintf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $review_count, 'woo-better-reviews' ) ), $count_wrapper, $title_wrapper );

			// Include the "leave a review" inline link if we have reviews.
			if ( ! empty( $review_count ) ) {
				$build .= ' <a class="woo-better-reviews-template-title-form-link" href="' . esc_url( $leave_review ) . '">' . esc_html__( 'Leave a review', 'woo-better-reviews' ) . '</a>';
			}

		// Close up the H2 tag.
		$build .= '</h2>';

	// Close up the div tag.
	$build .= '</div>';

	// Do the return or echo based on the call.
	return render_final_output( $build, $echo );
}

/**
 * Build and display the visual aggregated review data.
 *
 * @param  integer $product_id  The product ID we are leaving a review for.
 * @param  boolean $echo        Whether to echo it out or return it.
 *
 * @return HTML
 */
function display_review_template_visual_aggregate( $product_id = 0, $echo = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Check for an override.
	$maybe_override = apply_filters( Core\HOOK_PREFIX . 'display_review_template_visual_aggregate', null, $product_id );

	// Return the override if we have it.
	if ( ! empty( $maybe_override ) ) {
		return render_final_output( $maybe_override, $echo );
	}

	// Fetch any existing reviews we may have.
	$fetch_reviews  = Queries\get_reviews_for_product( $product_id, 'display' );

	// Bail without reviews.
	if ( empty( $fetch_reviews ) ) {
		return;
	}

	// Calculate out the scores.
	$total_scores   = wp_list_pluck( $fetch_reviews, 'total_score' );

	// Get various counts.
	$review_count   = count( $fetch_reviews );
	$range_counts   = array_count_values( $total_scores );

	// Do the attribute modeling.
	$attribute_args = wp_list_pluck( $fetch_reviews, 'rating_attributes' );
	$attribute_set  = Utilities\calculate_average_attribute_scoring( array_values( $attribute_args ) );

	// Set our empty.
	$build  = '';

	// Set the div wrapper.
	$build .= '<div class="woo-better-reviews-list-visual-aggregate-wrapper">';

		// Set the group for the average rating score.
		$build .= LayoutReviewAggregate\set_review_aggregate_average_rating_view( $product_id, $review_count );

		// Set the group for the rating breakdown score.
		$build .= LayoutReviewAggregate\set_review_aggregate_rating_breakdown_view( $product_id, $range_counts, $review_count );

		// Set the group for the rating summary score.
		$build .= LayoutReviewAggregate\set_review_aggregate_attribute_summary_view( $product_id, $attribute_set );

	// Close up the div tag.
	$build .= '</div>';

	// Do the return or echo based on the call.
	return render_final_output( $build, $echo );
}

/**
 * Build and display the sorting options.
 *
 * @param  integer $product_id  The product ID we are leaving a review for.
 * @param  boolean $echo        Whether to echo it out or return it.
 *
 * @return HTML
 */
function display_review_template_sorting( $product_id = 0, $echo = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Check for an override.
	$maybe_override = apply_filters( Core\HOOK_PREFIX . 'display_review_template_sorting_override', null, $product_id );

	// Return the override if we have it.
	if ( ! empty( $maybe_override ) ) {
		return render_final_output( $maybe_override, $echo );
	}

	// Check the review count.
	$review_count   = Helpers\get_front_review_count( $product_id );

	// Bail without reviews.
	if ( empty( $review_count ) ) {
		return;
	}

	// Get all the characteristics we have.
	$all_charstcs   = Queries\get_all_charstcs( 'display' );
	// preprint( $all_charstcs, true );

	// Show nothing if we have no characteristics.
	if ( empty( $all_charstcs ) ) {
		return;
	}

	// Set my action link.
	$action_link    = Helpers\get_review_action_link( $product_id, 'tab-reviews' );

	// Set our empty.
	$build  = '';

	// Set the div wrapper.
	$build .= '<div class="woo-better-reviews-list-sorting-wrapper">';

		// Set our form wrapper.
		$build .= '<form class="woo-better-reviews-sorting-container" name="woo-better-reviews-sorting-form" action="' . esc_url( $action_link ) . '" method="post">';

			// Set these in an unordered list.
			$build .= '<ul class="woo-better-reviews-list-group">';

				// Set the title / intro piece.
				$build .= '<li class="woo-better-reviews-list-single woo-better-reviews-list-single-intro">' . __( 'Sort By:', 'woo-better-reviews' ) . '</li>';

				// Now loop each characteristic and make a dropdown.
				foreach ( $all_charstcs as $single_charstc ) {

					// Set my field name.
					$field_name = 'woo-better-reviews-sorting[charstcs][' . absint( $single_charstc['id'] ) . ']';

					// Set up my field args.
					$field_args = array(
						'id'      => $single_charstc['id'],
						'slug'    => $single_charstc['slug'],
						'label'   => $single_charstc['name'],
						'options' => $single_charstc['values'],
					);

					// And set the markup.
					$build .= '<li class="woo-better-reviews-list-single woo-better-reviews-list-single-field">';
					$build .= FormFields\get_review_sorting_dropdown_field( $field_args, $single_charstc['slug'], $field_name );
					$build .= '</li>';
				}

				// Set the submit piece.
				$build .= '<li class="woo-better-reviews-list-single woo-better-reviews-list-single-submit">';
					$build .= '<button name="wbr-single-sort-submit" type="submit" class="button woo-better-reviews-single-button" value="1">' . __( 'Filter', 'woo-better-reviews' ) . '</button>';
				$build .= '</li>';

			// Close up the list.
			$build .= '</ul>';

			// Include some hidden fields for this.
			$build .= '<input type="hidden" name="wbr-single-sort-product-id" value="' . absint( $product_id ) . '">';
			$build .= wp_nonce_field( 'wbr_sort_reviews_action', 'wbr_sort_reviews_nonce', true, false );

		// Close out the form.
		$build .= '</form>';

	// Close up the div tag.
	$build .= '</div>';

	// Do the return or echo based on the call.
	return render_final_output( $build, $echo );
}

/**
 * Build and display the our existing reviews.
 *
 * @param  integer $product_id  The product ID we are displaying reviews for.
 * @param  boolean $echo        Whether to echo it out or return it.
 *
 * @return HTML
 */
function display_existing_reviews( $product_id = 0, $echo = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Check for an override.
	$maybe_override = apply_filters( Core\HOOK_PREFIX . 'display_existing_reviews_override', null, $product_id );

	// Return the override if we have it.
	if ( ! empty( $maybe_override ) ) {
		return render_final_output( $maybe_override, $echo );
	}

	// Check for a sorting request.
	$filtered_ids   = Helpers\maybe_sorted_reviews();

	// Fetch any existing reviews we may have.
	$fetch_reviews  = false !== $filtered_ids ? Queries\get_review_batch( $filtered_ids ) : Queries\get_reviews_for_product( $product_id, 'display' );

	// If we have no reviews, return a message of some kind.
	if ( empty( $fetch_reviews ) ) {

		// Determine the text based on whether we have a filter sort request.
		$no_msg = false !== $filtered_ids ? __( 'No reviews matched your criteria. Please try again.', 'woo-better-reviews' ) : __( 'There are no reviews yet. Be the first!', 'woo-better-reviews' );

		// Set our single line return.
		$notext = '<p class="woocommerce-noreviews woo-better-reviews-no-reviews">' . esc_html( $no_msg ) . '</p>';

		// And be done.
		return render_final_output( $notext, $echo );
	}

	// Reset the array keys.
	$fetch_reviews  = array_values( $fetch_reviews );

	// Set a simple counter.
	$i  = 0;

	// Set our empty.
	$build  = '';

	// Set the div wrapper.
	$build .= '<div class="woo-better-reviews-list-display-wrapper">';

	// Now begin to loop the reviews and do the thing.
	foreach ( (array) $fetch_reviews as $single_review ) {
		// preprint( $single_review, true );

		// Skip the non-approved ones for now.
		if ( empty( $single_review['status'] ) || 'approved' !== sanitize_text_field( $single_review['status'] ) ) {
			continue;
		}
		// preprint( $single_review, true );

		// Get my div class.
		$class  = Utilities\set_single_review_div_class( $single_review, $i );

		// Now open a div for the individual review.
		$build .= '<div id="' . sanitize_html_class( 'woo-better-reviews-single-' . absint( $single_review['review_id'] ) ) . '" class="' . esc_attr( $class ) . '">';

			// Output the title.
			$build .= LayoutSingleReview\set_single_review_header_view( $single_review );

			// Do the scoring output.
			$build .= LayoutSingleReview\set_single_review_attributes_scoring_view( $single_review );

			// Output the actual content of the review.
			$build .= LayoutSingleReview\set_single_review_content_body_view( $single_review );

			// Output the author characteristics.
			$build .= LayoutSingleReview\set_single_review_author_charstcs_view( $single_review );

		// Close the single review div.
		$build .= '</div>';

		// And increment the counter.
		$i++;
	}

	// Close the large div wrapper.
	$build .= '</div>';

	// Do the return or echo based on the call.
	return render_final_output( $build, $echo );
}

/**
 * Build and display the 'leave a review' form.
 *
 * @param  integer $product_id  The product ID we are leaving a review for.
 * @param  boolean $echo        Whether to echo it out or return it.
 *
 * @return HTML
 */
function display_new_review_form( $product_id = 0, $echo = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Check for an override.
	$maybe_override = apply_filters( Core\HOOK_PREFIX . 'display_new_review_form_override', null, $product_id );

	// Return the override if we have it.
	if ( ! empty( $maybe_override ) ) {
		return render_final_output( $maybe_override, $echo );
	}

	// Set my action link.
	$action_link    = Helpers\get_review_action_link( $product_id );

	// Set our empty.
	$build  = '';

	// Wrap the entire thing in our div.
	$build .= '<div id="review_form_wrapper" class="woo-better-reviews-display-block woo-better-reviews-form-block">';

		// Set our form wrapper.
		$build .= '<form class="woo-better-reviews-form-container" name="woo-better-reviews-rating-form" action="' . esc_url( $action_link ) . '" method="post">';

			// Add actions for before and after fields.
			$build .= do_action( Core\HOOK_PREFIX . 'before_display_new_review_form', $product_id );

			// Add the title.
			$build .= LayoutNewReviewForm\set_review_form_rating_title_view( $product_id );

			// Output the rating input.
			$build .= LayoutNewReviewForm\set_review_form_rating_stars_view( $product_id );

			// Set the attributes.
			$build .= LayoutNewReviewForm\set_review_form_rating_attributes_view( $product_id );

			// Handle the inputs themselves.
			$build .= LayoutNewReviewForm\set_review_form_content_fields_view( $product_id );

			// Now get the author fields.
			$build .= LayoutNewReviewForm\set_review_form_author_fields_view( get_current_user_id() );

			// Output the submit actions.
			$build .= LayoutNewReviewForm\set_review_form_submit_action_fields_view( $product_id );

			// Output the hidden stuff.
			$build .= LayoutNewReviewForm\set_review_form_hidden_meta_fields_view( $product_id, get_current_user_id() );

			// Add actions for before and after fields.
			$build .= do_action( Core\HOOK_PREFIX . 'after_new_review_form_after', $product_id );

		// Close out the form.
		$build .= '</form>';

	// Close up the div.
	$build .= '</div>';

	// Do the return or echo based on the call.
	return render_final_output( $build, $echo );
}

/**
 * Handle the last-step output of echoing or returning.
 *
 * @param  mixed   $markup  The markup we are going to render.
 * @param  boolean $echo    Whether to echo it out or return it.
 *
 * @return HTML
 */
function render_final_output( $markup, $echo = true ) {

	// Return if requested.
	if ( empty( $echo ) ) {
		return $markup;
	}

	// Just echo it.
	echo $markup;

	// And be done.
	return;
}
