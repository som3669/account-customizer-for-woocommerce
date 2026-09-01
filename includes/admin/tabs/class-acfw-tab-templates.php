<?php
/**
 * Starter Templates tab.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Tab_Templates' ) ) {

	/**
	 * Renders the Starter Templates gallery and handles applying a template.
	 */
	class ACFW_Tab_Templates extends ACFW_Admin_Tab {

		/**
		 * Handle POST actions for the templates tab.
		 *
		 * @param string     $action Sanitized action slug.
		 * @param ACFW_Items $items  Menu items manager.
		 */
		public function handle( $action, $items ) {
			// Nonce is verified in ACFW_Admin::handle_actions() before dispatch.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			switch ( $action ) {

				case 'apply_template':
					$tslug     = isset( $_POST['template_slug'] ) ? acfw_sanitize_key( wp_unslash( $_POST['template_slug'] ) ) : '';
					$templates = acfw_prebuilt_templates();
					if ( ! empty( $templates[ $tslug ]['options'] ) ) {
						// Reset the whole design to defaults first so the result is
						// deterministic and matches the template preview exactly,
						// with no leftover values from a previous template.
						foreach ( acfw_design_option_defaults() as $dk => $dv ) {
							update_option( $dk, $dv );
						}
						// Overlay the template's own values.
						$keys = acfw_design_option_keys();
						foreach ( $templates[ $tslug ]['options'] as $ok => $ov ) {
							if ( in_array( $ok, $keys, true ) ) {
								update_option( $ok, is_string( $ov ) ? sanitize_text_field( $ov ) : $ov );
							}
						}
						update_option( 'acfw_active_template', $tslug );
					}
					break;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Render the Starter Templates tab: a gallery of one-click designs.
		 */
		public function render() {
			?>
			<?php $active_tpl = get_option( 'acfw_active_template', '' ); ?>
			<div class="acfw-card acfw-templates">
				<h2 class="acfw-section-title"><?php esc_html_e( 'Starter templates', 'my-account-customizer' ); ?></h2>
				<p class="acfw-hint"><?php esc_html_e( 'One-click ready-made designs. Applying a template overwrites the related design settings.', 'my-account-customizer' ); ?></p>
				<div class="acfw-template-grid">
					<?php foreach ( acfw_prebuilt_templates() as $tslug => $tpl ) : ?>
						<?php $is_applied = ( $tslug === $active_tpl ); ?>
						<div class="acfw-template-card <?php echo $is_applied ? 'is-applied' : ''; ?>" style="--acfw-tpl-accent: <?php echo esc_attr( $tpl['accent'] ); ?>;">
							<?php if ( $is_applied ) : ?>
								<span class="acfw-template-badge"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Applied', 'my-account-customizer' ); ?></span>
							<?php endif; ?>
							<div class="acfw-template-preview acfw-tpl-<?php echo esc_attr( $tslug ); ?>">
								<?php $this->template_preview_mock( $tpl ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts. ?>
							</div>
							<div class="acfw-template-body">
								<strong class="acfw-template-name"><?php echo esc_html( $tpl['label'] ); ?></strong>
								<span class="acfw-template-desc"><?php echo esc_html( $tpl['description'] ); ?></span>
							</div>
							<form method="post" class="acfw-template-apply">
								<?php wp_nonce_field( self::NONCE ); ?>
								<input type="hidden" name="acfw_action" value="apply_template" />
								<input type="hidden" name="template_slug" value="<?php echo esc_attr( $tslug ); ?>" />
								<?php if ( $is_applied ) : ?>
									<button type="button" class="button acfw-tpl-applied" disabled><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Applied', 'my-account-customizer' ); ?></button>
								<?php else : ?>
									<button type="submit" class="button button-primary"><?php esc_html_e( 'Apply template', 'my-account-customizer' ); ?></button>
								<?php endif; ?>
							</form>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Render a miniature live mock of a template using the default endpoints.
		 *
		 * @param array $tpl Template definition ( label, accent, options ).
		 */
		protected function template_preview_mock( $tpl ) {
			$o                       = $tpl['options'];
			$position                = $o['acfw_menu_position'] ?? 'vertical-left';
			list( $layout, $preset ) = acfw_menu_style_resolve( $o['acfw_menu_style'] ?? 'simple' );
			$indicator               = $o['acfw_active_indicator'] ?? 'bar';
			$show_icons              = ( 'no' !== ( $o['acfw_show_icons'] ?? 'yes' ) );

			// Top row layout for tabs / horizontal, else a sidebar.
			$is_top = ( 'tabs' === $layout || 'horizontal' === $position );
			$pos    = $is_top ? 'top' : ( 'vertical-right' === $position ? 'right' : 'left' );

			$classes = array(
				'acfw-mock',
				'pos-' . $pos,
				'lay-' . sanitize_html_class( $layout ),
				'pre-' . sanitize_html_class( $preset ),
				'ind-' . sanitize_html_class( $indicator ),
			);

			// A few default endpoints for the mock ( first is active ).
			$items = array();
			foreach ( ACFW()->items->get_defaults() as $key => $def ) {
				if ( 'customer-logout' === $key ) {
					continue;
				}
				$items[] = array(
					'label' => $def['label'],
					'icon'  => $def['icon'] ?? '',
				);
				if ( count( $items ) >= 5 ) {
					break;
				}
			}
			?>
			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
				<ul class="acfw-mock-nav">
					<?php foreach ( $items as $i => $it ) : ?>
						<li class="acfw-mock-item <?php echo 0 === $i ? 'is-active' : ''; ?>">
							<?php if ( $show_icons && $it['icon'] ) : ?>
								<i class="acfw-mock-icon <?php echo esc_attr( $it['icon'] ); ?>"></i>
							<?php endif; ?>
							<span class="acfw-mock-label"><?php echo esc_html( $it['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="acfw-mock-content">
					<span class="acfw-mock-line w1"></span>
					<span class="acfw-mock-line w2"></span>
					<span class="acfw-mock-line w3"></span>
				</div>
			</div>
			<?php
		}
	}
}
