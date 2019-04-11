<?php
/**
 * Handle some front-end functionality.
 *
 * @package WooBetterReviews
 */

// Declare our namespace (same as the main).
namespace LiquidWeb\WooBetterReviews;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Queries as Queries;
use LiquidWeb\WooBetterReviews\Database as Database;
use LiquidWeb\WooBetterReviews\ConvertExisting as ConvertExisting;

// Pull in the CLI items.
use WP_CLI;
use WP_CLI_Command;

/**
 * Extend the CLI command class with our own.
 */
class WBR_Commands extends WP_CLI_Command {

	/**
	 * Get the array of arguments for the runcommand function.
	 *
	 * @param  array $custom  Any custom args to pass.
	 *
	 * @return array
	 */
	protected function get_command_args( $custom = array() ) {

		// Set my base args.
		$args   = array(
			'return'     => true,   // Return 'STDOUT'; use 'all' for full object.
			'parse'      => 'json', // Parse captured STDOUT to JSON array.
			'launch'     => false,  // Reuse the current process.
			'exit_error' => false,   // Halt script execution on error.
		);

		// Return either the base args, or the merged item.
		return ! empty( $custom ) ? wp_parse_args( $args, $custom ) : $args;
	}

	/**
	 * Update the review count meta keys.
	 *
	 * @param  integer $product_id    Which product ID we're updating.
	 * @param  integer $review_count  What the review count is.
	 *
	 * @return void
	 */
	protected function update_review_count( $product_id = 0, $review_count = 0 ) {

		// Confirm we have a product ID.
		if ( empty( $product_id ) ) {
			return;
		}

		// Add the meta flags to both.
		WP_CLI::runcommand( 'post meta update ' . absint( $product_id ) . ' _wc_review_count ' . absint( $review_count ) . ' --quiet=true' );
		WP_CLI::runcommand( 'post meta update ' . absint( $product_id ) . ' ' . Core\META_PREFIX . 'review_count ' . absint( $review_count ) . ' --quiet=true' );
	}

	/**
	 * Update the average review scoring.
	 *
	 * @param  integer $product_id     The product ID we're updating.
	 * @param  array   $review_totals  The array of scoring totals.
	 *
	 * @return void
	 */
	protected function calculate_review_average( $product_id = 0, $review_totals = array() ) {

		// Bail without a product ID or totals.
		if ( empty( $product_id ) || empty( $review_totals ) ) {
			return;
		}

		// And calculate the average.
		$review_average = array_sum( $review_totals ) / count( $review_totals );

		// Round it.
		$review_rounded = round( $review_average, 0 );

		// Make sure the average is not zero.
		$review_no_zero = absint( $review_rounded ) < 1 ? 1 : absint( $review_rounded );

		// Add the meta flags to both.
		WP_CLI::runcommand( 'post meta update ' . absint( $product_id ) . ' _wc_average_rating ' . absint( $review_no_zero ) . ' --quiet=true' );
		WP_CLI::runcommand( 'post meta update ' . absint( $product_id ) . ' ' . Core\META_PREFIX . 'average_rating ' . absint( $review_no_zero ) . ' --quiet=true' );
	}

	/**
	 * Get all the product data we want.
	 *
	 * @param  array $fields  Which field(s) to pull from.
	 *
	 * @return array
	 */
	protected function get_product_data( $fields = array() ) {

		// Determine the args for the post fields.
		$set_fields = ! empty( $fields ) ? implode( ',', (array) $fields ) : 'ID,post_title';

		// Set the query args.
		$setup_args = array(
			'post',
			'list',
			'--post_type=product',
			'--post_status=publish',
			'--comment_status=open',
			'--fields=' . $set_fields,
			'--format=json'
		);

		// Get an array of the product IDs with open comment status.
		$data_array = WP_CLI::runcommand( implode( ' ', $setup_args ), $this->get_command_args() );

		// Return what we have.
		return ! empty( $data_array ) ? wp_list_pluck( $data_array, 'post_title', 'ID' ) : false;
	}

	/**
	 * Run all the scoring recalculations for scoring.
	 *
	 * ## EXAMPLES
	 *
	 *     wp woo-better-reviews recalculate
	 *
	 * @when after_wp_load
	 */
	function recalculate( $args = array(), $assoc_args = array() ) {

		// Go and get my product data.
		$product_data   = $this->get_product_data();

		// Bail on empty or error.
		if ( empty( $product_data ) || is_wp_error( $product_data ) ) {
			WP_CLI::error( __( 'No available product IDs could be retrieved.', 'woo-better-reviews' ) );
		}

		// Display how many products we're checking.
		WP_CLI::line( sprintf( __( 'Recalculating available review scoring data for %d products...', 'woo-better-reviews' ), count( $product_data ) ) );

		// Set a counter.
		$update = 0;

		// Now loop my product IDs check for reviews.
		foreach ( (array) $product_data as $product_id => $product_title ) {

			// Fetch my approved counts and totals.
			$maybe_approved = Queries\get_approved_reviews_for_product( $product_id, 'total' );

			// Skip any that don't have reviews.
			if ( empty( $maybe_approved ) ) {

				// Say we're skipping it.
				WP_CLI::log( sprintf( __( '"%s" has no approved reviews, skipping...', 'woo-better-reviews' ), $product_title ) );

				// And continue.
				continue;
			}

			// Parse out the counts and total.
			$review_count   = ! empty( $maybe_approved ) ? count( $maybe_approved ) : 0;
			$review_totals  = is_array( $maybe_approved ) ? array_map( 'absint', $maybe_approved ) : false;

			// Run the count update.
			$this->update_review_count( $product_id, $review_count );

			// If we wanted averages too, run that.
			if ( ! empty( $review_totals ) ) {
				$this->calculate_review_average( $product_id, $review_totals );
			}

			// Set my "success" text with some color.
			$success_text   = WP_CLI::colorize( "%G" . sprintf( __( '"%s" has been updated!', 'woo-better-reviews' ), $product_title ) . "%n" );

			// And output the text.
			WP_CLI::log( $success_text );

			// Increment the counter.
			$update++;
		}

		// Handle the transient purging.
		Utilities\purge_transients( null, 'reviews' );

		// Show the result and bail.
		WP_CLI::success( sprintf( _n( '%d product has been updated.', '%d products have been updated.', absint( $update ), 'woo-better-reviews' ), absint( $update ) ) );
		WP_CLI::halt( 0 );
	}

	/**
	 * Approve all the pending reviews.
	 *
	 * ## EXAMPLES
	 *
	 *     wp woo-better-reviews approve
	 *
	 * @when after_wp_load
	 */
	function approve( $args = array(), $assoc_args = array() ) {

		// Get my pending reviews.
		$pending_reviews    = Queries\get_pending_reviews( 'ids' );

		// Bail on empty or error.
		if ( empty( $pending_reviews ) || is_wp_error( $pending_reviews ) ) {

			// Display the 'none' message and halt.
			WP_CLI::line( __( 'No pending reviews exist.', 'woo-better-reviews' ) );
			WP_CLI::halt( 0 );
		}

		// Output the confirm.
		WP_CLI::confirm( sprintf( __( 'Are you sure you want to approve %d pending reviews?', 'woo-better-reviews' ), count( $pending_reviews ) ), $assoc_args );

		// Set a counter.
		$update = 0;

		// Now loop my product IDs check for reviews.
		foreach ( (array) $pending_reviews as $review_id ) {

			// Run the update.
			$maybe_updated  = Database\update( 'content', absint( $review_id ), array( 'review_status' => 'approved' ) );

			// Check for some error return or blank.
			if ( empty( $maybe_updated ) || false === $maybe_updated || is_wp_error( $maybe_updated ) ) {
				WP_CLI::error( sprintf( __( 'Review ID %d could not be updated, exiting now.', 'woo-better-reviews' ), absint( $review_id ) ) );
			}

			// Increment the counter.
			$update++;
		}

		// Handle the transient purging.
		Utilities\purge_transients( null, 'reviews' );

		// Show the result and bail.
		WP_CLI::success( sprintf( _n( '%d review has been updated.', '%d reviews have been updated.', absint( $update ), 'woo-better-reviews' ), absint( $update ) ) );
		WP_CLI::halt( 0 );
	}

	/**
	 * Convert the existing comment-based reviews.
	 *
	 * ## OPTIONS
	 *
	 * [--cleanup]
	 * : How to handle the cleanup of the existing.
	 * ---
	 * default: convert
	 * options:
	 *   - convert
	 *   - purge
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp woo-better-reviews convert
	 *     wp woo-better-reviews convert --cleanup=convert
	 *     wp woo-better-reviews convert --cleanup=purge
	 *
	 * @when after_wp_load
	 */
	function convert( $args = array(), $assoc_args = array() ) {

		// Parse out the associatives.
		$parse_cli_args = wp_parse_args( $assoc_args, array(
			'cleanup' => 'convert',
		));

		// Make sure the cleanup argument is valid.
		if ( empty( $parse_cli_args['cleanup'] ) || ! in_array( esc_attr( $parse_cli_args['cleanup'] ), array( 'convert', 'purge' ) ) ) {
			WP_CLI::error( __( 'An invalid cleanup type was requested. Please try again.', 'woo-better-reviews' ) );
		}

		// Check for the purge and convert flags.
		$maybe_do_type  = 'convert' === esc_attr( $parse_cli_args['cleanup'] ) ? true : false;
		$maybe_do_purge = 'purge' === esc_attr( $parse_cli_args['cleanup'] ) ? true : false;

		// Handle a confirm for the purge flag.
		if ( false !== $maybe_do_purge ) {
			WP_CLI::confirm( __( 'Are you sure you want to purge the existing reviews? THIS CANNOT BE UNDONE.', 'woo-better-reviews' ), $assoc_args );
		}

		// The function itself is a single function, since
		// this is just a wrapper for the conversion function.
		$maybe_convert  = ConvertExisting\attempt_existing_review_conversion( $maybe_do_type, $maybe_do_purge );

		// If we have an actual empty return, display
		// the blank error message and halt.
		if ( empty( $maybe_convert ) ) {
			WP_CLI::error( __( 'The attempted conversion returned an empty result.', 'woo-better-reviews' ) );
		}

		// First look for the 'no-reviews' string. If is
		// exists, display the 'none' message and halt.
		if ( 'no-reviews' === esc_attr( $maybe_convert ) ) {
			WP_CLI::line( __( 'No existing reviews exist to convert.', 'woo-better-reviews' ) );
			WP_CLI::halt( 0 );
		}

		// Handle a WP_Error object return.
		if ( is_wp_error( $maybe_convert ) ) {

			// Get my error code and message.
			$wp_error_code  = $maybe_convert->get_error_code();
			$wp_error_text  = $maybe_convert->get_error_message();

			// Build my return message.
			$return_message = sprintf( __( '%1$s | ERR_CODE %2$s', 'woo-better-reviews' ), esc_attr( $error_text ), esc_attr( $error_code ) );

			// Display the formatted error message.
			WP_CLI::error( esc_attr( $return_message ) );
		}

		// If we have the success flag, return that.
		if ( ! empty( $maybe_convert['success'] ) ) {

			// Determine the message.
			$return_message = ! empty( $maybe_convert['message'] ) ? $maybe_convert['message'] : __( 'The existing reviews have been converted.', 'woo-better-reviews' );

			// Show the result and bail.
			WP_CLI::success( esc_attr( $return_message ) );
			WP_CLI::halt( 0 );
		}

		// Display our "unknown" error.
		WP_CLI::error( __( 'The conversion has completed but there was no results returned. Please check the status manually.', 'woo-better-reviews' ) );
	}

	/**
	 * This is a placeholder function for testing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp woo-better-reviews runtests
	 *
	 * @when after_wp_load
	 */
	function runtests() {
		// This is blank, just here when I need it.
	}

	// End all custom CLI commands.
}
