/* global jQuery, acfw */
( function ( $ ) {
	'use strict';

	$( function () {

		// Group expand/collapse.
		$( '.acfw-menu' ).on( 'click', '.acfw-group-toggle', function () {
			$( this ).closest( '.acfw-type-group' ).toggleClass( 'is-open' );
		} );

		// Menu search filter.
		$( '.acfw-menu' ).on( 'input', '.acfw-menu-search', function () {
			var q = ( this.value || '' ).toLowerCase().trim();
			$( this ).closest( '.acfw-menu' ).find( '.acfw-menu-item' ).each( function () {
				var text = $( this ).find( '.acfw-label, .acfw-group-toggle' ).first().text().toLowerCase();
				$( this ).toggle( '' === q || text.indexOf( q ) !== -1 );
			} );
		} );

		// Pin favorites to top.
		$( '.acfw-menu.acfw-pinnable' ).each( function () {
			var pins = [];
			try { pins = JSON.parse( window.localStorage.getItem( 'acfwPins' ) || '[]' ); } catch ( e ) {}
			var $list = $( this ).find( '#acfw-menu-list' );
			pins.slice().reverse().forEach( function ( k ) {
				var $item = $list.children( '.acfw-menu-item' ).filter( function () {
					return String( $( this ).find( '.acfw-pin' ).data( 'key' ) ) === String( k );
				} );
				if ( $item.length ) {
					$item.addClass( 'is-pinned' ).prependTo( $list );
					$item.find( '.acfw-pin .dashicons' ).removeClass( 'dashicons-star-empty' ).addClass( 'dashicons-star-filled' );
				}
			} );
		} );
		$( document ).on( 'click', '.acfw-pin', function ( e ) {
			e.preventDefault();
			var k = String( $( this ).data( 'key' ) );
			var pins = [];
			try { pins = JSON.parse( window.localStorage.getItem( 'acfwPins' ) || '[]' ); } catch ( e2 ) {}
			var i = pins.indexOf( k );
			if ( -1 === i ) { pins.push( k ); } else { pins.splice( i, 1 ); }
			try { window.localStorage.setItem( 'acfwPins', JSON.stringify( pins ) ); } catch ( e3 ) {}
			window.location.reload();
		} );

		// Collapsible icon rail.
		$( '.acfw-menu.acfw-collapsible' ).each( function () {
			try {
				if ( '1' === window.localStorage.getItem( 'acfwCollapsed' ) ) {
					$( this ).addClass( 'is-collapsed' );
				}
			} catch ( e ) {}
		} );
		$( document ).on( 'click', '.acfw-collapse-toggle', function () {
			var collapsed = $( this ).closest( '.acfw-menu' ).toggleClass( 'is-collapsed' ).hasClass( 'is-collapsed' );
			try {
				window.localStorage.setItem( 'acfwCollapsed', collapsed ? '1' : '0' );
			} catch ( e ) {}
		} );

		// Confirm before logout.
		if ( acfw && acfw.logoutConfirm ) {
			$( '.acfw-menu' ).on( 'click', 'a[href*="customer-logout"]', function ( e ) {
				if ( ! window.confirm( acfw.logoutMsg ) ) {
					e.preventDefault();
				}
			} );
		}

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
