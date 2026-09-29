<?php
/**
 * Commerce widgets for the My Account page: Buy Again ( one-click reorder ),
 * Recently Viewed products and an Order Tracking summary.
 *
 * Loaded in every context because the reorder AJAX handlers and the
 * Buy Again endpoint must be available on admin-ajax and rewrite parsing.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Commerce' ) ) {

	/**
	 * Buy Again, Recently Viewed and Order Tracking features.
	 */
	class ACFW_Commerce {

		/**
		 * AJAX nonce action.
		 *
		 * @var string
		 */
		const NONCE = 'acfw_commerce';

		/**
		 * Buy Again endpoint / menu key.
		 *
		 * @var string
		 */
		const BUYAGAIN = 'buy-again';

		/**
		 * Recently Viewed endpoint / menu key.
		 *
		 * @var string
		 */
		const RECENT = 'recently-viewed';

		/**
		 * Recently-viewed cookie name.
		 *
		 * @var string
		 */
		const RV_COOKIE = 'acfw_recently_viewed';

		/**
		 * Hook up endpoint, menu, dashboard widgets, tracking and AJAX.
		 */
		public function __construct() {

			// Reorder AJAX ( available on admin-ajax in any context ).
			add_action( 'wp_ajax_acfw_reorder_add', array( $this, 'ajax_reorder_add' ) );
			add_action( 'wp_ajax_acfw_reorder_order', array( $this, 'ajax_reorder_order' ) );

			// Endpoints inject into the menu ( ACFW_Items reads this filter in
			// build_defaults, so it also registers the rewrites ).
			$has_endpoint = false;

			if ( self::enabled( 'buyagain' ) ) {
				add_action( 'woocommerce_account_' . self::BUYAGAIN . '_endpoint', array( $this, 'render_buy_again' ) );
				$has_endpoint = true;
			}

			if ( self::enabled( 'recent' ) ) {
				add_action( 'template_redirect', array( $this, 'track_recently_viewed' ) );
				add_action( 'woocommerce_account_' . self::RECENT . '_endpoint', array( $this, 'render_recently_viewed' ) );
				$has_endpoint = true;
			}

			if ( $has_endpoint ) {
				add_filter( 'woocommerce_account_menu_items', array( $this, 'inject_menu_item' ), 20 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
			}

			// Flush rewrites when a toggle that owns an endpoint changes. The add_
			// hooks cover the first save, when the option does not exist yet and
			// update_option() adds it instead.
			foreach ( array( 'acfw_buyagain_enable', 'acfw_recent_enable' ) as $option ) {
				add_action( 'add_option_' . $option, array( $this, 'flag_flush' ) );
				add_action( 'update_option_' . $option, array( $this, 'flag_flush' ) );
			}

			// Dashboard widgets ( fires on the front end only ).
			add_action( 'woocommerce_account_dashboard', array( $this, 'render_dashboard_widgets' ), 7 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
		}

		/**
		 * Is a commerce feature enabled?
		 *
		 * @param string $feature buyagain | recent | tracking.
		 * @return bool
		 */
		public static function enabled( $feature ) {
			return 'yes' === get_option( 'acfw_' . $feature . '_enable', 'no' );
		}

		/**
		 * Menu keys of the built-in endpoints whose feature is switched off.
		 *
		 * Once the menu has been saved, these keys live in the stored order, so
		 * turning a feature off must hide the item rather than rely on the
		 * endpoint no longer being injected.
		 *
		 * @return string[]
		 */
		public static function disabled_keys() {
			$keys = array();
			if ( ! self::enabled( 'buyagain' ) ) {
				$keys[] = self::BUYAGAIN;
			}
			if ( ! self::enabled( 'recent' ) ) {
				$keys[] = self::RECENT;
			}
			/**
			 * Built-in menu keys whose feature is switched off ( privacy, returns … ).
			 *
			 * @param string[] $keys Menu keys.
			 */
			return (array) apply_filters( 'acfw_disabled_keys', $keys );
		}

		/**
		 * Mark rewrite rules for a flush on the next load.
		 */
		public function flag_flush() {
			update_option( 'acfw_flush_rewrite_rules', 1 );
		}

		/**
		 * Add the enabled commerce endpoints to the account menu, before Log out.
		 *
		 * @param array $items Menu items ( key => label ).
		 * @return array
		 */
		public function inject_menu_item( $items ) {

			$extras = array();
			if ( self::enabled( 'buyagain' ) && ! isset( $items[ self::BUYAGAIN ] ) ) {
				$extras[ self::BUYAGAIN ] = __( 'Buy again', 'my-account-dashboard-builder' );
			}
			if ( self::enabled( 'recent' ) && ! isset( $items[ self::RECENT ] ) ) {
				$extras[ self::RECENT ] = __( 'Recently viewed', 'my-account-dashboard-builder' );
			}
			if ( empty( $extras ) ) {
				return $items;
			}

			$out      = array();
			$injected = false;
			foreach ( $items as $key => $val ) {
				if ( 'customer-logout' === $key ) {
					$out      = array_merge( $out, $extras );
					$injected = true;
				}
				$out[ $key ] = $val;
			}
			if ( ! $injected ) {
				$out = array_merge( $out, $extras );
			}
			return $out;
		}

		/* -----------------------------------------------------------------
		 * Buy Again
		 * ----------------------------------------------------------------- */

		/**
		 * Products the current customer has previously bought and can buy again.
		 *
		 * @param int $limit Max products ( 0 = no limit ).
		 * @return array List of array{ product: WC_Product, variation_id, quantity, order_id }.
		 */
		public static function reorderable_products( $limit = 0 ) {

			$uid = get_current_user_id();
			if ( ! $uid || ! function_exists( 'wc_get_orders' ) ) {
				return array();
			}

			$orders = wc_get_orders(
				array(
					'customer_id' => $uid,
					'status'      => array( 'wc-completed', 'wc-processing' ),
					'limit'       => 25,
					'orderby'     => 'date',
					'order'       => 'DESC',
				)
			);

			$seen = array();
			$out  = array();

			foreach ( $orders as $order ) {
				foreach ( $order->get_items() as $line ) {
					$product_id   = $line->get_product_id();
					$variation_id = $line->get_variation_id();
					$pick         = $variation_id ? $variation_id : $product_id;

					if ( ! $pick || isset( $seen[ $pick ] ) ) {
						continue;
					}
					$seen[ $pick ] = true;

					$product = $line->get_product();
					if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
						continue;
					}

					$out[] = array(
						'product'      => $product,
						'variation_id' => $variation_id,
						'quantity'     => $line->get_quantity(),
						'order_id'     => $order->get_id(),
					);

					if ( $limit && count( $out ) >= $limit ) {
						return $out;
					}
				}
			}

			return $out;
		}

		/**
		 * Render the Buy Again endpoint content.
		 */
		public function render_buy_again() {
			$products = self::reorderable_products( 0 );
			acfw_get_template(
				'buy-again.php',
				array(
					'products' => $products,
					'nonce'    => wp_create_nonce( self::NONCE ),
				)
			);
		}

		/**
		 * Confirm a customer really purchased a product before reordering it.
		 *
		 * @param int $product_id Product ( or variation ) ID.
		 * @return bool
		 */
		protected function customer_bought( $product_id ) {
			foreach ( self::reorderable_products( 0 ) as $row ) {
				if ( (int) $row['product']->get_id() === (int) $product_id || (int) $row['variation_id'] === (int) $product_id ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * AJAX: add a single previously-bought product to the cart.
		 */
		public function ajax_reorder_add() {

			if ( ! self::enabled( 'buyagain' ) || ! is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'my-account-dashboard-builder' ) ), 403 );
			}
			check_ajax_referer( self::NONCE, 'nonce' );

			$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
			$qty        = isset( $_POST['quantity'] ) ? max( 1, absint( $_POST['quantity'] ) ) : 1;

			if ( ! $product_id || ! $this->customer_bought( $product_id ) ) {
				wp_send_json_error( array( 'message' => __( 'That product is not available to reorder.', 'my-account-dashboard-builder' ) ) );
			}

			$added = $this->add_to_cart( $product_id, $qty );
			if ( ! $added ) {
				wp_send_json_error( array( 'message' => __( 'Sorry, this product cannot be added to the cart.', 'my-account-dashboard-builder' ) ) );
			}

			wp_send_json_success(
				array(
					'message'  => __( 'Added to cart.', 'my-account-dashboard-builder' ),
					'cart_url' => wc_get_cart_url(),
				)
			);
		}

		/**
		 * AJAX: add every product from a past order to the cart.
		 */
		public function ajax_reorder_order() {

			if ( ! self::enabled( 'buyagain' ) || ! is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'my-account-dashboard-builder' ) ), 403 );
			}
			check_ajax_referer( self::NONCE, 'nonce' );

			$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
			$order    = $order_id ? wc_get_order( $order_id ) : false;

			if ( ! $order || (int) $order->get_customer_id() !== get_current_user_id() ) {
				wp_send_json_error( array( 'message' => __( 'Order not found.', 'my-account-dashboard-builder' ) ), 403 );
			}

			$added = 0;
			foreach ( $order->get_items() as $line ) {
				$pick    = $line->get_variation_id() ? $line->get_variation_id() : $line->get_product_id();
				$product = $line->get_product();
				if ( $product && $product->is_purchasable() && $product->is_in_stock() && $this->add_to_cart( $pick, $line->get_quantity() ) ) {
					++$added;
				}
			}

			if ( ! $added ) {
				wp_send_json_error( array( 'message' => __( 'None of the items in that order could be added.', 'my-account-dashboard-builder' ) ) );
			}

			wp_send_json_success(
				array(
					/* translators: %d: number of items added. */
					'message'  => sprintf( _n( '%d item added to cart.', '%d items added to cart.', $added, 'my-account-dashboard-builder' ), $added ),
					'cart_url' => wc_get_cart_url(),
				)
			);
		}

		/**
		 * Add a product ( or variation ) to the cart, carrying variation data.
		 *
		 * @param int $pick Product or variation ID.
		 * @param int $qty  Quantity.
		 * @return bool
		 */
		protected function add_to_cart( $pick, $qty ) {

			$product = wc_get_product( $pick );
			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				return false;
			}

			$qty = max( 1, (int) $qty );

			if ( $product->is_type( 'variation' ) ) {
				$parent_id = $product->get_parent_id();
				$added     = WC()->cart->add_to_cart( $parent_id, $qty, $product->get_id(), $product->get_variation_attributes() );
			} else {
				$added = WC()->cart->add_to_cart( $product->get_id(), $qty );
			}

			return (bool) $added;
		}

		/* -----------------------------------------------------------------
		 * Recently Viewed
		 * ----------------------------------------------------------------- */

		/**
		 * Record a product view in the recently-viewed cookie.
		 */
		public function track_recently_viewed() {

			if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
				return;
			}

			$product_id = get_queried_object_id();
			if ( ! $product_id ) {
				return;
			}

			$ids = self::recently_viewed_ids();
			$ids = array_diff( $ids, array( $product_id ) );
			array_unshift( $ids, $product_id );
			$ids = array_slice( $ids, 0, 15 );

			wc_setcookie( self::RV_COOKIE, implode( '|', array_map( 'absint', $ids ) ) );
		}

		/**
		 * Parse the recently-viewed product IDs from the cookie.
		 *
		 * @return array
		 */
		public static function recently_viewed_ids() {
			if ( empty( $_COOKIE[ self::RV_COOKIE ] ) ) {
				return array();
			}
			$raw = sanitize_text_field( wp_unslash( $_COOKIE[ self::RV_COOKIE ] ) );
			return array_filter( array_map( 'absint', explode( '|', $raw ) ) );
		}

		/* -----------------------------------------------------------------
		 * Order Tracking
		 * ----------------------------------------------------------------- */

		/**
		 * Tracking details for an order.
		 *
		 * Auto-detects the WooCommerce Shipment Tracking extension's meta; when
		 * absent, returns an empty list so the caller shows a status timeline.
		 *
		 * @param WC_Order $order Order object.
		 * @return array List of array{ provider, number, link }.
		 */
		public static function tracking_items( $order ) {

			$items = $order->get_meta( '_wc_shipment_tracking_items' );
			if ( empty( $items ) || ! is_array( $items ) ) {
				return array();
			}

			$out = array();
			foreach ( $items as $item ) {
				$number = isset( $item['tracking_number'] ) ? $item['tracking_number'] : '';
				if ( '' === $number ) {
					continue;
				}
				$out[] = array(
					'provider' => isset( $item['tracking_provider'] ) && $item['tracking_provider'] ? $item['tracking_provider'] : ( isset( $item['custom_tracking_provider'] ) ? $item['custom_tracking_provider'] : '' ),
					'number'   => $number,
					'link'     => isset( $item['custom_tracking_link'] ) ? $item['custom_tracking_link'] : '',
				);
			}
			return $out;
		}

		/**
		 * Recent trackable orders for the current customer.
		 *
		 * @param int $limit Max orders.
		 * @return array
		 */
		public static function trackable_orders( $limit = 3 ) {
			$uid = get_current_user_id();
			if ( ! $uid || ! function_exists( 'wc_get_orders' ) ) {
				return array();
			}
			return wc_get_orders(
				array(
					'customer_id' => $uid,
					'status'      => array( 'wc-processing', 'wc-on-hold', 'wc-completed' ),
					'limit'       => $limit,
					'orderby'     => 'date',
					'order'       => 'DESC',
				)
			);
		}

		/* -----------------------------------------------------------------
		 * Dashboard widgets
		 * ----------------------------------------------------------------- */

		/**
		 * Render the enabled commerce widgets on the dashboard.
		 */
		public function render_dashboard_widgets() {

			if ( ! is_user_logged_in() ) {
				return;
			}

			if ( self::enabled( 'tracking' ) ) {
				$this->render_tracking_widget();
			}
			if ( self::enabled( 'buyagain' ) ) {
				$this->render_buyagain_tile();
			}
		}

		/**
		 * Buy Again dashboard tile ( product thumbnails linking to the endpoint ).
		 */
		protected function render_buyagain_tile() {

			$products = self::reorderable_products( 6 );
			if ( empty( $products ) ) {
				return;
			}
			$nonce = wp_create_nonce( self::NONCE );
			$url   = wc_get_account_endpoint_url( self::BUYAGAIN );

			echo '<div class="acfw-commerce-widget acfw-buyagain-tile">';
			echo '<div class="acfw-cw-head"><h3>' . esc_html__( 'Buy again', 'my-account-dashboard-builder' ) . '</h3><a href="' . esc_url( $url ) . '">' . esc_html__( 'View all', 'my-account-dashboard-builder' ) . '</a></div>';
			echo '<div class="acfw-cw-grid">';
			foreach ( $products as $row ) {
				$product = $row['product'];
				echo '<div class="acfw-cw-card">';
				echo '<a href="' . esc_url( $product->get_permalink() ) . '" class="acfw-cw-thumb">' . wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ) . '</a>';
				echo '<span class="acfw-cw-name">' . esc_html( $product->get_name() ) . '</span>';
				echo '<button type="button" class="button acfw-reorder-btn" data-product="' . esc_attr( $product->get_id() ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Add to cart', 'my-account-dashboard-builder' ) . '</button>';
				echo '</div>';
			}
			echo '</div></div>';
		}

		/**
		 * Recently-viewed products the customer can still see, newest first.
		 *
		 * @return array List of WC_Product.
		 */
		public static function recently_viewed_products() {
			$out = array();
			foreach ( self::recently_viewed_ids() as $id ) {
				$product = wc_get_product( $id );
				if ( $product && 'publish' === $product->get_status() && $product->is_visible() ) {
					$out[] = $product;
				}
			}
			return $out;
		}

		/**
		 * Render the Recently Viewed endpoint content.
		 */
		public function render_recently_viewed() {
			acfw_get_template(
				'recently-viewed.php',
				array(
					'products' => self::recently_viewed_products(),
				)
			);
		}

		/**
		 * Order Tracking dashboard widget.
		 */
		protected function render_tracking_widget() {

			$orders = self::trackable_orders( 3 );
			if ( empty( $orders ) ) {
				return;
			}

			echo '<div class="acfw-commerce-widget acfw-tracking-widget">';
			echo '<div class="acfw-cw-head"><h3>' . esc_html__( 'Order tracking', 'my-account-dashboard-builder' ) . '</h3></div>';

			foreach ( $orders as $order ) {
				$tracking = self::tracking_items( $order );
				$status   = wc_get_order_status_name( $order->get_status() );

				echo '<div class="acfw-track-row">';
				printf(
					'<div class="acfw-track-head"><a href="%1$s">%2$s</a><span class="acfw-track-status acfw-status-%3$s">%4$s</span></div>',
					esc_url( $order->get_view_order_url() ),
					/* translators: %s: order number. */
					esc_html( sprintf( __( 'Order #%s', 'my-account-dashboard-builder' ), $order->get_order_number() ) ),
					esc_attr( $order->get_status() ),
					esc_html( $status )
				);

				if ( ! empty( $tracking ) ) {
					foreach ( $tracking as $t ) {
						echo '<div class="acfw-track-num">';
						if ( $t['provider'] ) {
							echo '<span class="acfw-track-carrier">' . esc_html( $t['provider'] ) . '</span> ';
						}
						if ( $t['link'] ) {
							echo '<a href="' . esc_url( $t['link'] ) . '" target="_blank" rel="noopener">' . esc_html( $t['number'] ) . '</a>';
						} else {
							echo '<span>' . esc_html( $t['number'] ) . '</span>';
						}
						echo '</div>';
					}
				} else {
					echo '<div class="acfw-track-timeline">' . $this->status_timeline( $order->get_status() ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
				}
				echo '</div>';
			}
			echo '</div>';
		}

		/**
		 * Simple status progress bar used when no tracking number exists.
		 *
		 * @param string $status Order status slug ( no wc- prefix ).
		 * @return string
		 */
		protected function status_timeline( $status ) {
			$steps      = array(
				'processing' => __( 'Processing', 'my-account-dashboard-builder' ),
				'on-hold'    => __( 'On hold', 'my-account-dashboard-builder' ),
				'completed'  => __( 'Completed', 'my-account-dashboard-builder' ),
			);
			$order_flow = array( 'processing', 'completed' );
			$current    = in_array( $status, $order_flow, true ) ? array_search( $status, $order_flow, true ) : 0;

			$out = '<ul class="acfw-timeline">';
			foreach ( $order_flow as $i => $slug ) {
				$done = $i <= $current;
				$out .= '<li class="' . ( $done ? 'is-done' : '' ) . '">' . esc_html( $steps[ $slug ] ) . '</li>';
			}
			$out .= '</ul>';
			return $out;
		}
	}
}
