<?php
/**
 * Handle setting the various bits of form data.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\FormData;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Utilities as Utilities;
use Nexcess\WooBetterReviews\Queries as Queries;

/**
 * Set and return the array of content fields.
 *
 * @param  boolean $only_keys  Whether to return just the keys.
 *
 * @return array
 */
function get_review_content_form_fields( $only_keys = false ) {

	// Set up our array.
	$setup  = array(

		'review-title' => array(
			'label'       => __( 'Review Title', 'woo-better-reviews' ),
			'type'        => 'text',
			'required'    => true,
			'description' => __( 'Example: This product has great features!', 'woo-better-reviews' ),
		),

		'review-content' => array(
			'label'       => __( 'Review Content', 'woo-better-reviews' ),
			'type'        => 'editor-minimal',
			'required'    => true,
			'description' => '',
		),

	);

	// Set the fields filtered.
	$fields = apply_filters( Core\HOOK_PREFIX . 'review_form_content_fields', $setup );

	// Either return the full array, or just the keys if requested.
	return false !== $only_keys ? array_keys( $fields ) : $fields;
}

/**
 * Set and return the array of author entry fields.
 *
 * @param  integer $author_id  The potential author ID tied to the review.
 * @param  boolean $only_keys  Whether to return just the keys.
 *
 * @return array
 */
function get_review_author_base_form_fields( $author_id = 0, $only_keys = false ) {

	// Get some values if we have them.
	$author_display = ! empty( $author_id ) ? get_the_author_meta( 'display_name', $author_id ) : '';
	$author_email   = ! empty( $author_id ) ? get_the_author_meta( 'user_email', $author_id ) : '';

	// Set up our initial array.
	$setup  = array(

		'author-name' => array(
			'label'    => __( 'Your Name', 'woo-better-reviews' ),
			'type'     => 'text',
			'required' => true,
			'value'    => $author_display,
		),

		'author-email' => array(
			'label'    => __( 'Your Email', 'woo-better-reviews' ),
			'type'     => 'email',
			'required' => true,
			'value'    => $author_email,
		),

	);

	// Set the fields filtered.
	$fields = apply_filters( Core\HOOK_PREFIX . 'review_author_base_form_fields', $setup );

	// Either return the full array, or just the keys if requested.
	return false !== $only_keys ? array_keys( $fields ) : $fields;
}

/**
 * Set and return the array of author entry fields.
 *
 * @param  integer $author_id  The potential author ID tied to the review.
 * @param  boolean $only_keys  Whether to return just the keys.
 *
 * @return array
 */
function get_review_author_charstcs_form_fields( $author_id = 0, $only_keys = false ) {

	// Get all my characteristics.
	$fetch_charstcs = Queries\get_all_charstcs( 'display' );

	// Bail without any to display.
	if ( empty( $fetch_charstcs ) ) {
		return false;
	}

	// Loop and add each one to the array.
	foreach ( $fetch_charstcs as $charstcs ) {

		// Skip if no values exist.
		if ( empty( $charstcs['values'] ) ) {
			continue;
		}

		// Set our array key.
		$array_key  = sanitize_html_class( $charstcs['slug'] );

		// And add it.
		$setup[ $array_key ] = array(
			'label'         => esc_html( $charstcs['name'] ),
			'type'          => 'dropdown',
			'required'      => false,
			'include-empty' => true,
			'is-charstcs'   => true,
			'charstcs-id'   => absint( $charstcs['id'] ),
			'options'       => $charstcs['values'],
		);
	}

	// Set the fields filtered.
	$fields = apply_filters( Core\HOOK_PREFIX . 'review_author_charstcs_form_fields', $setup );

	// Either return the full array, or just the keys if requested.
	return false !== $only_keys ? array_keys( $fields ) : $fields;
}

/**
 * Set and return the array of action buttons.
 *
 * @param  boolean $only_keys  Whether to return just the keys.
 *
 * @return array
 */
function get_review_action_buttons_fields( $only_keys = false ) {

	// Set my button array.
	$setup  = array(

		// Set up the submit button items.
		'submit-review' => array(
			'label' => __( 'Submit Review', 'woo-better-reviews' ),
			'class' => 'woo-better-reviews-rating-submit-button',
			'type'  => 'submit',
			'value' => true,
		),

		// Set up the reset button items.
		'reset-fields' => array(
			'label' => __( 'Reset', 'woo-better-reviews' ),
			'class' => 'woo-better-reviews-rating-reset-button',
			'type'  => 'reset',
		),
	);

	// Set the fields filtered.
	$fields = apply_filters( Core\HOOK_PREFIX . 'review_form_action_buttons_fields', $setup );

	// Either return the full array, or just the keys if requested.
	return false !== $only_keys ? array_keys( $fields ) : $fields;
}

/**
 * Set and return the array of hidden meta field data.
 *
 * @param  integer $product_id  The product ID tied to the reviews.
 * @param  integer $author_id   The author ID tied to the reviews.
 * @param  boolean $only_keys   Whether to return just the keys.
 *
 * @return array
 */
function get_review_hidden_meta_fields( $product_id = 0, $author_id = 0, $only_keys = false ) {

	// Bail without the product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Set my button array.
	$setup  = array(

		// Set up the product ID hidden field.
		'product-id-hidden' => array(
			'name'  => 'woo-better-reviews-product-id',
			'value' => absint( $product_id ),
			'type'  => 'hidden',
		),

		// Set up the author ID hidden field.
		'author-id-hidden' => array(
			'name'  => 'woo-better-reviews-author-id',
			'value' => absint( $author_id ),
			'type'  => 'hidden',
		),

		// Set the trigger.
		'add-trigger' => array(
			'name'  => 'woo-better-reviews-add-new',
			'value' => 1,
			'type'  => 'hidden',
		),

		// And the nonce.
		'new-review-nonce' => array(
			'name'  => 'woo-better-reviews-add-new-nonce',
			'value' => wp_create_nonce( 'woo-better-reviews-add-new-action' ),
			'type'  => 'hidden',
		),
	);

	// Set the fields filtered.
	$fields = apply_filters( Core\HOOK_PREFIX . 'review_form_hidden_meta_fields', $setup );

	// Either return the full array, or just the keys if requested.
	return false !== $only_keys ? array_keys( $fields ) : $fields;
}
