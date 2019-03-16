<?php
/**
 * This is for testing and won't last.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Testing;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'admin_init', __NAMESPACE__ . '\test_and_do_database', 11 );

/**
 * Run our insert check.
 * @return [type] [description]
 */
function test_and_do_database() {

	// Look for the trigger key first.
	if ( empty( $_GET['do-db'] ) ) {
		return;
	}

	// Now make sure I have a type.
	if ( empty( $_GET['db-action'] ) ) {
		die( 'set an action' );
	}

	// Handle the database insert based on the provided name.
	switch ( sanitize_text_field( $_GET['db-action'] ) ) {

		case 'insert' :
			test_and_do_insert();
			break;

		case 'update' :
			test_and_do_update();
			break;

		case 'delete' :
			test_and_do_delete();
			break;

		case 'products' :
			test_and_do_product();
			break;

		// No more case breaks, no more tables.
	}

}

function test_and_do_product() {

}

/**
 * [test_and_do_insert description]
 * @return [type] [description]
 */
function test_and_do_insert() {

	// set the title because we use it twice.
	$review_title   = lcl_better_rvs_random_title();

	// Set up the insert data array.
	$insert_args    = array(
		'author_id'      => lcl_better_rvs_random_customer_id( true ),
		'product_id'     => lcl_better_rvs_random_product_id( true ),
		'review_date'    => lcl_better_rvs_random_date( true ),
		'review_title'   => lcl_better_rvs_random_title(),
		'review_slug'    => sanitize_title_with_dashes( $review_title, null, 'save' ),
		'review_summary' => lcl_better_rvs_random_summary(),
		'review_content' => lcl_better_rvs_random_content(),
		'review_status'  => lcl_better_rvs_random_status(),
		'is_verified'    => rand( 0, 1 ),
	);
	// preprint( $insert_args, true );

	$run_insert = Database\insert( 'content', $insert_args );

	preprint( $run_insert, true );
}

/**
 * update a thing.
 */
function test_and_do_update() {

	// Set my update ID.
	$update_id  = 20;

	$update_title = 'I Once Saw A Man Eat A Bird';

	// Set my update args.
	$update_args = array(
		'review_title'   => $update_title,
		'review_slug'    => sanitize_title_with_dashes( $update_title, null, 'save' ),
	);
	// preprint( $update_args, true );

	$run_update = Database\update( 'content', $update_id, $update_args );

	preprint( $run_update, true );
}

/**
 * delete a thing.
 */
function test_and_do_delete() {

	$run_delete = Database\delete( 'authormeta', 1111 );

	preprint( $run_delete, true );
}

/*

 */
