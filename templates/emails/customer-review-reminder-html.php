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
 * @package WooCommerce/Templates/Emails
 * @version 3.5.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );

// Output our introduction.
echo '<p>' . wp_kses_post( $sections['introduction'] ) . '</p>';

// Output the intro to the product list.
echo '<p>' . esc_html__( 'In case you forgot, here is what you purchased:', 'woo-better-reviews' ) . '</p>';

// Output the list wrapper.
echo '<ul>';

// Loop and name.
foreach ( $sections['product_list'] as $product_id ) {

	// Pull out each part.
	$product_name   = get_the_title( $product_id );
	$product_link   = get_permalink( $product_id );

	// Now make the list item.
	echo '<li>' . esc_attr( $product_name ) . ' <small><a href="' . esc_url( $product_link ) . '#tab-reviews">(' . esc_html__( 'Review Link', 'woo-better-reviews' ) . ')</a></small></li>';
}

// Close the list
echo '</ul>';

// Output our closing.
echo '<p>' . wp_kses_post( $sections['closing'] ) . '</p>';

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
