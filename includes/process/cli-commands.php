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
	 * This is a placeholder function for testing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp woo-product-interest runtests
	 *
	 * @when after_wp_load
	 */
	function runtests() {
		// This is blank, just here when I need it.
	}

	// End all custom CLI commands.
}
