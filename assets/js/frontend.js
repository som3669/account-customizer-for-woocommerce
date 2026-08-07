/* global jQuery, acfw */
( function ( $ ) {
	'use strict';

	$( function () {

		// Group expand/collapse ( click the title again to close ).
		$( '.acfw-menu' ).on( 'click', '.acfw-group-toggle', function () {
			$( this ).closest( '.acfw-type-group' ).toggleClass( 'is-open' );
		} );

		// Click outside closes open group dropdowns in the Tabs layout.
		$( document ).on( 'click', function ( e ) {
			if ( ! $( e.target ).closest( '.acfw-type-group' ).length ) {
				$( '.acfw-menu.layout-tabs .acfw-type-group.is-open' ).removeClass( 'is-open' );
			}
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

		// Customer avatar upload.
		$( '.acfw-avatar-uploadable' ).each( function () {
			var $block   = $( this );
			var $file    = $block.find( '.acfw-avatar-file' );
			var $img     = $block.find( '.acfw-avatar-img' );
			var $remove  = $block.find( '.acfw-avatar-remove' );
			var $spinner = $block.find( '.acfw-avatar-spinner' );

			function busy( on ) {
				$block.toggleClass( 'is-busy', on );
				$spinner.prop( 'hidden', ! on );
			}

			$block.on( 'click', '.acfw-avatar-edit', function () {
				$file.trigger( 'click' );
			} );

			$file.on( 'change', function () {
				if ( ! this.files || ! this.files.length ) {
					return;
				}
				var data = new FormData();
				data.append( 'action', 'acfw_avatar_upload' );
				data.append( 'nonce', acfw.avatarNonce );
				data.append( 'avatar', this.files[ 0 ] );
				this.value = '';

				busy( true );
				$.ajax( {
					url: acfw.ajaxUrl,
					method: 'POST',
					data: data,
					processData: false,
					contentType: false
				} ).done( function ( res ) {
					if ( res && res.success && res.data && res.data.url ) {
						$img.find( 'img' ).attr( 'srcset', '' ).attr( 'src', res.data.url );
						$remove.prop( 'hidden', false );
					} else {
						window.alert( ( res && res.data && res.data.message ) || acfw.avatarErrorMsg );
					}
				} ).fail( function () {
					window.alert( acfw.avatarErrorMsg );
				} ).always( function () {
					busy( false );
				} );
			} );

			$remove.on( 'click', function () {
				if ( ! window.confirm( acfw.avatarRemoveMsg ) ) {
					return;
				}
				busy( true );
				$.post( acfw.ajaxUrl, {
					action: 'acfw_avatar_remove',
					nonce: acfw.avatarNonce
				} ).done( function ( res ) {
					if ( res && res.success && res.data && res.data.url ) {
						$img.find( 'img' ).attr( 'srcset', '' ).attr( 'src', res.data.url );
						$remove.prop( 'hidden', true );
					}
				} ).always( function () {
					busy( false );
				} );
			} );
		} );

		// Reorder / Buy Again: add past products to the cart via AJAX.
		function acfwToast( msg ) {
			var $t = $( '<div class="acfw-toast" role="status"></div>' ).text( msg );
			$( 'body' ).append( $t );
			// Force reflow so the transition runs, then show and auto-dismiss.
			$t[ 0 ].offsetHeight; // eslint-disable-line no-unused-expressions
			$t.addClass( 'is-visible' );
			window.setTimeout( function () {
				$t.removeClass( 'is-visible' );
				window.setTimeout( function () {
					$t.remove();
				}, 300 );
			}, 2600 );
		}

		function acfwReorder( $btn, payload ) {
			if ( $btn.prop( 'disabled' ) ) {
				return;
			}
			$btn.prop( 'disabled', true ).addClass( 'loading' );
			payload.action = payload.action || 'acfw_reorder_add';
			payload.nonce = $btn.data( 'nonce' );

			$.post( acfw.ajaxUrl, payload ).done( function ( res ) {
				if ( res && res.success ) {
					acfwToast( ( res.data && res.data.message ) || 'Added to cart.' );
					$( document.body ).trigger( 'wc_fragment_refresh' );
				} else {
					acfwToast( ( res && res.data && res.data.message ) || 'Could not add to cart.' );
				}
			} ).fail( function () {
				acfwToast( 'Could not add to cart.' );
			} ).always( function () {
				$btn.prop( 'disabled', false ).removeClass( 'loading' );
			} );
		}

		$( document ).on( 'click', '.acfw-reorder-btn', function () {
			var $btn = $( this );
			acfwReorder( $btn, {
				action: 'acfw_reorder_add',
				product_id: $btn.data( 'product' ),
				quantity: $btn.data( 'qty' ) || 1
			} );
		} );

		$( document ).on( 'click', '.acfw-reorder-order-btn', function () {
			var $btn = $( this );
			acfwReorder( $btn, {
				action: 'acfw_reorder_order',
				order_id: $btn.data( 'order' )
			} );
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
