<?php
/**
 * Our activation call
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Activate;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Database as Database;
use LiquidWeb\WooBetterReviews\Queries as Queries;

/**
 * Our inital setup function when activated.
 *
 * @return void
 */
function activate() {

	// Do the check for WooCommerce being active.
	$maybe_woo  = Helpers\maybe_woo_activated();

	// Deactivate if we aren't Woo'd.
	if ( ! $maybe_woo ) {

		// Deactivate the plugin.
		deactivate_plugins( Core\BASE );

		// And display the notice.
		wp_die( sprintf( __( 'Using the Better Reviews for WooCommerce plugin required that you have WooCommerce installed and activated. <a href="%s">Click here</a> to return to the plugins page.', 'woo-better-reviews' ), admin_url( '/plugins.php' ) ) );
	}

	// Run the check on the DB table.
	Database\maybe_install_tables();

	// Handle the meta swap.
	backup_existing_review_counts();

	// Set our initial options.
	set_initial_options();

	// Schedule our cron job assuming it isn't there already.
	if ( ! wp_next_scheduled( Core\REMINDER_CRON ) ) {
		Utilities\modify_reminder_cron( false, 'twicedaily' );
	}

	// Include our action so that we may add to this later.
	do_action( Core\HOOK_PREFIX . 'activate_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( Core\FILE, __NAMESPACE__ . '\activate' );

/**
 * Take the existing review counts and save them backed up.
 *
 * @param  boolean $replace  Whether to replace the existing count with a zero.
 *
 * @return void
 */
function backup_existing_review_counts( $replace = false ) {

	// Get my legacy stuff.
	$legacy_counts  = Queries\get_legacy_review_counts();

	// Bail without any.
	if ( empty( $legacy_counts ) ) {
		return;
	}

	// Loop them to update the meta count.
	foreach ( $legacy_counts as $product_id => $review_count ) {

		// Make sure this is a product.
		if ( 'product' !== get_post_type( absint( $product_id ) ) ) {
			continue;
		}

		// Handle the before action.
		do_action( Core\HOOK_PREFIX . 'before_legacy_backup', $product_id, $review_count );

		// First do the existing as a backup.
		update_post_meta( $product_id, Core\META_PREFIX . 'legacy_count', absint( $review_count ) );

		// Update the Woo item with a zero.
		if ( ! empty( $replace ) ) {
			update_post_meta( $product_id, '_wc_review_count', 0 );
		}

		// Handle the after action.
		do_action( Core\HOOK_PREFIX . 'after_legacy_backup', $product_id, $review_count );
	}
}

/**
 * Set the initial options we need.
 *
 * @return void
 */
function set_initial_options() {

	// Set our actual option flags.
	update_option( 'woocommerce_enable_reviews', 'yes' );
	update_option( Core\OPTION_PREFIX . 'plugin_version', Core\VERS );
	update_option( Core\OPTION_PREFIX . 'allow_anonymous', 'no' );
	update_option( Core\OPTION_PREFIX . 'global_attributes', 'yes' );
	update_option( Core\OPTION_PREFIX . 'send_reminders', 'yes' );
	update_option( Core\OPTION_PREFIX . 'reminder_wait', array( 'number' => 2, 'unit' => 'weeks' ) );
}
