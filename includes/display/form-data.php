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
function get_review_author_charstcs_form_fields( $author_id = 0, $product_id = 0, $only_keys = false ) {

	// Get all my characteristics for this form.
	$fetch_form_traits  = Helpers\get_author_traits_for_form( $product_id );

	// Bail without any to display.
	if ( empty( $fetch_form_traits ) ) {
		return false;
	}

	// Check for any applied traits to the author.
	$maybe_get_traits   = ! empty( $author_id ) ? Queries\get_trait_values_for_author( $author_id ) : false;
	$maybe_has_traits   = ! empty( $maybe_get_traits ) ? $maybe_get_traits : array();

	// Loop and add each one to the array.
	foreach ( $fetch_form_traits as $form_trait ) {

		// Skip if no values exist.
		if ( empty( $form_trait['values'] ) ) {
			continue;
		}

		// Set the ID.
		$define_id  = absint( $form_trait['id'] );

		// Check if we have the trait or not.
		$has_trait  = isset( $maybe_has_traits[ $define_id ] ) ? $maybe_has_traits[ $define_id ] : '';

		// See if we have a description.
		$maybe_desc = ! empty( $form_trait['desc'] ) ? $form_trait['desc'] : '';

		// Set our array key.
		$array_key  = sanitize_html_class( $form_trait['slug'] );

		// And add it.
		$setup[ $array_key ] = array(
			'label'         => esc_html( $form_trait['name'] ),
			'slug'          => $form_trait['slug'],
			'desc'          => $maybe_desc,
			'type'          => 'dropdown',
			'required'      => false,
			'include-empty' => true,
			'is-charstcs'   => true,
			'is-trait'      => true,
			'selected'      => $has_trait,
			'charstcs-id'   => $define_id,
			'trait-id'      => $define_id,
			'options'       => $form_trait['values'],
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
			'id'    => 'product-id-hidden',
			'name'  => 'woo-better-reviews-product-id',
			'value' => absint( $product_id ),
			'type'  => 'hidden',
		),

		// Set up the author ID hidden field.
		'author-id-hidden' => array(
			'id'    => 'author-id-hidden',
			'name'  => 'woo-better-reviews-author-id',
			'value' => absint( $author_id ),
			'type'  => 'hidden',
		),

		// Set the trigger.
		'add-trigger' => array(
			'id'    => 'add-trigger',
			'name'  => 'woo-better-reviews-add-new',
			'value' => 1,
			'type'  => 'hidden',
		),

		// And the nonce.
		'new-review-nonce' => array(
			'id'    => 'new-review-nonce',
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
