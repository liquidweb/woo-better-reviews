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

		case 'summaries' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_summary', 'review_id' );

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

		case 'summaries' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_summary', 'review_id' );

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

		case 'summaries' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_summary', 'review_id' );

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
 * Get all the consolidated reviews.
 *
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $date_order   If the date order should be maintained on the field returns.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_consolidated_reviews( $return_type = 'objects', $date_order = true, $purge = false ) {

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'cnsldtd_reviews';

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
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'consolidated';

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

		case 'summaries' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_summary', 'review_id' );

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
 * Get all the consolidated reviews for a given product ID.
 *
 * @param  integer $product_id   Which product ID we are looking up.
 * @param  string  $return_type  What type of return we want. Accepts "counts", "objects", or fields.
 * @param  boolean $date_order   If the date order should be maintained on the field returns.
 * @param  boolean $purge        Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_consolidated_reviews_for_product( $product_id = 0, $return_type = 'objects', $date_order = true, $purge = false ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return new WP_Error( 'missing_product_id', __( 'A product ID is required.', 'woo-better-reviews' ) );
	}

	// Set the key to use in our transient.
	$ky = Core\HOOK_PREFIX . 'cnsldtd_reviews_for_product_' . absint( $product_id );

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
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'consolidated';

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
			return Utilities\merge_review_object_taxonomies( $cached_dataset );
			break;

		case 'ids' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'con_id', null );

			// Sort my list assuming we didn't want date order.
			if ( ! $date_order ) {
				sort( $query_list );
			}

			// Return my list, sorted.
			return $query_list;
			break;

		case 'review-ids' :

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

		case 'summaries' :

			// Set my query list.
			$query_list = wp_list_pluck( $cached_dataset, 'review_summary', 'review_id' );

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
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'consolidated';

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

		case 'ids' :

			// Set and return my query list.
			return wp_list_pluck( $cached_dataset, 'attribute_id', null );
			break;

		case 'slugs' :

			// Set and return my query list.
			return wp_list_pluck( $cached_dataset, 'attribute_slug', 'attribute_id' );
			break;

		case 'titles' :

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

		// Call the global database.
		global $wpdb;

		// Set our table name.
		$table_name = $wpdb->prefix . Core\TABLE_PREFIX . 'productsetup';

		// Set up our query.
		$query_args = $wpdb->prepare("
			SELECT   attribute_id
			FROM     $table_name
			WHERE    product_id = '%d'
		", absint( $product_id ) );

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
			$attribute_id   = absint( $single_arg->attribute_id );

			// Get the single attribute data.
			$attribute_data = get_single_attribute( $attribute_id );

			// Skip the empty data.
			if ( empty( $attribute_data ) ) {
				continue;
			}

			// Add the data to the list.
			$query_list[] = $attribute_data;
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
 * @param  boolean $purge         Optional to purge the cache'd version before looking up.
 *
 * @return mixed
 */
function get_single_attribute( $attribute_id = 0, $purge = false ) {

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

	// Return the dataset.
	return $cached_dataset;
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
