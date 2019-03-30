<?php
/**
 * Handle the parts of the form.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display\LayoutReviewAggregate;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;
use LiquidWeb\WooBetterReviews\Display\FormData as FormData;
use LiquidWeb\WooBetterReviews\Display\FormFields as FormFields;

/**
 * Set up the portion displaying the average product review rating.
 *
 * @param  integer $product_id    The product ID we are displaying for.
 * @param  integer $review_count  How many reviews we have.
 *
 * @return HTML
 */
function set_review_aggregate_average_rating_view( $product_id = 0, $review_count = 0 ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) ) {
		return;
	}

	// Get some variables based on the product ID.
	$average_score  = get_post_meta( $product_id, Core\META_PREFIX . 'average_rating', true );
	$average_stars  = Helpers\get_scoring_stars_display( 0, $average_score, false );

	// Set some text strings.
	$score_wrapper  = '<span class="woo-better-reviews-scoring-number woo-better-reviews-scoring-value">' . absint( $average_score ) . '</span>';
	$total_wrapper  = '<span class="woo-better-reviews-scoring-number woo-better-reviews-scoring-total">' . absint( 7 ) . '</span>';
	$count_wrapper  = '<span class="woo-better-reviews-scoring-number woo-better-reviews-scoring-count">' . absint( $review_count ) . '</span>';

	// First set the empty.
	$display_view   = '';

	// Set the group for the average rating score.
	$display_view  .= '<div class="woo-better-reviews-list-aggregate-group woo-better-reviews-list-aggregate-average-rating">';

		// Set a group title.
		$display_view  .= '<h4 class="woo-better-reviews-list-aggregate-group-title">' . esc_html__( 'Average Rating:', 'woo-better-reviews' ) . '</h4>';

		// Wrap the group content in a div.
		$display_view  .= '<div class="woo-better-reviews-list-aggregate-group-content woo-better-reviews-list-aggregate-group-scoring-content">';

			// Output my total stars.
			$display_view  .= '<p class="woo-better-reviews-list-aggregate-group-content-item woo-better-reviews-list-aggregate-group-content-stars">' . $average_stars . '</p>';

			// Output the text version.
			$display_view  .= '<p class="woo-better-reviews-list-aggregate-group-content-item woo-better-reviews-list-aggregate-group-content-average-text">' . sprintf( __( '%1$s of %2$s', 'woo-better-reviews' ), $score_wrapper, $total_wrapper ) . '</p>';

			// Output the total version.
			$display_view  .= '<p class="woo-better-reviews-list-aggregate-group-content-item woo-better-reviews-list-aggregate-group-content-count-total">' . sprintf( __( '%s reviews total', 'woo-better-reviews' ), $count_wrapper ) . '</p>';

		// Close the group content div.
		$display_view  .= '</div>';

	// Close the overall score group.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_aggregate_average_rating_view', $display_view, $product_id, $review_count );
}

/**
 * Set up the portion displaying the rating breakdown for product review rating.
 *
 * @param  integer $product_id    The product ID we are displaying for.
 * @param  array   $range_counts  The range of each possible score.
 * @param  integer $review_count  How many reviews we have.
 *
 * @return HTML
 */
function set_review_aggregate_rating_breakdown_view( $product_id = 0, $range_counts = array(), $review_count = 0 ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) || empty( $range_counts ) || empty( $review_count ) ) {
		return;
	}

	// First set the empty.
	$display_view   = '';

	// Set the group for the rating breakdown score.
	$display_view  .= '<div class="woo-better-reviews-list-aggregate-group woo-better-reviews-list-aggregate-rating-breakdown">';

		// Set a group title.
		$display_view  .= '<h4 class="woo-better-reviews-list-aggregate-group-title">' . esc_html__( 'Rating Breakdown:', 'woo-better-reviews' ) . '</h4>';

		// Wrap the group content in a div.
		$display_view  .= '<div class="woo-better-reviews-list-aggregate-group-content">';

			// Output my score breakdown.
			$display_view  .= '<ul class="woo-better-reviews-list-aggregate-group-content-list woo-better-reviews-list-rating-breakdown-content-list">';

			// Output each count, going in reverse.
			for ( $i = 7; $i >= 1; $i-- ) {

				// Check for some instances of that.
				$maybe_has  = array_key_exists( $i, $range_counts ) ? $range_counts[ $i ] : 0;

				// Set the class for the bar chart.
				$bar_class  = Utilities\set_single_bar_graph_class( $maybe_has, $review_count );

				// Begin the list opening.
				$display_view  .= '<li class="woo-better-reviews-list-aggregate-group-content-list-item woo-better-reviews-list-rating-breakdown-item">';

					// Output the label portion.
					$display_view  .= '<span class="woo-better-reviews-list-breakdown-label">' . sprintf( _n( '%d Star:', '%d Stars:', absint( $i ), 'woo-better-reviews' ), absint( $i ) ) . '</span>';

					// Handle the bar graph output.
					$display_view  .= '<span class="' . esc_attr( $bar_class ) . '"></span>';

					// Output the value portion.
					$display_view  .= '<span class="woo-better-reviews-list-breakdown-value">' . absint( $maybe_has ) . '</span>';

				// Close the list.
				$display_view  .= '</li>';
			}

			// Close the list.
			$display_view  .= '</ul>';

		// Close the group content div.
		$display_view  .= '</div>';

	// Close the overall score group.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_aggregate_rating_breakdown_view', $display_view, $product_id, $range_counts );
}

/**
 * Set up the portion displaying the attribute summary breakdown for product review rating.
 *
 * @param  integer $product_id     The product ID we are displaying for.
 * @param  array   $attribute_set  All the related attributes and the scoring.
 *
 * @return HTML
 */
function set_review_aggregate_attribute_summary_view( $product_id = 0, $attribute_set = array() ) {

	// Bail without the parts we want.
	if ( empty( $product_id ) || empty( $attribute_set ) ) {
		return;
	}

	// First set the empty.
	$display_view   = '';

	// Set the group for the rating breakdown score.
	$display_view  .= '<div class="woo-better-reviews-list-aggregate-group woo-better-reviews-list-aggregate-attribute-summary">';

		// Set a group title.
		$display_view  .= '<h4 class="woo-better-reviews-list-aggregate-group-title">' . esc_html__( 'Review Summary:', 'woo-better-reviews' ) . '</h4>';

		// Wrap the group content in a div.
		$display_view  .= '<div class="woo-better-reviews-list-aggregate-group-content">';

		// Loop my attributes to set up each one.
		foreach ( $attribute_set as $attribute_data ) {

			// Set my average score.
			$average_score  = ! empty( $attribute_data['average'] ) ? absint( $attribute_data['average'] ) : 0;

			// Set my min and max labels.
			$set_label_arr  = ! empty( $attribute_data['labels'] ) ? array_map( 'sanitize_text_field', $attribute_data['labels'] ) : array();
			$set_min_label  = ! empty( $set_label_arr['min'] ) ? esc_attr( $set_label_arr['min'] ) : __( 'Min.', 'woo-better-reviews' );
			$set_max_label  = ! empty( $set_label_arr['max'] ) ? esc_attr( $set_label_arr['max'] ) : __( 'Max.', 'woo-better-reviews' );
			$min_max_class  = 'woo-better-reviews-list-attribute-summary-label woo-better-reviews-list-attribute-summary-label-';

			// Set a class for each block.
			$block_class    = 'woo-better-reviews-list-attribute-summary-block woo-better-reviews-list-' . esc_attr( $attribute_data['slug'] ) . '-summary-block';

			// Begin the div opening.
			$display_view  .= '<div class="' . esc_attr( $block_class ) . '">';

				// Output the title portion.
				$display_view  .= '<p class="woo-better-reviews-list-attribute-summary-title">' . esc_html( $attribute_data['title'] ) . '</p>';

				// Wrap the span blocks in a paragraph.
				$display_view  .= '<p class="woo-better-reviews-list-attribute-summary-squares">';

				// Now output each count block with a class applied on the match.
				for ( $i = 1; $i <= 7; $i++ ) {

					// Set a class if it matches.
					$square_class   = 'woo-better-reviews-list-attribute-summary-square woo-better-reviews-list-attribute-summary-square-' . absint( $i );
					$square_class  .= absint( $average_score ) === absint( $i ) ? ' woo-better-reviews-list-attribute-summary-square-fill' : ' woo-better-reviews-list-attribute-summary-square-empty';

					// Make my span.
					$display_view  .= '<span class="' . esc_attr( $square_class ) . '"></span>';
				}

				// Close the paragraph.
				$display_view  .= '</p>';

				// Handle my min-max labeling.
				$display_view  .= '<p class="woo-better-reviews-list-attribute-summary-labelset">';

					// Set the min and max.
					$display_view  .= '<span class="' . esc_attr( $min_max_class . 'min' ) . '">' . $set_min_label . '</span>';
					$display_view  .= '<span class="' . esc_attr( $min_max_class . 'max' ) . '">' . $set_max_label . '</span>';

				// Close the label group.
				$display_view  .= '</p>';

			// Close the div.
			$display_view  .= '</div>';
		}

		// Close the group content div.
		$display_view  .= '</div>';

	// Close the overall score group.
	$display_view  .= '</div>';

	// Return it, filtered.
	return apply_filters( Core\HOOK_PREFIX . 'review_aggregate_attribute_summary_view', $display_view, $product_id, $attribute_set );
}
