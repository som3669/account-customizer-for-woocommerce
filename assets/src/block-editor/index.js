/**
 * Endpoint content block editor.
 *
 * Mounts an isolated WordPress block editor onto each endpoint's custom-content
 * field and keeps a hidden <textarea> in sync with the serialized block markup,
 * so the standard settings form submits it like any other field. No custom post
 * type or REST persistence is involved.
 *
 * The DOM contract (wrapper/​radio/​textarea class names) is produced by the
 * plugin's own PHP template; this script only reads it.
 *
 * @package AccountCustomizerForWooCommerce
 */

import { createRoot, useState, useMemo, useRef, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import { registerCoreBlocks } from '@wordpress/block-library';
import { parse, serialize, rawHandler } from '@wordpress/blocks';
import {
	BlockEditorProvider,
	BlockList,
	BlockTools,
	BlockInspector,
	WritingFlow,
	ObserveTyping,
	ButtonBlockAppender,
	EditorStyles,
	Inserter,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useDispatch } from '@wordpress/data';
import { Button, Popover, SlotFillProvider } from '@wordpress/components';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { addFilter } from '@wordpress/hooks';
import { MediaUpload, uploadMedia } from '@wordpress/media-utils';
import { ALLOWED_BLOCKS } from './allowed-blocks';
import './style.scss';

/* -------------------------------------------------------------------------
 * Environment ( values localized from PHP ).
 * ---------------------------------------------------------------------- */
const ENV = window.acfwBlockEditorData || {};
const MAY_UPLOAD = ENV.canUploadMedia !== false;
const MAY_UNFILTERED = ENV.canUserUseUnfilteredHTML !== false;

/* One-time boot: register core blocks + point block media pickers at wp.media. */
let booted = false;
function bootOnce() {
	if ( booted ) {
		return;
	}
	booted = true;

	if ( typeof registerCoreBlocks === 'function' ) {
		registerCoreBlocks();
	}

	addFilter( 'editor.MediaUpload', 'acfw/endpoint-editor/media', () => MediaUpload );
}

/* Parse stored value into blocks. Delimited markup → parse(); plain HTML from
 * the Classic editor → rawHandler() ( parse() would flag it "needs recovery" ). */
function toBlocks( value ) {
	const html = ( value || '' ).trim();
	if ( ! html || html === '<p></p>' ) {
		return [];
	}
	return /<!--\s*wp:/i.test( html ) ? parse( html ) : rawHandler( { HTML: html } );
}

/* -------------------------------------------------------------------------
 * The editor surface, bound to one hidden textarea.
 * ---------------------------------------------------------------------- */
function EndpointEditor( { field } ) {
	const [ blocks, setBlocks ] = useState( () => toBlocks( field.value ) );
	const [ sidebar, setSidebar ] = useState( '' ); // '' | 'inspector'
	const canvasRef = useRef( null );
	const { clearSelectedBlock } = useDispatch( blockEditorStore );

	// Write blocks back to the textarea on every change.
	const sync = useCallback(
		( next ) => {
			setBlocks( next );
			field.value = serialize( next );
		},
		[ field ]
	);

	const editorSettings = useMemo(
		() => ( {
			...( window.acfwBlockEditorSettings || {} ),
			hasFixedToolbar: true,
			allowedBlockTypes: ALLOWED_BLOCKS,
			__experimentalCanUserUseUnfilteredHTML: MAY_UNFILTERED,
			codeEditingEnabled: MAY_UNFILTERED,
			...( MAY_UPLOAD
				? {
						mediaUpload: ( args ) =>
							uploadMedia( {
								...args,
								onError: ( { message } ) => args.onError && args.onError( message ),
							} ),
				  }
				: {} ),
		} ),
		[]
	);

	const toggleInspector = () => setSidebar( ( cur ) => ( cur === 'inspector' ? '' : 'inspector' ) );

	return (
		<div className="acfw-be">
			<ShortcutProvider>
				<SlotFillProvider>
					<BlockEditorProvider
						value={ blocks }
						onInput={ sync }
						onChange={ sync }
						settings={ editorSettings }
					>
						<div className="acfw-be__toolbar">
							<Inserter
								rootClientId={ null }
								position="bottom right"
								showInserterHelpPanel
								onSelectOrClose={ () => clearSelectedBlock() }
								renderToggle={ ( { onToggle, disabled, isOpen } ) => (
									<Button
										className="acfw-be__insert"
										variant="primary"
										onClick={ onToggle }
										disabled={ disabled }
										aria-expanded={ isOpen }
									>
										{ __( 'Add block', 'my-account-customizer' ) }
									</Button>
								) }
							/>
							<Button
								className="acfw-be__settings"
								isPressed={ sidebar === 'inspector' }
								onClick={ toggleInspector }
							>
								{ __( 'Block settings', 'my-account-customizer' ) }
							</Button>
						</div>

						<div className="acfw-be__body">
							<div className="acfw-be__canvas" ref={ canvasRef }>
								<BlockTools __unstableContentRef={ canvasRef }>
									<div className="editor-styles-wrapper">
										{ EditorStyles && editorSettings.styles && (
											<EditorStyles styles={ editorSettings.styles } />
										) }
										<WritingFlow>
											<ObserveTyping>
												<BlockList renderAppender={ ButtonBlockAppender } />
											</ObserveTyping>
										</WritingFlow>
									</div>
								</BlockTools>
							</div>

							{ sidebar === 'inspector' && (
								<aside className="acfw-be__sidebar">
									<BlockInspector />
								</aside>
							) }
						</div>

						<Popover.Slot />
					</BlockEditorProvider>
				</SlotFillProvider>
			</ShortcutProvider>
		</div>
	);
}

/* -------------------------------------------------------------------------
 * Mounting + the Classic/Block switch controller.
 * ---------------------------------------------------------------------- */

/* Resolve the DOM pieces for one endpoint from the PHP-rendered markup. */
function pieces( endpoint ) {
	const at = '[data-endpoint="' + endpoint + '"]';
	return {
		classic: document.querySelector( '.acfw_classic_editor_wrapper' + at ),
		block: document.querySelector( '.acfw_block_editor_wrapper' + at ),
		classicField: document.getElementById( 'acfw_content_' + endpoint ),
		blockField: document.getElementById( 'acfw_block_content_' + endpoint ),
		mount: document.querySelector( '.acfw_block_editor' + at ),
	};
}

/* Mount the React editor into a container once. */
function mount( container ) {
	if ( ! container || container.dataset.acfwMounted === '1' ) {
		return;
	}
	const fieldId = container.getAttribute( 'data-textarea' );
	const field = fieldId ? document.getElementById( fieldId ) : null;
	if ( ! field ) {
		return;
	}

	bootOnce();
	container.dataset.acfwMounted = '1';
	createRoot( container ).render( <EndpointEditor field={ field } /> );
}

/* Read the current Classic (TinyMCE-aware) content for carry-over. */
function classicValue( endpoint ) {
	const id = 'acfw_content_' + endpoint;
	const tiny = window.tinymce && window.tinymce.get( id );
	if ( tiny && ! tiny.isHidden() ) {
		return tiny.getContent();
	}
	const el = document.getElementById( id );
	return el ? el.value : '';
}

/* Show one editor mode for an endpoint; only the visible field stays enabled so
 * a single value is submitted. On first switch to Block, seed from Classic. */
function showMode( endpoint, mode, seed ) {
	const p = pieces( endpoint );
	if ( ! p.classic || ! p.block ) {
		return;
	}

	// Reflect the choice on the chip UI.
	document
		.querySelectorAll( '.acfw_editor_type_radio[data-endpoint="' + endpoint + '"]' )
		.forEach( ( radio ) => {
			const chip = radio.closest( '.acfw_choose_icon_type_inner_wrapper' );
			if ( chip ) {
				chip.classList.toggle( 'active', radio.checked );
			}
		} );

	const useBlock = mode === 'block';
	p.classic.classList.toggle( 'acfw_hidden', useBlock );
	p.block.classList.toggle( 'acfw_hidden', ! useBlock );
	if ( p.classicField ) {
		p.classicField.disabled = useBlock;
	}
	if ( p.blockField ) {
		p.blockField.disabled = ! useBlock;
	}

	if ( ! useBlock ) {
		return;
	}

	const already = p.mount && p.mount.dataset.acfwMounted === '1';
	if ( already ) {
		return;
	}

	// Carry Classic content over the first time the Block editor opens empty.
	if ( seed && p.blockField && ! p.blockField.value.trim() ) {
		const carried = classicValue( endpoint );
		if ( carried && carried.trim() !== '<p></p>' ) {
			p.blockField.value = carried;
		}
	}

	mount( p.mount );
}

/* Block toolbar buttons default to type="submit"; stop them reloading the
 * settings form. The real Save button lives outside the editor frame. */
function guardForm() {
	const form = document.querySelector( 'form.acfw-items-form' );
	if ( ! form || form.dataset.acfwGuarded === '1' ) {
		return;
	}
	form.dataset.acfwGuarded = '1';
	form.addEventListener(
		'submit',
		( event ) => {
			const from = ( el ) => el && el.closest && el.closest( '.acfw-be' );
			if ( from( event.submitter ) || from( document.activeElement ) ) {
				event.preventDefault();
			}
		},
		true
	);
}

/* Apply each endpoint's currently-checked mode on load. */
function syncAll() {
	document
		.querySelectorAll( '.acfw_editor_type_radio:checked' )
		.forEach( ( radio ) => showMode( radio.getAttribute( 'data-endpoint' ), radio.value, false ) );
}

/* Radio change (native). */
document.addEventListener( 'change', ( event ) => {
	const el = event.target;
	if ( el && el.classList && el.classList.contains( 'acfw_editor_type_radio' ) && el.checked ) {
		showMode( el.getAttribute( 'data-endpoint' ), el.value, true );
	}
} );

/* Chip click ( the reused icon-chip markup checks the radio via jQuery, which
 * doesn't emit a native change event ). */
document.addEventListener( 'click', ( event ) => {
	const chip = event.target.closest( '.acfw_choose_icon_type_inner_wrapper' );
	const radio = chip && chip.querySelector( '.acfw_editor_type_radio' );
	if ( radio ) {
		radio.checked = true;
		showMode( radio.getAttribute( 'data-endpoint' ), radio.value, true );
	}
} );

// Public handle for other scripts.
window.acfwBlockEditor = { mount, showMode, syncAll };

domReady( () => {
	guardForm();
	syncAll();
} );
