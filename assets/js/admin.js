/* global jQuery, acfwAdmin, wp */
( function ( $ ) {
	'use strict';

	$( function () {

		/* ---- Toast notifications ( auto-dismiss + click to close ) ---- */
		function acfwDismissToast( $toast ) {
			$toast.addClass( 'is-leaving' );
			window.setTimeout( function () {
				$toast.remove();
			}, 250 );
		}
		// Confirmations fade on their own; a warning or an error stays until it
		// is closed, so there is time to read it.
		$( '.acfw-toast.acfw-toast-success' ).each( function () {
			var $toast = $( this );
			window.setTimeout( function () {
				acfwDismissToast( $toast );
			}, 3500 );
		} );
		$( document ).on( 'click', '.acfw-toast-close', function () {
			acfwDismissToast( $( this ).closest( '.acfw-toast' ) );
		} );

		/* ---- Color pickers ( legacy iris, if any .acfw-color remain ) ---- */
		if ( $.fn.wpColorPicker ) {
			$( '.acfw-color' ).wpColorPicker();
		}

		/* ---- Round swatch colour control ( popover + alpha + hex ) ---- */
		var $bcpPop = null;
		var $bcpRoot = null;

		function bcpParse( val ) {
			val = ( val || '' ).trim();
			var m = val.match( /^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([0-9.]+)\s*)?\)$/i );
			if ( m ) {
				return {
					hex: bcpRgbToHex( +m[1], +m[2], +m[3] ),
					alpha: ( undefined !== m[4] && '' !== m[4] ) ? parseFloat( m[4] ) : 1
				};
			}
			if ( /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test( val ) ) {
				return { hex: bcpNormHex( val ), alpha: 1 };
			}
			return { hex: '#000000', alpha: 1 };
		}

		function bcpNormHex( h ) {
			h = h.replace( '#', '' );
			if ( 3 === h.length ) {
				h = h[ 0 ] + h[ 0 ] + h[ 1 ] + h[ 1 ] + h[ 2 ] + h[ 2 ];
			}
			return '#' + h.toLowerCase();
		}

		function bcpRgbToHex( r, g, b ) {
			function h( n ) {
				n = Math.max( 0, Math.min( 255, n ) ).toString( 16 );
				return 1 === n.length ? '0' + n : n;
			}
			return '#' + h( r ) + h( g ) + h( b );
		}

		function bcpToValue( hex, alpha ) {
			if ( alpha >= 1 ) {
				return hex;
			}
			var h = hex.replace( '#', '' );
			var r = parseInt( h.substr( 0, 2 ), 16 );
			var g = parseInt( h.substr( 2, 2 ), 16 );
			var b = parseInt( h.substr( 4, 2 ), 16 );
			return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + ( Math.round( alpha * 100 ) / 100 ) + ')';
		}

		function bcpClose() {
			if ( $bcpPop ) {
				$bcpPop.remove();
				$bcpPop = null;
				$bcpRoot = null;
			}
		}

		$( document ).on( 'click', '.acfw-bcp-control', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			var $root = $( this ).closest( '.acfw-bcp-root' );
			if ( $bcpRoot && $bcpRoot[ 0 ] === $root[ 0 ] ) {
				bcpClose();
				return;
			}
			bcpClose();
			$bcpRoot = $root;

			var $input = $root.find( '.acfw-bcp-input' );
			var parsed = bcpParse( $input.val() );

			$bcpPop = $(
				'<div class="acfw-bcp-popover">' +
					'<input type="color" class="acfw-bcp-color" value="' + parsed.hex + '" />' +
					'<input type="range" class="acfw-bcp-alpha" min="0" max="100" value="' + Math.round( parsed.alpha * 100 ) + '" />' +
					'<div class="acfw-bcp-alpha-label"><span>Opacity</span><span class="acfw-bcp-alpha-val">' + Math.round( parsed.alpha * 100 ) + '%</span></div>' +
					'<div class="acfw-bcp-fields">' +
						'<input type="text" class="acfw-bcp-hex" value="' + $input.val() + '" placeholder="#rrggbb" />' +
						'<button type="button" class="acfw-bcp-clear">Clear</button>' +
					'</div>' +
				'</div>'
			);
			$( 'body' ).append( $bcpPop );

			var off = $( this ).offset();
			$bcpPop.css( {
				top: off.top + $( this ).outerHeight() + 6,
				left: off.left
			} );

			function apply( hex, alpha ) {
				var val = bcpToValue( hex, alpha );
				$input.val( val );
				$root.find( '.acfw-bcp-swatch' ).css( '--acfw-bcp-color', val );
				$bcpPop.find( '.acfw-bcp-hex' ).val( val );
			}

			$bcpPop.on( 'input', '.acfw-bcp-color', function () {
				apply( this.value, $bcpPop.find( '.acfw-bcp-alpha' ).val() / 100 );
			} );
			$bcpPop.on( 'input', '.acfw-bcp-alpha', function () {
				$bcpPop.find( '.acfw-bcp-alpha-val' ).text( this.value + '%' );
				apply( $bcpPop.find( '.acfw-bcp-color' ).val(), this.value / 100 );
			} );
			$bcpPop.on( 'change', '.acfw-bcp-hex', function () {
				var p = bcpParse( this.value );
				$bcpPop.find( '.acfw-bcp-color' ).val( p.hex );
				$bcpPop.find( '.acfw-bcp-alpha' ).val( Math.round( p.alpha * 100 ) );
				$bcpPop.find( '.acfw-bcp-alpha-val' ).text( Math.round( p.alpha * 100 ) + '%' );
				apply( p.hex, p.alpha );
			} );
			$bcpPop.on( 'click', '.acfw-bcp-clear', function () {
				$input.val( '' );
				$root.find( '.acfw-bcp-swatch' ).css( '--acfw-bcp-color', 'transparent' );
				bcpClose();
			} );
			$bcpPop.on( 'click', function ( ev ) {
				ev.stopPropagation();
			} );
		} );

		$( document ).on( 'click', function () {
			bcpClose();
		} );
		$( document ).on( 'keydown', function ( ev ) {
			if ( 27 === ev.keyCode ) {
				bcpClose();
			}
		} );

		/* ---- Role chips + searchable icon picker with glyphs (bundled select2) ---- */
		if ( $.fn.select2 ) {
			$( '.acfw-roles-select' ).each( function () {
				$( this ).select2( {
					width: '100%',
					placeholder: $( this ).data( 'placeholder' ) || acfwAdmin.everyone || '',
					closeOnSelect: false
				} );
			} );
			$( '.acfw-banner-select' ).each( function () {
				var $el = $( this );
				$el.select2( {
					width: '100%',
					placeholder: $el.data( 'placeholder' ) || '',
					closeOnSelect: false,
					allowClear: true,
				} );
			} );

			var iconTpl = function ( data ) {
				if ( ! data.id ) {
					return data.text;
				}
				return $( '<span class="acfw-icon-opt"><i class="' + data.id + '"></i> <span>' + data.text + '</span></span>' );
			};
			// Every picker searches one shared list ( printed once as
			// acfwIconChoices ), paged 60 at a time, so none of them carries a
			// thousand <option>s of its own.
			var iconChoices = window.acfwIconChoices || [];
			$( '.acfw-icon-select' ).each( function () {
				var $el = $( this );
				$el.select2( {
					width: '100%',
					placeholder: $el.data( 'placeholder' ) || '',
					allowClear: true,
					templateResult: iconTpl,
					templateSelection: iconTpl,
					ajax: {
						delay: 0,
						transport: function ( params, success ) {
							var term = ( ( params.data && params.data.term ) || '' ).toLowerCase();
							var page = ( params.data && params.data.page ) || 1;
							var hits = ! term ? iconChoices : iconChoices.filter( function ( icon ) {
								return -1 !== icon.text.toLowerCase().indexOf( term ) || -1 !== icon.id.indexOf( term );
							} );
							success( {
								results: hits.slice( ( page - 1 ) * 60, page * 60 ),
								pagination: { more: page * 60 < hits.length }
							} );
							return { abort: function () {} };
						},
						processResults: function ( data ) {
							return data;
						}
					}
				} );
			} );

			// Purchased-products rule: search the catalogue through WooCommerce's
			// own product search ( names, SKUs, variations ).
			$( '.acfw-product-select' ).each( function () {
				var $el = $( this );
				$el.select2( {
					width: '100%',
					placeholder: $el.data( 'placeholder' ) || '',
					closeOnSelect: false,
					minimumInputLength: 3,
					language: {
						inputTooShort: function () {
							return acfwAdmin.productMinChar;
						},
						searching: function () {
							return acfwAdmin.searching;
						},
						noResults: function () {
							return acfwAdmin.noResults;
						}
					},
					ajax: {
						url: acfwAdmin.ajaxUrl,
						dataType: 'json',
						delay: 250,
						data: function ( params ) {
							return {
								action: 'woocommerce_json_search_products_and_variations',
								security: acfwAdmin.productNonce,
								term: params.term
							};
						},
						processResults: function ( data ) {
							var results = [];
							$.each( data || {}, function ( id, text ) {
								// Names come back with entities ( &ndash; … ); select2 escapes
								// its text, so decode them to plain characters first.
								results.push( { id: id, text: $( '<textarea/>' ).html( text ).text() } );
							} );
							return { results: results };
						},
						cache: true
					}
				} );
			} );
		}

		var $details = $( '.acfw-builder-detail' );

		/* ---- Add item ( split-button dropdown ) ---- */
		var $addForm = $( '.acfw-add-form' );

		function closeAddMenu() {
			$( '.acfw-add-dropdown' ).prop( 'hidden', true );
			$( '.acfw-add-toggle' ).attr( 'aria-expanded', 'false' ).removeClass( 'is-open' );
		}

		$( document ).on( 'click', '.acfw-add-toggle', function ( e ) {
			e.stopPropagation();
			var $dd = $( this ).siblings( '.acfw-add-dropdown' );
			var wasHidden = $dd.prop( 'hidden' );
			closeAddMenu();
			if ( wasHidden ) {
				$dd.prop( 'hidden', false );
				$( this ).attr( 'aria-expanded', 'true' ).addClass( 'is-open' );
			}
		} );

		$( document ).on( 'click', function ( e ) {
			if ( ! $( e.target ).closest( '.acfw-add-menu' ).length ) {
				closeAddMenu();
			}
		} );

		$( document ).on( 'click', '.acfw-add-btn', function () {
			closeAddMenu();
			$addForm.find( '.acfw-add-type' ).val( $( this ).data( 'type' ) );
			$addForm.show().find( '.acfw-add-label' ).val( '' ).focus();
		} );

		$( '.acfw-add-cancel' ).on( 'click', function () {
			$addForm.hide();
		} );

		/* ---- Add banner (banners tab) ---- */
		/* ---- Add banner: ask for a name first, then open the full form ---- */
		var $addModal   = $( '#acfw-add-banner-modal' );
		var $addName    = $( '#acfw-new-banner-name' );
		var $addError   = $addModal.find( '.acfw-modal-error' );
		var addReturnEl = null;

		function openAddBanner( trigger ) {
			addReturnEl = trigger || null;
			$addError.attr( 'hidden', 'hidden' );
			$addName.val( '' );
			$addModal.removeAttr( 'hidden' );
			$addName.trigger( 'focus' );
		}

		function closeAddBanner() {
			$addModal.attr( 'hidden', 'hidden' );
			if ( addReturnEl ) {
				$( addReturnEl ).trigger( 'focus' );
				addReturnEl = null;
			}
		}

		function confirmAddBanner() {
			var name = $.trim( $addName.val() );

			if ( ! name ) {
				$addError.removeAttr( 'hidden' );
				$addName.trigger( 'focus' );
				return;
			}

			// The empty state stands in for the builder until the first banner.
			$( '.acfw-empty-state' ).hide();
			$( '.acfw-builder' ).removeAttr( 'hidden' );

			closeAddBanner();
			selectItem( '__new__' );

			var $form = $( '.acfw-detail[data-key="__new__"]' );
			$form.find( 'input[name="banner_title"]' ).val( name ).trigger( 'change' ).trigger( 'focus' );
		}

		$( document ).on( 'click', '.acfw-add-banner-btn', function () {
			openAddBanner( this );
		} );

		$addModal.on( 'click', '.acfw-modal-cancel, .acfw-modal-close', closeAddBanner );
		$addModal.on( 'click', '.acfw-modal-confirm', confirmAddBanner );

		// Click the backdrop, not the dialog itself.
		$addModal.on( 'click', function ( e ) {
			if ( e.target === this ) {
				closeAddBanner();
			}
		} );

		$addName.on( 'keydown', function ( e ) {
			if ( 'Enter' === e.key ) {
				e.preventDefault();
				confirmAddBanner();
			}
		} );

		$( document ).on( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && ! $addModal.attr( 'hidden' ) ) {
				closeAddBanner();
			}
		} );

		/* Bring a Classic editor back to life after its container was hidden.
		 *
		 * wp_editor() runs while the detail pane ( and, for Block-mode items, the
		 * whole Classic wrapper ) is display:none, so TinyMCE builds its iframe
		 * with no height and never recovers on its own. Re-initialising is the
		 * only reliable repaint; hide()/show() alone leaves a 0px body.
		 *
		 * @param {string} ukey Item key with dashes already turned into underscores.
		 */
		function repaintClassicEditor( ukey ) {
			var id = 'acfw_content_' + ukey;
			var el = document.getElementById( id );

			if ( ! el || ! $( el ).closest( '.acfw_classic_editor_wrapper' ).length ) {
				return;
			}
			// Nothing to do while the wrapper is still hidden.
			if ( $( el ).closest( '.acfw_classic_editor_wrapper' ).hasClass( 'acfw_hidden' ) ) {
				return;
			}

			var ed = window.tinymce && window.tinymce.get( id );

			if ( ed && ! ed.isHidden() ) {
				// Push the current content back to the textarea before tearing down.
				ed.save();
			}

			if ( window.wp && window.wp.editor && window.wp.editor.initialize ) {
				// wp.editor.initialize() rebuilds the toolbar with only "Add Media",
				// so carry the "Add smart tags" button across the rebuild.
				// The rebuilt toolbar has no ids, so find it by class inside the wrap.
				var $smartTags = $( '#wp-' + id + '-wrap .wp-media-buttons .acfw-smarttag-wrap' ).detach();
				if ( ed ) {
					window.wp.editor.remove( id );
				}
				window.wp.editor.initialize( id, {
					tinymce: {
						wpautop: true,
						toolbar1: 'bold,italic,bullist,numlist,link,undo,redo',
					},
					quicktags: true,
					mediaButtons: true,
				} );
				if ( $smartTags.length ) {
					$( '#wp-' + id + '-wrap .wp-media-buttons' ).first().append( $smartTags );
				}
				return;
			}

			// No wp.editor API: fall back to the old repaint.
			if ( ed ) {
				ed.hide();
				ed.show();
			}
		}

		window.acfwRepaintClassic = repaintClassicEditor;

		/* ---- Select a row → show its detail form ---- */
		function byKey( selector, key ) {
			// Keys can hold "%" octets from older versions, so match on data
			// rather than building an attribute selector from the key.
			return $( selector ).filter( function () {
				return String( $( this ).data( 'key' ) ) === String( key );
			} );
		}

		/* ---- Detail sections ( General / Content / Visibility / Advanced ) ---- */
		var lastSection = 'general';

		// select2 sizes its placeholder while the pane is hidden ( 0px wide ),
		// so re-measure whenever a pane or a section becomes visible.
		function refreshSelects( $scope ) {
			$scope.find( 'select' ).each( function () {
				if ( $( this ).data( 'select2' ) ) {
					$( this ).trigger( 'change.select2' );
				}
			} );
		}

		function showSection( $detail, section, focus ) {
			var $tabs = $detail.find( '.acfw-section-tab' );
			var $tab  = $tabs.filter( '[data-section="' + section + '"]' );
			if ( ! $tab.length ) {
				// Not every item type has every section ( only endpoints have Content ).
				$tab    = $tabs.first();
				section = String( $tab.data( 'section' ) );
			}

			$tabs.removeClass( 'is-active' ).attr( { 'aria-selected': 'false', tabindex: '-1' } );
			$tab.addClass( 'is-active' ).attr( { 'aria-selected': 'true', tabindex: '0' } );
			$detail.find( '.acfw-section' ).removeClass( 'is-active' );
			var $panel = $detail.find( '.acfw-section[data-section="' + section + '"]' ).addClass( 'is-active' );

			if ( focus ) {
				$tab.trigger( 'focus' );
			}
			refreshSelects( $panel );
			if ( 'content' === section ) {
				repaintClassicEditor( String( $detail.data( 'key' ) ).replace( /-/g, '_' ) );
			}
			return section;
		}

		$( document ).on( 'click', '.acfw-section-tab', function () {
			lastSection = showSection( $( this ).closest( '.acfw-detail' ), String( $( this ).data( 'section' ) ), false );
		} );

		// Arrow keys, Home and End move between the tabs ( WAI-ARIA tabs pattern ).
		$( document ).on( 'keydown', '.acfw-section-tab', function ( e ) {
			var moves = { ArrowRight: 1, ArrowLeft: -1, Home: 'first', End: 'last' };
			if ( ! Object.prototype.hasOwnProperty.call( moves, e.key ) ) {
				return;
			}
			e.preventDefault();
			var $tabs = $( this ).closest( '.acfw-section-tabs' ).find( '.acfw-section-tab' );
			var count = $tabs.length;
			var at    = $tabs.index( this );
			var move  = moves[ e.key ];
			var next  = 'first' === move ? 0 : ( 'last' === move ? count - 1 : ( at + move + count ) % count );
			lastSection = showSection( $( this ).closest( '.acfw-detail' ), String( $tabs.eq( next ).data( 'section' ) ), true );
		} );

		function selectItem( key ) {
			$( '.acfw-node' ).removeClass( 'is-selected' );
			byKey( '.acfw-node', key ).first().addClass( 'is-selected' );

			$details.find( '.acfw-detail-empty' ).hide();
			$details.find( '.acfw-detail' ).attr( 'hidden', 'hidden' );
			var $detail = byKey( '.acfw-builder-detail .acfw-detail', key ).removeAttr( 'hidden' );
			$( '.acfw-builder-layout' ).addClass( 'is-editing' );

			// Saving the menu lands back on this item.
			$( '.acfw-selected-input' ).val( key );

			// Menu items open on the section last used, so comparing several
			// items' Visibility rules does not mean re-clicking the tab each time.
			if ( $detail.find( '.acfw-section-tab' ).length ) {
				showSection( $detail, lastSection, false );
			} else {
				refreshSelects( $detail );
				repaintClassicEditor( String( key ).replace( /-/g, '_' ) );
			}
		}

		/* ---- Live preview: the pane, its preview strip and the list row follow the fields ---- */
		function paneOf( el ) {
			return $( el ).closest( '.acfw-item-form' );
		}

		function rowOf( $pane ) {
			return byKey( '.acfw-node', $pane.data( 'key' ) ).first();
		}

		function iconMarkup( cls, url, wrapper ) {
			if ( url ) {
				return $( '<img alt="" />' ).addClass( wrapper + ' acfw-icon-img' ).attr( 'src', url );
			}
			if ( cls && -1 !== cls.indexOf( 'fa-' ) ) {
				return $( '<i></i>' ).addClass( wrapper + ' ' + cls );
			}
			return $( '<span></span>' ).addClass( wrapper + ' dashicons ' + ( cls || 'dashicons-menu-alt' ) );
		}

		function refreshIcon( $pane ) {
			var upload = 'upload' === $pane.find( '.acfw-icon-source input:checked' ).val();
			var url    = upload ? $.trim( $pane.find( '.acfw-icon-upload .acfw-media-input' ).val() || '' ) : '';
			var cls    = upload ? '' : ( $pane.find( '.acfw-icon-select' ).val() || '' );
			var $row   = rowOf( $pane );

			if ( ! url && ! cls ) {
				// Nothing picked yet: keep what the server drew ( the type's default ).
				return;
			}
			$pane.find( '.acfw-preview-icon' ).empty().append( iconMarkup( cls, url, 'acfw-preview-glyph' ) );
			$pane.find( '.acfw-detail-head .acfw-detail-icon' ).replaceWith( iconMarkup( cls, url, 'acfw-detail-icon' ) );
			$row.find( '> .acfw-node-head .acfw-node-icon' ).replaceWith( iconMarkup( cls, url, 'acfw-node-icon' ) );
		}

		$( document ).on( 'input', '.acfw-item-form .acfw-label-input', function () {
			var $pane = paneOf( this );
			$pane.find( '.acfw-detail-title, .acfw-preview-label' ).text( this.value );
			rowOf( $pane ).find( '> .acfw-node-head .acfw-node-title' ).text( this.value );
		} );

		$( document ).on( 'input', '.acfw-item-form .acfw-badge-input', function () {
			var $pane = paneOf( this );
			var text  = $.trim( this.value );
			$pane.find( '.acfw-preview-pill' ).text( text ).prop( 'hidden', ! text );
			rowOf( $pane ).find( '> .acfw-node-head .acfw-node-chip' ).text( text ).prop( 'hidden', ! text );
		} );

		$( document ).on( 'input', '.acfw-item-form .acfw-slug-input', function () {
			var $preview = paneOf( this ).find( '.acfw-preview' );
			var slug     = this.value.toLowerCase().trim().replace( /[\s_]+/g, '-' ).replace( /[^a-z0-9-]/g, '' );
			$preview.find( '.acfw-preview-url' ).text( String( $preview.data( 'base' ) || '' ) + ( slug ? slug + '/' : '' ) );
		} );

		$( document ).on( 'input', '.acfw-item-form .acfw-url-input', function () {
			var url = $.trim( this.value );
			paneOf( this ).find( '.acfw-preview-url' ).text( url ).prop( 'hidden', ! url );
		} );

		$( document ).on( 'change', '.acfw-item-form .acfw-icon-select, .acfw-item-form .acfw-icon-source input', function () {
			refreshIcon( paneOf( this ) );
		} );

		$( document ).on( 'acfw:image', '.acfw-item-form .acfw-uploader', function () {
			refreshIcon( paneOf( this ) );
		} );

		// Visibility: how many rules narrow this item down.
		function countRules( $pane ) {
			var n = 0;
			n += ( $pane.find( '.acfw-roles-select' ).val() || [] ).length ? 1 : 0;
			n += ( $pane.find( 'input[name$="[vis_from]"]' ).val() || $pane.find( 'input[name$="[vis_to]"]' ).val() ) ? 1 : 0;
			n += ( $pane.find( '.acfw-product-select' ).val() || [] ).length ? 1 : 0;
			n += parseInt( $pane.find( 'input[name$="[vis_min_orders]"]' ).val(), 10 ) > 0 ? 1 : 0;
			n += parseFloat( $pane.find( 'input[name$="[vis_min_spent]"]' ).val() ) > 0 ? 1 : 0;
			return n;
		}

		$( document ).on( 'input change', '.acfw-item-form .acfw-rule-input', function () {
			var $pane = paneOf( this );
			var n     = countRules( $pane );
			$pane.find( '.acfw-section-count' ).text( n ).prop( 'hidden', ! n );
			$pane.find( '.acfw-preview-locked' ).prop( 'hidden', ! n );
			rowOf( $pane ).find( '> .acfw-node-head .acfw-node-lock' ).prop( 'hidden', ! n );
		} );

		/* ---- Unsaved changes: say so, and warn before leaving the page ---- */
		var $itemsForm = $( '.acfw-items-form' );
		var isDirty    = false;
		var submitting = false;

		function markDirty() {
			if ( isDirty ) {
				return;
			}
			isDirty = true;
			$itemsForm.find( '.acfw-savebar' ).addClass( 'is-dirty' ).find( '.acfw-savebar-dirty' ).prop( 'hidden', false );
		}

		if ( $itemsForm.length ) {
			$itemsForm.on( 'input change', ':input', function ( e ) {
				// Searching and filtering the list changes nothing that is saved.
				if ( ! $( e.target ).closest( '.acfw-panel-tools' ).length ) {
					markDirty();
				}
			} );
			$itemsForm.on( 'sortupdate acfw:image', markDirty );
			$itemsForm.on( 'keydown', '.acfw_block_editor', markDirty );

			var watchEditor = function ( editor ) {
				if ( editor && editor.id && 0 === String( editor.id ).indexOf( 'acfw_content_' ) ) {
					editor.on( 'input change undo redo', markDirty );
				}
			};
			$( document ).on( 'tinymce-editor-init', function ( event, editor ) {
				watchEditor( editor );
			} );
			if ( window.tinymce && window.tinymce.editors ) {
				$.each( window.tinymce.editors, function ( i, editor ) {
					watchEditor( editor );
				} );
			}

			$itemsForm.on( 'submit', function () {
				submitting = true;
			} );
			$( window ).on( 'beforeunload', function ( e ) {
				if ( isDirty && ! submitting ) {
					e.preventDefault();
					e.originalEvent.returnValue = '';
					return '';
				}
			} );
		}

		/* ---- Header overflow menu ---- */
		$( document ).on( 'click', '.acfw-more-toggle', function ( e ) {
			e.stopPropagation();
			var $btn  = $( this );
			var $menu = $btn.next( '.acfw-more-menu' );
			var open  = ! $menu.attr( 'hidden' );
			$menu.attr( 'hidden', open ? 'hidden' : null );
			$btn.attr( 'aria-expanded', open ? 'false' : 'true' );
		} );

		$( document ).on( 'click', function () {
			$( '.acfw-more-menu' ).attr( 'hidden', 'hidden' );
			$( '.acfw-more-toggle' ).attr( 'aria-expanded', 'false' );
		} );

		$( document ).on( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				$( '.acfw-more-menu' ).attr( 'hidden', 'hidden' );
				$( '.acfw-more-toggle' ).attr( 'aria-expanded', 'false' );
			}
		} );

		/* ---- Search + "enabled only" filter over the menu-item list ---- */
		function nodeMatches( $node, term, only ) {
			var label = $node.find( '> .acfw-node-head .acfw-node-title' ).first().text().toLowerCase();
			return ( ! term || -1 !== label.indexOf( term ) ) && ( ! only || ! $node.hasClass( 'is-inactive' ) );
		}

		function filterItems() {
			var term    = ( $( '.acfw-item-search' ).val() || '' ).toLowerCase().trim();
			var only    = 'true' === $( '.acfw-filter-toggle' ).attr( 'aria-pressed' );
			var visible = 0;

			$( '.acfw-sortable-root > .acfw-node' ).each( function () {
				var $node = $( this );
				var show  = nodeMatches( $node, term, only );
				var $kids = $node.find( '> .acfw-sortable-children > .acfw-node' );

				// A group stays listed when one of its items matches.
				if ( $kids.length ) {
					var groupHit = show;
					var kidHits  = 0;
					$kids.each( function () {
						var $kid = $( this );
						var hit  = groupHit ? ( ! only || ! $kid.hasClass( 'is-inactive' ) ) : nodeMatches( $kid, term, only );
						$kid.toggle( hit );
						kidHits += hit ? 1 : 0;
					} );
					show = groupHit || ( kidHits > 0 && ( ! only || ! $node.hasClass( 'is-inactive' ) ) );
				}

				$node.toggle( show );
				if ( show ) {
					visible++;
				}
			} );

			$( '.acfw-list-empty' ).attr( 'hidden', visible ? 'hidden' : null );
			// Reordering a filtered list would save a misleading order.
			$( '.acfw-list-hint' ).toggle( ! term && ! only );
		}

		$( document ).on( 'input', '.acfw-item-search', filterItems );

		$( document ).on( 'click', '.acfw-filter-toggle', function () {
			var $btn = $( this );
			$btn.attr( 'aria-pressed', 'true' === $btn.attr( 'aria-pressed' ) ? 'false' : 'true' );
			$btn.toggleClass( 'is-active' );
			filterItems();
		} );

		/* ---- Pencil opens the same detail pane as clicking the row ---- */
		$( document ).on( 'click', '.acfw-node-edit', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			selectItem( $( this ).data( 'key' ) );
		} );

		/* ---- Back to menu: collapse the detail pane, useful on narrow screens ---- */
		$( document ).on( 'click', '.acfw-back-to-menu', function () {
			$( '.acfw-node' ).removeClass( 'is-selected' );
			$details.find( '.acfw-detail' ).attr( 'hidden', 'hidden' );
			$details.find( '.acfw-detail-empty' ).show();
			$( '.acfw-builder-layout' ).removeClass( 'is-editing' );
			$( 'html, body' ).animate( { scrollTop: $( '.acfw-builder-list' ).offset().top - 60 }, 200 );
		} );

		$( document ).on( 'click', '.acfw-node-head', function ( e ) {
			if ( $( e.target ).closest( '.acfw-drag, .acfw-node-remove, .acfw-node-duplicate, .acfw-switch, .acfw-banner-row-delete' ).length ) {
				return;
			}
			selectItem( $( this ).closest( '.acfw-node' ).data( 'key' ) );
		} );

		/* ---- On load, reopen the item a save came from, else the first one ---- */
		var wanted     = window.URLSearchParams ? new window.URLSearchParams( window.location.search ).get( 'select' ) : '';
		var $firstNode = $( '.acfw-sortable-root > .acfw-node' ).first();
		if ( wanted && byKey( '.acfw-node', wanted ).length ) {
			selectItem( wanted );
		} else if ( $firstNode.length ) {
			selectItem( $firstNode.data( 'key' ) );
		} else if ( $( '.acfw-detail[data-key="__new__"]' ).length ) {
			selectItem( '__new__' );
		}

		/* ---- Active on/off toggle (saved with the single Save button) ---- */
		$( document ).on( 'change', '.acfw-active-proxy', function () {
			var key = $( this ).data( 'key' );
			var on = this.checked;
			$( this ).closest( '.acfw-node' ).toggleClass( 'is-inactive', ! on );
			var $pane = byKey( '.acfw-builder-detail .acfw-detail', key );
			$pane.find( '.acfw-active-input' ).val( on ? '1' : '0' );
			$pane.find( '.acfw-preview' ).toggleClass( 'is-off', ! on );
		} );

		/* ---- Radio-box groups (reference-style radio controls) ---- */
		$( document ).on( 'change', '.acfw-radio-group input[type="radio"]', function () {
			$( this ).closest( '.acfw-radio-group' ).find( '.acfw-radio-box, .acfw-image-card' ).removeClass( 'is-active' );
			$( this ).closest( '.acfw-radio-box, .acfw-image-card' ).addClass( 'is-active' );
		} );

		/* ---- Banner form: show only the controls that apply ---- */
		// Widget vs image, the link fields for the chosen link type, the link
		// text only when there is a link, the badge source only with a badge.
		function refreshBannerForm( $form ) {
			var type   = $form.find( 'input[name="banner_type"]:checked' ).val() || 'widget';
			var link   = $form.find( 'input[name="banner_link_type"]:checked' ).val() || 'none';
			var widget = 'widget' === type;

			$form.find( '.acfw-btype-widget' ).attr( 'hidden', widget ? null : 'hidden' );
			$form.find( '.acfw-btype-image' ).attr( 'hidden', widget ? 'hidden' : null );
			$form.find( '.acfw-blink-endpoint' ).attr( 'hidden', 'endpoint' === link ? null : 'hidden' );
			$form.find( '.acfw-blink-external' ).attr( 'hidden', 'external' === link ? null : 'hidden' );
			$form.find( '.acfw-blink-text' ).attr( 'hidden', widget && 'none' !== link ? null : 'hidden' );
			$form.find( '.acfw-bcount' ).attr( 'hidden', widget && $form.find( '.acfw-banner-count-toggle' ).is( ':checked' ) ? null : 'hidden' );

			$form.find( '.acfw-detail-head .acfw-node-badge' )
				.attr( 'class', 'acfw-node-badge acfw-badge-' + type )
				.text( type );
		}

		$( document ).on( 'change', 'input[name="banner_type"], input[name="banner_link_type"], .acfw-banner-count-toggle', function () {
			refreshBannerForm( $( this ).closest( '.acfw-detail' ) );
		} );

		// Initial state for every banner form on the page.
		$( '.acfw-detail:has(input[name="banner_type"])' ).each( function () {
			refreshBannerForm( $( this ) );
		} );

		/* ---- Endpoint URL: keep it to what a URL slug can hold ---- */
		$( document ).on( 'change', '.acfw-slug-input', function () {
			this.value = this.value.toLowerCase()
				.trim()
				.replace( /[\s_]+/g, '-' )
				.replace( /[^a-z0-9-]/g, '' )
				.replace( /-+/g, '-' )
				.replace( /^-|-$/g, '' );
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
			// The value was set in code, which fires no change event.
			$wrap.trigger( 'acfw:image', [ url ] );
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
			if ( ! window.confirm( acfwAdmin.confirmReset || acfwAdmin.confirmDelete ) ) {
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

		/* ---- Delete banner from the left list row ---- */
		$( document ).on( 'click', '.acfw-banner-row-delete', function ( e ) {
			e.stopPropagation();
			if ( ! window.confirm( acfwAdmin.confirmDelete ) ) {
				return;
			}
			var slug = $( this ).data( 'slug' );
			$( '.acfw-detail[data-key="' + slug + '"] form.acfw-banner-form' )
				.find( 'input[name="acfw_action"]' ).val( 'remove_banner' ).end()
				.trigger( 'submit' );
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
