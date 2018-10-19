<?php
/**
 * Handle the specific layouts fo admin pages.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Admin\AdminPages;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Queries as Queries;

// And pull in any other namespaces.
use WP_Error;

/**
 * Load our primary settings page.
 *
 * @return void
 */
function display_primary_settings_page() {

	// Wrap the entire thing.
	echo '<div class="wrap woo-better-reviews-admin-wrap woo-better-reviews-admin-reviews-wrap">';

		// Handle the title.
		echo '<h1 class="wp-heading-inline woo-better-reviews-admin-title">' . esc_html( get_admin_page_title() ) . '</h1>';

		// Handle the table.
		load_review_list_table(); // WPCS: XSS ok.

	// Close the entire thing.
	echo '</div>';
}

/**
 * Create and return the table of reviews.
 *
 * @param  array $requests  The existing requests.
 *
 * @return HTML
 */
function load_review_list_table() {

	// Fetch the action link.
	$action = Helpers\get_admin_menu_link();

	// Call our table class.
	$table  = new \WooBetterReviews_ListReviews();

	// And output the table.
	$table->prepare_items();

	// And handle the display
	echo '<form class="woo-better-reviews-admin-form" id="woo-better-reviews-admin-reviews-form" action="' . esc_url( $action ) . '" method="post">';

	// The actual table itself.
	$table->display();

	// And close it up.
	echo '</form>';
}

/**
 * Load our attributes settings page.
 *
 * @return void
 */
function display_product_attributes_page() {

	// Fetch the action link.
	$action = Helpers\get_admin_menu_link( 'woo-better-reviews-product-attributes' );

	// Wrap the entire thing.
	echo '<div class="wrap woo-better-reviews-admin-wrap woo-better-reviews-admin-attributes-wrap">';

		// Handle the title.
		echo '<h1 class="wp-heading-inline woo-better-reviews-admin-title">' . esc_html( get_admin_page_title() ) . '</h1>';

		// Wrap the whole thing in a container for columns.
		echo '<div id="col-container" class="wp-clearfix">';

			// Handle the left column.
			echo '<div id="col-left">';
				echo '<div class="col-wrap">';

				// Load the add new item form section.
				echo load_add_attribute_form( $action ); // WPCS: XSS ok.

				echo '</div>';
			echo '</div>';

			// Handle the right column.
			echo '<div id="col-right">';
				echo '<div class="col-wrap">';

				// Load the table form with the existing.
				load_edit_attributes_form(); // WPCS: XSS ok.

				echo '</div>';
			echo '</div>';

		// Close the column container.
		echo '</div>';

	// Close the wrapper.
	echo '</div>';
}

/**
 * Load the form to add a new attribute.
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return HTML
 */
function load_add_attribute_form( $action = '' ) {

	// Add a key to the action link.
	$action = add_query_arg( array( 'wbr-action' => 'add' ), $action );

	// Set an empty.
	$build  = '';

	// Set the form div wrapper.
	$build .= '<div class="form-wrap woo-better-reviews-form-wrap">';

		// Title it.
		$build .= '<h2>' . esc_html__( 'Add New Attribute', 'woo-better-reviews' ) . '</h2>';

		// Now set the actual form itself.
		$build .= '<form id="woo-better-reviews-add-attribute" class="woo-better-reviews-admin-form" method="post" action="' . esc_url( $action ) . '">';

			// Output our nonce.
			$build .= wp_nonce_field( 'wbr_add_attribute_action', 'wbr_add_attribute_nonce', true, false );

			// Set the name field.
			$build .= '<div class="woo-better-reviews-form-field form-field form-required attribute-name-wrap">';

				// Output the label and actual field.
				$build .= '<label for="attribute-name">' . esc_html__( 'Name', 'woo-better-reviews' ) . '</label>';
				$build .= '<input name="new-attribute[name]" id="attribute-name" value="" size="40" aria-required="true" type="text">';

				// Include some explain text.
				$build .= '<p>' . esc_html__( 'Eventual description text', 'woo-better-reviews' ) . '</p>';

			// Close the name field.
			$build .= '</div>';

			// Set the description field.
			$build .= '<div class="woo-better-reviews-form-field form-field attribute-desc-wrap">';

				// Output the label and actual field.
				$build .= '<label for="attribute-desc">' . esc_html__( 'Description', 'woo-better-reviews' ) . '</label>';
				$build .= '<textarea name="new-attribute[desc]" id="attribute-desc" rows="5" cols="40"></textarea>';

				// Include some explain text.
				$build .= '<p>' . esc_html__( 'Eventual description text', 'woo-better-reviews' ) . '</p>';

			// Close the description field.
			$build .= '</div>';

			// Set the min / max labels field.
			$build .= '<div class="woo-better-reviews-form-field form-field attribute-labels-wrap">';

				// Set a label on the top.
				$build .= '<label for="attribute-labels">' . esc_html__( 'Rating Labels', 'woo-better-reviews' ) . '</label>';

				// Output the left side label and actual field.
				$build .= '<span class="woo-better-reviews-form-split woo-better-reviews-form-split-left">';
					$build .= '<input name="new-attribute[min-label]" id="attribute-label-min" value="" class="widefat" type="text">';
					$build .= '<label class="woo-better-reviews-form-split-label" for="attribute-label-min">' . esc_html__( 'Minimum', 'woo-better-reviews' ) . '</label>';
				$build .= '</span>';

				// Output the left side label and actual field.
				$build .= '<span class="woo-better-reviews-form-split woo-better-reviews-form-split-right">';
					$build .= '<input name="new-attribute[max-label]" id="attribute-label-max" value="" class="widefat" type="text">';
					$build .= '<label class="woo-better-reviews-form-split-label" for="attribute-label-max">' . esc_html__( 'Maximum', 'woo-better-reviews' ) . '</label>';
				$build .= '</span>';

			// Close the min / max labels field.
			$build .= '</div>';

			// Output the submit button.
			$build .= get_submit_button( __( 'Add New Attribute', 'woo-better-reviews' ), 'primary', 'add-new-attribute' );

		// Close up the form markup.
		$build .= '</form>';

	// Close up the form div wrapper.
	$build .= '</div>';

	// Return the entire form build.
	return $build;
}

/**
 * Load the form to edit existing attributes.
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return HTML
 */
function load_edit_attributes_form( $action = '' ) {

	// Add a key to the action link.
	$action = add_query_arg( array( 'wbr-action' => 'edit' ), $action );

	// Call our table class.
	$table  = new \WooBetterReviews_ListAttributes();

	// And output the table.
	$table->prepare_items();

	// And handle the display
	echo '<form class="woo-better-reviews-admin-form" id="woo-better-reviews-admin-attributes-form" action="' . esc_url( $action ) . '" method="post">';

	// The actual table itself.
	$table->display();

	// And close it up.
	echo '</form>';
}


function display_author_characteristics_page() {
	echo 'hello characteristics';
}
