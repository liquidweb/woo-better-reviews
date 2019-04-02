
/**
 * Scroll down to our review form.
 */
function scrollToPageClass( pageClass ) {

	jQuery( 'html,body' ).animate({
		scrollTop: jQuery( pageClass ).offset().top - 200
	}, 500 );

	// And just return false.
	return false;
}

/**
 * Now let's get started.
 */
jQuery( document ).ready( function($) {

	/**
	 * Handle the click for leaving a review.
	 */
	$( '.woo-better-reviews-list-title-wrapper' ).on( 'click', '.woo-better-reviews-template-title-form-link', function( event ) {

		// Stop the actual click.
		event.preventDefault();

		// And scroll to it.
		scrollToPageClass( '.woo-better-reviews-form-block' );
	});

//********************************************************
// You're still here? It's over. Go home.
//********************************************************
});
