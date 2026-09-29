<?php
/**
 * Privacy self-service: a "Privacy" page in My Account where customers ask
 * for a copy of their data or for it to be erased.
 *
 * It files WordPress's own privacy requests ( Tools → Export / Erase Personal
 * Data ): WordPress emails the customer a link to confirm, and the store
 * finishes the request there as usual. Nothing is erased from this page.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Privacy' ) ) {

	/**
	 * The Privacy endpoint.
	 */
	class ACFW_Privacy {

		/**
		 * Endpoint / menu key.
		 *
		 * @var string
		 */
		const KEY = 'privacy';

		/**
		 * Form nonce action.
		 *
		 * @var string
		 */
		const NONCE = 'acfw_privacy';

		/**
		 * Request types this page files ( WordPress action names ).
		 *
		 * @var string[]
		 */
		const TYPES = array( 'export_personal_data', 'remove_personal_data' );

		/**
		 * Hook the endpoint when the feature is on.
		 */
		public function __construct() {
			foreach ( array( 'add_option_acfw_privacy_enable', 'update_option_acfw_privacy_enable' ) as $hook ) {
				add_action( $hook, array( __CLASS__, 'flag_flush' ) );
			}
			add_filter( 'acfw_disabled_keys', array( __CLASS__, 'disabled_key' ) );
			if ( ! self::enabled() ) {
				return;
			}
			add_filter( 'woocommerce_account_menu_items', array( $this, 'menu_item' ), 20 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
			add_action( 'woocommerce_account_' . self::KEY . '_endpoint', array( $this, 'render' ) );
			add_action( 'template_redirect', array( $this, 'handle' ) );
		}

		/**
		 * Is the Privacy page switched on?
		 *
		 * @return bool
		 */
		public static function enabled() {
			return 'yes' === get_option( 'acfw_privacy_enable', 'no' );
		}

		/**
		 * Rewrite rules follow the switch.
		 */
		public static function flag_flush() {
			update_option( 'acfw_flush_rewrite_rules', 1 );
		}

		/**
		 * Hide the item from a saved menu while the feature is off.
		 *
		 * @param string[] $keys Menu keys.
		 * @return string[]
		 */
		public static function disabled_key( $keys ) {
			if ( ! self::enabled() ) {
				$keys[] = self::KEY;
			}
			return $keys;
		}

		/**
		 * Add "Privacy" above Log out.
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
			$items[ self::KEY ] = __( 'Privacy', 'my-account-dashboard-builder' );
			return $items + $logout;
		}

		/**
		 * The customer's latest request of a type.
		 *
		 * @param string $email Email.
		 * @param string $type  export_personal_data | remove_personal_data.
		 * @return WP_Post|null
		 */
		public static function latest_request( $email, $type ) {
			$posts = get_posts(
				array(
					'post_type'     => 'user_request',
					'post_name__in' => array( $type ),
					'title'         => $email,
					'post_status'   => array( 'request-pending', 'request-confirmed', 'request-failed', 'request-completed' ),
					'numberposts'   => 1,
					'orderby'       => 'date',
					'order'         => 'DESC',
				)
			);
			return $posts ? $posts[0] : null;
		}

		/**
		 * File a request from the form.
		 */
		public function handle() {
			if ( empty( $_POST['acfw_privacy_request'] ) || ! is_user_logged_in() ) {
				return;
			}
			check_admin_referer( self::NONCE );
			$type = sanitize_key( wp_unslash( $_POST['acfw_privacy_request'] ) );
			if ( ! in_array( $type, self::TYPES, true ) ) {
				return;
			}
			if ( 'remove_personal_data' === $type && empty( $_POST['acfw_privacy_understand'] ) ) {
				wc_add_notice( __( 'Tick the box to confirm you understand that erasing your data cannot be undone.', 'my-account-dashboard-builder' ), 'error' );
				return;
			}

			$email   = wp_get_current_user()->user_email;
			$request = wp_create_user_request( $email, $type );
			if ( is_wp_error( $request ) ) {
				// An open request of this type already exists.
				wc_add_notice( __( 'You already have a request waiting. Check your email for the confirmation link.', 'my-account-dashboard-builder' ), 'notice' );
			} else {
				wp_send_user_request( $request );
				wc_add_notice( __( 'Request sent. Check your email and click the link to confirm it.', 'my-account-dashboard-builder' ), 'success' );
			}
			wp_safe_redirect( wc_get_account_endpoint_url( self::KEY ) );
			exit;
		}

		/**
		 * A request's status in words.
		 *
		 * @param WP_Post|null $request Request.
		 * @return string
		 */
		protected static function status_text( $request ) {
			if ( ! $request ) {
				return '';
			}
			$date = wp_date( get_option( 'date_format' ), strtotime( $request->post_date_gmt . ' UTC' ) );
			switch ( $request->post_status ) {
				case 'request-pending':
					/* translators: %s: date of the request. */
					return sprintf( __( 'Requested on %s. Waiting for you to confirm it from the email we sent.', 'my-account-dashboard-builder' ), $date );
				case 'request-confirmed':
					/* translators: %s: date of the request. */
					return sprintf( __( 'Confirmed on %s. The store is working on it.', 'my-account-dashboard-builder' ), $date );
				case 'request-completed':
					/* translators: %s: date of the request. */
					return sprintf( __( 'Done (requested on %s).', 'my-account-dashboard-builder' ), $date );
				default:
					return __( 'The last request was not confirmed in time. You can send a new one.', 'my-account-dashboard-builder' );
			}
		}

		/**
		 * The Privacy page.
		 */
		public function render() {
			$email  = wp_get_current_user()->user_email;
			$export = self::latest_request( $email, 'export_personal_data' );
			$erase  = self::latest_request( $email, 'remove_personal_data' );
			$open   = array( 'request-pending', 'request-confirmed' );
			?>
			<div class="acfw-privacy">
				<section class="acfw-privacy-card">
					<h3><?php esc_html_e( 'Download your data', 'my-account-dashboard-builder' ); ?></h3>
					<p><?php esc_html_e( 'Get a copy of the personal data this store keeps about you: your account, addresses and orders. We email you to confirm the request, then send a download link once it is ready.', 'my-account-dashboard-builder' ); ?></p>
					<?php if ( $export ) : ?>
						<p class="acfw-privacy-status"><?php echo esc_html( self::status_text( $export ) ); ?></p>
					<?php endif; ?>
					<form method="post">
						<?php wp_nonce_field( self::NONCE ); ?>
						<button type="submit" class="<?php echo esc_attr( acfw_button_class() ); ?>" name="acfw_privacy_request" value="export_personal_data"<?php disabled( $export && in_array( $export->post_status, $open, true ) ); ?>><?php esc_html_e( 'Request my data', 'my-account-dashboard-builder' ); ?></button>
					</form>
				</section>

				<section class="acfw-privacy-card">
					<h3><?php esc_html_e( 'Erase your data', 'my-account-dashboard-builder' ); ?></h3>
					<p><?php esc_html_e( 'Ask the store to erase your personal data. Orders the store has to keep, for example for tax, are kept with your details removed where the law allows.', 'my-account-dashboard-builder' ); ?></p>
					<?php if ( $erase ) : ?>
						<p class="acfw-privacy-status"><?php echo esc_html( self::status_text( $erase ) ); ?></p>
					<?php endif; ?>
					<form method="post">
						<?php wp_nonce_field( self::NONCE ); ?>
						<p><label><input type="checkbox" name="acfw_privacy_understand" value="1" /> <?php esc_html_e( 'I understand this cannot be undone.', 'my-account-dashboard-builder' ); ?></label></p>
						<button type="submit" class="<?php echo esc_attr( acfw_button_class() ); ?>" name="acfw_privacy_request" value="remove_personal_data"<?php disabled( $erase && in_array( $erase->post_status, $open, true ) ); ?>><?php esc_html_e( 'Request erasure', 'my-account-dashboard-builder' ); ?></button>
					</form>
				</section>
			</div>
			<?php
		}
	}
}
