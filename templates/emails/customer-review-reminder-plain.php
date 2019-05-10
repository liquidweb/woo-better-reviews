<?php
/**
 * The plain-text template for the review reminder email.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '= ' . esc_html( $email_heading ) . " =\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// Handle the intro.
echo esc_html( $sections['introduction'] ) . "\n\n";

echo "----------\n\n";

// Output the intro to the product list.
echo esc_html__( 'As a reminder, here is what you purchased:', 'woo-better-reviews' ) . "\n\n";

// Loop and name.
foreach ( $sections['product_list'] as $product_id ) {

	// Pull out each part.
	$product_name   = get_the_title( $product_id );
	$product_link   = get_permalink( $product_id );

	// Now make the list item.
	echo "\t" . '--' . esc_attr( $product_name ) . "\n";
	echo "\t" . '   ' .esc_html__( 'Review Link', 'woo-better-reviews' ) . ': ' . esc_url( $product_link ) . '#tab-reviews' . "\n\n";
}

echo "----------\n\n";

// Handle the intro.
echo esc_html( $sections['closing'] ) . "\n\n";

echo "----------\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
