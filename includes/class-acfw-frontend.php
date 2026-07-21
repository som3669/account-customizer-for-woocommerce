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
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'wp', array( $this, 'setup' ), 20 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 15 );

			// Avatar block above the navigation.
			add_action( 'woocommerce_account_navigation', array( $this, 'render_avatar' ), 4 );
			// Replace the default WooCommerce navigation.
			add_action( 'woocommerce_account_navigation', array( $this, 'render_menu' ), 5 );
			// Inject per-endpoint custom content.
			add_action( 'woocommerce_account_content', array( $this, 'setup_endpoint_content' ), 1 );
			// Endpoint banners (top / bottom).
			add_action( 'woocommerce_account_content', array( $this, 'render_banner_top' ), 2 );
			add_action( 'woocommerce_account_content', array( $this, 'render_banner_bottom' ), 20 );
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

			// Remove the default WooCommerce navigation so ours takes over.
			$priority = has_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation' );
			if ( false !== $priority ) {
				remove_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation', $priority );
			}
		}

		/**
		 * Filter a set of items by active flag + role visibility.
		 *
		 * @param array $items Items to filter.
		 * @return array
		 */
		protected function filter_visible( $items ) {
			foreach ( $items as $key => $item ) {
				if ( ! $this->is_visible( $item ) ) {
					unset( $items[ $key ] );
					continue;
				}
				if ( ! empty( $item['children'] ) ) {
					$items[ $key ]['children'] = $this->filter_visible( $item['children'] );
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

			$visible = true;

			if ( isset( $item['visibility'] ) && 'roles' === $item['visibility'] && ! empty( $item['usr_roles'] ) ) {
				if ( current_user_can( 'administrator' ) ) {
					$visible = true;
				} else {
					$user    = wp_get_current_user();
					$roles   = (array) $user->roles;
					$visible = (bool) array_intersect( $item['usr_roles'], $roles );
				}
			}

			return apply_filters( 'acfw_item_is_visible', $visible, $item );
		}

		/**
		 * Enqueue frontend styles/scripts on the account page.
		 */
		public function enqueue_assets() {
			if ( ! $this->is_account ) {
				return;
			}

			wp_enqueue_style(
				'acfw-fontawesome',
				ACFW_ASSETS_URL . '/css/fontawesome/all.min.css',
				array(),
				ACFW_VERSION
			);
			wp_enqueue_style(
				'acfw-frontend',
				ACFW_ASSETS_URL . '/css/frontend.css',
				array( 'acfw-fontawesome' ),
				ACFW_VERSION
			);
			wp_add_inline_style( 'acfw-frontend', $this->dynamic_css() );

			wp_enqueue_script(
				'acfw-frontend',
				ACFW_ASSETS_URL . '/js/frontend.js',
				array( 'jquery' ),
				ACFW_VERSION,
				true
			);
			wp_localize_script(
				'acfw-frontend',
				'acfw',
				array(
					'ajaxNavigation' => 'yes' === get_option( 'acfw_ajax_navigation', 'no' ),
					'contentSelector' => apply_filters( 'acfw_content_selector', '.woocommerce-MyAccount-content' ),
				)
			);
		}

		/**
		 * Build inline CSS variables from the style options.
		 *
		 * The static stylesheet consumes these tokens, so all theming flows
		 * through a single source of truth.
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

			return sprintf(
				'.acfw-menu,.acfw-avatar-block{--acfw-accent:%1$s;--acfw-text:%2$s;--acfw-accent-tint:%3$s;--acfw-radius:%4$dpx;--acfw-gap:%5$dpx;--acfw-item-padding:%6$dpx;--acfw-avatar-size:%7$dpx;--acfw-font-size:%8$dpx;--acfw-font-weight:%9$s;}',
				$accent,
				$text,
				$tint,
				$radius,
				$gap,
				$padding,
				$avatar ? $avatar : 72,
				$fsize ? $fsize : 15,
				$fweight
			);
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

			// Custom avatar image overrides the gravatar when set.
			$custom = get_option( 'acfw_avatar_image', '' );
			$avatar = $custom
				? sprintf( '<img src="%s" alt="" width="%2$d" height="%2$d" />', esc_url( $custom ), $size )
				: get_avatar( $user->ID, $size );

			$role_label = '';
			if ( ! empty( $user->roles[0] ) ) {
				$roles      = wp_roles()->get_names();
				$role_label = isset( $roles[ $user->roles[0] ] ) ? translate_user_role( $roles[ $user->roles[0] ] ) : '';
			}

			acfw_get_template(
				'myaccount-avatar.php',
				array(
					'user'       => $user,
					'avatar'     => $avatar,
					'shape'      => get_option( 'acfw_avatar_shape', 'circle' ),
					'align'      => get_option( 'acfw_avatar_align', 'center' ),
					'show_name'  => 'yes' === get_option( 'acfw_avatar_show_name', 'yes' ),
					'show_role'  => 'yes' === get_option( 'acfw_avatar_show_role', 'no' ),
					'role_label' => $role_label,
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
			$item = $this->current_item();
			if ( empty( $item['banner_slug'] ) ) {
				return;
			}
			$item_pos = isset( $item['banner_position'] ) && 'bottom' === $item['banner_position'] ? 'bottom' : 'top';
			if ( $item_pos !== $position ) {
				return;
			}
			echo ACFW_Banners::render( $item['banner_slug'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer.
		}

		/**
		 * Render the custom account menu.
		 */
		public function render_menu() {

			$position = get_option( 'acfw_menu_position', 'vertical-left' );
			$layout   = get_option( 'acfw_menu_layout', 'simple' );

			acfw_get_template(
				'myaccount-menu.php',
				array(
					'items'      => $this->menu_items,
					'current'    => acfw_get_current_endpoint(),
					'position'   => $position,
					'layout'     => $layout,
					'theme'      => sanitize_html_class( get_template() ),
					'show_icons' => 'no' !== get_option( 'acfw_show_icons', 'yes' ),
					'group_open' => 'yes' === get_option( 'acfw_group_open', 'no' ),
					'frontend'   => $this,
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

			$item = $this->current_item();
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
			$content = wpautop( $content );
			$content = apply_filters( 'acfw_endpoint_content', $content, $item, $user );

			echo do_shortcode( $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered post-style content.
		}

		/**
		 * Render a single menu item (used by the template).
		 *
		 * @param string $key  Item key.
		 * @param array  $item Item options.
		 */
		public function render_item( $key, $item ) {

			$type    = isset( $item['type'] ) ? $item['type'] : 'endpoint';
			$current = acfw_get_current_endpoint();
			$classes = array( 'acfw-menu-item', 'acfw-type-' . $type );

			if ( ! empty( $item['class'] ) ) {
				$classes[] = sanitize_html_class( $item['class'] );
			}
			if ( $key === $current ) {
				$classes[] = 'is-active';
			}

			if ( 'link' === $type ) {
				$url    = esc_url( $item['url'] );
				$target = ! empty( $item['target_blank'] ) ? ' target="_blank" rel="noopener"' : '';
			} else {
				$base = wc_get_page_permalink( 'myaccount' );
				$url  = ( 'dashboard' === $key ) ? $base : wc_get_endpoint_url( $key, '', $base );
				$target = '';
			}

			acfw_get_template(
				'myaccount-menu-item.php',
				array(
					'key'     => $key,
					'item'    => $item,
					'url'     => $url,
					'target'  => $target,
					'classes' => implode( ' ', apply_filters( 'acfw_item_classes', $classes, $key, $item ) ),
				)
			);
		}
	}
}
