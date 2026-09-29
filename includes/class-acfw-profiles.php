<?php
/**
 * Menus per customer group: customers with a chosen role ( wholesale buyers,
 * members … ) get a menu of their own instead of the main one.
 *
 * A group menu has its own order and its own set of items. Item settings
 * ( label, icon, content, rules ) are shared with the main menu, so an item is
 * set up once and used wherever it appears.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Profiles' ) ) {

	/**
	 * Group menus.
	 */
	class ACFW_Profiles {

		/**
		 * Option: slug => { label, roles, order ( JSON tree ), removed ( keys ) }.
		 *
		 * @var string
		 */
		const OPTION = 'acfw_menu_profiles';

		/**
		 * The group menu applied in this request, if any.
		 *
		 * @var string
		 */
		protected static $applied = '';

		/**
		 * Hook the menu swap and endpoint registration.
		 */
		public function __construct() {
			add_filter( 'acfw_endpoint_items', array( __CLASS__, 'endpoint_items' ) );
			if ( ! is_admin() ) {
				// Before the account page reads the menu ( wp, 20 ).
				add_action( 'wp', array( __CLASS__, 'apply_for_visitor' ), 5 );
			}
		}

		/**
		 * Every group menu, in match order.
		 *
		 * @return array
		 */
		public static function all() {
			$profiles = get_option( self::OPTION, array() );
			return is_array( $profiles ) ? $profiles : array();
		}

		/**
		 * One group menu.
		 *
		 * @param string $slug Slug.
		 * @return array|null
		 */
		public static function get( $slug ) {
			$all = self::all();
			return isset( $all[ $slug ] ) && is_array( $all[ $slug ] ) ? $all[ $slug ] : null;
		}

		/**
		 * A group menu's order tree.
		 *
		 * @param string $slug Slug.
		 * @return array
		 */
		public static function tree( $slug ) {
			$profile = self::get( $slug );
			$tree    = $profile ? json_decode( (string) ( $profile['order'] ?? '' ), true ) : array();
			return is_array( $tree ) ? $tree : array();
		}

		/**
		 * The group menu a user gets: the first whose roles they hold.
		 *
		 * @param WP_User $user User.
		 * @return string Slug, or '' for the main menu.
		 */
		public static function for_user( $user ) {
			$roles = $user instanceof WP_User ? (array) $user->roles : array();
			foreach ( self::all() as $slug => $profile ) {
				if ( array_intersect( (array) ( $profile['roles'] ?? array() ), $roles ) ) {
					return (string) $slug;
				}
			}
			return '';
		}

		/**
		 * Build the menu from a group menu for the rest of this request.
		 *
		 * @param string $slug Slug.
		 */
		public static function apply( $slug ) {
			$profile = self::get( $slug );
			if ( ! $profile ) {
				return;
			}
			self::$applied = $slug;
			$tree          = self::tree( $slug );
			$removed       = array_map( 'strval', (array) ( $profile['removed'] ?? array() ) );
			add_filter(
				'acfw_items_order',
				function () use ( $tree ) {
					return $tree;
				},
				20
			);
			add_filter(
				'acfw_get_items',
				function ( $items ) use ( $removed ) {
					return acfw_items_without( $items, $removed );
				},
				20
			);
			ACFW()->items->build( true );
		}

		/**
		 * Which group menu is in use in this request.
		 *
		 * @return string
		 */
		public static function applied() {
			return self::$applied;
		}

		/**
		 * Swap in the visitor's group menu.
		 */
		public static function apply_for_visitor() {
			if ( is_user_logged_in() ) {
				$slug = self::for_user( wp_get_current_user() );
				if ( '' !== $slug ) {
					self::apply( $slug );
				}
			}
		}

		/**
		 * Register the URLs of endpoints only a group menu uses.
		 *
		 * @param array $flat Key => item from the main menu.
		 * @return array
		 */
		public static function endpoint_items( $flat ) {
			foreach ( array_keys( self::all() ) as $slug ) {
				foreach ( array_keys( acfw_flatten_order_tree( self::tree( (string) $slug ) ) ) as $key ) {
					if ( isset( $flat[ $key ] ) ) {
						continue;
					}
					$item = get_option( 'acfw_item_' . $key, array() );
					if ( is_array( $item ) && 'endpoint' === ( $item['type'] ?? 'endpoint' ) && $item ) {
						$flat[ $key ] = $item;
					}
				}
			}
			return $flat;
		}

		/**
		 * Save a group menu ( a new one gets a slug from its name ).
		 *
		 * @param string $slug  Slug, '' for a new menu.
		 * @param string $label Name.
		 * @param array  $roles Roles.
		 * @param array  $tree  Order tree for a new menu.
		 * @return string Slug.
		 */
		public static function save( $slug, $label, $roles, $tree = array() ) {
			$all   = self::all();
			$label = sanitize_text_field( $label );
			$roles = array_values( array_filter( array_map( 'sanitize_key', (array) $roles ) ) );
			if ( '' === $slug || ! isset( $all[ $slug ] ) ) {
				$base = acfw_item_key_from_label( '' !== $label ? $label : 'menu', 'menu' );
				$slug = $base;
				$n    = 2;
				while ( isset( $all[ $slug ] ) ) {
					$slug = $base . '-' . $n;
					++$n;
				}
				$all[ $slug ] = array(
					'order'   => wp_json_encode( acfw_sanitize_order_tree( $tree ) ),
					'removed' => array(),
				);
			}
			$all[ $slug ]['label'] = '' !== $label ? $label : $slug;
			$all[ $slug ]['roles'] = $roles;
			update_option( self::OPTION, $all );
			update_option( 'acfw_flush_rewrite_rules', 1 );
			return $slug;
		}

		/**
		 * Store a group menu's order.
		 *
		 * @param string $slug Slug.
		 * @param array  $tree Order tree.
		 */
		public static function save_tree( $slug, $tree ) {
			$all = self::all();
			if ( ! isset( $all[ $slug ] ) ) {
				return;
			}
			$tree                    = acfw_sanitize_order_tree( $tree );
			$all[ $slug ]['order']   = wp_json_encode( $tree );
			$all[ $slug ]['removed'] = array_values( array_diff( (array) ( $all[ $slug ]['removed'] ?? array() ), array_keys( acfw_flatten_order_tree( $tree ) ) ) );
			update_option( self::OPTION, $all );
			update_option( 'acfw_flush_rewrite_rules', 1 );
		}

		/**
		 * Take an item out of a group menu ( it stays in the main menu ).
		 *
		 * @param string $slug Slug.
		 * @param string $key  Item key.
		 */
		public static function remove_item( $slug, $key ) {
			$all = self::all();
			if ( ! isset( $all[ $slug ] ) ) {
				return;
			}
			$flat = acfw_flatten_order_tree( self::tree( $slug ) );
			$tree = acfw_order_without( self::tree( $slug ), (string) $key );
			// Built-in items would come back on their own; remember they were taken
			// out, with everything in a group that is taken out.
			$keys                    = array_merge( array( (string) $key ), array_map( 'strval', array_keys( acfw_flatten_order_tree( $flat[ (string) $key ]['children'] ?? array() ) ) ) );
			$removed                 = array_values( array_unique( array_merge( (array) ( $all[ $slug ]['removed'] ?? array() ), $keys ) ) );
			$all[ $slug ]['order']   = wp_json_encode( $tree );
			$all[ $slug ]['removed'] = $removed;
			update_option( self::OPTION, $all );
		}

		/**
		 * Put an item back into a group menu, at the end.
		 *
		 * @param string $slug Slug.
		 * @param string $key  Item key.
		 * @param string $type Item type.
		 */
		public static function add_item( $slug, $key, $type ) {
			$all = self::all();
			if ( ! isset( $all[ $slug ] ) ) {
				return;
			}
			$tree = acfw_order_insert( acfw_order_without( self::tree( $slug ), (string) $key ), (string) $key, $type );
			if ( 'customer-logout' !== $key ) {
				$tree = acfw_order_logout_last( $tree );
			}
			$all[ $slug ]['order']   = wp_json_encode( acfw_sanitize_order_tree( $tree ) );
			$all[ $slug ]['removed'] = array_values( array_diff( (array) ( $all[ $slug ]['removed'] ?? array() ), array( (string) $key ) ) );
			update_option( self::OPTION, $all );
			update_option( 'acfw_flush_rewrite_rules', 1 );
		}

		/**
		 * Delete a group menu ( its customers get the main menu again ).
		 *
		 * @param string $slug Slug.
		 */
		public static function delete( $slug ) {
			$all = self::all();
			unset( $all[ $slug ] );
			update_option( self::OPTION, $all );
			update_option( 'acfw_flush_rewrite_rules', 1 );
		}
	}
}
