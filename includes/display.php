<?php
/**
 * Handle some basic display logic.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Display;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Queries as Queries;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'comments_template', __NAMESPACE__ . '\load_review_template', 99 );

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

	// Set our template file.
	$custom_template    = apply_filters( Core\HOOK_PREFIX . 'review_template_file', Core\TEMPLATE_PATH . 'single-product-reviews.php' );

	// Return ours (if it exists) or whatever we had originally.
	return ! empty( $custom_template ) && file_exists( $custom_template ) ? $custom_template : $default_template;
}

/**
 * Build and display the header.
 *
 * @param  integer $product_id  The product ID we are leaving a review for.
 * @param  boolean $echo        Whether to echo it out or return it.
 *
 * @return HTML
 */
function display_review_template_header( $product_id = 0, $echo = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Get the total count of reviews we have.
	$review_count   = Queries\get_reviews_for_product( $product_id, 'counts' );

	// Set our empty.
	$build  = '';

	// Wrap the title with our H2.
	$build .= '<h2 class="woocommerce-Reviews-title woo-better-reviews-template-title">';

		/* translators: 1: reviews count 2: product name */
		$build .= sprintf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $review_count, 'woo-better-reviews' ) ), esc_html( $review_count ), '<span>' . get_the_title( $product_id ) . '</span>' );

	// Close up the H2 tag.
	$build .= '</h2>';

	// Echo if requested.
	if ( ! empty( $echo ) ) {
		echo $build;
	}

	// Return it.
	return $build;
}

/**
 * Build and display the 'leave a review' form.
 *
 * @param  integer $product_id  The product ID we are leaving a review for.
 * @param  boolean $echo        Whether to echo it out or return it.
 *
 * @return HTML
 */
function display_existing_reviews( $product_id = 0, $echo = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Fetch any existing reviews we may have.
	$fetch_reviews  = Queries\get_reviews_for_product( $product_id );

	// Set my content.
	if ( empty( $fetch_reviews ) ) {

		// Set our single line return.
		$notext = '<p class="woocommerce-noreviews woo-better-reviews-no-reviews">' . esc_html__( 'There are no reviews yet. Be the first!', 'woo-better-reviews' ) . '</p>';

		// Echo if requested.
		if ( ! empty( $echo ) ) {
			echo $notext;
		}

		// Return it.
		return $notext;
	}

	// Set our empty.
	$build  = '';

	// Set the div wrapper.
	$build .= '<div class="woo-better-reviews-list-display-wrapper">';

	// Now begin to loop the reviews and do the thing.
	foreach ( $fetch_reviews as $review ) {
		// preprint( $review, true );

		// And now we display some reviews.
	}

	// Close the large div wrapper.
	$build .= '</div>';

	// Echo if requested.
	if ( ! empty( $echo ) ) {
		echo $build;
	}

	// Return it.
	return $build;
}

/**
 * Build and display the 'leave a review' form.
 *
 * @param  integer $product_id  The product ID we are leaving a review for.
 * @param  boolean $echo        Whether to echo it out or return it.
 *
 * @return HTML
 */
function new_review_form( $product_id = 0, $echo = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Set our empty.
	$build  = '';

	$build .= '<div id="review_form_wrapper" class="woo-better-reviews-display-block woo-better-reviews-form-block">';
	$build .= '</div>';

	// Echo if requested.
	if ( ! empty( $echo ) ) {
		echo $build;
	}

	// Return it.
	return $build;
}
