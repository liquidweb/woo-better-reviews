<?php
/**
 * Handle the specific layouts for admin pages.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\AdminPages;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Utilities as Utilities;
use Nexcess\WooBetterReviews\Queries as Queries;
use Nexcess\WooBetterReviews\Admin\AdminSetup as AdminSetup;

// And pull in any other namespaces.
use WP_Error;

/**
 * Create and return the table of reviews.
 *
 * @return void
 */
function display_reviews_list_page() {

	// Fetch the action link.
	$action = Helpers\get_admin_menu_link( Core\REVIEWS_ANCHOR );

	// Check to see if we are editing an attribute or not.
	$isedit = ! empty( $_GET['wbr-action-name'] ) && 'edit' === sanitize_text_field( $_GET['wbr-action-name'] ) ? 1 : 0;

	// Wrap the entire thing.
	echo '<div class="wrap woo-better-reviews-admin-wrap woo-better-reviews-admin-reviews-wrap">';

		// Handle the title.
		echo '<h1 class="wp-heading-inline woo-better-reviews-admin-title">' . esc_html( get_admin_page_title() ) . '</h1>';

		// Cut off the header.
		echo '<hr class="wp-header-end">';

		// Load the proper page.
		echo ! $isedit ? load_primary_reviews_display( $action ) : load_edit_single_review_form( $action );

	// Close the entire thing.
	echo '</div>';
}

/**
 * Load the primary display, which is the big table
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return void
 */
function load_primary_reviews_display( $action = '' ) {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

	// Call our table class.
	$table  = new \WooBetterReviews_ListReviews();

	// And output the table.
	$table->prepare_items();

	// The actual table itself.
	$table->display();
}

/**
 * Load the form to edit an existing review.
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return HTML
 */
function load_edit_single_review_form( $action ) {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

	// Get the overall review data.
	$review_data    = Queries\get_single_review( $_GET['wbr-item-id'] );

	// Parse out the scoring data.
	$review_scoring = Utilities\format_review_scoring_data( (array) $review_data, true );

	// Set an empty.
	$build  = '';

	// Now set the actual form itself.
	$build .= '<form class="woo-better-reviews-admin-form woo-better-reviews-admin-edit-single-item-form" id="woo-better-reviews-admin-edit-review-form" action="' . esc_url( $action ) . '" method="post">';

		$build .= '<input type="hidden" name="action" value="update">';
		$build .= '<input type="hidden" name="item-id" value="' . absint( $_GET['wbr-item-id'] ) . '">';
		$build .= '<input type="hidden" name="item-type" value="review">';
		$build .= '<input type="hidden" name="product-id" value="' . absint( $review_data->product_id ) . '">';
		$build .= '<input type="hidden" name="author-id" value="' . absint( $review_data->author_id ) . '">';

		// Output our nonce.
		$build .= wp_nonce_field( 'wbr_edit_review_action', 'wbr_edit_review_nonce', true, false );

		// Now set the table wrap.
		$build .= '<table class="form-table">';

			// Set up the table body.
			$build .= '<tbody>';

				// Set the title field.
				$build .= '<tr class="form-field woo-better-reviews-form-field form-required review-title-wrap">';

					// Output the label.
					$build .= '<th scope="row">';
						$build .= '<label for="review-title">' . esc_html__( 'Title', 'woo-better-reviews' ) . '</label>';
					$build .= '</th>';

					// Output the actual field.
					$build .= '<td>';
						$build .= '<input name="review-args[title]" id="review-title" value="' . esc_attr( $review_data->review_title ) . '" size="40" aria-required="true" type="text">';
					$build .= '</td>';

				// Close the title field.
				$build .= '</tr>';

				// Set the main content field.
				$build .= '<tr class="form-field woo-better-reviews-form-field review-content-wrap">';

					// Output the label.
					$build .= '<th scope="row">';
						$build .= '<label for="review-content">' . esc_html__( 'Content', 'woo-better-reviews' ) . '</label>';
					$build .= '</th>';

					// Output the actual field.
					$build .= '<td>';
						$build .= Utilities\set_review_form_editor( 'review-content', 'review-args[content]', 'edit-review-content', $review_data->review_content );
					$build .= '</td>';

				// Close the main content field.
				$build .= '</tr>';

				// Set the review status field.
				$build .= '<tr class="form-field woo-better-reviews-form-field review-status-wrap">';

					// Output the label.
					$build .= '<th scope="row">';
						$build .= '<label for="review-status">' . esc_html__( 'Status', 'woo-better-reviews' ) . '</label>';
					$build .= '</th>';

					// Output the actual field.
					$build .= '<td>';
						$build .= AdminSetup\set_admin_data_dropdown( Helpers\get_review_statuses(), 'review-args[status]', 'review-status', $review_data->review_status );
					$build .= '</td>';

				// Close the review status field.
				$build .= '</tr>';

				// Set the read-only scoring fields.
				$build .= '<tr class="form-field woo-better-reviews-form-field review-scoring-wrap">';

					// Output the label.
					$build .= '<th scope="row">';
						$build .= '<label for="review-status">' . esc_html__( 'Scoring', 'woo-better-reviews' ) . '</label>';
					$build .= '</th>';

					// Output the actual field.
					$build .= '<td>';

						// Wrap it in an unordered list.
						$build .= '<ul class="woo-better-reviews-form-inside-list-wrap">';

							// Set a list item.
							$build .= '<li class="woo-better-reviews-form-inside-list-single woo-better-reviews-form-inside-list-total-score">';

								// Do the label.
								$build .= '<span class="woo-better-reviews-form-inside-list-label">' . esc_html__( 'Total Rating:', 'woo-better-reviews' ) . ' </span>';

								// Do the value.
								$build .= '<span class="woo-better-reviews-form-inside-list-value">';
									$build .= AdminSetup\set_admin_star_display( $review_scoring['total_score'] );
								$build .= '</span>';

							// Close the list item for total score.
							$build .= '</li>';

							// Loop my individual attribute scores.
							foreach ( $review_scoring['rating_attributes'] as $single_attribute ) {

								// Set a list item.
								$build .= '<li class="woo-better-reviews-form-inside-list-single woo-better-reviews-form-inside-list-total-score">';

									// Do the label.
									$build .= '<span class="woo-better-reviews-form-inside-list-label">' . esc_html( $single_attribute['label'] ) . ': </span>';

									// Do the value.
									$build .= '<span class="woo-better-reviews-form-inside-list-value">';
										$build .= sprintf( __( '%s out of 7', 'woo-better-reviews' ), absint( $single_attribute['value'] ) );
									$build .= '</span>';

								// Close the list item for total score.
								$build .= '</li>';
							}

						// Close up the list.
						$build .= '</ul>';

					// Close the td.
					$build .= '</td>';

				// Close the review scoring fields.
				$build .= '</tr>';

			// Close up the table body.
			$build .= '</tbody>';

		// Close up the table.
		$build .= '</table>';

		// Output the submit button.
		$build .= '<div class="edit-tag-actions edit-single-item-actions edit-review-actions">';

			// Wrap it in a paragraph.
			$build .= '<p class="submit">';

				// The actual submit button.
				$build .= get_submit_button( __( 'Update Review', 'woo-better-reviews' ), 'primary', 'edit-existing-review', false );

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
 * Load our attributes settings page.
 *
 * @return void
 */
function display_review_attributes_page() {

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

		// Cut off the header.
		echo '<hr class="wp-header-end">';

		// Load the proper page.
		echo ! $isedit ? load_primary_attributes_display( $action ) : load_edit_single_attribute_form( $action );

	// Close the wrapper.
	echo '</div>';
}

/**
 * Display the main admin page for author traits.
 *
 * @return HTML
 */
function display_author_traits_page() {

	// Pull in the action link.
	$action = Helpers\get_admin_menu_link( Core\CHARSTCS_ANCHOR );

	// Check to see if we are editing an attribute or not.
	$isedit = ! empty( $_GET['wbr-action-name'] ) && 'edit' === sanitize_text_field( $_GET['wbr-action-name'] ) ? 1 : 0;

	// Check for a search string.
	$search = Helpers\maybe_search_term( 'string' );

	// Wrap the entire thing.
	echo '<div class="wrap woo-better-reviews-admin-wrap woo-better-reviews-admin-characteristics-wrap">';

		// Output the title tag.
		echo '<h1 class="wp-heading-inline woo-better-reviews-admin-title">' . esc_html( get_admin_page_title() ) . '</h1>';

		// Output the search subtitle.
		if ( ! empty( $search ) ) {
			printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $search ) );
		}

		// Cut off the header.
		echo '<hr class="wp-header-end">';

		// Load the proper page.
		echo ! $isedit ? load_primary_traits_display( $action ) : load_edit_single_traits_form( $action );

	// Close the wrapper.
	echo '</div>';
}

/**
 * Load the form to edit an existing characteristic.
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return HTML
 */
function display_review_import_page() {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

	// Pull in the action link.
	$action = Helpers\get_admin_importer_link();

	// Count how many reviews we have.
	$counts = Queries\get_existing_woo_reviews( 'counts' );

	// Wrap the entire thing.
	echo '<div class="wrap woo-better-reviews-admin-wrap woo-better-reviews-admin-importer-wrap">';

		// Handle the title.
		echo '<h1 class="wp-heading-inline woo-better-reviews-admin-title">' . esc_html__( 'WooCommerce Product Review Importer', 'woo-better-reviews' ) . '</h1>';

		// Cut off the header.
		echo '<hr class="wp-header-end">';

		// Set the same div on either.
		echo '<div class="woo-better-reviews-importer-wrap">';

			// Load the proper page.
			echo ! empty( $counts ) ? load_primary_importer_display( $counts, $action ) : load_empty_importer_display();

		// Close the dynamic wrapper.
		echo '</div>';

	// Close the entire thing.
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
	echo '<div id="col-container" class="col-primary-display-container col-attributes-container wp-clearfix">';

		// Handle the left column.
		echo '<div id="col-left" class="col-primary-display-left col-attributes-left">';
			echo '<div class="col-wrap">';

			// Load the add new item form section.
			echo load_add_new_attribute_form( $action ); // WPCS: XSS ok.

			echo '</div>';
		echo '</div>';

		// Handle the right column.
		echo '<div id="col-right" class="col-primary-display-right col-attributes-right">';
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
			$build .= '<p class="submit woo-better-reviews-add-new-submit-wrap">';
				$build .= get_submit_button( __( 'Add New Review Attribute', 'woo-better-reviews' ), 'primary', 'add-new-attribute', false );
			$build .= '</p>';

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
function load_edit_single_attribute_form( $action = '' ) {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

	// Get the attribute data.
	$attribute_data = Queries\get_single_attribute( $_GET['wbr-item-id'] );

	// Set an empty.
	$build  = '';

	// Now set the actual form itself.
	$build .= '<form class="woo-better-reviews-admin-form woo-better-reviews-admin-edit-single-item-form" id="woo-better-reviews-admin-edit-attribute-form" action="' . esc_url( $action ) . '" method="post">';

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
		$build .= '<div class="edit-tag-actions edit-single-item-actions edit-attribute-actions">';

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
 * Load the primary display, which is the add new and table list.
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return void
 */
function load_primary_traits_display( $action = '' ) {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

	// Wrap the whole thing in a container for columns.
	echo '<div id="col-container" class="col-primary-display-container col-charstcs-container wp-clearfix">';

		// Handle the left column.
		echo '<div id="col-left" class="col-primary-display-left col-charstcs-left">';
			echo '<div class="col-wrap">';

			// Load the add new item form section.
			echo load_add_new_trait_form( $action ); // WPCS: XSS ok.

			echo '</div>';
		echo '</div>';

		// Handle the right column.
		echo '<div id="col-right" class="col-primary-display-right col-charstcs-right">';
			echo '<div class="col-wrap">';

			// Load the table form with the existing.
			load_trait_list_table_form(); // WPCS: XSS ok.

			echo '</div>';
		echo '</div>';

	// Close the column container.
	echo '</div>';
}

/**
 * Load the form to add a new characteristic.
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return HTML
 */
function load_add_new_trait_form( $action = '' ) {

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
		$build .= '<h2>' . esc_html__( 'Add New Review Author Trait', 'woo-better-reviews' ) . '</h2>';

		// Now set the actual form itself.
		$build .= '<form id="woo-better-reviews-add-characteristic" class="woo-better-reviews-admin-form" method="post" action="' . esc_url( $action ) . '">';

			// Add some hidden fields to handle the addition.
			$build .= '<input type="hidden" name="action" value="add-new">';
			$build .= '<input type="hidden" name="item-type" value="charstc">';

			// Output our nonce.
			$build .= wp_nonce_field( 'wbr_add_charstcs_action', 'wbr_add_charstcs_nonce', true, false );

			// Set the name field.
			$build .= '<div class="woo-better-reviews-form-field form-field form-required characteristic-name-wrap">';

				// Output the label and actual field.
				$build .= '<label for="charstc-name">' . esc_html__( 'Name', 'woo-better-reviews' ) . '</label>';
				$build .= '<input name="new-charstc[name]" id="charstc-name" value="" size="40" aria-required="true" type="text">';

				// Include some explain text.
				$build .= '<p>' . esc_html__( 'The name is how it appears on your site.', 'woo-better-reviews' ) . '</p>';

			// Close the name field.
			$build .= '</div>';

			// Set the description field.
			$build .= '<div class="woo-better-reviews-form-field form-field characteristic-desc-wrap">';

				// Output the label and actual field.
				$build .= '<label for="charstc-desc">' . esc_html__( 'Description', 'woo-better-reviews' ) . '</label>';
				$build .= '<textarea name="new-charstc[desc]" id="charstc-desc" rows="5" cols="40"></textarea>';

				// Include some explain text.
				$build .= '<p>' . esc_html__( 'The description is optional and may not be displayed based on your theme.', 'woo-better-reviews' ) . '</p>';

			// Close the description field.
			$build .= '</div>';

			// Set the values field.
			$build .= '<div class="woo-better-reviews-form-field form-field form-required characteristic-values-wrap">';

				// Output the label and actual field.
				$build .= '<label for="charstc-values">' . esc_html__( 'Values', 'woo-better-reviews' ) . '</label>';
				$build .= '<input name="new-charstc[values]" id="charstc-values" value="" size="40" aria-required="true" type="text">';

				// Include some explain text.
				$build .= '<p>' . esc_html__( 'Separate individual values with commas.', 'woo-better-reviews' ) . '</p>';

			// Close the name field.
			$build .= '</div>';

			/*
			// Set the field type dropdown field.
			$build .= '<div class="woo-better-reviews-form-field form-field characteristic-type-wrap">';

				// Output the label and actual field.
				$build .= '<label for="charstc-type">' . esc_html__( 'Field Type', 'woo-better-reviews' ) . '</label>';
				$build .= '<select name="new-charstc[type]" id="charstc-type" class="postform">';
				$build .= '<option value="0">' . esc_html__( '(select)', 'woo-better-reviews' ) . '</option>';

				// Now loop my individual fields.
				foreach ( Helpers\get_available_field_types() as $key => $label ) {
					$build .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>';
				}

				// Close up the select box.
				$build .= '</select>';

				// Include some explain text.
				$build .= '<p>' . esc_html__( 'Select the type of field this is.', 'woo-better-reviews' ) . '</p>';

			// Close the type field.
			$build .= '</div>';
			*/
			// Hiding the selection for now.
			$build .= '<input name="new-charstc[type]" value="dropdown" type="hidden">';

			// Output the submit button.
			$build .= '<p class="submit woo-better-reviews-add-new-submit-wrap">';
				$build .= get_submit_button( __( 'Add New Review Author Trait', 'woo-better-reviews' ), 'primary', 'add-new-charstc', false );
			$build .= '</p>';

		// Close up the form markup.
		$build .= '</form>';

	// Close up the form div wrapper.
	$build .= '</div>';

	// Return the entire form build.
	return $build;
}

/**
 * Load the form to edit existing characteristics.
 *
 * @return HTML
 */
function load_trait_list_table_form() {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

	// Call our table class.
	$table  = new \WooBetterReviews_ListCharstcs();

	// And output the table.
	$table->prepare_items();

	// The actual table itself.
	$table->display();
}

/**
 * Load the form to edit an existing characteristic.
 *
 * @param  string $action  The URL to include in the form action.
 *
 * @return HTML
 */
function load_edit_single_traits_form( $action = '' ) {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

	// Get the characteristic data.
	$charstcs_data  = Queries\get_single_charstcs( $_GET['wbr-item-id'] );

	// Set an empty.
	$build  = '';

	// Now set the actual form itself.
	$build .= '<form class="woo-better-reviews-admin-form woo-better-reviews-admin-edit-single-item-form" id="woo-better-reviews-admin-edit-charstcs-form" action="' . esc_url( $action ) . '" method="post">';

		$build .= '<input type="hidden" name="action" value="update">';
		$build .= '<input type="hidden" name="item-id" value="' . absint( $_GET['wbr-item-id'] ) . '">';
		$build .= '<input type="hidden" name="item-type" value="charstcs">';

		// Output our nonce.
		$build .= wp_nonce_field( 'wbr_edit_charstcs_action', 'wbr_edit_charstcs_nonce', true, false );

		// Now set the table wrap.
		$build .= '<table class="form-table">';

			// Set up the table body.
			$build .= '<tbody>';

				// Set the name field.
				$build .= '<tr class="form-field woo-better-reviews-form-field form-required charstcs-name-wrap">';

					// Output the label.
					$build .= '<th scope="row">';
						$build .= '<label for="charstcs-name">' . esc_html__( 'Name', 'woo-better-reviews' ) . '</label>';
					$build .= '</th>';

					// Output the actual field.
					$build .= '<td>';

						// The field input.
						$build .= '<input name="charstcs-args[name]" id="charstcs-name" value="' . esc_attr( $charstcs_data['charstcs_name'] ) . '" size="40" aria-required="true" type="text">';

						// Include some explain text.
						$build .= '<p class="description">' . esc_html__( 'The name is how it appears on your site.', 'woo-better-reviews' ) . '</p>';

					$build .= '</td>';

				// Close the name field.
				$build .= '</tr>';

				// Set the description field.
				$build .= '<tr class="form-field woo-better-reviews-form-field charstcs-desc-wrap">';

					// Output the label.
					$build .= '<th scope="row">';
						$build .= '<label for="charstcs-desc">' . esc_html__( 'Description', 'woo-better-reviews' ) . '</label>';
					$build .= '</th>';

					// Output the actual field.
					$build .= '<td>';

						// The field input.
						$build .= '<textarea name="charstcs-args[desc]" id="charstcs-desc" rows="5" cols="40">' . esc_textarea( $charstcs_data['charstcs_desc'] ) . '</textarea>';

						// Include some explain text.
						$build .= '<p class="description">' . esc_html__( 'The description is optional and may not be displayed based on your theme.', 'woo-better-reviews' ) . '</p>';

					$build .= '</td>';

				// Close the description field.
				$build .= '</tr>';

				// Set the values field.
				$build .= '<tr class="form-field woo-better-reviews-form-field charstcs-values-wrap">';

					// Output the label.
					$build .= '<th scope="row">';
						$build .= '<label for="charstc-values">' . esc_html__( 'Values', 'woo-better-reviews' ) . '</label>';
					$build .= '</th>';

					// Output the actual field.
					$build .= '<td>';

						// The field input.
						$build .= '<input name="charstcs-args[values]" id="charstc-values" value="' . Utilities\format_array_values_display( $charstcs_data['charstcs_values'], 'inline' ) . '" size="40" aria-required="true" type="text">';

						// Include some explain text.
						$build .= '<p class="description">' . esc_html__( 'Separate individual values with commas.', 'woo-better-reviews' ) . '</p>';

					$build .= '</td>';

				// Close the values field.
				$build .= '</tr>';

				/*
				// Set the type field.
				$build .= '<tr class="form-field woo-better-reviews-form-field charstcs-type-wrap">';

					// Output the label.
					$build .= '<th scope="row">';
						$build .= '<label for="charstc-type">' . esc_html__( 'Field Type', 'woo-better-reviews' ) . '</label>';
					$build .= '</th>';

					// Output the actual field.
					$build .= '<td>';

						// The field input.
						$build .= '<select name="charstcs-args[type]" id="charstc-type" class="postform">';
						$build .= '<option value="0">' . esc_html__( '(select)', 'woo-better-reviews' ) . '</option>';

						// Now loop my individual fields.
						foreach ( Helpers\get_available_field_types() as $key => $label ) {
							$build .= '<option value="' . esc_attr( $key ) . '" ' . selected( $charstcs_data['charstcs_type'], $key, false ) . ' >' . esc_html( $label ) . '</option>';
						}

						// Close up the select box.
						$build .= '</select>';

						// Include some explain text.
						$build .= '<p class="description">' . esc_html__( 'Select the type of field this is.', 'woo-better-reviews' ) . '</p>';

					$build .= '</td>';

				// Close the values field.
				$build .= '</tr>';
				*/
				$build .= '<input name="charstcs-args[type]" value="' . $charstcs_data['charstcs_type'] . '" type="hidden">';

			// Close up the table body.
			$build .= '</tbody>';

		// Close up the table.
		$build .= '</table>';

		// Output the submit button.
		$build .= '<div class="edit-tag-actions edit-single-item-actions edit-charstcs-actions">';

			// Wrap it in a paragraph.
			$build .= '<p class="submit">';

				// The actual submit button.
				$build .= get_submit_button( __( 'Update Review Author Trait', 'woo-better-reviews' ), 'primary', 'edit-existing-charstcs', false );

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
 * Load the page including the form to trigger an import.
 *
 * @param  integer $review_count  How many reviews we have to convert.
 * @param  string  $action        The URL to include in the form action.
 *
 * @return HTML
 */
function load_primary_importer_display( $review_count = 0, $action = '' ) {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not permitted to view this page.', 'woo-better-reviews' ) );
	}

	// If somehow the review count didn't come, return our empty.
	if ( empty( $review_count ) ) {
		return load_empty_importer_display();
	}

	// Set up the button text.
	$set_button_txt = sprintf( _n( 'Import %d Existing Review', 'Import %d Existing Reviews', absint( $review_count ), 'woo-better-reviews' ), absint( $review_count ) );

	// Set an empty.
	$build  = '';

	// Add an introduction.
	$build .= '<p>' . esc_html__( 'Import any existing WooCommerce product reviews to the Better Product Reviews for WooCommerce system.', 'woo-better-reviews' ) . '</p>';

	// Now set the actual form itself.
	$build .= '<form class="woo-better-reviews-admin-form woo-better-reviews-admin-import-existing-form" id="woo-better-reviews-admin-import-existing-form" action="' . esc_url( $action ) . '" method="post">';

		// Do the label.
		$build .= '<p><label for="wbr-purge-on-import">';

			// Output the checkbox.
			$build .= '<input type="checkbox" name="wbr-purge-on-import" id="wbr-purge-on-import" value="yes" />';

			// Output the actual text.
			$build .= '&nbsp;' . __( 'Purge existing reviews after import.', 'woo-better-reviews' );

			// And close the label.
		$build .=  '</label></p>';

		// Output the submit button.
		$build .= '<div class="woo-better-reviews-import-submit-wrapper">';

			// Wrap it in a paragraph.
			$build .= '<p class="submit">';

				// The actual submit button.
				$build .= get_submit_button( __( $set_button_txt, 'woo-better-reviews' ), 'primary', 'import-existing-reviews', false );

				// Our cancel link.
				$build .= '<span class="cancel-import-link-wrap">';
					$build .= '<a class="cancel-import-link" href="' . esc_url( $action ) . '">' . esc_html__( 'Cancel', 'woo-better-reviews' ) . '</a>';
				$build .= '</span>';

			// Close up the paragraph.
			$build .= '</p>';

			// Output our nonce.
			$build .= wp_nonce_field( 'wbr_run_import_action', 'wbr_run_import_nonce', true, false );

		// Close up the submit wrap.
		$build .= '</div>';

	// Close up the form markup.
	$build .= '</form>';

	// Return the entire form build.
	return $build;
}

/**
 * Load the page for when no import can be done.
 *
 * @return HTML
 */
function load_empty_importer_display() {

	// Return just a simple sentence for now.
	return '<p>' . esc_html__( 'Well, this is awkward. It looks as though you do not have any existing WooCommerce reviews to import.', 'woo-better-reviews' ) . '</p>';
}
