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
						$pslug = acfw_sanitize_key( $pname );
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
					$pslug   = isset( $_POST['preset_slug'] ) ? acfw_sanitize_key( wp_unslash( $_POST['preset_slug'] ) ) : '';
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
					$pslug   = isset( $_POST['preset_slug'] ) ? acfw_sanitize_key( wp_unslash( $_POST['preset_slug'] ) ) : '';
					$presets = get_option( 'acfw_presets', array() );
					if ( is_array( $presets ) ) {
						unset( $presets[ $pslug ] );
						update_option( 'acfw_presets', $presets );
					}
					break;

				case 'reset':
					global $wpdb;
					$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'acfw\_%'" ); // phpcs:ignore WordPress.DB
					wp_cache_flush();
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
					'label' => __( 'General', 'account-customizer-for-woocommerce' ),
					'icon'  => 'admin-settings',
				),
				'presets' => array(
					'label' => __( 'Presets & Reset', 'account-customizer-for-woocommerce' ),
					'icon'  => 'art',
				),
				'tools'   => array(
					'label' => __( 'Import / Export', 'account-customizer-for-woocommerce' ),
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
			<form method="post" action="options.php">
				<?php settings_fields( 'acfw_settings' ); ?>
				<table class="form-table" role="presentation">

						<tr>
							<th scope="row"><?php esc_html_e( 'AJAX navigation', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<div class="acfw-switch-row">
									<label class="acfw-switch acfw-switch-lg">
										<input type="checkbox" name="acfw_ajax_navigation" value="yes"
											<?php checked( 'yes', get_option( 'acfw_ajax_navigation', 'no' ) ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
									<span class="acfw-control-hint"><?php esc_html_e( 'Load endpoints without a full page reload.', 'account-customizer-for-woocommerce' ); ?></span>
								</div>
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

						<tr>
							<th scope="row"><?php esc_html_e( 'After-login redirect', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<select name="acfw_login_redirect">
									<?php $lr = get_option( 'acfw_login_redirect', '' ); ?>
									<option value="" <?php selected( $lr, '' ); ?>><?php esc_html_e( 'Default (dashboard)', 'account-customizer-for-woocommerce' ); ?></option>
									<?php
									foreach ( ACFW()->items->get_items() as $key => $item ) {
										if ( 'endpoint' !== ( $item['type'] ?? 'endpoint' ) ) {
											continue;
										}
										printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $lr, $key, false ), esc_html( $item['label'] ) );
									}
									?>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'After-logout redirect', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<?php $lo = get_option( 'acfw_logout_redirect', 'default' ); ?>
								<select name="acfw_logout_redirect">
									<option value="default" <?php selected( $lo, 'default' ); ?>><?php esc_html_e( 'Default', 'account-customizer-for-woocommerce' ); ?></option>
									<option value="home" <?php selected( $lo, 'home' ); ?>><?php esc_html_e( 'Home page', 'account-customizer-for-woocommerce' ); ?></option>
									<option value="login" <?php selected( $lo, 'login' ); ?>><?php esc_html_e( 'My Account (login)', 'account-customizer-for-woocommerce' ); ?></option>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Guest message', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<textarea name="acfw_guest_message" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'acfw_guest_message', '' ) ); ?></textarea>
								<p class="acfw-hint"><?php esc_html_e( 'Shown above the login form for logged-out visitors.', 'account-customizer-for-woocommerce' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Track endpoint views', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<div class="acfw-switch-row">
									<label class="acfw-switch acfw-switch-lg">
										<input type="checkbox" name="acfw_track_views" value="yes" <?php checked( 'yes', get_option( 'acfw_track_views', 'no' ) ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
									<span class="acfw-control-hint"><?php esc_html_e( 'Count how often each endpoint is viewed.', 'account-customizer-for-woocommerce' ); ?></span>
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
							<th scope="row"><?php esc_html_e( 'Buy Again', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<div class="acfw-switch-row">
									<label class="acfw-switch acfw-switch-lg">
										<input type="checkbox" name="acfw_buyagain_enable" value="yes" <?php checked( 'yes', get_option( 'acfw_buyagain_enable', 'no' ) ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
									<span class="acfw-control-hint"><?php esc_html_e( 'Add a "Buy again" menu tab and dashboard tile for one-click reordering of past products.', 'account-customizer-for-woocommerce' ); ?></span>
								</div>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Recently viewed', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<div class="acfw-switch-row">
									<label class="acfw-switch acfw-switch-lg">
										<input type="checkbox" name="acfw_recent_enable" value="yes" <?php checked( 'yes', get_option( 'acfw_recent_enable', 'no' ) ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
									<span class="acfw-control-hint"><?php esc_html_e( 'Show a Recently Viewed products tile on the dashboard.', 'account-customizer-for-woocommerce' ); ?></span>
								</div>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Order tracking', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<div class="acfw-switch-row">
									<label class="acfw-switch acfw-switch-lg">
										<input type="checkbox" name="acfw_tracking_enable" value="yes" <?php checked( 'yes', get_option( 'acfw_tracking_enable', 'no' ) ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
									<span class="acfw-control-hint"><?php esc_html_e( 'Show a tracking summary of recent orders on the dashboard ( auto-detects WooCommerce Shipment Tracking ).', 'account-customizer-for-woocommerce' ); ?></span>
								</div>
							</td>
						</tr>

				</table>
				<div class="acfw-form-footer">
					<?php submit_button( __( 'Save changes', 'account-customizer-for-woocommerce' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
			</div><!-- .acfw-card -->
			<?php endif; // General section. ?>

			<?php if ( 'presets' === $section ) : ?>
			<div class="acfw-card">
				<div class="acfw-presets">
					<h2 class="acfw-section-title"><?php esc_html_e( 'Design presets', 'account-customizer-for-woocommerce' ); ?></h2>
					<p class="acfw-hint"><?php esc_html_e( 'Save the current design as a named preset, then apply it anytime.', 'account-customizer-for-woocommerce' ); ?></p>
					<form method="post" class="acfw-preset-save">
						<?php wp_nonce_field( self::NONCE ); ?>
						<input type="hidden" name="acfw_action" value="save_preset" />
						<input type="text" name="preset_name" placeholder="<?php esc_attr_e( 'Preset name', 'account-customizer-for-woocommerce' ); ?>" required />
						<button type="submit" class="button button-primary"><span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save current design', 'account-customizer-for-woocommerce' ); ?></button>
					</form>
					<?php $presets = get_option( 'acfw_presets', array() ); ?>
					<?php if ( ! empty( $presets ) && is_array( $presets ) ) : ?>
						<ul class="acfw-preset-list">
							<?php foreach ( $presets as $pslug => $pdata ) : ?>
								<li>
									<span class="acfw-preset-name"><?php echo esc_html( $pdata['__label'] ?? $pslug ); ?></span>
									<span class="acfw-preset-actions">
										<form method="post"><?php wp_nonce_field( self::NONCE ); ?><input type="hidden" name="acfw_action" value="apply_preset" /><input type="hidden" name="preset_slug" value="<?php echo esc_attr( $pslug ); ?>" /><button type="submit" class="button"><?php esc_html_e( 'Apply', 'account-customizer-for-woocommerce' ); ?></button></form>
										<form method="post"><?php wp_nonce_field( self::NONCE ); ?><input type="hidden" name="acfw_action" value="delete_preset" /><input type="hidden" name="preset_slug" value="<?php echo esc_attr( $pslug ); ?>" /><button type="submit" class="button acfw-preset-del"><?php esc_html_e( 'Delete', 'account-customizer-for-woocommerce' ); ?></button></form>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<form method="post" class="acfw-reset-form">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="reset" />
					<button type="submit" class="button acfw-reset-btn"><span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Reset all settings', 'account-customizer-for-woocommerce' ); ?></button>
					<span class="acfw-hint"><?php esc_html_e( 'Restore endpoints, design and banners to defaults. Cannot be undone.', 'account-customizer-for-woocommerce' ); ?></span>
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
