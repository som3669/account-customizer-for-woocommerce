/*
 * View as a customer: runs on My Account while a shop manager previews it as
 * one customer ( see ACFW_View_As ).
 *
 * Links inside My Account keep the preview's signed args, so every page opened
 * is a preview too. Anything that would act for the customer ( forms, Log out,
 * pay, cancel, order again, add to cart, coupon links, picture upload ) is
 * stopped with a short message. The server refuses the same requests.
 */
( function () {
	'use strict';

	var cfg  = window.acfwViewAs || {};
	var args = cfg.args || {};
	var base = cfg.base || '/';
	var note = null;
	var timer = null;

	function say() {
		if ( ! note ) {
			note = document.createElement( 'div' );
			note.className = 'acfw-view-as-toast';
			note.setAttribute( 'role', 'status' );
			document.body.appendChild( note );
		}
		note.textContent = cfg.blocked || '';
		note.classList.add( 'is-shown' );
		window.clearTimeout( timer );
		timer = window.setTimeout( function () {
			note.classList.remove( 'is-shown' );
		}, 3200 );
	}

	function stop( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();
		say();
	}

	var acting = /(^|\/)(customer-logout|delete-payment-method|set-default-payment-method|add-payment-method)(\/|$)/;
	var nonced = /[?&](_wpnonce|cancel_order|order_again|pay_for_order|add-to-cart|remove_item|acfw_apply)=/;
	var busy   = '.acfw-reorder-btn, .acfw-reorder-order-btn, .acfw-avatar-edit, .acfw-avatar-remove, .add_to_cart_button, .single_add_to_cart_button, .acfw-offer-apply';

	document.addEventListener(
		'click',
		function ( e ) {
			var el = e.target && e.target.closest ? e.target.closest( 'a[href], button, input[type="submit"], input[type="image"]' ) : null;
			if ( ! el ) {
				return;
			}
			if ( el.matches( busy ) ) {
				stop( e );
				return;
			}
			if ( 'A' !== el.tagName ) {
				return;
			}
			var href = el.getAttribute( 'href' ) || '';
			if ( '' === href || '#' === href.charAt( 0 ) || /^(javascript|mailto|tel):/i.test( href ) ) {
				return;
			}
			var url;
			try {
				url = new window.URL( el.href, window.location.href );
			} catch ( err ) {
				return;
			}
			if ( url.origin !== window.location.origin || 0 !== url.pathname.indexOf( base ) || acting.test( url.pathname ) || nonced.test( url.search ) ) {
				stop( e );
				return;
			}
			Object.keys( args ).forEach( function ( key ) {
				url.searchParams.set( key, args[ key ] );
			} );
			el.href = url.toString();
		},
		true
	);

	// No form is ever sent from the preview.
	document.addEventListener( 'submit', stop, true );
}() );
