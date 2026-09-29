<?php
/**
 * View as a customer: a shop manager opens My Account the way one customer
 * sees it, with that customer's menu, group menu, rules, badges, offers and
 * dashboard, and a bar saying what is hidden from them and why.
 *
 * The preview runs the request as the customer, so everything on the page
 * is theirs. It never signs anyone in: the link carries a signed, short-lived
 * token that only works for the manager it was made for, on plain GET
 * requests to My Account. While it runs:
 *  - forms, cart and order actions, Log out and links with a nonce are
 *    refused, in the browser and on the server;
 *  - WooCommerce gets a session that lives in memory only, so the customer's
 *    cart and session are not touched, and "last active" is not updated;
 *  - no coupon is created, and nothing is counted in Insights.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_View_As' ) ) {

	/**
	 * View as a customer.
	 */
	class ACFW_View_As {

		/**
		 * Query args: the customer, when the link expires, and its signature.
		 */
		const ARG_USER = 'acfw_view_as';
		const ARG_EXP  = 'acfw_view_exp';
		const ARG_KEY  = 'acfw_view_key';

		/**
		 * How long a preview link works.
		 *
		 * @var int
		 */
		const TTL = 7200;

		/**
		 * AJAX nonce action for the customer search.
		 *
		 * @var string
		 */
		const NONCE = 'acfw_view_as';

		/**
		 * The shop manager who opened the preview.
		 *
		 * @var int
		 */
		protected static $real = 0;

		/**
		 * The customer the page is shown as.
		 *
		 * @var int
		 */
		protected static $target = 0;

		/**
		 * A preview link was used for something other than looking.
		 *
		 * @var bool
		 */
		protected static $refused = false;

		/**
		 * Swap the current user as early as WordPress resolves it. Called when
		 * the plugin file loads, before any other plugin can ask who is logged in.
		 */
		public static function boot() {
			// After WordPress' own cookie checks ( 10, 20 ).
			add_filter( 'determine_current_user', array( __CLASS__, 'determine' ), 100 );
		}

		/**
		 * Hook the preview's guards, its bar and the customer search.
		 */
		public function __construct() {
			add_action( 'wp_ajax_acfw_view_as_search', array( $this, 'ajax_search' ) );
			add_action( 'init', array( $this, 'guard' ), 0 );
			add_filter( 'woocommerce_session_handler', array( __CLASS__, 'session_handler' ) );
		}

		/**
		 * Whether this request is a preview as a customer.
		 *
		 * @return bool
		 */
		public static function active() {
			return self::$target > 0;
		}

		/**
		 * The shop manager behind the preview ( 0 when there is none ).
		 *
		 * @return int
		 */
		public static function real_user_id() {
			return self::$real;
		}

		/**
		 * The previewed customer's ID ( 0 when there is none ).
		 *
		 * @return int
		 */
		public static function target_id() {
			return self::$target;
		}

		/**
		 * Sign a preview link.
		 *
		 * @param int $real    Shop manager.
		 * @param int $target  Customer.
		 * @param int $expires Unix time.
		 * @return string
		 */
		protected static function sign( $real, $target, $expires ) {
			return hash_hmac( 'sha256', 'acfw-view-as|' . (int) $real . '|' . (int) $target . '|' . (int) $expires, wp_salt( 'auth' ) );
		}

		/**
		 * Can this user be previewed? Customers, subscribers and other roles
		 * without editing rights; never staff, whose pages carry admin links.
		 *
		 * @param int $user_id User.
		 * @return bool
		 */
		public static function can_view( $user_id ) {
			$user = get_userdata( (int) $user_id );
			return $user && ! user_can( $user, 'edit_posts' ) && ! user_can( $user, 'manage_woocommerce' );
		}

		/**
		 * A preview link for the current shop manager.
		 *
		 * @param int    $target Customer.
		 * @param string $base   Page to open ( default My Account ).
		 * @return string '' when the customer cannot be previewed.
		 */
		public static function url( $target, $base = '' ) {
			$real = get_current_user_id();
			if ( ! $real || ! current_user_can( 'manage_woocommerce' ) || ! self::can_view( $target ) ) {
				return '';
			}
			$base    = '' !== $base ? $base : wc_get_page_permalink( 'myaccount' );
			$expires = time() + self::TTL;
			return add_query_arg(
				array(
					self::ARG_USER => (int) $target,
					self::ARG_EXP  => $expires,
					self::ARG_KEY  => self::sign( $real, $target, $expires ),
				),
				$base
			);
		}

		/**
		 * The preview args in this request, if any.
		 *
		 * @return array|null { user, exp, key }
		 */
		protected static function requested() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- the args carry their own signature, checked in determine().
			if ( empty( $_GET[ self::ARG_USER ] ) || empty( $_GET[ self::ARG_EXP ] ) || empty( $_GET[ self::ARG_KEY ] ) ) {
				return null;
			}
			return array(
				'user' => absint( wp_unslash( $_GET[ self::ARG_USER ] ) ),
				'exp'  => absint( wp_unslash( $_GET[ self::ARG_EXP ] ) ),
				'key'  => preg_replace( '/[^a-f0-9]/', '', strtolower( (string) wp_unslash( $_GET[ self::ARG_KEY ] ) ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- reduced to hex.
			);
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Swap the logged-in shop manager for the previewed customer, when the
		 * request carries a valid preview link.
		 *
		 * @param int|false $user_id User WordPress resolved from the cookies.
		 * @return int|false
		 */
		public static function determine( $user_id ) {
			if ( self::$target ) {
				return self::$target;
			}
			$req = self::requested();
			if ( ! $req || ! $user_id ) {
				return $user_id;
			}

			$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
			$uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$page   = ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() && ! ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
				&& false === strpos( $uri, '/wp-json/' ) && false === strpos( $uri, 'rest_route=' ) && false === strpos( $uri, 'wc-ajax=' );
			if ( ! $page ) {
				return $user_id;
			}
			if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
				// A preview never sends a form; refuse it ( see guard() ).
				self::$refused = true;
				return $user_id;
			}

			$now = time();
			if ( $req['exp'] < $now || $req['exp'] > $now + self::TTL + 60 ) {
				return $user_id;
			}
			if ( ! hash_equals( self::sign( $user_id, $req['user'], $req['exp'] ), $req['key'] ) ) {
				return $user_id;
			}
			if ( (int) $user_id === $req['user'] || ! user_can( (int) $user_id, 'manage_woocommerce' ) || ! self::can_view( $req['user'] ) ) {
				return $user_id;
			}

			self::$real   = (int) $user_id;
			self::$target = $req['user'];
			return self::$target;
		}

		/**
		 * The preview only looks: refuse what would change something, and set
		 * the rest of the request up as read-only.
		 */
		public function guard() {
			if ( self::$refused ) {
				wp_die(
					esc_html__( 'This is a preview as a customer, so nothing can be sent from it.', 'my-account-dashboard-builder' ),
					esc_html__( 'Preview', 'my-account-dashboard-builder' ),
					array(
						'response'  => 403,
						'back_link' => true,
					)
				);
			}
			if ( ! self::active() ) {
				return;
			}

			if ( self::acts() ) {
				wp_die(
					esc_html__( 'This is a preview as a customer: its buttons and forms are switched off, so nothing changes for them.', 'my-account-dashboard-builder' ),
					esc_html__( 'Preview', 'my-account-dashboard-builder' ),
					array(
						'response'  => 200,
						'back_link' => true,
					)
				);
			}

			// WooCommerce would mark the customer "active" on every page view.
			remove_action( 'wp', 'wc_current_user_is_active', 10 );
			add_filter( 'woocommerce_persistent_cart_enabled', '__return_false' );
			add_filter( 'show_admin_bar', '__return_false' );
			add_filter(
				'pre_option_acfw_ajax_navigation',
				static function () {
					return 'no';
				}
			);
			add_action( 'template_redirect', array( $this, 'account_only' ), 0 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 30 );
			add_action( 'wp_footer', array( $this, 'render_bar' ), 5 );
			add_filter(
				'body_class',
				static function ( $classes ) {
					$classes[] = 'acfw-viewing-as';
					return $classes;
				}
			);
		}

		/**
		 * Would this request do something: log out, cancel, pay, add to the
		 * cart, change a payment method, or anything with a nonce?
		 *
		 * @return bool
		 */
		protected static function acts() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only check of which args are present.
			foreach ( array( '_wpnonce', 'nonce', 'security', 'cancel_order', 'order_again', 'pay_for_order', 'add-to-cart', 'remove_item', 'undo_item', 'acfw_apply', 'action' ) as $arg ) {
				if ( isset( $_GET[ $arg ] ) ) {
					return true;
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
			$vars = function_exists( 'WC' ) && WC()->query ? WC()->query->get_query_vars() : array();
			foreach ( array( 'customer-logout', 'delete-payment-method', 'set-default-payment-method', 'add-payment-method' ) as $endpoint ) {
				$slug = isset( $vars[ $endpoint ] ) && '' !== $vars[ $endpoint ] ? $vars[ $endpoint ] : $endpoint;
				if ( false !== strpos( $path, '/' . trim( $slug, '/' ) . '/' ) || '/' . trim( $slug, '/' ) === substr( rtrim( $path, '/' ), -strlen( '/' . trim( $slug, '/' ) ) ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * The preview shows My Account only.
		 */
		public function account_only() {
			nocache_headers();
			if ( function_exists( 'is_account_page' ) && ! is_account_page() ) {
				wp_die(
					wp_kses_post(
						sprintf(
							/* translators: %s: link to the preview of My Account. */
							__( 'The preview as a customer shows their My Account pages only. %s', 'my-account-dashboard-builder' ),
							'<a href="' . esc_url( self::keep( wc_get_page_permalink( 'myaccount' ) ) ) . '">' . esc_html__( 'Back to My Account', 'my-account-dashboard-builder' ) . '</a>'
						)
					),
					esc_html__( 'Preview', 'my-account-dashboard-builder' ),
					array( 'response' => 200 )
				);
			}
		}

		/**
		 * A URL with this preview's args, so the next page is a preview too.
		 *
		 * @param string $url URL.
		 * @return string
		 */
		public static function keep( $url ) {
			$req = self::requested();
			if ( ! self::active() || ! $req ) {
				return $url;
			}
			return add_query_arg(
				array(
					self::ARG_USER => $req['user'],
					self::ARG_EXP  => $req['exp'],
					self::ARG_KEY  => $req['key'],
				),
				$url
			);
		}

		/**
		 * WooCommerce's session for the preview: in memory only.
		 *
		 * @param string $handler Session class.
		 * @return string
		 */
		public static function session_handler( $handler ) {
			if ( ! self::active() || ! class_exists( 'WC_Session_Handler' ) ) {
				return $handler;
			}
			require_once __DIR__ . '/class-acfw-view-as-session.php';
			return 'ACFW_View_As_Session';
		}

		/**
		 * The script that keeps the preview inside My Account and switches its
		 * buttons and forms off.
		 */
		public function enqueue() {
			list( $url, $ver ) = acfw_asset_src( 'js/view-as.js' );
			wp_enqueue_script( 'acfw-view-as', $url, array(), $ver, true );
			$req = self::requested();
			wp_localize_script(
				'acfw-view-as',
				'acfwViewAs',
				array(
					'base'    => wp_make_link_relative( wc_get_page_permalink( 'myaccount' ) ),
					'args'    => array(
						self::ARG_USER => (string) $req['user'],
						self::ARG_EXP  => (string) $req['exp'],
						self::ARG_KEY  => (string) $req['key'],
					),
					'blocked' => __( 'This is a preview: buttons and forms are switched off, so nothing changes for the customer.', 'my-account-dashboard-builder' ),
				)
			);
		}

		/**
		 * The preview bar: who the page is shown as, which menu they get, and
		 * what is hidden from them, with why.
		 */
		public function render_bar() {
			$user = get_userdata( self::$target );
			if ( ! $user ) {
				return;
			}
			$report  = self::hidden_report( $user );
			$profile = class_exists( 'ACFW_Profiles' ) ? ACFW_Profiles::applied() : '';
			$group   = '' !== $profile ? ACFW_Profiles::get( $profile ) : null;
			$count   = count( $report );
			?>
			<div class="acfw-view-as-bar" role="region" aria-label="<?php esc_attr_e( 'Preview as a customer', 'my-account-dashboard-builder' ); ?>">
				<div class="acfw-vab-head">
					<span class="acfw-vab-eye" aria-hidden="true"></span>
					<span class="acfw-vab-who">
						<?php
						printf(
							/* translators: 1: customer name, 2: their roles. */
							esc_html__( 'Viewing as %1$s (%2$s)', 'my-account-dashboard-builder' ),
							'<strong>' . esc_html( $user->display_name ) . '</strong>',
							esc_html( acfw_role_names( $user->roles ) )
						);
						?>
					</span>
					<span class="acfw-vab-menu">
						<?php
						echo esc_html(
							$group
								/* translators: %s: group menu name. */
								? sprintf( __( 'Menu: %s', 'my-account-dashboard-builder' ), $group['label'] ?? $profile )
								: __( 'Menu: main menu', 'my-account-dashboard-builder' )
						);
						?>
					</span>
					<?php if ( $count ) : ?>
						<details class="acfw-vab-hidden">
							<?php /* translators: %d: number of hidden items. */ ?>
							<summary><?php echo esc_html( sprintf( _n( '%d hidden from them', '%d hidden from them', $count, 'my-account-dashboard-builder' ), $count ) ); ?></summary>
							<ul>
								<?php foreach ( $report as $row ) : ?>
									<li>
										<span class="acfw-vab-kind"><?php echo esc_html( $row['kind'] ); ?></span>
										<strong><?php echo esc_html( $row['label'] ); ?></strong>
										<span class="acfw-vab-why"><?php echo esc_html( implode( ' · ', $row['why'] ) ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						</details>
					<?php else : ?>
						<span class="acfw-vab-none"><?php esc_html_e( 'Nothing on this page is hidden from them.', 'my-account-dashboard-builder' ); ?></span>
					<?php endif; ?>
					<span class="acfw-vab-note"><?php esc_html_e( 'Read-only: nothing changes for the customer.', 'my-account-dashboard-builder' ); ?></span>
				</div>
			</div>
			<?php
		}

		/**
		 * What the customer does not get, and why: menu items ( of the menu they
		 * get ) and the banners of the page being viewed.
		 *
		 * @param WP_User $user Customer.
		 * @return array[] array{ kind, label, why: string[] }
		 */
		public static function hidden_report( $user ) {
			$rows = array();
			$off  = class_exists( 'ACFW_Commerce' ) ? ACFW_Commerce::disabled_keys() : array();

			foreach ( acfw_flatten_items( ACFW()->items->get_items() ) as $key => $item ) {
				$why = array();
				if ( isset( $item['active'] ) && ! $item['active'] ) {
					$why[] = __( 'Switched off', 'my-account-dashboard-builder' );
				} elseif ( in_array( (string) $key, $off, true ) ) {
					$why[] = __( 'Its feature is switched off in Settings', 'my-account-dashboard-builder' );
				} else {
					foreach ( acfw_visibility_check( $item, $user ) as $rule => $data ) {
						$why[] = acfw_visibility_reason( $rule, $data );
					}
					if ( ! $why && ! apply_filters( 'acfw_item_is_visible', true, $item ) ) {
						$why[] = __( 'Hidden by a filter in the site’s code', 'my-account-dashboard-builder' );
					}
				}
				if ( $why ) {
					$rows[] = array(
						'kind'  => __( 'Menu', 'my-account-dashboard-builder' ),
						'label' => (string) ( $item['label'] ?? $key ),
						'why'   => $why,
					);
				}
			}

			// Banners set on the page being viewed.
			$current = acfw_get_current_endpoint();
			$flat    = acfw_flatten_items( ACFW()->items->get_items() );
			foreach ( isset( $flat[ $current ] ) ? acfw_item_banner_slugs( $flat[ $current ] ) : array() as $slug ) {
				$banner = ACFW_Banners::get( $slug );
				if ( ! $banner ) {
					continue;
				}
				$why = array();
				foreach ( acfw_visibility_check( ACFW_Banners::rules( $banner ), $user ) as $rule => $data ) {
					$why[] = acfw_visibility_reason( $rule, $data );
				}
				if ( ! $why && 'widget' === $banner['type'] && 'yes' === $banner['offer'] && class_exists( 'ACFW_Offers' ) && ! ACFW_Offers::available() ) {
					$why[] = __( 'Coupons are switched off in WooCommerce', 'my-account-dashboard-builder' );
				}
				if ( $why ) {
					$rows[] = array(
						'kind'  => __( 'Banner', 'my-account-dashboard-builder' ),
						'label' => wp_strip_all_tags( acfw_apply_smart_tags( (string) ( $banner['title'] ?? $slug ), $user, false ) ),
						'why'   => $why,
					);
				}
			}
			return $rows;
		}

		/**
		 * AJAX: find customers to preview ( name, email or username ), with a
		 * signed preview link for each; an empty search lists the newest.
		 */
		public function ajax_search() {
			check_ajax_referer( self::NONCE, 'nonce' );
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( array(), 403 );
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked above.
			$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
			$args = array(
				'number'  => 30,
				'orderby' => 'registered',
				'order'   => 'DESC',
				'fields'  => 'all',
			);
			if ( '' !== $term ) {
				$args['search']         = '*' . $term . '*';
				$args['search_columns'] = array( 'user_login', 'user_email', 'display_name', 'user_nicename' );
			}
			$results = array();
			foreach ( get_users( $args ) as $user ) {
				if ( count( $results ) >= 20 ) {
					break;
				}
				if ( ! self::can_view( $user->ID ) ) {
					continue;
				}
				$results[] = array(
					'id'    => (int) $user->ID,
					'text'  => $user->display_name . ' · ' . $user->user_email,
					'roles' => acfw_role_names( $user->roles ),
					'url'   => self::url( $user->ID ),
				);
			}
			wp_send_json_success( array( 'results' => $results ) );
		}
	}
}
