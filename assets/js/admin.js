/* global jQuery, acfwAdmin, wp */
( function ( $ ) {
	'use strict';

	$( function () {

		/* ---- Color pickers ---- */
		if ( $.fn.wpColorPicker ) {
			$( '.acfw-color' ).wpColorPicker();
		}

		var $details = $( '.acfw-builder-detail' );
		var initedEditors = {};

		/* ---- Add item ---- */
		var $addForm = $( '.acfw-add-form' );

		$( '.acfw-add-btn' ).on( 'click', function () {
			$addForm.find( '.acfw-add-type' ).val( $( this ).data( 'type' ) );
			$addForm.show().find( '.acfw-add-label' ).val( '' ).focus();
		} );

		$( '.acfw-add-cancel' ).on( 'click', function () {
			$addForm.hide();
		} );

		/* ---- Select a row → show its detail form ---- */
		function selectItem( key ) {
			$( '.acfw-node' ).removeClass( 'is-selected' );
			$( '.acfw-node[data-key="' + key + '"]' ).first().addClass( 'is-selected' );

			$details.find( '.acfw-detail-empty' ).hide();
			$details.find( '.acfw-detail' ).attr( 'hidden', 'hidden' );
			var $form = $details.find( '.acfw-detail[data-key="' + key + '"]' ).removeAttr( 'hidden' );

			// Lazily boot the rich editor for endpoint content.
			var $ta = $form.find( '.acfw-content-editor' );
			if ( $ta.length && ! initedEditors[ key ] && window.wp && wp.editor ) {
				wp.editor.initialize( $ta.attr( 'id' ), {
					tinymce: { wpautop: true, plugins: 'lists,link,paste,wordpress,wplink', toolbar1: 'bold,italic,bullist,numlist,link,undo,redo' },
					quicktags: true,
					mediaButtons: true
				} );
				initedEditors[ key ] = true;
			}
		}

		$( document ).on( 'click', '.acfw-node-head', function ( e ) {
			if ( $( e.target ).closest( '.acfw-drag, .acfw-node-remove, .acfw-switch' ).length ) {
				return;
			}
			selectItem( $( this ).closest( '.acfw-node' ).data( 'key' ) );
		} );

		/* ---- Active on/off toggle (persists immediately) ---- */
		$( document ).on( 'change', '.acfw-active-proxy', function () {
			var key = $( this ).data( 'key' );
			var on = this.checked;
			$( this ).closest( '.acfw-node' ).toggleClass( 'is-inactive', ! on );
			var $form = $details.find( '.acfw-detail[data-key="' + key + '"]' );
			$form.find( '.acfw-active-input' ).val( on ? '1' : '0' );
			syncEditors();
			$form.trigger( 'submit' );
		} );

		/* ---- Icon source toggle ---- */
		$( document ).on( 'change', '.acfw-icon-source input[type="radio"]', function () {
			var $form = $( this ).closest( '.acfw-detail' );
			var upload = 'upload' === this.value;
			$form.find( '.acfw-radio-card' ).removeClass( 'is-active' );
			$( this ).closest( '.acfw-radio-card' ).addClass( 'is-active' );
			$form.find( '.acfw-icon-upload' ).attr( 'hidden', upload ? null : 'hidden' );
			$form.find( '.acfw-icon-choose' ).attr( 'hidden', upload ? 'hidden' : null );
		} );

		/* ---- Media picker for uploaded icon ---- */
		$( document ).on( 'click', '.acfw-icon-media', function ( e ) {
			e.preventDefault();
			var $field = $( this ).siblings( '.acfw-icon-url' );
			var frame = wp.media( {
				title: acfwAdmin.mediaTitle,
				button: { text: acfwAdmin.mediaButton },
				multiple: false
			} );
			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				$field.val( att.url );
			} );
			frame.open();
		} );

		/* ---- Delete item ---- */
		$( document ).on( 'click', '.acfw-node-remove', function ( e ) {
			e.stopPropagation();
			if ( ! window.confirm( acfwAdmin.confirmDelete ) ) {
				return;
			}
			$( '.acfw-delete-form' ).find( '.acfw-delete-key' ).val( $( this ).data( 'key' ) ).end().trigger( 'submit' );
		} );

		/* ---- Push TinyMCE content back to textareas before any save ---- */
		function syncEditors() {
			if ( window.tinymce ) {
				window.tinymce.triggerSave();
			}
		}
		$( document ).on( 'submit', '.acfw-item-form', syncEditors );

		/* ---- Drag & drop (nestable, 1 level into groups) ---- */
		$( '.acfw-sortable' ).sortable( {
			handle: '.acfw-drag',
			items: '> .acfw-node',
			placeholder: 'acfw-node-placeholder',
			connectWith: '.acfw-sortable',
			tolerance: 'pointer',
			cursor: 'grabbing',
			receive: function ( event, ui ) {
				if ( $( this ).hasClass( 'acfw-sortable-children' ) && 'group' === ui.item.data( 'type' ) ) {
					$( ui.sender ).sortable( 'cancel' );
				}
			}
		} );

		/* ---- Serialize order tree on save ---- */
		function serialize( $list ) {
			var order = {};
			$list.children( '.acfw-node' ).each( function () {
				var $node = $( this );
				var node = { type: $node.data( 'type' ) };
				var $children = $node.children( '.acfw-sortable-children' );
				if ( $children.length ) {
					node.children = serialize( $children );
				}
				order[ $node.data( 'key' ) ] = node;
			} );
			return order;
		}

		$( '.acfw-order-form' ).on( 'submit', function () {
			$( this ).find( '.acfw-order-input' ).val( JSON.stringify( serialize( $( '.acfw-sortable-root' ) ) ) );
		} );
	} );

} )( jQuery );
