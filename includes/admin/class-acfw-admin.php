<?php
/**
 * Admin settings panel.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-acfw-admin-tab.php';
require_once __DIR__ . '/tabs/class-acfw-tab-items.php';
require_once __DIR__ . '/tabs/class-acfw-tab-templates.php';
require_once __DIR__ . '/tabs/class-acfw-tab-settings.php';
require_once __DIR__ . '/tabs/class-acfw-tab-banners.php';
require_once __DIR__ . '/tabs/class-acfw-tab-tools.php';

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
				'items'     => new ACFW_Tab_Items(),
				'templates' => new ACFW_Tab_Templates(),
				'general'   => new ACFW_Tab_Settings(),
				'banners'   => new ACFW_Tab_Banners(),
				'tools'     => new ACFW_Tab_Tools(),
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
				__( 'My Account Customizer', 'my-account-customizer' ),
				__( 'My Account', 'my-account-customizer' ),
				'manage_woocommerce',
				self::PAGE,
				array( $this, 'render_page' ),
				$icon,
				56
			);

			$base = 'admin.php?page=' . self::PAGE;
			$subs = array(
				self::PAGE               => __( 'Menu Items', 'my-account-customizer' ),
				$base . '&tab=templates' => __( 'Templates', 'my-account-customizer' ),
				$base . '&tab=general'   => __( 'Settings', 'my-account-customizer' ),
				ACFW_Customizer::url()   => __( 'Customizer', 'my-account-customizer' ),
				$base . '&tab=banners'   => __( 'Banners', 'my-account-customizer' ),
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
			$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'my-account-customizer' ) . '</a>';
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
			$deps = array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker', 'editor', 'acfw-select2' );

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
					'confirmDelete' => __( 'Delete this item? This cannot be undone.', 'my-account-customizer' ),
					'mediaTitle'    => __( 'Select an icon image', 'my-account-customizer' ),
					'mediaButton'   => __( 'Use this image', 'my-account-customizer' ),
				)
			);

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
		 * Get the active tab.
		 *
		 * @return string
		 */
		protected function current_tab() {
			$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'items'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return in_array( $tab, array( 'general', 'items', 'banners', 'tools', 'templates' ), true ) ? $tab : 'items';
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

			foreach ( $this->tabs as $tab ) {
				$tab->handle( $action, $items );
			}

			$redirect = add_query_arg(
				array(
					'page'    => self::PAGE,
					'tab'     => $this->current_tab(),
					'updated' => 'true',
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redirect );
			exit;
		}

		/**
		 * Render the panel with its top tabs.
		 */
		public function render_page() {

			$tab  = $this->current_tab();
			$tabs = array(
				'items'      => __( 'Menu Items', 'my-account-customizer' ),
				'templates'  => __( 'Templates', 'my-account-customizer' ),
				'general'    => __( 'Settings', 'my-account-customizer' ),
				'customizer' => __( 'Customizer', 'my-account-customizer' ),
				'banners'    => __( 'Banners', 'my-account-customizer' ),
			);
			?>
			<div class="wrap acfw-wrap">
				<div class="acfw-header">
					<div class="acfw-header-left">
						<div class="acfw-header-brand">
							<img class="acfw-header-icon" src="<?php echo esc_url( ACFW_ASSETS_URL . '/images/icon.svg' ); ?>" alt="<?php esc_attr_e( 'My Account Customizer', 'my-account-customizer' ); ?>" width="42" height="42" />
						</div>
						<nav class="acfw-tabs">
							<?php
							foreach ( $tabs as $slug => $label ) :
								$tab_url = 'customizer' === $slug
									? ACFW_Customizer::url()
									: admin_url( 'admin.php?page=' . self::PAGE . '&tab=' . $slug );
								?>
								<a href="<?php echo esc_url( $tab_url ); ?>"
									class="acfw-tab <?php echo $tab === $slug ? 'is-active' : ''; ?>">
									<?php echo esc_html( $label ); ?>
								</a>
							<?php endforeach; ?>
						</nav>
					</div>
					<div class="acfw-header-actions">
						<?php if ( 'items' === $tab ) : ?>
							<button type="button" class="button acfw-header-btn acfw-add-btn" data-type="endpoint">
								<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add endpoint', 'my-account-customizer' ); ?>
							</button>
							<button type="button" class="button acfw-header-btn acfw-add-btn" data-type="group">
								<span class="dashicons dashicons-portfolio"></span> <?php esc_html_e( 'Add group', 'my-account-customizer' ); ?>
							</button>
							<button type="button" class="button acfw-header-btn acfw-add-btn" data-type="link">
								<span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Add link', 'my-account-customizer' ); ?>
							</button>
							<button type="button" class="button acfw-header-btn acfw-add-btn" data-type="page">
								<span class="dashicons dashicons-media-document"></span> <?php esc_html_e( 'Add page', 'my-account-customizer' ); ?>
							</button>
						<?php endif; ?>
						<?php if ( 'banners' === $tab ) : ?>
							<button type="button" class="button acfw-header-btn acfw-add-banner-btn">
								<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add banner', 'my-account-customizer' ); ?>
							</button>
						<?php endif; ?>
						<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
							<button type="button" class="button acfw-header-btn acfw-icon-only acfw-preview-btn" data-url="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" title="<?php esc_attr_e( 'Preview', 'my-account-customizer' ); ?>" aria-label="<?php esc_attr_e( 'Preview', 'my-account-customizer' ); ?>">
								<span class="dashicons dashicons-visibility"></span>
								<span class="acfw-btn-text"><?php esc_html_e( 'Preview', 'my-account-customizer' ); ?></span>
							</button>
							<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button acfw-header-btn acfw-icon-only" target="_blank" rel="noopener" title="<?php esc_attr_e( 'View My Account', 'my-account-customizer' ); ?>" aria-label="<?php esc_attr_e( 'View My Account', 'my-account-customizer' ); ?>">
								<span class="dashicons dashicons-external"></span>
								<span class="acfw-btn-text"><?php esc_html_e( 'View My Account', 'my-account-customizer' ); ?></span>
							</a>
						<?php endif; ?>
					</div>
				</div>
				<hr class="wp-header-end" />

				<?php
				$acfw_toasts = array();
				if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$acfw_toasts[] = array(
						'type' => 'success',
						'msg'  => __( 'Changes saved.', 'my-account-customizer' ),
					);
				}
				$acfw_import_notice = get_transient( 'acfw_import_notice' );
				if ( $acfw_import_notice ) {
					delete_transient( 'acfw_import_notice' );
					$acfw_ok       = 'success' === $acfw_import_notice;
					$acfw_toasts[] = array(
						'type' => $acfw_ok ? 'success' : 'error',
						'msg'  => $acfw_ok ? __( 'Configuration imported.', 'my-account-customizer' ) : $acfw_import_notice,
					);
				}
				if ( $acfw_toasts ) :
					?>
					<div class="acfw-toast-wrap" aria-live="polite">
						<?php foreach ( $acfw_toasts as $t ) : ?>
							<div class="acfw-toast acfw-toast-<?php echo esc_attr( $t['type'] ); ?>">
								<span class="dashicons dashicons-<?php echo 'success' === $t['type'] ? 'yes-alt' : 'warning'; ?>"></span>
								<span class="acfw-toast-msg"><?php echo esc_html( $t['msg'] ); ?></span>
								<button type="button" class="acfw-toast-close" aria-label="<?php esc_attr_e( 'Dismiss', 'my-account-customizer' ); ?>">&times;</button>
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
					<span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Add smart tags', 'my-account-customizer' ); ?>
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
