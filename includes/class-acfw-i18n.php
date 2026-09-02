<?php
/**
 * Multilingual support: registers the plugin's user-authored strings
 * ( endpoint labels/content, dashboard title, guest message, banners ) with
 * WPML or Polylang and translates them on the front end.
 *
 * Both WPML and Polylang implement the `wpml_register_single_string` action and
 * `wpml_translate_single_string` filter, so a single integration serves both.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_I18n' ) ) {

	/**
	 * Registers and translates author-supplied strings.
	 */
	class ACFW_I18n {

		/**
		 * String-translation context / domain.
		 *
		 * @var string
		 */
		const CONTEXT = 'my-account-dashboard-builder';

		/**
		 * Wire up registration and the display-time translation filters.
		 */
		public function __construct() {

			if ( ! self::active() ) {
				return;
			}

			add_action( 'init', array( $this, 'register_strings' ), 99 );
			add_filter( 'acfw_get_items', array( $this, 'translate_items' ), 20 );
		}

		/**
		 * Is a supported multilingual plugin active?
		 *
		 * @return bool
		 */
		public static function active() {
			return defined( 'ICL_SITEPRESS_VERSION' )
				|| function_exists( 'pll_register_string' )
				|| has_action( 'wpml_register_single_string' );
		}

		/**
		 * Register a single translatable string.
		 *
		 * @param string $name  Unique string name.
		 * @param string $value Source-language value.
		 */
		public static function register( $name, $value ) {
			if ( '' === (string) $value ) {
				return;
			}
			do_action( 'wpml_register_single_string', self::CONTEXT, $name, (string) $value ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML/Polylang API hook.
		}

		/**
		 * Translate a previously registered string for the current language.
		 *
		 * @param string $name  Unique string name.
		 * @param string $value Source-language value ( fallback ).
		 * @return string
		 */
		public static function translate( $name, $value ) {
			if ( '' === (string) $value || ! self::active() ) {
				return $value;
			}
			return (string) apply_filters( 'wpml_translate_single_string', $value, self::CONTEXT, $name ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML/Polylang API hook.
		}

		/**
		 * Register every author-supplied string from its raw ( source ) option.
		 *
		 * Reads options directly so the registered source is always the
		 * default-language value, never a translated render.
		 */
		public function register_strings() {

			// Endpoint labels + custom content.
			if ( function_exists( 'ACFW' ) && ACFW()->items ) {
				foreach ( ACFW()->items->get_item_keys() as $key ) {
					$data = get_option( 'acfw_item_' . $key, array() );
					if ( ! is_array( $data ) ) {
						continue;
					}
					if ( ! empty( $data['label'] ) ) {
						self::register( 'item_' . $key . '_label', $data['label'] );
					}
					if ( ! empty( $data['content'] ) ) {
						self::register( 'item_' . $key . '_content', $data['content'] );
					}
				}
			}

			// Dashboard title + guest message.
			self::register( 'dashboard_title', get_option( 'acfw_dashboard_title', '' ) );
			self::register( 'guest_message', get_option( 'acfw_guest_message', '' ) );
			self::register( 'dashboard_notice', get_option( 'acfw_dashboard_notice', '' ) );

			// Banner title + body content.
			if ( class_exists( 'ACFW_Banners' ) ) {
				foreach ( ACFW_Banners::all() as $slug => $banner ) {
					if ( ! empty( $banner['title'] ) ) {
						self::register( 'banner_' . $slug . '_title', $banner['title'] );
					}
					if ( ! empty( $banner['content'] ) ) {
						self::register( 'banner_' . $slug . '_content', $banner['content'] );
					}
				}
			}
		}

		/**
		 * Translate endpoint labels + content on the resolved items list.
		 *
		 * @param array $items Resolved items ( from ACFW_Items::get_items ).
		 * @return array
		 */
		public function translate_items( $items ) {
			if ( ! is_array( $items ) ) {
				return $items;
			}
			foreach ( $items as $key => $item ) {
				$items[ $key ] = $this->translate_item( $key, $item );
				if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
					foreach ( $item['children'] as $child_key => $child ) {
						$items[ $key ]['children'][ $child_key ] = $this->translate_item( $child_key, $child );
					}
				}
			}
			return $items;
		}

		/**
		 * Translate one item's label + content.
		 *
		 * @param string $key  Item key.
		 * @param array  $item Item options.
		 * @return array
		 */
		protected function translate_item( $key, $item ) {
			if ( ! empty( $item['label'] ) ) {
				$item['label'] = self::translate( 'item_' . $key . '_label', $item['label'] );
			}
			if ( ! empty( $item['content'] ) ) {
				$item['content'] = self::translate( 'item_' . $key . '_content', $item['content'] );
			}
			return $item;
		}
	}
}
