<?php
/**
 * Personal offers: a widget banner that gives each customer who sees it a
 * coupon of their own ( "10% off your second order", "We miss you" ), aimed
 * with the banner's visibility rules.
 *
 * The coupon is a normal WooCommerce coupon, limited to the customer's email
 * and one use, issued the first time the customer sees the banner and reused
 * after that. The banner goes away once the coupon is used or has expired.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Offers' ) ) {

	/**
	 * Personal coupons behind offer banners.
	 */
	class ACFW_Offers {

		/**
		 * User meta prefix: acfw_offer_{banner slug} => coupon ID.
		 *
		 * @var string
		 */
		const META = 'acfw_offer_';

		/**
		 * Characters of the random part of a code ( no 0/O, 1/I lookalikes ).
		 *
		 * @var string
		 */
		const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

		/**
		 * Hook the "Use it now" link and the redemption count.
		 */
		public function __construct() {
			add_action( 'template_redirect', array( $this, 'maybe_apply' ) );
			add_action( 'woocommerce_order_status_processing', array( $this, 'count_use' ), 10, 2 );
			add_action( 'woocommerce_order_status_completed', array( $this, 'count_use' ), 10, 2 );
		}

		/**
		 * Can offers work on this store ( coupons switched on in WooCommerce )?
		 *
		 * @return bool
		 */
		public static function available() {
			return class_exists( 'WC_Coupon' ) && function_exists( 'wc_coupons_enabled' ) && wc_coupons_enabled();
		}

		/**
		 * The offer's value in words: "10%" or "£5.00".
		 *
		 * @param array $banner Banner options.
		 * @return string
		 */
		public static function amount_text( $banner ) {
			$amount = (float) $banner['offer_amount'];
			if ( 'fixed_cart' === $banner['offer_type'] ) {
				return acfw_plain_price( $amount );
			}
			return rtrim( rtrim( number_format( $amount, 2, '.', '' ), '0' ), '.' ) . '%';
		}

		/**
		 * The coupon a customer holds for an offer banner, issued on first sight.
		 *
		 * @param string  $slug   Banner slug.
		 * @param array   $banner Banner options.
		 * @param WP_User $user   Customer.
		 * @return WC_Coupon|null Null when coupons are off or it could not be created.
		 */
		public static function coupon_for( $slug, $banner, $user ) {
			if ( ! self::available() || ! $user || ! $user->ID ) {
				return null;
			}
			$id = (int) get_user_meta( $user->ID, self::META . $slug, true );
			if ( $id && 'shop_coupon' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
				return new WC_Coupon( $id );
			}
			return self::issue( $slug, $banner, $user );
		}

		/**
		 * Create a customer's coupon for an offer banner.
		 *
		 * @param string  $slug   Banner slug.
		 * @param array   $banner Banner options.
		 * @param WP_User $user   Customer.
		 * @return WC_Coupon|null
		 */
		protected static function issue( $slug, $banner, $user ) {
			$emails = array_values(
				array_unique(
					array_filter(
						array(
							strtolower( (string) $user->user_email ),
							strtolower( (string) get_user_meta( $user->ID, 'billing_email', true ) ),
						)
					)
				)
			);

			$coupon = new WC_Coupon();
			$coupon->set_code( self::new_code( $banner['offer_prefix'] ) );
			$coupon->set_discount_type( 'fixed_cart' === $banner['offer_type'] ? 'fixed_cart' : 'percent' );
			$coupon->set_amount( (float) $banner['offer_amount'] );
			$coupon->set_usage_limit( 1 );
			$coupon->set_usage_limit_per_user( 1 );
			$coupon->set_email_restrictions( $emails );
			$coupon->set_free_shipping( 'yes' === $banner['offer_free_shipping'] );
			if ( (float) $banner['offer_min'] > 0 ) {
				$coupon->set_minimum_amount( (float) $banner['offer_min'] );
			}
			if ( absint( $banner['offer_days'] ) ) {
				$coupon->set_date_expires( self::last_moment( absint( $banner['offer_days'] ) ) );
			}
			$coupon->set_description(
				sprintf(
					/* translators: 1: banner name, 2: customer email. */
					__( 'Personal offer “%1$s” for %2$s, from My Account Dashboard Builder.', 'my-account-dashboard-builder' ),
					$banner['title'],
					$user->user_email
				)
			);
			$coupon->update_meta_data( '_acfw_offer', $slug );
			$coupon->update_meta_data( '_acfw_offer_user', (int) $user->ID );
			$id = $coupon->save();
			if ( ! $id ) {
				return null;
			}

			update_user_meta( $user->ID, self::META . $slug, $id );
			if ( class_exists( 'ACFW_Stats' ) && ACFW_Stats::enabled() ) {
				ACFW_Stats::bump( 'offer_issued', $slug );
			}
			return $coupon;
		}

		/**
		 * The end of the day $days days from now, in the site's timezone ( the
		 * whole last day counts ).
		 *
		 * @param int $days Days.
		 * @return int Unix time.
		 */
		public static function last_moment( $days ) {
			$end = ( new DateTimeImmutable( 'now', wp_timezone() ) )->modify( '+' . absint( $days ) . ' days' )->setTime( 23, 59, 59 );
			return $end->getTimestamp();
		}

		/**
		 * A code nobody has yet: PREFIX-XXXXXX.
		 *
		 * @param string $prefix Code prefix.
		 * @return string
		 */
		public static function new_code( $prefix ) {
			$prefix = acfw_offer_prefix( $prefix );
			do {
				$random = '';
				for ( $i = 0; $i < 6; $i++ ) {
					$random .= self::ALPHABET[ wp_rand( 0, strlen( self::ALPHABET ) - 1 ) ];
				}
				$code = $prefix . '-' . $random;
			} while ( function_exists( 'wc_get_coupon_id_by_code' ) && wc_get_coupon_id_by_code( $code ) );
			return $code;
		}

		/**
		 * Where a coupon stands.
		 *
		 * @param WC_Coupon $coupon Coupon.
		 * @return string ready | used | expired
		 */
		public static function state( $coupon ) {
			if ( $coupon->get_usage_count() >= max( 1, (int) $coupon->get_usage_limit() ) ) {
				return 'used';
			}
			$expires = $coupon->get_date_expires();
			if ( $expires && $expires->getTimestamp() < time() ) {
				return 'expired';
			}
			return 'ready';
		}

		/**
		 * The offer inside a banner for the current viewer.
		 *
		 * @param string $slug   Banner slug.
		 * @param array  $banner Banner options.
		 * @return array|null { html, vars } or null when the banner should not show.
		 */
		public static function for_viewer( $slug, $banner ) {
			if ( ! self::available() || ! is_user_logged_in() ) {
				return null;
			}

			// Shop managers see how it looks without collecting coupons.
			if ( current_user_can( 'manage_woocommerce' ) ) {
				$code    = acfw_offer_prefix( $banner['offer_prefix'] ) . '-PREVIEW';
				$expires = absint( $banner['offer_days'] ) ? wp_date( get_option( 'date_format' ), self::last_moment( absint( $banner['offer_days'] ) ) ) : '';
				return array(
					'html' => self::markup( $code, $expires, '', __( 'Preview: each customer gets a code of their own.', 'my-account-dashboard-builder' ) ),
					'vars' => self::vars( $code, $banner, $expires ),
				);
			}

			$coupon = self::coupon_for( $slug, $banner, wp_get_current_user() );
			if ( ! $coupon || 'ready' !== self::state( $coupon ) ) {
				return null;
			}
			$code    = $coupon->get_code();
			$expires = $coupon->get_date_expires() ? wp_date( get_option( 'date_format' ), $coupon->get_date_expires()->getTimestamp() ) : '';
			$apply   = function_exists( 'wc_get_cart_url' ) ? add_query_arg( 'acfw_apply', rawurlencode( $code ), wc_get_cart_url() ) : '';

			return array(
				'html' => self::markup( strtoupper( $code ), $expires, $apply, '' ),
				'vars' => self::vars( strtoupper( $code ), $banner, $expires ),
			);
		}

		/**
		 * Values for {offer_code}, {offer_amount} and {offer_expiry} in the banner text.
		 *
		 * @param string $code    Code.
		 * @param array  $banner  Banner options.
		 * @param string $expires Formatted last day, or ''.
		 * @return array
		 */
		protected static function vars( $code, $banner, $expires ) {
			return array(
				'{offer_code}'   => $code,
				'{offer_amount}' => self::amount_text( $banner ),
				'{offer_expiry}' => $expires,
			);
		}

		/**
		 * The code block: code, Copy, Use it now, last day.
		 *
		 * @param string $code    Code.
		 * @param string $expires Formatted last day, or ''.
		 * @param string $apply   "Use it now" URL, or '' for none.
		 * @param string $note    A note under the code, or ''.
		 * @return string
		 */
		protected static function markup( $code, $expires, $apply, $note ) {
			ob_start();
			?>
			<div class="acfw-offer">
				<span class="acfw-offer-label"><?php esc_html_e( 'Your code', 'my-account-dashboard-builder' ); ?></span>
				<span class="acfw-offer-row">
					<code class="acfw-offer-code"><?php echo esc_html( $code ); ?></code>
					<button type="button" class="acfw-offer-copy" data-code="<?php echo esc_attr( $code ); ?>" data-done="<?php esc_attr_e( 'Copied', 'my-account-dashboard-builder' ); ?>"><?php esc_html_e( 'Copy', 'my-account-dashboard-builder' ); ?></button>
					<?php if ( $apply ) : ?>
						<a class="acfw-offer-apply" href="<?php echo esc_url( $apply ); ?>"><?php esc_html_e( 'Use it now', 'my-account-dashboard-builder' ); ?></a>
					<?php endif; ?>
				</span>
				<?php if ( $expires ) : ?>
					<?php /* translators: %s: last day the code works. */ ?>
					<span class="acfw-offer-expiry"><?php echo esc_html( sprintf( __( 'Valid until %s', 'my-account-dashboard-builder' ), $expires ) ); ?></span>
				<?php endif; ?>
				<?php if ( $note ) : ?>
					<span class="acfw-offer-note"><?php echo esc_html( $note ); ?></span>
				<?php endif; ?>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		/**
		 * "Use it now": apply the customer's own code and go to the cart.
		 */
		public function maybe_apply() {
			if ( empty( $_GET['acfw_apply'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- applies the viewer's own coupon only.
				return;
			}
			$code = function_exists( 'wc_format_coupon_code' ) ? wc_format_coupon_code( sanitize_text_field( wp_unslash( $_GET['acfw_apply'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $code && is_user_logged_in() && function_exists( 'WC' ) && WC()->cart ) {
				$id = wc_get_coupon_id_by_code( $code );
				// Only the customer the code was made for can apply it this way.
				if ( $id && get_current_user_id() === (int) get_post_meta( $id, '_acfw_offer_user', true ) && ! WC()->cart->has_discount( $code ) ) {
					WC()->cart->apply_coupon( $code );
				}
			}
			wp_safe_redirect( remove_query_arg( 'acfw_apply' ) );
			exit;
		}

		/**
		 * Count a paid order that used an offer coupon ( once per order ).
		 *
		 * @param int           $order_id Order ID.
		 * @param WC_Order|null $order    Order.
		 */
		public function count_use( $order_id, $order = null ) {
			$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
			if ( ! $order || $order->get_meta( '_acfw_offer_counted' ) ) {
				return;
			}
			$counted = false;
			foreach ( $order->get_coupon_codes() as $code ) {
				$id   = wc_get_coupon_id_by_code( $code );
				$slug = $id ? (string) get_post_meta( $id, '_acfw_offer', true ) : '';
				if ( '' === $slug ) {
					continue;
				}
				if ( class_exists( 'ACFW_Stats' ) && ACFW_Stats::enabled() ) {
					ACFW_Stats::bump( 'offer_used', $slug );
					ACFW_Stats::bump( 'offer_revenue', $slug, (int) round( (float) $order->get_total() * 100 ) );
				}
				$counted = true;
			}
			if ( $counted ) {
				$order->update_meta_data( '_acfw_offer_counted', 1 );
				$order->save();
			}
		}
	}
}
