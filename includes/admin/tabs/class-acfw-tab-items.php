<?php
/**
 * Menu Items builder tab.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Tab_Items' ) ) {

	/**
	 * Renders the Menu Items builder and handles its save actions.
	 */
	class ACFW_Tab_Items extends ACFW_Admin_Tab {

		/**
		 * Handle POST actions for the menu-items builder.
		 *
		 * @param string     $action Sanitized action slug.
		 * @param ACFW_Items $items  Menu items manager.
		 */
		public function handle( $action, $items ) {
			// Nonce is verified in ACFW_Admin::handle_actions() before dispatch.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
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
						$editor_type = ( isset( $data['editor_type'] ) && 'block' === $data['editor_type'] ) ? 'block' : 'classic';
						// Block markup keeps its `<!-- wp: -->` delimiters ( wp_kses_post would strip them ).
						$raw_content = isset( $data['content'] ) ? (string) $data['content'] : '';
						$content     = ( 'block' === $editor_type && current_user_can( 'unfiltered_html' ) ) ? $raw_content : wp_kses_post( $raw_content );

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
								'content'          => $content,
								'editor_type'      => $editor_type,
								'content_position' => isset( $data['content_position'] ) ? sanitize_key( $data['content_position'] ) : 'before',
								'usr_roles'        => $roles,
								'visibility'       => empty( $roles ) ? 'all' : 'roles',
								'url'              => isset( $data['url'] ) ? esc_url_raw( $data['url'] ) : '',
								'page_id'          => isset( $data['page_id'] ) ? absint( $data['page_id'] ) : 0,
								'target_blank'     => ! empty( $data['target_blank'] ),
								'banner_slugs'     => isset( $data['banner_slugs'] ) && is_array( $data['banner_slugs'] )
									? array_values( array_filter( array_map( 'acfw_sanitize_key', $data['banner_slugs'] ) ) )
									: array(),
								'banner_slug'      => '',
								'banner_position'  => ( isset( $data['banner_position'] ) && 'bottom' === $data['banner_position'] ) ? 'bottom' : 'top',
								'vis_from'         => isset( $data['vis_from'] ) ? preg_replace( '/[^0-9-]/', '', $data['vis_from'] ) : '',
								'vis_to'           => isset( $data['vis_to'] ) ? preg_replace( '/[^0-9-]/', '', $data['vis_to'] ) : '',
								'vis_product'      => isset( $data['vis_product'] ) ? absint( $data['vis_product'] ) : 0,
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
					$key = isset( $_POST['item_key'] ) ? acfw_sanitize_key( sanitize_text_field( wp_unslash( $_POST['item_key'] ) ) ) : '';
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

				case 'duplicate_item':
					$src = isset( $_POST['item_key'] ) ? acfw_sanitize_key( sanitize_text_field( wp_unslash( $_POST['item_key'] ) ) ) : '';
					if ( '' !== $src ) {
						$all    = $items->get_items();
						$source = $all[ $src ] ?? null;
						if ( ! $source ) {
							foreach ( $all as $it ) {
								if ( ! empty( $it['children'][ $src ] ) ) {
									$source = $it['children'][ $src ];
									break;
								}
							}
						}
						if ( $source ) {
							$type   = $source['type'] ?? 'endpoint';
							$newkey = acfw_sanitize_key( $src . '-copy' );
							$n      = 2;
							while ( isset( $all[ $newkey ] ) || false !== get_option( 'acfw_item_' . $newkey, false ) ) {
								$newkey = acfw_sanitize_key( $src . '-copy-' . $n );
								++$n;
							}
							$data          = $source;
							$data['label'] = ( $source['label'] ?? $src ) . ' (copy)';
							$data['slug']  = $newkey;
							unset( $data['children'] );
							$items->save_item( $newkey, $type, $data, false );
							$order = json_decode( get_option( 'acfw_items_order', '[]' ), true );
							$order = is_array( $order ) ? $order : array();
							if ( empty( $order ) ) {
								foreach ( $all as $k => $it ) {
									$order[ $k ] = array( 'type' => $it['type'] ?? 'endpoint' );
								}
							}
							$new_order = array();
							foreach ( $order as $k => $v ) {
								$new_order[ $k ] = $v;
								if ( $k === $src ) {
									$new_order[ $newkey ] = array( 'type' => $type );
								}
							}
							if ( ! isset( $new_order[ $newkey ] ) ) {
								$new_order[ $newkey ] = array( 'type' => $type );
							}
							$items->save_order( $new_order );
						}
					}
					break;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Render the Menu Items builder tab: left selectable list + right options form.
		 */
		public function render() {

			$items = ACFW()->items->get_items();
			?>
			<div class="acfw-builder">

				<div class="acfw-builder-bar">
					<form method="post" class="acfw-add-form" style="display:none;">
						<?php wp_nonce_field( self::NONCE ); ?>
						<input type="hidden" name="acfw_action" value="add_item" />
						<input type="hidden" name="item_type" class="acfw-add-type" value="endpoint" />
						<input type="text" name="item_label" class="acfw-add-label" placeholder="<?php esc_attr_e( 'New item label', 'my-account-dashboard-builder' ); ?>" required />
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Create', 'my-account-dashboard-builder' ); ?></button>
						<button type="button" class="button acfw-add-cancel"><?php esc_html_e( 'Cancel', 'my-account-dashboard-builder' ); ?></button>
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
								<p><?php esc_html_e( 'Select a menu item on the left to edit its options.', 'my-account-dashboard-builder' ); ?></p>
							</div>
							<?php
							$render_details = function ( $list ) use ( &$render_details ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.listFound -- retained from original.
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
							<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save changes', 'my-account-dashboard-builder' ); ?>
						</button>
					</div>
				</form>

				<form method="post" class="acfw-delete-form" style="display:none;">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="remove_item" />
					<input type="hidden" name="item_key" class="acfw-delete-key" value="" />
				</form>

				<form method="post" class="acfw-duplicate-form" style="display:none;">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="duplicate_item" />
					<input type="hidden" name="item_key" class="acfw-duplicate-key" value="" />
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
					<span class="acfw-drag dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag', 'my-account-dashboard-builder' ); ?>"></span>
					<?php echo $this->icon_markup( $item, 'acfw-node-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="acfw-node-title"><?php echo esc_html( $item['label'] ); ?></span>
					<span class="acfw-node-spacer"></span>
					<label class="acfw-switch" title="<?php esc_attr_e( 'Enable / disable', 'my-account-dashboard-builder' ); ?>">
						<input type="checkbox" class="acfw-active-proxy" data-key="<?php echo esc_attr( $key ); ?>" <?php checked( $active ); ?> />
						<span class="acfw-switch-slider"></span>
					</label>
					<button type="button" class="acfw-node-duplicate dashicons dashicons-admin-page" title="<?php esc_attr_e( 'Duplicate', 'my-account-dashboard-builder' ); ?>" data-key="<?php echo esc_attr( $key ); ?>"></button>
					<?php if ( $is_default ) : ?>
						<button type="button" class="acfw-node-remove is-disabled dashicons dashicons-trash" title="<?php esc_attr_e( 'Default items cannot be deleted', 'my-account-dashboard-builder' ); ?>" disabled aria-disabled="true"></button>
					<?php else : ?>
						<button type="button" class="acfw-node-remove dashicons dashicons-trash" title="<?php esc_attr_e( 'Delete', 'my-account-dashboard-builder' ); ?>" data-key="<?php echo esc_attr( $key ); ?>"></button>
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
			$active      = ! empty( $item['active'] );
			$icon_source = ( ! empty( $item['icon_url'] ) || ( isset( $item['icon_source'] ) && 'upload' === $item['icon_source'] ) ) ? 'upload' : 'choose';
			$sel_roles   = (array) ( $item['usr_roles'] ?? array() );
			$type_labels = array(
				'endpoint' => __( 'Endpoint', 'my-account-dashboard-builder' ),
				'group'    => __( 'Group', 'my-account-dashboard-builder' ),
				'link'     => __( 'Link', 'my-account-dashboard-builder' ),
				'page'     => __( 'Page', 'my-account-dashboard-builder' ),
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
					<label><?php esc_html_e( 'Endpoint label', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'The menu label shown to customers.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input type="text" name="items[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $item['label'] ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Endpoint icon', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'Choose a FontAwesome icon or upload your own image.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<div class="acfw-icon-source">
						<label class="acfw-radio-card <?php echo 'choose' === $icon_source ? 'is-active' : ''; ?>">
							<input type="radio" name="items[<?php echo esc_attr( $key ); ?>][icon_source]" value="choose" <?php checked( $icon_source, 'choose' ); ?> />
							<span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e( 'Choose icon', 'my-account-dashboard-builder' ); ?>
						</label>
						<label class="acfw-radio-card <?php echo 'upload' === $icon_source ? 'is-active' : ''; ?>">
							<input type="radio" name="items[<?php echo esc_attr( $key ); ?>][icon_source]" value="upload" <?php checked( $icon_source, 'upload' ); ?> />
							<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload icon', 'my-account-dashboard-builder' ); ?>
						</label>
					</div>

				</div>

				<div class="acfw-field acfw-icon-choose" <?php echo 'choose' === $icon_source ? '' : 'hidden'; ?>>
					<label><?php esc_html_e( 'Select an icon', 'my-account-dashboard-builder' ); ?></label>
					<select name="items[<?php echo esc_attr( $key ); ?>][icon]" class="acfw-icon-select">
						<option value=""><?php esc_html_e( '— Select an icon —', 'my-account-dashboard-builder' ); ?></option>
						<?php foreach ( $this->icon_choices() as $ic => $label ) : ?>
							<option value="<?php echo esc_attr( $ic ); ?>" <?php selected( $item['icon'] ?? '', $ic ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="acfw-field acfw-icon-upload" <?php echo 'upload' === $icon_source ? '' : 'hidden'; ?>>
					<label><?php esc_html_e( 'Upload an image', 'my-account-dashboard-builder' ); ?></label>
					<?php $this->uploader( 'items[' . $key . '][icon_url]', $item['icon_url'] ?? '' ); ?>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'CSS class', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'Optional extra CSS class for this menu item.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input type="text" name="items[<?php echo esc_attr( $key ); ?>][class]" value="<?php echo esc_attr( $item['class'] ?? '' ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'User roles', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'Show only to selected roles. Empty = everyone.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<select name="items[<?php echo esc_attr( $key ); ?>][usr_roles][]" multiple size="4" class="acfw-roles-select">
						<?php foreach ( $roles as $role_key => $role_label ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( in_array( $role_key, $sel_roles, true ) ); ?>><?php echo esc_html( $role_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="acfw-hint"><?php esc_html_e( 'Leave empty to show for everyone.', 'my-account-dashboard-builder' ); ?></p>
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Show from', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'Only show this item on or after this date.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input type="date" name="items[<?php echo esc_attr( $key ); ?>][vis_from]" value="<?php echo esc_attr( $item['vis_from'] ?? '' ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Show until', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'Only show this item up to this date.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input type="date" name="items[<?php echo esc_attr( $key ); ?>][vis_to]" value="<?php echo esc_attr( $item['vis_to'] ?? '' ); ?>" />
				</div>

				<div class="acfw-field">
					<label><?php esc_html_e( 'Purchased product', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'Only show to customers who bought this product ID.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input type="number" name="items[<?php echo esc_attr( $key ); ?>][vis_product]" min="0" value="<?php echo esc_attr( $item['vis_product'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Product ID', 'my-account-dashboard-builder' ); ?>" />
				</div>

				<?php if ( 'link' === $type ) : ?>
					<div class="acfw-field">
						<label><?php esc_html_e( 'URL', 'my-account-dashboard-builder' ); ?></label>
						<input type="url" name="items[<?php echo esc_attr( $key ); ?>][url]" value="<?php echo esc_attr( $item['url'] ?? '' ); ?>" />
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Open in new tab', 'my-account-dashboard-builder' ); ?></label>
						<label class="acfw-switch acfw-switch-lg"><input type="checkbox" name="items[<?php echo esc_attr( $key ); ?>][target_blank]" value="1" <?php checked( ! empty( $item['target_blank'] ) ); ?> /><span class="acfw-switch-slider"></span></label>
					</div>
				<?php elseif ( 'page' === $type ) : ?>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Page', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'Link this menu item to an existing WordPress page.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'items[' . esc_attr( $key ) . '][page_id]',
								'selected'          => (int) ( $item['page_id'] ?? 0 ),
								'show_option_none'  => esc_html__( '— Select a page —', 'my-account-dashboard-builder' ),
								'option_none_value' => 0,
							)
						);
						?>
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Open in new tab', 'my-account-dashboard-builder' ); ?></label>
						<label class="acfw-switch acfw-switch-lg"><input type="checkbox" name="items[<?php echo esc_attr( $key ); ?>][target_blank]" value="1" <?php checked( ! empty( $item['target_blank'] ) ); ?> /><span class="acfw-switch-slider"></span></label>
					</div>
				<?php elseif ( 'endpoint' === $type ) : ?>
					<?php
					$ukey        = str_replace( '-', '_', $key );
					$eid         = 'acfw_content_' . $ukey;
					$editor_type = ( isset( $item['editor_type'] ) && 'block' === $item['editor_type'] ) ? 'block' : 'classic';
					$is_block    = 'block' === $editor_type;
					?>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Content editor', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'Edit custom content with the Classic editor or the Block ( Gutenberg ) editor.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
						<div class="acfw-radio-group acfw-editor-type-group" role="radiogroup">
							<label class="acfw-radio-box acfw_choose_icon_type_inner_wrapper <?php echo ! $is_block ? 'is-active active' : ''; ?>">
								<input type="radio" class="acfw_editor_type_radio" name="items[<?php echo esc_attr( $key ); ?>][editor_type]" value="classic" data-endpoint="<?php echo esc_attr( $ukey ); ?>" <?php checked( $editor_type, 'classic' ); ?> />
								<span class="acfw-radio-dot"></span><span class="acfw-radio-text"><?php esc_html_e( 'Classic', 'my-account-dashboard-builder' ); ?></span>
							</label>
							<label class="acfw-radio-box acfw_choose_icon_type_inner_wrapper <?php echo $is_block ? 'is-active active' : ''; ?>">
								<input type="radio" class="acfw_editor_type_radio" name="items[<?php echo esc_attr( $key ); ?>][editor_type]" value="block" data-endpoint="<?php echo esc_attr( $ukey ); ?>" <?php checked( $editor_type, 'block' ); ?> />
								<span class="acfw-radio-dot"></span><span class="acfw-radio-text"><?php esc_html_e( 'Block', 'my-account-dashboard-builder' ); ?></span>
							</label>
						</div>
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Custom content', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'Extra content added to this endpoint. Use Add media and smart tags.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
						<div class="acfw-content-wrap">
							<div class="acfw_classic_editor_wrapper <?php echo $is_block ? 'acfw_hidden' : ''; ?>" data-endpoint="<?php echo esc_attr( $ukey ); ?>">
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
							<div class="acfw_block_editor_wrapper <?php echo $is_block ? '' : 'acfw_hidden'; ?>" data-endpoint="<?php echo esc_attr( $ukey ); ?>">
								<textarea id="acfw_block_content_<?php echo esc_attr( $ukey ); ?>" name="items[<?php echo esc_attr( $key ); ?>][content]" class="acfw_block_editor_input" style="display:none;" <?php disabled( $is_block, false ); ?>><?php echo esc_textarea( $item['content'] ?? '' ); ?></textarea>
								<div class="acfw_block_editor" data-endpoint="<?php echo esc_attr( $ukey ); ?>" data-textarea="acfw_block_content_<?php echo esc_attr( $ukey ); ?>" data-autoinit="<?php echo $is_block ? '1' : '0'; ?>"></div>
							</div>
						</div>
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Custom content position', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'Where the custom content appears relative to the default content.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
						<select name="items[<?php echo esc_attr( $key ); ?>][content_position]">
							<?php $cp = $item['content_position'] ?? 'before'; ?>
							<option value="before" <?php selected( $cp, 'before' ); ?>><?php esc_html_e( 'Before default content', 'my-account-dashboard-builder' ); ?></option>
							<option value="after" <?php selected( $cp, 'after' ); ?>><?php esc_html_e( 'After default content', 'my-account-dashboard-builder' ); ?></option>
							<option value="override" <?php selected( $cp, 'override' ); ?>><?php esc_html_e( 'Replace default content', 'my-account-dashboard-builder' ); ?></option>
						</select>
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Banners', 'my-account-dashboard-builder' ); ?><?php echo $this->tip( __( 'Show one or more banners on this endpoint. They render in the order picked.', 'my-account-dashboard-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
						<?php $acfw_selected_banners = acfw_item_banner_slugs( $item ); ?>
						<select name="items[<?php echo esc_attr( $key ); ?>][banner_slugs][]" class="acfw-banner-select" multiple data-placeholder="<?php esc_attr_e( 'No banners', 'my-account-dashboard-builder' ); ?>">
							<?php foreach ( ACFW_Banners::all() as $b_slug => $b ) : ?>
								<option value="<?php echo esc_attr( $b_slug ); ?>" <?php selected( in_array( $b_slug, $acfw_selected_banners, true ) ); ?>><?php echo esc_html( $b['title'] ? $b['title'] : $b_slug ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="acfw-field">
						<label><?php esc_html_e( 'Banner position', 'my-account-dashboard-builder' ); ?></label>
						<?php
						$this->buttonset(
							'banner_position',
							$item['banner_position'] ?? 'top',
							array(
								'top'    => __( 'Top', 'my-account-dashboard-builder' ),
								'bottom' => __( 'Bottom', 'my-account-dashboard-builder' ),
							),
							'top'
						);
						?>
					</div>
				<?php endif; ?>

			</div>
			<?php
		}
	}
}
