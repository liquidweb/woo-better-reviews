
/**
 * Now let's get started.
 */
jQuery( document ).ready( function($) {

	// Handle the class if checked or not.
	if ( $( '#product-do-reminders' ).is( ':checked' ) ) {
		$( '.product-reminder-duration-wrap' ).removeClass( 'product-reminder-disabled-hide' );
	} else {
		$( '.product-reminder-duration-wrap' ).addClass( 'product-reminder-disabled-hide' );
	}

	/**
	 * Handle showing the reminder timelength.
	 */
	$( '.product-do-reminders_field' ).on( 'change', '#product-do-reminders', function() {

		// Handle the class if checked or not.
		if ( $( this ).is( ':checked' ) ) {
			$( '.product-reminder-duration-wrap' ).removeClass( 'product-reminder-disabled-hide' );
		} else {
			$( '.product-reminder-duration-wrap' ).addClass( 'product-reminder-disabled-hide' );
		}

		// console.log( $(this).val() );

	});

//********************************************************
// You're still here? It's over. Go home.
//********************************************************
});
