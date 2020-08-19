<?php
/**
 * Handle the product attribute meta assignments.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace Nexcess\WooBetterReviews\Admin\ProductMeta;

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Utilities as Utilities;
use Nexcess\WooBetterReviews\Queries as Queries;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'add_meta_boxes_product', __NAMESPACE__ . '\filter_default_review_metaboxes', 11 );
add_action( 'woocommerce_product_options_advanced', __NAMESPACE__ . '\add_reminder_delay_meta' );
add_action( 'save_post_product', __NAMESPACE__ . '\save_reminder_delay_meta', 10, 2 );
add_action( 'save_post_product', __NAMESPACE__ . '\save_applied_product_meta', 10, 2 );

/**
 * Removes the default reviews metabox in leiu of our own.
 *
 * @param object $post  The entire WP_Post object.
 *
 * @return void
 */
function filter_default_review_metaboxes( $post ) {

	// Run the check if we're enabled or not.
	$maybe_enabled  = Helpers\maybe_reviews_enabled( $post->ID );

	// Bail if we aren't enabled.
	if ( ! $maybe_enabled ) {
		return;
	}

	// First remove the default comment box.
	remove_meta_box( 'commentsdiv', 'product', 'normal' );

	// Create some easy setup args to pass for attributes.
	$attribute_setup_args   = array(
		'global'   => Helpers\maybe_attributes_global(),
		'items'    => Queries\get_all_attributes( 'names' ),
		'selected' => Helpers\get_selected_product_attributes( $post->ID ),
	);

	// Create some easy setup args to pass for characteristics (traits).
	$charstcs_setup_args    = array(
		'global'   => Helpers\maybe_charstcs_global(),
		'items'    => Queries\get_all_charstcs( 'names' ),
		'selected' => Helpers\get_selected_product_charstcs( $post->ID ),
	);

	// Call the actual metaboxs.
	add_meta_box( 'wbr-attribute-metabox', __( 'Product Review Attributes', 'woo-better-reviews' ), __NAMESPACE__ . '\render_attribute_metabox', 'product', 'side', 'core', $attribute_setup_args );
	add_meta_box( 'wbr-charstcs-metabox', __( 'Review Author Traits', 'woo-better-reviews' ), __NAMESPACE__ . '\render_charstcs_metabox', 'product', 'side', 'core', $charstcs_setup_args );
}

/**
 * Build and display the metabox for applying review attributes.
 *
 * @param  object $post      The WP_Post object.
 * @param  array  $callback  The custom callback args.
 *
 * @return void
 */
function render_attribute_metabox( $post, $callback ) {

	// If none exist, show the message and bail.
	if ( empty( $callback['args']['items'] ) ) {

		// Do the message.
		echo '<p class="description">' . __( 'No review attributes have been created yet.', 'woo-better-reviews' ) . '</p>';

		// And be done.
		return;
	}

	// If they are global, just message.
	if ( ! empty( $callback['args']['global'] ) ) {

		// Do the message.
		echo '<p class="description">' . __( 'Review attributes have been enabled globally by the site administrator.', 'woo-better-reviews' ) . '</p>';

		// And be done.
		return;
	}

	// Get my selected items.
	$selected   = ! empty( $callback['args']['selected'] ) ? $callback['args']['selected'] : array();

	// Begin the markup for an unordered list.
	echo '<ul class="woo-better-reviews-product-meta-list woo-better-reviews-product-attribute-list">';

	// Now loop my attributes to create my checkboxes.
	foreach ( $callback['args']['items'] as $attribute_id => $attribute_name ) {

		// Set the field name and ID.
		$field_name = 'wbr-product-attributes[]';
		$field_id   = 'wbr-product-attributes-' . absint( $attribute_id );

		// Determine if it's checked or not.
		$is_checked = in_array( $attribute_id, (array) $selected ) ? 'checked="checked"' : '';

		// Echo the markup.
		echo '<li class="woo-better-reviews-single-product-meta woo-better-reviews-single-product-attribute">';

			// Do the label.
			echo '<label for="' . esc_attr( $field_id ) . '">';

				// Output the checkbox.
				echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . absint( $attribute_id ) . '" ' . $is_checked . ' />';

				// Output the actual text.
				echo '&nbsp;' . esc_html( $attribute_name );

			// And close the label.
			echo  '</label>';

		echo '</li>';
	}

	// Include the hidden field to trigger.
	echo '<input type="hidden" name="wbr-product-meta-attributes" value="go">';

	// Close up the markup.
	echo '</ul>';

	// Gimme some sweet nonce action.
	echo wp_nonce_field( 'wbr_save_product_attrb_action', 'wbr_save_product_attrb_nonce', false, false );
}

/**
 * Build and display the metabox for applying review author traits.
 *
 * @param  object $post      The WP_Post object.
 * @param  array  $callback  The custom callback args.
 *
 * @return void
 */
function render_charstcs_metabox( $post, $callback ) {

	// If none exist, show the message and bail.
	if ( empty( $callback['args']['items'] ) ) {

		// Do the message.
		echo '<p class="description">' . __( 'No review author traits have been created yet.', 'woo-better-reviews' ) . '</p>';

		// And be done.
		return;
	}

	// If they are global, just message.
	if ( ! empty( $callback['args']['global'] ) ) {

		// Do the message.
		echo '<p class="description">' . __( 'Review author traits have been enabled globally by the site administrator.', 'woo-better-reviews' ) . '</p>';

		// And be done.
		return;
	}

	// Get my selected items.
	$selected   = ! empty( $callback['args']['selected'] ) ? $callback['args']['selected'] : array();

	// Begin the markup for an unordered list.
	echo '<ul class="woo-better-reviews-product-meta-list woo-better-reviews-author-charstcs-list">';

	// Now loop my attributes to create my checkboxes.
	foreach ( $callback['args']['items'] as $attribute_id => $attribute_name ) {

		// Set the field name and ID.
		$field_name = 'wbr-review-author-charstcs[]';
		$field_id   = 'wbr-review-author-charstcs-' . absint( $attribute_id );

		// Determine if it's checked or not.
		$is_checked = in_array( $attribute_id, (array) $selected ) ? 'checked="checked"' : '';

		// Echo the markup.
		echo '<li class="woo-better-reviews-single-product-meta woo-better-reviews-single-author-charstcs">';

			// Do the label.
			echo '<label for="' . esc_attr( $field_id ) . '">';

				// Output the checkbox.
				echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . absint( $attribute_id ) . '" ' . $is_checked . ' />';

				// Output the actual text.
				echo '&nbsp;' . esc_html( $attribute_name );

			// And close the label.
			echo  '</label>';

		echo '</li>';
	}

	// Close up the markup.
	echo '</ul>';

	// Include the hidden field to trigger.
	echo '<input type="hidden" name="wbr-product-meta-charstcs" value="go">';

	// Gimme some sweet nonce action.
	echo wp_nonce_field( 'wbr_save_author_charstcs_action', 'wbr_save_author_charstcs_nonce', false, false );
}

/**
 * Display the fields for custom reminder info.
 *
 * @return HTML
 */
function add_reminder_delay_meta() {

	// Call our global.
	global $post;

	// Check to see if we are enabled.
	$maybe_enabled  = Helpers\maybe_reminders_enabled( $post->ID, 'strings' );

	// Get the wait time.
	$wait_time_args = get_post_meta( $post->ID, Core\META_PREFIX . 'reminder_wait', true );

	// Now parse out each one.
	$wait_time_nmbr = ! empty( $wait_time_args['number'] ) ? $wait_time_args['number'] : '';
	$wait_time_unit = ! empty( $wait_time_args['unit'] ) ? $wait_time_args['unit'] : '';

	// Set and filter the wrapper class.
	$wrapper_class  = apply_filters( Core\HOOK_PREFIX . 'product_meta_wrapper_class', 'show_if_simple show_if_variable hide_if_external hide_if_grouped' );

	// Throw a group div around it.
	echo '<div class="options_group wbr-reviews-reminder-product-meta">';

	// Output our checkbox all Woo style.
	woocommerce_wp_checkbox(
		array(
			'id'            => 'product-do-reminders',
			'name'          => Core\META_PREFIX . 'send_reminders',
			'value'         => $maybe_enabled,
			'wrapper_class' => esc_attr( $wrapper_class ),
			'label'         => __( 'Enable Reminders', 'woo-better-reviews' ),
			'description'   => __( 'Send an email reminder for customers to leave product reviews.', 'woo-better-reviews' ),
			'cbvalue'       => 'yes',
		)
	);

	// Do our custom relative date field.
	single_reminder_relative_date_field(
		array(
			'id'            => 'product-reminder-wait',
			'name'          => Core\META_PREFIX . 'reminder_wait',
			'number'        => $wait_time_nmbr,
			'unit'          => $wait_time_unit,
			'enabled'       => $maybe_enabled,
			'wrapper_class' => esc_attr( $wrapper_class ),
			'label'         => __( 'Reminder Delay', 'woo-better-reviews' ),
			'description'   => __( 'Set the amount of time from purchase to send the reminder.', 'woo-better-reviews' ),
		)
	);

	// Output our hidden trigger field all Woo style.
	woocommerce_wp_hidden_input(
		array(
			'id'    => 'product-reminders-trigger',
			'value' => true,
		)
	);

	// Gimme some sweet nonce action.
	wp_nonce_field( 'wbr_save_product_reminder_action', 'wbr_save_product_reminder_nonce', false, true );

	// Close our div.
	echo '</div>';
}

/**
 * Build out the relative date field, same as the admin settings.
 *
 * @param  array $field  The field args being passed.
 *
 * @return HTML
 */
function single_reminder_relative_date_field( $field ) {

	// Set the time windows.
	$periods      = array(
		'days'   => __( 'Day(s)', 'woocommerce' ),
		'weeks'  => __( 'Week(s)', 'woocommerce' ),
		'months' => __( 'Month(s)', 'woocommerce' ),
		'years'  => __( 'Year(s)', 'woocommerce' ),
	);

	// Pull out all the various field info needed.
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'relative-date';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['label']         = isset( $field['label'] ) ? $field['label'] : '';

	// Pull our the array value.
	$stored_arg = get_option( Core\OPTION_PREFIX . 'reminder_wait', array( 'number' => '2', 'unit' => 'weeks' ) );

	// Pull out the meta value based on what was passed.
	$meta_nmbr  = ! empty( $field['number'] ) ? $field['number'] : $stored_arg['number'];
	$meta_unit  = ! empty( $field['unit'] ) ? $field['unit'] : $stored_arg['unit'];

	// Set the div wrapper class.
	$display_cl = ! empty( $field['enabled'] ) && 'no' === sanitize_text_field( $field['enabled'] ) ? 'product-reminder-disabled-hide' : 'product-reminder-enabled-show';

	// Do the display div.
	echo '<div class="product-reminder-duration-wrap ' . esc_attr( $display_cl ) . '">';

		// Start rendering the field.
		echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">';

			// Render our field label.
			echo '<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

			// Render the numerical portion,
			echo '<input type="number" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '[number]" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $meta_nmbr ) . '" /> ';

			// Now do the dropdown.
			echo '<select name="' . esc_attr( $field['name'] ) . '[unit]" style="width: auto;">';

			// Loop each time period we have.
			foreach ( $periods as $period_value => $period_label ) {
				echo '<option value="' . esc_attr( $period_value ) . '"' . selected( $meta_unit, $period_value, false ) . '>' . esc_html( $period_label ) . '</option>';
			}

			// Close up the select.
			echo '</select>';

			// Check for a description field.
			if ( ! empty( $field['description'] ) ) {
				echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
			}

		// Close the field paragraph.
		echo '</p>';

	// Close the display div.
	echo '</div>';
}

/**
 * Save whether or not the reminders are active on the product.
 *
 * @param  integer $post_id  The individual post ID.
 * @param  object  $post     The entire post object.
 *
 * @return void
 */
function save_reminder_delay_meta( $post_id, $post ) {

	// Bail if it isn't an actual product.
	if ( 'product' !== get_post_type( $post_id ) ) {
		return;
	}

	// Make sure the current user has the ability to save.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Check for the triggr.
	if ( empty( $_POST['product-reminders-trigger'] ) ) {
		return;
	}

	// Do our nonce check. ALWAYS A NONCE CHECK.
	if ( empty( $_POST['wbr_save_product_reminder_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_save_product_reminder_nonce'], 'wbr_save_product_reminder_action' ) ) {
		wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
	}

	// Handle the enabled flag based on what was passed.
	$reminder_flag  = ! empty( $_POST[ Core\META_PREFIX . 'send_reminders'] ) ? 'yes' : 'no';

	// Update the meta.
	update_post_meta( $post_id, Core\META_PREFIX . 'send_reminder', $reminder_flag );

	// If we have a yes, also save the time length.
	if ( 'yes' === $reminder_flag ) {

		// Find the reminder meta.
		$reminder_wait  = ! empty( $_POST[ Core\META_PREFIX . 'reminder_wait'] ) ? $_POST[ Core\META_PREFIX . 'reminder_wait'] : array();

		// Pull out the stored array value.
		$option_setting = get_option( Core\OPTION_PREFIX . 'reminder_wait', array( 'number', 2, 'unit' => 'weeks' ) );

		// Pull out the meta value based on what was passed.
		$reminder_nmbr  = ! empty( $reminder_wait['number'] ) ? $reminder_wait['number'] : $option_setting['number'];
		$reminder_unit  = ! empty( $reminder_wait['unit'] ) ? $reminder_wait['unit'] : $option_setting['unit'];

		// Handle the metadata based on what was passed.
		$reminder_args  = array( 'number' => absint( $reminder_nmbr ), 'unit' => esc_attr( $reminder_unit ) );

		// Update the meta.
		update_post_meta( $post_id, Core\META_PREFIX . 'reminder_wait', $reminder_args );
	}
}

/**
 * Save the assigned product attributes.
 *
 * @param  integer $post_id  The individual post ID.
 * @param  object  $post     The entire post object.
 *
 * @return void
 */
function save_applied_product_meta( $post_id, $post ) {

	// Do the constants check.
	$check_constant = Utilities\check_constants_for_process();

	// Bail out if we hit a constant.
	if ( false === $check_constant ) {
		return;
	}

	// Run the check if we're enabled or not.
	$maybe_enabled  = Helpers\maybe_reviews_enabled( $post_id );

	// Bail if we aren't enabled.
	if ( false === $maybe_enabled ) {
		return;
	}

	// Check for posted attributes trigger.
	if ( ! empty( $_POST['wbr-product-meta-attributes'] ) && 'go' === sanitize_text_field( $_POST['wbr-product-meta-attributes'] ) ) {

		// Make sure we have the cap.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( __( 'You do not have the capability to perform this action.', 'woo-better-reviews' ) );
		}

		// Do our nonce check. ALWAYS A NONCE CHECK.
		if ( empty( $_POST['wbr_save_product_attrb_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_save_product_attrb_nonce'], 'wbr_save_product_attrb_action' ) ) {
			wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
		}

		// Set the attributes, cleaned up, maybe.
		$maybe_has_applied  = ! empty( $_POST['wbr-product-attributes'] ) ? array_map( 'absint', $_POST['wbr-product-attributes'] ) : '';

		// And do our best to save them.
		maybe_save_product_attributes( $post_id, $maybe_has_applied );
	}

	// Check for posted charstcs trigger.
	if ( ! empty( $_POST['wbr-product-meta-charstcs'] ) && 'go' === sanitize_text_field( $_POST['wbr-product-meta-charstcs'] ) ) {

		// Make sure we have the cap.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( __( 'You do not have the capability to perform this action.', 'woo-better-reviews' ) );
		}

		// Do our nonce check. ALWAYS A NONCE CHECK.
		if ( empty( $_POST['wbr_save_author_charstcs_nonce'] ) || ! wp_verify_nonce( $_POST['wbr_save_author_charstcs_nonce'], 'wbr_save_author_charstcs_action' ) ) {
			wp_die( __( 'Your security nonce failed.', 'woo-better-reviews' ) );
		}

		// Set the charstcs, cleaned up, maybe.
		$maybe_has_applied  = ! empty( $_POST['wbr-review-author-charstcs'] ) ? array_map( 'absint', $_POST['wbr-review-author-charstcs'] ) : '';

		// And do our best to save them.
		maybe_save_product_charstcs( $post_id, $maybe_has_applied );
	}

	// Nothing left to check inside.
}

/**
 * Take the provided attributes and save them.
 *
 * @param  integer $post_id     The ID we're saving.
 * @param  array   $post_items  The attributes we wanna save.
 *
 * @return void
 */
function maybe_save_product_attributes( $post_id = 0, $post_items = array() ) {

	// Run the before action.
	do_action( Core\HOOK_PREFIX . 'before_product_attributes_save', $post_id, $post_items );

	// Now update the array.
	update_post_meta( $post_id, Core\META_PREFIX . 'product_attributes', $post_items );

	// Handle some transient purging.
	Utilities\purge_transients( Core\HOOK_PREFIX . 'attributes_product' . $post_id );

	// Run the after action.
	do_action( Core\HOOK_PREFIX . 'after_product_attributes_save', $post_id, $post_items );
}

/**
 * Take the provided charstcs and save them.
 *
 * @param  integer $post_id     The ID we're saving.
 * @param  array   $post_items  The charstcs we wanna save.
 *
 * @return void
 */
function maybe_save_product_charstcs( $post_id = 0, $post_items = array() ) {

	// Run the before action.
	do_action( Core\HOOK_PREFIX . 'before_product_author_charstcs_save', $post_id, $post_items );

	// Now update the array.
	update_post_meta( $post_id, Core\META_PREFIX . 'product_author_charstcs', $post_items );

	// Handle some transient purging.
	Utilities\purge_transients( Core\HOOK_PREFIX . 'author_charstcs_product' . $post_id );

	// Run the after action.
	do_action( Core\HOOK_PREFIX . 'after_product_author_charstcs_save', $post_id, $post_items );
}
