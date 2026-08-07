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
			$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<div class="acfw-card">
			<form method="post" action="options.php">
				<?php settings_fields( 'acfw_settings' ); ?>
				<table class="form-table" role="presentation">
					<?php if ( 'general' === $tab ) : ?>

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

					<?php elseif ( 'style' === $tab ) : // style. ?>

						<tr>
							<th scope="row"><?php esc_html_e( 'Menu position', 'account-customizer-for-woocommerce' ); ?></th>
							<td>
								<?php
								$this->image_radio(
									'acfw_menu_position',
									get_option( 'acfw_menu_position', 'vertical-left' ),
									array(
										'vertical-left'  => array(
											'label' => __( 'Left', 'account-customizer-for-woocommerce' ),
											'img'   => 'vertical-left.svg',
										),
										'vertical-right' => array(
											'label' => __( 'Right', 'account-customizer-for-woocommerce' ),
											'img'   => 'vertical-right.svg',
										),
										'horizontal'     => array(
											'label' => __( 'Top', 'account-customizer-for-woocommerce' ),
											'img'   => 'top-horizontal.svg',
										),
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
										'circle' => array(
											'label' => __( 'Circle', 'account-customizer-for-woocommerce' ),
											'img'   => 'circle-profile.svg',
										),
										'square' => array(
											'label' => __( 'Square', 'account-customizer-for-woocommerce' ),
											'img'   => 'square-profile.svg',
										),
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
										'left'   => array(
											'label' => __( 'Left', 'account-customizer-for-woocommerce' ),
											'img'   => 'align-left.svg',
										),
										'center' => array(
											'label' => __( 'Center', 'account-customizer-for-woocommerce' ),
											'img'   => 'align-center.svg',
										),
										'right'  => array(
											'label' => __( 'Right', 'account-customizer-for-woocommerce' ),
											'img'   => 'align-right.svg',
										),
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

			<?php if ( 'general' === $tab ) : ?>
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
			<?php endif; ?>
			</div>
			<?php
		}
	}
}
