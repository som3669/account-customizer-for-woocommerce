/* global wp */
( function ( blocks, element, ServerSideRender, i18n ) {
	'use strict';
	var __ = i18n.__;

	blocks.registerBlockType( 'acfw/account-menu', {
		apiVersion: 3,
		title: __( 'Account Menu', 'account-customizer-for-woocommerce' ),
		description: __( 'The customized My Account navigation menu.', 'account-customizer-for-woocommerce' ),
		icon: 'menu-alt',
		category: 'woocommerce',
		supports: { html: false },
		edit: function () {
			return element.createElement( ServerSideRender, { block: 'acfw/account-menu' } );
		},
		save: function () {
			return null;
		}
	} );
} )( wp.blocks, wp.element, wp.serverSideRender, wp.i18n );
