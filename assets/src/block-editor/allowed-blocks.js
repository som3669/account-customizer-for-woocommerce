/**
 * Allowed blocks for the endpoint custom-content editor.
 *
 * Parent blocks from the product allowlist plus required child / structural
 * blocks so Accordion, Query Loop, Navigation, Comments, etc. still function.
 *
 * @since 2.0.4
 */

/** @type {string[]} */
export const ALLOWED_BLOCK_TYPES = [
	// Explicit allowlist (inserter).
	'core/math',
	'core/verse', // Poetry
	'core/icon',
	'core/accordion',
	'core/more',
	'core/nextpage', // Page Break
	'core/archives',
	'core/calendar',
	'core/categories', // Terms List / Categories List
	'core/latest-comments',
	'core/latest-posts',
	'core/page-list',
	'core/rss',
	'core/search',
	'core/social-links',
	'core/tag-cloud',
	'core/navigation',
	'core/site-logo',
	'core/site-title',
	'core/site-tagline',
	'core/query',
	'core/avatar',
	'core/post-title',
	'core/post-excerpt',
	'core/post-featured-image',
	'core/post-author-name',
	'core/post-comments-count',
	'core/post-comments-link',
	'core/post-date', // Date / Post Date / Modified Date
	'core/post-terms', // Categories / Tags
	'core/post-navigation-link', // Previous / Next Post
	'core/post-time-to-read', // Time to Read / Word Count
	'core/read-more',
	'core/comments',
	'core/post-comments-form',
	'core/loginout',
	'core/term-count',
	'core/term-description',
	'core/term-name',
	'core/terms-query',
	'core/query-title', // Archive / Search Results / Post Type Label
	'core/post-author-biography',
	'core/breadcrumbs',

	// Content / media / layout (unrestricted for endpoint editing).
	'core/paragraph',
	'core/heading',
	'core/image',
	'core/file',
	'core/media-text',
	'core/shortcode',
	'core/block', // Pattern
	'core/template-part',
	'core/separator',
	'core/spacer',
	'core/buttons',
	'core/button',
	'core/code',
	'core/preformatted',
	'core/list',
	'core/list-item',

	// Required children / structural (often inserter:false).
	'core/accordion-item',
	'core/accordion-heading',
	'core/accordion-panel',
	'core/page-list-item',
	'core/social-link',
	'core/navigation-link',
	'core/navigation-submenu',
	'core/navigation-overlay-close',
	'core/home-link',
	'core/post-template',
	'core/query-pagination',
	'core/query-pagination-next',
	'core/query-pagination-previous',
	'core/query-pagination-numbers',
	'core/query-no-results',
	'core/query-total',
	'core/term-template',
	'core/comment-template',
	'core/comment-author-name',
	'core/comment-content',
	'core/comment-date',
	'core/comment-edit-link',
	'core/comment-reply-link',
	'core/comments-title',
	'core/comments-pagination',
	'core/comments-pagination-next',
	'core/comments-pagination-previous',
	'core/comments-pagination-numbers',
	// Layout wrappers used inside Query Loop / Accordion templates.
	'core/group',
	'core/columns',
	'core/column',
];
