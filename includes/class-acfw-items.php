<?php
/**
 * Menu items model: build, store and query My Account menu items.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Items' ) ) {

	/**
	 * Manages the ordered set of My Account menu items.
	 *
	 * Storage:
	 *  - acfw_items_order : JSON tree ({ key: {type, children?} }) describing order + structure.
	 *  - acfw_item_{key}  : per-item options array.
	 */
	class ACFW_Items {

		const ITEM_TYPES = array( 'endpoint', 'group', 'link' );

		/**
		 * Resolved items (order option merged with per-item options + defaults).
		 *
		 * @var array
		 */
		protected $items = array();

		/**
		 * Default WooCommerce-derived items.
		 *
		 * @var array
		 */
		protected $defaults = array();

		/**
		 * Constructor: hook into init to build items and register endpoints.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'build' ), 20 );
			add_action( 'init', array( $this, 'register_endpoints' ), 21 );
			add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 22 );
		}

		/**
		 * Get resolved items.
		 *
		 * @return array
		 */
		public function get_items() {
			return apply_filters( 'acfw_get_items', $this->items );
		}

		/**
		 * Get WooCommerce default items.
		 *
		 * @return array
		 */
		public function get_defaults() {
			return $this->defaults;
		}

		/**
		 * Flat list of all item keys, including group children.
		 *
		 * @return array
		 */
		public function get_item_keys() {
			$keys = array();
			foreach ( $this->items as $key => $item ) {
				$keys[] = $key;
				if ( ! empty( $item['children'] ) ) {
					$keys = array_merge( $keys, array_keys( $item['children'] ) );
				}
			}
			return $keys;
		}

		/**
		 * Build the default items from WooCommerce account menu.
		 */
		protected function build_defaults() {

			if ( ! empty( $this->defaults ) ) {
				return;
			}

			$labels = array(
				'dashboard'       => __( 'Dashboard', 'account-customizer-for-woocommerce' ),
				'orders'          => __( 'Orders', 'account-customizer-for-woocommerce' ),
				'downloads'       => __( 'Downloads', 'account-customizer-for-woocommerce' ),
				'edit-address'    => __( 'Addresses', 'account-customizer-for-woocommerce' ),
				'payment-methods' => __( 'Payment methods', 'account-customizer-for-woocommerce' ),
				'edit-account'    => __( 'Account details', 'account-customizer-for-woocommerce' ),
				'customer-logout' => __( 'Log out', 'account-customizer-for-woocommerce' ),
			);

			$labels = apply_filters( 'woocommerce_account_menu_items', $labels );
			if ( ! is_array( $labels ) ) {
				$labels = array();
			}

			$icons = array(
				'dashboard'       => 'dashicons-dashboard',
				'orders'          => 'dashicons-cart',
				'downloads'       => 'dashicons-download',
				'edit-address'    => 'dashicons-location',
				'payment-methods' => 'dashicons-money-alt',
				'edit-account'    => 'dashicons-admin-users',
				'customer-logout' => 'dashicons-exit',
			);

			foreach ( $labels as $key => $label ) {
				$options          = acfw_default_endpoint_options( $key );
				$options['label'] = $label;
				$options['icon']  = isset( $icons[ $key ] ) ? $icons[ $key ] : '';
				// Logout is a link-style item (no endpoint content).
				$this->defaults[ $key ] = $options;
			}
		}

		/**
		 * Build the resolved items list from stored order + per-item options.
		 *
		 * @param bool $force Force a rebuild even if already built.
		 */
		public function build( $force = false ) {

			if ( ! empty( $this->items ) && ! $force ) {
				return;
			}

			$this->build_defaults();
			$defaults = $this->defaults;

			$order = json_decode( get_option( 'acfw_items_order', '' ), true );
			$order = apply_filters( 'acfw_items_order', $order );

			$this->items = array();

			if ( empty( $order ) || ! is_array( $order ) ) {
				// No saved order: use defaults, but honour any per-item saved options.
				foreach ( $defaults as $key => $options ) {
					$stored              = get_option( 'acfw_item_' . $key, array() );
					$this->items[ $key ] = ( is_array( $stored ) && ! empty( $stored ) )
						? array_merge( $options, $stored )
						: $options;
				}
				return;
			}

			foreach ( $order as $key => $node ) {

				$type          = ! empty( $node['type'] ) ? $node['type'] : 'endpoint';
				$default_fn     = "acfw_default_{$type}_options";
				$default_option = function_exists( $default_fn ) ? call_user_func( $default_fn, $key ) : array();
				$stored         = get_option( 'acfw_item_' . $key, array() );

				if ( empty( $stored ) && isset( $defaults[ $key ] ) ) {
					$stored = $defaults[ $key ];
				}

				$options = array_merge( $default_option, is_array( $stored ) ? $stored : array() );

				if ( ! empty( $node['children'] ) && is_array( $node['children'] ) ) {
					$children = array();
					foreach ( $node['children'] as $child_key => $child_node ) {
						$child_type    = ! empty( $child_node['type'] ) ? $child_node['type'] : 'endpoint';
						$child_default = "acfw_default_{$child_type}_options";
						$child_base    = function_exists( $child_default ) ? call_user_func( $child_default, $child_key ) : array();
						$child_stored  = get_option( 'acfw_item_' . $child_key, array() );

						if ( empty( $child_stored ) && isset( $defaults[ $child_key ] ) ) {
							$child_stored = $defaults[ $child_key ];
						}

						$children[ $child_key ] = array_merge( $child_base, is_array( $child_stored ) ? $child_stored : array() );
						unset( $defaults[ $child_key ] );
					}
					$options['children'] = $children;
				}

				unset( $defaults[ $key ] );
				$this->items[ $key ] = $options;
			}

			// Append any WooCommerce defaults not present in the saved order.
			$this->items = array_merge( $this->items, $defaults );
		}

		/**
		 * Register custom endpoint items as WooCommerce query vars + rewrite endpoints.
		 */
		public function register_endpoints() {

			if ( ! function_exists( 'WC' ) || ! WC()->query ) {
				return;
			}

			$mask = WC()->query->get_endpoints_mask();

			foreach ( $this->items as $key => $item ) {
				if ( 'endpoint' !== ( $item['type'] ?? 'endpoint' ) || 'dashboard' === $key ) {
					continue;
				}
				$slug = ! empty( $item['slug'] ) ? $item['slug'] : $key;
				if ( isset( WC()->query->query_vars[ $key ] ) ) {
					continue;
				}
				WC()->query->query_vars[ $key ] = $slug;
				add_rewrite_endpoint( $slug, $mask );
			}
		}

		/**
		 * Flush rewrite rules once after an activation or item change.
		 */
		public function maybe_flush_rewrite_rules() {
			if ( get_option( 'acfw_flush_rewrite_rules', 0 ) ) {
				update_option( 'acfw_flush_rewrite_rules', 0 );
				flush_rewrite_rules();
			}
		}

		/**
		 * Persist a single item's options.
		 *
		 * @param string $key     Item key.
		 * @param string $type    Item type.
		 * @param array  $data    Raw options.
		 * @param bool   $rebuild Rebuild items after saving.
		 * @return bool
		 */
		public function save_item( $key, $type, $data, $rebuild = true ) {

			$key = acfw_sanitize_key( $key );
			if ( ! in_array( $type, self::ITEM_TYPES, true ) || '' === $key ) {
				return false;
			}

			$data['type']   = $type;
			$data['label']  = isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : '';
			$data['active'] = ! empty( $data['active'] );

			if ( 'endpoint' === $type ) {
				$data['slug'] = ! empty( $data['slug'] ) ? acfw_sanitize_key( $data['slug'] ) : $key;
			} elseif ( 'link' === $type ) {
				$data['url']          = ! empty( $data['url'] ) ? esc_url_raw( $data['url'] ) : '#';
				$data['target_blank'] = ! empty( $data['target_blank'] );
			}

			update_option( 'acfw_item_' . $key, $data );

			if ( $rebuild ) {
				$this->build( true );
			}

			return true;
		}

		/**
		 * Persist the full order/structure tree.
		 *
		 * @param array $order Ordered structure.
		 */
		public function save_order( $order ) {
			update_option( 'acfw_items_order', wp_json_encode( $order ) );
			update_option( 'acfw_flush_rewrite_rules', 1 );
			$this->build( true );
		}

		/**
		 * Remove a custom item (defaults cannot be removed).
		 *
		 * @param string $key Item key.
		 * @return bool
		 */
		public function remove_item( $key ) {
			$this->build_defaults();
			if ( array_key_exists( $key, $this->defaults ) ) {
				return false;
			}
			delete_option( 'acfw_item_' . $key );
			$this->build( true );
			return true;
		}
	}
}
