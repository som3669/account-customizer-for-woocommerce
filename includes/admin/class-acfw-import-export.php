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
				'plugin'   => 'account-customizer-for-woocommerce',
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
				return new WP_Error( 'acfw_import_invalid', __( 'The file is not a valid My Account Customizer export.', 'account-customizer-for-woocommerce' ) );
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

				// Endpoint records ( acfw_item_* ) carry rich content that is echoed
				// on the front end; sanitise it unless the importer may post raw HTML.
				if ( ! $allow_raw_html && 0 === strpos( $name, 'acfw_item_' ) && is_array( $value ) && isset( $value['content'] ) ) {
					$value['content'] = wp_kses_post( (string) $value['content'] );
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
	}
}
