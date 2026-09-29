/* global jQuery */
/*
 * Design Studio ( My Account → Design ).
 *
 * Holds a draft of every design value. Values the preview can apply on its
 * own are sent straight to the iframe; the rest are saved as the admin's
 * draft ( a transient ) and the iframe reloads to show them. Nothing reaches
 * the live site until "Save design".
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $studio = $( '.acfw-studio' );
		if ( ! $studio.length ) {
			return;
		}

		var cfg     = JSON.parse( $( '#acfw-studio-config' ).text() || '{}' );
		var i18n    = cfg.i18n || {};
		var fields  = cfg.fields || {};
		var saved   = $.extend( {}, cfg.saved );
		var draft   = $.extend( {}, cfg.saved );
		var look    = '';
		var frame   = $studio.find( '.acfw-studio-frame iframe' ).get( 0 );
		var $status = $studio.find( '.acfw-studio-status' );
		var draftTimer = null;
		var needsReload = false;
		var busy = false;

		/* ---- Reading and writing controls -------------------------------- */

		function control( key ) {
			return $studio.find( '.acfw-sf[data-key="' + key + '"]' );
		}

		function readControl( $sf ) {
			var type = $sf.data( 'type' );
			var $in  = $sf.find( '.acfw-sf-input' );
			if ( 'toggle' === type ) {
				return $in.is( ':checked' ) ? 'yes' : 'no';
			}
			if ( 'choice' === type ) {
				return String( $in.filter( ':checked' ).val() || '' );
			}
			if ( 'range' === type ) {
				return parseInt( $in.val(), 10 );
			}
			if ( 'color' === type ) {
				var hex = $.trim( $in.val() ).toLowerCase();
				if ( hex && '#' !== hex.charAt( 0 ) ) {
					hex = '#' + hex;
				}
				return /^#([0-9a-f]{3}|[0-9a-f]{6})$/.test( hex ) ? hex : '';
			}
			if ( 'css' === type && window.acfwCssEditor ) {
				return window.acfwCssEditor.codemirror.getValue();
			}
			if ( 'layout' === type ) {
				return readLayout( $sf );
			}
			return String( $in.val() || '' );
		}

		function writeControl( key, value ) {
			var $sf  = control( key );
			var type = $sf.data( 'type' );
			var $in  = $sf.find( '.acfw-sf-input' );
			if ( ! $sf.length ) {
				// A switch the dashboard arrangement's rows carry ( numbers, meter, tiles ).
				$studio.find( '.acfw-layout-switch[data-option="' + key + '"]' ).prop( 'checked', 'yes' === value );
				return;
			}
			if ( 'toggle' === type ) {
				$in.prop( 'checked', 'yes' === value );
			} else if ( 'choice' === type ) {
				$in.filter( function () {
					return String( this.value ) === String( value );
				} ).prop( 'checked', true );
			} else if ( 'range' === type ) {
				$in.val( value );
				showRange( $sf );
			} else if ( 'color' === type ) {
				$in.val( value || '' );
				paintSwatch( $sf );
			} else if ( 'css' === type && window.acfwCssEditor ) {
				window.acfwCssEditor.codemirror.setValue( value || '' );
			} else if ( 'layout' === type ) {
				writeLayout( $sf, value );
			} else {
				$in.val( value );
				if ( 'image' === type ) {
					$sf.find( '.acfw-sf-thumb' ).attr( 'src', value || '' ).prop( 'hidden', ! value );
				}
			}
		}

		function showRange( $sf ) {
			var $in  = $sf.find( '.acfw-sf-input' );
			var unit = ( $sf.find( '.acfw-sf-output' ).text().match( /[a-z%]+$/i ) || [ '' ] )[ 0 ];
			$sf.find( '.acfw-sf-output' ).text( $in.val() + unit );
		}

		function paintSwatch( $sf ) {
			var key = $sf.data( 'key' );
			var hex = readControl( $sf ) || ( fields[ key ] && fields[ key ]['default'] ) || '#ffffff';
			$sf.find( '.acfw-sf-swatch' ).val( 3 === hex.length - 1 ? '#' + hex[ 1 ] + hex[ 1 ] + hex[ 2 ] + hex[ 2 ] + hex[ 3 ] + hex[ 3 ] : hex );
		}

		/* ---- Dashboard arrangement ------------------------------------------- */

		// Rows in order; a part the arrangement itself hides gets a "-". Parts
		// switched by an option of their own ( numbers, meter, tiles ) never do.
		function readLayout( $sf ) {
			return $sf.find( '.acfw-layout-row' ).map( function () {
				var $sw = $( this ).find( '.acfw-layout-switch' );
				var off = ! $sw.data( 'option' ) && ! $sw.is( ':checked' );
				return ( off ? '-' : '' ) + $( this ).data( 'part' );
			} ).get().join( ',' );
		}

		function writeLayout( $sf, value ) {
			var $list = $sf.find( '.acfw-layout-list' );
			String( value || '' ).split( ',' ).forEach( function ( token ) {
				var $row = $list.children( '[data-part="' + token.replace( /^-/, '' ) + '"]' );
				if ( ! $row.length ) {
					return;
				}
				$list.append( $row );
				var $sw = $row.find( '.acfw-layout-switch' );
				if ( ! $sw.data( 'option' ) ) {
					$sw.prop( 'checked', '-' !== token.charAt( 0 ) );
				}
			} );
		}

		function layoutChanged( el ) {
			set( 'acfw_dashboard_layout', readLayout( $( el ).closest( '.acfw-sf' ) ) );
		}

		$studio.on( 'change', '.acfw-layout-switch', function () {
			var option = $( this ).data( 'option' );
			if ( option ) {
				set( option, this.checked ? 'yes' : 'no' );
			} else {
				layoutChanged( this );
			}
		} );

		$studio.on( 'click', '.acfw-layout-up, .acfw-layout-down', function () {
			var $row = $( this ).closest( '.acfw-layout-row' );
			var up   = $( this ).hasClass( 'acfw-layout-up' );
			var $to  = up ? $row.prev() : $row.next();
			if ( ! $to.length ) {
				return;
			}
			if ( up ) {
				$row.insertBefore( $to );
			} else {
				$row.insertAfter( $to );
			}
			// At the top or the bottom that arrow hides; keep focus on the other one.
			var $keep = $row.is( ':first-child' ) && up ? $row.find( '.acfw-layout-down' ) : ( $row.is( ':last-child' ) && ! up ? $row.find( '.acfw-layout-up' ) : $( this ) );
			$keep.trigger( 'focus' );
			if ( window.wp && window.wp.a11y ) {
				window.wp.a11y.speak( $row.find( '.acfw-layout-name' ).text() + ' ' + ( $row.index() + 1 ) + '/' + $row.parent().children().length );
			}
			layoutChanged( $row );
		} );

		if ( $.fn.sortable ) {
			$studio.find( '.acfw-layout-list' ).sortable( {
				handle: '.acfw-layout-handle',
				axis: 'y',
				tolerance: 'pointer',
				update: function () {
					layoutChanged( this );
				},
			} );
		}

		/* ---- Dependent controls ( stat cards need the numbers on, … ) ------ */

		function refreshDependents() {
			$studio.find( '.acfw-sf[data-parent]' ).each( function () {
				var parent = $( this ).data( 'parent' );
				var on     = 'yes' === draft[ parent ] && ! control( parent ).prop( 'hidden' );
				$( this ).prop( 'hidden', ! on );
			} );
			// A second pass settles grandchildren ( upload size under uploads under the card ).
			$studio.find( '.acfw-sf[data-parent]' ).each( function () {
				var parent = $( this ).data( 'parent' );
				$( this ).prop( 'hidden', ! ( 'yes' === draft[ parent ] && ! control( parent ).prop( 'hidden' ) ) );
			} );
		}

		/* ---- Density ------------------------------------------------------ */

		function refreshDensity() {
			var match = 'custom';
			$.each( cfg.densities || {}, function ( slug, d ) {
				if ( parseInt( draft.acfw_item_padding, 10 ) === d.padding && parseInt( draft.acfw_menu_gap, 10 ) === d.gap ) {
					match = slug;
				}
			} );
			var $radios = $studio.find( 'input[name="acfw_studio_density"]' );
			$radios.prop( 'checked', false ).filter( '[value="' + match + '"]' ).prop( 'checked', true );
			if ( 'custom' === match ) {
				$studio.find( '.acfw-finetune' ).prop( 'open', true );
			}
		}

		$studio.on( 'change', 'input[name="acfw_studio_density"]', function () {
			var d = ( cfg.densities || {} )[ this.value ];
			if ( ! d ) {
				return;
			}
			writeControl( 'acfw_item_padding', d.padding );
			writeControl( 'acfw_menu_gap', d.gap );
			// Both values first: set() re-checks the density, and between the two
			// calls it saw a "custom" mix and opened Fine-tune.
			draft.acfw_item_padding = d.padding;
			draft.acfw_menu_gap     = d.gap;
			set( 'acfw_item_padding', d.padding );
			set( 'acfw_menu_gap', d.gap );
		} );

		/* ---- Contrast check for the accent ----------------------------------- */

		function luminance( hex ) {
			var h = hex.replace( '#', '' );
			if ( 3 === h.length ) {
				h = h[ 0 ] + h[ 0 ] + h[ 1 ] + h[ 1 ] + h[ 2 ] + h[ 2 ];
			}
			var rgb = [ 0, 2, 4 ].map( function ( i ) {
				var c = parseInt( h.substr( i, 2 ), 16 ) / 255;
				return c <= 0.03928 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
			} );
			return 0.2126 * rgb[ 0 ] + 0.7152 * rgb[ 1 ] + 0.0722 * rgb[ 2 ];
		}

		// The current page shows accent text on a 10% tint of the accent over white.
		function tintOf( hex ) {
			var h = hex.replace( '#', '' );
			if ( 3 === h.length ) {
				h = h[ 0 ] + h[ 0 ] + h[ 1 ] + h[ 1 ] + h[ 2 ] + h[ 2 ];
			}
			return '#' + [ 0, 2, 4 ].map( function ( i ) {
				var c = Math.round( parseInt( h.substr( i, 2 ), 16 ) * 0.1 + 255 * 0.9 );
				return ( '0' + c.toString( 16 ) ).slice( -2 );
			} ).join( '' );
		}

		function refreshContrast() {
			// The style, placement and marker pictures are drawn in the chosen accent.
			$studio.get( 0 ).style.setProperty( '--acfw-studio-accent', draft.acfw_accent_color || '#2563eb' );
			var $p = $studio.find( '.acfw-sf-contrast' );
			if ( ! $p.length ) {
				return;
			}
			var text = draft.acfw_active_color || draft.acfw_accent_color || '#2563eb';
			var bg   = tintOf( draft.acfw_accent_color || '#2563eb' );
			var a    = luminance( text );
			var b    = luminance( bg );
			var ratio = ( Math.max( a, b ) + 0.05 ) / ( Math.min( a, b ) + 0.05 );
			// Round down: 4.47 must not read as a passing 4.5.
			var shown = ( Math.floor( ratio * 10 ) / 10 ) + ':1';
			var ok    = ratio >= 4.5;
			$p.toggleClass( 'is-low', ! ok ).text( ( ok ? i18n.contrastOk : i18n.contrastLo ).replace( '%s', shown ) );
		}

		/* ---- State --------------------------------------------------------------- */

		function isDirty() {
			var dirty = false;
			$.each( draft, function ( key, value ) {
				if ( String( value ) !== String( saved[ key ] ) ) {
					dirty = true;
					return false;
				}
			} );
			return dirty;
		}

		function refreshBar( text ) {
			var dirty = isDirty();
			$studio.find( '.acfw-studio-save, .acfw-studio-discard' ).prop( 'disabled', ! dirty || busy );
			$studio.toggleClass( 'is-dirty', dirty );
			$status.text( text || ( dirty ? i18n.unsaved : i18n.saved ) );
		}

		function send() {
			if ( frame && frame.contentWindow ) {
				frame.contentWindow.postMessage( { source: 'acfw-studio', type: 'apply', values: draft }, window.location.origin );
			}
		}

		function reloadFrame() {
			if ( frame && frame.contentWindow ) {
				try {
					frame.contentWindow.location.reload();
				} catch ( e ) {
					frame.src = frame.src;
				}
			}
		}

		function post( action, data ) {
			return $.post( cfg.ajaxUrl, $.extend( { action: action, nonce: cfg.nonce }, data || {} ) );
		}

		function saveDraft() {
			var reload  = needsReload;
			needsReload = false;
			post( 'acfw_design_draft', { values: JSON.stringify( draft ) } ).done( function () {
				if ( reload ) {
					reloadFrame();
				}
			} ).fail( function () {
				refreshBar( i18n.error );
			} );
		}

		function set( key, value ) {
			draft[ key ] = value;
			if ( fields[ key ] && ! fields[ key ].live ) {
				needsReload = true;
			}
			send();
			refreshDependents();
			refreshDensity();
			refreshContrast();
			refreshBar();
			window.clearTimeout( draftTimer );
			draftTimer = window.setTimeout( saveDraft, fields[ key ] && 'css' === fields[ key ].type ? 700 : 300 );
		}

		/* ---- Control events ------------------------------------------------------ */

		$studio.on( 'input change', '.acfw-sf .acfw-sf-input', function ( e ) {
			var $sf = $( this ).closest( '.acfw-sf' );
			var key = $sf.data( 'key' );
			var type = $sf.data( 'type' );
			if ( ! key ) {
				return;
			}
			// Typing a colour: wait for a full hex before previewing.
			if ( 'color' === type && 'input' === e.type && '' !== readControl( $sf ) && ! /^#?[0-9a-f]{6}$/i.test( $.trim( this.value ) ) ) {
				return;
			}
			if ( 'range' === type ) {
				showRange( $sf );
			}
			if ( 'color' === type ) {
				paintSwatch( $sf );
			}
			if ( 'image' === type ) {
				$sf.find( '.acfw-sf-thumb' ).attr( 'src', this.value ).prop( 'hidden', ! this.value );
			}
			set( key, readControl( $sf ) );
		} );

		// The native picker writes into the hex field.
		$studio.on( 'input', '.acfw-sf-swatch', function () {
			var $sf = $( this ).closest( '.acfw-sf' );
			$sf.find( '.acfw-sf-hex' ).val( this.value );
			set( $sf.data( 'key' ), this.value.toLowerCase() );
		} );

		// The info icon explains a control; clicking it must not press the
		// switch whose label it sits in.
		$studio.on( 'click', '.acfw-help', function ( e ) {
			e.preventDefault();
		} );

		// Centre a tooltip on its icon where there is room, keep it inside the
		// control's width, and point its arrow at the icon.
		function placeTip( help ) {
			var tip   = help.querySelector( '.acfw-help-tip' );
			var glyph = help.querySelector( '.dashicons' );
			var field = help.closest( '.acfw-sf' );
			if ( ! tip || ! glyph || ! field ) {
				return;
			}
			// Measured from the field's padding box, the tip's containing block
			// ( indented controls have a left border ).
			var origin = field.getBoundingClientRect().left + field.clientLeft;
			var icon   = glyph.getBoundingClientRect();
			var width  = tip.offsetWidth;
			var mid    = icon.left + ( icon.width / 2 ) - origin;
			var left   = Math.max( 0, Math.min( mid - ( width / 2 ), field.clientWidth - width ) );
			tip.style.left = left + 'px';
			tip.style.setProperty( '--acfw-tip-arrow', ( mid - left ) + 'px' );
		}

		$studio.on( 'mouseenter', '.acfw-help', function () {
			placeTip( this );
		} );
		$studio.on( 'focusin', '.acfw-sf', function () {
			var help = this.querySelector( '.acfw-help' );
			if ( help ) {
				placeTip( help );
			}
		} );

		// Escape hides an open tooltip until the pointer or the focus moves on.
		$( document ).on( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				$studio.addClass( 'is-quiet-tips' );
			}
		} );
		$studio.on( 'mousemove focusin', function () {
			$studio.removeClass( 'is-quiet-tips' );
		} );

		$studio.on( 'click', '.acfw-sf-reset', function () {
			var $sf = $( this ).closest( '.acfw-sf' );
			var key = $sf.data( 'key' );
			var def = fields[ key ] ? fields[ key ]['default'] : '';
			writeControl( key, def );
			set( key, def );
		} );

		$studio.on( 'click', '.acfw-sf-media', function ( e ) {
			e.preventDefault();
			var $sf = $( this ).closest( '.acfw-sf' );
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}
			var media = window.wp.media( { library: { type: 'image' }, multiple: false } );
			media.on( 'select', function () {
				var url = media.state().get( 'selection' ).first().toJSON().url;
				writeControl( $sf.data( 'key' ), url );
				set( $sf.data( 'key' ), url );
			} );
			media.open();
		} );

		/* ---- Looks ------------------------------------------------------------- */

		$studio.on( 'click', '.acfw-look', function () {
			var slug   = String( $( this ).data( 'look' ) );
			var values = ( cfg.looks || {} )[ slug ];
			if ( ! values ) {
				return;
			}
			$.each( values, function ( key, value ) {
				if ( Object.prototype.hasOwnProperty.call( draft, key ) && String( draft[ key ] ) !== String( value ) ) {
					if ( fields[ key ] && ! fields[ key ].live ) {
						needsReload = true;
					}
					draft[ key ] = value;
					writeControl( key, value );
				}
			} );
			// Built-in looks are remembered as "in use" when saved; saved presets are not.
			look = 0 === slug.indexOf( 'preset-' ) ? '' : slug;
			$studio.find( '.acfw-look' ).attr( 'aria-pressed', 'false' ).removeClass( 'is-picked' );
			$( this ).attr( 'aria-pressed', 'true' ).addClass( 'is-picked' );
			send();
			refreshDependents();
			refreshDensity();
			refreshContrast();
			refreshBar();
			window.clearTimeout( draftTimer );
			saveDraft();
		} );

		var $lookForm = $studio.find( '.acfw-save-look-form' );
		$studio.on( 'click', '.acfw-save-look-toggle', function () {
			var open = $lookForm.prop( 'hidden' );
			$lookForm.prop( 'hidden', ! open );
			$( this ).attr( 'aria-expanded', open ? 'true' : 'false' );
			if ( open ) {
				$lookForm.find( 'input' ).trigger( 'focus' );
			}
		} );
		$studio.on( 'click', '.acfw-save-look-cancel', function () {
			$lookForm.prop( 'hidden', true );
			$studio.find( '.acfw-save-look-toggle' ).attr( 'aria-expanded', 'false' ).trigger( 'focus' );
		} );
		function saveLook() {
			var name = $.trim( $lookForm.find( 'input' ).val() );
			if ( ! name ) {
				$lookForm.find( 'input' ).trigger( 'focus' );
				return;
			}
			post( 'acfw_design_save_look', { name: name, values: JSON.stringify( draft ) } ).done( function ( res ) {
				if ( ! res || ! res.success ) {
					refreshBar( ( res && res.data && res.data.message ) || i18n.error );
					return;
				}
				var slug = 'preset-' + res.data.slug;
				cfg.looks[ slug ] = $.extend( {}, draft );
				var $card = $( '<button type="button" class="acfw-look acfw-look-plain" aria-pressed="false"></button>' )
					.attr( 'data-look', slug )
					.css( '--acfw-look-accent', draft.acfw_accent_color || '#2563eb' )
					.append( $( '<span class="acfw-look-swatch" aria-hidden="true"></span>' ) )
					.append(
						$( '<span class="acfw-look-body"></span>' )
							.append( $( '<span class="acfw-look-name"></span>' ).text( name ) )
					);
				$studio.find( '.acfw-looks' ).append( $card );
				$lookForm.prop( 'hidden', true ).find( 'input' ).val( '' );
				$studio.find( '.acfw-save-look-toggle' ).attr( 'aria-expanded', 'false' );
				refreshBar( res.data.message );
			} ).fail( function () {
				refreshBar( i18n.error );
			} );
		}
		$studio.on( 'click', '.acfw-save-look-confirm', saveLook );
		$studio.on( 'keydown', '.acfw-save-look-name', function ( e ) {
			if ( 'Enter' === e.key ) {
				e.preventDefault();
				saveLook();
			} else if ( 'Escape' === e.key ) {
				$studio.find( '.acfw-save-look-cancel' ).trigger( 'click' );
			}
		} );

		/* ---- Save / discard ----------------------------------------------------------- */

		$studio.on( 'click', '.acfw-studio-save', function () {
			busy = true;
			refreshBar( i18n.saving );
			window.clearTimeout( draftTimer );
			post( 'acfw_design_save', { values: JSON.stringify( draft ), look: look } ).done( function ( res ) {
				busy = false;
				if ( ! res || ! res.success ) {
					refreshBar( i18n.error );
					return;
				}
				saved = $.extend( {}, res.data.values );
				draft = $.extend( {}, res.data.values );
				if ( look ) {
					$studio.find( '.acfw-look' ).removeClass( 'is-in-use' ).find( '.acfw-look-tag' ).remove();
					$studio.find( '.acfw-look[data-look="' + look + '"]' ).addClass( 'is-in-use' );
				}
				refreshBar( res.data.message );
				reloadFrame();
			} ).fail( function () {
				busy = false;
				refreshBar( i18n.error );
			} );
		} );

		$studio.on( 'click', '.acfw-studio-discard', function () {
			busy = true;
			window.clearTimeout( draftTimer );
			post( 'acfw_design_discard' ).always( function () {
				busy = false;
				draft = $.extend( {}, saved );
				look  = '';
				$.each( saved, writeControl );
				$studio.find( '.acfw-look' ).attr( 'aria-pressed', 'false' ).removeClass( 'is-picked' );
				refreshDependents();
				refreshDensity();
				refreshContrast();
				refreshBar();
				reloadFrame();
			} );
		} );

		$( window ).on( 'beforeunload', function ( e ) {
			if ( isDirty() && ! busy ) {
				e.preventDefault();
				e.originalEvent.returnValue = '';
				return '';
			}
		} );

		/* ---- Preview frame ----------------------------------------------------------- */

		window.addEventListener( 'message', function ( e ) {
			var data = e.data;
			if ( ! frame || e.source !== frame.contentWindow || ! data || 'acfw-preview' !== data.source ) {
				return;
			}
			if ( 'ready' === data.type ) {
				// A reload may land before the draft save; push the live values again.
				send();
				$studio.find( '.acfw-studio-where' ).text( data.path || '' );
			}
		} );

		$studio.on( 'click', '.acfw-device', function () {
			var device = $( this ).data( 'device' );
			$studio.find( '.acfw-device' ).removeClass( 'is-active' ).attr( 'aria-pressed', 'false' );
			$( this ).addClass( 'is-active' ).attr( 'aria-pressed', 'true' );
			$studio.find( '.acfw-studio-frame' ).attr( 'data-device', device );
		} );

		$studio.on( 'click', '.acfw-studio-reload', reloadFrame );

		// View as a customer: open the preview as them ( '' = yourself ).
		var $note = $studio.find( '.acfw-studio-note' );
		$studio.on( 'acfw:viewas', '.acfw-view-as-select', function ( e, url ) {
			var src = $studio.data( 'preview' );
			if ( url ) {
				var u = new window.URL( url, window.location.href );
				u.searchParams.set( $( this ).data( 'preview-arg' ) || 'acfw_preview', '1' );
				src = u.toString();
			}
			if ( frame ) {
				frame.src = src;
			}
			$note.text( url ? $note.data( 'other' ) : $note.data( 'self' ) );
		} );

		/* ---- Custom CSS: WordPress' code editor, when it is available ---------------- */

		var $css = $studio.find( '.acfw-sf-css textarea' );
		if ( $css.length && window.wp && window.wp.codeEditor && window.acfwStudioCodeEditor ) {
			window.acfwCssEditor = window.wp.codeEditor.initialize( $css.get( 0 ), window.acfwStudioCodeEditor );
			window.acfwCssEditor.codemirror.on( 'change', function () {
				set( 'acfw_custom_css', window.acfwCssEditor.codemirror.getValue() );
			} );
			// CodeMirror measures badly inside a closed <details>.
			$css.closest( 'details' ).on( 'toggle', function () {
				if ( this.open ) {
					window.acfwCssEditor.codemirror.refresh();
				}
			} );
		}

		$studio.find( '.acfw-sf-color' ).each( function () {
			paintSwatch( $( this ) );
		} );
		refreshDependents();
		refreshDensity();
		refreshContrast();
		refreshBar();
	} );
} )( jQuery );
