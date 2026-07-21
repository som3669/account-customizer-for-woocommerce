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
				'plugin'    => 'account-customizer-for-woocommerce',
				'version'   => defined( 'ACFW_VERSION' ) ? ACFW_VERSION : '',
				'exported'  => gmdate( 'c' ),
				'options'   => $data,
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

			foreach ( $parsed['options'] as $name => $value ) {
				// Only restore our own, safely-prefixed options.
				if ( 0 !== strpos( (string) $name, 'acfw_' ) || in_array( $name, self::SKIP, true ) ) {
					continue;
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
