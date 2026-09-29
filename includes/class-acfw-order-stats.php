<?php
/**
 * Per-customer order statistics for the dashboard widgets, cached in user meta.
 *
 * Counting a customer's orders by status used to load every order object on
 * each dashboard view. The counts are now read with one COUNT-style query per
 * status, cached per customer, and dropped whenever one of their orders is
 * created, updated, changes status, or is trashed or deleted.
 *
 * Loaded in every context: orders change in wp-admin, at checkout, over REST
 * and from cron, and each of those must clear the cache.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Order_Stats' ) ) {

	/**
	 * Cached order counts per customer.
	 */
	class ACFW_Order_Stats {

		/**
		 * User meta key holding the cached stats.
		 *
		 * @var string
		 */
		const META = 'acfw_order_stats';

		/**
		 * Maximum age of a cached entry, in seconds. Order events clear the
		 * cache sooner; this only bounds what a missed event could leave.
		 *
		 * @var int
		 */
		const TTL = 43200;

		/**
		 * Hook the cache invalidation to WooCommerce's order events.
		 */
		public function __construct() {
			add_action( 'woocommerce_new_order', array( $this, 'flush_for_order' ), 10, 2 );
			add_action( 'woocommerce_update_order', array( $this, 'flush_for_order' ), 10, 2 );
			add_action( 'woocommerce_before_trash_order', array( $this, 'flush_for_order' ), 10, 2 );
			add_action( 'woocommerce_before_delete_order', array( $this, 'flush_for_order' ), 10, 2 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'flush_for_status_change' ), 10, 4 );
		}

		/**
		 * Drop the cached stats of an order's customer.
		 *
		 * @param int           $order_id Order ID.
		 * @param WC_Order|null $order    Order object, when the hook passes one.
		 */
		public function flush_for_order( $order_id, $order = null ) {
			if ( ! is_object( $order ) && function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( $order_id );
			}
			if ( is_object( $order ) && method_exists( $order, 'get_customer_id' ) && $order->get_customer_id() ) {
				self::flush( $order->get_customer_id() );
			}
		}

		/**
		 * Drop the cached stats when an order changes status.
		 *
		 * @param int           $order_id Order ID.
		 * @param string        $from     Previous status.
		 * @param string        $to       New status.
		 * @param WC_Order|null $order    Order object.
		 */
		public function flush_for_status_change( $order_id, $from = '', $to = '', $order = null ) {
			$this->flush_for_order( $order_id, $order );
		}

		/**
		 * Drop a customer's cached stats.
		 *
		 * @param int $user_id User ID.
		 */
		public static function flush( $user_id ) {
			$user_id = absint( $user_id );
			if ( $user_id ) {
				delete_user_meta( $user_id, self::META );
			}
		}

		/**
		 * A customer's order counts.
		 *
		 * @param int $user_id User ID.
		 * @return array {
		 *     @type int   $total     Orders across every status except drafts.
		 *     @type array $by_status status ( no wc- prefix ) => count, zero counts left out.
		 *     @type int   $latest    ID of the most recent order, 0 when none.
		 *     @type int   $latest_time When it was placed ( Unix time ), 0 when none.
		 * }
		 */
		public static function get( $user_id ) {
			$user_id = absint( $user_id );
			$empty   = array(
				'total'       => 0,
				'by_status'   => array(),
				'latest'      => 0,
				'latest_time' => 0,
			);

			if ( ! $user_id || ! function_exists( 'wc_get_orders' ) || ! function_exists( 'wc_get_order_statuses' ) ) {
				return $empty;
			}

			$cached = get_user_meta( $user_id, self::META, true );
			// Entries cached before latest_time existed are rebuilt.
			if ( is_array( $cached ) && isset( $cached['time'], $cached['by_status'] ) && array_key_exists( 'latest_time', $cached ) && ( time() - (int) $cached['time'] ) < self::TTL ) {
				return array_merge( $empty, $cached );
			}

			// Draft orders are checkouts still in progress, not orders the
			// customer has placed.
			$statuses = array_diff( array_keys( wc_get_order_statuses() ), array( 'wc-checkout-draft' ) );

			$by_status = array();
			foreach ( $statuses as $status ) {
				$result = wc_get_orders(
					array(
						'customer_id' => $user_id,
						'status'      => $status,
						'limit'       => 1,
						'paginate'    => true,
						'return'      => 'ids',
					)
				);
				$count  = ( is_object( $result ) && isset( $result->total ) ) ? (int) $result->total : 0;
				if ( $count > 0 ) {
					$slug               = 0 === strpos( $status, 'wc-' ) ? substr( $status, 3 ) : $status;
					$by_status[ $slug ] = $count;
				}
			}

			$latest = array();
			if ( $by_status ) {
				$latest = wc_get_orders(
					array(
						'customer_id' => $user_id,
						'status'      => $statuses,
						'limit'       => 1,
						'orderby'     => 'date',
						'order'       => 'DESC',
						'return'      => 'ids',
					)
				);
			}

			$latest_id   = $latest ? (int) reset( $latest ) : 0;
			$latest_time = 0;
			if ( $latest_id ) {
				$latest_order = wc_get_order( $latest_id );
				$created      = $latest_order ? $latest_order->get_date_created() : null;
				$latest_time  = $created ? $created->getTimestamp() : 0;
			}

			$stats = array(
				'time'        => time(),
				'total'       => (int) array_sum( $by_status ),
				'by_status'   => $by_status,
				'latest'      => $latest_id,
				'latest_time' => $latest_time,
			);

			update_user_meta( $user_id, self::META, $stats );

			return $stats;
		}
	}
}
