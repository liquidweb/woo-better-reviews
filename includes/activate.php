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
use LiquidWeb\WooBetterReviews\Database as Database;
use LiquidWeb\WooBetterReviews\Queries as Queries;

/**
 * Our inital setup function when activated.
 *
 * @return void
 */
function activate() {

	// Run the check on the DB table.
	Database\maybe_install_tables();

	// Handle the meta swap.
	backup_existing_review_counts();

	// Set our initial options.
	set_initial_options();

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
	update_option( 'woocommerce_enable_reviews', 'yes' );
	update_option( Core\OPTION_PREFIX . 'allow_anonymous', 'no' );
	update_option( Core\OPTION_PREFIX . 'global_attributes', 'yes' );
}
