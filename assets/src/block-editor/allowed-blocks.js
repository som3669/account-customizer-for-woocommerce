/**
 * Blocks offered inside the endpoint content editor.
 *
 * A curated set of content/media/layout blocks that make sense inside a My
 * Account panel. Site/query/template blocks are intentionally left out.
 *
 * @package AccountCustomizerForWooCommerce
 */

export const ALLOWED_BLOCKS = [
	// Text.
	'core/paragraph',
	'core/heading',
	'core/list',
	'core/list-item',
	'core/quote',
	'core/pullquote',
	'core/code',
	'core/preformatted',
	'core/table',
	'core/verse',
	'core/details',

	// Media.
	'core/image',
	'core/gallery',
	'core/cover',
	'core/media-text',
	'core/video',
	'core/audio',
	'core/file',
	'core/embed',

	// Layout.
	'core/group',
	'core/columns',
	'core/column',
	'core/buttons',
	'core/button',
	'core/separator',
	'core/spacer',
	'core/more',

	// Utility.
	'core/shortcode',
	'core/html',
	'core/block',
];
