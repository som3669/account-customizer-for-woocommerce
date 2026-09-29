<?php
/**
 * Admin settings panel.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-acfw-admin-tab.php';
require_once __DIR__ . '/tabs/class-acfw-tab-items.php';
require_once __DIR__ . '/tabs/class-acfw-tab-design.php';
require_once __DIR__ . '/tabs/class-acfw-tab-settings.php';
require_once __DIR__ . '/tabs/class-acfw-tab-banners.php';
require_once __DIR__ . '/tabs/class-acfw-tab-tools.php';
require_once __DIR__ . '/tabs/class-acfw-tab-insights.php';
require_once __DIR__ . '/tabs/class-acfw-tab-fields.php';
require_once __DIR__ . '/tabs/class-acfw-tab-returns.php';

if ( ! class_exists( 'ACFW_Admin' ) ) {

	/**
	 * Registers the admin panel, settings and menu-items builder.
	 */
	class ACFW_Admin {

		const PAGE  = 'acfw-settings';
		const NONCE = 'acfw_admin_action';

		/**
		 * Registered tab handlers keyed by tab slug.
		 *
		 * @var ACFW_Admin_Tab[]
		 */
		private $tabs;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->tabs = array(
				'items'    => new ACFW_Tab_Items(),
				'design'   => new ACFW_Tab_Design(),
				'general'  => new ACFW_Tab_Settings(),
				'banners'  => new ACFW_Tab_Banners(),
				'tools'    => new ACFW_Tab_Tools(),
				'insights' => new ACFW_Tab_Insights(),
				'fields'   => new ACFW_Tab_Fields(),
				'returns'  => new ACFW_Tab_Returns(),
			);
			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_init', array( $this, 'handle_actions' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'media_buttons', array( $this, 'render_smart_tag_button' ), 20 );
			// Let registered blocks' editor scripts/styles load on our settings page
			// ( same pattern as WP_Customize_Widgets ).
			add_filter( 'should_load_block_editor_scripts_and_styles', array( $this, 'should_load_block_editor_scripts_and_styles' ) );
			add_filter(
				'plugin_action_links_' . plugin_basename( ACFW_FILE ),
				array( $this, 'action_links' )
			);
		}

		/**
		 * Register the submenu page under WooCommerce.
		 */
		public function register_menu() {

			$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><circle cx="19" cy="18" r="5" fill="#a7aaad"/><path d="M9 35c0-5.5 4.5-9.5 10-9.5s10 4 10 9.5" fill="#a7aaad"/><path d="M35 12v24" stroke="#a7aaad" stroke-width="2.4" stroke-linecap="round"/><circle cx="35" cy="21" r="4" fill="#a7aaad"/></svg>' ); // phpcs:ignore

			add_menu_page(
				__( 'My Account Dashboard Builder', 'my-account-dashboard-builder' ),
				__( 'My Account', 'my-account-dashboard-builder' ),
				'manage_woocommerce',
				self::PAGE,
				array( $this, 'render_page' ),
				$icon,
				56
			);

			$base = 'admin.php?page=' . self::PAGE;
			$subs = array(
				self::PAGE              => __( 'Menu Items', 'my-account-dashboard-builder' ),
				$base . '&tab=design'   => __( 'Design', 'my-account-dashboard-builder' ),
				$base . '&tab=general'  => __( 'Settings', 'my-account-dashboard-builder' ),
				$base . '&tab=banners'  => __( 'Banners', 'my-account-dashboard-builder' ),
				$base . '&tab=fields'   => __( 'Fields', 'my-account-dashboard-builder' ),
				$base . '&tab=returns'  => __( 'Returns', 'my-account-dashboard-builder' ),
				$base . '&tab=insights' => __( 'Insights', 'my-account-dashboard-builder' ),
			);
			foreach ( $subs as $slug => $title ) {
				add_submenu_page(
					self::PAGE,
					$title,
					$title,
					'manage_woocommerce',
					$slug,
					self::PAGE === $slug ? array( $this, 'render_page' ) : ''
				);
			}
		}

		/**
		 * Add a Settings link on the plugins screen.
		 *
		 * @param array $links Existing links.
		 * @return array
		 */
		public function action_links( $links ) {
			$url  = admin_url( 'admin.php?page=' . self::PAGE );
			$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'my-account-dashboard-builder' ) . '</a>';
			array_unshift( $links, $link );
			return $links;
		}

		/**
		 * Register general + style options via the Settings API.
		 */
		public function register_settings() {

			// Only the options the Settings tab form actually contains. Design
			// options ( colors, spacing, avatar, etc. ) are managed by the
			// Customizer — registering them here would let a Settings-tab save
			// blank them out via the Settings API.
			$settings = array(
				'acfw_ajax_navigation'  => 'sanitize_text_field',
				'acfw_default_endpoint' => 'sanitize_text_field',
				'acfw_login_redirect'   => 'sanitize_text_field',
				'acfw_logout_redirect'  => 'sanitize_text_field',
				'acfw_guest_message'    => 'wp_kses_post',
				'acfw_dashboard_notice' => 'wp_kses_post',
				'acfw_notice_style'     => 'sanitize_key',
				'acfw_notice_dismiss'   => 'sanitize_text_field',
				'acfw_track_views'      => 'sanitize_text_field',
				'acfw_buyagain_enable'  => 'sanitize_text_field',
				'acfw_recent_enable'    => 'sanitize_text_field',
				'acfw_tracking_enable'  => 'sanitize_text_field',
			);

			foreach ( $settings as $option => $sanitize ) {
				register_setting( 'acfw_settings', $option, array( 'sanitize_callback' => $sanitize ) );
			}

			// Settings → Orders & privacy. A group of its own: options.php resets
			// every option of the group that a form leaves out, so sharing one
			// group would have each form wipe the other's.
			$orders = array(
				'acfw_cancel_enable'      => 'sanitize_text_field',
				'acfw_cancel_hours'       => 'absint',
				'acfw_returns_enable'     => 'sanitize_text_field',
				'acfw_returns_days'       => 'absint',
				'acfw_returns_reasons'    => 'sanitize_textarea_field',
				'acfw_addressbook_enable' => 'sanitize_text_field',
				'acfw_privacy_enable'     => 'sanitize_text_field',
			);
			foreach ( $orders as $option => $sanitize ) {
				register_setting( 'acfw_settings_orders', $option, array( 'sanitize_callback' => $sanitize ) );
			}
		}

		/**
		 * Enqueue admin assets on our page only.
		 *
		 * @param string $hook Current admin page hook.
		 */
		public function enqueue_assets( $hook ) {
			if ( 'toplevel_page_' . self::PAGE !== $hook ) {
				return;
			}

			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style(
				'acfw-fontawesome',
				ACFW_ASSETS_URL . '/css/fontawesome/all.min.css',
				array(),
				ACFW_VERSION
			);
			list( $admin_css_url, $admin_css_ver ) = acfw_asset_src( 'css/admin.css' );
			wp_enqueue_style(
				'acfw-admin',
				$admin_css_url,
				array(),
				$admin_css_ver
			);

			// Rich content editor + media for the endpoint content field.
			wp_enqueue_editor();
			wp_enqueue_media();

			// Avoid conflicts with WooCommerce's selectWoo fork on our screen.
			wp_dequeue_script( 'selectWoo' );
			wp_dequeue_script( 'select2' );

			// Bundled select2 ( role chips + searchable icon picker with glyphs ).
			wp_enqueue_style(
				'acfw-select2',
				ACFW_ASSETS_URL . '/css/select2/select2.min.css',
				array(),
				ACFW_VERSION
			);
			wp_enqueue_script(
				'acfw-select2',
				ACFW_ASSETS_URL . '/js/select2/select2.min.js',
				array( 'jquery' ),
				ACFW_VERSION,
				true
			);
			$deps = array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker', 'editor', 'wp-a11y', 'acfw-select2' );

			list( $admin_js_url, $admin_js_ver ) = acfw_asset_src( 'js/admin.js' );
			wp_enqueue_script(
				'acfw-admin',
				$admin_js_url,
				$deps,
				$admin_js_ver,
				true
			);
			wp_localize_script(
				'acfw-admin',
				'acfwAdmin',
				array(
					'confirmDelete'  => __( 'Delete this item? This cannot be undone.', 'my-account-dashboard-builder' ),
					'confirmReset'   => __( 'Reset every menu item, design setting and banner to the defaults? This cannot be undone.', 'my-account-dashboard-builder' ),
					'mediaTitle'     => __( 'Select an icon image', 'my-account-dashboard-builder' ),
					'mediaButton'    => __( 'Use this image', 'my-account-dashboard-builder' ),
					'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
					'productNonce'   => wp_create_nonce( 'search-products' ),
					'productMinChar' => __( 'Type at least 3 characters to search.', 'my-account-dashboard-builder' ),
					'searching'      => __( 'Searching…', 'my-account-dashboard-builder' ),
					'noResults'      => __( 'No products found.', 'my-account-dashboard-builder' ),
					'everyone'       => __( 'Everyone', 'my-account-dashboard-builder' ),
					'canvas'         => $this->canvas_strings(),
				)
			);

			// The icon library, once for every picker on the page ( see icon_picker() ).
			$icons = array();
			foreach ( acfw_icon_choices() as $icon_class => $icon_label ) {
				$icons[] = array(
					'id'   => $icon_class,
					'text' => $icon_label,
				);
			}
			wp_add_inline_script( 'acfw-admin', 'window.acfwIconChoices = ' . wp_json_encode( $icons ) . ';', 'before' );

			// Menu Items: the canvas draws the menu with the storefront's own
			// stylesheet ( compiled scoped to .acfw-canvas-page ) and the design
			// saved in the Studio, so it looks the way customers will see it.
			if ( 'items' === $this->current_tab() ) {
				require_once ACFW_DIR . 'includes/frontend/class-acfw-frontend.php';
				list( $canvas_url, $canvas_ver ) = acfw_asset_src( 'css/canvas.css' );
				wp_enqueue_style( 'acfw-canvas', $canvas_url, array( 'acfw-admin', 'acfw-fontawesome', 'dashicons' ), $canvas_ver );
				wp_add_inline_style( 'acfw-canvas', ACFW_Frontend::design_css() );
			}

			// Design Studio: its own script, the media library and the CSS editor.
			if ( 'design' === $this->current_tab() ) {
				list( $studio_url, $studio_ver ) = acfw_asset_src( 'js/design-studio.js' );
				wp_enqueue_script( 'acfw-design-studio', $studio_url, array( 'jquery' ), $studio_ver, true );
				$css_editor = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
				wp_add_inline_script( 'acfw-design-studio', 'window.acfwStudioCodeEditor = ' . wp_json_encode( $css_editor ) . ';', 'before' );
				return;
			}

			// Standalone Block Editor for endpoint custom content ( optional ).
			$this->enqueue_block_editor();
		}

		/**
		 * Load registered block editor scripts/styles on our settings page.
		 *
		 * @param bool $is_block_editor_screen Current core decision.
		 * @return bool
		 */
		public function should_load_block_editor_scripts_and_styles( $is_block_editor_screen ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( is_admin() && isset( $_GET['page'] ) && self::PAGE === $_GET['page'] ) {
				return true;
			}
			return $is_block_editor_screen;
		}

		/**
		 * Enqueue the standalone Block Editor assets for endpoint custom content.
		 * Silently no-ops when the bundle has not been built ( Classic keeps working ).
		 */
		public function enqueue_block_editor() {
			$asset_file = ACFW_DIR . 'assets/build/index.asset.php';
			if ( ! file_exists( $asset_file ) ) {
				return;
			}
			$asset = require $asset_file;

			// Editor UI + core block styles.
			wp_enqueue_style( 'wp-block-editor' );
			wp_enqueue_style( 'wp-edit-blocks' );
			wp_enqueue_style( 'wp-editor' );
			wp_enqueue_style( 'wp-block-library' );
			wp_enqueue_style( 'wp-block-library-theme' );
			wp_enqueue_style( 'wp-components' );
			wp_enqueue_style( 'wp-format-library' );

			wp_enqueue_media();
			wp_enqueue_script( 'wp-media-utils' );

			// Classic block ( core/freeform ) needs TinyMCE + wp.oldEditor.
			if ( function_exists( 'wp_tinymce_inline_scripts' ) ) {
				wp_tinymce_inline_scripts();
			}
			if ( function_exists( 'wp_enqueue_editor' ) ) {
				wp_enqueue_editor();
			}
			wp_enqueue_script( 'wp-format-library' );

			// Same hook as the post/widgets editor so registered blocks load.
			do_action( 'enqueue_block_editor_assets' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- re-firing a WordPress core hook, same as the widgets editor.

			$dependencies = array_merge(
				(array) $asset['dependencies'],
				array( 'wp-format-library', 'wp-media-utils', 'media-editor', 'wp-api-fetch', 'editor', 'wp-tinymce' )
			);

			wp_enqueue_script(
				'acfw-block-editor',
				ACFW_ASSETS_URL . '/build/index.js',
				$dependencies,
				$asset['version'],
				true
			);

			wp_localize_script(
				'acfw-block-editor',
				'acfwBlockEditorData',
				array(
					'canUploadMedia'           => current_user_can( 'upload_files' ),
					'canUserUseUnfilteredHTML' => current_user_can( 'unfiltered_html' ),
				)
			);

			if ( class_exists( '\WP_Block_Editor_Context' ) && function_exists( 'get_block_editor_settings' ) ) {
				$editor_context  = new \WP_Block_Editor_Context( array( 'name' => 'core/edit-post' ) );
				$editor_settings = get_block_editor_settings( array(), $editor_context );

				$editor_settings['__experimentalBlockPatterns']          = acfw_get_block_editor_patterns();
				$editor_settings['__experimentalBlockPatternCategories'] = acfw_get_block_editor_pattern_categories();
				$editor_settings['__experimentalUserPatternCategories']  = acfw_get_block_editor_user_pattern_categories();

				unset( $editor_settings['__unstableResolvedAssets'] );

				wp_add_inline_script(
					'acfw-block-editor',
					'window.acfwBlockEditorSettings = ' . wp_json_encode( $editor_settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';',
					'before'
				);
			}

			$style_file = ACFW_DIR . 'assets/build/style-index.css';
			if ( file_exists( $style_file ) ) {
				wp_enqueue_style(
					'acfw-block-editor',
					ACFW_ASSETS_URL . '/build/style-index.css',
					array( 'wp-block-editor', 'wp-edit-blocks', 'wp-editor' ),
					$asset['version']
				);
			}
		}

		/**
		 * Cache-busting asset version: file mtime when readable, else plugin version.
		 *
		 * @param string $rel Path relative to the assets directory.
		 * @return string
		 */
		protected function asset_ver( $rel ) {
			$file = ACFW_DIR . 'assets/' . ltrim( $rel, '/' );
			return file_exists( $file ) ? (string) filemtime( $file ) : ACFW_VERSION;
		}

		/**
		 * Words the Menu Items canvas builds on the fly: the rule chips, where "+"
		 * adds an item, and what a keyboard move did.
		 *
		 * @return array
		 */
		protected function canvas_strings() {
			$format = function_exists( 'get_woocommerce_price_format' ) ? get_woocommerce_price_format() : '%1$s%2$s';
			$symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';

			return array(
				// The chip's singular and plural, from the entry the server's chip uses.
				/* translators: %d: minimum number of orders. */
				'order'       => _n( '%d+ order', '%d+ orders', 1, 'my-account-dashboard-builder' ),
				/* translators: %d: minimum number of orders. */
				'orders'      => _n( '%d+ order', '%d+ orders', 2, 'my-account-dashboard-builder' ),
				/* translators: %s: minimum amount spent, with its currency. */
				'spent'       => __( '%s+ spent', 'my-account-dashboard-builder' ),
				'noOrders'    => __( 'No orders yet', 'my-account-dashboard-builder' ),
				/* translators: %d: maximum number of orders. */
				'maxOrder'    => _n( 'At most %d order', 'At most %d orders', 1, 'my-account-dashboard-builder' ),
				/* translators: %d: maximum number of orders. */
				'maxOrders'   => _n( 'At most %d order', 'At most %d orders', 2, 'my-account-dashboard-builder' ),
				/* translators: %d: number of days. */
				'idleDay'     => _n( 'No order in %d day', 'No order in %d days', 1, 'my-account-dashboard-builder' ),
				/* translators: %d: number of days. */
				'idleDays'    => _n( 'No order in %d day', 'No order in %d days', 2, 'my-account-dashboard-builder' ),
				'product'     => __( 'Bought 1 product', 'my-account-dashboard-builder' ),
				/* translators: %d: number of products. */
				'products'    => __( 'Bought one of %d products', 'my-account-dashboard-builder' ),
				/* translators: 1: first day, 2: last day. */
				'range'       => __( '%1$s – %2$s', 'my-account-dashboard-builder' ),
				/* translators: %s: first day. */
				'from'        => __( 'From %s', 'my-account-dashboard-builder' ),
				/* translators: %s: last day. */
				'until'       => __( 'Until %s', 'my-account-dashboard-builder' ),
				// Dates read "j M", as date_i18n() prints them on the server.
				'months'      => array_values( $GLOBALS['wp_locale']->month_abbrev ),
				'price'       => html_entity_decode( $format, ENT_QUOTES, 'UTF-8' ),
				'decimals'    => function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2,
				'decimalSep'  => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '.',
				'thousandSep' => function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : ',',
				'currency'    => html_entity_decode( $symbol, ENT_QUOTES, 'UTF-8' ),
				/* translators: %s: menu item label. */
				'movedUp'     => __( '%s moved up.', 'my-account-dashboard-builder' ),
				/* translators: %s: menu item label. */
				'movedDown'   => __( '%s moved down.', 'my-account-dashboard-builder' ),
				/* translators: 1: menu item label, 2: group label. */
				'movedIn'     => __( '%1$s moved into %2$s.', 'my-account-dashboard-builder' ),
				/* translators: %s: menu item label. */
				'movedOut'    => __( '%s moved out of its group.', 'my-account-dashboard-builder' ),
				'cantMove'    => __( 'It cannot move that way.', 'my-account-dashboard-builder' ),
			);
		}

		/**
		 * Get the active tab.
		 *
		 * @return string
		 */
		protected function current_tab() {
			$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'items'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// Templates and the Customizer panel became the Design Studio; old links land there.
			if ( in_array( $tab, array( 'templates', 'customizer' ), true ) ) {
				$tab = 'design';
			}
			return in_array( $tab, array( 'general', 'items', 'design', 'banners', 'tools', 'insights', 'fields', 'returns' ), true ) ? $tab : 'items';
		}

		/**
		 * The Settings tab's inner section, when one is being shown.
		 *
		 * @return string Empty outside the Settings tab.
		 */
		protected function current_section() {
			if ( 'general' !== $this->current_tab() || ! isset( $_GET['section'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return '';
			}
			$section = sanitize_key( wp_unslash( $_GET['section'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return in_array( $section, array( 'general', 'presets', 'tools' ), true ) ? $section : '';
		}

		/**
		 * Transient that carries notices across the post-save redirect.
		 *
		 * @return string
		 */
		protected function notices_key() {
			return 'acfw_admin_notices_' . get_current_user_id();
		}

		/**
		 * Handle POST actions for the menu-items builder.
		 */
		public function handle_actions() {

			if ( empty( $_POST['acfw_action'] ) ) {
				return;
			}
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}
			check_admin_referer( self::NONCE );

			$action = sanitize_key( wp_unslash( $_POST['acfw_action'] ) );
			$items  = ACFW()->items;

			$args = array(
				'page'    => self::PAGE,
				'tab'     => $this->current_tab(),
				'updated' => 'true',
			);
			// Presets and Import live in sections of the Settings tab; land back there.
			if ( '' !== $this->current_section() ) {
				$args['section'] = $this->current_section();
			}

			$notices = array();
			foreach ( $this->tabs as $tab ) {
				$tab->handle( $action, $items );
				$args    = array_merge( $args, $tab->redirect_args() );
				$notices = array_merge( $notices, $tab->notices() );
			}

			if ( $notices ) {
				set_transient( $this->notices_key(), $notices, MINUTE_IN_SECONDS );
			}

			// add_query_arg() does not encode values; a key saved with "%xx"
			// octets by an earlier version must survive the round trip.
			if ( ! empty( $args['select'] ) ) {
				$args['select'] = rawurlencode( (string) $args['select'] );
			}

			$redirect = add_query_arg( $args, admin_url( 'admin.php' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		/**
		 * Render the panel with its top tabs.
		 */
		public function render_page() {

			$tab  = $this->current_tab();
			$tabs = array(
				'items'    => array( __( 'Menu Items', 'my-account-dashboard-builder' ), 'menu-alt' ),
				'design'   => array( __( 'Design', 'my-account-dashboard-builder' ), 'art' ),
				'banners'  => array( __( 'Banners', 'my-account-dashboard-builder' ), 'megaphone' ),
				'fields'   => array( __( 'Fields', 'my-account-dashboard-builder' ), 'forms' ),
				'returns'  => array( __( 'Returns', 'my-account-dashboard-builder' ), 'undo' ),
				'insights' => array( __( 'Insights', 'my-account-dashboard-builder' ), 'chart-bar' ),
				'general'  => array( __( 'Settings', 'my-account-dashboard-builder' ), 'admin-generic' ),
			);
			?>
			<div class="wrap acfw-wrap">
				<div class="acfw-header">
					<div class="acfw-header-left">
						<div class="acfw-header-brand">
							<img class="acfw-header-icon" src="<?php echo esc_url( ACFW_ASSETS_URL . '/images/icon.svg' ); ?>" alt="<?php esc_attr_e( 'My Account Dashboard Builder', 'my-account-dashboard-builder' ); ?>" width="42" height="42" />
						</div>
						<nav class="acfw-tabs">
							<?php
							foreach ( $tabs as $slug => $tab_def ) :
								list( $label, $icon ) = $tab_def;
								$tab_url              = admin_url( 'admin.php?page=' . self::PAGE . '&tab=' . $slug );
								?>
								<a href="<?php echo esc_url( $tab_url ); ?>"
									class="acfw-tab <?php echo $tab === $slug ? 'is-active' : ''; ?>"
									<?php echo $tab === $slug ? 'aria-current="page"' : ''; ?>>
									<span class="acfw-tab-icon dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
									<span class="acfw-tab-label"><?php echo esc_html( $label ); ?></span>
								</a>
							<?php endforeach; ?>
						</nav>
					</div>
					<div class="acfw-header-actions">
						<?php if ( 'banners' === $tab ) : ?>
							<button type="button" class="button acfw-header-btn acfw-add-banner-btn">
								<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add banner', 'my-account-dashboard-builder' ); ?>
							</button>
						<?php endif; ?>
						<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
							<div class="acfw-header-more">
								<button type="button" class="button acfw-header-btn acfw-icon-only acfw-more-toggle" aria-expanded="false" aria-haspopup="true" title="<?php esc_attr_e( 'More actions', 'my-account-dashboard-builder' ); ?>" aria-label="<?php esc_attr_e( 'More actions', 'my-account-dashboard-builder' ); ?>">
									<span class="dashicons dashicons-ellipsis"></span>
								</button>
								<div class="acfw-more-menu" hidden>
									<button type="button" class="acfw-more-item acfw-preview-btn" data-url="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
										<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
										<?php esc_html_e( 'Preview', 'my-account-dashboard-builder' ); ?>
									</button>
									<a class="acfw-more-item" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" target="_blank" rel="noopener">
										<span class="dashicons dashicons-external" aria-hidden="true"></span>
										<?php esc_html_e( 'View My Account', 'my-account-dashboard-builder' ); ?>
									</a>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<hr class="wp-header-end" />

				<?php
				$acfw_toasts = array();
				// Our own saves land with ?updated=1; the Settings form goes through
				// options.php, which comes back with ?settings-updated=true.
				if ( isset( $_GET['updated'] ) || isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$acfw_toasts[] = array(
						'type' => 'success',
						'msg'  => __( 'Changes saved.', 'my-account-dashboard-builder' ),
					);
				}
				$acfw_queued = get_transient( $this->notices_key() );
				if ( $acfw_queued ) {
					delete_transient( $this->notices_key() );
					foreach ( (array) $acfw_queued as $acfw_notice ) {
						if ( ! empty( $acfw_notice['msg'] ) ) {
							if ( 'error' === ( $acfw_notice['type'] ?? '' ) ) {
								// A save that failed must not also say "Changes saved."
								$acfw_toasts = array_values(
									array_filter(
										$acfw_toasts,
										function ( $t ) {
											return 'success' !== $t['type'];
										}
									)
								);
							}
							$acfw_toasts[] = array(
								'type' => in_array( $acfw_notice['type'] ?? '', array( 'success', 'warning', 'error' ), true ) ? $acfw_notice['type'] : 'warning',
								'msg'  => (string) $acfw_notice['msg'],
							);
						}
					}
				}
				if ( $acfw_toasts ) :
					?>
					<div class="acfw-toast-wrap" aria-live="polite">
						<?php foreach ( $acfw_toasts as $t ) : ?>
							<div class="acfw-toast acfw-toast-<?php echo esc_attr( $t['type'] ); ?>" role="<?php echo 'success' === $t['type'] ? 'status' : 'alert'; ?>">
								<span class="dashicons dashicons-<?php echo 'success' === $t['type'] ? 'yes-alt' : 'warning'; ?>"></span>
								<span class="acfw-toast-msg"><?php echo esc_html( $t['msg'] ); ?></span>
								<button type="button" class="acfw-toast-close" aria-label="<?php esc_attr_e( 'Dismiss', 'my-account-dashboard-builder' ); ?>">&times;</button>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php
				$active_tab = isset( $this->tabs[ $tab ] ) ? $this->tabs[ $tab ] : $this->tabs['items'];
				$active_tab->render();
				?>
			</div>
			<?php
		}

		/**
		 * Add an "Add smart tags" button beside the editor's Add Media button.
		 * Fires on the core `media_buttons` hook; only for our editors.
		 *
		 * @param string $editor_id Current editor ID.
		 */
		public function render_smart_tag_button( $editor_id ) {
			if ( 0 !== strpos( (string) $editor_id, 'acfw_content_' ) ) {
				return;
			}
			?>
			<span class="acfw-smarttag-wrap">
				<button type="button" class="button acfw-smarttag-btn" data-target="<?php echo esc_attr( $editor_id ); ?>">
					<span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Add smart tags', 'my-account-dashboard-builder' ); ?>
				</button>
				<div class="acfw-smarttag-menu" hidden>
					<?php foreach ( acfw_smart_tags() as $tag => $tag_label ) : ?>
						<button type="button" class="acfw-smarttag-item" data-tag="<?php echo esc_attr( $tag ); ?>"><?php echo esc_html( $tag_label ); ?> <code><?php echo esc_html( $tag ); ?></code></button>
					<?php endforeach; ?>
				</div>
			</span>
			<?php
		}
	}
}
