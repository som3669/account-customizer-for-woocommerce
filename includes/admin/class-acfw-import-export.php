<?php
/**
 * Import / Export: serialise and restore the full plugin configuration.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Import_Export' ) ) {

	/**
	 * Exports and imports endpoints, settings and banners as JSON.
	 */
	class ACFW_Import_Export {

		/**
		 * Option names that are not portable ( runtime flags ).
		 *
		 * @var array
		 */
		const SKIP = array( 'acfw_flush_rewrite_rules' );

		/**
		 * Collect all plugin options into a portable array.
		 *
		 * @return array
		 */
		public static function export() {
			global $wpdb;

			$names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'acfw\_%'"
			);

			$data = array();
			foreach ( (array) $names as $name ) {
				if ( in_array( $name, self::SKIP, true ) ) {
					continue;
				}
				$data[ $name ] = get_option( $name );
			}

			return array(
				'plugin'   => 'my-account-dashboard-builder',
				'version'  => defined( 'ACFW_VERSION' ) ? ACFW_VERSION : '',
				'exported' => gmdate( 'c' ),
				'options'  => $data,
			);
		}

		/**
		 * Encode the export as a JSON string.
		 *
		 * @return string
		 */
		public static function export_json() {
			return wp_json_encode( self::export(), JSON_PRETTY_PRINT );
		}

		/**
		 * Import a previously exported configuration.
		 *
		 * @param string $json Raw JSON string.
		 * @return true|WP_Error
		 */
		public static function import( $json ) {

			$parsed = json_decode( $json, true );

			if ( ! is_array( $parsed ) || empty( $parsed['options'] ) || ! is_array( $parsed['options'] ) ) {
				return new WP_Error( 'acfw_import_invalid', __( 'The file is not a valid My Account Dashboard Builder export.', 'my-account-dashboard-builder' ) );
			}

			// A user who cannot post unfiltered HTML must not be able to smuggle
			// scripts into endpoint content via an import file ( the normal save
			// path runs the same content through wp_kses_post ).
			$allow_raw_html = current_user_can( 'unfiltered_html' );

			foreach ( $parsed['options'] as $name => $value ) {
				// Only restore our own, safely-prefixed options.
				if ( 0 !== strpos( (string) $name, 'acfw_' ) || in_array( $name, self::SKIP, true ) ) {
					continue;
				}

				// Rich, front-end-echoed fields must pass the same sanitisers the
				// normal save path applies, unless the importer may post raw HTML.
				if ( ! $allow_raw_html ) {
					$value = self::sanitize_imported( $name, $value );
				}

				// The menu tree drives rewrite rules for everyone, so its shape is
				// checked whoever imports it.
				if ( 'acfw_items_order' === $name ) {
					$tree  = is_string( $value ) ? json_decode( $value, true ) : $value;
					$value = wp_json_encode( acfw_sanitize_order_tree( $tree ) );
				}

				update_option( $name, $value );
			}

			// Rebuild items + flush endpoints on next load.
			update_option( 'acfw_flush_rewrite_rules', 1 );
			if ( function_exists( 'ACFW' ) && ACFW()->items ) {
				ACFW()->items->build( true );
			}

			return true;
		}

		/**
		 * Re-apply the save-path sanitisers to one imported option value.
		 *
		 * Only used for importers who cannot post unfiltered HTML: the admin save
		 * handlers run these same filters, so an import must not be a way around
		 * them.
		 *
		 * @param string $name  Option name.
		 * @param mixed  $value Imported value.
		 * @return mixed
		 */
		protected static function sanitize_imported( $name, $value ) {

			// Endpoint / group / link records carry per-item custom content.
			if ( 0 === strpos( $name, 'acfw_item_' ) && is_array( $value ) && isset( $value['content'] ) ) {
				$value['content'] = wp_kses_post( (string) $value['content'] );
				return $value;
			}

			// Banner records: title is plain text, content is rich.
			if ( 'acfw_banners' === $name && is_array( $value ) ) {
				foreach ( $value as $slug => $banner ) {
					if ( ! is_array( $banner ) ) {
						continue;
					}
					if ( isset( $banner['content'] ) ) {
						$value[ $slug ]['content'] = wp_kses_post( (string) $banner['content'] );
					}
					if ( isset( $banner['title'] ) ) {
						$value[ $slug ]['title'] = sanitize_text_field( (string) $banner['title'] );
					}
				}
				return $value;
			}

			// The logged-out notice and the dashboard notice are both echoed
			// through wpautop() on the front end.
			if ( 'acfw_guest_message' === $name || 'acfw_dashboard_notice' === $name ) {
				return wp_kses_post( (string) $value );
			}

			return $value;
		}
	}
}
