/* global jQuery, acfw */
( function ( $ ) {
	'use strict';

	$( function () {

		// Group expand/collapse.
		$( '.acfw-menu' ).on( 'click', '.acfw-group-toggle', function () {
			$( this ).closest( '.acfw-type-group' ).toggleClass( 'is-open' );
		} );

		// AJAX navigation between endpoints.
		if ( ! acfw || ! acfw.ajaxNavigation ) {
			return;
		}

		var $content = $( acfw.contentSelector );
		if ( ! $content.length ) {
			return;
		}

		$( '.acfw-menu' ).on( 'click', '.acfw-type-endpoint > a', function ( e ) {
			var url = $( this ).attr( 'href' );
			if ( ! url || url.indexOf( '#' ) === 0 ) {
				return;
			}
			e.preventDefault();

			var $item = $( this ).closest( '.acfw-menu-item' );
			$content.addClass( 'acfw-loading' );

			$.get( url, function ( html ) {
				var $fetched = $( html ).find( acfw.contentSelector ).first();
				if ( $fetched.length ) {
					$content.html( $fetched.html() );
				}
				$content.removeClass( 'acfw-loading' );

				$item.addClass( 'is-active' ).siblings().removeClass( 'is-active' );
				if ( window.history && window.history.pushState ) {
					window.history.pushState( null, '', url );
				}
			} ).fail( function () {
				window.location.href = url;
			} );
		} );
	} );

} )( jQuery );
