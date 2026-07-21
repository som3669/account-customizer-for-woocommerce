<?php
/**
 * Admin settings panel.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Admin' ) ) {

	/**
	 * Registers the admin panel, settings and menu-items builder.
	 */
	class ACFW_Admin {

		const PAGE  = 'acfw-settings';
		const NONCE = 'acfw_admin_action';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_init', array( $this, 'handle_actions' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'media_buttons', array( $this, 'render_smart_tag_button' ), 20 );
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
				__( 'My Account Customizer', 'account-customizer-for-woocommerce' ),
				__( 'My Account', 'account-customizer-for-woocommerce' ),
				'manage_woocommerce',
				self::PAGE,
				array( $this, 'render_page' ),
				$icon,
				56
			);

			$base = 'admin.php?page=' . self::PAGE;
			$subs = array(
				self::PAGE            => __( 'General', 'account-customizer-for-woocommerce' ),
				$base . '&tab=items'  => __( 'Menu Items', 'account-customizer-for-woocommerce' ),
				ACFW_Customizer::url() => __( 'Customizer', 'account-customizer-for-woocommerce' ),
				$base . '&tab=banners' => __( 'Banners', 'account-customizer-for-woocommerce' ),
				$base . '&tab=tools'  => __( 'Import / Export', 'account-customizer-for-woocommerce' ),
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
			$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'account-customizer-for-woocommerce' ) . '</a>';
			array_unshift( $links, $link );
			return $links;
		}

		/**
		 * Register general + style options via the Settings API.
		 */
		public function register_settings() {

			$settings = array(
				'acfw_menu_position'    => 'sanitize_text_field',
				'acfw_menu_layout'      => 'sanitize_text_field',
				'acfw_ajax_navigation'  => 'sanitize_text_field',
				'acfw_default_endpoint' => 'sanitize_text_field',
				'acfw_accent_color'     => 'sanitize_hex_color',
				'acfw_text_color'       => 'sanitize_hex_color',
				'acfw_menu_radius'      => 'absint',
				'acfw_menu_gap'         => 'absint',
				'acfw_item_padding'     => 'absint',
				'acfw_font_size'        => 'absint',
				'acfw_font_weight'      => 'sanitize_text_field',
				'acfw_avatar_enable'    => 'sanitize_text_field',
				'acfw_avatar_shape'     => 'sanitize_text_field',
				'acfw_avatar_align'     => 'sanitize_text_field',
				'acfw_avatar_size'      => 'absint',
				'acfw_avatar_show_name' => 'sanitize_text_field',
				'acfw_avatar_show_role' => 'sanitize_text_field',
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
			wp_enqueue_style(
				'acfw-admin',
				ACFW_ASSETS_URL . '/css/admin.css',
				array(),
				ACFW_VERSION
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

			wp_enqueue_script(
				'acfw-admin',
				ACFW_ASSETS_URL . '/js/admin.js',
				$deps,
				ACFW_VERSION,
				true
			);
			wp_localize_script(
				'acfw-admin',
				'acfwAdmin',
				array(
					'confirmDelete' => __( 'Delete this item? This cannot be undone.', 'account-customizer-for-woocommerce' ),
					'mediaTitle'    => __( 'Select an icon image', 'account-customizer-for-woocommerce' ),
					'mediaButton'   => __( 'Use this image', 'account-customizer-for-woocommerce' ),
				)
			);
		}

		/**
		 * Get the active tab.
		 *
		 * @return string
		 */
		protected function current_tab() {
			$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return in_array( $tab, array( 'general', 'items', 'banners', 'tools' ), true ) ? $tab : 'general';
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

			switch ( $action ) {

				case 'add_item':
					$type  = isset( $_POST['item_type'] ) ? sanitize_key( wp_unslash( $_POST['item_type'] ) ) : 'endpoint';
					$label = isset( $_POST['item_label'] ) ? sanitize_text_field( wp_unslash( $_POST['item_label'] ) ) : '';
					if ( '' !== $label ) {
						$key = acfw_sanitize_key( $label );
						$items->save_item(
							$key,
							$type,
							array(
								'label'  => $label,
								'slug'   => $key,
								'active' => true,
							),
							false
						);
						$order = json_decode( get_option( 'acfw_items_order', '[]' ), true );
						$order = is_array( $order ) ? $order : array();
						// Seed with current items so the new one lands at the end
						// ( otherwise default items append after it ).
						if ( empty( $order ) ) {
							foreach ( $items->get_items() as $existing_key => $existing ) {
								$order[ $existing_key ] = array( 'type' => $existing['type'] ?? 'endpoint' );
							}
						}
						$order[ $key ] = array( 'type' => $type );
						$items->save_order( $order );
					}
					break;

				case 'save_all':
					$items_in = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each field sanitized below.
					foreach ( $items_in as $raw_key => $data ) {
						$key = acfw_sanitize_key( $raw_key );
						if ( '' === $key || ! is_array( $data ) ) {
							continue;
						}
						$type        = isset( $data['type'] ) ? sanitize_key( $data['type'] ) : 'endpoint';
						$roles       = isset( $data['usr_roles'] ) ? array_map( 'sanitize_key', (array) $data['usr_roles'] ) : array();
						$icon_source = isset( $data['icon_source'] ) ? sanitize_key( $data['icon_source'] ) : 'choose';

						$items->save_item(
							$key,
							$type,
							array(
								'label'            => isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : '',
								'icon_source'      => 'upload' === $icon_source ? 'upload' : 'choose',
								'icon'             => ( 'upload' !== $icon_source && isset( $data['icon'] ) ) ? acfw_sanitize_icon( $data['icon'] ) : '',
								'icon_url'         => ( 'upload' === $icon_source && isset( $data['icon_url'] ) ) ? esc_url_raw( $data['icon_url'] ) : '',
								'class'            => isset( $data['class'] ) ? sanitize_html_class( $data['class'] ) : '',
								'active'           => ! empty( $data['active'] ),
								'content'          => isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '',
								'content_position' => isset( $data['content_position'] ) ? sanitize_key( $data['content_position'] ) : 'before',
								'usr_roles'        => $roles,
								'visibility'       => empty( $roles ) ? 'all' : 'roles',
								'url'              => isset( $data['url'] ) ? esc_url_raw( $data['url'] ) : '',
								'banner_slug'      => isset( $data['banner_slug'] ) ? acfw_sanitize_key( $data['banner_slug'] ) : '',
								'banner_position'  => ( isset( $data['banner_position'] ) && 'bottom' === $data['banner_position'] ) ? 'bottom' : 'top',
							),
							false
						);
					}

					$raw   = isset( $_POST['acfw_order'] ) ? sanitize_textarea_field( wp_unslash( $_POST['acfw_order'] ) ) : '';
					$order = json_decode( $raw, true );
					if ( is_array( $order ) && ! empty( $order ) ) {
						$items->save_order( $order );
					} else {
						$items->build( true );
					}
					break;

				case 'remove_item':
					$key = isset( $_POST['item_key'] ) ? acfw_sanitize_key( wp_unslash( $_POST['item_key'] ) ) : '';
					if ( '' !== $key ) {
						$items->remove_item( $key );
						$order = json_decode( get_option( 'acfw_items_order', '[]' ), true );
						if ( is_array( $order ) ) {
							unset( $order[ $key ] );
							$items->save_order( $order );
						}
					}
					break;

				case 'save_order':
					$raw   = isset( $_POST['acfw_order'] ) ? sanitize_textarea_field( wp_unslash( $_POST['acfw_order'] ) ) : '';
					$order = json_decode( $raw, true );
					if ( is_array( $order ) ) {
						$items->save_order( $order );
					}
					break;

				case 'save_banner':
					$roles = isset( $_POST['banner_roles'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['banner_roles'] ) ) : array();
					$bdata = array(
						'type'          => isset( $_POST['banner_type'] ) ? sanitize_key( wp_unslash( $_POST['banner_type'] ) ) : 'widget',
						'title'         => isset( $_POST['banner_title'] ) ? sanitize_text_field( wp_unslash( $_POST['banner_title'] ) ) : '',
						'content'       => isset( $_POST['banner_content'] ) ? wp_kses_post( wp_unslash( $_POST['banner_content'] ) ) : '',
						'image_url'     => isset( $_POST['banner_image_url'] ) ? esc_url_raw( wp_unslash( $_POST['banner_image_url'] ) ) : '',
						'icon'          => isset( $_POST['banner_icon'] ) ? acfw_sanitize_icon( wp_unslash( $_POST['banner_icon'] ) ) : '',
						'icon_source'   => isset( $_POST['banner_icon_source'] ) ? sanitize_key( wp_unslash( $_POST['banner_icon_source'] ) ) : 'choose',
						'icon_url'      => isset( $_POST['banner_icon_url'] ) ? esc_url_raw( wp_unslash( $_POST['banner_icon_url'] ) ) : '',
						'icon_width'    => isset( $_POST['banner_icon_width'] ) ? absint( wp_unslash( $_POST['banner_icon_width'] ) ) : 40,
						'widget_width'  => isset( $_POST['banner_widget_width'] ) ? absint( wp_unslash( $_POST['banner_widget_width'] ) ) : 250,
						'show_count'    => ! empty( $_POST['banner_show_count'] ) ? 'yes' : 'no',
						'link_type'     => isset( $_POST['banner_link_type'] ) ? sanitize_key( wp_unslash( $_POST['banner_link_type'] ) ) : 'none',
						'link_endpoint' => isset( $_POST['banner_link_endpoint'] ) ? acfw_sanitize_key( wp_unslash( $_POST['banner_link_endpoint'] ) ) : '',
						'link'          => isset( $_POST['banner_link'] ) ? esc_url_raw( wp_unslash( $_POST['banner_link'] ) ) : '',
						'roles'         => $roles,
					);
					foreach ( array_keys( ACFW_Banners::color_fields() ) as $ckey ) {
						$bdata[ $ckey ] = isset( $_POST[ 'banner_' . $ckey ] ) ? sanitize_hex_color( wp_unslash( $_POST[ 'banner_' . $ckey ] ) ) : '';
					}
					ACFW_Banners::save(
						isset( $_POST['banner_key'] ) ? sanitize_text_field( wp_unslash( $_POST['banner_key'] ) ) : '',
						$bdata
					);
					break;

				case 'remove_banner':
					$bkey = isset( $_POST['banner_key'] ) ? acfw_sanitize_key( wp_unslash( $_POST['banner_key'] ) ) : '';
					if ( '' !== $bkey ) {
						ACFW_Banners::remove( $bkey );
					}
					break;

				case 'export':
					$json = ACFW_Import_Export::export_json();
					nocache_headers();
					header( 'Content-Type: application/json; charset=utf-8' );
					header( 'Content-Disposition: attachment; filename=account-customizer-' . gmdate( 'Y-m-d' ) . '.json' );
					header( 'Content-Length: ' . strlen( $json ) );
					echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
					exit;

				case 'import':
					$json = '';
					if ( ! empty( $_FILES['acfw_import_file']['tmp_name'] ) && is_uploaded_file( $_FILES['acfw_import_file']['tmp_name'] ) ) {
						$json = file_get_contents( $_FILES['acfw_import_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					} elseif ( ! empty( $_POST['acfw_import_json'] ) ) {
						$json = wp_unslash( $_POST['acfw_import_json'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated as JSON in importer.
					}
					$result = ACFW_Import_Export::import( (string) $json );
					set_transient( 'acfw_import_notice', is_wp_error( $result ) ? $result->get_error_message() : 'success', 30 );
					break;
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
				'general' => __( 'General', 'account-customizer-for-woocommerce' ),
				'items'   => __( 'Menu Items', 'account-customizer-for-woocommerce' ),
				'customizer' => __( 'Customizer', 'account-customizer-for-woocommerce' ),
				'banners' => __( 'Banners', 'account-customizer-for-woocommerce' ),
				'tools'   => __( 'Import / Export', 'account-customizer-for-woocommerce' ),
			);
			?>
			<div class="wrap acfw-wrap">
				<div class="acfw-header">
					<div class="acfw-header-left">
						<div class="acfw-header-brand">
							<img class="acfw-header-icon" src="<?php echo esc_url( ACFW_ASSETS_URL . '/images/icon.svg' ); ?>" alt="<?php esc_attr_e( 'My Account Customizer', 'account-customizer-for-woocommerce' ); ?>" width="42" height="42" />
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
								<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add endpoint', 'account-customizer-for-woocommerce' ); ?>
							</button>
							<button type="button" class="button acfw-header-btn acfw-add-btn" data-type="group">
								<span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Add group', 'account-customizer-for-woocommerce' ); ?>
							</button>
							<button type="button" class="button acfw-header-btn acfw-add-btn" data-type="link">
								<span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Add link', 'account-customizer-for-woocommerce' ); ?>
							</button>
						<?php endif; ?>
						<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
							<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button acfw-header-btn" target="_blank" rel="noopener">
								<span class="dashicons dashicons-external"></span>
								<?php esc_html_e( 'View My Account', 'account-customizer-for-woocommerce' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
				<hr class="wp-header-end" />

				<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
					<div class="notice notice-success is-dismissible acfw-notice"><p><?php esc_html_e( 'Changes saved.', 'account-customizer-for-woocommerce' ); ?></p></div>
				<?php endif; ?>

				<?php
				$acfw_import_notice = get_transient( 'acfw_import_notice' );
				if ( $acfw_import_notice ) :
					delete_transient( 'acfw_import_notice' );
					$acfw_ok = 'success' === $acfw_import_notice;
					?>
					<div class="notice <?php echo $acfw_ok ? 'notice-success' : 'notice-error'; ?> is-dismissible acfw-notice">
						<p><?php echo esc_html( $acfw_ok ? __( 'Configuration imported.', 'account-customizer-for-woocommerce' ) : $acfw_import_notice ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( 'items' === $tab ) : ?>
					<?php $this->render_items_tab(); ?>
				<?php elseif ( 'banners' === $tab ) : ?>
					<?php $this->render_banners_tab(); ?>
				<?php elseif ( 'tools' === $tab ) : ?>
					<?php $this->render_tools_tab(); ?>
				<?php else : ?>
					<div class="acfw-card"><?php $this->render_settings_tab( $tab ); ?></div>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Render the General / Style settings tab.
		 *
		 * @param string $tab Active tab.
		 */
		protected function render_settings_tab( $tab ) {
			?>
			<form method="post" action="options.php">
				<?php settings_fields( 'acfw_settings' ); ?>
				<table class="form-table" role="presentation">
					<?php if ( 'general' === $tab ) : ?>

						<tr>
							<th scope="row"><?php esc_html_e( 'AJAX navigation', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<label class="acfw-switch acfw-switch-lg">
									<input type="checkbox" name="acfw_ajax_navigation" value="yes"
										<?php checked( 'yes', get_option( 'acfw_ajax_navigation', 'no' ) ); ?> />
									<span class="acfw-switch-slider"></span>
								</label>
								<span class="acfw-control-hint"><?php esc_html_e( 'Load endpoints without a full page reload.', 'account-customizer-for-woocommerce' ); ?></span>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Default endpoint', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<select name="acfw_default_endpoint">
									<?php
									$default = get_option( 'acfw_default_endpoint', 'dashboard' );
									foreach ( ACFW()->items->get_items() as $key => $item ) {
										if ( 'endpoint' !== ( $item['type'] ?? 'endpoint' ) ) {
											continue;
										}
										printf(
											'<option value="%s" %s>%s</option>',
											esc_attr( $key ),
											selected( $default, $key, false ),
											esc_html( $item['label'] )
										);
									}
									?>
								</select>
							</td>
						</tr>

					<?php elseif ( 'style' === $tab ) : // style. ?>

						<tr>
							<th scope="row"><?php esc_html_e( 'Menu position', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<?php
								$this->image_radio(
									'acfw_menu_position',
									get_option( 'acfw_menu_position', 'vertical-left' ),
									array(
										'vertical-left'  => array( 'label' => __( 'Left', 'account-customizer-for-woocommerce' ), 'img' => 'vertical-left.svg' ),
										'vertical-right' => array( 'label' => __( 'Right', 'account-customizer-for-woocommerce' ), 'img' => 'vertical-right.svg' ),
										'horizontal'     => array( 'label' => __( 'Top', 'account-customizer-for-woocommerce' ), 'img' => 'top-horizontal.svg' ),
									),
									'vertical-left'
								);
								?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Menu layout', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<?php
								$this->buttonset(
									'acfw_menu_layout',
									get_option( 'acfw_menu_layout', 'simple' ),
									array(
										'simple'     => __( 'Simple', 'account-customizer-for-woocommerce' ),
										'classic'    => __( 'Classic', 'account-customizer-for-woocommerce' ),
										'modern'     => __( 'Modern', 'account-customizer-for-woocommerce' ),
										'no-borders' => __( 'No borders', 'account-customizer-for-woocommerce' ),
									),
									'simple'
								);
								?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Accent color', 'account-customizer-for-woocommerce' ); ?></th>
							<td><input type="text" name="acfw_accent_color" value="<?php echo esc_attr( get_option( 'acfw_accent_color', '#2271b1' ) ); ?>" class="acfw-color" /></td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Text color', 'account-customizer-for-woocommerce' ); ?></th>
							<td><input type="text" name="acfw_text_color" value="<?php echo esc_attr( get_option( 'acfw_text_color', '#333333' ) ); ?>" class="acfw-color" /></td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Corner radius', 'account-customizer-for-woocommerce' ); ?></th>
							<td><?php $this->slider( 'acfw_menu_radius', get_option( 'acfw_menu_radius', 8 ), 0, 24 ); ?></td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Item spacing', 'account-customizer-for-woocommerce' ); ?></th>
							<td><?php $this->slider( 'acfw_menu_gap', get_option( 'acfw_menu_gap', 4 ), 0, 24 ); ?></td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Item padding', 'account-customizer-for-woocommerce' ); ?></th>
							<td><?php $this->slider( 'acfw_item_padding', get_option( 'acfw_item_padding', 11 ), 4, 28 ); ?></td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Font size', 'account-customizer-for-woocommerce' ); ?></th>
							<td><?php $this->slider( 'acfw_font_size', get_option( 'acfw_font_size', 15 ), 11, 22 ); ?></td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Font weight', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<?php
								$this->buttonset(
									'acfw_font_weight',
									get_option( 'acfw_font_weight', '500' ),
									array(
										'400' => __( 'Normal', 'account-customizer-for-woocommerce' ),
										'500' => __( 'Medium', 'account-customizer-for-woocommerce' ),
										'600' => __( 'Bold', 'account-customizer-for-woocommerce' ),
									),
									'500'
								);
								?>
							</td>
						</tr>

					<?php else : // avatar. ?>

						<tr>
							<th scope="row"><?php esc_html_e( 'Show avatar', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<label class="acfw-switch acfw-switch-lg">
									<input type="checkbox" name="acfw_avatar_enable" value="yes"
										<?php checked( 'yes', get_option( 'acfw_avatar_enable', 'no' ) ); ?> />
									<span class="acfw-switch-slider"></span>
								</label>
								<span class="acfw-control-hint"><?php esc_html_e( 'Display the customer avatar above the account menu.', 'account-customizer-for-woocommerce' ); ?></span>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Avatar shape', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<?php
								$this->image_radio(
									'acfw_avatar_shape',
									get_option( 'acfw_avatar_shape', 'circle' ),
									array(
										'circle' => array( 'label' => __( 'Circle', 'account-customizer-for-woocommerce' ), 'img' => 'circle-profile.svg' ),
										'square' => array( 'label' => __( 'Square', 'account-customizer-for-woocommerce' ), 'img' => 'square-profile.svg' ),
									),
									'circle'
								);
								?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Avatar alignment', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<?php
								$this->image_radio(
									'acfw_avatar_align',
									get_option( 'acfw_avatar_align', 'center' ),
									array(
										'left'   => array( 'label' => __( 'Left', 'account-customizer-for-woocommerce' ), 'img' => 'align-left.svg' ),
										'center' => array( 'label' => __( 'Center', 'account-customizer-for-woocommerce' ), 'img' => 'align-center.svg' ),
										'right'  => array( 'label' => __( 'Right', 'account-customizer-for-woocommerce' ), 'img' => 'align-right.svg' ),
									),
									'center'
								);
								?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Avatar size', 'account-customizer-for-woocommerce' ); ?></th>
							<td><?php $this->slider( 'acfw_avatar_size', get_option( 'acfw_avatar_size', 72 ), 32, 160 ); ?></td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Show display name', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<label class="acfw-switch acfw-switch-lg">
									<input type="checkbox" name="acfw_avatar_show_name" value="yes"
										<?php checked( 'yes', get_option( 'acfw_avatar_show_name', 'yes' ) ); ?> />
									<span class="acfw-switch-slider"></span>
								</label>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Show user role', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<label class="acfw-switch acfw-switch-lg">
									<input type="checkbox" name="acfw_avatar_show_role" value="yes"
										<?php checked( 'yes', get_option( 'acfw_avatar_show_role', 'no' ) ); ?> />
									<span class="acfw-switch-slider"></span>
								</label>
							</td>
						</tr>

					<?php endif; ?>
				</table>
				<div class="acfw-form-footer">
					<?php submit_button( __( 'Save changes', 'account-customizer-for-woocommerce' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
			<?php
		}

		/**
		 * Render a range slider control with a px value bubble.
		 *
		 * @param string $name    Option / field name.
		 * @param int    $current Current value.
		 * @param int    $min     Minimum.
		 * @param int    $max     Maximum.
		 */
		protected function slider( $name, $current, $min = 0, $max = 24 ) {
			$current = '' === $current || null === $current ? $min : (int) $current;
			printf(
				'<input type="range" name="%1$s" min="%2$d" max="%3$d" step="1" value="%4$d" oninput="this.nextElementSibling.textContent=this.value+\'px\'" /><span class="acfw-range-val">%4$dpx</span>',
				esc_attr( $name ),
				(int) $min,
				(int) $max,
				(int) $current
			);
		}

		/**
		 * Info tooltip icon markup for a field label.
		 *
		 * @param string $text Tooltip text.
		 * @return string
		 */
		protected function tip( $text ) {
			return ' <span class="acfw-tip dashicons dashicons-info-outline" title="' . esc_attr( $text ) . '"></span>';
		}

		/**
		 * Render a media uploader box ( dropzone-style: click / drag to pick from
		 * the media library, or paste a URL ) with live preview.
		 *
		 * @param string $name  Field name storing the image URL.
		 * @param string $value Current URL.
		 */
		protected function uploader( $name, $value ) {
			$has = ! empty( $value );
			?>
			<div class="acfw-uploader">
				<div class="acfw-uploader-box <?php echo $has ? 'has-image' : ''; ?>">
					<div class="acfw-uploader-empty">
						<span class="dashicons dashicons-upload"></span>
						<p><?php esc_html_e( 'Drag & drop or', 'account-customizer-for-woocommerce' ); ?> <button type="button" class="acfw-uploader-browse acfw-media-btn"><?php esc_html_e( 'upload a file', 'account-customizer-for-woocommerce' ); ?></button></p>
					</div>
					<div class="acfw-uploader-preview">
						<img class="acfw-media-preview" src="<?php echo esc_url( $value ); ?>" alt="" <?php echo $has ? '' : 'hidden'; ?> />
						<button type="button" class="acfw-uploader-remove" title="<?php esc_attr_e( 'Remove', 'account-customizer-for-woocommerce' ); ?>">&times;</button>
					</div>
				</div>
				<div class="acfw-media-row">
					<input type="url" name="<?php echo esc_attr( $name ); ?>" class="acfw-media-input" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'Paste image URL', 'account-customizer-for-woocommerce' ); ?>" />
					<button type="button" class="button acfw-media-btn"><span class="dashicons dashicons-admin-media"></span> <?php esc_html_e( 'Media library', 'account-customizer-for-woocommerce' ); ?></button>
				</div>
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
					<span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Add smart tags', 'account-customizer-for-woocommerce' ); ?>
				</button>
				<div class="acfw-smarttag-menu" hidden>
					<?php foreach ( acfw_smart_tags() as $tag => $tag_label ) : ?>
						<button type="button" class="acfw-smarttag-item" data-tag="<?php echo esc_attr( $tag ); ?>"><?php echo esc_html( $tag_label ); ?> <code><?php echo esc_html( $tag ); ?></code></button>
					<?php endforeach; ?>
				</div>
			</span>
			<?php
		}

		/**
		 * Render a visual image-radio control ( thumbnail option picker ).
		 *
		 * @param string $name    Option / field name.
		 * @param string $current Current value.
		 * @param array  $choices value => array( 'label' => .., 'img' => file ).
		 * @param string $default Default value.
		 */
		protected function image_radio( $name, $current, $choices, $default = '' ) {
			$current = ( '' === $current || null === $current ) ? $default : $current;
			echo '<div class="acfw-radio-group acfw-image-radio" role="radiogroup">';
			foreach ( $choices as $value => $choice ) {
				$id     = sanitize_html_class( $name . '-' . $value );
				$active = (string) $current === (string) $value;
				printf(
					'<label class="acfw-image-card%1$s" for="%2$s"><input type="radio" id="%2$s" name="%3$s" value="%4$s" %5$s /><img src="%6$s" alt="" /><span class="acfw-image-label">%7$s</span></label>',
					$active ? ' is-active' : '',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					checked( $current, $value, false ),
					esc_url( ACFW_ASSETS_URL . '/images/controls/' . $choice['img'] ),
					esc_html( $choice['label'] )
				);
			}
			echo '</div>';
		}

		/**
		 * Render a segmented buttonset control (styled radio group).
		 *
		 * @param string $name    Option / field name.
		 * @param string $current Current value.
		 * @param array  $choices value => label pairs.
		 * @param string $default Default value when none stored.
		 */
		protected function buttonset( $name, $current, $choices, $default = '' ) {
			$current = ( '' === $current || null === $current ) ? $default : $current;
			echo '<div class="acfw-radio-group" role="radiogroup">';
			foreach ( $choices as $value => $label ) {
				$id     = sanitize_html_class( $name . '-' . $value );
				$active = (string) $current === (string) $value;
				printf(
					'<label class="acfw-radio-box%1$s" for="%2$s"><input type="radio" id="%2$s" name="%3$s" value="%4$s" %5$s /><span class="acfw-radio-dot"></span><span class="acfw-radio-text">%6$s</span></label>',
					$active ? ' is-active' : '',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					checked( $current, $value, false ),
					esc_html( $label )
				);
			}
			echo '</div>';
		}

		/**
		 * Render the Banners tab: create form + editable list.
		 */
		protected function render_banners_tab() {

			$banners = ACFW_Banners::all();
			?>
			<div class="acfw-card">
				<h2 class="acfw-section-title"><?php esc_html_e( 'Add a banner', 'account-customizer-for-woocommerce' ); ?></h2>
				<p class="acfw-hint"><?php esc_html_e( 'Create reusable widget or image banners, then assign them to an endpoint from the Menu Items tab.', 'account-customizer-for-woocommerce' ); ?></p>
				<?php $this->render_banner_form( '', ACFW_Banners::defaults(), true ); ?>
			</div>

			<?php if ( ! empty( $banners ) ) : ?>
				<div class="acfw-card">
					<h2 class="acfw-section-title"><?php esc_html_e( 'Your banners', 'account-customizer-for-woocommerce' ); ?></h2>
					<?php foreach ( $banners as $slug => $banner ) : ?>
						<?php $this->render_banner_form( $slug, wp_parse_args( $banner, ACFW_Banners::defaults() ), false ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php
		}

		/**
		 * Render a single banner create/edit form.
		 *
		 * @param string $slug   Banner slug ( '' for the create form ).
		 * @param array  $banner Banner options.
		 * @param bool   $is_new Whether this is the create form.
		 */
		protected function render_banner_form( $slug, $banner, $is_new ) {
			$roles     = wp_roles()->get_names();
			$sel_roles = (array) ( $banner['roles'] ?? array() );
			?>
			<form method="post" class="acfw-banner-form">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="acfw_action" value="save_banner" />
				<?php if ( ! $is_new ) : ?>
					<input type="hidden" name="banner_key" value="<?php echo esc_attr( $slug ); ?>" />
					<div class="acfw-banner-form-head">
						<strong><?php echo esc_html( $banner['title'] ? $banner['title'] : $slug ); ?></strong>
						<span class="acfw-node-badge acfw-badge-<?php echo esc_attr( $banner['type'] ); ?>"><?php echo esc_html( $banner['type'] ); ?></span>
					</div>
				<?php endif; ?>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Banner name', 'account-customizer-for-woocommerce' ); ?></label>
					<input type="text" name="banner_title" value="<?php echo esc_attr( $banner['title'] ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Banner type', 'account-customizer-for-woocommerce' ); ?></label>
					<?php $this->buttonset( 'banner_type', $banner['type'], array( 'widget' => __( 'Widget', 'account-customizer-for-woocommerce' ), 'image' => __( 'Image', 'account-customizer-for-woocommerce' ) ), 'widget' ); ?>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Banner icon', 'account-customizer-for-woocommerce' ); ?></label>
					<?php $b_icon_src = ( 'upload' === ( $banner['icon_source'] ?? 'choose' ) || ! empty( $banner['icon_url'] ) ) ? 'upload' : 'choose'; ?>
					<div class="acfw-icon-source">
						<label class="acfw-radio-card <?php echo 'choose' === $b_icon_src ? 'is-active' : ''; ?>"><input type="radio" name="banner_icon_source" value="choose" <?php checked( $b_icon_src, 'choose' ); ?> /><span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e( 'Choose icon', 'account-customizer-for-woocommerce' ); ?></label>
						<label class="acfw-radio-card <?php echo 'upload' === $b_icon_src ? 'is-active' : ''; ?>"><input type="radio" name="banner_icon_source" value="upload" <?php checked( $b_icon_src, 'upload' ); ?> /><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload icon', 'account-customizer-for-woocommerce' ); ?></label>
					</div>
					<div class="acfw-icon-choose" <?php echo 'choose' === $b_icon_src ? '' : 'hidden'; ?>>
						<select name="banner_icon" class="acfw-icon-select">
							<option value=""><?php esc_html_e( '— Select an icon —', 'account-customizer-for-woocommerce' ); ?></option>
							<?php foreach ( $this->icon_choices() as $ic => $ic_label ) : ?>
								<option value="<?php echo esc_attr( $ic ); ?>" <?php selected( $banner['icon'] ?? '', $ic ); ?>><?php echo esc_html( $ic_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="acfw-icon-upload" <?php echo 'upload' === $b_icon_src ? '' : 'hidden'; ?>>
						<?php $this->uploader( 'banner_icon_url', $banner['icon_url'] ?? '' ); ?>
					</div>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Icon width (px)', 'account-customizer-for-woocommerce' ); ?></label>
					<input type="number" name="banner_icon_width" min="16" max="100" value="<?php echo esc_attr( $banner['icon_width'] ?? 40 ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Widget width (px)', 'account-customizer-for-woocommerce' ); ?></label>
					<input type="number" name="banner_widget_width" min="200" max="700" value="<?php echo esc_attr( $banner['widget_width'] ?? 250 ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Widget text', 'account-customizer-for-woocommerce' ); ?></label>
					<textarea name="banner_content" rows="3"><?php echo esc_textarea( $banner['content'] ); ?></textarea>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Image', 'account-customizer-for-woocommerce' ); ?></label>
					<?php $this->uploader( 'banner_image_url', $banner['image_url'] ); ?>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Banner colors', 'account-customizer-for-woocommerce' ); ?></label>
					<div class="acfw-swatch-row">
						<?php foreach ( ACFW_Banners::color_fields() as $ckey => $clabel ) : ?>
							<span class="acfw-swatch">
								<input type="text" name="banner_<?php echo esc_attr( $ckey ); ?>" class="acfw-color" value="<?php echo esc_attr( $banner[ $ckey ] ?? '' ); ?>" />
								<span class="acfw-swatch-label"><?php echo esc_html( $clabel ); ?></span>
							</span>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Show item-count badge', 'account-customizer-for-woocommerce' ); ?></label>
					<label class="acfw-switch acfw-switch-lg"><input type="checkbox" name="banner_show_count" value="yes" <?php checked( 'yes', $banner['show_count'] ?? 'no' ); ?> /><span class="acfw-switch-slider"></span></label>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Banner link', 'account-customizer-for-woocommerce' ); ?></label>
					<?php $this->buttonset( 'banner_link_type', $banner['link_type'] ?? 'none', array( 'none' => __( 'None', 'account-customizer-for-woocommerce' ), 'endpoint' => __( 'Endpoint', 'account-customizer-for-woocommerce' ), 'external' => __( 'External URL', 'account-customizer-for-woocommerce' ) ), 'none' ); ?>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Link endpoint', 'account-customizer-for-woocommerce' ); ?></label>
					<select name="banner_link_endpoint">
						<option value=""><?php esc_html_e( '— Select —', 'account-customizer-for-woocommerce' ); ?></option>
						<?php foreach ( ACFW()->items->get_items() as $ep_key => $ep ) : ?>
							<?php if ( 'endpoint' !== ( $ep['type'] ?? 'endpoint' ) ) { continue; } ?>
							<option value="<?php echo esc_attr( $ep_key ); ?>" <?php selected( $banner['link_endpoint'] ?? '', $ep_key ); ?>><?php echo esc_html( $ep['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'External URL', 'account-customizer-for-woocommerce' ); ?></label>
					<input type="url" name="banner_link" value="<?php echo esc_attr( $banner['link'] ); ?>" placeholder="https://…" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Show banner to', 'account-customizer-for-woocommerce' ); ?></label>
					<select name="banner_roles[]" multiple size="4" class="acfw-roles-select">
						<?php foreach ( $roles as $role_key => $role_label ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( in_array( $role_key, $sel_roles, true ) ); ?>><?php echo esc_html( $role_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="acfw-hint"><?php esc_html_e( 'Leave empty to show to all users.', 'account-customizer-for-woocommerce' ); ?></p>
				</div>

				<div class="acfw-form-footer">
					<?php if ( ! $is_new ) : ?>
						<button type="submit" class="button acfw-banner-delete" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Delete', 'account-customizer-for-woocommerce' ); ?></button>
					<?php endif; ?>
					<button type="submit" class="button button-primary"><?php echo $is_new ? esc_html__( 'Create banner', 'account-customizer-for-woocommerce' ) : esc_html__( 'Save banner', 'account-customizer-for-woocommerce' ); ?></button>
				</div>
			</form>
			<?php
		}

		/**
		 * Render the Import / Export tab.
		 */
		protected function render_tools_tab() {
			?>
			<div class="acfw-card">
				<h2 class="acfw-section-title"><?php esc_html_e( 'Export', 'account-customizer-for-woocommerce' ); ?></h2>
				<p class="acfw-hint"><?php esc_html_e( 'Download all endpoints, design settings and banners as a JSON file.', 'account-customizer-for-woocommerce' ); ?></p>
				<form method="post">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="export" />
					<div class="acfw-form-footer" style="justify-content:flex-start;border-top:0;padding-top:0;margin-top:8px;">
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-download" style="vertical-align:text-bottom;"></span>
							<?php esc_html_e( 'Export configuration', 'account-customizer-for-woocommerce' ); ?>
						</button>
					</div>
				</form>
			</div>

			<div class="acfw-card">
				<h2 class="acfw-section-title"><?php esc_html_e( 'Import', 'account-customizer-for-woocommerce' ); ?></h2>
				<p class="acfw-hint"><?php esc_html_e( 'Upload a JSON file, or paste its contents. This overwrites your current configuration.', 'account-customizer-for-woocommerce' ); ?></p>
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="import" />
					<div class="acfw-field">
						<label><?php esc_html_e( 'JSON file', 'account-customizer-for-woocommerce' ); ?></label>
						<input type="file" name="acfw_import_file" accept="application/json,.json" />
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Or paste JSON', 'account-customizer-for-woocommerce' ); ?></label>
						<textarea name="acfw_import_json" rows="6" placeholder="{ &quot;plugin&quot;: &quot;account-customizer-for-woocommerce&quot;, … }"></textarea>
					</div>
					<div class="acfw-form-footer">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Import configuration', 'account-customizer-for-woocommerce' ); ?></button>
					</div>
				</form>
			</div>
			<?php
		}

		/**
		 * Render the Menu Items builder tab: left selectable list + right options form.
		 */
		protected function render_items_tab() {

			$items = ACFW()->items->get_items();
			?>
			<div class="acfw-builder">

				<div class="acfw-builder-bar">
					<form method="post" class="acfw-add-form" style="display:none;">
						<?php wp_nonce_field( self::NONCE ); ?>
						<input type="hidden" name="acfw_action" value="add_item" />
						<input type="hidden" name="item_type" class="acfw-add-type" value="endpoint" />
						<input type="text" name="item_label" class="acfw-add-label" placeholder="<?php esc_attr_e( 'New item label', 'account-customizer-for-woocommerce' ); ?>" required />
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Create', 'account-customizer-for-woocommerce' ); ?></button>
						<button type="button" class="button acfw-add-cancel"><?php esc_html_e( 'Cancel', 'account-customizer-for-woocommerce' ); ?></button>
					</form>
				</div>

				<form method="post" class="acfw-items-form">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="save_all" />
					<input type="hidden" name="acfw_order" class="acfw-order-input" value="" />

					<div class="acfw-builder-layout">

						<div class="acfw-card acfw-builder-list">
							<ol class="acfw-sortable acfw-sortable-root">
								<?php
								foreach ( $items as $key => $item ) {
									$this->render_item_row( $key, $item );
								}
								?>
							</ol>
						</div>

						<div class="acfw-card acfw-builder-detail">
							<div class="acfw-detail-empty">
								<span class="dashicons dashicons-arrow-left-alt"></span>
								<p><?php esc_html_e( 'Select a menu item on the left to edit its options.', 'account-customizer-for-woocommerce' ); ?></p>
							</div>
							<?php
							$render_details = function ( $list ) use ( &$render_details ) {
								foreach ( $list as $k => $it ) {
									$this->render_item_detail( $k, $it );
									if ( ! empty( $it['children'] ) ) {
										$render_details( $it['children'] );
									}
								}
							};
							$render_details( $items );
							?>
						</div>
					</div>

					<div class="acfw-form-footer">
						<button type="submit" class="button button-primary acfw-save-all">
							<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save changes', 'account-customizer-for-woocommerce' ); ?>
						</button>
					</div>
				</form>

				<form method="post" class="acfw-delete-form" style="display:none;">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="remove_item" />
					<input type="hidden" name="item_key" class="acfw-delete-key" value="" />
				</form>
			</div>
			<?php
		}

		/**
		 * Render a single row in the left list (recurses into group children).
		 *
		 * @param string $key  Item key.
		 * @param array  $item Item options.
		 */
		protected function render_item_row( $key, $item ) {

			$type       = $item['type'] ?? 'endpoint';
			$defaults   = ACFW()->items->get_defaults();
			$is_default = array_key_exists( $key, $defaults );
			$active     = ! empty( $item['active'] );
			?>
			<li class="acfw-node acfw-type-<?php echo esc_attr( $type ); ?> <?php echo $active ? '' : 'is-inactive'; ?>"
				data-key="<?php echo esc_attr( $key ); ?>" data-type="<?php echo esc_attr( $type ); ?>">

				<div class="acfw-node-head">
					<span class="acfw-drag dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag', 'account-customizer-for-woocommerce' ); ?>"></span>
					<?php echo $this->icon_markup( $item, 'acfw-node-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="acfw-node-title"><?php echo esc_html( $item['label'] ); ?></span>
					<span class="acfw-node-spacer"></span>
					<label class="acfw-switch" title="<?php esc_attr_e( 'Enable / disable', 'account-customizer-for-woocommerce' ); ?>">
						<input type="checkbox" class="acfw-active-proxy" data-key="<?php echo esc_attr( $key ); ?>" <?php checked( $active ); ?> />
						<span class="acfw-switch-slider"></span>
					</label>
					<?php if ( ! $is_default ) : ?>
						<button type="button" class="acfw-node-remove dashicons dashicons-trash" title="<?php esc_attr_e( 'Delete', 'account-customizer-for-woocommerce' ); ?>" data-key="<?php echo esc_attr( $key ); ?>"></button>
					<?php endif; ?>
				</div>

				<?php if ( 'group' === $type ) : ?>
					<ol class="acfw-sortable acfw-sortable-children">
						<?php
						if ( ! empty( $item['children'] ) ) {
							foreach ( $item['children'] as $child_key => $child_item ) {
								$this->render_item_row( $child_key, $child_item );
							}
						}
						?>
					</ol>
				<?php endif; ?>
			</li>
			<?php
		}

		/**
		 * Render the options form for one item (right column, hidden until selected).
		 *
		 * @param string $key  Item key.
		 * @param array  $item Item options.
		 */
		protected function render_item_detail( $key, $item ) {

			$type        = $item['type'] ?? 'endpoint';
			$roles       = wp_roles()->get_names();
			$active       = ! empty( $item['active'] );
			$icon_source = ( ! empty( $item['icon_url'] ) || ( isset( $item['icon_source'] ) && 'upload' === $item['icon_source'] ) ) ? 'upload' : 'choose';
			$sel_roles   = (array) ( $item['usr_roles'] ?? array() );
			$type_labels = array(
				'endpoint' => __( 'Endpoint', 'account-customizer-for-woocommerce' ),
				'group'    => __( 'Group', 'account-customizer-for-woocommerce' ),
				'link'     => __( 'Link', 'account-customizer-for-woocommerce' ),
			);
			?>
			<div class="acfw-detail acfw-item-form" data-key="<?php echo esc_attr( $key ); ?>" data-type="<?php echo esc_attr( $type ); ?>" hidden>
				<input type="hidden" name="items[<?php echo esc_attr( $key ); ?>][type]" value="<?php echo esc_attr( $type ); ?>" />
				<input type="hidden" name="items[<?php echo esc_attr( $key ); ?>][active]" class="acfw-active-input" value="<?php echo $active ? '1' : '0'; ?>" />

				<div class="acfw-detail-head">
					<?php echo $this->icon_markup( $item, 'acfw-detail-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="acfw-detail-title"><?php echo esc_html( $item['label'] ); ?></h2>
					<span class="acfw-node-badge acfw-badge-<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type_labels[ $type ] ?? $type ); ?></span>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Endpoint label', 'account-customizer-for-woocommerce' ); ?><?php echo $this->tip( __( 'The menu label shown to customers.', 'account-customizer-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input type="text" name="items[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $item['label'] ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Endpoint icon', 'account-customizer-for-woocommerce' ); ?><?php echo $this->tip( __( 'Choose a FontAwesome icon or upload your own image.', 'account-customizer-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<div class="acfw-icon-source">
						<label class="acfw-radio-card <?php echo 'choose' === $icon_source ? 'is-active' : ''; ?>">
							<input type="radio" name="items[<?php echo esc_attr( $key ); ?>][icon_source]" value="choose" <?php checked( $icon_source, 'choose' ); ?> />
							<span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e( 'Choose icon', 'account-customizer-for-woocommerce' ); ?>
						</label>
						<label class="acfw-radio-card <?php echo 'upload' === $icon_source ? 'is-active' : ''; ?>">
							<input type="radio" name="items[<?php echo esc_attr( $key ); ?>][icon_source]" value="upload" <?php checked( $icon_source, 'upload' ); ?> />
							<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload icon', 'account-customizer-for-woocommerce' ); ?>
						</label>
					</div>

				</div>

				<div class="acfw-field acfw-icon-choose" <?php echo 'choose' === $icon_source ? '' : 'hidden'; ?>>
					<label><?php esc_html_e( 'Select an icon', 'account-customizer-for-woocommerce' ); ?></label>
					<select name="items[<?php echo esc_attr( $key ); ?>][icon]" class="acfw-icon-select">
						<option value=""><?php esc_html_e( '— Select an icon —', 'account-customizer-for-woocommerce' ); ?></option>
						<?php foreach ( $this->icon_choices() as $ic => $label ) : ?>
							<option value="<?php echo esc_attr( $ic ); ?>" <?php selected( $item['icon'] ?? '', $ic ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="acfw-field acfw-icon-upload" <?php echo 'upload' === $icon_source ? '' : 'hidden'; ?>>
					<label><?php esc_html_e( 'Upload an image', 'account-customizer-for-woocommerce' ); ?></label>
					<?php $this->uploader( 'items[' . $key . '][icon_url]', $item['icon_url'] ?? '' ); ?>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'CSS class', 'account-customizer-for-woocommerce' ); ?><?php echo $this->tip( __( 'Optional extra CSS class for this menu item.', 'account-customizer-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input type="text" name="items[<?php echo esc_attr( $key ); ?>][class]" value="<?php echo esc_attr( $item['class'] ?? '' ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'User roles', 'account-customizer-for-woocommerce' ); ?><?php echo $this->tip( __( 'Show only to selected roles. Empty = everyone.', 'account-customizer-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<select name="items[<?php echo esc_attr( $key ); ?>][usr_roles][]" multiple size="4" class="acfw-roles-select">
						<?php foreach ( $roles as $role_key => $role_label ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( in_array( $role_key, $sel_roles, true ) ); ?>><?php echo esc_html( $role_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="acfw-hint"><?php esc_html_e( 'Leave empty to show for everyone.', 'account-customizer-for-woocommerce' ); ?></p>
				</div>

				<?php if ( 'link' === $type ) : ?>
					<div class="acfw-field">
						<label><?php esc_html_e( 'URL', 'account-customizer-for-woocommerce' ); ?></label>
						<input type="url" name="items[<?php echo esc_attr( $key ); ?>][url]" value="<?php echo esc_attr( $item['url'] ?? '' ); ?>" />
					</div>
				<?php elseif ( 'endpoint' === $type ) : ?>
					<?php $eid = 'acfw_content_' . str_replace( '-', '_', $key ); ?>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Custom content', 'account-customizer-for-woocommerce' ); ?><?php echo $this->tip( __( 'Extra content added to this endpoint. Use Add media and smart tags.', 'account-customizer-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
						<div class="acfw-content-wrap">
							<?php
							wp_editor(
								$item['content'] ?? '',
								$eid,
								array(
									'textarea_name' => 'items[' . $key . '][content]',
									'textarea_rows' => 6,
									'media_buttons' => true,
									'quicktags'     => true,
									'tinymce'       => array( 'toolbar1' => 'bold,italic,bullist,numlist,link,undo,redo' ),
								)
							);
							?>
						</div>
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Custom content position', 'account-customizer-for-woocommerce' ); ?><?php echo $this->tip( __( 'Where the custom content appears relative to the default content.', 'account-customizer-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
						<select name="items[<?php echo esc_attr( $key ); ?>][content_position]">
							<?php $cp = $item['content_position'] ?? 'before'; ?>
							<option value="before" <?php selected( $cp, 'before' ); ?>><?php esc_html_e( 'Before default content', 'account-customizer-for-woocommerce' ); ?></option>
							<option value="after" <?php selected( $cp, 'after' ); ?>><?php esc_html_e( 'After default content', 'account-customizer-for-woocommerce' ); ?></option>
							<option value="override" <?php selected( $cp, 'override' ); ?>><?php esc_html_e( 'Replace default content', 'account-customizer-for-woocommerce' ); ?></option>
						</select>
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Banner', 'account-customizer-for-woocommerce' ); ?></label>
						<select name="items[<?php echo esc_attr( $key ); ?>][banner_slug]">
							<option value=""><?php esc_html_e( '— None —', 'account-customizer-for-woocommerce' ); ?></option>
							<?php foreach ( ACFW_Banners::all() as $b_slug => $b ) : ?>
								<option value="<?php echo esc_attr( $b_slug ); ?>" <?php selected( $item['banner_slug'] ?? '', $b_slug ); ?>><?php echo esc_html( $b['title'] ? $b['title'] : $b_slug ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Banner position', 'account-customizer-for-woocommerce' ); ?></label>
						<?php
						$this->buttonset(
							'banner_position',
							$item['banner_position'] ?? 'top',
							array(
								'top'    => __( 'Top', 'account-customizer-for-woocommerce' ),
								'bottom' => __( 'Bottom', 'account-customizer-for-woocommerce' ),
							),
							'top'
						);
						?>
					</div>
				<?php endif; ?>

			</div>
			<?php
		}

		/**
		 * Build the icon markup for an item (uploaded image or dashicon).
		 *
		 * @param array  $item  Item options.
		 * @param string $class Wrapper class.
		 * @return string
		 */
		protected function icon_markup( $item, $class ) {
			$icon = ! empty( $item['icon'] ) ? $item['icon'] : '';
			if ( empty( $item['icon_url'] ) && '' === $icon ) {
				$icon = 'dashicons-menu-alt';
			}
			return acfw_icon_markup( $icon, $item['icon_url'] ?? '', $class );
		}

		/**
		 * Curated list of dashicons offered in the icon picker.
		 *
		 * @return array
		 */
		protected function icon_choices() {
			$choices = array();
			foreach ( acfw_icon_list() as $class ) {
				// Derive a readable label from the fa-* name.
				$name              = preg_replace( '/^.*fa-/', '', $class );
				$choices[ $class ] = ucwords( str_replace( '-', ' ', $name ) );
			}
			return $choices;
		}
	}
}
