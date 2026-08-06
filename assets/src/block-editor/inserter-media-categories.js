/**
 * Inserter Media tab categories — mirrors wp-editor so Images / Videos / Audio /
 * Openverse work in the standalone block editor.
 *
 * @since 2.0.4
 */

import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __, sprintf, _x } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Fetch attachments from the REST API.
 *
 * @param {Object} query      Query args.
 * @param {string} mediaType Media type slug.
 * @return {Promise<Array>} Media items.
 */
async function coreMediaFetch( query = {}, mediaType ) {
	const items = await apiFetch( {
		path: addQueryArgs( '/wp/v2/media', {
			...query,
			media_type: mediaType,
			orderby: query?.search ? 'relevance' : 'date',
		} ),
	} );

	return ( Array.isArray( items ) ? items : [] ).map( ( mediaItem ) => ( {
		...mediaItem,
		alt: mediaItem.alt_text,
		url: mediaItem.source_url,
		previewUrl: mediaItem.media_details?.sizes?.medium?.source_url,
		caption: mediaItem.caption?.raw,
	} ) );
}

/**
 * Build an Openverse caption string.
 *
 * @param {Object} media Media item from Openverse.
 * @return {string} Caption HTML.
 */
function getOpenverseCaption( media ) {
	const { title, foreign_landing_url: foreignLandingUrl, license, license_url: licenseUrl } =
		media;

	const getExternalLink = ( url, text ) =>
		`<a href="${ url }" target="_blank" rel="nofollow noopener noreferrer">${ text }</a>`;
	const getExternalLinkAttributes = ( url ) =>
		`href="${ url }" target="_blank" rel="nofollow noopener noreferrer"`;

	const fullLicense = license?.toUpperCase();

	if ( foreignLandingUrl && licenseUrl ) {
		return sprintf(
			_x( '"%1$s"/ %2$s', 'caption' ),
			getExternalLink(
				foreignLandingUrl,
				decodeEntities( title || __( 'Work', 'customize-my-account-page-for-woocommerce' ) )
			),
			getExternalLink( `${ licenseUrl }?ref=openverse`, fullLicense )
		);
	}

	return '';
}

export const inserterMediaCategories = [
	{
		name: 'images',
		labels: {
			name: __( 'Images', 'customize-my-account-page-for-woocommerce' ),
			search_items: __( 'Search images', 'customize-my-account-page-for-woocommerce' ),
		},
		mediaType: 'image',
		fetch( query = {} ) {
			return coreMediaFetch( query, 'image' );
		},
	},
	{
		name: 'audio',
		labels: {
			name: __( 'Audio', 'customize-my-account-page-for-woocommerce' ),
			search_items: __( 'Search audio', 'customize-my-account-page-for-woocommerce' ),
		},
		mediaType: 'audio',
		fetch( query = {} ) {
			return coreMediaFetch( query, 'audio' );
		},
	},
	{
		name: 'openverse',
		labels: {
			name: __( 'Openverse', 'customize-my-account-page-for-woocommerce' ),
			search_items: __( 'Search Openverse', 'customize-my-account-page-for-woocommerce' ),
		},
		mediaType: 'image',
		isExternalResource: true,
		async fetch( query = {} ) {
			const defaultArgs = {
				mature: false,
				excluded_source: 'flickr,inaturalist,wikimedia',
				license: 'pdm,cc0',
			};
			const finalQuery = { ...query, ...defaultArgs };
			const mapFromInserterMediaRequest = {
				per_page: 'page_size',
				search: 'q',
			};
			const url = new URL( 'https://api.openverse.org/v1/images/' );
			Object.entries( finalQuery ).forEach( ( [ key, value ] ) => {
				const queryKey = mapFromInserterMediaRequest[ key ] || key;
				url.searchParams.set( queryKey, value );
			} );
			const response = await window.fetch( url, {
				headers: {
					'User-Agent': 'WordPress/inserter-media-fetch',
				},
			} );
			const jsonResponse = await response.json();
			const results = jsonResponse.results || [];
			return results.map( ( result ) => ( {
				...result,
				title: result.title?.toLowerCase().startsWith( 'file:' )
					? result.title.slice( 5 )
					: result.title,
				sourceId: result.id,
				id: undefined,
				caption: getOpenverseCaption( result ),
				previewUrl: result.thumbnail,
			} ) );
		},
		getReportUrl: ( { sourceId } ) =>
			`https://wordpress.org/openverse/image/${ sourceId }/report/`,
	},
];
