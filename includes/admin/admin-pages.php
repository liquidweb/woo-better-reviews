<?php
/**
 * Handle the specific layouts for admin pages.
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
 * Create and return the table of reviews.
 *
 * @return void
 */
function display_reviews_list_page() {

	// Wrap the entire thing.
	echo '<div class="wrap woo-better-reviews-admin-wrap woo-better-reviews-admin-reviews-wrap">';

		// Handle the title.
		echo '<h1 class="wp-heading-inline woo-better-reviews-admin-title">' . esc_html( get_admin_page_title() ) . '</h1>';

		// Cut off the header.
		echo '<hr class="wp-header-end">';

		// Call our table class.
		$table  = new \WooBetterReviews_ListReviews();

		// And output the table.
		$table->prepare_items();

		// The actual table itself.
		$table->display();

	// Close the entire thing.
	echo '</div>';
}

/**
 * Load our attributes settings page.
 *
 * @return void
 */
function display_product_attributes_page() {

	// Fetch the action link.
	$action = Helpers\get_admin_menu_link( Core\ATTRIBUTES_ANCHOR );

	// Check to see if we are editing an attribute or not.
	$isedit = ! empty( $_GET['wbr-action-name'] ) && 'edit' === sanitize_text_field( $_GET['wbr-action-name'] ) ? 1 : 0;

	// Check for a search string.
	$search = Helpers\maybe_search_term( 'string' );

	// Wrap the entire thing.
	echo '<div class="wrap woo-better-reviews-admin-wrap woo-better-reviews-admin-attributes-wrap">';

		// Output the title tag.
		echo '<h1 class="wp-heading-inline woo-better-reviews-admin-title">' . esc_html( get_admin_page_title() ) . '</h1>';

		// Output the search subtitle.
		if ( ! empty( $search ) ) {
			printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $search ) );
		}

		// Load the proper page.
		echo ! $isedit ? load_primary_attributes_display( $action ) : load_edit_single_attribute_form( $action );

	// Close the wrapper.
	echo '</div>';
}

/**
 * Load the primary display, which is the add new and table list.
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return void
 */
function load_primary_attributes_display( $action = '' ) {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

	// Wrap the whole thing in a container for columns.
	echo '<div id="col-container" class="col-attributes-container wp-clearfix">';

		// Handle the left column.
		echo '<div id="col-left" class="col-attributes-left">';
			echo '<div class="col-wrap">';

			// Load the add new item form section.
			echo load_add_new_attribute_form( $action ); // WPCS: XSS ok.

			echo '</div>';
		echo '</div>';

		// Handle the right column.
		echo '<div id="col-right" class="col-attributes-right">';
			echo '<div class="col-wrap">';

			// Load the table form with the existing.
			load_attributes_list_table_form(); // WPCS: XSS ok.

			echo '</div>';
		echo '</div>';

	// Close the column container.
	echo '</div>';
}

/**
 * Load the form to add a new attribute.
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return HTML
 */
function load_add_new_attribute_form( $action = '' ) {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

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

			// Add some hidden fields to handle the addition.
			$build .= '<input type="hidden" name="action" value="add-new">';
			$build .= '<input type="hidden" name="item-type" value="attribute">';

			// Output our nonce.
			$build .= wp_nonce_field( 'wbr_add_attribute_action', 'wbr_add_attribute_nonce', true, false );

			// Set the name field.
			$build .= '<div class="woo-better-reviews-form-field form-field form-required attribute-name-wrap">';

				// Output the label and actual field.
				$build .= '<label for="attribute-name">' . esc_html__( 'Name', 'woo-better-reviews' ) . '</label>';
				$build .= '<input name="new-attribute[name]" id="attribute-name" value="" size="40" aria-required="true" type="text">';

				// Include some explain text.
				$build .= '<p>' . esc_html__( 'The name is how it appears on your site.', 'woo-better-reviews' ) . '</p>';

			// Close the name field.
			$build .= '</div>';

			// Set the description field.
			$build .= '<div class="woo-better-reviews-form-field form-field attribute-desc-wrap">';

				// Output the label and actual field.
				$build .= '<label for="attribute-desc">' . esc_html__( 'Description', 'woo-better-reviews' ) . '</label>';
				$build .= '<textarea name="new-attribute[desc]" id="attribute-desc" rows="5" cols="40"></textarea>';

				// Include some explain text.
				$build .= '<p>' . esc_html__( 'The description is optional and may not be displayed based on your theme.', 'woo-better-reviews' ) . '</p>';

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
function load_attributes_list_table_form() {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

	// Call our table class.
	$table  = new \WooBetterReviews_ListAttributes();

	// And output the table.
	$table->prepare_items();

	// The actual table itself.
	$table->display();
}

/**
 * Load the form to edit an existing attribute.
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return HTML
 */
function load_edit_single_attribute_form( $action ) {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

	// Get the attribute data.
	$attribute_data = Queries\get_single_attribute( $_GET['wbr-item-id'] );

	// Set an empty.
	$build  = '';

	// Now set the actual form itself.
	$build .= '<form class="woo-better-reviews-admin-form" id="woo-better-reviews-admin-edit-attribute-form" action="' . esc_url( $action ) . '" method="post">';

		$build .= '<input type="hidden" name="action" value="update">';
		$build .= '<input type="hidden" name="item-id" value="' . absint( $_GET['wbr-item-id'] ) . '">';
		$build .= '<input type="hidden" name="item-type" value="attribute">';

		// Output our nonce.
		$build .= wp_nonce_field( 'wbr_edit_attribute_action', 'wbr_edit_attribute_nonce', true, false );

		// Now set the table wrap.
		$build .= '<table class="form-table">';

			// Set up the table body.
			$build .= '<tbody>';

				// Set the name field.
				$build .= '<tr class="form-field woo-better-reviews-form-field form-required attribute-name-wrap">';

					// Output the label.
					$build .= '<th scope="row">';
						$build .= '<label for="attribute-name">' . esc_html__( 'Name', 'woo-better-reviews' ) . '</label>';
					$build .= '</th>';

					// Output the actual field.
					$build .= '<td>';

						// The field input.
						$build .= '<input name="attribute-args[name]" id="attribute-name" value="' . esc_attr( $attribute_data['attribute_name'] ) . '" size="40" aria-required="true" type="text">';

						// Include some explain text.
						$build .= '<p class="description">' . esc_html__( 'The name is how it appears on your site.', 'woo-better-reviews' ) . '</p>';

					$build .= '</td>';

				// Close the name field.
				$build .= '</tr>';

				// Set the description field.
				$build .= '<tr class="form-field woo-better-reviews-form-field attribute-desc-wrap">';

					// Output the label.
					$build .= '<th scope="row">';
						$build .= '<label for="attribute-desc">' . esc_html__( 'Description', 'woo-better-reviews' ) . '</label>';
					$build .= '</th>';

					// Output the actual field.
					$build .= '<td>';

						// The field input.
						$build .= '<textarea name="attribute-args[desc]" id="attribute-desc" rows="5" cols="40">' . esc_textarea( $attribute_data['attribute_desc'] ) . '</textarea>';

						// Include some explain text.
						$build .= '<p class="description">' . esc_html__( 'The description is optional and may not be displayed based on your theme.', 'woo-better-reviews' ) . '</p>';

					$build .= '</td>';

				// Close the description field.
				$build .= '</tr>';

				// Set the min / max labels field.
				$build .= '<tr class="form-field woo-better-reviews-form-field attribute-labels-wrap">';

					// Output the label.
					$build .= '<th scope="row">';
						$build .= '<label for="attribute-labels">' . esc_html__( 'Rating Labels', 'woo-better-reviews' ) . '</label>';
					$build .= '</th>';

					// Output the actual field.
					$build .= '<td>';

						// Output the left side label and actual field.
						$build .= '<span class="woo-better-reviews-form-split woo-better-reviews-form-split-left">';
							$build .= '<input name="attribute-args[min-label]" id="attribute-label-min" value="' . esc_attr( $attribute_data['min_label'] ) . '" class="widefat" type="text">';
							$build .= '<label class="woo-better-reviews-form-split-label" for="attribute-label-min">' . esc_html__( 'Minimum', 'woo-better-reviews' ) . '</label>';
						$build .= '</span>';

						// Output the left side label and actual field.
						$build .= '<span class="woo-better-reviews-form-split woo-better-reviews-form-split-right">';
							$build .= '<input name="attribute-args[max-label]" id="attribute-label-max" value="' . esc_attr( $attribute_data['max_label'] ) . '" class="widefat" type="text">';
							$build .= '<label class="woo-better-reviews-form-split-label" for="attribute-label-max">' . esc_html__( 'Maximum', 'woo-better-reviews' ) . '</label>';
						$build .= '</span>';

					$build .= '</td>';

				// Close the description field.
				$build .= '</tr>';

			// Close up the table body.
			$build .= '</tbody>';

		// Close up the table.
		$build .= '</table>';

		// Output the submit button.
		$build .= '<div class="edit-tag-actions edit-attribute-actions">';

			// Wrap it in a paragraph.
			$build .= '<p class="submit">';

				// The actual submit button.
				$build .= get_submit_button( __( 'Update Attribute', 'woo-better-reviews' ), 'primary', 'edit-existing-attribute', false );

				// Our cancel link.
				$build .= '<span class="cancel-edit-link-wrap">';
					$build .= '<a class="cancel-edit-link" href="' . esc_url( $action ) . '">' . esc_html__( 'Cancel', 'woo-better-reviews' ) . '</a>';
				$build .= '</span>';

			// Close up the paragraph.
			$build .= '</p>';

		// And the div.
		$build .= '</div>';

	// Close up the form markup.
	$build .= '</form>';

	// Return the entire form build.
	return $build;
}

/**
 * Load the form to something authors.
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return HTML
 */
function display_author_characteristics_page() {
	echo 'hello characteristics';
}
