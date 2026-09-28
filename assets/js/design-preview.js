/*
 * Design Studio preview: runs inside the Studio's iframe on the account page
 * ( ?acfw_preview=1, shop managers only ).
 *
 * The server already renders the admin's draft. This script applies the
 * values that can change without a reload ( colours, sizes, menu classes ) the
 * moment the Studio sends them, and keeps navigation inside the preview.
 */
( function () {
	'use strict';

	var cfg = window.acfwPreview || {};

	// Opened on its own, not inside the Studio: behave like the normal page.
	if ( window.parent === window ) {
		return;
	}

	var style = null;

	function trusted( origin ) {
		return origin === window.location.origin || ( cfg.origin && 0 === cfg.origin.indexOf( origin ) );
	}

	function hexToRgba( hex, alpha ) {
		var h = String( hex || '' ).replace( '#', '' );
		if ( 3 === h.length ) {
			h = h[ 0 ] + h[ 0 ] + h[ 1 ] + h[ 1 ] + h[ 2 ] + h[ 2 ];
		}
		if ( ! /^[0-9a-f]{6}$/i.test( h ) ) {
			return 'rgba(37,99,235,' + alpha + ')';
		}
		return 'rgba(' + parseInt( h.substr( 0, 2 ), 16 ) + ',' + parseInt( h.substr( 2, 2 ), 16 ) + ',' + parseInt( h.substr( 4, 2 ), 16 ) + ',' + alpha + ')';
	}

	function px( value, fallback ) {
		var n = parseInt( value, 10 );
		return ( isNaN( n ) ? fallback : n ) + 'px';
	}

	// The same rule ACFW_Frontend::dynamic_css() prints, built from the draft.
	function tokenCss( v ) {
		var accent = v.acfw_accent_color || '#2563eb';
		var text   = v.acfw_text_color || '#383838';
		var active = v.acfw_active_color || accent;
		var fonts  = cfg.fonts || {};
		var css    = cfg.scope + '{' +
			'--acfw-accent:' + accent + ';' +
			'--acfw-text:' + text + ';' +
			'--acfw-accent-tint:' + hexToRgba( accent, 0.1 ) + ';' +
			'--acfw-radius:' + px( v.acfw_menu_radius, 8 ) + ';' +
			'--acfw-gap:' + px( v.acfw_menu_gap, 4 ) + ';' +
			'--acfw-item-padding:' + px( v.acfw_item_padding, 11 ) + ';' +
			'--acfw-avatar-size:' + px( v.acfw_avatar_size, 72 ) + ';' +
			'--acfw-font-size:' + px( v.acfw_font_size, 15 ) + ';' +
			'--acfw-font-weight:' + ( parseInt( v.acfw_font_weight, 10 ) || 500 ) + ';' +
			'--acfw-active:' + active + ';' +
			// "initial" makes a token invalid, so var() falls back to the stylesheet's default.
			'--acfw-menu-bg:' + ( v.acfw_menu_bg || 'initial' ) + ';' +
			'--acfw-hover-bg:' + ( v.acfw_hover_bg || 'initial' ) + ';' +
			( fonts[ v.acfw_font_family ] ? '--acfw-font-family:' + fonts[ v.acfw_font_family ] + ';' : '' ) +
			'}';

		css += '.acfw-menu{font-family:' + ( fonts[ v.acfw_font_family ] ? 'var(--acfw-font-family)' : 'inherit' ) + ';}';
		css += '.acfw-menu:not(.layout-theme){color:' + ( '#383838' !== String( text ).toLowerCase() ? 'var(--acfw-text)' : 'inherit' ) + ';}';
		return css;
	}

	function swapClass( el, prefix, value ) {
		var kept = el.className.split( /\s+/ ).filter( function ( c ) {
			return c && 0 !== c.indexOf( prefix );
		} );
		kept.push( prefix + value );
		el.className = kept.join( ' ' );
	}

	function apply( v ) {
		if ( ! style ) {
			style = document.createElement( 'style' );
			style.id = 'acfw-studio-live';
			document.head.appendChild( style );
		}
		style.textContent = tokenCss( v );

		var resolved = ( cfg.styles || {} )[ v.acfw_menu_style ] || [ 'simple', 'flat' ];
		var navs     = document.querySelectorAll( 'nav.acfw-menu' );
		Array.prototype.forEach.call( navs, function ( nav ) {
			swapClass( nav, 'position-', v.acfw_menu_position || 'vertical-left' );
			swapClass( nav, 'layout-', resolved[ 0 ] );
			swapClass( nav, 'acfw-preset-', resolved[ 1 ] );
			swapClass( nav, 'acfw-ind-', v.acfw_active_indicator || 'bar' );
			swapClass( nav, 'acfw-anim-', v.acfw_hover_anim || 'none' );
			swapClass( nav, 'acfw-scheme-', v.acfw_color_scheme || 'light' );
			nav.classList.toggle( 'acfw-hide-icons', 'no' === v.acfw_show_icons );
			nav.classList.toggle( 'acfw-sticky', 'yes' === v.acfw_sticky_menu );
		} );
	}

	window.addEventListener( 'message', function ( e ) {
		var data = e.data;
		if ( ! trusted( e.origin ) || ! data || 'acfw-studio' !== data.source ) {
			return;
		}
		if ( 'apply' === data.type && data.values ) {
			apply( data.values );
		}
	} );

	// Keep clicks inside the account area, in preview mode, and never log out.
	document.addEventListener(
		'click',
		function ( e ) {
			var link = e.target && e.target.closest ? e.target.closest( 'a[href]' ) : null;
			if ( ! link ) {
				return;
			}
			var url;
			try {
				url = new window.URL( link.href, window.location.href );
			} catch ( err ) {
				return;
			}
			if ( /customer-logout/.test( url.pathname + url.search ) || url.origin !== window.location.origin || 0 !== url.pathname.indexOf( cfg.base || '/' ) ) {
				e.preventDefault();
				return;
			}
			url.searchParams.set( cfg.arg || 'acfw_preview', '1' );
			link.href = url.toString();
		},
		true
	);

	// Open on the account area, not the theme's header; after a reload, return
	// to where the admin had scrolled.
	var scrollKey = 'acfwPreviewScroll:' + window.location.pathname;
	window.addEventListener( 'beforeunload', function () {
		try {
			window.sessionStorage.setItem( scrollKey, String( window.scrollY ) );
		} catch ( err ) {}
	} );
	window.addEventListener( 'load', function () {
		var kept = null;
		try {
			kept = window.sessionStorage.getItem( scrollKey );
		} catch ( err ) {}
		if ( null !== kept ) {
			window.scrollTo( 0, parseInt( kept, 10 ) || 0 );
			return;
		}
		var area = document.querySelector( '.woocommerce-MyAccount-navigation, .woocommerce' );
		if ( area ) {
			window.scrollTo( 0, Math.max( 0, area.getBoundingClientRect().top + window.scrollY - 24 ) );
		}
	} );

	window.parent.postMessage( { source: 'acfw-preview', type: 'ready', path: window.location.pathname }, '*' );
}() );
