<?php
/**
 * Self-service order actions: cancel an order that has not shipped, and ask
 * to return items from a completed order.
 *
 * Cancelling uses WooCommerce's own cancel link and handler; this only widens
 * which orders may be cancelled, and for how long. Returns are requests the
 * store answers on the Returns tab ( approve, reject, received, refunded );
 * the customer follows them on a Returns page in My Account and by email.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Returns' ) ) {

	/**
	 * Cancellations and return requests.
	 */
	class ACFW_Returns {

		/**
		 * Post type of a return request.
		 *
		 * @var string
		 */
		const CPT = 'acfw_return';

		/**
		 * Endpoint / menu key.
		 *
		 * @var string
		 */
		const KEY = 'returns';

		/**
		 * Form nonce action.
		 *
		 * @var string
		 */
		const NONCE = 'acfw_return_request';

		/**
		 * Statuses a request can have ( status => label ).
		 *
		 * @return array
		 */
		public static function statuses() {
			return array(
				'requested' => __( 'Requested', 'my-account-dashboard-builder' ),
				'approved'  => __( 'Approved', 'my-account-dashboard-builder' ),
				'rejected'  => __( 'Not accepted', 'my-account-dashboard-builder' ),
				'received'  => __( 'Received', 'my-account-dashboard-builder' ),
				'refunded'  => __( 'Refunded', 'my-account-dashboard-builder' ),
			);
		}

		/**
		 * Statuses that still hold the items ( they cannot be asked for again ).
		 *
		 * @var string[]
		 */
		const HOLDING = array( 'requested', 'approved', 'received', 'refunded' );

		/**
		 * Hook cancellations, the Returns page and the admin order box.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'register_type' ) );
			foreach ( array( 'add_option_acfw_returns_enable', 'update_option_acfw_returns_enable' ) as $hook ) {
				add_action( $hook, array( __CLASS__, 'flag_flush' ) );
			}
			add_filter( 'acfw_disabled_keys', array( __CLASS__, 'disabled_key' ) );

			if ( self::cancel_enabled() ) {
				add_filter( 'woocommerce_valid_order_statuses_for_cancel', array( $this, 'cancellable_statuses' ), 10, 2 );
				add_action( 'woocommerce_cancelled_order', array( $this, 'note_paid_cancel' ) );
			}

			if ( self::returns_enabled() ) {
				add_filter( 'woocommerce_account_menu_items', array( $this, 'menu_item' ), 20 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
				add_action( 'woocommerce_account_' . self::KEY . '_endpoint', array( $this, 'render_page' ) );
				add_action( 'template_redirect', array( $this, 'handle_request' ) );
				add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'order_action' ), 10, 2 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
				add_action( 'add_meta_boxes', array( $this, 'order_meta_box' ) );
			}

			if ( self::cancel_enabled() || self::returns_enabled() ) {
				add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_buttons' ) );
			}
		}

		/**
		 * Can customers cancel orders before they ship?
		 *
		 * @return bool
		 */
		public static function cancel_enabled() {
			return 'yes' === get_option( 'acfw_cancel_enable', 'no' );
		}

		/**
		 * Can customers ask to return items?
		 *
		 * @return bool
		 */
		public static function returns_enabled() {
			return 'yes' === get_option( 'acfw_returns_enable', 'no' );
		}

		/**
		 * Rewrite rules follow the switch.
		 */
		public static function flag_flush() {
			update_option( 'acfw_flush_rewrite_rules', 1 );
		}

		/**
		 * Hide the Returns item from a saved menu while returns are off.
		 *
		 * @param string[] $keys Menu keys.
		 * @return string[]
		 */
		public static function disabled_key( $keys ) {
			if ( ! self::returns_enabled() ) {
				$keys[] = self::KEY;
			}
			return $keys;
		}

		/**
		 * The request post type ( not public; answered on the Returns tab ).
		 */
		public function register_type() {
			register_post_type(
				self::CPT,
				array(
					'labels'          => array( 'name' => __( 'Returns', 'my-account-dashboard-builder' ) ),
					'public'          => false,
					'show_ui'         => false,
					'rewrite'         => false,
					'query_var'       => false,
					'supports'        => array( 'title' ),
					'capability_type' => 'shop_order',
					'map_meta_cap'    => true,
				)
			);
		}

		// ---- Cancelling -------------------------------------------------------------

		/**
		 * Has the order shipped ( completed, or carrying tracking numbers )?
		 *
		 * @param WC_Order $order Order.
		 * @return bool
		 */
		public static function has_shipped( $order ) {
			return $order->has_status( 'completed' ) || ! empty( $order->get_meta( '_wc_shipment_tracking_items' ) );
		}

		/**
		 * Until when the customer may cancel ( Unix time ), or 0 for no limit.
		 *
		 * @param WC_Order $order Order.
		 * @return int
		 */
		public static function cancel_until( $order ) {
			$hours   = absint( get_option( 'acfw_cancel_hours', 24 ) );
			$created = $order->get_date_created();
			return ( $hours && $created ) ? $created->getTimestamp() + $hours * HOUR_IN_SECONDS : 0;
		}

		/**
		 * Let customers cancel on-hold and processing orders that have not shipped,
		 * within the window ( WooCommerce itself allows pending and failed ).
		 *
		 * @param string[]      $statuses Statuses.
		 * @param WC_Order|null $order    Order.
		 * @return string[]
		 */
		public function cancellable_statuses( $statuses, $order = null ) {
			if ( ! $order instanceof WC_Order || self::has_shipped( $order ) ) {
				return $statuses;
			}
			$until = self::cancel_until( $order );
			if ( $until && time() > $until ) {
				return $statuses;
			}
			return array_values( array_unique( array_merge( (array) $statuses, array( 'pending', 'on-hold', 'processing' ) ) ) );
		}

		/**
		 * A paid order the customer cancelled needs a refund: say so on the order.
		 *
		 * @param int $order_id Order ID.
		 */
		public function note_paid_cancel( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order && $order->get_date_paid() ) {
				$order->add_order_note( __( 'Cancelled by the customer from My Account before it shipped. The payment was taken: refund it from this screen.', 'my-account-dashboard-builder' ) );
			}
		}

		// ---- Returns: rules ----------------------------------------------------------

		/**
		 * Reasons offered in the form.
		 *
		 * @return string[]
		 */
		public static function reasons() {
			$raw     = (string) get_option( 'acfw_returns_reasons', '' );
			$reasons = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) ) );
			return $reasons ? $reasons : array(
				__( 'Wrong size or fit', 'my-account-dashboard-builder' ),
				__( 'Damaged or faulty', 'my-account-dashboard-builder' ),
				__( 'Not as described', 'my-account-dashboard-builder' ),
				__( 'Changed my mind', 'my-account-dashboard-builder' ),
				__( 'Something else', 'my-account-dashboard-builder' ),
			);
		}

		/**
		 * The last day a completed order can be returned ( Unix time ).
		 *
		 * @param WC_Order $order Order.
		 * @return int
		 */
		public static function return_until( $order ) {
			$done = $order->get_date_completed() ? $order->get_date_completed() : $order->get_date_created();
			$days = max( 1, absint( get_option( 'acfw_returns_days', 30 ) ) );
			return $done ? $done->getTimestamp() + $days * DAY_IN_SECONDS : 0;
		}

		/**
		 * Every request for an order.
		 *
		 * @param int $order_id Order ID.
		 * @return WP_Post[]
		 */
		public static function for_order( $order_id ) {
			return get_posts(
				array(
					'post_type'   => self::CPT,
					'post_status' => 'publish',
					'numberposts' => -1,
					'meta_key'    => '_acfw_order_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'  => (int) $order_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
		}

		/**
		 * How many of each order line can still be asked for.
		 *
		 * @param WC_Order $order Order.
		 * @return array Item ID => quantity left.
		 */
		public static function returnable( $order ) {
			$held = array();
			foreach ( self::for_order( $order->get_id() ) as $request ) {
				if ( ! in_array( get_post_meta( $request->ID, '_acfw_status', true ), self::HOLDING, true ) ) {
					continue;
				}
				foreach ( (array) get_post_meta( $request->ID, '_acfw_items', true ) as $item_id => $qty ) {
					$held[ (int) $item_id ] = ( $held[ (int) $item_id ] ?? 0 ) + (int) $qty;
				}
			}
			$left = array();
			foreach ( $order->get_items() as $item_id => $item ) {
				$qty = (int) $item->get_quantity() + (int) $order->get_qty_refunded_for_item( $item_id ) - ( $held[ $item_id ] ?? 0 );
				if ( $qty > 0 ) {
					$left[ $item_id ] = $qty;
				}
			}
			return $left;
		}

		/**
		 * How many of a customer's return requests are still open ( requested,
		 * approved or received ).
		 *
		 * @param int $user_id Customer.
		 * @return int
		 */
		public static function open_count( $user_id ) {
			if ( ! $user_id ) {
				return 0;
			}
			$ids = get_posts(
				array(
					'post_type'   => self::CPT,
					'post_status' => 'publish',
					'author'      => (int) $user_id,
					'numberposts' => 50,
					'fields'      => 'ids',
					'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a customer's own few requests.
						array(
							'key'     => '_acfw_status',
							'value'   => array( 'requested', 'approved', 'received' ),
							'compare' => 'IN',
						),
					),
				)
			);
			return count( $ids );
		}

		/**
		 * Can the customer ask to return items from this order now?
		 *
		 * @param WC_Order $order Order.
		 * @return bool
		 */
		public static function can_return( $order ) {
			return $order->has_status( 'completed' ) && time() <= self::return_until( $order ) && (bool) self::returnable( $order );
		}

		// ---- Returns: My Account -------------------------------------------------------

		/**
		 * Add "Returns" above Log out.
		 *
		 * @param array $items Key => label.
		 * @return array
		 */
		public function menu_item( $items ) {
			if ( isset( $items[ self::KEY ] ) ) {
				return $items;
			}
			$logout = isset( $items['customer-logout'] ) ? array( 'customer-logout' => $items['customer-logout'] ) : array();
			unset( $items['customer-logout'] );
			$items[ self::KEY ] = __( 'Returns', 'my-account-dashboard-builder' );
			return $items + $logout;
		}

		/**
		 * "Return items" in the Orders list.
		 *
		 * @param array    $actions Actions.
		 * @param WC_Order $order   Order.
		 * @return array
		 */
		public function order_action( $actions, $order ) {
			if ( $order instanceof WC_Order && self::can_return( $order ) ) {
				$actions['acfw-return'] = array(
					'url'  => add_query_arg( 'order', $order->get_id(), wc_get_account_endpoint_url( self::KEY ) ),
					'name' => __( 'Return items', 'my-account-dashboard-builder' ),
				);
			}
			return $actions;
		}

		/**
		 * Cancel / Return buttons under an order's details.
		 *
		 * @param WC_Order $order Order.
		 */
		public function order_buttons( $order ) {
			if ( ! $order instanceof WC_Order || ! is_account_page() || get_current_user_id() !== (int) $order->get_customer_id() ) {
				return;
			}
			$buttons = array();
			$cancel  = apply_filters( 'woocommerce_valid_order_statuses_for_cancel', array( 'pending', 'failed' ), $order ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce filter.
			if ( self::cancel_enabled() && $order->has_status( $cancel ) ) {
				$until     = self::cancel_until( $order );
				$buttons[] = array(
					'url'   => $order->get_cancel_order_url( wc_get_account_endpoint_url( 'orders' ) ),
					'label' => __( 'Cancel order', 'my-account-dashboard-builder' ),
					/* translators: %s: date and time. */
					'note'  => $until ? sprintf( __( 'You can cancel until %s.', 'my-account-dashboard-builder' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $until ) ) : '',
					'ask'   => __( 'Cancel this order?', 'my-account-dashboard-builder' ),
				);
			}
			if ( self::returns_enabled() && self::can_return( $order ) ) {
				$buttons[] = array(
					'url'   => add_query_arg( 'order', $order->get_id(), wc_get_account_endpoint_url( self::KEY ) ),
					'label' => __( 'Return items', 'my-account-dashboard-builder' ),
					/* translators: %s: last day. */
					'note'  => sprintf( __( 'Returns are open until %s.', 'my-account-dashboard-builder' ), wp_date( get_option( 'date_format' ), self::return_until( $order ) ) ),
					'ask'   => '',
				);
			}
			if ( ! $buttons ) {
				return;
			}
			$in_table = self::details_show_actions();
			echo '<div class="acfw-order-actions">';
			foreach ( $buttons as $button ) {
				if ( $in_table ) {
					// The buttons are in the order table's Actions row; say until when.
					if ( $button['note'] ) {
						echo '<p class="acfw-order-actions-note">' . esc_html( $button['note'] ) . '</p>';
					}
					continue;
				}
				echo '<p><a class="' . esc_attr( acfw_button_class() ) . '" href="' . esc_url( $button['url'] ) . '"' . ( $button['ask'] ? ' data-acfw-confirm="' . esc_attr( $button['ask'] ) . '"' : '' ) . '>' . esc_html( $button['label'] ) . '</a>';
				if ( $button['note'] ) {
					echo ' <span class="acfw-order-actions-note">' . esc_html( $button['note'] ) . '</span>';
				}
				echo '</p>';
			}
			echo '</div>';
		}

		/**
		 * Does the order page's template print an Actions row itself? WooCommerce's
		 * has since 8.9; an older copy in a theme may not.
		 *
		 * @return bool
		 */
		protected static function details_show_actions() {
			static $has = null;
			if ( null === $has ) {
				$file = function_exists( 'wc_locate_template' ) ? wc_locate_template( 'order/order-details.php' ) : '';
				$has  = $file && is_readable( $file ) && false !== strpos( (string) file_get_contents( $file ), 'wc_get_account_orders_actions' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local template file.
			}
			return $has;
		}

		/**
		 * The Returns page: the form for one order, then every request.
		 */
		public function render_page() {
			$order_id = isset( $_GET['order'] ) ? absint( wp_unslash( $_GET['order'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which order to show.
			$order    = $order_id ? wc_get_order( $order_id ) : null;
			$form     = false;
			if ( $order && get_current_user_id() === (int) $order->get_customer_id() ) {
				if ( self::can_return( $order ) ) {
					$this->render_form( $order );
					$form = true;
				} else {
					echo '<p class="woocommerce-info">' . esc_html__( 'This order cannot be returned any more.', 'my-account-dashboard-builder' ) . '</p>';
				}
			}
			$this->render_list( $form );
		}

		/**
		 * The request form.
		 *
		 * @param WC_Order $order Order.
		 */
		protected function render_form( $order ) {
			$left = self::returnable( $order );
			?>
			<form method="post" enctype="multipart/form-data" class="acfw-return-form">
				<?php /* translators: %s: order number. */ ?>
				<h3><?php echo esc_html( sprintf( __( 'Return items from order #%s', 'my-account-dashboard-builder' ), $order->get_order_number() ) ); ?></h3>
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="acfw_return_order" value="<?php echo esc_attr( $order->get_id() ); ?>" />
				<table class="shop_table acfw-return-items">
					<thead><tr><th><?php esc_html_e( 'Item', 'my-account-dashboard-builder' ); ?></th><th><?php esc_html_e( 'Quantity to return', 'my-account-dashboard-builder' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
							<?php
							if ( empty( $left[ $item_id ] ) ) {
								continue;
							}
							?>
							<tr>
								<td><?php echo esc_html( $item->get_name() ); ?></td>
								<td>
									<select name="acfw_return_qty[<?php echo esc_attr( $item_id ); ?>]">
										<?php for ( $q = 0; $q <= $left[ $item_id ]; $q++ ) : ?>
											<option value="<?php echo esc_attr( $q ); ?>" <?php selected( 1 === count( $left ) && $q === $left[ $item_id ] ); ?>><?php echo esc_html( $q ); ?></option>
										<?php endfor; ?>
									</select>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="form-row form-row-wide">
					<label for="acfw_return_reason"><?php esc_html_e( 'Why are you returning it?', 'my-account-dashboard-builder' ); ?> <abbr class="required" title="<?php esc_attr_e( 'required', 'my-account-dashboard-builder' ); ?>">*</abbr></label>
					<select name="acfw_return_reason" id="acfw_return_reason" required>
						<option value=""><?php esc_html_e( 'Choose…', 'my-account-dashboard-builder' ); ?></option>
						<?php foreach ( self::reasons() as $reason ) : ?>
							<option value="<?php echo esc_attr( $reason ); ?>"><?php echo esc_html( $reason ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p class="form-row form-row-wide">
					<label for="acfw_return_comment"><?php esc_html_e( 'Anything else we should know?', 'my-account-dashboard-builder' ); ?></label>
					<textarea name="acfw_return_comment" id="acfw_return_comment" rows="3" maxlength="1000"></textarea>
				</p>
				<p class="form-row form-row-wide">
					<label for="acfw_return_photo"><?php esc_html_e( 'A photo (optional, e.g. of the damage)', 'my-account-dashboard-builder' ); ?></label>
					<input type="file" name="acfw_return_photo" id="acfw_return_photo" accept="image/jpeg,image/png,image/webp" />
				</p>
				<p><button type="submit" class="<?php echo esc_attr( acfw_button_class() ); ?>" name="acfw_return_submit" value="1"><?php esc_html_e( 'Send return request', 'my-account-dashboard-builder' ); ?></button></p>
			</form>
			<?php
		}

		/**
		 * The customer's requests.
		 *
		 * @param bool $below_form The request form is open above ( no empty-list hint then ).
		 */
		protected function render_list( $below_form = false ) {
			$requests = get_posts(
				array(
					'post_type'   => self::CPT,
					'post_status' => 'publish',
					'author'      => get_current_user_id(),
					'numberposts' => 50,
				)
			);
			if ( ! $requests ) {
				if ( $below_form ) {
					return;
				}
				echo '<p class="acfw-returns-empty">' . esc_html__( 'You have no returns. To return items, open a completed order and choose “Return items”.', 'my-account-dashboard-builder' ) . '</p>';
				return;
			}
			$labels = self::statuses();
			?>
			<table class="shop_table shop_table_responsive acfw-returns-table">
				<thead><tr><th><?php esc_html_e( 'Order', 'my-account-dashboard-builder' ); ?></th><th><?php esc_html_e( 'Items', 'my-account-dashboard-builder' ); ?></th><th><?php esc_html_e( 'Status', 'my-account-dashboard-builder' ); ?></th><th><?php esc_html_e( 'Requested', 'my-account-dashboard-builder' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $requests as $request ) : ?>
						<?php
						$order  = wc_get_order( (int) get_post_meta( $request->ID, '_acfw_order_id', true ) );
						$status = (string) get_post_meta( $request->ID, '_acfw_status', true );
						$note   = (string) get_post_meta( $request->ID, '_acfw_reply', true );
						?>
						<tr>
							<td data-title="<?php esc_attr_e( 'Order', 'my-account-dashboard-builder' ); ?>"><?php echo $order ? '<a href="' . esc_url( $order->get_view_order_url() ) . '">#' . esc_html( $order->get_order_number() ) . '</a>' : '—'; ?></td>
							<td data-title="<?php esc_attr_e( 'Items', 'my-account-dashboard-builder' ); ?>"><?php echo esc_html( self::items_text( $request->ID, $order ) ); ?></td>
							<td data-title="<?php esc_attr_e( 'Status', 'my-account-dashboard-builder' ); ?>">
								<span class="acfw-return-status acfw-return-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $labels[ $status ] ?? $status ); ?></span>
								<?php if ( '' !== $note ) : ?>
									<span class="acfw-return-reply"><?php echo esc_html( $note ); ?></span>
								<?php endif; ?>
							</td>
							<td data-title="<?php esc_attr_e( 'Requested', 'my-account-dashboard-builder' ); ?>"><?php echo esc_html( get_the_date( '', $request ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}

		/**
		 * "2 × Jacket, 1 × Scarf".
		 *
		 * @param int           $request_id Request.
		 * @param WC_Order|null $order      Order.
		 * @return string
		 */
		public static function items_text( $request_id, $order ) {
			$parts = array();
			foreach ( (array) get_post_meta( $request_id, '_acfw_items', true ) as $item_id => $qty ) {
				$item    = $order ? $order->get_item( (int) $item_id ) : null;
				$parts[] = (int) $qty . ' × ' . ( $item ? $item->get_name() : '#' . (int) $item_id );
			}
			return implode( ', ', $parts );
		}

		/**
		 * File a request from the form.
		 */
		public function handle_request() {
			if ( empty( $_POST['acfw_return_submit'] ) || ! is_user_logged_in() ) {
				return;
			}
			check_admin_referer( self::NONCE );
			$order = wc_get_order( isset( $_POST['acfw_return_order'] ) ? absint( wp_unslash( $_POST['acfw_return_order'] ) ) : 0 );
			$back  = wc_get_account_endpoint_url( self::KEY );
			if ( ! $order || get_current_user_id() !== (int) $order->get_customer_id() || ! self::can_return( $order ) ) {
				wc_add_notice( __( 'This order cannot be returned.', 'my-account-dashboard-builder' ), 'error' );
				wp_safe_redirect( $back );
				exit;
			}

			$left  = self::returnable( $order );
			$items = array();
			foreach ( isset( $_POST['acfw_return_qty'] ) ? (array) wp_unslash( $_POST['acfw_return_qty'] ) : array() as $item_id => $qty ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cast below.
				$item_id = absint( $item_id );
				$qty     = min( absint( $qty ), $left[ $item_id ] ?? 0 );
				if ( $qty > 0 ) {
					$items[ $item_id ] = $qty;
				}
			}
			$reason = isset( $_POST['acfw_return_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['acfw_return_reason'] ) ) : '';
			$again  = add_query_arg( 'order', $order->get_id(), $back );
			if ( ! $items ) {
				wc_add_notice( __( 'Choose how many of an item to return.', 'my-account-dashboard-builder' ), 'error' );
				wp_safe_redirect( $again );
				exit;
			}
			if ( ! in_array( $reason, self::reasons(), true ) ) {
				wc_add_notice( __( 'Choose a reason for the return.', 'my-account-dashboard-builder' ), 'error' );
				wp_safe_redirect( $again );
				exit;
			}

			$id = wp_insert_post(
				array(
					'post_type'   => self::CPT,
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
					/* translators: %s: order number. */
					'post_title'  => sprintf( __( 'Return for order #%s', 'my-account-dashboard-builder' ), $order->get_order_number() ),
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				wc_add_notice( __( 'The request could not be sent. Please try again.', 'my-account-dashboard-builder' ), 'error' );
				wp_safe_redirect( $again );
				exit;
			}
			update_post_meta( $id, '_acfw_order_id', $order->get_id() );
			update_post_meta( $id, '_acfw_items', $items );
			update_post_meta( $id, '_acfw_reason', $reason );
			update_post_meta( $id, '_acfw_comment', isset( $_POST['acfw_return_comment'] ) ? substr( sanitize_textarea_field( wp_unslash( $_POST['acfw_return_comment'] ) ), 0, 1000 ) : '' );
			update_post_meta( $id, '_acfw_status', 'requested' );

			$photo = $this->store_photo( $id );
			if ( $photo ) {
				update_post_meta( $id, '_acfw_photo', $photo );
			}

			/* translators: %s: reason. */
			$order->add_order_note( sprintf( __( 'The customer asked to return items (%s). Answer on My Account → Returns.', 'my-account-dashboard-builder' ), $reason ) );
			self::notify_store( $id, $order );
			wc_add_notice( __( 'Return request sent. We will email you when we have looked at it.', 'my-account-dashboard-builder' ), 'success' );
			wp_safe_redirect( $back );
			exit;
		}

		/**
		 * Keep the photo, if one came with the request ( images up to 5 MB ).
		 *
		 * @param int $request_id Request.
		 * @return int Attachment ID, 0 for none.
		 */
		protected function store_photo( $request_id ) {
			if ( empty( $_FILES['acfw_return_photo']['name'] ) || ! empty( $_FILES['acfw_return_photo']['error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked in handle_request().
				return 0;
			}
			$size = isset( $_FILES['acfw_return_photo']['size'] ) ? (int) $_FILES['acfw_return_photo']['size'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$type = wp_check_filetype( sanitize_file_name( wp_unslash( $_FILES['acfw_return_photo']['name'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( $size > 5 * MB_IN_BYTES || ! in_array( $type['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
				wc_add_notice( __( 'The photo was left out: use a JPG, PNG or WebP image up to 5 MB.', 'my-account-dashboard-builder' ), 'notice' );
				return 0;
			}
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			$id = media_handle_upload( 'acfw_return_photo', $request_id, array(), array( 'test_form' => false ) );
			return is_wp_error( $id ) ? 0 : (int) $id;
		}

		// ---- Emails ---------------------------------------------------------------------

		/**
		 * Send an email in WooCommerce's layout.
		 *
		 * @param string $to      Recipient.
		 * @param string $subject Subject.
		 * @param string $heading Heading.
		 * @param string $body    HTML body.
		 */
		protected static function mail( $to, $subject, $heading, $body ) {
			if ( ! function_exists( 'WC' ) || ! $to ) {
				return;
			}
			$mailer = WC()->mailer();
			$mailer->send( $to, $subject, $mailer->wrap_message( $heading, $body ), "Content-Type: text/html\r\n" );
		}

		/**
		 * Tell the store about a new request.
		 *
		 * @param int      $request_id Request.
		 * @param WC_Order $order      Order.
		 */
		protected static function notify_store( $request_id, $order ) {
			$link = admin_url( 'admin.php?page=acfw-settings&tab=returns' );
			$body = '<p>' . esc_html(
				sprintf(
					/* translators: 1: customer name, 2: order number. */
					__( '%1$s asked to return items from order #%2$s.', 'my-account-dashboard-builder' ),
					$order->get_formatted_billing_full_name(),
					$order->get_order_number()
				)
			) . '</p><p><strong>' . esc_html( self::items_text( $request_id, $order ) ) . '</strong></p><p>' . esc_html( (string) get_post_meta( $request_id, '_acfw_reason', true ) ) . '</p><p><a href="' . esc_url( $link ) . '">' . esc_html__( 'Answer the request', 'my-account-dashboard-builder' ) . '</a></p>';
			/* translators: %s: order number. */
			self::mail( get_option( 'admin_email' ), sprintf( __( 'Return request for order #%s', 'my-account-dashboard-builder' ), $order->get_order_number() ), __( 'New return request', 'my-account-dashboard-builder' ), $body );
		}

		/**
		 * Change a request's status and tell the customer.
		 *
		 * @param int    $request_id Request.
		 * @param string $status     New status.
		 * @param string $reply      A message for the customer.
		 * @return bool
		 */
		public static function set_status( $request_id, $status, $reply = '' ) {
			$statuses = self::statuses();
			if ( self::CPT !== get_post_type( $request_id ) || ! isset( $statuses[ $status ] ) ) {
				return false;
			}
			update_post_meta( $request_id, '_acfw_status', $status );
			update_post_meta( $request_id, '_acfw_reply', $reply );
			$order = wc_get_order( (int) get_post_meta( $request_id, '_acfw_order_id', true ) );
			$user  = get_userdata( (int) get_post_field( 'post_author', $request_id ) );
			if ( $order ) {
				/* translators: %s: new status. */
				$order->add_order_note( sprintf( __( 'Return request marked “%s”.', 'my-account-dashboard-builder' ), $statuses[ $status ] ) );
			}
			if ( $user ) {
				$body = '<p>' . esc_html(
					sprintf(
						/* translators: 1: order number, 2: status. */
						__( 'Your return for order #%1$s is now: %2$s.', 'my-account-dashboard-builder' ),
						$order ? $order->get_order_number() : '',
						$statuses[ $status ]
					)
				) . '</p>' . ( '' !== $reply ? '<p>' . esc_html( $reply ) . '</p>' : '' ) . '<p><a href="' . esc_url( wc_get_account_endpoint_url( self::KEY ) ) . '">' . esc_html__( 'See your returns', 'my-account-dashboard-builder' ) . '</a></p>';
				/* translators: %s: status. */
				self::mail( $user->user_email, sprintf( __( 'Your return: %s', 'my-account-dashboard-builder' ), $statuses[ $status ] ), __( 'Your return', 'my-account-dashboard-builder' ), $body );
			}
			return true;
		}

		// ---- Admin: the order screen ------------------------------------------------------

		/**
		 * A "Returns" box on the order screen ( classic and HPOS ).
		 */
		public function order_meta_box() {
			$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
			add_meta_box( 'acfw-returns', __( 'Returns', 'my-account-dashboard-builder' ), array( $this, 'order_box' ), $screen, 'side', 'default' );
		}

		/**
		 * The box contents.
		 *
		 * @param WP_Post|WC_Order $post_or_order Post ( classic ) or order ( HPOS ).
		 */
		public function order_box( $post_or_order ) {
			$order    = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
			$requests = $order ? self::for_order( $order->get_id() ) : array();
			if ( ! $requests ) {
				echo '<p>' . esc_html__( 'No return requests.', 'my-account-dashboard-builder' ) . '</p>';
				return;
			}
			$labels = self::statuses();
			echo '<ul>';
			foreach ( $requests as $request ) {
				$status = (string) get_post_meta( $request->ID, '_acfw_status', true );
				echo '<li><strong>' . esc_html( $labels[ $status ] ?? $status ) . '</strong> · ' . esc_html( self::items_text( $request->ID, $order ) ) . '</li>';
			}
			echo '</ul><p><a href="' . esc_url( admin_url( 'admin.php?page=acfw-settings&tab=returns' ) ) . '">' . esc_html__( 'Answer returns', 'my-account-dashboard-builder' ) . '</a></p>';
		}
	}
}
