<?php
/**
 * General / Style settings tab.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Tab_Settings' ) ) {

	/**
	 * Renders the settings form and handles design presets + reset.
	 */
	class ACFW_Tab_Settings extends ACFW_Admin_Tab {

		/**
		 * Handle POST actions for the settings tab.
		 *
		 * @param string     $action Sanitized action slug.
		 * @param ACFW_Items $items  Menu items manager.
		 */
		public function handle( $action, $items ) {
			// Nonce is verified in ACFW_Admin::handle_actions() before dispatch.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			switch ( $action ) {

				case 'save_preset':
					$pname = isset( $_POST['preset_name'] ) ? sanitize_text_field( wp_unslash( $_POST['preset_name'] ) ) : '';
					if ( '' !== $pname ) {
						$pslug = acfw_item_key_from_label( $pname, 'preset' );
						$data  = array( '__label' => $pname );
						foreach ( acfw_design_option_keys() as $ok ) {
							$data[ $ok ] = get_option( $ok, '' );
						}
						$presets           = get_option( 'acfw_presets', array() );
						$presets           = is_array( $presets ) ? $presets : array();
						$presets[ $pslug ] = $data;
						update_option( 'acfw_presets', $presets );
					}
					break;

				case 'apply_preset':
					$pslug   = isset( $_POST['preset_slug'] ) ? acfw_sanitize_key( sanitize_title( wp_unslash( $_POST['preset_slug'] ) ) ) : '';
					$presets = get_option( 'acfw_presets', array() );
					if ( ! empty( $presets[ $pslug ] ) ) {
						$keys = acfw_design_option_keys();
						foreach ( $presets[ $pslug ] as $ok => $ov ) {
							if ( in_array( $ok, $keys, true ) ) {
								update_option( $ok, $ov );
							}
						}
					}
					break;

				case 'delete_preset':
					$pslug   = isset( $_POST['preset_slug'] ) ? acfw_sanitize_key( sanitize_title( wp_unslash( $_POST['preset_slug'] ) ) ) : '';
					$presets = get_option( 'acfw_presets', array() );
					if ( is_array( $presets ) ) {
						unset( $presets[ $pslug ] );
						update_option( 'acfw_presets', $presets );
					}
					break;

				case 'reset':
					global $wpdb;
					$names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'acfw_' ) . '%' )
					);
					// delete_option() clears each option's cache; flushing the whole
					// object cache would evict every other plugin's data too.
					foreach ( (array) $names as $name ) {
						delete_option( $name );
					}
					// Custom endpoints just disappeared; their rewrite rules must go too.
					update_option( 'acfw_flush_rewrite_rules', 1 );
					$items->build( true );
					break;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Render the General / Style settings tab.
		 */
		public function render() {
			// Inner section of the Settings page ( defaults to General ).
			$section  = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$sections = array(
				'general' => array(
					'label' => __( 'General', 'my-account-dashboard-builder' ),
					'icon'  => 'admin-settings',
				),
				'presets' => array(
					'label' => __( 'Presets & Reset', 'my-account-dashboard-builder' ),
					'icon'  => 'art',
				),
				'tools'   => array(
					'label' => __( 'Import / Export', 'my-account-dashboard-builder' ),
					'icon'  => 'migrate',
				),
			);
			if ( ! isset( $sections[ $section ] ) ) {
				$section = 'general';
			}
			$base = admin_url( 'admin.php?page=' . self::PAGE . '&tab=general' );
			?>
			<div class="acfw-subnav-layout">
			<nav class="acfw-subnav">
				<?php foreach ( $sections as $sslug => $smeta ) : ?>
					<a href="<?php echo esc_url( $base . '&section=' . $sslug ); ?>" class="acfw-subnav-item <?php echo $section === $sslug ? 'is-active' : ''; ?>">
						<span class="acfw-subnav-icon dashicons dashicons-<?php echo esc_attr( $smeta['icon'] ); ?>"></span>
						<span class="acfw-subnav-label"><?php echo esc_html( $smeta['label'] ); ?></span>
						<span class="acfw-subnav-caret dashicons dashicons-arrow-right-alt2"></span>
					</a>
				<?php endforeach; ?>
			</nav>
			<div class="acfw-subnav-body">

			<?php if ( 'general' === $section ) : ?>
			<div class="acfw-card">
				<?php
				$acfw_heads = array(
					'general' => array( 'admin-settings', __( 'General', 'my-account-dashboard-builder' ), __( 'Navigation, redirects and the commerce features', 'my-account-dashboard-builder' ) ),
					'presets' => array( 'art', __( 'Presets & Reset', 'my-account-dashboard-builder' ), __( 'Save a design you like, or start over', 'my-account-dashboard-builder' ) ),
					'tools'   => array( 'update', __( 'Import / Export', 'my-account-dashboard-builder' ), __( 'Move your configuration between sites', 'my-account-dashboard-builder' ) ),
				);
				$acfw_head  = $acfw_heads[ $section ] ?? $acfw_heads['general'];
				?>
				<div class="acfw-panel-head">
					<span class="acfw-panel-icon dashicons dashicons-<?php echo esc_attr( $acfw_head[0] ); ?>" aria-hidden="true"></span>
					<div class="acfw-panel-heading">
						<h2><?php echo esc_html( $acfw_head[1] ); ?></h2>
						<p><?php echo esc_html( $acfw_head[2] ); ?></p>
					</div>
				</div>
			<form method="post" action="options.php">
				<?php settings_fields( 'acfw_settings' ); ?>
				<table class="form-table" role="presentation">

						<tr>
							<th scope="row"><?php esc_html_e( 'AJAX navigation', 'my-account-dashboard-builder' ); ?></th>
							<td>
								<div class="acfw-switch-row">
									<label class="acfw-switch acfw-switch-lg">
										<input type="checkbox" name="acfw_ajax_navigation" value="yes"
											<?php checked( 'yes', get_option( 'acfw_ajax_navigation', 'no' ) ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
									<span class="acfw-control-hint"><?php esc_html_e( 'Load endpoints without a full page reload.', 'my-account-dashboard-builder' ); ?></span>
								</div>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Default endpoint', 'my-account-dashboard-builder' ); ?></th>
							<td>
								<select name="acfw_default_endpoint">
									<?php
									$default = acfw_default_endpoint();
									foreach ( ACFW()->items->get_flat_items() as $key => $item ) {
										if ( 'endpoint' !== ( $item['type'] ?? 'endpoint' ) || 'customer-logout' === $key ) {
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
								<p class="acfw-hint"><?php esc_html_e( 'Customers land here when they open My Account. With anything but Dashboard, the dashboard moves to its own /dashboard/ address and stays in the menu.', 'my-account-dashboard-builder' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'After-login redirect', 'my-account-dashboard-builder' ); ?></th>
							<td>
								<select name="acfw_login_redirect">
									<?php $lr = get_option( 'acfw_login_redirect', '' ); ?>
									<option value="" <?php selected( $lr, '' ); ?>><?php esc_html_e( 'Default (dashboard)', 'my-account-dashboard-builder' ); ?></option>
									<?php
									foreach ( ACFW()->items->get_flat_items() as $key => $item ) {
										if ( 'endpoint' !== ( $item['type'] ?? 'endpoint' ) || 'customer-logout' === $key ) {
											continue;
										}
										printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $lr, $key, false ), esc_html( $item['label'] ) );
									}
									?>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'After-logout redirect', 'my-account-dashboard-builder' ); ?></th>
							<td>
								<?php $lo = get_option( 'acfw_logout_redirect', 'default' ); ?>
								<select name="acfw_logout_redirect">
									<option value="default" <?php selected( $lo, 'default' ); ?>><?php esc_html_e( 'Default', 'my-account-dashboard-builder' ); ?></option>
									<option value="home" <?php selected( $lo, 'home' ); ?>><?php esc_html_e( 'Home page', 'my-account-dashboard-builder' ); ?></option>
									<option value="login" <?php selected( $lo, 'login' ); ?>><?php esc_html_e( 'My Account (login)', 'my-account-dashboard-builder' ); ?></option>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Guest message', 'my-account-dashboard-builder' ); ?></th>
							<td>
								<textarea name="acfw_guest_message" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'acfw_guest_message', '' ) ); ?></textarea>
								<p class="acfw-hint"><?php esc_html_e( 'Shown above the login form for logged-out visitors.', 'my-account-dashboard-builder' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Dashboard notice', 'my-account-dashboard-builder' ); ?></th>
							<td>
								<textarea name="acfw_dashboard_notice" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'acfw_dashboard_notice', '' ) ); ?></textarea>
								<p class="acfw-hint"><?php esc_html_e( 'Shown at the top of the account dashboard for logged-in customers. Leave empty to hide it. Smart tags work here, e.g. {first_name}.', 'my-account-dashboard-builder' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Notice style', 'my-account-dashboard-builder' ); ?></th>
							<td>
								<?php
								$this->buttonset(
									'acfw_notice_style',
									get_option( 'acfw_notice_style', 'info' ),
									array(
										'info'    => __( 'Info', 'my-account-dashboard-builder' ),
										'success' => __( 'Success', 'my-account-dashboard-builder' ),
										'warning' => __( 'Warning', 'my-account-dashboard-builder' ),
										'plain'   => __( 'Plain', 'my-account-dashboard-builder' ),
									),
									'info',
									array(
										'info'    => 'dot-ring',
										'success' => 'dot-ring',
										'warning' => 'dot-ring',
										'plain'   => 'dot-ring',
									)
								);
								?>
								<div class="acfw-switch-row" style="margin-top:10px;">
									<label class="acfw-switch acfw-switch-lg">
										<input type="hidden" name="acfw_notice_dismiss" value="no" />
										<input type="checkbox" name="acfw_notice_dismiss" value="yes" <?php checked( get_option( 'acfw_notice_dismiss', 'no' ), 'yes' ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
									<span><?php esc_html_e( 'Let customers dismiss it', 'my-account-dashboard-builder' ); ?></span>
								</div>
								<p class="acfw-hint"><?php esc_html_e( 'A dismissed notice stays hidden in that browser until you edit its text.', 'my-account-dashboard-builder' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Track endpoint views', 'my-account-dashboard-builder' ); ?></th>
							<td>
								<div class="acfw-switch-row">
									<label class="acfw-switch acfw-switch-lg">
										<input type="checkbox" name="acfw_track_views" value="yes" <?php checked( 'yes', get_option( 'acfw_track_views', 'no' ) ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
									<span class="acfw-control-hint"><?php esc_html_e( 'Count how often each endpoint is viewed.', 'my-account-dashboard-builder' ); ?></span>
								</div>
								<?php
								$views = get_option( 'acfw_endpoint_views', array() );
								if ( ! empty( $views ) && is_array( $views ) ) :
									arsort( $views );
									?>
									<ul class="acfw-view-stats">
										<?php foreach ( $views as $vk => $vc ) : ?>
											<li><span><?php echo esc_html( $vk ); ?></span> <strong><?php echo esc_html( number_format_i18n( (int) $vc ) ); ?></strong></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Buy Again', 'my-account-dashboard-builder' ); ?></th>
							<td>
								<div class="acfw-switch-row">
									<label class="acfw-switch acfw-switch-lg">
										<input type="checkbox" name="acfw_buyagain_enable" value="yes" <?php checked( 'yes', get_option( 'acfw_buyagain_enable', 'no' ) ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
									<span class="acfw-control-hint"><?php esc_html_e( 'Add a "Buy again" menu tab and dashboard tile for one-click reordering of past products.', 'my-account-dashboard-builder' ); ?></span>
								</div>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Recently viewed', 'my-account-dashboard-builder' ); ?></th>
							<td>
								<div class="acfw-switch-row">
									<label class="acfw-switch acfw-switch-lg">
										<input type="checkbox" name="acfw_recent_enable" value="yes" <?php checked( 'yes', get_option( 'acfw_recent_enable', 'no' ) ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
									<span class="acfw-control-hint"><?php esc_html_e( 'Add a "Recently viewed" menu tab listing the products the customer looked at last.', 'my-account-dashboard-builder' ); ?></span>
								</div>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Order tracking', 'my-account-dashboard-builder' ); ?></th>
							<td>
								<div class="acfw-switch-row">
									<label class="acfw-switch acfw-switch-lg">
										<input type="checkbox" name="acfw_tracking_enable" value="yes" <?php checked( 'yes', get_option( 'acfw_tracking_enable', 'no' ) ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
									<span class="acfw-control-hint"><?php esc_html_e( 'Show a tracking summary of recent orders on the dashboard ( auto-detects WooCommerce Shipment Tracking ).', 'my-account-dashboard-builder' ); ?></span>
								</div>
							</td>
						</tr>

				</table>
				<div class="acfw-form-footer">
					<?php submit_button( __( 'Save changes', 'my-account-dashboard-builder' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
			</div><!-- .acfw-card -->
			<?php endif; // General section. ?>

			<?php if ( 'presets' === $section ) : ?>
			<div class="acfw-card">
				<div class="acfw-presets">
					<h2 class="acfw-section-title"><?php esc_html_e( 'Design presets', 'my-account-dashboard-builder' ); ?></h2>
					<p class="acfw-hint"><?php esc_html_e( 'Save the current design as a named preset, then apply it anytime.', 'my-account-dashboard-builder' ); ?></p>
					<form method="post" class="acfw-preset-save">
						<?php wp_nonce_field( self::NONCE ); ?>
						<input type="hidden" name="acfw_action" value="save_preset" />
						<input type="text" name="preset_name" placeholder="<?php esc_attr_e( 'Preset name', 'my-account-dashboard-builder' ); ?>" required />
						<button type="submit" class="button button-primary"><span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save current design', 'my-account-dashboard-builder' ); ?></button>
					</form>
					<?php $presets = get_option( 'acfw_presets', array() ); ?>
					<?php if ( ! empty( $presets ) && is_array( $presets ) ) : ?>
						<ul class="acfw-preset-list">
							<?php foreach ( $presets as $pslug => $pdata ) : ?>
								<li>
									<span class="acfw-preset-name"><?php echo esc_html( $pdata['__label'] ?? $pslug ); ?></span>
									<span class="acfw-preset-actions">
										<form method="post"><?php wp_nonce_field( self::NONCE ); ?><input type="hidden" name="acfw_action" value="apply_preset" /><input type="hidden" name="preset_slug" value="<?php echo esc_attr( $pslug ); ?>" /><button type="submit" class="button"><?php esc_html_e( 'Apply', 'my-account-dashboard-builder' ); ?></button></form>
										<form method="post"><?php wp_nonce_field( self::NONCE ); ?><input type="hidden" name="acfw_action" value="delete_preset" /><input type="hidden" name="preset_slug" value="<?php echo esc_attr( $pslug ); ?>" /><button type="submit" class="button acfw-preset-del"><?php esc_html_e( 'Delete', 'my-account-dashboard-builder' ); ?></button></form>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<form method="post" class="acfw-reset-form">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="reset" />
					<button type="submit" class="button acfw-reset-btn"><span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Reset all settings', 'my-account-dashboard-builder' ); ?></button>
					<span class="acfw-hint"><?php esc_html_e( 'Restore endpoints, design and banners to defaults. Cannot be undone.', 'my-account-dashboard-builder' ); ?></span>
				</form>
			</div><!-- .acfw-card -->
			<?php endif; // Presets section. ?>

			<?php
			if ( 'tools' === $section && class_exists( 'ACFW_Tab_Tools' ) ) {
				$acfw_tools = new ACFW_Tab_Tools();
				$acfw_tools->render();
			}
			?>
			</div><!-- .acfw-subnav-body -->
			</div><!-- .acfw-subnav-layout -->
			<?php
		}
	}
}
