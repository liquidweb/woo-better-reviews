<?php
/**
 * Our helper functions to use across the plugin.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Helpers;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;

/**
 * Set the key / value pair for all our custom tables.
 *
 * @param  boolean $keys  Whether to return just the keys.
 *
 * @return array
 */
function get_table_names( $keys = false ) {

	// Set up our array.
	$tables = array(
		'content'       => 'Review Content',
		'authors'       => 'Review Authors',
		'attributes'    => 'Attributes',
		'metadata'      => 'Review Metadata',
		'relationships' => 'Attribute Relationships',
	);

	// Either return the full array, or just the keys if requested.
	return ! $keys ? $tables : array_keys( $tables );
}
