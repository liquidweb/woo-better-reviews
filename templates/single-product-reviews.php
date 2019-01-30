<?php
/**
 * Display single product reviews (comments)
 *
 * This file totally overrides the template in either Woo or a theme.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Call the global product.
global $product;

// Bail if we don't have comments (reviews) open.
if ( ! comments_open() ) {
	return;
}

?>

<div id="reviews" class="woocommerce-Reviews woo-better-reviews-display-wrapper">

	<div id="comments" class="woo-better-reviews-display-block woo-better-reviews-existing-block">

		<?php \LiquidWeb\WooBetterReviews\Display\display_review_template_header( $product->get_id() ); ?>

		<?php \LiquidWeb\WooBetterReviews\Display\display_existing_reviews( $product->get_id() ); ?>

	</div>

	<?php \LiquidWeb\WooBetterReviews\Display\new_review_form( $product->get_id() ); ?>

	<div class="clear"></div>
</div>
