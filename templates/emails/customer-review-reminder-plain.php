<?php
/**
 * Customer review reminder email
 *
 * This template can be filtered in the main class.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails/Plain
 * @version 3.5.2
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
echo esc_html__( 'In case you forgot, here is what you purchased:', 'woo-better-reviews' ) . "\n\n";

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
