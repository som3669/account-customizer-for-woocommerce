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
			// The name field is under General.
			lastSection = showSection( $form, 'general', false );
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
			var $all  = $detail.find( '.acfw-section-tab' );
			var $tabs = $all.not( '[hidden]' );
			var $tab  = $tabs.filter( '[data-section="' + section + '"]' );
			if ( ! $tab.length ) {
				// Not every pane has every section ( only endpoints have Content,
				// only widget banners have Style ).
				$tab    = $tabs.first();
				section = String( $tab.data( 'section' ) );
			}

			$all.removeClass( 'is-active' ).attr( { 'aria-selected': 'false', tabindex: '-1' } );
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
			var $tabs = $( this ).closest( '.acfw-section-tabs' ).find( '.acfw-section-tab' ).not( '[hidden]' );
			var count = $tabs.length;
			var at    = $tabs.index( this );
			var move  = moves[ e.key ];
			var next  = 'first' === move ? 0 : ( 'last' === move ? count - 1 : ( at + move + count ) % count );
			lastSection = showSection( $( this ).closest( '.acfw-detail' ), String( $tabs.eq( next ).data( 'section' ) ), true );
		} );

		function selectItem( key ) {
			$( '.acfw-node' ).removeClass( 'is-selected' );
			var $node = byKey( '.acfw-node', key ).first().addClass( 'is-selected' );

			// The canvas marks the item being edited the way the storefront marks
			// the page a customer is on.
			$( '.acfw-canvas-menu .acfw-menu-item' ).removeClass( 'is-active' ).children( '.acfw-canvas-link' ).attr( 'aria-pressed', 'false' );
			$node.filter( '.acfw-menu-item' ).addClass( 'is-active' ).children( '.acfw-canvas-link' ).attr( 'aria-pressed', 'true' );

			$details.find( '.acfw-detail-empty' ).hide();
			$details.find( '.acfw-detail' ).attr( 'hidden', 'hidden' );
			var $detail = byKey( '.acfw-builder-detail .acfw-detail', key ).removeAttr( 'hidden' );
			$( '.acfw-builder-layout, .acfw-canvas-layout' ).addClass( 'is-editing' );

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

		/* ---- Live preview: the canvas row and the inspector follow the fields ---- */
		function paneOf( el ) {
			return $( el ).closest( '.acfw-item-form' );
		}

		function rowOf( $pane ) {
			return byKey( '.acfw-node', $pane.data( 'key' ) ).first();
		}

		// A row's own link ( a group's toggle ), not the links of its children.
		function linkOf( $row ) {
			return $row.children( '.acfw-canvas-link' );
		}

		// "%s", "%d", "%1$s" and "%2$s" in a translated string, in one pass, so a
		// "$" or "%s" typed in a label is never read as a placeholder.
		function fmt( tpl, a, b ) {
			return String( tpl || '' ).replace( /%([12])\$[sd]|%[sd]/g, function ( match, n ) {
				return String( '2' === n ? b : a );
			} );
		}

		// An amount the way wc_price() prints it: the store's decimals,
		// separators and currency position.
		function money( raw ) {
			var c     = acfwAdmin.canvas || {};
			var parts = parseFloat( raw ).toFixed( undefined === c.decimals ? 2 : c.decimals ).split( '.' );
			parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, c.thousandSep || '' );
			return fmt( c.price || '%1$s%2$s', c.currency || '', parts.join( c.decimalSep || '.' ) );
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
			var $link  = linkOf( rowOf( $pane ) );

			if ( ! url && ! cls ) {
				// Nothing picked yet: keep what the server drew ( the type's default ).
				return;
			}
			$pane.find( '.acfw-detail-head .acfw-detail-icon' ).replaceWith( iconMarkup( cls, url, 'acfw-detail-icon' ) );
			if ( $link.children( '.acfw-icon' ).length ) {
				$link.children( '.acfw-icon' ).replaceWith( iconMarkup( cls, url, 'acfw-icon' ) );
			} else {
				$link.prepend( iconMarkup( cls, url, 'acfw-icon' ) );
			}
		}

		$( document ).on( 'input', '.acfw-item-form .acfw-label-input', function () {
			var $pane = paneOf( this );
			$pane.find( '.acfw-detail-title' ).text( this.value );
			linkOf( rowOf( $pane ) ).children( '.acfw-label' ).text( this.value );
		} );

		$( document ).on( 'input', '.acfw-item-form .acfw-badge-input', function () {
			var text = $.trim( this.value );
			linkOf( rowOf( paneOf( this ) ) ).children( '.acfw-count' ).text( text ).prop( 'hidden', ! text );
		} );

		$( document ).on( 'input', '.acfw-item-form .acfw-slug-input', function () {
			var $meta = paneOf( this ).find( '.acfw-inspector-meta' );
			var slug  = this.value.toLowerCase().trim().replace( /[\s_]+/g, '-' ).replace( /[^a-z0-9-]/g, '' );
			$meta.find( '.acfw-inspector-url' ).text( String( $meta.data( 'base' ) || '' ) + ( slug ? slug + '/' : '' ) );
		} );

		$( document ).on( 'input', '.acfw-item-form .acfw-url-input', function () {
			var url = $.trim( this.value );
			paneOf( this ).find( '.acfw-inspector-url' ).text( url ).prop( 'hidden', ! url );
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
			n += '' !== $.trim( $pane.find( 'input[name$="[vis_max_orders]"]' ).val() || '' ) ? 1 : 0;
			n += parseFloat( $pane.find( 'input[name$="[vis_min_spent]"]' ).val() ) > 0 ? 1 : 0;
			n += parseInt( $pane.find( 'input[name$="[vis_inactive_days]"]' ).val(), 10 ) > 0 ? 1 : 0;
			return n;
		}

		// "j M", the way date_i18n() prints it for the chip on the server.
		function shortDay( value ) {
			var m      = /^(\d{4})-(\d{2})-(\d{2})$/.exec( value || '' );
			var months = ( acfwAdmin.canvas && acfwAdmin.canvas.months ) || [];
			if ( ! m ) {
				return value;
			}
			return parseInt( m[ 3 ], 10 ) + ' ' + ( months[ parseInt( m[ 2 ], 10 ) - 1 ] || m[ 2 ] );
		}

		// Who can see an item, in words ( the chip on its row ). Mirrors
		// ACFW_Tab_Items::rule_summary().
		function ruleSummary( $pane ) {
			var c        = acfwAdmin.canvas || {};
			var parts    = [];
			var roles    = $pane.find( '.acfw-roles-select option:selected' ).map( function () {
				return $.trim( $( this ).text() );
			} ).get();
			var orders   = parseInt( $pane.find( 'input[name$="[vis_min_orders]"]' ).val(), 10 ) || 0;
			var maxRaw   = $.trim( $pane.find( 'input[name$="[vis_max_orders]"]' ).val() || '' );
			var idle     = parseInt( $pane.find( 'input[name$="[vis_inactive_days]"]' ).val(), 10 ) || 0;
			var spent    = $.trim( $pane.find( 'input[name$="[vis_min_spent]"]' ).val() || '' );
			var products = ( $pane.find( '.acfw-product-select' ).val() || [] ).length;
			var from     = $pane.find( 'input[name$="[vis_from]"]' ).val();
			var to       = $pane.find( 'input[name$="[vis_to]"]' ).val();

			if ( roles.length ) {
				parts.push( roles.join( ', ' ) );
			}
			if ( orders > 0 ) {
				parts.push( fmt( 1 === orders ? c.order : c.orders, orders ) );
			}
			if ( '' !== maxRaw ) {
				var max = Math.max( 0, parseInt( maxRaw, 10 ) || 0 );
				parts.push( 0 === max ? c.noOrders : fmt( 1 === max ? c.maxOrder : c.maxOrders, max ) );
			}
			if ( idle > 0 ) {
				parts.push( fmt( 1 === idle ? c.idleDay : c.idleDays, idle ) );
			}
			if ( parseFloat( spent ) > 0 ) {
				parts.push( fmt( c.spent, money( spent ) ) );
			}
			if ( 1 === products ) {
				parts.push( c.product );
			} else if ( products > 1 ) {
				parts.push( fmt( c.products, products ) );
			}
			if ( from && to ) {
				parts.push( fmt( c.range, shortDay( from ), shortDay( to ) ) );
			} else if ( from ) {
				parts.push( fmt( c.from, shortDay( from ) ) );
			} else if ( to ) {
				parts.push( fmt( c.until, shortDay( to ) ) );
			}
			return parts;
		}

		$( document ).on( 'input change', '.acfw-item-form .acfw-rule-input', function () {
			var $pane = paneOf( this );
			var n     = countRules( $pane );
			var text  = ruleSummary( $pane ).join( ' \u00b7 ' );
			var $chip = linkOf( rowOf( $pane ) ).children( '.acfw-canvas-rules' );

			$pane.find( '.acfw-section-count' ).text( n ).prop( 'hidden', ! n );
			$chip.prop( 'hidden', ! text ).attr( 'title', text ).find( '.acfw-canvas-rules-text' ).text( text );
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
				// Searching the menu, or typing a new item's label, changes nothing
				// that is saved ( "Add item" saves on its own ).
				if ( ! $( e.target ).closest( '.acfw-panel-tools, .acfw-add-pop' ).length ) {
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
			var label = $node.children( '.acfw-canvas-link' ).children( '.acfw-label' ).text().toLowerCase();
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

			// Moving items in a filtered menu would save a misleading order.
			var filtering = !! ( term || only );
			$( '.acfw-canvas' ).toggleClass( 'is-filtering', filtering );
			$( '.acfw-canvas .acfw-sortable.ui-sortable' ).sortable( 'option', 'disabled', filtering );
		}

		$( document ).on( 'input', '.acfw-item-search', filterItems );

		$( document ).on( 'click', '.acfw-filter-toggle', function () {
			var $btn = $( this );
			$btn.attr( 'aria-pressed', 'true' === $btn.attr( 'aria-pressed' ) ? 'false' : 'true' );
			$btn.toggleClass( 'is-active' );
			filterItems();
		} );

		/* ---- Back to menu: collapse the detail pane, useful on narrow screens ---- */
		$( document ).on( 'click', '.acfw-back-to-menu', function () {
			$( '.acfw-node' ).removeClass( 'is-selected' );
			$details.find( '.acfw-detail' ).attr( 'hidden', 'hidden' );
			$details.find( '.acfw-detail-empty' ).show();
			$( '.acfw-canvas-menu .acfw-menu-item' ).removeClass( 'is-active' ).children( '.acfw-canvas-link' ).attr( 'aria-pressed', 'false' );
			$( '.acfw-builder-layout, .acfw-canvas-layout' ).removeClass( 'is-editing' );
			$( 'html, body' ).animate( { scrollTop: $( '.acfw-builder-list, .acfw-canvas' ).first().offset().top - 60 }, 200 );
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
			// The row's switch and the inspector's stay in step.
			byKey( '.acfw-active-proxy', key ).not( this ).prop( 'checked', on );
			byKey( '.acfw-node', key ).toggleClass( 'is-inactive', ! on );
			var $pane = byKey( '.acfw-builder-detail .acfw-detail', key );
			$pane.find( '.acfw-active-input' ).val( on ? '1' : '0' );
			$pane.find( '.acfw-inspector-off' ).prop( 'hidden', on );
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

			// Style and Offer are for widget banners.
			var $widgetTabs = $form.find( '.acfw-section-tab[data-section="style"], .acfw-section-tab[data-section="offer"]' ).prop( 'hidden', ! widget );
			if ( ! widget && $widgetTabs.filter( '.is-active' ).length ) {
				lastSection = showSection( $form, 'general', false );
			}
			$form.find( '.acfw-offer-field' ).attr( 'hidden', $form.find( '.acfw-offer-toggle' ).is( ':checked' ) ? null : 'hidden' );

			$form.find( '.acfw-detail-head .acfw-node-badge' )
				.attr( 'class', 'acfw-node-badge acfw-badge-' + type )
				.text( type );
		}

		// Banner Visibility: how many rules narrow it down ( roles, dates ).
		$( document ).on( 'input change', '.acfw-banner-form .acfw-rule-input', function () {
			var $form = $( this ).closest( '.acfw-banner-form' );
			var n     = ( $form.find( '.acfw-roles-select' ).val() || [] ).length ? 1 : 0;
			n += ( $form.find( 'input[name="banner_vis_from"]' ).val() || $form.find( 'input[name="banner_vis_to"]' ).val() ) ? 1 : 0;
			n += ( $form.find( '.acfw-product-select' ).val() || [] ).length ? 1 : 0;
			n += parseInt( $form.find( 'input[name="banner_vis_min_orders"]' ).val(), 10 ) > 0 ? 1 : 0;
			n += '' !== $.trim( $form.find( 'input[name="banner_vis_max_orders"]' ).val() || '' ) ? 1 : 0;
			n += parseFloat( $form.find( 'input[name="banner_vis_min_spent"]' ).val() ) > 0 ? 1 : 0;
			n += parseInt( $form.find( 'input[name="banner_vis_inactive_days"]' ).val(), 10 ) > 0 ? 1 : 0;
			$form.find( '.acfw-section-count' ).text( n ).prop( 'hidden', ! n );
		} );

		$( document ).on( 'change', 'input[name="banner_type"], input[name="banner_link_type"], .acfw-banner-count-toggle, .acfw-offer-toggle', function () {
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
			var $form = $( '.acfw-delete-form' );
			if ( ! window.confirm( $form.data( 'confirm' ) || acfwAdmin.confirmDelete ) ) {
				return;
			}
			$form.find( '.acfw-delete-key' ).val( $( this ).data( 'key' ) ).end().trigger( 'submit' );
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

		/* ---- How to use: the guide panel ---- */
		var $guide      = $( '#acfw-guide' );
		var guideReturn = null;
		function openGuide() {
			guideReturn = document.activeElement;
			$guide.prop( 'hidden', false );
			$( 'body' ).addClass( 'acfw-guide-open' );
			$( '.acfw-guide-toggle' ).attr( 'aria-expanded', 'true' );
			var $open = $guide.find( '.acfw-guide-section[open]' ).first();
			if ( $open.length ) {
				$open.get( 0 ).scrollIntoView( { block: 'nearest' } );
			}
			$guide.find( '.acfw-guide-search-input' ).trigger( 'focus' );
		}
		function closeGuide() {
			if ( $guide.prop( 'hidden' ) ) {
				return;
			}
			$guide.prop( 'hidden', true );
			$( 'body' ).removeClass( 'acfw-guide-open' );
			$( '.acfw-guide-toggle' ).attr( 'aria-expanded', 'false' );
			if ( guideReturn ) {
				$( guideReturn ).trigger( 'focus' );
				guideReturn = null;
			}
		}
		$( document ).on( 'click', '.acfw-guide-toggle', function () {
			if ( $guide.prop( 'hidden' ) ) {
				openGuide();
			} else {
				closeGuide();
			}
		} );
		$guide.on( 'click', '[data-acfw-guide-close]', closeGuide );
		// A click on the dimmed page, not the panel, closes it.
		$guide.on( 'click', function ( e ) {
			if ( e.target === this ) {
				closeGuide();
			}
		} );
		$( document ).on( 'keydown', function ( e ) {
			if ( $guide.prop( 'hidden' ) ) {
				return;
			}
			if ( 'Escape' === e.key ) {
				closeGuide();
				return;
			}
			// Tab stays inside the panel.
			if ( 'Tab' === e.key ) {
				var $stops = $guide.find( 'input, summary, a[href], button' ).filter( ':visible' );
				var first  = $stops.get( 0 );
				var last   = $stops.get( $stops.length - 1 );
				if ( e.shiftKey && document.activeElement === first ) {
					e.preventDefault();
					last.focus();
				} else if ( ! e.shiftKey && document.activeElement === last ) {
					e.preventDefault();
					first.focus();
				}
			}
		} );
		// Search: sections with the words stay, and open; the rest hide.
		$guide.on( 'input', '.acfw-guide-search-input', function () {
			var words = $.trim( this.value ).toLowerCase().split( /\s+/ ).filter( Boolean );
			$guide.find( '.acfw-guide-section' ).each( function () {
				var text  = this.textContent.toLowerCase();
				var match = words.every( function ( w ) {
					return -1 !== text.indexOf( w );
				} );
				this.hidden = ! match;
				if ( words.length && match ) {
					this.open = true;
				}
			} );
			$guide.find( '.acfw-guide-empty' ).prop( 'hidden', !! $guide.find( '.acfw-guide-section:not([hidden])' ).length );
		} );

		/* ---- View as a customer: the customer picker of the previews ---- */
		var viewAs = acfwAdmin.viewAs || {};
		function initViewAs( $select, $parent ) {
			if ( ! $.fn.select2 || $select.data( 'select2' ) ) {
				return;
			}
			$select.select2( {
				width: '260px',
				dropdownParent: $parent && $parent.length ? $parent : $( document.body ),
				minimumInputLength: 0,
				language: {
					noResults: function () {
						return viewAs.none;
					},
					searching: function () {
						return viewAs.searching;
					},
					inputTooShort: function () {
						return viewAs.search;
					},
				},
				ajax: {
					url: acfwAdmin.ajaxUrl,
					type: 'POST',
					dataType: 'json',
					delay: 250,
					data: function ( params ) {
						return { action: 'acfw_view_as_search', nonce: viewAs.nonce, term: params.term || '' };
					},
					processResults: function ( res, params ) {
						var list = ( res && res.success && res.data && res.data.results ) || [];
						if ( ! params.term ) {
							list.unshift( { id: 0, text: viewAs.self, url: '' } );
						}
						return { results: list };
					},
				},
				templateResult: function ( item ) {
					if ( ! item.id || ! item.roles ) {
						return item.text;
					}
					return $( '<span class="acfw-va-option"></span>' )
						.append( $( '<span class="acfw-va-name"></span>' ).text( item.text ) )
						.append( $( '<span class="acfw-va-roles"></span>' ).text( item.roles ) );
				},
			} );
			// Tell whoever shows the preview which page to open ( '' = yourself ).
			$select.on( 'select2:select', function ( e ) {
				var d = ( e.params && e.params.data ) || {};
				$select.trigger( 'acfw:viewas', [ d.url || '', d.id ? d.text : '' ] );
			} );
		}
		$( '.acfw-view-as-select' ).each( function () {
			initViewAs( $( this ), $( this ).closest( '.acfw-view-as' ) );
		} );

		/* ---- Preview overlay ( the account page in an iframe, as you or a customer ) ---- */
		function closePreview() {
			$( '.acfw-preview-overlay' ).remove();
			$( 'body' ).removeClass( 'acfw-preview-open' );
		}
		$( document ).on( 'click', '.acfw-preview-btn', function () {
			var url = $( this ).data( 'url' );
			if ( ! url ) {
				return;
			}
			var $ov = $(
				'<div class="acfw-preview-overlay" role="dialog" aria-modal="true">' +
					'<div class="acfw-preview-frame">' +
						'<div class="acfw-preview-head acfw-view-as">' +
							'<label class="acfw-view-as-label" for="acfw-view-as-overlay"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></label>' +
							'<select id="acfw-view-as-overlay" class="acfw-view-as-select"><option value="0" selected></option></select>' +
							'<span class="acfw-preview-spacer"></span>' +
							'<button type="button" class="acfw-preview-close">&times;</button>' +
						'</div>' +
						'<iframe></iframe>' +
					'</div>' +
				'</div>'
			);
			$ov.attr( 'aria-label', viewAs.label || '' );
			$ov.find( '.acfw-view-as-label' ).append( document.createTextNode( viewAs.label || '' ) );
			$ov.find( 'option' ).text( viewAs.self || '' );
			$ov.find( '.acfw-preview-close' ).attr( 'aria-label', viewAs.close || 'Close' );
			$ov.find( 'iframe' ).attr( 'src', url );
			$( 'body' ).append( $ov ).addClass( 'acfw-preview-open' );
			var $pick = $ov.find( '.acfw-view-as-select' );
			initViewAs( $pick, $ov.find( '.acfw-preview-head' ) );
			$pick.on( 'acfw:viewas', function ( e, target ) {
				$ov.find( 'iframe' ).attr( 'src', target || url );
			} );
			$ov.find( '.acfw-preview-close' ).trigger( 'focus' );
		} );
		$( document ).on( 'click', '.acfw-preview-overlay, .acfw-preview-close', function ( e ) {
			if ( e.target === this ) {
				closePreview();
			}
		} );
		$( document ).on( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && $( '.acfw-preview-overlay' ).length && ! $( '.select2-container--open' ).length ) {
				closePreview();
			}
		} );

		/* ---- Dialogs opened by a button ( group menus ) ---- */
		var dialogReturn = null;
		$( document ).on( 'click', '[data-acfw-open]', function () {
			dialogReturn = this;
			var $dialog = $( '#' + $( this ).data( 'acfw-open' ) ).prop( 'hidden', false );
			$( 'body' ).addClass( 'acfw-dialog-open' );
			$dialog.find( 'input[type="text"]' ).first().trigger( 'focus' );
		} );
		function closeDialogs() {
			$( '.acfw-dialog' ).prop( 'hidden', true );
			$( 'body' ).removeClass( 'acfw-dialog-open' );
			if ( dialogReturn ) {
				$( dialogReturn ).trigger( 'focus' );
				dialogReturn = null;
			}
		}
		$( document ).on( 'click', '.acfw-dialog [data-acfw-close]', closeDialogs );
		$( document ).on( 'click', '.acfw-dialog', function ( e ) {
			if ( e.target === this ) {
				closeDialogs();
			}
		} );
		$( document ).on( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && $( '.acfw-dialog:not([hidden])' ).length ) {
				closeDialogs();
			}
		} );

		// Buttons that ask first ( data-acfw-confirm ).
		$( document ).on( 'click', '[data-acfw-confirm]', function ( e ) {
			if ( ! window.confirm( String( $( this ).data( 'acfw-confirm' ) ) ) ) {
				e.preventDefault();
				e.stopImmediatePropagation();
			}
		} );

		/* ---- Fields tab: add, remove, reorder, key from label ---- */
		var $fieldsList = $( '.acfw-fields-list' );
		if ( $fieldsList.length ) {
			var syncFieldsEmpty = function () {
				$( '.acfw-fields-empty' ).prop( 'hidden', !! $fieldsList.children( '.acfw-field-card' ).length );
			};
			$( document ).on( 'click', '.acfw-fields-add', function () {
				var next = parseInt( $fieldsList.attr( 'data-next' ), 10 ) || 0;
				var html = document.getElementById( 'acfw-field-template' ).innerHTML.replace( /__i__/g, String( next ) );
				$fieldsList.attr( 'data-next', next + 1 ).append( html );
				syncFieldsEmpty();
				markDirty();
				$fieldsList.children( '.acfw-field-card' ).last().find( '.acfw-fc-label' ).trigger( 'focus' );
			} );
			$( document ).on( 'click', '.acfw-field-remove', function () {
				$( this ).closest( '.acfw-field-card' ).remove();
				syncFieldsEmpty();
				markDirty();
			} );
			$( document ).on( 'input', '.acfw-fc-label', function () {
				var $card = $( this ).closest( '.acfw-field-card' );
				$card.find( '.acfw-field-card-title' ).text( this.value || '…' );
				if ( $card.is( '[data-fresh]' ) ) {
					var key = this.value.toLowerCase().normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' ).replace( /[^a-z0-9]+/g, '_' ).replace( /^_+|_+$/g, '' ).slice( 0, 30 );
					$card.find( '.acfw-fc-key' ).val( key );
					$card.find( '.acfw-field-card-tag' ).text( key ? '{field_' + key + '}' : '' );
				}
			} );
			$( document ).on( 'input', '.acfw-fc-key', function () {
				var $card = $( this ).closest( '.acfw-field-card' ).removeAttr( 'data-fresh' );
				$card.find( '.acfw-field-card-tag' ).text( this.value ? '{field_' + this.value + '}' : '' );
			} );
			$( document ).on( 'change', '.acfw-fc-type', function () {
				$( this ).closest( '.acfw-field-card' ).find( '.acfw-fc-options' ).prop( 'hidden', -1 === [ 'select', 'radio' ].indexOf( this.value ) );
			} );
			if ( $.fn.sortable ) {
				$fieldsList.sortable( { handle: '.acfw-field-drag', items: '> .acfw-field-card', update: markDirty } );
			}
		}

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

		/* ---- Menu canvas: the customer's menu, edited in place ---- */
		var $canvas = $( '.acfw-canvas' );
		var canvasText = acfwAdmin.canvas || {};

		function canvasLabel( $node ) {
			return $.trim( $node.children( '.acfw-canvas-link' ).children( '.acfw-label' ).text() );
		}

		function speak( message ) {
			if ( message && window.wp && window.wp.a11y && window.wp.a11y.speak ) {
				window.wp.a11y.speak( message, 'assertive' );
			}
		}

		// An empty group shows where to drop items into it.
		function refreshGroups() {
			$canvas.find( '.acfw-sortable-children' ).each( function () {
				$( this ).toggleClass( 'is-empty', ! $( this ).children( '.acfw-node' ).length );
			} );
		}
		refreshGroups();

		// Click ( or Enter / Space ) opens the item in the inspector.
		$( document ).on( 'click', '.acfw-canvas-link', function ( e ) {
			e.preventDefault();
			selectItem( $( this ).closest( '.acfw-node' ).data( 'key' ) );
			// jQuery UI cancels the mousedown ( the row is a drag handle ), which
			// also stops the click from focusing it; without focus, Alt + arrow
			// keys had nothing to move. Focus from a mouse click ( detail > 0 )
			// is marked so it draws no keyboard ring.
			$( this ).toggleClass( 'is-pointer-focus', !! ( e.originalEvent && e.originalEvent.detail ) );
			this.focus( { preventScroll: true } );
			// Stacked layout: the inspector is below the menu, so bring it up.
			if ( window.matchMedia && window.matchMedia( '(max-width: 900px)' ).matches ) {
				var $open = $details.find( '.acfw-detail:not([hidden])' );
				if ( $open.length ) {
					$open[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'start' } );
				}
			}
		} );

		/* Alt + arrow keys move the focused item: up and down among its
		 * neighbours, right into the group above it, left out of its group. */
		function moveNode( $node, key ) {
			var $list  = $node.parent();
			var nested = $list.hasClass( 'acfw-sortable-children' );
			var $prev  = $node.prev( '.acfw-node' );
			var $next  = $node.next( '.acfw-node' );
			var label  = canvasLabel( $node );

			if ( 'ArrowUp' === key && $prev.length ) {
				$node.insertBefore( $prev );
				return fmt( canvasText.movedUp, label );
			}
			if ( 'ArrowDown' === key && $next.length ) {
				$node.insertAfter( $next );
				return fmt( canvasText.movedDown, label );
			}
			if ( 'ArrowRight' === key && ! nested && 'group' !== $node.data( 'type' ) && 'group' === $prev.data( 'type' ) ) {
				$prev.children( '.acfw-sortable-children' ).append( $node );
				return fmt( canvasText.movedIn, label, canvasLabel( $prev ) );
			}
			if ( 'ArrowLeft' === key && nested ) {
				$node.insertAfter( $list.closest( '.acfw-node' ) );
				return fmt( canvasText.movedOut, label );
			}
			return '';
		}

		// The ring comes back once the keyboard is in use, and goes with the focus.
		$( document ).on( 'focusout', '.acfw-canvas-link', function () {
			$( this ).removeClass( 'is-pointer-focus' );
		} );

		$( document ).on( 'keydown', '.acfw-canvas-link', function ( e ) {
			$( this ).removeClass( 'is-pointer-focus' );
			// A link acting as a button answers Space too.
			if ( ' ' === e.key && 'A' === this.tagName && ! e.altKey ) {
				e.preventDefault();
				$( this ).trigger( 'click' );
				return;
			}
			var arrows = [ 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight' ];
			if ( ! e.altKey || -1 === arrows.indexOf( e.key ) ) {
				return;
			}
			// Alt + Left / Right would otherwise go Back / Forward.
			e.preventDefault();

			var key = e.key;
			if ( 'rtl' === document.documentElement.dir && ( 'ArrowLeft' === key || 'ArrowRight' === key ) ) {
				key = 'ArrowLeft' === key ? 'ArrowRight' : 'ArrowLeft';
			}
			var $node = $( this ).closest( '.acfw-node' );
			var moved = $canvas.hasClass( 'is-filtering' ) ? '' : moveNode( $node, key );

			if ( ! moved ) {
				speak( canvasText.cantMove );
				return;
			}
			refreshGroups();
			markDirty();
			// Moving an element takes the focus off it.
			$node.children( '.acfw-canvas-link' ).trigger( 'focus' );
			speak( moved );
		} );

		/* ---- Drag & drop (nestable, 1 level into groups) ---- */
		$canvas.find( '.acfw-sortable' ).sortable( {
			// The row itself is the handle; a short move tells a drag from a click.
			handle: '> .acfw-canvas-link',
			cancel: 'input, textarea, select, option',
			distance: 6,
			items: '> .acfw-node',
			placeholder: 'acfw-canvas-placeholder',
			forcePlaceholderSize: true,
			connectWith: '.acfw-canvas .acfw-sortable',
			tolerance: 'pointer',
			cursor: 'grabbing',
			start: function ( event, ui ) {
				var group = 'group' === ui.item.data( 'type' );
				$canvas.addClass( 'is-dragging' ).toggleClass( 'is-dragging-group', group );
				// Groups do not go inside groups, so their lists sit this one out.
				if ( group ) {
					$canvas.find( '.acfw-sortable-children' ).sortable( 'option', 'disabled', true );
				}
				// The drop areas just opened up, so measure the lists again.
				$( this ).sortable( 'refresh' );
			},
			receive: function ( event, ui ) {
				if ( $( this ).hasClass( 'acfw-sortable-children' ) && 'group' === ui.item.data( 'type' ) ) {
					$( ui.sender ).sortable( 'cancel' );
				}
			},
			stop: function () {
				$canvas.removeClass( 'is-dragging is-dragging-group' );
				$canvas.find( '.acfw-sortable-children' ).sortable( 'option', 'disabled', $canvas.hasClass( 'is-filtering' ) );
				refreshGroups();
			}
		} );

		/* ---- "Add to menu": an endpoint, group, link or page, at the end ---- */
		// A dialog in the middle of the window.
		var $addOverlay = $( '.acfw-add-overlay' ).not( '.acfw-dialog' );
		var $addPop     = $addOverlay.find( '.acfw-add-pop' );
		var addReturn   = null;

		function closeAddPop( restoreFocus ) {
			if ( $addOverlay.prop( 'hidden' ) ) {
				return;
			}
			$addOverlay.prop( 'hidden', true );
			$( 'body' ).removeClass( 'acfw-dialog-open' );
			$( '.acfw-canvas-add' ).removeClass( 'is-open' ).attr( 'aria-expanded', 'false' );
			if ( restoreFocus && addReturn ) {
				$( addReturn ).trigger( 'focus' );
			}
			addReturn = null;
		}

		function setAddType( $radio ) {
			$radio.prop( 'checked', true );
			$addPop.find( '.acfw-add-hint' ).text( String( $radio.data( 'hint' ) || '' ) );
		}

		function openAddPop( btn ) {
			$addPop.find( '.acfw-add-error' ).prop( 'hidden', true );
			$addPop.find( '.acfw-add-label' ).val( '' ).removeAttr( 'aria-invalid' );
			$addOverlay.prop( 'hidden', false );
			$( 'body' ).addClass( 'acfw-dialog-open' );
			$( btn ).addClass( 'is-open' ).attr( 'aria-expanded', 'true' );
			addReturn = btn;
			$addPop.find( '.acfw-add-label' ).trigger( 'focus' );
		}

		$( document ).on( 'click', '.acfw-canvas-add', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			if ( addReturn === this ) {
				closeAddPop( true );
				return;
			}
			openAddPop( this );
		} );

		$addPop.on( 'change', 'input[name="acfw_add[type]"]', function () {
			setAddType( $( this ) );
		} );

		$addPop.on( 'click', '.acfw-add-cancel, .acfw-add-close', function () {
			closeAddPop( true );
		} );

		// A click on the backdrop, not the dialog, closes it.
		$addOverlay.on( 'click', function ( e ) {
			if ( e.target === this ) {
				closeAddPop( true );
			}
		} );

		// Tab stays inside the dialog while it is open.
		$addOverlay.on( 'keydown', function ( e ) {
			if ( 'Tab' !== e.key ) {
				return;
			}
			var $stops = $addPop.find( 'button, input[type="text"], input[type="radio"]:checked' ).filter( ':visible:not(:disabled)' );
			var first  = $stops.get( 0 );
			var last   = $stops.get( $stops.length - 1 );
			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		} );

		// "Add item" posts the whole form, so nothing typed elsewhere is lost.
		$addPop.on( 'click', '.acfw-add-submit', function ( e ) {
			var $label = $addPop.find( '.acfw-add-label' );
			if ( ! $.trim( $label.val() ) ) {
				e.preventDefault();
				$addPop.find( '.acfw-add-error' ).prop( 'hidden', false );
				$label.attr( 'aria-invalid', 'true' ).trigger( 'focus' );
			}
		} );

		// Enter in the label adds the item ( the form's default button is Save ).
		$addPop.on( 'keydown', '.acfw-add-label', function ( e ) {
			if ( 'Enter' === e.key ) {
				e.preventDefault();
				$addPop.find( '.acfw-add-submit' ).trigger( 'click' );
			}
		} );

		$( document ).on( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && ! $addOverlay.prop( 'hidden' ) ) {
				closeAddPop( true );
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
