<?php
/**
 * Our abstracted data queries.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Queries;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

/**
 * Get all the reviews.
 *
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $date_order   If the date order should be maintained on the field returns.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_reviews_for_admin( $purge = false ) {

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'admin_reviews';

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'content';

		// Set up our query.
		$query_run  = $wpdb->get_results("
			SELECT   *
			FROM     $table_name
			ORDER BY review_date DESC
		" );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Return the entire dataset.
	return $cached_dataset;
}

/**
 * Get all the reviews.
 *
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $date_order   If the date order should be maintained on the field returns.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_all_reviews( $return_type = 'objects', $date_order = true, $purge = false ) {

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'all_reviews';

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'content';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   *
			FROM     $table_name
			WHERE    review_status NOT LIKE '%s'
			ORDER BY review_date DESC
		", esc_attr( 'rejected' ) );

		// Process the query.
		$query_run  = $wpdb->get_results( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'display' :
			return merge_review_object_taxonomies( $cached_dataset );
			break;

		case 'ids' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_id', null );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				sort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'slugs' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_slug', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'titles' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_title', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'content' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_content', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'authors' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'author_id', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'products' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'product_id', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get all the reviews pending approval.
 *
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $date_order   If the date order should be maintained on the field returns.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_pending_reviews( $return_type = 'objects', $date_order = true, $purge = false ) {

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'pending_reviews';

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'content';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   *
			FROM     $table_name
			WHERE    review_status LIKE '%s'
			ORDER BY review_date DESC
		", esc_attr( 'pending' ) );

		// Process the query.
		$query_run  = $wpdb->get_results( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'display' :
			return merge_review_object_taxonomies( $cached_dataset );
			break;

		case 'ids' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_id', null );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				sort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'slugs' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_slug', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'titles' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_title', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'content' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_content', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'authors' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'author_id', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'products' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'product_id', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get all the reviews for a given product ID.
 *
 * @param  integer $product_id   Which product ID we are looking up.
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $date_order   If the date order should be maintained on the field returns.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_reviews_for_product( $product_id = 0, $return_type = 'objects', $date_order = true, $purge = false ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return new WP_Error( 'missing_product_id', __( 'A product ID is required.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'reviews_for_product_' . absint( $product_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'content';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   *
			FROM     $table_name
			WHERE    product_id = '%d'
			AND      review_status NOT LIKE '%s'
			ORDER BY review_date DESC
		", absint( $product_id ), esc_attr( 'rejected' ) );

		// Process the query.
		$query_run  = $wpdb->get_results( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'display' :
			return merge_review_object_taxonomies( $cached_dataset );
			break;

		case 'ids' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_id', null );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				sort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'slugs' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_slug', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'titles' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_title', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'content' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_content', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'authors' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'author_id', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get all the approved reviews for a product.
 *
 * @param  integer $product_id   Which product ID we are looking up.
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $date_order   If the date order should be maintained on the field returns.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_approved_reviews_for_product( $product_id = 0, $return_type = 'objects', $date_order = true, $purge = false ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return new WP_Error( 'missing_product_id', __( 'A product ID is required.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'approved_reviews_for_product_' . absint( $product_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'content';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   *
			FROM     $table_name
			WHERE    product_id = '%d'
			AND      review_status LIKE '%s'
			ORDER BY review_date DESC
		", absint( $product_id ), esc_attr( 'approved' ) );

		// Process the query.
		$query_run  = $wpdb->get_results( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'display' :
			return merge_review_object_taxonomies( $cached_dataset );
			break;

		case 'ids' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_id', null );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				sort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'slugs' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_slug', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'titles' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_title', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'content' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_content', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'authors' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'author_id', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'total' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'rating_total_score', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'products' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'product_id', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get all the reviews for a given author ID.
 *
 * @param  integer $author_id    Which author ID we are looking up.
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $date_order   If the date order should be maintained on the field returns.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_reviews_for_author( $author_id = 0, $return_type = 'objects', $date_order = true, $purge = false ) {

	// Bail without an author ID.
	if ( empty( $author_id ) ) {
		return new WP_Error( 'author_id', __( 'An author ID is required.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'reviews_for_author_' . absint( $author_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'content';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   *
			FROM     $table_name
			WHERE    author_id = '%d'
			AND      review_status NOT LIKE '%s'
			ORDER BY review_date DESC
		", absint( $author_id ), esc_attr( 'rejected' ) );

		// Process the query.
		$query_run  = $wpdb->get_results( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'display' :
			return merge_review_object_taxonomies( $cached_dataset );
			break;

		case 'ids' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_id', null );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				sort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'slugs' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_slug', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'titles' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_title', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'content' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_content', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'products' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'product_id', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get some recent reviews for a given product ID.
 *
 * @param  integer $product_id   Which product ID we are looking up.
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $date_order   If the date order should be maintained on the field returns.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_recent_reviews_for_product( $product_id = 0, $return_type = 'objects', $date_order = true, $purge = false ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return new WP_Error( 'missing_product_id', __( 'A product ID is required.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'recent_reviews_for_product_' . absint( $product_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'content';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   *
			FROM     $table_name
			WHERE    product_id = '%d'
			AND      review_status = '%s'
			ORDER BY review_date DESC
			LIMIT    5
		", absint( $product_id ), esc_attr( 'approved' ) );

		// Process the query.
		$query_run  = $wpdb->get_results( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'display' :
			return merge_review_object_taxonomies( $cached_dataset );
			break;

		case 'ids' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_id', null );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				sort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'slugs' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_slug', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'titles' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_title', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'content' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_content', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'authors' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'author_id', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get all the verified reviews.
 *
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $date_order   If the date order should be maintained on the field returns.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_verified_reviews( $return_type = 'objects', $date_order = true, $purge = false ) {

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'verifed_reviews';

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'content';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   *
			FROM     $table_name
			WHERE    is_verified = '%d'
			AND      review_status NOT LIKE '%s'
			ORDER BY review_date DESC
		", absint( 1 ), esc_attr( 'rejected' ) );

		// Process the query.
		$query_run  = $wpdb->get_results( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'display' :
			return merge_review_object_taxonomies( $cached_dataset );
			break;

		case 'ids' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_id', null );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				sort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'slugs' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_slug', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'titles' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_title', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'content' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_content', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'authors' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'author_id', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'products' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'product_id', 'review_id' );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				ksort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get one review.
 *
 * @param  integer $review_id    The review ID we are looking up.
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_single_review( $review_id = 0, $return_type = 'objects', $purge = false ) {

	// Bail without a review ID.
	if ( empty( $review_id ) ) {
		return new WP_Error( 'missing_review_id', __( 'A review ID is required.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'single_review_' . absint( $review_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'content';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   *
			FROM     $table_name
			WHERE    review_id = '%d'
		", absint( $review_id ) );

		// Process the query.
		$query_run  = $wpdb->get_results( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) || empty( $query_run[0] ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run[0], HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run[0];
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'display' :

			// Set the weird array for the merge.
			$review_list[ $review_id ] = $cached_dataset;

			return merge_review_object_taxonomies( $review_list );
			break;

		case 'product' :
			return $cached_dataset->product_id;
			break;

		case 'status' :
			return $cached_dataset->review_status;
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Check for reviews that match search criteria.
 *
 * @param  integer $charstcs_id  Which author ID we are looking up.
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", "display", or single fields.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_reviews_for_sorting( $product_id = 0, $charstcs_id = 0, $charstcs_value = '', $return_type = 'objects', $purge = false ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return new WP_Error( 'missing_product_id', __( 'A product ID is required.', 'woo-better-reviews' ) );
	}

	// Bail without a characteristic ID.
	if ( empty( $charstcs_id ) ) {
		return new WP_Error( 'missing_charstcs_id', __( 'An characteristic ID is required.', 'woo-better-reviews' ) );
	}

	// Bail without a characteristic value.
	if ( empty( $charstcs_value ) ) {
		return new WP_Error( 'missing_charstcs_value', __( 'An characteristic value is required.', 'woo-better-reviews' ) );
	}

	// Call the global database.
	global $wpdb;

	// Set our table name.
	$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'authormeta';

	// Set up our query.
	$query_args = $wpdb->prepare("
		SELECT   review_id
		FROM     $table_name
		WHERE    product_id = '%d'
		AND      charstcs_id = '%d'
		AND      charstcs_value = '%s'
	", absint( $product_id ), absint( $charstcs_id ), esc_attr( $charstcs_value ) );

	// Process the query.
	$query_run  = $wpdb->get_results( $query_args );

	// Bail without any results.
	if ( empty( $query_run ) ) {
		return false;
	}

	// Set my list.
	$query_list = wp_list_pluck( $query_run, 'review_id', null );

	// Bail without any reviews.
	return ! empty( $query_list ) ? $query_list : false;
}

/**
 * Get a batch of reviews from a sort or filter.
 *
 * @param  array $review_ids  The IDs we want.
 *
 * @return mixed
 */
function get_review_batch( $review_ids = array() ) {

	// If we have a 'none', return false right away.
	if ( 'none' === sanitize_text_field( $review_ids ) ) {
		return false;
	}

	// Bail without review IDs.
	if ( empty( $review_ids ) ) {
		return new WP_Error( 'missing_review_ids', __( 'Review IDs are required for batch.', 'woo-better-reviews' ) );
	}

	// Set an empty return.
	$review_list    = array();

	// Now loop and fetch.
	foreach ( $review_ids as $review_id ) {
		$review_list[ $review_id ] = get_single_review( $review_id );
	}

	// Return my list with formatting.
	return merge_review_object_taxonomies( $review_list );
}

/**
 * Get all the ratings for a review ID.
 *
 * @param  integer $review_id    The review ID we want scores from.
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", "display", or single fields.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_ratings_for_review_attribute( $review_id = 0, $attribute_id = 0, $return_type = 'objects', $purge = false ) {

	// Bail without a review ID.
	if ( empty( $review_id ) ) {
		return new WP_Error( 'missing_review_id', __( 'A review ID is required.', 'woo-better-reviews' ) );
	}

	// Call the global database.
	global $wpdb;

	// Set our table name.
	$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'ratings';

	// Set up our query.
	$query_args = $wpdb->prepare("
		SELECT   rating_score
		FROM     $table_name
		WHERE    review_id = '%d'
		AND      attribute_id = '%d'
	", absint( $review_id ), absint( $attribute_id ) );

	// Process the query.
	$query_run  = $wpdb->get_row( $query_args );

	// Bail without any results.
	if ( empty( $query_run ) ) {
		return false;
	}

	// Set my list.
	$query_list = wp_list_pluck( $query_run, 'rating_score', 'attribute_id' );

	// Bail without any scoring data.
	return ! empty( $query_list ) ? $query_list : false;
}

/**
 * Get just the review count for a given product ID.
 *
 * @param  integer $product_id  Which product ID we are looking up.
 * @param  boolean $purge       Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_review_count_for_product( $product_id = 0, $purge = false ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return new WP_Error( 'missing_product_id', __( 'A product ID is required.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'review_count_product' . absint( $product_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the review count from the cache.
	$cached_count   = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_count ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'content';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   COUNT(*)
			FROM     $table_name
			WHERE    product_id = '%d'
			AND      review_status NOT LIKE '%s'
		", absint( $product_id ), esc_attr( 'rejected' ) );

		// Process the query.
		$query_run  = $wpdb->get_var( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, absint( $query_run ), HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_count = absint( $query_run );
	}

	// And return the overall count.
	return $cached_count;
}

/**
 * Get just the legacy review count for a given product ID.
 *
 * @param  integer $product_id  Which product ID we are looking up.
 * @param  boolean $purge       Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_legacy_review_counts( $purge = false ) {

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'legacy_review_counts';

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the review count from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . 'postmeta';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   post_id, meta_value
			FROM     $table_name
			WHERE    meta_key = '%s'
		", esc_attr( '_wc_review_count' ) );

		// Process the query.
		$query_run  = $wpdb->get_results( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set the list we want.
		$query_list = wp_list_pluck( $query_run, 'meta_value', 'post_id' );

		// Set our transient with our data.
		set_transient( $ky, $query_list, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_list;
	}

	// And return the overall count.
	return $cached_dataset;
}

/**
 * Get all the attributes.
 *
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_all_attributes( $return_type = 'objects', $purge = false ) {

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'all_attributes';

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'attributes';

		// Set up our query.
		$query_run  = $wpdb->get_results("
			SELECT   *
			FROM     $table_name
			ORDER BY attribute_name ASC
		" );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'indexed' :

			// First get the IDs.
			$id_index   = wp_list_pluck( $cached_dataset, 'attribute_id', null );

			// Return it with our IDs as the index.
			return array_combine( $id_index, $cached_dataset );
			break;

		case 'display' :
			return Utilities\format_attribute_display_data( $cached_dataset );
			break;

		case 'ids' :

			// Set and return my query list.
			return wp_list_pluck( $cached_dataset, 'attribute_id', null );
			break;

		case 'slugs' :

			// Set and return my query list.
			return wp_list_pluck( $cached_dataset, 'attribute_slug', 'attribute_id' );
			break;

		case 'titles' :
		case 'names' :

			// Set and return my query list.
			return wp_list_pluck( $cached_dataset, 'attribute_name', 'attribute_id' );
			break;

		case 'descriptions' :

			// Set and return my query list.
			return wp_list_pluck( $cached_dataset, 'attribute_desc', 'attribute_id' );
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get just the attributes assigned to the product.
 *
 * @param  integer $product_id   Which product ID we are looking up.
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", "display", or single fields.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_attributes_for_product( $product_id = 0, $return_type = 'objects', $purge = false ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return new WP_Error( 'missing_product_id', __( 'A product ID is required.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'attributes_product' . absint( $product_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Check for the stored product meta.
		$maybe_attributes   = Helpers\get_selected_product_attributes( $product_id );

		// Bail without any selected attributes.
		if ( empty( $maybe_attributes ) ) {
			return false;
		}

		// Get all my attributes.
		$all_attributes = get_all_attributes( 'indexed' );

		// Set my empty.
		$query_list = array();

		// Loop the attribute IDs.
		foreach ( $maybe_attributes as $attribute_id ) {

			// Skip the empty data.
			if ( empty( $all_attributes[ $attribute_id ] ) ) {
				continue;
			}

			// Add the data to the list.
			$query_list[] = $all_attributes[ $attribute_id ];
		}

		// Set our transient with our data.
		set_transient( $ky, $query_list, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_list;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'display' :
			return Utilities\format_attribute_display_data( $cached_dataset );
			break;

		case 'ids' :

			// Return my list, plucked.
			return wp_list_pluck( $cached_dataset, 'attribute_id', null );
			break;

		case 'names' :

			// Return my list, plucked.
			return wp_list_pluck( $cached_dataset, 'attribute_name', 'attribute_id' );
			break;

		case 'slugs' :

			// Return my list, plucked.
			return wp_list_pluck( $cached_dataset, 'attribute_slug', 'attribute_id' );
			break;

		case 'descriptions' :

			// Return my list, plucked.
			return wp_list_pluck( $cached_dataset, 'attribute_desc', 'attribute_id' );
			break;

		case 'labels' :

			// Get each set of labels.
			$min_labels = wp_list_pluck( $cached_dataset, 'min_label' );
			$max_labels = wp_list_pluck( $cached_dataset, 'max_label' );

			// Return my list, plucked.
			return array_merge( $min_labels, $max_labels );
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get the data for a single attribute.
 *
 * @param  integer $attribute_id  The ID we are checking for.
 * @param  string  $return_type   What type of return we want. Accepts various fields.
 * @param  boolean $purge         Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_single_attribute( $attribute_id = 0, $return_type = 'dataset', $purge = false ) {

	// Make sure we have an attribute ID.
	if ( empty( $attribute_id ) ) {
		return new WP_Error( 'missing_attribute_id', __( 'The required attribute ID is missing.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'single_attribute_' . absint( $attribute_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'attributes';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   *
			FROM     $table_name
			WHERE    attribute_id = '%d'
		", absint( $attribute_id ) );

		// Process the query.
		$query_run  = $wpdb->get_row( $query_args, ARRAY_A );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'dataset' :
			return $cached_dataset;
			break;

		case 'name' :

			// Return the single bit.
			return $cached_dataset['attribute_name'];
			break;

		case 'slug' :

			// Return the single bit.
			return $cached_dataset['attribute_slug'];
			break;

		case 'description' :

			// Return the single bit.
			return $cached_dataset['attribute_desc'];
			break;

		case 'labels' :

			// Get each set of labels.
			$min_label  = $cached_dataset['min_label'];
			$max_label  = $cached_dataset['max_label'];

			// Get my label set as an array.
			$label_arr  = array( 'min' => $min_label, 'max' => $max_label );

			// Return my array, cleaned out.
			return array_filter( $label_arr );
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get all the charstcs.
 *
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_all_charstcs( $return_type = 'objects', $purge = false ) {

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'all_charstcs';

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'charstcs';

		// Set up our query.
		$query_run  = $wpdb->get_results("
			SELECT   *
			FROM     $table_name
			ORDER BY charstcs_name ASC
		" );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'display' :
			return Utilities\format_charstcs_display_data( $cached_dataset );
			break;

		case 'ids' :

			// Set and return my query list.
			return wp_list_pluck( $cached_dataset, 'charstcs_id', null );
			break;

		case 'slugs' :

			// Set and return my query list.
			return wp_list_pluck( $cached_dataset, 'charstcs_slug', 'charstcs_id' );
			break;

		case 'titles' :

			// Set and return my query list.
			return wp_list_pluck( $cached_dataset, 'charstcs_name', 'charstcs_id' );
			break;

		case 'descriptions' :

			// Set and return my query list.
			return wp_list_pluck( $cached_dataset, 'charstcs_desc', 'charstcs_id' );
			break;

		case 'values' :

			// Parse out the values.
			$plucked_values = wp_list_pluck( $cached_dataset, 'charstcs_values', 'charstcs_id' );

			// Set and return my query list.
			return array_map( 'maybe_unserialize', $plucked_values );
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get just the charstcs assigned to the author.
 *
 * @param  integer $author_id    Which author ID we are looking up.
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", "display", or single fields.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_charstcs_for_author( $author_id = 0, $return_type = 'objects', $purge = false ) {

	// Bail without a author ID.
	if ( empty( $author_id ) ) {
		return new WP_Error( 'missing_author_id', __( 'An author ID is required.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'charstcs_author' . absint( $author_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'authormeta';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   charstcs_id
			FROM     $table_name
			WHERE    author_id = '%d'
		", absint( $author_id ) );

		// Process the query.
		$query_run  = $wpdb->get_results( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set my empty.
		$query_list = array();

		// Loop the attribute IDs.
		foreach ( $query_run as $single_arg ) {

			// Pull out my ID.
			$charstcs_id    = absint( $single_arg->charstcs_id );

			// Get the single attribute data.
			$charstcs_data  = get_single_charstcs( $charstcs_id );

			// Skip the empty data.
			if ( empty( $charstcs_data ) ) {
				continue;
			}

			// Add the data to the list.
			$query_list[] = $charstcs_data;
		}

		// Set our transient with our data.
		set_transient( $ky, absint( $query_list ), HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_list;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'counts' :
			return count( $cached_dataset );
			break;

		case 'objects' :
			return $cached_dataset;
			break;

		case 'ids' :

			// Return my list, plucked.
			return wp_list_pluck( $cached_dataset, 'charstcs_id', null );
			break;

		case 'values' :

			// Return my list, plucked.
			return wp_list_pluck( $cached_dataset, 'charstcs_value', 'charstcs_id' );
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get the data for a single charstc.
 *
 * @param  integer $charstc_id  The ID we are checking for.
 * @param  boolean $purge       Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_single_charstcs( $charstcs_id = 0, $purge = false ) {

	// Make sure we have an charstc ID.
	if ( empty( $charstcs_id ) ) {
		return new WP_Error( 'missing_charstcs_id', __( 'The required characteristic ID is missing.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'single_charstcs_' . absint( $charstcs_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'charstcs';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   *
			FROM     $table_name
			WHERE    charstcs_id = '%d'
		", absint( $charstcs_id ) );

		// Process the query.
		$query_run  = $wpdb->get_row( $query_args, ARRAY_A );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_run, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// Return the dataset.
	return $cached_dataset;
}

/**
 * Get all the order data for pending reminders.
 *
 * @param  string  $return_type  What type of return we want. Accepts "filtered", "indexed", "counts", "ids", or "dataset".
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_reminder_order_data( $return_type = 'filtered', $purge = false ) {

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'reminder_orders';

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . 'postmeta';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   post_id
			FROM     $table_name
			WHERE    meta_key = '%s'
			AND      meta_value = '%s'
		", esc_attr( Core\META_PREFIX . 'review_reminder_status' ), esc_attr( 'pending' ) );

		// Process the query.
		$query_run  = $wpdb->get_results( $query_args );

		// Bail without any reviews.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set the list we want.
		$order_ids  = wp_list_pluck( $query_run, 'post_id' );

		// Set an empty.
		$query_list = array();

		// Now add the dataset, whether we want it or not.
		foreach ( $order_ids as $order_id ) {

			// Fetch the postmeta.
			$order_meta = get_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_data', true );

			// Skip if no meta exists.
			if ( empty( $order_meta ) ) {
				continue;
			}

			// Now set the key / value in the array.
			$query_list[ $order_id ] = array(
				'order_id' => $order_id,
				'customer' => Helpers\get_potential_customer_data( 0, $order_id ),
				'products' => wp_list_pluck( $order_meta, 'timestamp', 'product_id' ),
			);
		}

		// Bail without any data.
		if ( empty( $query_list ) ) {
			return false;
		}

		// Set our transient with our data.
		set_transient( $ky, $query_list, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_list;
	}

	// Now switch between my return types.
	switch ( sanitize_text_field( $return_type ) ) {

		case 'filtered' :
			return Utilities\filter_reminder_data( $cached_dataset );
			break;

		case 'indexed' :
			return $cached_dataset;
			break;

		case 'dataset' :
			return array_values( $cached_dataset );
			break;

		case 'ids' :
			return wp_list_pluck( $cached_dataset, 'order-id', null );
			break;

		case 'counts' :
			return count( $cached_dataset );
			break;

		// No more case breaks, no more return types.
	}

	// No reason we should get down this far but here we go.
	return false;
}

/**
 * Get the data for the aggregate structured schema data.
 *
 * @param  integer $product_id  Which product ID we are looking up.
 * @param  boolean $purge       Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_schema_data_for_product( $product_id = 0, $purge = false ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return new WP_Error( 'missing_product_id', __( 'A product ID is required.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'schema_product' . absint( $product_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the reviews from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Grab the entire post object.
		$product_object = get_post( $product_id );

		// Start pulling out various pieces we need.
		$product_name   = $product_object->post_title;
		$product_desc   = $product_object->post_excerpt;
		$product_image  = get_the_post_thumbnail_url( $product_id, 'medium' );

		// Pull out the averages and total review count.
		$average_score  = get_post_meta( $product_id, Core\META_PREFIX . 'average_rating', true );
		$review_count   = Helpers\get_admin_review_count( $product_id, false );

		// Set up the schema arguments.
		$schema_args    = array(
			'@context'        => 'http://schema.org/',
			'@type'           => 'Product',
			'name'            => esc_attr( $product_name ),
			'image'           => esc_url( $product_image ),
			'description'     => wp_strip_all_tags( $product_desc, true ),
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => esc_attr( $average_score ),
				'bestRating'  => '7',
				'worstRating' => '1',
				'ratingCount' => esc_attr( $review_count ),
			),
		);

		// Set our transient with our data.
		set_transient( $ky, $schema_args, HOUR_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $schema_args;
	}

	// Return the raw dataset, we will format it later.
	return $cached_dataset;
}

/**
 * Take the review object array and merge the taxonomies.
 *
 * @param  array $reviews  The review objects from the query.
 *
 * @return array
 */
function merge_review_object_taxonomies( $reviews ) {

	// Bail with no reviews.
	if ( empty( $reviews ) ) {
		return false;
	}

	// Set the initial empty.
	$merged = array();

	// Now loop and do the things.
	foreach ( (array) $reviews as $object ) {

		// Set the ID.
		$id = $object->review_id;

		// Cast it as an array.
		$review = (array) $object;

		// Pass it into the content setup.
		$review = Utilities\format_review_content_data( $review );

		// Pass it into the scoring setup.
		$review = Utilities\format_review_scoring_data( $review );

		// Pass it into the author setup.
		$review = Utilities\format_review_author_charstcs( $review );

		// And now merge the data.
		$merged[ $id ] = $review;

		// Should be nothing left inside the loop.
	}

	// Return our large merged data.
	return $merged;
}

/**
 * Take a big group of product IDs and merge into one.
 *
 * @param  array  $dataset  The dataset I wanna flatten.
 *
 * @return array
 */
function merge_order_product_ids( $dataset = array() ){

	// Bail without a dataset.
	if ( empty( $dataset ) ) {
		return false;
	}

	// Set my flat empty.
	$set_ids    = array();

	// Loop the
	foreach( new \RecursiveIteratorIterator( new \RecursiveArrayIterator( $dataset ) ) as $value ) {
		$set_ids[]  = $value;
	}

	// Bail without a flat array.
	if ( empty( $set_ids ) ) {
		return false;
	}

	// Set my array up unique.
	$unique_ids = array_unique( $set_ids, SORT_NUMERIC );

	// Return the flattened array.
	return array_values( $unique_ids );
}
