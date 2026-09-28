<?php
/**
 * Import / Export tab.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Tab_Tools' ) ) {

	/**
	 * Renders the Import / Export tab and handles its actions.
	 */
	class ACFW_Tab_Tools extends ACFW_Admin_Tab {

		/**
		 * Handle POST actions for the tools tab.
		 *
		 * @param string     $action Sanitized action slug.
		 * @param ACFW_Items $items  Menu items manager.
		 */
		public function handle( $action, $items ) {
			// Nonce is verified in ACFW_Admin::handle_actions() before dispatch.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			switch ( $action ) {

				case 'export':
					$json = ACFW_Import_Export::export_json();
					nocache_headers();
					header( 'Content-Type: application/json; charset=utf-8' );
					header( 'Content-Disposition: attachment; filename=my-account-dashboard-builder-' . gmdate( 'Y-m-d' ) . '.json' );
					header( 'Content-Length: ' . strlen( $json ) );
					echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
					exit;

				case 'import':
					$json = '';
					// Not unslashed on purpose: a PHP upload path on Windows contains
					// backslashes that wp_unslash() would strip, breaking the read.
					$tmp = isset( $_FILES['acfw_import_file']['tmp_name'] )
						? sanitize_text_field( $_FILES['acfw_import_file']['tmp_name'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
						: '';

					if ( '' !== $tmp && is_uploaded_file( $tmp ) ) {
						$json = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					} elseif ( ! empty( $_POST['acfw_import_json'] ) ) {
						$json = wp_unslash( $_POST['acfw_import_json'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated as JSON in importer.
					}
					$result = ACFW_Import_Export::import( (string) $json );
					if ( is_wp_error( $result ) ) {
						$this->add_notice( $result->get_error_message(), 'error' );
					} else {
						$this->add_notice( __( 'Configuration imported.', 'my-account-dashboard-builder' ), 'success' );
					}
					// The notice says what happened; a generic "Changes saved." next to an error would contradict it.
					$this->redirect_args['updated'] = false;
					break;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Render the Import / Export tab.
		 */
		public function render() {
			?>
			<div class="acfw-card">
				<h2 class="acfw-section-title"><?php esc_html_e( 'Export', 'my-account-dashboard-builder' ); ?></h2>
				<p class="acfw-hint"><?php esc_html_e( 'Download all endpoints, design settings and banners as a JSON file.', 'my-account-dashboard-builder' ); ?></p>
				<form method="post">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="export" />
					<div class="acfw-form-footer" style="justify-content:flex-start;border-top:0;padding-top:0;margin-top:8px;">
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-download" style="vertical-align:text-bottom;"></span>
							<?php esc_html_e( 'Export configuration', 'my-account-dashboard-builder' ); ?>
						</button>
					</div>
				</form>
			</div>

			<div class="acfw-card">
				<h2 class="acfw-section-title"><?php esc_html_e( 'Import', 'my-account-dashboard-builder' ); ?></h2>
				<p class="acfw-hint"><?php esc_html_e( 'Upload a JSON file, or paste its contents. This overwrites your current configuration.', 'my-account-dashboard-builder' ); ?></p>
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="import" />
					<div class="acfw-field">
						<label><?php esc_html_e( 'JSON file', 'my-account-dashboard-builder' ); ?></label>
						<input type="file" name="acfw_import_file" accept="application/json,.json" />
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Or paste JSON', 'my-account-dashboard-builder' ); ?></label>
						<textarea name="acfw_import_json" rows="6" placeholder="{ &quot;plugin&quot;: &quot;my-account-dashboard-builder&quot;, … }"></textarea>
					</div>
					<div class="acfw-form-footer">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Import configuration', 'my-account-dashboard-builder' ); ?></button>
					</div>
				</form>
			</div>
			<?php
		}
	}
}
