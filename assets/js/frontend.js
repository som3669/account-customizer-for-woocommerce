/* global jQuery, acfw */
( function ( $ ) {
	'use strict';

	$( function () {

		// Group expand/collapse.
		$( '.acfw-menu' ).on( 'click', '.acfw-group-toggle', function () {
			$( this ).closest( '.acfw-type-group' ).toggleClass( 'is-open' );
		} );

		// Mobile nav drawer.
		var $nav = $( '.woocommerce-MyAccount-navigation.acfw-menu' );
		function closeDrawer() {
			$nav.removeClass( 'is-open' );
			$( 'body' ).removeClass( 'acfw-nav-open' );
			$( '.acfw-nav-toggle' ).attr( 'aria-expanded', 'false' );
		}
		$( document ).on( 'click', '.acfw-nav-toggle', function () {
			var open = ! $nav.hasClass( 'is-open' );
			$nav.toggleClass( 'is-open', open );
			$( 'body' ).toggleClass( 'acfw-nav-open', open );
			$( this ).attr( 'aria-expanded', open ? 'true' : 'false' );
		} );
		$( document ).on( 'click', '.acfw-nav-backdrop', closeDrawer );
		$( document ).on( 'keyup', function ( e ) {
			if ( 27 === e.keyCode ) {
				closeDrawer();
			}
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
