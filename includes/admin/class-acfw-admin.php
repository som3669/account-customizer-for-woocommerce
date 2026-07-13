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
			add_filter(
				'plugin_action_links_' . plugin_basename( ACFW_FILE ),
				array( $this, 'action_links' )
			);
		}

		/**
		 * Register the submenu page under WooCommerce.
		 */
		public function register_menu() {
			add_submenu_page(
				'woocommerce',
				__( 'My Account Customizer', 'account-customizer-for-woocommerce' ),
				__( 'My Account', 'account-customizer-for-woocommerce' ),
				'manage_woocommerce',
				self::PAGE,
				array( $this, 'render_page' )
			);
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
			if ( 'woocommerce_page_' . self::PAGE !== $hook ) {
				return;
			}

			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style(
				'acfw-admin',
				ACFW_ASSETS_URL . '/css/admin.css',
				array(),
				ACFW_VERSION
			);

			// Rich content editor + media for the endpoint content field.
			wp_enqueue_editor();
			wp_enqueue_media();

			wp_enqueue_script(
				'acfw-admin',
				ACFW_ASSETS_URL . '/js/admin.js',
				array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker', 'editor' ),
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
			return in_array( $tab, array( 'general', 'items', 'style' ), true ) ? $tab : 'general';
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
						$order         = json_decode( get_option( 'acfw_items_order', '[]' ), true );
						$order         = is_array( $order ) ? $order : array();
						$order[ $key ] = array( 'type' => $type );
						$items->save_order( $order );
					}
					break;

				case 'save_item':
					$key  = isset( $_POST['item_key'] ) ? acfw_sanitize_key( wp_unslash( $_POST['item_key'] ) ) : '';
					$type = isset( $_POST['item_type'] ) ? sanitize_key( wp_unslash( $_POST['item_type'] ) ) : 'endpoint';
					if ( '' !== $key ) {
						$roles       = isset( $_POST['usr_roles'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['usr_roles'] ) ) : array();
						$icon_source = isset( $_POST['icon_source'] ) ? sanitize_key( wp_unslash( $_POST['icon_source'] ) ) : 'choose';

						$data = array(
							'label'            => isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '',
							'icon_source'      => 'upload' === $icon_source ? 'upload' : 'choose',
							'icon'             => ( 'upload' !== $icon_source && isset( $_POST['icon'] ) ) ? sanitize_html_class( wp_unslash( $_POST['icon'] ) ) : '',
							'icon_url'         => ( 'upload' === $icon_source && isset( $_POST['icon_url'] ) ) ? esc_url_raw( wp_unslash( $_POST['icon_url'] ) ) : '',
							'class'            => isset( $_POST['class'] ) ? sanitize_html_class( wp_unslash( $_POST['class'] ) ) : '',
							'active'           => ! empty( $_POST['active'] ),
							'content'          => isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '',
							'content_position' => isset( $_POST['content_position'] ) ? sanitize_key( wp_unslash( $_POST['content_position'] ) ) : 'before',
							'usr_roles'        => $roles,
							'visibility'       => empty( $roles ) ? 'all' : 'roles',
							'url'              => isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '',
							'slug'             => isset( $_POST['slug'] ) ? acfw_sanitize_key( wp_unslash( $_POST['slug'] ) ) : $key,
						);
						$items->save_item( $key, $type, $data );
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
				'style'   => __( 'Style', 'account-customizer-for-woocommerce' ),
			);
			?>
			<div class="wrap acfw-wrap">
				<div class="acfw-header">
					<span class="dashicons dashicons-admin-customizer acfw-header-icon"></span>
					<div>
						<h1><?php esc_html_e( 'My Account Customizer', 'account-customizer-for-woocommerce' ); ?></h1>
						<p class="acfw-subtitle"><?php esc_html_e( 'Reorder the menu, add endpoints, control visibility and restyle the WooCommerce account area.', 'account-customizer-for-woocommerce' ); ?></p>
					</div>
				</div>

				<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
					<div class="notice notice-success is-dismissible acfw-notice"><p><?php esc_html_e( 'Changes saved.', 'account-customizer-for-woocommerce' ); ?></p></div>
				<?php endif; ?>

				<nav class="acfw-tabs">
					<?php foreach ( $tabs as $slug => $label ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '&tab=' . $slug ) ); ?>"
							class="acfw-tab <?php echo $tab === $slug ? 'is-active' : ''; ?>">
							<?php echo esc_html( $label ); ?>
						</a>
					<?php endforeach; ?>
				</nav>

				<?php if ( 'items' === $tab ) : ?>
					<?php $this->render_items_tab(); ?>
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
								<label>
									<input type="checkbox" name="acfw_ajax_navigation" value="yes"
										<?php checked( 'yes', get_option( 'acfw_ajax_navigation', 'no' ) ); ?> />
									<?php esc_html_e( 'Load endpoints without a full page reload.', 'account-customizer-for-woocommerce' ); ?>
								</label>
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

					<?php else : // style. ?>

						<tr>
							<th scope="row"><?php esc_html_e( 'Menu position', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<?php $pos = get_option( 'acfw_menu_position', 'vertical-left' ); ?>
								<select name="acfw_menu_position">
									<option value="vertical-left" <?php selected( $pos, 'vertical-left' ); ?>><?php esc_html_e( 'Vertical (left)', 'account-customizer-for-woocommerce' ); ?></option>
									<option value="vertical-right" <?php selected( $pos, 'vertical-right' ); ?>><?php esc_html_e( 'Vertical (right)', 'account-customizer-for-woocommerce' ); ?></option>
									<option value="horizontal" <?php selected( $pos, 'horizontal' ); ?>><?php esc_html_e( 'Horizontal', 'account-customizer-for-woocommerce' ); ?></option>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Menu layout', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<?php $layout = get_option( 'acfw_menu_layout', 'simple' ); ?>
								<select name="acfw_menu_layout">
									<option value="simple" <?php selected( $layout, 'simple' ); ?>><?php esc_html_e( 'Simple', 'account-customizer-for-woocommerce' ); ?></option>
									<option value="modern" <?php selected( $layout, 'modern' ); ?>><?php esc_html_e( 'Modern', 'account-customizer-for-woocommerce' ); ?></option>
									<option value="no-borders" <?php selected( $layout, 'no-borders' ); ?>><?php esc_html_e( 'No borders', 'account-customizer-for-woocommerce' ); ?></option>
								</select>
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
							<td>
								<input type="range" name="acfw_menu_radius" min="0" max="24" step="1"
									value="<?php echo esc_attr( get_option( 'acfw_menu_radius', 12 ) ); ?>"
									oninput="this.nextElementSibling.textContent=this.value+'px'" />
								<span class="acfw-range-val"><?php echo esc_html( get_option( 'acfw_menu_radius', 12 ) ); ?>px</span>
							</td>
						</tr>

					<?php endif; ?>
				</table>
				<?php submit_button(); ?>
			</form>
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
					<div class="acfw-add-buttons">
						<button type="button" class="button acfw-add-btn" data-type="endpoint">
							<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add endpoint', 'account-customizer-for-woocommerce' ); ?>
						</button>
						<button type="button" class="button acfw-add-btn" data-type="group">
							<span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Add group', 'account-customizer-for-woocommerce' ); ?>
						</button>
						<button type="button" class="button acfw-add-btn" data-type="link">
							<span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Add link', 'account-customizer-for-woocommerce' ); ?>
						</button>
					</div>

					<form method="post" class="acfw-add-form" style="display:none;">
						<?php wp_nonce_field( self::NONCE ); ?>
						<input type="hidden" name="acfw_action" value="add_item" />
						<input type="hidden" name="item_type" class="acfw-add-type" value="endpoint" />
						<input type="text" name="item_label" class="acfw-add-label" placeholder="<?php esc_attr_e( 'New item label', 'account-customizer-for-woocommerce' ); ?>" required />
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Create', 'account-customizer-for-woocommerce' ); ?></button>
						<button type="button" class="button acfw-add-cancel"><?php esc_html_e( 'Cancel', 'account-customizer-for-woocommerce' ); ?></button>
					</form>
				</div>

				<div class="acfw-builder-layout">

					<div class="acfw-card acfw-builder-list">
						<ol class="acfw-sortable acfw-sortable-root">
							<?php
							foreach ( $items as $key => $item ) {
								$this->render_item_row( $key, $item );
							}
							?>
						</ol>
						<form method="post" class="acfw-order-form">
							<?php wp_nonce_field( self::NONCE ); ?>
							<input type="hidden" name="acfw_action" value="save_order" />
							<input type="hidden" name="acfw_order" class="acfw-order-input" value="" />
							<button type="submit" class="button button-primary acfw-save-order">
								<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save menu order', 'account-customizer-for-woocommerce' ); ?>
							</button>
						</form>
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
			<form method="post" class="acfw-detail acfw-item-form" data-key="<?php echo esc_attr( $key ); ?>" hidden>
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="acfw_action" value="save_item" />
				<input type="hidden" name="item_key" value="<?php echo esc_attr( $key ); ?>" />
				<input type="hidden" name="item_type" value="<?php echo esc_attr( $type ); ?>" />
				<input type="hidden" name="active" class="acfw-active-input" value="<?php echo $active ? '1' : '0'; ?>" />

				<div class="acfw-detail-head">
					<?php echo $this->icon_markup( $item, 'acfw-detail-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="acfw-detail-title"><?php echo esc_html( $item['label'] ); ?></h2>
					<span class="acfw-node-badge acfw-badge-<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type_labels[ $type ] ?? $type ); ?></span>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Label', 'account-customizer-for-woocommerce' ); ?></label>
					<input type="text" name="label" value="<?php echo esc_attr( $item['label'] ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Icon', 'account-customizer-for-woocommerce' ); ?></label>
					<div class="acfw-icon-source">
						<label class="acfw-radio-card <?php echo 'choose' === $icon_source ? 'is-active' : ''; ?>">
							<input type="radio" name="icon_source" value="choose" <?php checked( $icon_source, 'choose' ); ?> />
							<span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e( 'Choose icon', 'account-customizer-for-woocommerce' ); ?>
						</label>
						<label class="acfw-radio-card <?php echo 'upload' === $icon_source ? 'is-active' : ''; ?>">
							<input type="radio" name="icon_source" value="upload" <?php checked( $icon_source, 'upload' ); ?> />
							<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload icon', 'account-customizer-for-woocommerce' ); ?>
						</label>
					</div>

					<div class="acfw-icon-choose" <?php echo 'choose' === $icon_source ? '' : 'hidden'; ?>>
						<select name="icon" class="acfw-icon-select">
							<option value=""><?php esc_html_e( '— Select an icon —', 'account-customizer-for-woocommerce' ); ?></option>
							<?php foreach ( $this->icon_choices() as $ic => $label ) : ?>
								<option value="<?php echo esc_attr( $ic ); ?>" <?php selected( $item['icon'] ?? '', $ic ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="acfw-icon-upload" <?php echo 'upload' === $icon_source ? '' : 'hidden'; ?>>
						<input type="url" name="icon_url" class="acfw-icon-url" value="<?php echo esc_attr( $item['icon_url'] ?? '' ); ?>" placeholder="https://…" />
						<button type="button" class="button acfw-icon-media"><?php esc_html_e( 'Select image', 'account-customizer-for-woocommerce' ); ?></button>
					</div>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'CSS class', 'account-customizer-for-woocommerce' ); ?></label>
					<input type="text" name="class" value="<?php echo esc_attr( $item['class'] ?? '' ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'User roles', 'account-customizer-for-woocommerce' ); ?></label>
					<select name="usr_roles[]" multiple size="4" class="acfw-roles-select">
						<?php foreach ( $roles as $role_key => $role_label ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( in_array( $role_key, $sel_roles, true ) ); ?>><?php echo esc_html( $role_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="acfw-hint"><?php esc_html_e( 'Leave empty to show for everyone.', 'account-customizer-for-woocommerce' ); ?></p>
				</div>

				<?php if ( 'link' === $type ) : ?>
					<div class="acfw-field">
						<label><?php esc_html_e( 'URL', 'account-customizer-for-woocommerce' ); ?></label>
						<input type="url" name="url" value="<?php echo esc_attr( $item['url'] ?? '' ); ?>" />
					</div>
				<?php elseif ( 'endpoint' === $type ) : ?>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Custom content', 'account-customizer-for-woocommerce' ); ?></label>
						<textarea name="content" class="acfw-content-editor" id="acfw-content-<?php echo esc_attr( $key ); ?>" rows="6"><?php echo esc_textarea( $item['content'] ?? '' ); ?></textarea>
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Custom content position', 'account-customizer-for-woocommerce' ); ?></label>
						<select name="content_position">
							<?php $cp = $item['content_position'] ?? 'before'; ?>
							<option value="before" <?php selected( $cp, 'before' ); ?>><?php esc_html_e( 'Before default content', 'account-customizer-for-woocommerce' ); ?></option>
							<option value="after" <?php selected( $cp, 'after' ); ?>><?php esc_html_e( 'After default content', 'account-customizer-for-woocommerce' ); ?></option>
							<option value="override" <?php selected( $cp, 'override' ); ?>><?php esc_html_e( 'Replace default content', 'account-customizer-for-woocommerce' ); ?></option>
						</select>
					</div>
				<?php endif; ?>

				<div class="acfw-detail-actions">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'account-customizer-for-woocommerce' ); ?></button>
				</div>
			</form>
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
			if ( ! empty( $item['icon_url'] ) ) {
				return sprintf(
					'<img class="%s acfw-icon-img" src="%s" alt="" />',
					esc_attr( $class ),
					esc_url( $item['icon_url'] )
				);
			}
			$icon = ! empty( $item['icon'] ) ? $item['icon'] : 'dashicons-menu-alt';
			return sprintf( '<span class="%s dashicons %s"></span>', esc_attr( $class ), esc_attr( $icon ) );
		}

		/**
		 * Curated list of dashicons offered in the icon picker.
		 *
		 * @return array
		 */
		protected function icon_choices() {
			return array(
				'dashicons-dashboard'      => __( 'Dashboard', 'account-customizer-for-woocommerce' ),
				'dashicons-cart'           => __( 'Cart', 'account-customizer-for-woocommerce' ),
				'dashicons-download'       => __( 'Download', 'account-customizer-for-woocommerce' ),
				'dashicons-location'       => __( 'Location', 'account-customizer-for-woocommerce' ),
				'dashicons-money-alt'      => __( 'Money', 'account-customizer-for-woocommerce' ),
				'dashicons-admin-users'    => __( 'User', 'account-customizer-for-woocommerce' ),
				'dashicons-id'             => __( 'ID card', 'account-customizer-for-woocommerce' ),
				'dashicons-heart'          => __( 'Heart', 'account-customizer-for-woocommerce' ),
				'dashicons-star-filled'    => __( 'Star', 'account-customizer-for-woocommerce' ),
				'dashicons-tickets-alt'    => __( 'Tickets', 'account-customizer-for-woocommerce' ),
				'dashicons-email'          => __( 'Email', 'account-customizer-for-woocommerce' ),
				'dashicons-bell'           => __( 'Bell', 'account-customizer-for-woocommerce' ),
				'dashicons-admin-home'     => __( 'Home', 'account-customizer-for-woocommerce' ),
				'dashicons-portfolio'      => __( 'Portfolio', 'account-customizer-for-woocommerce' ),
				'dashicons-products'       => __( 'Products', 'account-customizer-for-woocommerce' ),
				'dashicons-awards'         => __( 'Awards', 'account-customizer-for-woocommerce' ),
				'dashicons-tag'            => __( 'Tag', 'account-customizer-for-woocommerce' ),
				'dashicons-admin-generic'  => __( 'Settings', 'account-customizer-for-woocommerce' ),
				'dashicons-exit'           => __( 'Log out', 'account-customizer-for-woocommerce' ),
				'dashicons-calendar-alt'   => __( 'Calendar', 'account-customizer-for-woocommerce' ),
			);
		}
	}
}
