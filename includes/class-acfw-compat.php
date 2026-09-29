<?php
/**
 * Other plugins' account pages.
 *
 * Pages other plugins add to the WooCommerce account menu ( Subscriptions,
 * Memberships, Bookings, points, wallets … ) already show in the canvas,
 * because the menu is read through WooCommerce's own filter. This gives them
 * fitting icons, and adds a Wishlist page inside My Account when YITH or TI
 * WooCommerce Wishlist is active.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Compat' ) ) {

	/**
	 * Third-party account pages.
	 */
	class ACFW_Compat {

		/**
		 * Wishlist endpoint / menu key.
		 *
		 * @var string
		 */
		const WISHLIST = 'wishlist';

		/**
		 * Hook icons and the wishlist page.
		 */
		public function __construct() {
			add_filter( 'acfw_default_icons', array( __CLASS__, 'icons' ) );
			add_filter( 'acfw_disabled_keys', array( __CLASS__, 'disabled_key' ) );
			if ( '' !== self::wishlist_shortcode() ) {
				add_filter( 'woocommerce_account_menu_items', array( $this, 'wishlist_item' ), 20 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
				add_action( 'woocommerce_account_' . self::WISHLIST . '_endpoint', array( $this, 'render_wishlist' ) );
			}
		}

		/**
		 * Icons for known account pages ( the key a plugin gives its endpoint ).
		 *
		 * @param array $icons Key => icon class.
		 * @return array
		 */
		public static function icons( $icons ) {
			return (array) $icons + array(
				'privacy'            => 'fas fa-user-shield',
				'returns'            => 'fas fa-undo-alt',
				self::WISHLIST       => 'fas fa-heart',
				'subscriptions'      => 'fas fa-sync-alt',   // WooCommerce Subscriptions.
				'members-area'       => 'fas fa-id-card',    // WooCommerce Memberships.
				'bookings'           => 'fas fa-calendar-check', // WooCommerce Bookings.
				'points-and-rewards' => 'fas fa-coins',      // WooCommerce Points and Rewards.
				'my-points'          => 'fas fa-coins',      // YITH Points and Rewards.
				'woo-wallet'         => 'fas fa-wallet',     // TeraWallet.
				'support-tickets'    => 'fas fa-life-ring',
				'my-tickets'         => 'fas fa-ticket-alt', // Event tickets.
				'following'          => 'fas fa-user-friends',
				'affiliate-area'     => 'fas fa-handshake',
			);
		}

		/**
		 * The wishlist shortcode of an active wishlist plugin.
		 *
		 * @return string '' when none is active.
		 */
		public static function wishlist_shortcode() {
			if ( defined( 'YITH_WCWL' ) || function_exists( 'yith_wcwl_get_wishlist' ) ) {
				return '[yith_wcwl_wishlist]';
			}
			if ( defined( 'TINVWL_FVERSION' ) || class_exists( 'TInvWL_Public_Wishlist_View' ) ) {
				return '[ti_wishlistsview]';
			}
			return '';
		}

		/**
		 * Hide a saved Wishlist item when no wishlist plugin is active.
		 *
		 * @param string[] $keys Menu keys.
		 * @return string[]
		 */
		public static function disabled_key( $keys ) {
			if ( '' === self::wishlist_shortcode() ) {
				$keys[] = self::WISHLIST;
			}
			return $keys;
		}

		/**
		 * Add "Wishlist" above Log out.
		 *
		 * @param array $items Key => label.
		 * @return array
		 */
		public function wishlist_item( $items ) {
			if ( isset( $items[ self::WISHLIST ] ) ) {
				return $items;
			}
			$logout = isset( $items['customer-logout'] ) ? array( 'customer-logout' => $items['customer-logout'] ) : array();
			unset( $items['customer-logout'] );
			$items[ self::WISHLIST ] = __( 'Wishlist', 'my-account-dashboard-builder' );
			return $items + $logout;
		}

		/**
		 * The wishlist, inside My Account.
		 */
		public function render_wishlist() {
			echo do_shortcode( self::wishlist_shortcode() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the wishlist plugin's own output.
		}
	}
}
