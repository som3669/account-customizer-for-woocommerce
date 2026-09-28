<?php
/**
 * Frontend handler: replaces the WooCommerce account navigation and manages
 * per-endpoint content, visibility and styling.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Frontend' ) ) {

	/**
	 * Frontend behaviours.
	 */
	class ACFW_Frontend {

		/**
		 * Resolved menu items (visibility-filtered).
		 *
		 * @var array
		 */
		protected $menu_items = array();

		/**
		 * Whether we are on the account page.
		 *
		 * @var bool
		 */
		protected $is_account = false;

		/**
		 * Whether the menu being rendered offers the pin-to-top star.
		 *
		 * @var bool
		 */
		protected $pinnable = false;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'wp', array( $this, 'setup' ), 20 );
			add_action( 'template_redirect', array( $this, 'maybe_redirect_to_default_endpoint' ), 20 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 15 );

			// Login / logout redirects + guest message.
			add_filter( 'woocommerce_login_redirect', array( $this, 'login_redirect' ), 20 );
			add_filter( 'woocommerce_logout_default_redirect_url', array( $this, 'logout_redirect' ), 20 );
			add_action( 'woocommerce_before_customer_login_form', array( $this, 'guest_message' ) );

			// Avatar block is rendered inside the nav ( see render_menu ), so it
			// stacks above the menu in the same column instead of beside it.
			// Dashboard custom title.
			// woocommerce_account_dashboard fires after the template's own greeting,
			// so hook the content wrapper instead to sit above everything.
			add_action( 'woocommerce_account_content', array( $this, 'render_dashboard_notice' ), 1 );
			add_action( 'woocommerce_account_dashboard', array( $this, 'render_dashboard_title' ), 1 );
			// Dashboard stat widgets ( orders, spent, downloads, pie chart… ).
			add_action( 'woocommerce_account_dashboard', array( $this, 'render_dashboard_stats' ), 3 );
			// Dashboard quick-link tiles.
			add_action( 'woocommerce_account_dashboard', array( $this, 'render_dashboard_tiles' ), 5 );
			// Profile completeness meter.
			add_action( 'woocommerce_account_dashboard', array( $this, 'render_profile_meter' ), 4 );
			// Replace the default WooCommerce navigation.
			add_action( 'woocommerce_account_navigation', array( $this, 'render_menu' ), 5 );
			// Inject per-endpoint custom content.
			add_action( 'woocommerce_account_content', array( $this, 'setup_endpoint_content' ), 1 );
			// Endpoint banners (top / bottom).
			add_action( 'woocommerce_account_content', array( $this, 'render_banner_top' ), 2 );
			add_action( 'woocommerce_account_content', array( $this, 'render_banner_bottom' ), 20 );

			// Dashboard column template ( left / center / right ) via body class.
			add_filter( 'body_class', array( $this, 'body_class' ) );
		}

		/**
		 * Add a body class for the dashboard column template on the dashboard endpoint.
		 *
		 * @param array $classes Body classes.
		 * @return array
		 */
		public function body_class( $classes ) {
			if ( $this->is_account && 'dashboard' === acfw_get_current_endpoint() ) {
				$tpl       = get_option( 'acfw_dashboard_align', 'left' );
				$tpl       = in_array( $tpl, array( 'left', 'center', 'right' ), true ) ? $tpl : 'left';
				$classes[] = 'acfw-dash-tpl-' . $tpl;
			}
			return $classes;
		}

		/**
		 * Detect the account page and resolve the visible menu items.
		 */
		public function setup() {

			$this->is_account = function_exists( 'is_account_page' ) && is_account_page();
			$this->is_account = apply_filters( 'acfw_is_account_page', $this->is_account );

			if ( ! $this->is_account ) {
				return;
			}

			$this->menu_items = $this->filter_visible( ACFW()->items->get_items() );

			// Track endpoint views.
			if ( is_user_logged_in() && 'yes' === get_option( 'acfw_track_views', 'no' ) ) {
				$ep           = acfw_get_current_endpoint();
				$views        = get_option( 'acfw_endpoint_views', array() );
				$views        = is_array( $views ) ? $views : array();
				$views[ $ep ] = ( isset( $views[ $ep ] ) ? (int) $views[ $ep ] : 0 ) + 1;
				update_option( 'acfw_endpoint_views', $views, false );
			}

			$this->register_endpoint_titles();

			// Remove the default WooCommerce navigation so ours takes over.
			$priority = has_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation' );
			if ( false !== $priority ) {
				remove_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation', $priority );
			}
		}

		/**
		 * Give endpoints that WooCommerce has no title for their menu label as
		 * the page title, instead of the generic "My account".
		 *
		 * Runs early ( priority 9 ) so a plugin that titles its own endpoint
		 * still has the last word.
		 */
		protected function register_endpoint_titles() {
			foreach ( acfw_flatten_items( $this->menu_items ) as $key => $item ) {
				if ( 'endpoint' !== ( $item['type'] ?? 'endpoint' ) || 'customer-logout' === $key || empty( $item['label'] ) ) {
					continue;
				}
				$label = (string) $item['label'];
				add_filter(
					'woocommerce_endpoint_' . $key . '_title',
					static function ( $title ) use ( $label ) {
						return '' === (string) $title ? $label : $title;
					},
					9
				);
			}
		}

		/**
		 * Send customers to the landing endpoint when they open My Account.
		 *
		 * Only the bare account URL on a plain GET redirects: any endpoint
		 * ( /dashboard/ included ), form post, query string or Customizer
		 * preview is left alone.
		 */
		public function maybe_redirect_to_default_endpoint() {

			if ( ! $this->is_account || ! is_user_logged_in() || is_customize_preview() || wp_doing_ajax() ) {
				return;
			}

			$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check that the URL carries no query string.
			if ( 'GET' !== $method || ! empty( $_GET ) || ! function_exists( 'WC' ) || '' !== WC()->query->get_current_endpoint() ) {
				return;
			}

			$target = acfw_default_endpoint();
			if ( 'dashboard' === $target ) {
				return;
			}

			$flat = acfw_flatten_items( $this->menu_items );
			if ( ! isset( $flat[ $target ] ) || 'endpoint' !== ( $flat[ $target ]['type'] ?? 'endpoint' ) ) {
				return;
			}

			wp_safe_redirect( wc_get_account_endpoint_url( $target ) );
			exit;
		}

		/**
		 * Filter a set of items by active flag + visibility rules.
		 *
		 * Built-in endpoints whose feature is switched off are dropped, and so
		 * is a group left with no visible children.
		 *
		 * @param array $items Items to filter.
		 * @return array
		 */
		protected function filter_visible( $items ) {
			$off = class_exists( 'ACFW_Commerce' ) ? ACFW_Commerce::disabled_keys() : array();

			foreach ( $items as $key => $item ) {
				if ( in_array( (string) $key, $off, true ) || ! $this->is_visible( $item ) ) {
					unset( $items[ $key ] );
					continue;
				}
				if ( 'group' === ( $item['type'] ?? '' ) ) {
					$children = ! empty( $item['children'] ) ? $this->filter_visible( $item['children'] ) : array();
					if ( empty( $children ) ) {
						unset( $items[ $key ] );
						continue;
					}
					$items[ $key ]['children'] = $children;
				}
			}
			return $items;
		}

		/**
		 * Is a single item visible to the current user?
		 *
		 * @param array $item Item options.
		 * @return bool
		 */
		protected function is_visible( $item ) {

			if ( isset( $item['active'] ) && ! $item['active'] ) {
				return false;
			}

			return (bool) apply_filters( 'acfw_item_is_visible', acfw_visibility_passes( $item ), $item );
		}

		/**
		 * Enqueue frontend styles/scripts on the account page.
		 */
		public function enqueue_assets() {
			if ( $this->is_account ) {
				$this->enqueue_frontend();
			}
		}

		/**
		 * Enqueue the frontend styles/scripts ( also used by the shortcode/block ).
		 */
		public function enqueue_frontend() {

			if ( wp_style_is( 'acfw-frontend', 'enqueued' ) ) {
				return;
			}

			wp_enqueue_style(
				'acfw-fontawesome',
				ACFW_ASSETS_URL . '/css/fontawesome/all.min.css',
				array(),
				ACFW_VERSION
			);
			list( $fe_css_url, $fe_css_ver ) = acfw_asset_src( 'css/frontend.css' );
			wp_enqueue_style(
				'acfw-frontend',
				$fe_css_url,
				array( 'acfw-fontawesome' ),
				$fe_css_ver
			);
			wp_add_inline_style( 'acfw-frontend', $this->dynamic_css() );

			$custom_css = get_option( 'acfw_custom_css', '' );
			if ( $custom_css ) {
				wp_add_inline_style( 'acfw-frontend', wp_strip_all_tags( $custom_css ) );
			}

			// Core block styles for endpoints rendered with the Block editor.
			// With separate-core-assets on, wp-block-library is only common.css,
			// so per-block sheets ( video/columns/buttons/… ) must be enqueued
			// or block content renders unstyled.
			$this->enqueue_block_styles();

			list( $fe_js_url, $fe_js_ver ) = acfw_asset_src( 'js/frontend.js' );
			wp_enqueue_script(
				'acfw-frontend',
				$fe_js_url,
				array( 'jquery' ),
				$fe_js_ver,
				true
			);
			wp_localize_script(
				'acfw-frontend',
				'acfw',
				array(
					'ajaxNavigation'    => 'yes' === get_option( 'acfw_ajax_navigation', 'no' ),
					'contentSelector'   => apply_filters( 'acfw_content_selector', '.woocommerce-MyAccount-content' ),
					'logoutConfirm'     => 'yes' === get_option( 'acfw_logout_confirm', 'no' ),
					'logoutMsg'         => __( 'Are you sure you want to log out?', 'my-account-dashboard-builder' ),
					'searchPlaceholder' => __( 'Search…', 'my-account-dashboard-builder' ),
					'searchEmpty'       => __( 'No matching menu items.', 'my-account-dashboard-builder' ),
					/* translators: %s: menu item label. */
					'pinLabel'          => __( 'Pin %s to the top', 'my-account-dashboard-builder' ),
					/* translators: %s: menu item label. */
					'unpinLabel'        => __( 'Unpin %s', 'my-account-dashboard-builder' ),
					'userId'            => get_current_user_id(),
					'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
					'avatarNonce'       => wp_create_nonce( ACFW_Avatar::NONCE ),
					'avatarRemoveMsg'   => __( 'Remove your profile picture?', 'my-account-dashboard-builder' ),
					'avatarErrorMsg'    => __( 'Upload failed. Please try again.', 'my-account-dashboard-builder' ),
				)
			);
		}

		/**
		 * Build inline CSS variables from the style options.
		 *
		 * The static stylesheet consumes these tokens, so all theming flows
		 * through a single source of truth. They are declared on every block
		 * the plugin renders, dashboard widgets included, so the accent colour
		 * and corner radius reach the stats, tiles, meter and banners too.
		 *
		 * @return string
		 */
		protected function dynamic_css() {
			$accent  = sanitize_hex_color( get_option( 'acfw_accent_color', '#2563eb' ) );
			$text    = sanitize_hex_color( get_option( 'acfw_text_color', '#383838' ) );
			$accent  = $accent ? $accent : '#2563eb';
			$text    = $text ? $text : '#383838';
			$radius  = absint( get_option( 'acfw_menu_radius', 8 ) );
			$gap     = absint( get_option( 'acfw_menu_gap', 4 ) );
			$padding = absint( get_option( 'acfw_item_padding', 11 ) );
			$avatar  = absint( get_option( 'acfw_avatar_size', 72 ) );
			$fsize   = absint( get_option( 'acfw_font_size', 15 ) );
			$fweight = preg_replace( '/[^0-9]/', '', (string) get_option( 'acfw_font_weight', '500' ) );
			$fweight = $fweight ? $fweight : '500';
			$tint    = $this->hex_to_rgba( $accent, 0.10 );

			$menu_bg  = sanitize_hex_color( get_option( 'acfw_menu_bg', '' ) );
			$hover_bg = sanitize_hex_color( get_option( 'acfw_hover_bg', '' ) );
			$active   = sanitize_hex_color( get_option( 'acfw_active_color', '' ) );
			$active   = $active ? $active : $accent;

			$scope = implode(
				',',
				array(
					'.acfw-menu',
					'.acfw-avatar-block',
					'.acfw-buyagain',
					'.acfw-recent',
					'.acfw-dashboard-title',
					'.acfw-dashboard-notice',
					'.acfw-dashboard-stats',
					'.acfw-tiles',
					'.acfw-profile-meter',
					'.acfw-commerce-widget',
					'.acfw-banner',
				)
			);

			$vars = sprintf(
				'%11$s{--acfw-accent:%1$s;--acfw-text:%2$s;--acfw-accent-tint:%3$s;--acfw-radius:%4$dpx;--acfw-gap:%5$dpx;--acfw-item-padding:%6$dpx;--acfw-avatar-size:%7$dpx;--acfw-font-size:%8$dpx;--acfw-font-weight:%9$s;--acfw-active:%10$s;',
				$accent,
				$text,
				$tint,
				$radius,
				$gap,
				$padding,
				$avatar ? $avatar : 72,
				$fsize ? $fsize : 15,
				$fweight,
				$active,
				$scope
			);
			if ( $menu_bg ) {
				$vars .= '--acfw-menu-bg:' . $menu_bg . ';';
			}
			if ( $hover_bg ) {
				$vars .= '--acfw-hover-bg:' . $hover_bg . ';';
			}

			$fonts = array(
				'system' => '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif',
				'serif'  => 'Georgia,"Times New Roman",serif',
				'mono'   => 'ui-monospace,SFMono-Regular,Menlo,Consolas,monospace',
			);
			$ff    = get_option( 'acfw_font_family', 'inherit' );
			if ( isset( $fonts[ $ff ] ) ) {
				$vars .= '--acfw-font-family:' . $fonts[ $ff ] . ';';
			}
			$vars .= '}';

			if ( isset( $fonts[ $ff ] ) ) {
				$vars .= '.acfw-menu{font-family:var(--acfw-font-family);}';
			}

			// A chosen text colour reaches the menu labels. It is set on the nav so
			// the items inherit it and their hover / active colours still win. The
			// default is left out: the menu then keeps the theme's text colour,
			// which stays readable on a dark theme. "Theme style" always inherits.
			if ( '#383838' !== strtolower( $text ) ) {
				$vars .= '.acfw-menu:not(.layout-theme){color:var(--acfw-text);}';
			}

			return $vars;
		}

		/**
		 * Convert a hex color to an rgba() string.
		 *
		 * @param string $hex   Hex color (#rrggbb).
		 * @param float  $alpha Alpha channel 0..1.
		 * @return string
		 */
		protected function hex_to_rgba( $hex, $alpha = 1 ) {
			$hex = ltrim( $hex, '#' );
			if ( 3 === strlen( $hex ) ) {
				$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
			}
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
			return "rgba({$r},{$g},{$b},{$alpha})";
		}

		/**
		 * Enqueue core block styles when any endpoint uses the Block editor.
		 */
		protected function enqueue_block_styles() {
			$has_block = false;
			// Endpoints inside a group count too.
			foreach ( acfw_flatten_items( $this->menu_items ) as $it ) {
				if ( 'block' === ( $it['editor_type'] ?? 'classic' ) && ! empty( $it['content'] ) ) {
					$has_block = true;
					break;
				}
			}
			if ( ! $has_block ) {
				return;
			}

			wp_enqueue_style( 'wp-block-library' );
			wp_enqueue_style( 'wp-block-library-theme' );

			foreach ( array(
				'wp-block-video',
				'wp-block-audio',
				'wp-block-embed',
				'wp-block-image',
				'wp-block-gallery',
				'wp-block-media-text',
				'wp-block-cover',
				'wp-block-file',
				'wp-block-columns',
				'wp-block-column',
				'wp-block-group',
				'wp-block-search',
				'wp-block-latest-posts',
				'wp-block-latest-comments',
				'wp-block-query',
				'wp-block-post-template',
				'wp-block-post-title',
				'wp-block-accordion',
				'wp-block-buttons',
				'wp-block-button',
				'wp-block-separator',
				'wp-block-spacer',
				'wp-block-list',
				'wp-block-code',
				'wp-block-preformatted',
				'wp-block-shortcode',
				'wp-block-social-links',
				'wp-block-navigation',
				'wp-block-paragraph',
				'wp-block-heading',
				'wp-block-table',
				'wp-block-quote',
				'wp-block-pullquote',
			) as $handle ) {
				if ( wp_style_is( $handle, 'registered' ) ) {
					wp_enqueue_style( $handle );
				}
			}
		}

		/**
		 * Render the admin-authored notice at the top of the account dashboard.
		 *
		 * Dismissal is remembered per browser and keyed on a hash of the text, so
		 * editing the notice shows it again to everyone who dismissed the old one.
		 */
		public function render_dashboard_notice() {

			if ( ! is_user_logged_in() || 'dashboard' !== acfw_get_current_endpoint() ) {
				return;
			}

			$notice = trim( (string) get_option( 'acfw_dashboard_notice', '' ) );
			if ( '' === $notice ) {
				return;
			}

			$notice = ACFW_I18n::translate( 'dashboard_notice', $notice );
			$notice = acfw_apply_smart_tags( $notice );
			$notice = trim( wp_kses_post( $notice ) );
			if ( '' === $notice ) {
				return;
			}

			$styles = array( 'info', 'success', 'warning', 'plain' );
			$style  = (string) get_option( 'acfw_notice_style', 'info' );
			$style  = in_array( $style, $styles, true ) ? $style : 'info';

			$dismissible = 'yes' === get_option( 'acfw_notice_dismiss', 'no' );
			$key         = substr( md5( $notice ), 0, 12 );

			printf(
				'<div class="acfw-dashboard-notice acfw-notice-%1$s%2$s"%3$s>%4$s%5$s</div>',
				esc_attr( $style ),
				$dismissible ? ' is-dismissible' : '',
				$dismissible ? ' data-acfw-notice="' . esc_attr( $key ) . '"' : '',
				wp_kses_post( wpautop( $notice ) ),
				$dismissible
					? '<button type="button" class="acfw-notice-close" aria-label="' . esc_attr__( 'Dismiss this notice', 'my-account-dashboard-builder' ) . '">&times;</button>'
					: ''
			);
		}

		/**
		 * Render a custom dashboard heading.
		 */
		public function render_dashboard_title() {
			$title = trim( (string) get_option( 'acfw_dashboard_title', '' ) );
			if ( '' === $title ) {
				return;
			}
			$title = ACFW_I18n::translate( 'dashboard_title', $title );
			$title = acfw_apply_smart_tags( $title );
			echo '<h2 class="acfw-dashboard-title">' . wp_kses_post( $title ) . '</h2>';
		}

		/**
		 * Render dashboard stat widgets ( orders, spent, downloads, points,
		 * latest order and an orders-by-status pie chart ).
		 *
		 * Counts come from ACFW_Order_Stats, cached per customer, and each card
		 * links to the endpoint it summarises when that endpoint is in the menu.
		 */
		public function render_dashboard_stats() {

			if ( 'yes' !== get_option( 'acfw_dashboard_stats', 'no' ) ) {
				return;
			}

			$user    = wp_get_current_user();
			$user_id = (int) $user->ID;
			if ( ! $user_id || ! class_exists( 'ACFW_Order_Stats' ) ) {
				return;
			}

			$align      = get_option( 'acfw_dashboard_align', 'left' );
			$stats      = ACFW_Order_Stats::get( $user_id );
			$by_status  = $stats['by_status'];
			$orders_url = $this->visible_endpoint_url( 'orders' );
			$cards      = array();

			if ( 'yes' === get_option( 'acfw_stat_orders', 'yes' ) ) {
				$cards[] = $this->stat_card( 'cart', __( 'Total orders', 'my-account-dashboard-builder' ), (int) $stats['total'], $orders_url );
			}
			if ( 'yes' === get_option( 'acfw_stat_pending', 'yes' ) ) {
				$pending = ( $by_status['pending'] ?? 0 ) + ( $by_status['processing'] ?? 0 ) + ( $by_status['on-hold'] ?? 0 );
				$cards[] = $this->stat_card( 'clock', __( 'Pending orders', 'my-account-dashboard-builder' ), $pending, $orders_url );
			}
			if ( 'yes' === get_option( 'acfw_stat_spent', 'yes' ) && function_exists( 'wc_get_customer_total_spent' ) ) {
				$cards[] = $this->stat_card( 'money', __( 'Total spent', 'my-account-dashboard-builder' ), wc_price( wc_get_customer_total_spent( $user_id ) ), $orders_url );
			}
			if ( 'yes' === get_option( 'acfw_stat_refunds', 'no' ) ) {
				$cards[] = $this->stat_card( 'undo', __( 'Refunds', 'my-account-dashboard-builder' ), $by_status['refunded'] ?? 0, $orders_url );
			}
			if ( 'yes' === get_option( 'acfw_stat_downloads', 'yes' ) && function_exists( 'wc_get_customer_available_downloads' ) ) {
				$cards[] = $this->stat_card( 'download', __( 'Downloads', 'my-account-dashboard-builder' ), (int) acfw_endpoint_count( 'downloads', $user_id ), $this->visible_endpoint_url( 'downloads' ) );
			}
			if ( 'yes' === get_option( 'acfw_stat_points', 'no' ) ) {
				$cards[] = $this->stat_card( 'star-filled', __( 'Reward points', 'my-account-dashboard-builder' ), acfw_points_balance( $user ) );
			}

			$html = '';
			if ( $cards ) {
				$html .= '<div class="acfw-stats">' . implode( '', $cards ) . '</div>';
			}

			// Latest order block.
			$latest = ( 'yes' === get_option( 'acfw_stat_latest', 'no' ) && ! empty( $stats['latest'] ) ) ? wc_get_order( $stats['latest'] ) : false;
			if ( $latest ) {
				$html .= sprintf(
					'<div class="acfw-stat-latest"><span class="acfw-stat-latest-label">%s</span> <a href="%s">#%s</a> — %s <span class="acfw-badge">%s</span></div>',
					esc_html__( 'Latest order', 'my-account-dashboard-builder' ),
					esc_url( $latest->get_view_order_url() ),
					esc_html( $latest->get_order_number() ),
					wp_kses_post( $latest->get_formatted_order_total() ),
					esc_html( wc_get_order_status_name( $latest->get_status() ) )
				);
			}

			// Orders-by-status pie ( donut ) chart.
			if ( 'yes' === get_option( 'acfw_stat_piechart', 'no' ) && array_sum( $by_status ) > 0 ) {
				$html .= $this->orders_pie_chart( $by_status );
			}

			if ( $html ) {
				printf( '<div class="acfw-dashboard-stats acfw-align-%s">%s</div>', esc_attr( $align ), $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
			}
		}

		/**
		 * Build one stat card.
		 *
		 * @param string $icon  Dashicon slug ( without prefix ).
		 * @param string $label Card label.
		 * @param mixed  $value Display value ( pre-escaped/price markup allowed ).
		 * @param string $url   Optional link target; the card becomes a link.
		 * @return string
		 */
		protected function stat_card( $icon, $label, $value, $url = '' ) {
			$inner = sprintf(
				'<span class="acfw-stat-icon dashicons dashicons-%s" aria-hidden="true"></span><span class="acfw-stat-value">%s</span><span class="acfw-stat-label">%s</span>',
				esc_attr( $icon ),
				wp_kses_post( (string) $value ),
				esc_html( $label )
			);

			if ( '' !== (string) $url ) {
				return sprintf( '<a class="acfw-stat acfw-stat-link" href="%s">%s</a>', esc_url( $url ), $inner );
			}

			return '<div class="acfw-stat">' . $inner . '</div>';
		}

		/**
		 * URL of an endpoint, only when it is in the menu this customer sees.
		 *
		 * @param string $key Endpoint key.
		 * @return string Empty when the endpoint is hidden or not an endpoint.
		 */
		protected function visible_endpoint_url( $key ) {
			$flat = acfw_flatten_items( $this->menu_items );
			if ( ! isset( $flat[ $key ] ) || 'endpoint' !== ( $flat[ $key ]['type'] ?? 'endpoint' ) ) {
				return '';
			}
			return 'dashboard' === $key ? acfw_dashboard_url() : wc_get_account_endpoint_url( $key );
		}

		/**
		 * Inline SVG donut chart of orders grouped by status.
		 *
		 * @param array $by_status status => count.
		 * @return string
		 */
		protected function orders_pie_chart( $by_status ) {
			$palette = array( '#2563eb', '#16a34a', '#f59e0b', '#ef4444', '#8b5cf6', '#0ea5e9', '#64748b' );
			$total   = array_sum( $by_status );
			$radius  = 60;
			$circ    = 2 * M_PI * $radius;
			$offset  = 0;
			$segs    = '';
			$legend  = '';
			$i       = 0;

			foreach ( $by_status as $status => $count ) {
				$frac    = $count / $total;
				$color   = $palette[ $i % count( $palette ) ];
				$dash    = $frac * $circ;
				$segs   .= sprintf(
					'<circle r="%1$d" cx="80" cy="80" fill="transparent" stroke="%2$s" stroke-width="28" stroke-dasharray="%3$F %4$F" stroke-dashoffset="%5$F"></circle>',
					$radius,
					esc_attr( $color ),
					$dash,
					$circ - $dash,
					- $offset
				);
				$offset += $dash;
				$legend .= sprintf(
					'<li><span class="acfw-pie-dot" style="background:%s"></span>%s <strong>%d</strong></li>',
					esc_attr( $color ),
					esc_html( wc_get_order_status_name( $status ) ),
					(int) $count
				);
				++$i;
			}

			return sprintf(
				'<div class="acfw-pie"><svg viewBox="0 0 160 160" class="acfw-pie-svg" role="img" aria-label="%s"><g transform="rotate(-90 80 80)">%s</g><text x="80" y="86" text-anchor="middle" class="acfw-pie-total">%d</text></svg><ul class="acfw-pie-legend">%s</ul></div>',
				esc_attr__( 'Orders by status', 'my-account-dashboard-builder' ),
				$segs,
				(int) $total,
				$legend
			);
		}

		/**
		 * Render quick-link tiles on the dashboard endpoint.
		 */
		public function render_dashboard_tiles() {

			if ( 'yes' !== get_option( 'acfw_dashboard_tiles', 'no' ) ) {
				return;
			}

			$tiles = '';
			// Endpoints inside a group get a tile too.
			foreach ( acfw_flatten_items( $this->menu_items ) as $key => $item ) {
				if ( 'endpoint' !== ( $item['type'] ?? 'endpoint' ) || in_array( $key, array( 'dashboard', 'customer-logout' ), true ) ) {
					continue;
				}
				$badge  = $this->item_badge( $item );
				$count  = '' === $badge ? acfw_endpoint_count( $key ) : null;
				$pill   = '' !== $badge ? $badge : ( null !== $count ? (string) $count : '' );
				$icon   = acfw_icon_markup( $item['icon'] ?? '', $item['icon_url'] ?? '', 'acfw-tile-icon' );
				$tiles .= sprintf(
					'<a class="acfw-tile" href="%s">%s<span class="acfw-tile-label">%s</span>%s</a>',
					esc_url( wc_get_account_endpoint_url( $key ) ),
					$icon,
					esc_html( $item['label'] ),
					'' !== $pill ? '<span class="acfw-tile-count">' . esc_html( $pill ) . '</span>' : ''
				);
			}

			if ( $tiles ) {
				echo '<div class="acfw-tiles">' . $tiles . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
			}
		}

		/**
		 * Render the customer avatar block above the menu.
		 */
		public function render_avatar() {

			if ( 'yes' !== get_option( 'acfw_avatar_enable', 'no' ) ) {
				return;
			}

			$user = wp_get_current_user();
			if ( empty( $user->ID ) ) {
				return;
			}

			$size = absint( get_option( 'acfw_avatar_size', 72 ) );
			$size = $size ? $size : 72;

			// Precedence: customer upload → admin default image → gravatar.
			$uploaded   = class_exists( 'ACFW_Avatar' ) ? ACFW_Avatar::url( $user->ID, $size > 150 ? 'medium' : 'thumbnail' ) : '';
			$custom     = get_option( 'acfw_avatar_image', '' );
			$can_upload = class_exists( 'ACFW_Avatar' ) && ACFW_Avatar::enabled();

			if ( '' !== $uploaded ) {
				$avatar = sprintf( '<img src="%s" alt="" width="%2$d" height="%2$d" />', esc_url( $uploaded ), $size );
			} elseif ( $custom ) {
				$avatar = sprintf( '<img src="%s" alt="" width="%2$d" height="%2$d" />', esc_url( $custom ), $size );
			} else {
				$avatar = get_avatar( $user->ID, $size );
			}

			$role_label = '';
			if ( ! empty( $user->roles[0] ) ) {
				$roles      = wp_roles()->get_names();
				$role_label = isset( $roles[ $user->roles[0] ] ) ? translate_user_role( $roles[ $user->roles[0] ] ) : '';
			}

			acfw_get_template(
				'myaccount-avatar.php',
				array(
					'user'         => $user,
					'avatar'       => $avatar,
					'shape'        => get_option( 'acfw_avatar_shape', 'circle' ),
					'align'        => get_option( 'acfw_avatar_align', 'center' ),
					'show_name'    => 'yes' === get_option( 'acfw_avatar_show_name', 'yes' ),
					'show_role'    => 'yes' === get_option( 'acfw_avatar_show_role', 'no' ),
					'role_label'   => $role_label,
					'can_upload'   => $can_upload,
					'has_uploaded' => '' !== $uploaded,
				)
			);
		}

		/**
		 * Render the current endpoint's banner when positioned at the top.
		 */
		public function render_banner_top() {
			$this->render_banner( 'top' );
		}

		/**
		 * Render the current endpoint's banner when positioned at the bottom.
		 */
		public function render_banner_bottom() {
			$this->render_banner( 'bottom' );
		}

		/**
		 * Render the current endpoint's assigned banner at a position.
		 *
		 * @param string $position top|bottom.
		 */
		protected function render_banner( $position ) {

			$item  = $this->current_item();
			$slugs = acfw_item_banner_slugs( $item );

			if ( empty( $slugs ) ) {
				return;
			}

			$item_pos = isset( $item['banner_position'] ) && 'bottom' === $item['banner_position'] ? 'bottom' : 'top';
			if ( $item_pos !== $position ) {
				return;
			}

			foreach ( $slugs as $slug ) {
				echo ACFW_Banners::render( $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer.
			}
		}

		/**
		 * Redirect after login to the chosen endpoint.
		 *
		 * @param string $redirect Default redirect URL.
		 * @return string
		 */
		public function login_redirect( $redirect ) {
			$ep = get_option( 'acfw_login_redirect', '' );
			if ( $ep && function_exists( 'wc_get_account_endpoint_url' ) ) {
				return ( 'dashboard' === $ep ) ? acfw_dashboard_url() : wc_get_account_endpoint_url( $ep );
			}
			return $redirect;
		}

		/**
		 * Redirect after logout.
		 *
		 * @param string $url Default logout redirect URL.
		 * @return string
		 */
		public function logout_redirect( $url ) {
			$o = get_option( 'acfw_logout_redirect', 'default' );
			if ( 'home' === $o ) {
				return home_url( '/' );
			}
			if ( 'login' === $o && function_exists( 'wc_get_page_permalink' ) ) {
				return wc_get_page_permalink( 'myaccount' );
			}
			return $url;
		}

		/**
		 * Show a message above the login form for logged-out visitors.
		 */
		public function guest_message() {
			$msg = get_option( 'acfw_guest_message', '' );
			if ( $msg ) {
				$msg = ACFW_I18n::translate( 'guest_message', $msg );
				echo '<div class="acfw-guest-message">' . wp_kses_post( wpautop( $msg ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		/**
		 * The profile fields the completeness meter checks.
		 *
		 * @param WP_User $user User.
		 * @return array[] key => array{ label, done, url }
		 */
		protected function profile_fields( $user ) {
			$uid     = (int) $user->ID;
			$account = wc_get_account_endpoint_url( 'edit-account' );
			$billing = wc_get_endpoint_url( 'edit-address', 'billing', wc_get_page_permalink( 'myaccount' ) );

			$fields = array(
				'first_name'        => array(
					'label' => __( 'First name', 'my-account-dashboard-builder' ),
					'done'  => ! empty( $user->first_name ),
					'url'   => $account,
				),
				'last_name'         => array(
					'label' => __( 'Last name', 'my-account-dashboard-builder' ),
					'done'  => ! empty( $user->last_name ),
					'url'   => $account,
				),
				'user_email'        => array(
					'label' => __( 'Email address', 'my-account-dashboard-builder' ),
					'done'  => ! empty( $user->user_email ),
					'url'   => $account,
				),
				'billing_phone'     => array(
					'label' => __( 'Phone number', 'my-account-dashboard-builder' ),
					'done'  => '' !== (string) get_user_meta( $uid, 'billing_phone', true ),
					'url'   => $billing,
				),
				'billing_address_1' => array(
					'label' => __( 'Billing address', 'my-account-dashboard-builder' ),
					'done'  => '' !== (string) get_user_meta( $uid, 'billing_address_1', true ),
					'url'   => $billing,
				),
			);

			/**
			 * Filter the fields the profile completeness meter checks.
			 *
			 * @param array[] $fields key => array{ label: string, done: bool, url: string }.
			 * @param WP_User $user   Customer.
			 */
			$fields = apply_filters( 'acfw_profile_meter_fields', $fields, $user );

			return is_array( $fields ) ? $fields : array();
		}

		/**
		 * Render a profile-completeness meter on the dashboard.
		 *
		 * Below the bar it lists what is still missing, each linked to the
		 * form where the customer can fill it in.
		 */
		public function render_profile_meter() {
			if ( 'yes' !== get_option( 'acfw_profile_meter', 'no' ) ) {
				return;
			}
			$user = wp_get_current_user();
			if ( empty( $user->ID ) ) {
				return;
			}

			$fields  = $this->profile_fields( $user );
			$total   = count( $fields );
			$missing = array_filter(
				$fields,
				static function ( $field ) {
					return empty( $field['done'] );
				}
			);
			$pct     = $total ? (int) round( ( $total - count( $missing ) ) / $total * 100 ) : 0;
			?>
			<div class="acfw-profile-meter">
				<div class="acfw-pm-head">
					<strong id="acfw-pm-label"><?php esc_html_e( 'Profile completeness', 'my-account-dashboard-builder' ); ?></strong>
					<span><?php echo esc_html( $pct ); ?>%</span>
				</div>
				<div class="acfw-pm-bar" role="progressbar" aria-labelledby="acfw-pm-label" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $pct ); ?>"><span style="width:<?php echo esc_attr( $pct ); ?>%"></span></div>
				<?php if ( $missing ) : ?>
					<p class="acfw-pm-missing">
						<?php esc_html_e( 'Still missing:', 'my-account-dashboard-builder' ); ?>
						<?php
						$links = array();
						foreach ( $missing as $field ) {
							$label   = isset( $field['label'] ) ? (string) $field['label'] : '';
							$links[] = ! empty( $field['url'] )
								? '<a href="' . esc_url( $field['url'] ) . '">' . esc_html( $label ) . '</a>'
								: esc_html( $label );
						}
						echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
						?>
					</p>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Return the account menu markup ( for the shortcode / block ).
		 *
		 * @return string
		 */
		public function menu_markup() {
			if ( ! is_user_logged_in() ) {
				return '';
			}
			if ( empty( $this->menu_items ) ) {
				$this->menu_items = $this->filter_visible( ACFW()->items->get_items() );
			}
			$this->enqueue_frontend();
			ob_start();
			$this->render_menu();
			return ob_get_clean();
		}

		/**
		 * Render the custom account menu.
		 */
		public function render_menu() {

			$position                = get_option( 'acfw_menu_position', 'vertical-left' );
			list( $layout, $preset ) = acfw_menu_style_resolve( get_option( 'acfw_menu_style', 'simple' ) );
			$this->pinnable          = 'yes' === get_option( 'acfw_pin_enable', 'no' );

			// A group holding the current page opens, so the active item shows.
			$items = $this->menu_items;
			foreach ( $items as $key => $item ) {
				if ( 'group' !== ( $item['type'] ?? '' ) || empty( $item['children'] ) ) {
					continue;
				}
				foreach ( array_keys( $item['children'] ) as $child_key ) {
					if ( acfw_is_current_item( (string) $child_key ) ) {
						$items[ $key ]['has_current'] = true;
						break;
					}
				}
			}

			ob_start();
			$this->render_avatar();
			$avatar_html = ob_get_clean();

			acfw_get_template(
				'myaccount-menu.php',
				array(
					'avatar_html' => $avatar_html,
					'items'       => $items,
					'current'     => acfw_get_current_endpoint(),
					'position'    => $position,
					'layout'      => $layout,
					'preset'      => $preset,
					'theme'       => sanitize_html_class( get_template() ),
					'show_icons'  => 'no' !== get_option( 'acfw_show_icons', 'yes' ),
					'group_open'  => 'yes' === get_option( 'acfw_group_open', 'no' ),
					'search'      => 'yes' === get_option( 'acfw_menu_search', 'no' ),
					'sticky'      => 'yes' === get_option( 'acfw_sticky_menu', 'no' ),
					'indicator'   => get_option( 'acfw_active_indicator', 'bar' ),
					'anim'        => get_option( 'acfw_hover_anim', 'none' ),
					'scheme'      => get_option( 'acfw_color_scheme', 'light' ),
					'collapsible' => 'yes' === get_option( 'acfw_collapsible', 'no' ),
					'pinnable'    => $this->pinnable,
					'frontend'    => $this,
				)
			);
		}

		/**
		 * Resolve the current endpoint's options.
		 *
		 * @return array
		 */
		protected function current_item() {
			$current = acfw_get_current_endpoint();
			foreach ( $this->menu_items as $key => $item ) {
				if ( $key === $current ) {
					return $item;
				}
				if ( ! empty( $item['children'][ $current ] ) ) {
					return $item['children'][ $current ];
				}
			}
			return array();
		}

		/**
		 * Wire up custom content for the active endpoint.
		 */
		public function setup_endpoint_content() {

			$key  = acfw_get_current_endpoint();
			$item = $this->current_item();

			// A custom endpoint has no WooCommerce handler, and when
			// woocommerce_account_content() finds none it prints the dashboard,
			// greeting and widgets included, under the endpoint's own content.
			// An empty handler makes the endpoint show only what it was given.
			if ( $item && 'dashboard' !== $key && 'endpoint' === ( $item['type'] ?? 'endpoint' ) && ! has_action( 'woocommerce_account_' . $key . '_endpoint' ) ) {
				add_action( 'woocommerce_account_' . $key . '_endpoint', '__return_null' );
			}

			if ( empty( $item['content'] ) ) {
				return;
			}

			$position = isset( $item['content_position'] ) ? $item['content_position'] : 'before';

			switch ( $position ) {
				case 'after':
					add_action( 'woocommerce_account_content', array( $this, 'render_endpoint_content' ), 15 );
					break;
				case 'override':
					remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
					add_action( 'woocommerce_account_content', array( $this, 'render_endpoint_content' ), 10 );
					break;
				case 'before':
				default:
					add_action( 'woocommerce_account_content', array( $this, 'render_endpoint_content' ), 5 );
					break;
			}
		}

		/**
		 * Output the active endpoint's custom content.
		 */
		public function render_endpoint_content() {
			$item = $this->current_item();
			if ( empty( $item['content'] ) ) {
				return;
			}

			$user    = wp_get_current_user();
			$content = acfw_apply_smart_tags( $item['content'], $user );

			if ( 'block' === ( $item['editor_type'] ?? 'classic' ) ) {
				// Serialized Gutenberg block markup → rendered HTML.
				$content = do_blocks( $content );
				global $wp_embed;
				if ( isset( $wp_embed ) && is_object( $wp_embed ) ) {
					$content = $wp_embed->autoembed( $content );
				}
				if ( function_exists( 'wp_filter_content_tags' ) ) {
					$content = wp_filter_content_tags( $content );
				}
				$content = do_shortcode( $content );
			} else {
				$content = do_shortcode( wpautop( $content ) );
			}

			$content = apply_filters( 'acfw_endpoint_content', $content, $item, $user );

			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered post-style content.
		}

		/**
		 * The custom badge text of an item, smart tags resolved.
		 *
		 * @param array $item Item options.
		 * @return string Plain text; empty when the item has no badge.
		 */
		protected function item_badge( $item ) {
			if ( empty( $item['badge'] ) ) {
				return '';
			}
			return trim( wp_strip_all_tags( acfw_apply_smart_tags( (string) $item['badge'], null, false ) ) );
		}

		/**
		 * Render a single menu item (used by the template).
		 *
		 * @param string $key   Item key.
		 * @param array  $item  Item options.
		 * @param int    $depth 0 for a top-level item, 1 inside a group.
		 */
		public function render_item( $key, $item, $depth = 0 ) {

			$type       = isset( $item['type'] ) ? $item['type'] : 'endpoint';
			$classes    = array( 'acfw-menu-item', 'acfw-type-' . $type );
			$is_current = 'endpoint' === $type && acfw_is_current_item( (string) $key );

			// Keep WooCommerce's own item classes on every row. Core templates
			// emit woocommerce-MyAccount-navigation-link--{endpoint}, and themes,
			// third-party plugins and WooCommerce's E2E suite select on them, so
			// replacing the menu must not drop them.
			$classes[] = 'woocommerce-MyAccount-navigation-link';
			$classes[] = 'woocommerce-MyAccount-navigation-link--' . sanitize_html_class( $key );

			if ( ! empty( $item['class'] ) ) {
				$classes[] = sanitize_html_class( $item['class'] );
			}
			if ( $is_current ) {
				$classes[] = 'is-active';
				$classes[] = 'woocommerce-MyAccount-navigation-link--active';
			}

			if ( 'link' === $type ) {
				$url    = esc_url( $item['url'] );
				$target = ! empty( $item['target_blank'] ) ? ' target="_blank" rel="noopener"' : '';
			} elseif ( 'page' === $type ) {
				$url    = ! empty( $item['page_id'] ) ? esc_url( get_permalink( (int) $item['page_id'] ) ) : '#';
				$target = ! empty( $item['target_blank'] ) ? ' target="_blank" rel="noopener"' : '';
			} else {
				// wc_get_account_endpoint_url() adds the logout nonce, so Log out
				// signs the customer out instead of stopping at WooCommerce's
				// "Are you sure you want to log out?" page.
				$url    = ( 'dashboard' === $key ) ? acfw_dashboard_url() : wc_get_account_endpoint_url( $key );
				$target = '';
			}

			$badge = $this->item_badge( $item );
			$count = ( '' === $badge && 'no' !== get_option( 'acfw_show_counts', 'yes' ) ) ? acfw_endpoint_count( $key ) : null;

			// Fall back to a type-based default icon ( group = folder, page = file ).
			if ( empty( $item['icon'] ) && empty( $item['icon_url'] ) ) {
				$item['icon'] = acfw_default_type_icon( $type );
			}

			acfw_get_template(
				'myaccount-menu-item.php',
				array(
					'key'        => $key,
					'item'       => $item,
					'url'        => $url,
					'target'     => $target,
					'count'      => $count,
					'badge'      => $badge,
					'is_current' => $is_current,
					// Pinning reorders the top level only, so group children get no star.
					'pinnable'   => $this->pinnable && 0 === (int) $depth,
					'classes'    => implode( ' ', apply_filters( 'acfw_item_classes', $classes, $key, $item ) ),
				)
			);
		}
	}
}
