/**
 * Standalone Block Editor for endpoint custom content.
 *
 * Mounts a WordPress Block Editor (Gutenberg) into the plugin settings panel
 * without relying on a custom post type. The serialized block markup is written
 * to a hidden textarea that is submitted with the settings form.
 *
 * @since 2.0.4
 */

import {
	createRoot,
	useState,
	useEffect,
	useRef,
	useCallback,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerCoreBlocks } from '@wordpress/block-library';
import { parse, serialize, rawHandler } from '@wordpress/blocks';
import {
	BlockEditorProvider,
	BlockList,
	BlockTools,
	BlockInspector,
	ButtonBlockAppender,
	EditorStyles,
	WritingFlow,
	ObserveTyping,
	store as blockEditorStore,
	ListView as StableListView,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalListView as ExperimentalListView,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalLibrary as InserterLibrary,
} from '@wordpress/block-editor';
import { useDispatch } from '@wordpress/data';
import { Button, Popover, SlotFillProvider } from '@wordpress/components';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { addFilter } from '@wordpress/hooks';
import { MediaUpload, uploadMedia } from '@wordpress/media-utils';
import { listView as listViewIcon, undo as undoIcon, redo as redoIcon, plus as plusIcon, cog as cogIcon, fullscreen } from '@wordpress/icons';
import { ALLOWED_BLOCK_TYPES } from './allowed-blocks';
import './style.scss';

// `ListView` became stable in newer WordPress; fall back to the experimental name.
const ListView = StableListView || ExperimentalListView;

// Whether the current user can upload media (localized from PHP).
const CAN_UPLOAD_MEDIA =
	! window.acfwBlockEditorData ||
	false !== window.acfwBlockEditorData.canUploadMedia;

// Custom HTML / Custom CSS needs unfiltered_html (admins usually have it).
const CAN_USE_UNFILTERED_HTML =
	! window.acfwBlockEditorData ||
	false !== window.acfwBlockEditorData.canUserUseUnfilteredHTML;

/*
 * Image, Cover, Gallery, File, Media & Text, Audio, Video, etc. use the
 * `editor.MediaUpload` filter. Without wp-edit-post / wp-editor, the default
 * is a no-op — same pattern as edit-widgets.
 */
addFilter(
	'editor.MediaUpload',
	'acfw/replace-media-upload',
	() => MediaUpload
);

/**
 * Media upload handler for Image / Cover / File / Gallery blocks.
 * Without this, those blocks show "To edit this block, you need permission to upload media."
 *
 * @param {Object}   options         Upload options from the block.
 * @param {Function} options.onError Error callback.
 */
function mediaUpload( { onError = () => {}, ...rest } ) {
	uploadMedia( {
		onError: ( { message } ) => onError( message ),
		...rest,
	} );
}

let blocksRegistered = false;

/**
 * Register the core blocks a single time.
 */
function registerBlocksOnce() {
	if ( blocksRegistered ) {
		return;
	}

	if ( typeof registerCoreBlocks === 'function' ) {
		registerCoreBlocks();
	}

	blocksRegistered = true;
}

/**
 * Convert stored content into blocks.
 *
 * Saved Block editor content uses `<!-- wp:... -->` delimiters and must go
 * through `parse()`. Classic editor HTML has no delimiters — using `parse()`
 * on it (especially images) creates invalid blocks that show "Attempt Recovery".
 * `rawHandler()` converts plain HTML into valid blocks instead.
 *
 * @param {string} content Serialized blocks or classic HTML.
 * @return {Array} Block objects.
 */
function contentToBlocks( content ) {
	if ( ! content || ! content.trim() || '<p></p>' === content.trim() ) {
		return [];
	}

	if ( /<!--\s*wp:/i.test( content ) ) {
		return parse( content );
	}

	return rawHandler( { HTML: content } );
}

/**
 * The editor component bound to a hidden textarea.
 *
 * @param {Object}             props          Component props.
 * @param {HTMLTextAreaElement} props.textarea The hidden textarea to persist to.
 */
function Editor( { textarea } ) {
	const [ blocks, setBlocks ] = useState( () =>
		contentToBlocks( textarea.value )
	);
	const [ showInspector, setShowInspector ] = useState( false );
	const [ showListView, setShowListView ] = useState( false );
	const [ showInserter, setShowInserter ] = useState( false );
	const [ isExpanded, setIsExpanded ] = useState( false );
	const [ canUndo, setCanUndo ] = useState( false );
	const [ canRedo, setCanRedo ] = useState( false );
	// Required by __experimentalLibrary: on insert it reads ref.current in an
	// rAF (shouldFocusBlock defaults to false). Without a ref, that throws
	// "Cannot read properties of null (reading 'current')".
	const inserterLibraryRef = useRef( null );
	// Match BlockCanvas: popovers / in-between inserter need the content node.
	const contentRef = useRef( null );
	const { clearSelectedBlock } = useDispatch( blockEditorStore );

	/**
	 * Open the docked inserter and clear selection so new blocks append at the
	 * root (after Details / Group / etc.) instead of nesting into the selected
	 * inner block.
	 *
	 * @param {boolean} [next=true] Whether the inserter should open.
	 */
	const openInserterAtRoot = useCallback( ( next = true ) => {
		if ( next ) {
			clearSelectedBlock();
			setShowInserter( true );
			setShowListView( false );
			return;
		}
		setShowInserter( false );
	}, [ clearSelectedBlock ] );

	const openExpandedEditor = ( {
		openInserter = false,
		openInspector = false,
		openListView = false,
	} = {} ) => {
		setIsExpanded( true );
		setShowInserter( openInserter );
		setShowInspector( openInspector );
		setShowListView( openListView );
		// Inserter and list view share the left secondary sidebar.
		if ( openInserter ) {
			setShowListView( false );
		}
		if ( openListView ) {
			setShowInserter( false );
		}
	};

	const closeExpandedEditor = () => {
		setIsExpanded( false );
		setShowInspector( false );
		setShowListView( false );
		setShowInserter( false );
	};

	// Simple undo/redo history stack (the standalone editor has no core/editor store).
	const history = useRef( { stack: [ blocks ], index: 0 } );

	const write = ( next ) => {
		setBlocks( next );
		textarea.value = serialize( next );
	};

	// Transient changes (e.g. typing) — update value without a history entry.
	const handleInput = ( next ) => {
		write( next );
	};

	// Persistent changes — record a history entry.
	const handleChange = ( next ) => {
		const h = history.current;
		h.stack = h.stack.slice( 0, h.index + 1 );
		h.stack.push( next );
		h.index = h.stack.length - 1;

		setCanUndo( h.index > 0 );
		setCanRedo( false );
		write( next );
	};

	const undo = () => {
		const h = history.current;
		if ( h.index > 0 ) {
			h.index -= 1;
			write( h.stack[ h.index ] );
			setCanUndo( h.index > 0 );
			setCanRedo( true );
		}
	};

	const redo = () => {
		const h = history.current;
		if ( h.index < h.stack.length - 1 ) {
			h.index += 1;
			write( h.stack[ h.index ] );
			setCanRedo( h.index < h.stack.length - 1 );
			setCanUndo( true );
		}
	};

	// Ensure the textarea reflects the parsed content on first render.
	useEffect( () => {
		textarea.value = serialize( blocks );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Lock page scroll while the editor is expanded; Escape collapses it.
	useEffect( () => {
		if ( ! isExpanded ) {
			return undefined;
		}

		const previousOverflow = document.body.style.overflow;
		const previousHtmlOverflow = document.documentElement.style.overflow;
		document.body.style.overflow = 'hidden';
		document.documentElement.style.overflow = 'hidden';

		const onKeyDown = ( event ) => {
			if ( 'Escape' !== event.key ) {
				return;
			}

			if ( showInserter ) {
				setShowInserter( false );
				return;
			}

			if ( showListView ) {
				setShowListView( false );
				return;
			}

			if ( showInspector ) {
				setShowInspector( false );
				return;
			}

			closeExpandedEditor();
		};

		document.addEventListener( 'keydown', onKeyDown );

		return () => {
			document.body.style.overflow = previousOverflow;
			document.documentElement.style.overflow = previousHtmlOverflow;
			document.removeEventListener( 'keydown', onKeyDown );
		};
	}, [ isExpanded, showInserter, showListView, showInspector ] );

	// Wire Quick Inserter's "Browse all" to the docked Library sidebar
	// (same as the page editor via __experimentalSetIsInserterOpened).
	const setIsInserterOpened = useCallback(
		( value ) => {
			const shouldOpen = !! value;
			if ( shouldOpen ) {
				if ( ! isExpanded ) {
					clearSelectedBlock();
					openExpandedEditor( { openInserter: true } );
					return;
				}
				openInserterAtRoot( true );
				return;
			}
			setShowInserter( false );
		},
		// openExpandedEditor closes over stable setters; isExpanded is the
		// only runtime dependency that changes the branch.
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ isExpanded, clearSelectedBlock, openInserterAtRoot ]
	);

	// The same editor settings the page editor uses (theme editor styles,
	// theme.json features, patterns) so blocks render/behave identically.
	const editorSettings = window.acfwBlockEditorSettings || {};

	const settings = {
		...editorSettings,
		// Floating toolbar restores "Insert after" for Details / Group / etc.
		// (hasFixedToolbar:true without mounting <BlockToolbar /> hid that UI.)
		hasFixedToolbar: false,
		// Restrict the inserter to the approved block set.
		allowedBlockTypes: ALLOWED_BLOCK_TYPES,
		// Media / Openverse tabs insert Image/Video blocks which are not allowed.
		inserterMediaCategories: [],
		enableOpenverseMediaCategory: false,
		// Shows "Browse all" on the canvas + quick inserter and opens the
		// docked Blocks / Patterns / Media sidebar (matches page editor).
		__experimentalSetIsInserterOpened: setIsInserterOpened,
		// Custom HTML block modal (and related unfiltered markup).
		__experimentalCanUserUseUnfilteredHTML: CAN_USE_UNFILTERED_HTML,
		codeEditingEnabled: CAN_USE_UNFILTERED_HTML,
		// Required for Image, Cover, File, Gallery, Media & Text, etc.
		...( CAN_UPLOAD_MEDIA ? { mediaUpload } : {} ),
	};

	return (
		<div
			className={
				'acfw-block-editor__frame' +
				( isExpanded ? ' is-expanded' : '' ) +
				( isExpanded && showInserter ? ' has-inserter-open' : '' )
			}
		>
			{ /* ShortcutProvider renders a real <div>; class it so fullscreen
			     height/scroll can target that shell (providers otherwise break
			     grid/flex on the frame itself). */ }
			<ShortcutProvider className="acfw-block-editor__shell">
				<SlotFillProvider>
					<BlockEditorProvider
						value={ blocks }
						onInput={ handleInput }
						onChange={ handleChange }
						settings={ settings }
					>
						<div className="acfw-block-editor__header">
							<div className="acfw-block-editor__header-left">
								{ isExpanded ? (
									<Button
										className="acfw-block-editor__inserter-toggle"
										variant="primary"
										icon={ plusIcon }
										label={ __(
											'Toggle block inserter',
											'customize-my-account-page-for-woocommerce'
										) }
										isPressed={ showInserter }
										onClick={ () => {
											if ( showInserter ) {
												setShowInserter( false );
												return;
											}
											openInserterAtRoot( true );
										} }
									/>
								) : (
									// Compact preview: the block library needs room,
									// so "+" opens the full-screen editor instead of
									// the cramped inline inserter.
									<Button
										className="acfw-block-editor__inserter-toggle"
										variant="primary"
										icon={ plusIcon }
										label={ __(
											'Add block',
											'customize-my-account-page-for-woocommerce'
										) }
										onClick={ () => {
											clearSelectedBlock();
											openExpandedEditor( {
												openInserter: true,
											} );
										} }
									/>
								) }
								<Button
									icon={ undoIcon }
									label={ __(
										'Undo',
										'customize-my-account-page-for-woocommerce'
									) }
									onClick={ undo }
									disabled={ ! canUndo }
								/>
								<Button
									icon={ redoIcon }
									label={ __(
										'Redo',
										'customize-my-account-page-for-woocommerce'
									) }
									onClick={ redo }
									disabled={ ! canRedo }
								/>
								{ ListView && (
									<Button
										icon={ listViewIcon }
										label={ __(
											'List View',
											'customize-my-account-page-for-woocommerce'
										) }
										isPressed={ showListView }
										onClick={ () => {
											// Compact preview: open full-screen with list view.
											if ( ! isExpanded ) {
												openExpandedEditor( {
													openListView: true,
												} );
												return;
											}
											const next = ! showListView;
											setShowListView( next );
											if ( next ) {
												setShowInserter( false );
											}
										} }
									/>
								) }
							</div>
							<div className="acfw-block-editor__header-right">
								{ isExpanded ? (
									<Button
										className="acfw-block-editor__done"
										variant="primary"
										onClick={ closeExpandedEditor }
									>
										{ __(
											'Done',
											'customize-my-account-page-for-woocommerce'
										) }
									</Button>
								) : (
									<Button
										className="acfw-block-editor__edit"
										variant="primary"
										icon={ fullscreen }
										onClick={ () =>
											openExpandedEditor( {
												openInserter: true,
											} )
										}
									>
										{ __(
											'Edit content',
											'customize-my-account-page-for-woocommerce'
										) }
									</Button>
								) }
								<Button
									icon={ cogIcon }
									label={ __(
										'Settings',
										'customize-my-account-page-for-woocommerce'
									) }
									isPressed={ showInspector }
									onClick={ () => {
										// From the compact preview, open settings in
										// the full-screen editor where there's room.
										if ( ! isExpanded ) {
											openExpandedEditor( {
												openInspector: true,
											} );
										} else {
											setShowInspector( ! showInspector );
										}
									} }
								/>
							</div>
						</div>

						<div className="acfw-block-editor__body">
							{ isExpanded && showInserter && (
								<div className="editor-inserter-sidebar acfw-block-editor__inserter-sidebar">
									<div className="editor-inserter-sidebar__content">
										<InserterLibrary
											ref={ inserterLibraryRef }
											showInserterHelpPanel
											showMostUsedBlocks
											onClose={ () =>
												setShowInserter( false )
											}
										/>
									</div>
								</div>
							) }
							{ showListView && ListView && (
								<div className="acfw-block-editor__list-view-sidebar">
									<div className="acfw-block-editor__list-view-header">
										<strong>
											{ __(
												'List View',
												'customize-my-account-page-for-woocommerce'
											) }
										</strong>
										<Button
											icon="no-alt"
											label={ __(
												'Close',
												'customize-my-account-page-for-woocommerce'
											) }
											onClick={ () =>
												setShowListView( false )
											}
										/>
									</div>
									<div className="acfw-block-editor__list-view">
										<ListView />
									</div>
								</div>
							) }
							<div className="acfw-block-editor__content">
								<BlockTools __unstableContentRef={ contentRef }>
									<div
										className="editor-styles-wrapper"
										ref={ contentRef }
									>
										{ EditorStyles && settings.styles && (
											<EditorStyles
												styles={ settings.styles }
												scope=":where(.editor-styles-wrapper)"
											/>
										) }
										<WritingFlow>
											<ObserveTyping>
												{ /* Always show a trailing root "+" so users can
												     insert AFTER Details / Group / etc. Core hides
												     the default appender once the canvas has blocks. */ }
												<BlockList
													renderAppender={
														ButtonBlockAppender
													}
												/>
											</ObserveTyping>
										</WritingFlow>
									</div>
								</BlockTools>
							</div>
							{ showInspector && (
								<div className="acfw-block-editor__sidebar">
									<BlockInspector />
								</div>
							) }
						</div>
						{ /* Keep popovers out of the flex/grid height chain. */ }
						<div className="acfw-block-editor__popover-slot">
							<Popover.Slot />
						</div>
					</BlockEditorProvider>
				</SlotFillProvider>
			</ShortcutProvider>
		</div>
	);
}

/**
 * Initialize the block editor inside a given container element.
 *
 * @param {HTMLElement} container The `.acfw_block_editor` container.
 */
function init( container ) {
	if ( ! container || '1' === container.dataset.acfwInit ) {
		return;
	}

	const textareaId = container.getAttribute( 'data-textarea' );
	const textarea = textareaId ? document.getElementById( textareaId ) : null;

	if ( ! textarea ) {
		return;
	}

	registerBlocksOnce();
	installFormSubmitGuard();

	container.dataset.acfwInit = '1';
	const root = createRoot( container );
	container.__acfwRoot = root;
	root.render( <Editor textarea={ textarea } /> );
}

let submitGuardInstalled = false;

/**
 * Stop the settings form from submitting when the submit originates inside the
 * block editor. Block UI buttons (e.g. the Accordion "+" add-item and "×"
 * remove-item) are <button> without an explicit type, so they default to
 * type="submit" and would reload the page. The real Save button lives outside
 * the editor frame and is unaffected.
 */
function installFormSubmitGuard() {
	if ( submitGuardInstalled ) {
		return;
	}

	const form = document.querySelector( 'form.acfw-items-form' );

	if ( ! form ) {
		return;
	}

	submitGuardInstalled = true;

	const fromEditor = ( el ) =>
		el && el.closest && el.closest( '.acfw-block-editor__frame' );

	form.addEventListener(
		'submit',
		( event ) => {
			if (
				fromEditor( event.submitter ) ||
				fromEditor( document.activeElement )
			) {
				event.preventDefault();
			}
		},
		true
	);
}

/**
 * Get the related editor elements for an endpoint.
 *
 * @param {string} endpoint Endpoint slug.
 * @return {Object} Related DOM elements.
 */
function getScope( endpoint ) {
	const selector = '[data-endpoint="' + endpoint + '"]';

	return {
		classicWrap: document.querySelector(
			'.acfw_classic_editor_wrapper' + selector
		),
		blockWrap: document.querySelector(
			'.acfw_block_editor_wrapper' + selector
		),
		classicTextarea: document.getElementById(
			'acfw_content_' + endpoint
		),
		blockInput: document.getElementById(
			'acfw_block_content_' + endpoint
		),
		blockContainer: document.querySelector(
			'.acfw_block_editor' + selector
		),
	};
}

/**
 * Read the current classic editor content (TinyMCE aware).
 *
 * @param {string} endpoint Endpoint slug.
 * @return {string} Content HTML.
 */
function getClassicContent( endpoint ) {
	const editorId = 'acfw_content_' + endpoint;

	if (
		window.tinymce &&
		window.tinymce.get( editorId ) &&
		! window.tinymce.get( editorId ).isHidden()
	) {
		return window.tinymce.get( editorId ).getContent();
	}

	const textarea = document.getElementById( editorId );
	return textarea ? textarea.value : '';
}

/**
 * Toggle between the classic and block editor for an endpoint.
 *
 * Content handling is "live one-way carry-over" on first open only:
 * - First switch Classic → Block: carry classic HTML into the block editor
 *   (via rawHandler, so images/HTML do not become "Attempt Recovery" blocks).
 * - Later toggles: keep the block editor mounted so image/heading blocks stay
 *   intact when switching away to Classic and back.
 * - Block → Classic never rewrites classic content.
 * - Only the active editor's field is submitted.
 *
 * @param {string}  endpoint Endpoint slug.
 * @param {string}  type     Editor type: 'classic' or 'block'.
 * @param {boolean} reseed   Whether to carry classic content into the block
 *                           editor on first init. True for user switches;
 *                           false for the initial page-load sync.
 */
function applyEditorType( endpoint, type, reseed = false ) {
	const scope = getScope( endpoint );

	if ( ! scope.classicWrap || ! scope.blockWrap ) {
		return;
	}

	// Highlight the selected chip (reuses the Endpoint Icon control markup).
	document
		.querySelectorAll(
			'.acfw_editor_type_radio[data-endpoint="' + endpoint + '"]'
		)
		.forEach( ( radio ) => {
			const chip = radio.closest( '.acfw_choose_icon_type_inner_wrapper' );

			if ( chip ) {
				chip.classList.toggle( 'active', radio.checked );
			}
		} );

	if ( 'block' === type ) {
		scope.classicWrap.classList.add( 'acfw_hidden' );
		scope.blockWrap.classList.remove( 'acfw_hidden' );

		if ( scope.classicTextarea ) {
			scope.classicTextarea.disabled = true;
		}

		if ( scope.blockInput ) {
			scope.blockInput.disabled = false;
		}

		const initialized =
			scope.blockContainer &&
			'1' === scope.blockContainer.dataset.acfwInit;

		// Already mounted: just show it. Remounting/reseeding would destroy
		// image blocks and can trigger "Attempt Recovery".
		if ( initialized ) {
			return;
		}

		if ( reseed && scope.blockInput ) {
			const current = getClassicContent( endpoint );
			const carriedHtml =
				current && '<p></p>' !== current.trim() ? current : '';

			// Only seed from classic when the block field is still empty.
			if ( ! scope.blockInput.value.trim() && carriedHtml ) {
				scope.blockInput.value = carriedHtml;
			}
		}

		init( scope.blockContainer );
	} else {
		scope.blockWrap.classList.add( 'acfw_hidden' );
		scope.classicWrap.classList.remove( 'acfw_hidden' );

		if ( scope.classicTextarea ) {
			scope.classicTextarea.disabled = false;
		}

		if ( scope.blockInput ) {
			scope.blockInput.disabled = true;
		}
	}
}

/**
 * Sync every endpoint editor to its currently selected editor type.
 */
function syncAll() {
	document
		.querySelectorAll( '.acfw_editor_type_radio:checked' )
		.forEach( ( radio ) => {
			applyEditorType(
				radio.getAttribute( 'data-endpoint' ),
				radio.value
			);
		} );
}

// Toggle editor when the radio selection changes (direct radio interaction).
document.addEventListener( 'change', ( event ) => {
	const target = event.target;

	if (
		target &&
		target.classList &&
		target.classList.contains( 'acfw_editor_type_radio' ) &&
		target.checked
	) {
		applyEditorType(
			target.getAttribute( 'data-endpoint' ),
			target.value,
			true
		);
	}
} );

// The control reuses the Endpoint Icon chip markup: clicking the chip text
// checks the radio through jQuery (`.trigger('change')`), which does NOT fire a
// native `change` event. Handle the click natively so the editor still swaps.
document.addEventListener( 'click', ( event ) => {
	const chip = event.target.closest(
		'.acfw_choose_icon_type_inner_wrapper'
	);

	if ( ! chip ) {
		return;
	}

	const radio = chip.querySelector( '.acfw_editor_type_radio' );

	// Ignore the Endpoint Icon chips (they have no editor-type radio).
	if ( ! radio ) {
		return;
	}

	radio.checked = true;
	applyEditorType( radio.getAttribute( 'data-endpoint' ), radio.value, true );
} );

// Public API for other scripts (e.g. dynamically added endpoints).
window.acfwBlockEditor = {
	init,
	applyEditorType,
	syncAll,
};

if ( 'loading' !== document.readyState ) {
	syncAll();
} else {
	document.addEventListener( 'DOMContentLoaded', syncAll );
}
