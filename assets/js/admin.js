/* global jQuery, acfwAdmin, wp */
( function ( $ ) {
	'use strict';

	$( function () {

		/* ---- Color pickers ---- */
		if ( $.fn.wpColorPicker ) {
			$( '.acfw-color' ).wpColorPicker();
		}

		/* ---- Role chips + searchable icon picker with glyphs (bundled select2) ---- */
		if ( $.fn.select2 ) {
			$( '.acfw-roles-select' ).select2( { width: '100%', placeholder: 'All roles', closeOnSelect: false } );

			var iconTpl = function ( data ) {
				if ( ! data.id ) {
					return data.text;
				}
				return $( '<span class="acfw-icon-opt"><i class="' + data.id + '"></i> <span>' + data.text + '</span></span>' );
			};
			$( '.acfw-icon-select' ).select2( {
				width: '100%',
				placeholder: 'Select an icon',
				allowClear: true,
				templateResult: iconTpl,
				templateSelection: iconTpl
			} );
		}

		var $details = $( '.acfw-builder-detail' );

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

			// The editor is rendered server-side (wp_editor) inside a hidden form;
			// repaint TinyMCE now it is visible so the iframe sizes correctly.
			if ( window.tinymce ) {
				var ed = window.tinymce.get( 'acfw_content_' + String( key ).replace( /-/g, '_' ) );
				if ( ed ) {
					ed.hide();
					ed.show();
				}
			}
		}

		$( document ).on( 'click', '.acfw-node-head', function ( e ) {
			if ( $( e.target ).closest( '.acfw-drag, .acfw-node-remove, .acfw-node-duplicate, .acfw-switch' ).length ) {
				return;
			}
			selectItem( $( this ).closest( '.acfw-node' ).data( 'key' ) );
		} );

		/* ---- Auto-select the first item on load (reference shows options immediately) ---- */
		var $firstNode = $( '.acfw-sortable-root > .acfw-node' ).first();
		if ( $firstNode.length ) {
			selectItem( $firstNode.data( 'key' ) );
		}

		/* ---- Active on/off toggle (saved with the single Save button) ---- */
		$( document ).on( 'change', '.acfw-active-proxy', function () {
			var key = $( this ).data( 'key' );
			var on = this.checked;
			$( this ).closest( '.acfw-node' ).toggleClass( 'is-inactive', ! on );
			$details.find( '.acfw-detail[data-key="' + key + '"]' ).find( '.acfw-active-input' ).val( on ? '1' : '0' );
		} );

		/* ---- Radio-box groups (reference-style radio controls) ---- */
		$( document ).on( 'change', '.acfw-radio-group input[type="radio"]', function () {
			$( this ).closest( '.acfw-radio-group' ).find( '.acfw-radio-box, .acfw-image-card' ).removeClass( 'is-active' );
			$( this ).closest( '.acfw-radio-box, .acfw-image-card' ).addClass( 'is-active' );
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

		/* ---- Media library picker (uploader box: icons + banner images) ---- */
		function acfwSetImage( $wrap, url ) {
			$wrap.find( '.acfw-media-input' ).val( url );
			var $preview = $wrap.find( '.acfw-media-preview' );
			if ( url ) {
				$preview.attr( 'src', url ).removeAttr( 'hidden' );
				$wrap.find( '.acfw-uploader-box' ).addClass( 'has-image' );
			} else {
				$preview.attr( 'src', '' ).attr( 'hidden', 'hidden' );
				$wrap.find( '.acfw-uploader-box' ).removeClass( 'has-image' );
			}
		}

		$( document ).on( 'click', '.acfw-media-btn', function ( e ) {
			e.preventDefault();
			var $wrap = $( this ).closest( '.acfw-uploader' );
			if ( ! $wrap.length ) {
				$wrap = $( this ).closest( '.acfw-media-row' ).parent();
			}
			var frame = wp.media( {
				title: acfwAdmin.mediaTitle,
				button: { text: acfwAdmin.mediaButton },
				library: { type: 'image' },
				multiple: false
			} );
			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				acfwSetImage( $wrap, att.url );
			} );
			frame.open();
		} );

		// Click empty dropzone opens the media library.
		$( document ).on( 'click', '.acfw-uploader-box:not(.has-image) .acfw-uploader-empty', function ( e ) {
			if ( ! $( e.target ).is( 'button' ) ) {
				$( this ).closest( '.acfw-uploader' ).find( '.acfw-media-btn' ).first().trigger( 'click' );
			}
		} );

		// Remove image.
		$( document ).on( 'click', '.acfw-uploader-remove', function ( e ) {
			e.preventDefault();
			acfwSetImage( $( this ).closest( '.acfw-uploader' ), '' );
		} );

		// Paste-URL reflects into the preview.
		$( document ).on( 'change', '.acfw-uploader .acfw-media-input', function () {
			acfwSetImage( $( this ).closest( '.acfw-uploader' ), $( this ).val() );
		} );

		// Basic drag styling ( click/URL/media are the upload paths ).
		$( document ).on( 'dragover', '.acfw-uploader-box', function ( e ) {
			e.preventDefault();
			$( this ).addClass( 'is-dragover' );
		} ).on( 'dragleave drop', '.acfw-uploader-box', function () {
			$( this ).removeClass( 'is-dragover' );
		} );

		/* ---- Duplicate item ---- */
		$( document ).on( 'click', '.acfw-node-duplicate', function ( e ) {
			e.stopPropagation();
			$( '.acfw-duplicate-form' ).find( '.acfw-duplicate-key' ).val( $( this ).data( 'key' ) ).end().trigger( 'submit' );
		} );

		/* ---- Delete item ---- */
		$( document ).on( 'click', '.acfw-node-remove', function ( e ) {
			e.stopPropagation();
			if ( ! window.confirm( acfwAdmin.confirmDelete ) ) {
				return;
			}
			$( '.acfw-delete-form' ).find( '.acfw-delete-key' ).val( $( this ).data( 'key' ) ).end().trigger( 'submit' );
		} );

		/* ---- Insert smart tag into the content editor ---- */
		/* ---- Smart tags button + menu (beside Add Media) ---- */
		$( document ).on( 'click', '.acfw-smarttag-btn', function ( e ) {
			e.preventDefault();
			var $menu = $( this ).siblings( '.acfw-smarttag-menu' );
			$( '.acfw-smarttag-menu' ).not( $menu ).attr( 'hidden', 'hidden' );
			$menu.data( 'target', $( this ).data( 'target' ) );
			if ( $menu.attr( 'hidden' ) ) {
				$menu.removeAttr( 'hidden' );
			} else {
				$menu.attr( 'hidden', 'hidden' );
			}
		} );

		$( document ).on( 'click', '.acfw-smarttag-item', function ( e ) {
			e.preventDefault();
			var $menu = $( this ).closest( '.acfw-smarttag-menu' );
			var targetId = $menu.data( 'target' );
			var tag = $( this ).data( 'tag' );
			var ed = window.tinymce && window.tinymce.get( targetId );
			if ( ed && ! ed.isHidden() ) {
				ed.execCommand( 'mceInsertContent', false, tag );
			} else {
				var $ta = $( '#' + targetId );
				$ta.val( ( $ta.val() || '' ) + tag );
			}
			$menu.attr( 'hidden', 'hidden' );
		} );

		$( document ).on( 'click', function ( e ) {
			if ( ! $( e.target ).closest( '.acfw-smarttag-wrap' ).length ) {
				$( '.acfw-smarttag-menu' ).attr( 'hidden', 'hidden' );
			}
		} );

		/* ---- Preview overlay (live account page in an iframe) ---- */
		$( document ).on( 'click', '.acfw-preview-btn', function () {
			var url = $( this ).data( 'url' );
			if ( ! url ) {
				return;
			}
			var $ov = $( '<div class="acfw-preview-overlay"><div class="acfw-preview-frame"><button type="button" class="acfw-preview-close" aria-label="Close">&times;</button><iframe src="' + url + '"></iframe></div></div>' );
			$( 'body' ).append( $ov ).addClass( 'acfw-preview-open' );
		} );
		$( document ).on( 'click', '.acfw-preview-overlay, .acfw-preview-close', function ( e ) {
			if ( e.target === this ) {
				$( '.acfw-preview-overlay' ).remove();
				$( 'body' ).removeClass( 'acfw-preview-open' );
			}
		} );

		/* ---- Reset all settings confirm ---- */
		$( document ).on( 'click', '.acfw-reset-btn', function ( e ) {
			if ( ! window.confirm( acfwAdmin.confirmDelete ) ) {
				e.preventDefault();
			}
		} );

		/* ---- Delete banner (switches the form action) ---- */
		$( document ).on( 'click', '.acfw-banner-delete', function ( e ) {
			if ( ! window.confirm( acfwAdmin.confirmDelete ) ) {
				e.preventDefault();
				return;
			}
			$( this ).closest( 'form' ).find( 'input[name="acfw_action"]' ).val( 'remove_banner' );
		} );

		/* ---- Push TinyMCE content back to textareas before save ---- */
		function syncEditors() {
			if ( window.tinymce ) {
				window.tinymce.triggerSave();
			}
		}

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

		$( document ).on( 'submit', '.acfw-items-form', function () {
			syncEditors();
			$( this ).find( '.acfw-order-input' ).val( JSON.stringify( serialize( $( '.acfw-sortable-root' ) ) ) );
		} );
	} );

} )( jQuery );
