/* global jQuery */
( function ( $ ) {
	'use strict';

	var settings = window.acfw || {};

	/**
	 * Read ( no value ) or write localStorage, without throwing in private
	 * mode or when storage is disabled.
	 *
	 * @param {string}  key   Storage key.
	 * @param {string=} value Value to store.
	 * @return {?string} Stored value when reading.
	 */
	function store( key, value ) {
		try {
			if ( undefined === value ) {
				return window.localStorage.getItem( key );
			}
			window.localStorage.setItem( key, value );
		} catch ( e ) {
			// No storage: the preference simply is not remembered.
		}
		return null;
	}

	/**
	 * Open or close a menu group, keeping its toggle's aria-expanded in step.
	 *
	 * @param {jQuery}  $group Group <li>.
	 * @param {boolean} open   Open it.
	 */
	function setGroupOpen( $group, open ) {
		$group.toggleClass( 'is-open', open );
		$group.children( '.acfw-group-toggle' ).attr( 'aria-expanded', open ? 'true' : 'false' );
	}

	$( function () {

		// Dashboard notice: remember dismissal per browser, keyed on the notice
		// text, so editing it shows the new one to everyone again.
		var $notice = $( '.acfw-dashboard-notice.is-dismissible' );
		if ( $notice.length ) {
			$notice.each( function () {
				var $el = $( this );
				if ( store( 'acfw_notice_' + $el.data( 'acfw-notice' ) ) ) {
					$el.hide();
				}
			} );

			$notice.on( 'click', '.acfw-notice-close', function () {
				var $el = $( this ).closest( '.acfw-dashboard-notice' );
				$el.slideUp( 150 );
				store( 'acfw_notice_' + $el.data( 'acfw-notice' ), '1' );
			} );
		}

		/* ---- Groups: expand / collapse ( click the title again to close ) ---- */
		$( '.acfw-menu' ).on( 'click', '.acfw-group-toggle', function () {
			var $group = $( this ).closest( '.acfw-type-group' );
			setGroupOpen( $group, ! $group.hasClass( 'is-open' ) );
		} );

		// Click outside closes open group dropdowns in the Tabs layout.
		$( document ).on( 'click', function ( e ) {
			if ( ! $( e.target ).closest( '.acfw-type-group' ).length ) {
				$( '.acfw-menu.layout-tabs .acfw-type-group.is-open' ).each( function () {
					setGroupOpen( $( this ), false );
				} );
			}
		} );

		// Escape closes a Tabs dropdown and returns focus to its toggle.
		$( document ).on( 'keydown', function ( e ) {
			if ( 'Escape' !== e.key ) {
				return;
			}
			$( '.acfw-menu.layout-tabs .acfw-type-group.is-open' ).each( function () {
				var $group    = $( this );
				var hadFocus  = $.contains( this, document.activeElement );
				setGroupOpen( $group, false );
				if ( hadFocus ) {
					$group.children( '.acfw-group-toggle' ).trigger( 'focus' );
				}
			} );
		} );

		/* ---- Menu search filter ---- */
		function labelMatches( $el, q ) {
			return -1 !== $el.find( '.acfw-label' ).first().text().toLowerCase().indexOf( q );
		}

		$( '.acfw-menu' ).on( 'input', '.acfw-menu-search', function () {
			var q     = ( this.value || '' ).toLowerCase().trim();
			var $menu = $( this ).closest( '.acfw-menu' );
			var $list = $menu.find( '#acfw-menu-list' );
			var shown = 0;

			$list.children( '.acfw-menu-item' ).each( function () {
				var $item = $( this );
				var show;

				if ( $item.hasClass( 'acfw-type-group' ) ) {
					// A group stays when its own name or any child matches, and
					// opens while the search is what reveals its children.
					var groupHit = '' !== q && labelMatches( $item.children( '.acfw-group-toggle' ), q );
					var childHits = 0;
					$item.find( '.acfw-submenu > .acfw-menu-item' ).each( function () {
						var hit = '' === q || groupHit || labelMatches( $( this ), q );
						$( this ).toggle( hit );
						childHits += hit ? 1 : 0;
					} );
					show = '' === q || groupHit || childHits > 0;

					if ( '' !== q && childHits > 0 && ! $item.hasClass( 'is-open' ) ) {
						$item.data( 'acfwSearchOpened', true );
						setGroupOpen( $item, true );
					} else if ( '' === q && $item.data( 'acfwSearchOpened' ) ) {
						$item.removeData( 'acfwSearchOpened' );
						setGroupOpen( $item, false );
					}
				} else {
					show = '' === q || labelMatches( $item, q );
				}

				$item.toggle( show );
				shown += show ? 1 : 0;
			} );

			var $empty = $menu.find( '.acfw-menu-empty' );
			if ( ! $empty.length ) {
				$empty = $( '<p class="acfw-menu-empty" role="status" hidden></p>' ).text( settings.searchEmpty || '' );
				$list.after( $empty );
			}
			$empty.prop( 'hidden', shown > 0 );
		} );

		/* ---- Pin favorites to the top ( no reload, per customer ) ---- */
		$( '.acfw-menu.acfw-pinnable' ).each( function () {
			var $list  = $( this ).find( '#acfw-menu-list' );
			var pinKey = 'acfwPins:' + ( settings.userId || 0 );

			// Remember the configured order, so an unpinned item goes back to its place.
			$list.children( '.acfw-menu-item' ).each( function ( i ) {
				$( this ).attr( 'data-acfw-order', i );
			} );

			function keyOf( li ) {
				return String( $( li ).children( '.acfw-pin' ).data( 'key' ) || '' );
			}

			function readPins() {
				var raw = store( pinKey );
				if ( null === raw ) {
					// Pins saved before they were kept per customer.
					raw = store( 'acfwPins' );
				}
				try {
					var pins = JSON.parse( raw || '[]' );
					return Array.isArray( pins ) ? pins.map( String ) : [];
				} catch ( e ) {
					return [];
				}
			}

			function apply() {
				var pins  = readPins();
				var items = $list.children( '.acfw-menu-item' ).get();

				items.sort( function ( a, b ) {
					var pa = pins.indexOf( keyOf( a ) );
					var pb = pins.indexOf( keyOf( b ) );
					if ( pa !== pb ) {
						if ( -1 === pa ) {
							return 1;
						}
						if ( -1 === pb ) {
							return -1;
						}
						return pa - pb;
					}
					return a.getAttribute( 'data-acfw-order' ) - b.getAttribute( 'data-acfw-order' );
				} );
				$list.append( items );

				$.each( items, function ( i, li ) {
					var $item  = $( li );
					var $btn   = $item.children( '.acfw-pin' );
					var pinned = -1 !== pins.indexOf( keyOf( li ) );
					var label  = $.trim( $item.find( '.acfw-label' ).first().text() );
					var text   = pinned ? settings.unpinLabel : settings.pinLabel;

					$item.toggleClass( 'is-pinned', pinned );
					if ( $btn.length ) {
						$btn.attr( 'aria-pressed', pinned ? 'true' : 'false' );
						if ( text ) {
							$btn.attr( 'aria-label', text.replace( '%s', label ) );
						}
						$btn.find( '.dashicons' )
							.toggleClass( 'dashicons-star-filled', pinned )
							.toggleClass( 'dashicons-star-empty', ! pinned );
					}
				} );
			}

			$list.on( 'click', '.acfw-pin', function ( e ) {
				e.preventDefault();
				var key  = String( $( this ).data( 'key' ) );
				var pins = readPins();
				var at   = pins.indexOf( key );
				if ( -1 === at ) {
					pins.push( key );
				} else {
					pins.splice( at, 1 );
				}
				store( pinKey, JSON.stringify( pins ) );
				apply();
				// Moving the row drops focus; put it back on the star.
				$( this ).trigger( 'focus' );
			} );

			apply();
		} );

		/* ---- Collapsible icon rail ---- */
		$( '.acfw-menu.acfw-collapsible' ).each( function () {
			if ( '1' === store( 'acfwCollapsed' ) ) {
				$( this ).addClass( 'is-collapsed' );
			}
		} );
		$( document ).on( 'click', '.acfw-collapse-toggle', function () {
			var collapsed = $( this ).closest( '.acfw-menu' ).toggleClass( 'is-collapsed' ).hasClass( 'is-collapsed' );
			store( 'acfwCollapsed', collapsed ? '1' : '0' );
		} );

		/* ---- Confirm before logout ---- */
		if ( settings.logoutConfirm ) {
			$( '.acfw-menu' ).on( 'click', 'a[href*="customer-logout"]', function ( e ) {
				if ( ! window.confirm( settings.logoutMsg ) ) {
					e.preventDefault();
				}
			} );
		}

		/* ---- Mobile nav drawer ---- */
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

		/* ---- Customer avatar upload ---- */
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
				data.append( 'nonce', settings.avatarNonce );
				data.append( 'avatar', this.files[ 0 ] );
				this.value = '';

				busy( true );
				$.ajax( {
					url: settings.ajaxUrl,
					method: 'POST',
					data: data,
					processData: false,
					contentType: false
				} ).done( function ( res ) {
					if ( res && res.success && res.data && res.data.url ) {
						$img.find( 'img' ).attr( 'srcset', '' ).attr( 'src', res.data.url );
						$remove.prop( 'hidden', false );
					} else {
						window.alert( ( res && res.data && res.data.message ) || settings.avatarErrorMsg );
					}
				} ).fail( function () {
					window.alert( settings.avatarErrorMsg );
				} ).always( function () {
					busy( false );
				} );
			} );

			$remove.on( 'click', function () {
				if ( ! window.confirm( settings.avatarRemoveMsg ) ) {
					return;
				}
				busy( true );
				$.post( settings.ajaxUrl, {
					action: 'acfw_avatar_remove',
					nonce: settings.avatarNonce
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

		/* ---- Reorder / Buy Again: add past products to the cart via AJAX ---- */
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

			$.post( settings.ajaxUrl, payload ).done( function ( res ) {
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

		/* ---- AJAX navigation between endpoints ---- */
		if ( ! settings.ajaxNavigation ) {
			return;
		}

		var selector = settings.contentSelector || '.woocommerce-MyAccount-content';
		var $content = $( selector ).first();
		if ( ! $content.length || ! window.fetch || ! window.DOMParser || ! window.URL || ! ( window.history && window.history.pushState ) ) {
			return;
		}

		function samePage( a, b ) {
			var ua = new window.URL( a, window.location.href );
			var ub = new window.URL( b, window.location.href );
			return ua.origin + ua.pathname.replace( /\/+$/, '' ) === ub.origin + ub.pathname.replace( /\/+$/, '' );
		}

		// Move the active state ( classes, aria-current, open group ) to the new page.
		function markCurrent( url ) {
			var $menu = $( '.woocommerce-MyAccount-navigation.acfw-menu' );
			$menu.find( '.acfw-type-endpoint' )
				.removeClass( 'is-active woocommerce-MyAccount-navigation-link--active' )
				.children( 'a' ).removeAttr( 'aria-current' );
			$menu.find( '.acfw-type-group' ).removeClass( 'has-current' );

			$menu.find( '.acfw-type-endpoint > a' ).each( function () {
				if ( ! samePage( this.href, url ) ) {
					return;
				}
				$( this ).attr( 'aria-current', 'page' )
					.parent().addClass( 'is-active woocommerce-MyAccount-navigation-link--active' );
				var $group = $( this ).closest( '.acfw-type-group' );
				if ( $group.length ) {
					$group.addClass( 'has-current' );
					setGroupOpen( $group, ! $group.closest( '.acfw-menu' ).hasClass( 'layout-tabs' ) );
				}
			} );
		}

		// Screen readers follow the new content; sighted users keep their place.
		function focusContent() {
			var el = $content.get( 0 );
			$content.addClass( 'acfw-ajax-content' );
			if ( ! el.hasAttribute( 'tabindex' ) ) {
				el.setAttribute( 'tabindex', '-1' );
			}
			try {
				el.focus( { preventScroll: true } );
			} catch ( e ) {
				el.focus();
			}
			if ( el.getBoundingClientRect().top < 0 ) {
				el.scrollIntoView( { block: 'start' } );
			}
		}

		function load( url, push ) {
			$content.addClass( 'acfw-loading' ).attr( 'aria-busy', 'true' );

			window.fetch( url, { credentials: 'same-origin' } ).then( function ( res ) {
				if ( ! res.ok ) {
					throw new Error( String( res.status ) );
				}
				return res.text().then( function ( html ) {
					return { html: html, url: res.url || url };
				} );
			} ).then( function ( page ) {
				var doc   = new window.DOMParser().parseFromString( page.html, 'text/html' );
				var fresh = doc.querySelector( selector );
				if ( ! fresh ) {
					// Logged out, or not an account page any more: load it for real.
					throw new Error( 'no content' );
				}

				// jQuery runs inline scripts, as a full page load would.
				$content.html( fresh.innerHTML );
				if ( doc.title ) {
					document.title = doc.title;
				}
				if ( push ) {
					window.history.pushState( { acfw: true }, '', page.url );
				}
				markCurrent( page.url );
				closeDrawer();
				focusContent();
				$( document.body ).trigger( 'acfw:navigated', [ page.url ] );
				$content.removeClass( 'acfw-loading' ).removeAttr( 'aria-busy' );
			} ).catch( function () {
				window.location.href = url;
			} );
		}

		$( document ).on( 'click', '.acfw-menu .acfw-type-endpoint > a', function ( e ) {
			// Let the browser handle new-tab clicks, other targets and other sites.
			if ( e.isDefaultPrevented() || 0 !== e.button || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey ) {
				return;
			}
			if ( ( this.target && '_self' !== this.target ) || this.origin !== window.location.origin || this.hasAttribute( 'download' ) ) {
				return;
			}
			// Log out has to be a real request.
			if ( $( this ).parent().hasClass( 'woocommerce-MyAccount-navigation-link--customer-logout' ) ) {
				return;
			}
			var href = this.getAttribute( 'href' );
			if ( ! href || 0 === href.indexOf( '#' ) ) {
				return;
			}
			e.preventDefault();
			load( this.href, true );
		} );

		// Back / Forward: show the page the address bar now points to.
		window.history.replaceState( { acfw: true }, '', window.location.href );
		window.addEventListener( 'popstate', function ( e ) {
			if ( e.state && e.state.acfw ) {
				load( window.location.href, false );
			}
		} );
	} );

} )( jQuery );
