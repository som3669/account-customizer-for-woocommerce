/* global wp */
( function ( blocks, element, ServerSideRender, i18n ) {
	'use strict';
	var __ = i18n.__;

	blocks.registerBlockType( 'acfw/account-menu', {
		apiVersion: 3,
		title: __( 'Account Menu', 'my-account-dashboard-builder' ),
		description: __( 'The customized My Account navigation menu.', 'my-account-dashboard-builder' ),
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
