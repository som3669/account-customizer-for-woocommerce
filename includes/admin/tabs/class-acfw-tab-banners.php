<?php
/**
 * Banners tab.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Tab_Banners' ) ) {

	/**
	 * Renders the Banners builder and handles saving / removing banners.
	 */
	class ACFW_Tab_Banners extends ACFW_Admin_Tab {

		/**
		 * Handle POST actions for the banners tab.
		 *
		 * @param string     $action Sanitized action slug.
		 * @param ACFW_Items $items  Menu items manager.
		 */
		public function handle( $action, $items ) {
			// Nonce is verified in ACFW_Admin::handle_actions() before dispatch.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			switch ( $action ) {

				case 'save_banner':
					$roles       = isset( $_POST['banner_roles'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['banner_roles'] ) ) : array();
					$icon_source = isset( $_POST['banner_icon_source'] ) ? sanitize_key( wp_unslash( $_POST['banner_icon_source'] ) ) : 'choose';
					$bdata       = array(
						'type'          => isset( $_POST['banner_type'] ) ? sanitize_key( wp_unslash( $_POST['banner_type'] ) ) : 'widget',
						'title'         => isset( $_POST['banner_title'] ) ? sanitize_text_field( wp_unslash( $_POST['banner_title'] ) ) : '',
						'content'       => isset( $_POST['banner_content'] ) ? wp_kses_post( wp_unslash( $_POST['banner_content'] ) ) : '',
						'image_url'     => isset( $_POST['banner_image_url'] ) ? esc_url_raw( wp_unslash( $_POST['banner_image_url'] ) ) : '',
						'icon'          => ( 'upload' !== $icon_source && isset( $_POST['banner_icon'] ) ) ? acfw_sanitize_icon( sanitize_text_field( wp_unslash( $_POST['banner_icon'] ) ) ) : '',
						'icon_source'   => 'upload' === $icon_source ? 'upload' : 'choose',
						'icon_url'      => ( 'upload' === $icon_source && isset( $_POST['banner_icon_url'] ) ) ? esc_url_raw( wp_unslash( $_POST['banner_icon_url'] ) ) : '',
						'icon_width'    => isset( $_POST['banner_icon_width'] ) ? absint( wp_unslash( $_POST['banner_icon_width'] ) ) : 40,
						'widget_width'  => isset( $_POST['banner_widget_width'] ) ? absint( wp_unslash( $_POST['banner_widget_width'] ) ) : 250,
						'show_count'    => ! empty( $_POST['banner_show_count'] ) ? 'yes' : 'no',
						'link_type'     => isset( $_POST['banner_link_type'] ) ? sanitize_key( wp_unslash( $_POST['banner_link_type'] ) ) : 'none',
						'link_endpoint' => isset( $_POST['banner_link_endpoint'] ) ? acfw_sanitize_key( sanitize_text_field( wp_unslash( $_POST['banner_link_endpoint'] ) ) ) : '',
						'link'          => isset( $_POST['banner_link'] ) ? esc_url_raw( wp_unslash( $_POST['banner_link'] ) ) : '',
						'roles'         => $roles,
					);
					foreach ( array_keys( ACFW_Banners::color_fields() ) as $ckey ) {
						$bdata[ $ckey ] = isset( $_POST[ 'banner_' . $ckey ] ) ? acfw_sanitize_color( sanitize_text_field( wp_unslash( $_POST[ 'banner_' . $ckey ] ) ) ) : '';
					}
					ACFW_Banners::save(
						isset( $_POST['banner_key'] ) ? sanitize_text_field( wp_unslash( $_POST['banner_key'] ) ) : '',
						$bdata
					);
					break;

				case 'remove_banner':
					$bkey = isset( $_POST['banner_key'] ) ? acfw_sanitize_key( sanitize_text_field( wp_unslash( $_POST['banner_key'] ) ) ) : '';
					if ( '' !== $bkey ) {
						ACFW_Banners::remove( $bkey );
					}
					break;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Render the Banners tab: create form + editable list.
		 */
		public function render() {

			$banners = ACFW_Banners::all();
			?>
			<div class="acfw-builder">
				<div class="acfw-builder-layout">

					<div class="acfw-builder-list-col">
						<div class="acfw-card acfw-builder-list">
							<ul class="acfw-sortable-root acfw-banner-node-list">
								<?php foreach ( $banners as $slug => $banner ) : ?>
									<?php $this->render_banner_row( $slug, wp_parse_args( $banner, ACFW_Banners::defaults() ) ); ?>
								<?php endforeach; ?>
							</ul>
						</div>
						<button type="button" class="button acfw-add-banner-btn acfw-add-banner-below">
							<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add banner', 'my-account-customizer' ); ?>
						</button>
					</div>

					<div class="acfw-card acfw-builder-detail">
						<div class="acfw-detail-empty">
							<span class="dashicons dashicons-arrow-left-alt"></span>
							<p><?php esc_html_e( 'Select a banner on the left to edit it, or click "Add banner" above to create one.', 'my-account-customizer' ); ?></p>
						</div>

						<?php $this->render_banner_form( '', ACFW_Banners::defaults(), true ); ?>

						<?php foreach ( $banners as $slug => $banner ) : ?>
							<?php $this->render_banner_form( $slug, wp_parse_args( $banner, ACFW_Banners::defaults() ), false ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render a single banner row in the left list.
		 *
		 * @param string $slug   Banner slug.
		 * @param array  $banner Banner options.
		 */
		protected function render_banner_row( $slug, $banner ) {
			?>
			<li class="acfw-node" data-key="<?php echo esc_attr( $slug ); ?>">
				<div class="acfw-node-head">
					<?php echo $this->icon_markup( $banner, 'acfw-node-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="acfw-node-title"><?php echo esc_html( $banner['title'] ? $banner['title'] : $slug ); ?></span>
					<span class="acfw-node-badge acfw-badge-<?php echo esc_attr( $banner['type'] ); ?>"><?php echo esc_html( $banner['type'] ); ?></span>
					<span class="acfw-node-spacer"></span>
					<button type="button" class="acfw-banner-row-delete dashicons dashicons-trash" title="<?php esc_attr_e( 'Delete', 'my-account-customizer' ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>"></button>
				</div>
			</li>
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
			$key       = $is_new ? '__new__' : $slug;
			?>
			<div class="acfw-detail" data-key="<?php echo esc_attr( $key ); ?>" hidden>
			<form method="post" class="acfw-banner-form">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="acfw_action" value="save_banner" />
				<?php if ( ! $is_new ) : ?>
					<input type="hidden" name="banner_key" value="<?php echo esc_attr( $slug ); ?>" />
				<?php endif; ?>

				<div class="acfw-detail-head">
					<?php echo $this->icon_markup( $banner, 'acfw-detail-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="acfw-detail-title"><?php echo esc_html( $is_new ? __( 'Add a banner', 'my-account-customizer' ) : ( $banner['title'] ? $banner['title'] : $slug ) ); ?></h2>
					<?php if ( ! $is_new ) : ?>
						<span class="acfw-node-badge acfw-badge-<?php echo esc_attr( $banner['type'] ); ?>"><?php echo esc_html( $banner['type'] ); ?></span>
					<?php endif; ?>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Banner name', 'my-account-customizer' ); ?></label>
					<input type="text" name="banner_title" value="<?php echo esc_attr( $banner['title'] ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Banner type', 'my-account-customizer' ); ?></label>
					<?php
					$this->buttonset(
						'banner_type',
						$banner['type'],
						array(
							'widget' => __( 'Widget', 'my-account-customizer' ),
							'image'  => __( 'Image', 'my-account-customizer' ),
						),
						'widget'
					);
					?>
				</div>

				<div class="acfw-field acfw-btype acfw-btype-widget">
					<label><?php esc_html_e( 'Banner icon', 'my-account-customizer' ); ?></label>
					<?php $b_icon_src = ( 'upload' === ( $banner['icon_source'] ?? 'choose' ) || ! empty( $banner['icon_url'] ) ) ? 'upload' : 'choose'; ?>
					<div class="acfw-icon-source">
						<label class="acfw-radio-card <?php echo 'choose' === $b_icon_src ? 'is-active' : ''; ?>"><input type="radio" name="banner_icon_source" value="choose" <?php checked( $b_icon_src, 'choose' ); ?> /><span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e( 'Choose icon', 'my-account-customizer' ); ?></label>
						<label class="acfw-radio-card <?php echo 'upload' === $b_icon_src ? 'is-active' : ''; ?>"><input type="radio" name="banner_icon_source" value="upload" <?php checked( $b_icon_src, 'upload' ); ?> /><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload icon', 'my-account-customizer' ); ?></label>
					</div>
					<div class="acfw-icon-choose" <?php echo 'choose' === $b_icon_src ? '' : 'hidden'; ?>>
						<select name="banner_icon" class="acfw-icon-select">
							<option value=""><?php esc_html_e( '— Select an icon —', 'my-account-customizer' ); ?></option>
							<?php foreach ( $this->icon_choices() as $ic => $ic_label ) : ?>
								<option value="<?php echo esc_attr( $ic ); ?>" <?php selected( $banner['icon'] ?? '', $ic ); ?>><?php echo esc_html( $ic_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="acfw-icon-upload" <?php echo 'upload' === $b_icon_src ? '' : 'hidden'; ?>>
						<?php $this->uploader( 'banner_icon_url', $banner['icon_url'] ?? '' ); ?>
					</div>
				</div>

				<div class="acfw-field acfw-btype acfw-btype-widget">
					<label><?php esc_html_e( 'Icon width (px)', 'my-account-customizer' ); ?></label>
					<input type="number" name="banner_icon_width" min="16" max="100" value="<?php echo esc_attr( $banner['icon_width'] ?? 40 ); ?>" />
				</div>

				<div class="acfw-field acfw-btype acfw-btype-widget">
					<label><?php esc_html_e( 'Widget width (px)', 'my-account-customizer' ); ?></label>
					<input type="number" name="banner_widget_width" min="200" max="700" value="<?php echo esc_attr( $banner['widget_width'] ?? 250 ); ?>" />
				</div>

				<div class="acfw-field acfw-btype acfw-btype-widget">
					<label><?php esc_html_e( 'Widget text', 'my-account-customizer' ); ?></label>
					<textarea name="banner_content" rows="3"><?php echo esc_textarea( $banner['content'] ); ?></textarea>
				</div>

				<div class="acfw-field acfw-btype acfw-btype-image">
					<label><?php esc_html_e( 'Image', 'my-account-customizer' ); ?></label>
					<?php $this->uploader( 'banner_image_url', $banner['image_url'] ); ?>
				</div>

				<div class="acfw-field acfw-btype acfw-btype-widget">
					<label><?php esc_html_e( 'Banner colors', 'my-account-customizer' ); ?></label>
					<div class="acfw-swatch-row">
						<?php foreach ( ACFW_Banners::color_fields() as $ckey => $clabel ) : ?>
							<?php $this->color_control( 'banner_' . $ckey, $banner[ $ckey ] ?? '', $clabel ); ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="acfw-field acfw-btype acfw-btype-widget">
					<label><?php esc_html_e( 'Show item-count badge', 'my-account-customizer' ); ?></label>
					<label class="acfw-switch acfw-switch-lg"><input type="checkbox" name="banner_show_count" value="yes" <?php checked( 'yes', $banner['show_count'] ?? 'no' ); ?> /><span class="acfw-switch-slider"></span></label>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Banner link', 'my-account-customizer' ); ?></label>
					<?php
					$this->buttonset(
						'banner_link_type',
						$banner['link_type'] ?? 'none',
						array(
							'none'     => __( 'None', 'my-account-customizer' ),
							'endpoint' => __( 'Endpoint', 'my-account-customizer' ),
							'external' => __( 'External URL', 'my-account-customizer' ),
						),
						'none'
					);
					?>
				</div>

				<div class="acfw-field acfw-blink acfw-blink-endpoint">
					<label><?php esc_html_e( 'Link endpoint', 'my-account-customizer' ); ?></label>
					<select name="banner_link_endpoint">
						<option value=""><?php esc_html_e( '— Select —', 'my-account-customizer' ); ?></option>
						<?php foreach ( ACFW()->items->get_items() as $ep_key => $ep ) : ?>
							<?php
							if ( 'endpoint' !== ( $ep['type'] ?? 'endpoint' ) ) {
								continue; }
							?>
							<option value="<?php echo esc_attr( $ep_key ); ?>" <?php selected( $banner['link_endpoint'] ?? '', $ep_key ); ?>><?php echo esc_html( $ep['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="acfw-field acfw-blink acfw-blink-external">
					<label><?php esc_html_e( 'External URL', 'my-account-customizer' ); ?></label>
					<input type="url" name="banner_link" value="<?php echo esc_attr( $banner['link'] ); ?>" placeholder="https://…" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Show banner to', 'my-account-customizer' ); ?></label>
					<select name="banner_roles[]" multiple size="4" class="acfw-roles-select">
						<?php foreach ( $roles as $role_key => $role_label ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( in_array( $role_key, $sel_roles, true ) ); ?>><?php echo esc_html( $role_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="acfw-hint"><?php esc_html_e( 'Leave empty to show to all users.', 'my-account-customizer' ); ?></p>
				</div>

				<div class="acfw-form-footer">
					<?php if ( ! $is_new ) : ?>
						<button type="submit" class="button acfw-banner-delete" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Delete', 'my-account-customizer' ); ?></button>
					<?php endif; ?>
					<button type="submit" class="button button-primary"><?php echo $is_new ? esc_html__( 'Create banner', 'my-account-customizer' ) : esc_html__( 'Save banner', 'my-account-customizer' ); ?></button>
				</div>
			</form>
			</div>
			<?php
		}
	}
}
