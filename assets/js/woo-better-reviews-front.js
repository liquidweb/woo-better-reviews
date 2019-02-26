
/**
 * Scroll down to our review form.
 */
function scrollToReviewForm() {

	jQuery( 'html,body' ).animate({
		scrollTop: jQuery( '.woo-better-reviews-form-block' ).offset().top - 200
	}, 500 );

	// And just return false.
	return false;
}

/**
 * Now let's get started.
 */
jQuery( document ).ready( function($) {

	/**
	 * Handle the notice dismissal.
	 */
	$( '.woo-better-reviews-list-title-wrapper' ).on( 'click', '.woo-better-reviews-template-title-form-link', function( event ) {

		// Stop the actual click.
		event.preventDefault();

		// And scroll to it.
		scrollToReviewForm();
	});

//********************************************************
// You're still here? It's over. Go home.
//********************************************************
});
