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
						'count_source'  => isset( $_POST['banner_count_source'] ) ? sanitize_key( wp_unslash( $_POST['banner_count_source'] ) ) : 'orders',
						'link_type'     => isset( $_POST['banner_link_type'] ) ? sanitize_key( wp_unslash( $_POST['banner_link_type'] ) ) : 'none',
						'link_endpoint' => isset( $_POST['banner_link_endpoint'] ) ? acfw_sanitize_key( sanitize_title( wp_unslash( $_POST['banner_link_endpoint'] ) ) ) : '',
						'link'          => isset( $_POST['banner_link'] ) ? esc_url_raw( wp_unslash( $_POST['banner_link'] ) ) : '',
						'link_text'     => isset( $_POST['banner_link_text'] ) ? sanitize_text_field( wp_unslash( $_POST['banner_link_text'] ) ) : '',
						'roles'         => $roles,
						'vis_from'      => isset( $_POST['banner_vis_from'] ) ? sanitize_text_field( wp_unslash( $_POST['banner_vis_from'] ) ) : '',
						'vis_to'        => isset( $_POST['banner_vis_to'] ) ? sanitize_text_field( wp_unslash( $_POST['banner_vis_to'] ) ) : '',
					);
					foreach ( array_keys( ACFW_Banners::color_fields() ) as $ckey ) {
						$bdata[ $ckey ] = isset( $_POST[ 'banner_' . $ckey ] ) ? acfw_sanitize_color( sanitize_text_field( wp_unslash( $_POST[ 'banner_' . $ckey ] ) ) ) : '';
					}
					$saved = ACFW_Banners::save(
						isset( $_POST['banner_key'] ) ? sanitize_title( wp_unslash( $_POST['banner_key'] ) ) : '',
						$bdata
					);
					if ( '' !== $saved ) {
						$this->redirect_args['select'] = $saved;
					} else {
						$this->add_notice( __( 'Give the banner a name before saving it.', 'my-account-dashboard-builder' ), 'error' );
					}
					break;

				case 'remove_banner':
					$bkey = isset( $_POST['banner_key'] ) ? acfw_sanitize_key( sanitize_title( wp_unslash( $_POST['banner_key'] ) ) ) : '';
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

			$banners  = ACFW_Banners::all();
			$is_empty = empty( $banners );
			?>
			<?php if ( $is_empty ) : ?>
				<div class="acfw-card acfw-empty-state">
					<span class="acfw-empty-icon dashicons dashicons-archive" aria-hidden="true"></span>
					<h2 class="acfw-empty-title"><?php esc_html_e( 'No banners yet', 'my-account-dashboard-builder' ); ?></h2>
					<p class="acfw-empty-text"><?php esc_html_e( 'Create a banner to show a widget or image on your My Account page.', 'my-account-dashboard-builder' ); ?></p>
					<button type="button" class="acfw-empty-add acfw-add-banner-btn">
						<span class="dashicons dashicons-plus-alt"></span>
						<?php esc_html_e( 'Add banner', 'my-account-dashboard-builder' ); ?>
					</button>
				</div>
			<?php endif; ?>
			<div class="acfw-builder"<?php echo $is_empty ? ' hidden' : ''; ?>>
				<div class="acfw-builder-layout">

					<div class="acfw-builder-list-col">
						<?php // With no banners the list card would render as an empty white box. ?>
						<div class="acfw-card acfw-builder-list<?php echo $is_empty ? ' acfw-is-empty' : ''; ?>">
							<ul class="acfw-sortable-root acfw-banner-node-list">
								<?php foreach ( $banners as $slug => $banner ) : ?>
									<?php $this->render_banner_row( $slug, wp_parse_args( $banner, ACFW_Banners::defaults() ) ); ?>
								<?php endforeach; ?>
							</ul>
						</div>
						<button type="button" class="button acfw-add-banner-btn acfw-add-banner-below">
							<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add banner', 'my-account-dashboard-builder' ); ?>
						</button>

						<div class="acfw-card acfw-banner-preview-card">
							<h3 class="acfw-preview-title"><?php esc_html_e( 'Banner Preview', 'my-account-dashboard-builder' ); ?></h3>
							<div class="acfw-preview-stage">
								<span class="acfw-preview-arrow is-prev dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
								<div class="acfw-preview-frame" aria-hidden="true">
									<span class="acfw-preview-dots">
										<i></i><i></i><i></i>
									</span>
									<span class="acfw-preview-image">
										<span class="acfw-preview-sun"></span>
										<span class="acfw-preview-hill"></span>
									</span>
								</div>
								<span class="acfw-preview-arrow is-next dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
							</div>
							<span class="acfw-preview-pager" aria-hidden="true"><i class="is-on"></i><i></i><i></i></span>
							<p class="acfw-preview-note"><?php esc_html_e( 'Your banner will appear here when added.', 'my-account-dashboard-builder' ); ?></p>
						</div>
					</div>

					<div class="acfw-card acfw-builder-detail">
						<div class="acfw-detail-empty">
							<span class="dashicons dashicons-arrow-left-alt"></span>
							<p><?php esc_html_e( 'Select a banner on the left to edit it, or click "Add banner" above to create one.', 'my-account-dashboard-builder' ); ?></p>
						</div>

						<?php $this->render_banner_form( '', ACFW_Banners::defaults(), true ); ?>

						<?php foreach ( $banners as $slug => $banner ) : ?>
							<?php $this->render_banner_form( $slug, wp_parse_args( $banner, ACFW_Banners::defaults() ), false ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<?php // Name prompt shown before the full banner form opens. ?>
			<div class="acfw-modal-overlay" id="acfw-add-banner-modal" hidden>
				<div class="acfw-modal" role="dialog" aria-modal="true" aria-labelledby="acfw-add-banner-title">
					<div class="acfw-modal-head">
						<h2 id="acfw-add-banner-title"><?php esc_html_e( 'Add Banner', 'my-account-dashboard-builder' ); ?></h2>
						<button type="button" class="acfw-modal-close" aria-label="<?php esc_attr_e( 'Close', 'my-account-dashboard-builder' ); ?>">&times;</button>
					</div>
					<div class="acfw-modal-body">
						<label for="acfw-new-banner-name"><?php esc_html_e( 'Name', 'my-account-dashboard-builder' ); ?></label>
						<input type="text" id="acfw-new-banner-name" class="acfw-modal-input" autocomplete="off" />
						<p class="acfw-modal-error" role="alert" hidden><?php esc_html_e( 'Give the banner a name first.', 'my-account-dashboard-builder' ); ?></p>
					</div>
					<div class="acfw-modal-foot">
						<button type="button" class="button acfw-modal-cancel"><?php esc_html_e( 'Cancel', 'my-account-dashboard-builder' ); ?></button>
						<button type="button" class="button button-primary acfw-modal-confirm"><?php esc_html_e( 'Add Banner', 'my-account-dashboard-builder' ); ?></button>
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
					<button type="button" class="acfw-banner-row-delete dashicons dashicons-trash" title="<?php esc_attr_e( 'Delete', 'my-account-dashboard-builder' ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>"></button>
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
			$uid       = 'acfw-b' . substr( md5( (string) $key ), 0, 8 );
			$rules     = ( $sel_roles ? 1 : 0 ) + ( ( ! empty( $banner['vis_from'] ) || ! empty( $banner['vis_to'] ) ) ? 1 : 0 );
			$sections  = array(
				'general'    => array( __( 'General', 'my-account-dashboard-builder' ), 'admin-settings' ),
				'style'      => array( __( 'Style', 'my-account-dashboard-builder' ), 'art' ),
				'link'       => array( __( 'Link', 'my-account-dashboard-builder' ), 'admin-links' ),
				'visibility' => array( __( 'Visibility', 'my-account-dashboard-builder' ), 'visibility' ),
			);
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
					<h2 class="acfw-detail-title"><?php echo esc_html( $is_new ? __( 'Add a banner', 'my-account-dashboard-builder' ) : ( $banner['title'] ? $banner['title'] : $slug ) ); ?></h2>
					<?php if ( ! $is_new ) : ?>
						<span class="acfw-node-badge acfw-badge-<?php echo esc_attr( $banner['type'] ); ?>"><?php echo esc_html( $banner['type'] ); ?></span>
					<?php endif; ?>
				</div>

				<?php $this->section_tabs( $uid, $sections, __( 'Banner settings', 'my-account-dashboard-builder' ), array( 'visibility' => $rules ) ); ?>

				<?php // ---- General ---- ?>
				<section class="acfw-section is-active" id="<?php echo esc_attr( $uid . '-general' ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid . '-tab-general' ); ?>" data-section="general">
					<div class="acfw-field">
						<label><?php esc_html_e( 'Banner name', 'my-account-dashboard-builder' ); ?></label>
						<input type="text" name="banner_title" value="<?php echo esc_attr( $banner['title'] ); ?>" />
						<p class="acfw-hint"><?php esc_html_e( 'Enter a unique name for this banner.', 'my-account-dashboard-builder' ); ?></p>
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Banner type', 'my-account-dashboard-builder' ); ?></label>
						<?php
						$this->buttonset(
							'banner_type',
							$banner['type'],
							array(
								'widget' => __( 'Widget', 'my-account-dashboard-builder' ),
								'image'  => __( 'Image', 'my-account-dashboard-builder' ),
							),
							'widget',
							array(
								'widget' => 'widget',
								'image'  => 'image',
							)
						);
						?>
					</div>
					<div class="acfw-field acfw-btype acfw-btype-widget">
						<label><?php esc_html_e( 'Banner icon', 'my-account-dashboard-builder' ); ?></label>
						<?php $b_icon_src = ( 'upload' === ( $banner['icon_source'] ?? 'choose' ) || ! empty( $banner['icon_url'] ) ) ? 'upload' : 'choose'; ?>
						<div class="acfw-icon-source">
							<label class="acfw-radio-card <?php echo 'choose' === $b_icon_src ? 'is-active' : ''; ?>"><input type="radio" name="banner_icon_source" value="choose" <?php checked( $b_icon_src, 'choose' ); ?> /><?php echo wp_kses( acfw_ui_icon( 'widget' ), acfw_svg_kses() ); ?> <?php esc_html_e( 'Choose icon', 'my-account-dashboard-builder' ); ?></label>
							<label class="acfw-radio-card <?php echo 'upload' === $b_icon_src ? 'is-active' : ''; ?>"><input type="radio" name="banner_icon_source" value="upload" <?php checked( $b_icon_src, 'upload' ); ?> /><?php echo wp_kses( acfw_ui_icon( 'upload' ), acfw_svg_kses() ); ?> <?php esc_html_e( 'Upload icon', 'my-account-dashboard-builder' ); ?></label>
						</div>
						<div class="acfw-icon-choose" <?php echo 'choose' === $b_icon_src ? '' : 'hidden'; ?>>
							<?php $this->icon_picker( 'banner_icon', $banner['icon'] ?? '' ); ?>
						</div>
						<div class="acfw-icon-upload" <?php echo 'upload' === $b_icon_src ? '' : 'hidden'; ?>>
							<?php $this->uploader( 'banner_icon_url', $banner['icon_url'] ?? '' ); ?>
						</div>
					</div>
					<div class="acfw-field acfw-btype acfw-btype-widget">
						<label><?php esc_html_e( 'Icon width (px)', 'my-account-dashboard-builder' ); ?></label>
						<input type="number" name="banner_icon_width" min="16" max="100" value="<?php echo esc_attr( $banner['icon_width'] ?? 40 ); ?>" />
						<p class="acfw-hint"><?php esc_html_e( 'Set the icon width in pixels.', 'my-account-dashboard-builder' ); ?></p>
					</div>
					<div class="acfw-field acfw-btype acfw-btype-widget">
						<label><?php esc_html_e( 'Widget text', 'my-account-dashboard-builder' ); ?></label>
						<textarea name="banner_content" rows="3"><?php echo esc_textarea( $banner['content'] ); ?></textarea>
						<p class="acfw-hint"><?php esc_html_e( 'Optional text to display in the banner widget.', 'my-account-dashboard-builder' ); ?></p>
					</div>
					<div class="acfw-field acfw-btype acfw-btype-image">
						<label><?php esc_html_e( 'Image', 'my-account-dashboard-builder' ); ?></label>
						<?php $this->uploader( 'banner_image_url', $banner['image_url'] ); ?>
					</div>
				</section>

				<?php // ---- Style ---- ?>
				<section class="acfw-section" id="<?php echo esc_attr( $uid . '-style' ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid . '-tab-style' ); ?>" data-section="style">
					<div class="acfw-field acfw-btype acfw-btype-widget">
						<label><?php esc_html_e( 'Widget width (px)', 'my-account-dashboard-builder' ); ?></label>
						<input type="number" name="banner_widget_width" min="200" max="700" value="<?php echo esc_attr( $banner['widget_width'] ?? 250 ); ?>" />
						<p class="acfw-hint"><?php esc_html_e( 'Set the widget width in pixels.', 'my-account-dashboard-builder' ); ?></p>
					</div>
					<div class="acfw-field acfw-btype acfw-btype-widget">
						<label><?php esc_html_e( 'Banner colors', 'my-account-dashboard-builder' ); ?></label>
						<div class="acfw-swatch-row">
							<?php foreach ( ACFW_Banners::color_fields() as $ckey => $clabel ) : ?>
								<?php $this->color_control( 'banner_' . $ckey, $banner[ $ckey ] ?? '', $clabel ); ?>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="acfw-field acfw-btype acfw-btype-widget">
						<label><?php esc_html_e( 'Show item-count badge', 'my-account-dashboard-builder' ); ?></label>
						<label class="acfw-switch acfw-switch-lg"><input type="checkbox" name="banner_show_count" class="acfw-banner-count-toggle" value="yes" <?php checked( 'yes', $banner['show_count'] ?? 'no' ); ?> /><span class="acfw-switch-slider"></span></label>
					</div>
					<div class="acfw-field acfw-btype acfw-btype-widget acfw-bcount">
						<label><?php esc_html_e( 'Badge shows', 'my-account-dashboard-builder' ); ?></label>
						<select name="banner_count_source">
							<?php foreach ( ACFW_Banners::count_sources() as $acfw_source => $acfw_source_label ) : ?>
								<option value="<?php echo esc_attr( $acfw_source ); ?>" <?php selected( $banner['count_source'] ?? 'orders', $acfw_source ); ?>><?php echo esc_html( $acfw_source_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="acfw-hint"><?php esc_html_e( 'The number shown in the pill beside the banner title.', 'my-account-dashboard-builder' ); ?></p>
					</div>
				</section>

				<?php // ---- Link ---- ?>
				<section class="acfw-section" id="<?php echo esc_attr( $uid . '-link' ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid . '-tab-link' ); ?>" data-section="link">
					<div class="acfw-field">
						<label><?php esc_html_e( 'Banner link', 'my-account-dashboard-builder' ); ?></label>
						<?php
						$this->buttonset(
							'banner_link_type',
							$banner['link_type'] ?? 'none',
							array(
								'none'     => __( 'None', 'my-account-dashboard-builder' ),
								'endpoint' => __( 'Endpoint', 'my-account-dashboard-builder' ),
								'external' => __( 'External URL', 'my-account-dashboard-builder' ),
							),
							'none'
						);
						?>
					</div>
					<div class="acfw-field acfw-blink acfw-blink-endpoint">
						<label><?php esc_html_e( 'Link endpoint', 'my-account-dashboard-builder' ); ?></label>
						<select name="banner_link_endpoint">
							<option value=""><?php esc_html_e( '— Select —', 'my-account-dashboard-builder' ); ?></option>
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
						<label><?php esc_html_e( 'External URL', 'my-account-dashboard-builder' ); ?></label>
						<input type="url" name="banner_link" value="<?php echo esc_attr( $banner['link'] ); ?>" placeholder="https://…" />
					</div>
					<div class="acfw-field acfw-btype acfw-btype-widget acfw-blink-text">
						<label><?php esc_html_e( 'Link text', 'my-account-dashboard-builder' ); ?></label>
						<input type="text" name="banner_link_text" value="<?php echo esc_attr( $banner['link_text'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Learn more', 'my-account-dashboard-builder' ); ?>" />
					</div>
				</section>

				<?php // ---- Visibility ---- ?>
				<section class="acfw-section" id="<?php echo esc_attr( $uid . '-visibility' ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid . '-tab-visibility' ); ?>" data-section="visibility">
					<div class="acfw-field">
						<label><?php esc_html_e( 'Show banner to', 'my-account-dashboard-builder' ); ?></label>
						<select name="banner_roles[]" multiple size="4" class="acfw-roles-select acfw-rule-input">
							<?php foreach ( $roles as $role_key => $role_label ) : ?>
								<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( in_array( $role_key, $sel_roles, true ) ); ?>><?php echo esc_html( $role_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="acfw-hint"><?php esc_html_e( 'Leave empty to show to all users.', 'my-account-dashboard-builder' ); ?></p>
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Show from', 'my-account-dashboard-builder' ); ?></label>
						<input type="date" class="acfw-rule-input" name="banner_vis_from" value="<?php echo esc_attr( $banner['vis_from'] ?? '' ); ?>" />
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Show until', 'my-account-dashboard-builder' ); ?></label>
						<input type="date" class="acfw-rule-input" name="banner_vis_to" value="<?php echo esc_attr( $banner['vis_to'] ?? '' ); ?>" />
						<p class="acfw-hint"><?php esc_html_e( 'Whole days, in the site timezone. Leave both empty to always show it.', 'my-account-dashboard-builder' ); ?></p>
					</div>
				</section>

				<div class="acfw-form-footer">
					<?php if ( ! $is_new ) : ?>
						<button type="submit" class="button acfw-banner-delete" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Delete', 'my-account-dashboard-builder' ); ?></button>
					<?php endif; ?>
					<button type="submit" class="button button-primary acfw-banner-submit">
						<span class="dashicons dashicons-<?php echo $is_new ? 'plus-alt2' : 'yes'; ?>" aria-hidden="true"></span>
						<?php echo $is_new ? esc_html__( 'Create banner', 'my-account-dashboard-builder' ) : esc_html__( 'Save banner', 'my-account-dashboard-builder' ); ?>
					</button>
				</div>
			</form>
			</div>
			<?php
		}
	}
}
