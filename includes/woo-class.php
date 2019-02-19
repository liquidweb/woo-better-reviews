<?php
/**
 * This is an attempt to override the product class with our
 * own review count. Seems heavy handed but ¯\_(ツ)_/¯
 *
 * @package WooBetterReviews
 */

/**
 * Start our engines.
 */
//add_action( 'plugins_loaded', 'wbr_override_woo_product_data_class' );

/**
 * Extend the Woo product data class.
 *
 * @return void
 */
function wbr_override_woo_product_data_class() {

	// This is my class which is gonna extend stuff.
	class WBR_Review_Count extends WC_Product {

		public function __construct( $product ) {
			parent::__construct( $product );
		}

		/**
		 * Set review count. Read only.
		 *
		 * @param int $count Product review count.
		 */
		/*
		public function set_review_count( $count ) {
			$this->set_prop( 'review_count', absint( 888 ) );
		}
		*/

		/**
		 * Get review count.
		 *
		 * @param  string $context What the value is for. Valid values are view and edit.
		 * @return int
		 */
		/*
		public function get_review_count( $context = 'view' ) {
			return 8888;
		}
		*/
	}

	// No other items inside this init.
}
