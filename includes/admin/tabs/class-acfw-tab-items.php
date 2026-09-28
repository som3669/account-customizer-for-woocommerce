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
					$type  = in_array( $type, ACFW_Items::ITEM_TYPES, true ) ? $type : 'endpoint';
					$label = isset( $_POST['item_label'] ) ? sanitize_text_field( wp_unslash( $_POST['item_label'] ) ) : '';
					if ( '' !== $label ) {
						// Never reuse a key: "Orders" must not overwrite the Orders endpoint.
						$key = acfw_unique_item_key( $label, $type, $items->used_names() );
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
						$this->redirect_args['select'] = $key;
					}
					break;

				case 'save_all':
					$items_in = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each field sanitized below.
					$all_keys = array_map( 'acfw_sanitize_key', array_map( 'strval', array_keys( $items_in ) ) );
					$assigned = array();
					foreach ( $items_in as $raw_key => $data ) {
						$key = acfw_sanitize_key( $raw_key );
						if ( '' === $key || ! is_array( $data ) ) {
							continue;
						}
						$type = isset( $data['type'] ) ? sanitize_key( $data['type'] ) : 'endpoint';
						$slug = '';
						if ( 'endpoint' === $type && ! $items->is_default( $key ) ) {
							$slug       = $this->resolve_slug( $key, $data, $all_keys, $assigned, $items );
							$assigned[] = $slug;
						}
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
								'open'             => ! empty( $data['open'] ),
								'banner_slugs'     => isset( $data['banner_slugs'] ) && is_array( $data['banner_slugs'] )
									? array_values( array_filter( array_map( 'acfw_sanitize_key', $data['banner_slugs'] ) ) )
									: array(),
								'banner_slug'      => '',
								'banner_position'  => ( isset( $data['banner_position'] ) && 'bottom' === $data['banner_position'] ) ? 'bottom' : 'top',
								'slug'             => $slug,
								'badge'            => isset( $data['badge'] ) ? $this->clip( sanitize_text_field( $data['badge'] ), 40 ) : '',
								'vis_from'         => isset( $data['vis_from'] ) ? preg_replace( '/[^0-9-]/', '', $data['vis_from'] ) : '',
								'vis_to'           => isset( $data['vis_to'] ) ? preg_replace( '/[^0-9-]/', '', $data['vis_to'] ) : '',
								// The single-product field became a product list; older data is folded in on read.
								'vis_product'      => 0,
								'vis_products'     => acfw_parse_id_list( $data['vis_products'] ?? array() ),
								'vis_min_orders'   => isset( $data['vis_min_orders'] ) ? absint( $data['vis_min_orders'] ) : 0,
								'vis_min_spent'    => isset( $data['vis_min_spent'] ) ? $this->money( $data['vis_min_spent'] ) : 0,
							),
							false
						);
					}

					// The tree is decoded as-is, then sanitized node by node. A text
					// sanitizer on the raw JSON would strip "%xx" octets out of keys.
					$raw   = isset( $_POST['acfw_order'] ) ? wp_unslash( $_POST['acfw_order'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- decoded, then sanitized by acfw_sanitize_order_tree() in save_order().
					$order = is_string( $raw ) ? json_decode( $raw, true ) : null;
					if ( is_array( $order ) && ! empty( $order ) ) {
						$items->save_order( $order );
					} else {
						$items->build( true );
					}

					$selected = isset( $_POST['acfw_selected'] ) ? acfw_sanitize_key( sanitize_title( wp_unslash( $_POST['acfw_selected'] ) ) ) : '';
					if ( '' !== $selected ) {
						$this->redirect_args['select'] = $selected;
					}
					break;

				case 'remove_item':
					$key = isset( $_POST['item_key'] ) ? acfw_sanitize_key( sanitize_title( wp_unslash( $_POST['item_key'] ) ) ) : '';
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
					$raw   = isset( $_POST['acfw_order'] ) ? wp_unslash( $_POST['acfw_order'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- decoded, then sanitized by acfw_sanitize_order_tree() in save_order().
					$order = is_string( $raw ) ? json_decode( $raw, true ) : null;
					if ( is_array( $order ) ) {
						$items->save_order( $order );
					}
					break;

				case 'duplicate_item':
					$src = isset( $_POST['item_key'] ) ? acfw_sanitize_key( sanitize_title( wp_unslash( $_POST['item_key'] ) ) ) : '';
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
							$type          = $source['type'] ?? 'endpoint';
							$newkey        = acfw_unique_item_key( $src . '-copy', $type, $items->used_names() );
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
							$this->redirect_args['select'] = $newkey;
						}
					}
					break;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Work out the URL slug a custom endpoint may use.
		 *
		 * A slug is a WooCommerce query var and a rewrite endpoint, so it must
		 * not match WooCommerce's own endpoints, a WordPress query var, or any
		 * other item's key or slug. A slug that is taken is refused with a
		 * notice, and the endpoint keeps the URL it had.
		 *
		 * @param string     $key      Item key.
		 * @param array      $data     Posted item fields.
		 * @param array      $all_keys Every item key in this save.
		 * @param array      $assigned Slugs already given out in this save.
		 * @param ACFW_Items $items    Menu items manager.
		 * @return string
		 */
		protected function resolve_slug( $key, $data, $all_keys, $assigned, $items ) {
			$flat    = acfw_flatten_items( $items->get_items() );
			$current = ! empty( $flat[ $key ]['slug'] ) ? (string) $flat[ $key ]['slug'] : $key;
			$wanted  = isset( $data['slug'] ) ? acfw_ascii_slug( $data['slug'] ) : '';

			if ( '' === $wanted || $wanted === $current ) {
				return $current;
			}

			$taken = array_merge( acfw_reserved_item_keys(), $items->used_names( $key ), $all_keys, $assigned );
			$taken = array_diff( $taken, array( $key, $current ) );

			if ( $wanted !== $key && in_array( $wanted, $taken, true ) ) {
				$this->add_notice(
					sprintf(
						/* translators: 1: requested URL slug, 2: menu item label, 3: the slug it keeps. */
						__( 'The URL "%1$s" is already used, so "%2$s" keeps "%3$s".', 'my-account-dashboard-builder' ),
						$wanted,
						isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : $key,
						$current
					)
				);
				return $current;
			}

			return $wanted;
		}

		/**
		 * Trim a string to a maximum number of characters.
		 *
		 * @param string $value Value.
		 * @param int    $max   Maximum length.
		 * @return string
		 */
		protected function clip( $value, $max ) {
			$value = (string) $value;
			return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
		}

		/**
		 * Parse a money amount typed in the store's decimal format.
		 *
		 * @param mixed $raw Raw value.
		 * @return float Zero or more.
		 */
		protected function money( $raw ) {
			$raw = sanitize_text_field( (string) $raw );
			$raw = function_exists( 'wc_format_decimal' ) ? wc_format_decimal( $raw ) : $raw;
			return max( 0, (float) $raw );
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

				<form method="post" class="acfw-items-form" novalidate>
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="save_all" />
					<input type="hidden" name="acfw_order" class="acfw-order-input" value="" />
					<input type="hidden" name="acfw_selected" class="acfw-selected-input" value="" />

					<div class="acfw-builder-layout">

						<div class="acfw-card acfw-builder-list">
							<div class="acfw-panel-head">
								<span class="acfw-panel-icon dashicons dashicons-menu-alt" aria-hidden="true"></span>
								<div class="acfw-panel-heading">
									<h2><?php esc_html_e( 'Menu Items', 'my-account-dashboard-builder' ); ?></h2>
									<p><?php esc_html_e( 'Manage your account menu structure', 'my-account-dashboard-builder' ); ?></p>
								</div>
							</div>

							<div class="acfw-panel-tools">
								<span class="acfw-search-wrap">
									<span class="dashicons dashicons-search" aria-hidden="true"></span>
									<input type="search" class="acfw-item-search" placeholder="<?php esc_attr_e( 'Search menu items…', 'my-account-dashboard-builder' ); ?>" aria-label="<?php esc_attr_e( 'Search menu items', 'my-account-dashboard-builder' ); ?>" />
								</span>
								<button type="button" class="acfw-filter-toggle" aria-pressed="false" title="<?php esc_attr_e( 'Show enabled items only', 'my-account-dashboard-builder' ); ?>" aria-label="<?php esc_attr_e( 'Show enabled items only', 'my-account-dashboard-builder' ); ?>">
									<span class="dashicons dashicons-filter" aria-hidden="true"></span>
								</button>
							</div>

							<ol class="acfw-sortable acfw-sortable-root">
								<?php
								foreach ( $items as $key => $item ) {
									$this->render_item_row( $key, $item );
								}
								?>
							</ol>

							<p class="acfw-list-empty" hidden><?php esc_html_e( 'No menu items match that search.', 'my-account-dashboard-builder' ); ?></p>

							<div class="acfw-list-hint">
								<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
								<?php esc_html_e( 'Drag and drop to reorder menu items', 'my-account-dashboard-builder' ); ?>
							</div>
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

					<div class="acfw-form-footer acfw-savebar">
						<span class="acfw-savebar-status" role="status"><span class="acfw-savebar-dirty" hidden><?php esc_html_e( 'Unsaved changes', 'my-account-dashboard-builder' ); ?></span></span>
						<button type="submit" class="button button-primary acfw-save-all">
							<span class="dashicons dashicons-saved" aria-hidden="true"></span> <?php esc_html_e( 'Save changes', 'my-account-dashboard-builder' ); ?>
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
					<span class="acfw-node-chip"<?php echo empty( $item['badge'] ) ? ' hidden' : ''; ?>><?php echo esc_html( $item['badge'] ?? '' ); ?></span>
					<span class="acfw-node-lock dashicons dashicons-lock" title="<?php esc_attr_e( 'Only some customers see this item', 'my-account-dashboard-builder' ); ?>"<?php echo $this->rule_count( $item ) ? '' : ' hidden'; ?>></span>
					<?php if ( class_exists( 'ACFW_Commerce' ) && in_array( (string) $key, ACFW_Commerce::disabled_keys(), true ) ) : ?>
						<span class="acfw-node-note" title="<?php esc_attr_e( 'This feature is switched off in Settings, so customers do not see this item.', 'my-account-dashboard-builder' ); ?>"><?php esc_html_e( 'Off in Settings', 'my-account-dashboard-builder' ); ?></span>
					<?php endif; ?>
					<span class="acfw-node-spacer"></span>
					<label class="acfw-switch" title="<?php esc_attr_e( 'Enable / disable', 'my-account-dashboard-builder' ); ?>">
						<input type="checkbox" class="acfw-active-proxy" data-key="<?php echo esc_attr( $key ); ?>" <?php checked( $active ); ?> />
						<span class="acfw-switch-slider"></span>
					</label>
					<button type="button" class="acfw-node-edit dashicons dashicons-edit" title="<?php esc_attr_e( 'Edit', 'my-account-dashboard-builder' ); ?>" aria-label="<?php esc_attr_e( 'Edit', 'my-account-dashboard-builder' ); ?>" data-key="<?php echo esc_attr( $key ); ?>"></button>
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
		 * How many visibility rules limit an item.
		 *
		 * @param array $item Item options.
		 * @return int
		 */
		protected function rule_count( $item ) {
			$count  = ! empty( $item['usr_roles'] ) ? 1 : 0;
			$count += ( ! empty( $item['vis_from'] ) || ! empty( $item['vis_to'] ) ) ? 1 : 0;
			$count += acfw_rule_product_ids( $item ) ? 1 : 0;
			$count += ! empty( $item['vis_min_orders'] ) ? 1 : 0;
			$count += ( (float) ( $item['vis_min_spent'] ?? 0 ) > 0 ) ? 1 : 0;
			return $count;
		}

		/**
		 * The address an item points to, for the preview strip.
		 *
		 * @param string $key  Item key.
		 * @param array  $item Item options.
		 * @param string $type Item type.
		 * @return string Site-relative URL for account pages, the full URL for links.
		 */
		protected function preview_url( $key, $item, $type ) {
			if ( 'link' === $type ) {
				return (string) ( $item['url'] ?? '' );
			}
			if ( 'page' === $type ) {
				return ! empty( $item['page_id'] ) ? wp_make_link_relative( (string) get_permalink( (int) $item['page_id'] ) ) : '';
			}
			if ( 'group' === $type ) {
				return '';
			}
			$url = 'dashboard' === $key ? acfw_dashboard_url() : wc_get_endpoint_url( $key, '', wc_get_page_permalink( 'myaccount' ) );
			return wp_make_link_relative( $url );
		}

		/**
		 * Render the options form for one item (right column, hidden until selected).
		 *
		 * The options are split into sections ( General, Content, Visibility,
		 * Advanced ) shown as tabs. Without JavaScript every section shows.
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
			$name        = 'items[' . $key . ']';
			$uid         = 'acfw-i' . substr( md5( (string) $key ), 0, 8 );
			$rules       = $this->rule_count( $item );
			$is_custom   = 'endpoint' === $type && ! ACFW()->items->is_default( $key );
			$base        = trailingslashit( wp_make_link_relative( wc_get_page_permalink( 'myaccount' ) ) );
			$type_labels = array(
				'endpoint' => __( 'Endpoint', 'my-account-dashboard-builder' ),
				'group'    => __( 'Group', 'my-account-dashboard-builder' ),
				'link'     => __( 'Link', 'my-account-dashboard-builder' ),
				'page'     => __( 'Page', 'my-account-dashboard-builder' ),
			);
			$sections    = array( 'general' => __( 'General', 'my-account-dashboard-builder' ) );
			if ( 'endpoint' === $type ) {
				$sections['content'] = __( 'Content', 'my-account-dashboard-builder' );
			}
			$sections['visibility'] = __( 'Visibility', 'my-account-dashboard-builder' );
			$sections['advanced']   = __( 'Advanced', 'my-account-dashboard-builder' );
			$section_icons          = array(
				'general'    => 'admin-settings',
				'content'    => 'edit-page',
				'visibility' => 'visibility',
				'advanced'   => 'admin-tools',
			);
			?>
			<div class="acfw-detail acfw-item-form" data-key="<?php echo esc_attr( $key ); ?>" data-type="<?php echo esc_attr( $type ); ?>" hidden>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>[type]" value="<?php echo esc_attr( $type ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>[active]" class="acfw-active-input" value="<?php echo $active ? '1' : '0'; ?>" />

				<div class="acfw-detail-head">
					<?php echo $this->icon_markup( $item, 'acfw-detail-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="acfw-detail-title"><?php echo esc_html( $item['label'] ); ?></h2>
					<span class="acfw-node-badge acfw-badge-<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type_labels[ $type ] ?? $type ); ?></span>
					<span class="acfw-detail-spacer"></span>
					<button type="button" class="acfw-back-to-menu">
						<span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
						<?php esc_html_e( 'Back to menu', 'my-account-dashboard-builder' ); ?>
					</button>
				</div>

				<?php // The item as it will read in the customer's menu; mirrors the fields below, so it is hidden from assistive tech. ?>
				<?php
				$acfw_pv_accent = sanitize_hex_color( get_option( 'acfw_accent_color', '#2563eb' ) );
				$acfw_pv_style  = '--acfw-pv-accent:' . ( $acfw_pv_accent ? $acfw_pv_accent : '#2563eb' ) . ';--acfw-pv-radius:' . absint( get_option( 'acfw_menu_radius', 8 ) ) . 'px;';
				?>
				<div class="acfw-preview<?php echo $active ? '' : ' is-off'; ?>" aria-hidden="true" data-base="<?php echo esc_attr( $base ); ?>" style="<?php echo esc_attr( $acfw_pv_style ); ?>">
					<span class="acfw-preview-eyebrow"><?php esc_html_e( 'What customers see', 'my-account-dashboard-builder' ); ?></span>
					<span class="acfw-preview-row">
						<span class="acfw-preview-item">
							<span class="acfw-preview-icon"><?php echo $this->icon_markup( $item, 'acfw-preview-glyph' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="acfw-preview-label"><?php echo esc_html( $item['label'] ); ?></span>
							<span class="acfw-preview-pill"<?php echo empty( $item['badge'] ) ? ' hidden' : ''; ?>><?php echo esc_html( $item['badge'] ?? '' ); ?></span>
							<?php if ( 'group' === $type ) : ?>
								<span class="acfw-preview-caret"></span>
							<?php endif; ?>
						</span>
						<span class="acfw-preview-meta">
							<?php $acfw_preview_url = $this->preview_url( $key, $item, $type ); ?>
							<code class="acfw-preview-url"<?php echo '' === $acfw_preview_url ? ' hidden' : ''; ?>><?php echo esc_html( $acfw_preview_url ); ?></code>
							<span class="acfw-preview-flag acfw-preview-locked"<?php echo $rules ? '' : ' hidden'; ?>><span class="dashicons dashicons-lock"></span><?php esc_html_e( 'Some customers only', 'my-account-dashboard-builder' ); ?></span>
							<span class="acfw-preview-flag acfw-preview-hidden"><span class="dashicons dashicons-hidden"></span><?php esc_html_e( 'Hidden from the menu', 'my-account-dashboard-builder' ); ?></span>
						</span>
					</span>
				</div>

				<div class="acfw-section-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Item settings', 'my-account-dashboard-builder' ); ?>">
					<?php foreach ( $sections as $section => $section_label ) : ?>
						<?php $acfw_first = 'general' === $section; ?>
						<button type="button" class="acfw-section-tab<?php echo $acfw_first ? ' is-active' : ''; ?>" role="tab" id="<?php echo esc_attr( $uid . '-tab-' . $section ); ?>" aria-controls="<?php echo esc_attr( $uid . '-' . $section ); ?>" aria-selected="<?php echo $acfw_first ? 'true' : 'false'; ?>" tabindex="<?php echo $acfw_first ? '0' : '-1'; ?>" data-section="<?php echo esc_attr( $section ); ?>">
							<span class="acfw-section-tab-icon dashicons dashicons-<?php echo esc_attr( $section_icons[ $section ] ?? 'admin-generic' ); ?>" aria-hidden="true"></span>
							<span class="acfw-section-tab-label"><?php echo esc_html( $section_label ); ?></span>
							<?php if ( 'visibility' === $section ) : ?>
								<span class="acfw-section-count"<?php echo $rules ? '' : ' hidden'; ?>><?php echo esc_html( $rules ); ?></span>
							<?php endif; ?>
						</button>
					<?php endforeach; ?>
				</div>

				<?php // ---- General ---- ?>
				<section class="acfw-section is-active" id="<?php echo esc_attr( $uid . '-general' ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid . '-tab-general' ); ?>" data-section="general">
					<div class="acfw-field">
						<label for="<?php echo esc_attr( $uid . '-label' ); ?>"><?php esc_html_e( 'Label', 'my-account-dashboard-builder' ); ?></label>
						<input type="text" id="<?php echo esc_attr( $uid . '-label' ); ?>" class="acfw-label-input" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( $item['label'] ); ?>" />
					</div>

					<?php if ( $is_custom ) : ?>
						<div class="acfw-field">
							<label for="<?php echo esc_attr( $uid . '-slug' ); ?>"><?php esc_html_e( 'Endpoint URL', 'my-account-dashboard-builder' ); ?></label>
							<span class="acfw-slug-row">
								<span class="acfw-slug-base"><?php echo esc_html( $base ); ?></span>
								<input type="text" id="<?php echo esc_attr( $uid . '-slug' ); ?>" class="acfw-slug-input" name="<?php echo esc_attr( $name ); ?>[slug]" value="<?php echo esc_attr( ! empty( $item['slug'] ) ? $item['slug'] : $key ); ?>" spellcheck="false" autocomplete="off" />
							</span>
							<p class="acfw-hint"><?php esc_html_e( 'Lowercase letters, numbers and dashes. Links to the old address stop working when you change it.', 'my-account-dashboard-builder' ); ?></p>
						</div>
					<?php endif; ?>

					<?php if ( 'link' === $type ) : ?>
						<div class="acfw-field">
							<label for="<?php echo esc_attr( $uid . '-url' ); ?>"><?php esc_html_e( 'URL', 'my-account-dashboard-builder' ); ?></label>
							<input type="url" id="<?php echo esc_attr( $uid . '-url' ); ?>" class="acfw-url-input" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( $item['url'] ?? '' ); ?>" placeholder="https://" />
						</div>
					<?php elseif ( 'page' === $type ) : ?>
						<div class="acfw-field">
							<label for="<?php echo esc_attr( $uid . '-page' ); ?>"><?php esc_html_e( 'Page', 'my-account-dashboard-builder' ); ?></label>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => esc_attr( $name . '[page_id]' ),
									'id'                => esc_attr( $uid . '-page' ),
									'selected'          => (int) ( $item['page_id'] ?? 0 ),
									'show_option_none'  => esc_html__( '— Select a page —', 'my-account-dashboard-builder' ),
									'option_none_value' => 0,
								)
							);
							?>
						</div>
					<?php endif; ?>

					<?php if ( 'link' === $type || 'page' === $type ) : ?>
						<div class="acfw-field">
							<label for="<?php echo esc_attr( $uid . '-blank' ); ?>"><?php esc_html_e( 'Open in new tab', 'my-account-dashboard-builder' ); ?></label>
							<label class="acfw-switch acfw-switch-lg"><input type="checkbox" id="<?php echo esc_attr( $uid . '-blank' ); ?>" name="<?php echo esc_attr( $name ); ?>[target_blank]" value="1" <?php checked( ! empty( $item['target_blank'] ) ); ?> /><span class="acfw-switch-slider"></span></label>
						</div>
					<?php endif; ?>

					<?php if ( 'group' === $type ) : ?>
						<div class="acfw-field">
							<label for="<?php echo esc_attr( $uid . '-open' ); ?>"><?php esc_html_e( 'Start expanded', 'my-account-dashboard-builder' ); ?></label>
							<label class="acfw-switch acfw-switch-lg"><input type="checkbox" id="<?php echo esc_attr( $uid . '-open' ); ?>" name="<?php echo esc_attr( $name ); ?>[open]" value="1" <?php checked( ! empty( $item['open'] ) ); ?> /><span class="acfw-switch-slider"></span></label>
							<p class="acfw-hint"><?php esc_html_e( 'Show this group open when the page loads. It always opens on one of its own pages.', 'my-account-dashboard-builder' ); ?></p>
						</div>
					<?php else : ?>
						<div class="acfw-field">
							<label for="<?php echo esc_attr( $uid . '-badge' ); ?>"><?php esc_html_e( 'Badge', 'my-account-dashboard-builder' ); ?></label>
							<input type="text" id="<?php echo esc_attr( $uid . '-badge' ); ?>" class="acfw-badge-input" name="<?php echo esc_attr( $name ); ?>[badge]" value="<?php echo esc_attr( $item['badge'] ?? '' ); ?>" maxlength="40" placeholder="<?php esc_attr_e( 'e.g. New', 'my-account-dashboard-builder' ); ?>" />
							<p class="acfw-hint"><?php esc_html_e( 'A short pill beside the label, shown instead of the item count. Smart tags work, e.g. {points_balance} pts.', 'my-account-dashboard-builder' ); ?></p>
						</div>
					<?php endif; ?>

					<div class="acfw-field">
						<span class="acfw-field-label"><?php esc_html_e( 'Icon', 'my-account-dashboard-builder' ); ?></span>
						<div class="acfw-icon-source" role="radiogroup" aria-label="<?php esc_attr_e( 'Icon', 'my-account-dashboard-builder' ); ?>">
							<label class="acfw-radio-card <?php echo 'choose' === $icon_source ? 'is-active' : ''; ?>">
								<input type="radio" name="<?php echo esc_attr( $name ); ?>[icon_source]" value="choose" <?php checked( $icon_source, 'choose' ); ?> />
								<?php echo wp_kses( acfw_ui_icon( 'widget' ), acfw_svg_kses() ); ?> <?php esc_html_e( 'Choose icon', 'my-account-dashboard-builder' ); ?>
							</label>
							<label class="acfw-radio-card <?php echo 'upload' === $icon_source ? 'is-active' : ''; ?>">
								<input type="radio" name="<?php echo esc_attr( $name ); ?>[icon_source]" value="upload" <?php checked( $icon_source, 'upload' ); ?> />
								<?php echo wp_kses( acfw_ui_icon( 'upload' ), acfw_svg_kses() ); ?> <?php esc_html_e( 'Upload icon', 'my-account-dashboard-builder' ); ?>
							</label>
						</div>
					</div>

					<div class="acfw-field acfw-icon-choose" <?php echo 'choose' === $icon_source ? '' : 'hidden'; ?>>
						<label for="<?php echo esc_attr( $uid . '-icon' ); ?>"><?php esc_html_e( 'Icon from the library', 'my-account-dashboard-builder' ); ?></label>
						<?php $this->icon_picker( $name . '[icon]', $item['icon'] ?? '', $uid . '-icon' ); ?>
					</div>

					<div class="acfw-field acfw-icon-upload" <?php echo 'upload' === $icon_source ? '' : 'hidden'; ?>>
						<span class="acfw-field-label"><?php esc_html_e( 'Your image', 'my-account-dashboard-builder' ); ?></span>
						<?php $this->uploader( $name . '[icon_url]', $item['icon_url'] ?? '' ); ?>
					</div>
				</section>

				<?php if ( 'endpoint' === $type ) : ?>
					<?php
					$ukey        = str_replace( '-', '_', $key );
					$eid         = 'acfw_content_' . $ukey;
					$editor_type = ( isset( $item['editor_type'] ) && 'block' === $item['editor_type'] ) ? 'block' : 'classic';
					$is_block    = 'block' === $editor_type;
					?>
					<?php // ---- Content ---- ?>
					<section class="acfw-section" id="<?php echo esc_attr( $uid . '-content' ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid . '-tab-content' ); ?>" data-section="content">
						<h3 class="acfw-section-title"><?php esc_html_e( 'Page content', 'my-account-dashboard-builder' ); ?></h3>
						<div class="acfw-field">
							<span class="acfw-field-label"><?php esc_html_e( 'Editor', 'my-account-dashboard-builder' ); ?></span>
							<div class="acfw-radio-group acfw-editor-type-group acfw-has-icons" role="radiogroup" aria-label="<?php esc_attr_e( 'Editor', 'my-account-dashboard-builder' ); ?>">
								<label class="acfw-radio-box acfw_choose_icon_type_inner_wrapper <?php echo ! $is_block ? 'is-active active' : ''; ?>">
									<input type="radio" class="acfw_editor_type_radio" name="<?php echo esc_attr( $name ); ?>[editor_type]" value="classic" data-endpoint="<?php echo esc_attr( $ukey ); ?>" <?php checked( $editor_type, 'classic' ); ?> />
									<?php echo wp_kses( acfw_ui_icon( 'classic' ), acfw_svg_kses() ); ?><span class="acfw-radio-text"><?php esc_html_e( 'Classic', 'my-account-dashboard-builder' ); ?></span>
								</label>
								<label class="acfw-radio-box acfw_choose_icon_type_inner_wrapper <?php echo $is_block ? 'is-active active' : ''; ?>">
									<input type="radio" class="acfw_editor_type_radio" name="<?php echo esc_attr( $name ); ?>[editor_type]" value="block" data-endpoint="<?php echo esc_attr( $ukey ); ?>" <?php checked( $editor_type, 'block' ); ?> />
									<?php echo wp_kses( acfw_ui_icon( 'block' ), acfw_svg_kses() ); ?><span class="acfw-radio-text"><?php esc_html_e( 'Block', 'my-account-dashboard-builder' ); ?></span>
								</label>
							</div>
						</div>
						<div class="acfw-field acfw-field-wide">
							<span class="acfw-field-label"><?php esc_html_e( 'Custom content', 'my-account-dashboard-builder' ); ?></span>
							<div class="acfw-content-wrap">
								<div class="acfw_classic_editor_wrapper <?php echo $is_block ? 'acfw_hidden' : ''; ?>" data-endpoint="<?php echo esc_attr( $ukey ); ?>">
									<?php
									wp_editor(
										$item['content'] ?? '',
										$eid,
										array(
											'textarea_name' => $name . '[content]',
											'textarea_rows' => 8,
											'media_buttons' => true,
											'quicktags' => true,
											'tinymce'   => array( 'toolbar1' => 'bold,italic,bullist,numlist,link,undo,redo' ),
										)
									);
									?>
								</div>
								<div class="acfw_block_editor_wrapper <?php echo $is_block ? '' : 'acfw_hidden'; ?>" data-endpoint="<?php echo esc_attr( $ukey ); ?>">
									<textarea id="acfw_block_content_<?php echo esc_attr( $ukey ); ?>" name="<?php echo esc_attr( $name ); ?>[content]" class="acfw_block_editor_input" style="display:none;" <?php disabled( $is_block, false ); ?>><?php echo esc_textarea( $item['content'] ?? '' ); ?></textarea>
									<div class="acfw_block_editor" data-endpoint="<?php echo esc_attr( $ukey ); ?>" data-textarea="acfw_block_content_<?php echo esc_attr( $ukey ); ?>" data-autoinit="<?php echo $is_block ? '1' : '0'; ?>"></div>
								</div>
							</div>
							<p class="acfw-hint"><?php esc_html_e( 'Shortcodes and smart tags work here, e.g. Hello {first_name}.', 'my-account-dashboard-builder' ); ?></p>
						</div>
						<div class="acfw-field">
							<label for="<?php echo esc_attr( $uid . '-position' ); ?>"><?php esc_html_e( 'Placement', 'my-account-dashboard-builder' ); ?></label>
							<select id="<?php echo esc_attr( $uid . '-position' ); ?>" name="<?php echo esc_attr( $name ); ?>[content_position]">
								<?php $cp = $item['content_position'] ?? 'before'; ?>
								<option value="before" <?php selected( $cp, 'before' ); ?>><?php esc_html_e( 'Before default content', 'my-account-dashboard-builder' ); ?></option>
								<option value="after" <?php selected( $cp, 'after' ); ?>><?php esc_html_e( 'After default content', 'my-account-dashboard-builder' ); ?></option>
								<option value="override" <?php selected( $cp, 'override' ); ?>><?php esc_html_e( 'Replace default content', 'my-account-dashboard-builder' ); ?></option>
							</select>
							<?php if ( $is_custom ) : ?>
								<p class="acfw-hint"><?php esc_html_e( 'A custom endpoint has no default content, so this only matters for WooCommerce pages.', 'my-account-dashboard-builder' ); ?></p>
							<?php endif; ?>
						</div>

						<h3 class="acfw-section-title"><?php esc_html_e( 'Banners', 'my-account-dashboard-builder' ); ?></h3>
						<div class="acfw-field">
							<label for="<?php echo esc_attr( $uid . '-banners' ); ?>"><?php esc_html_e( 'Show banners', 'my-account-dashboard-builder' ); ?></label>
							<?php $acfw_selected_banners = acfw_item_banner_slugs( $item ); ?>
							<?php $acfw_all_banners = ACFW_Banners::all(); ?>
							<select id="<?php echo esc_attr( $uid . '-banners' ); ?>" name="<?php echo esc_attr( $name ); ?>[banner_slugs][]" class="acfw-banner-select" multiple data-placeholder="<?php esc_attr_e( 'No banners', 'my-account-dashboard-builder' ); ?>">
								<?php foreach ( $acfw_all_banners as $b_slug => $b ) : ?>
									<option value="<?php echo esc_attr( $b_slug ); ?>" <?php selected( in_array( $b_slug, $acfw_selected_banners, true ) ); ?>><?php echo esc_html( $b['title'] ? $b['title'] : $b_slug ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ( empty( $acfw_all_banners ) ) : ?>
								<p class="acfw-hint">
									<?php esc_html_e( 'No banners yet.', 'my-account-dashboard-builder' ); ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '&tab=banners' ) ); ?>"><?php esc_html_e( 'Create one on the Banners tab', 'my-account-dashboard-builder' ); ?></a>
								</p>
							<?php else : ?>
								<p class="acfw-hint"><?php esc_html_e( 'They show in the order you pick them.', 'my-account-dashboard-builder' ); ?></p>
							<?php endif; ?>
						</div>
						<div class="acfw-field">
							<span class="acfw-field-label"><?php esc_html_e( 'Banner position', 'my-account-dashboard-builder' ); ?></span>
							<?php
							$this->buttonset(
								$name . '[banner_position]',
								$item['banner_position'] ?? 'top',
								array(
									'top'    => __( 'Top', 'my-account-dashboard-builder' ),
									'bottom' => __( 'Bottom', 'my-account-dashboard-builder' ),
								),
								'top'
							);
							?>
						</div>
					</section>
				<?php endif; ?>

				<?php // ---- Visibility ---- ?>
				<section class="acfw-section acfw-rules" id="<?php echo esc_attr( $uid . '-visibility' ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid . '-tab-visibility' ); ?>" data-section="visibility">
					<p class="acfw-section-intro"><?php esc_html_e( 'Everyone sees this item until a rule below narrows it down. A customer has to pass every rule you set. Shop managers always see every item, so they can preview it.', 'my-account-dashboard-builder' ); ?></p>

					<div class="acfw-field">
						<label for="<?php echo esc_attr( $uid . '-roles' ); ?>"><?php esc_html_e( 'User roles', 'my-account-dashboard-builder' ); ?></label>
						<select id="<?php echo esc_attr( $uid . '-roles' ); ?>" name="<?php echo esc_attr( $name ); ?>[usr_roles][]" multiple size="4" class="acfw-roles-select acfw-rule-input" data-placeholder="<?php esc_attr_e( 'Everyone', 'my-account-dashboard-builder' ); ?>">
							<?php foreach ( $roles as $role_key => $role_label ) : ?>
								<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( in_array( $role_key, $sel_roles, true ) ); ?>><?php echo esc_html( translate_user_role( $role_label ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="acfw-field">
						<span class="acfw-field-label"><?php esc_html_e( 'Dates', 'my-account-dashboard-builder' ); ?></span>
						<span class="acfw-inline-fields">
							<label class="acfw-inline-field">
								<span><?php esc_html_e( 'From', 'my-account-dashboard-builder' ); ?></span>
								<input type="date" class="acfw-rule-input" name="<?php echo esc_attr( $name ); ?>[vis_from]" value="<?php echo esc_attr( $item['vis_from'] ?? '' ); ?>" />
							</label>
							<label class="acfw-inline-field">
								<span><?php esc_html_e( 'until', 'my-account-dashboard-builder' ); ?></span>
								<input type="date" class="acfw-rule-input" name="<?php echo esc_attr( $name ); ?>[vis_to]" value="<?php echo esc_attr( $item['vis_to'] ?? '' ); ?>" />
							</label>
						</span>
						<p class="acfw-hint"><?php esc_html_e( 'Whole days, in the site timezone. Leave either end open.', 'my-account-dashboard-builder' ); ?></p>
					</div>

					<div class="acfw-field">
						<label for="<?php echo esc_attr( $uid . '-products' ); ?>"><?php esc_html_e( 'Bought any of', 'my-account-dashboard-builder' ); ?></label>
						<select id="<?php echo esc_attr( $uid . '-products' ); ?>" name="<?php echo esc_attr( $name ); ?>[vis_products][]" class="acfw-product-select acfw-rule-input" multiple data-placeholder="<?php esc_attr_e( 'Search for a product…', 'my-account-dashboard-builder' ); ?>">
							<?php foreach ( acfw_rule_product_ids( $item ) as $acfw_product_id ) : ?>
								<?php $acfw_product = function_exists( 'wc_get_product' ) ? wc_get_product( $acfw_product_id ) : null; ?>
								<option value="<?php echo esc_attr( $acfw_product_id ); ?>" selected><?php echo esc_html( $acfw_product ? wp_strip_all_tags( $acfw_product->get_formatted_name() ) : '#' . $acfw_product_id ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="acfw-field">
						<span class="acfw-field-label"><?php esc_html_e( 'Order history', 'my-account-dashboard-builder' ); ?></span>
						<span class="acfw-inline-fields">
							<label class="acfw-inline-field">
								<span><?php esc_html_e( 'At least', 'my-account-dashboard-builder' ); ?></span>
								<input type="number" class="acfw-rule-input acfw-input-short" name="<?php echo esc_attr( $name ); ?>[vis_min_orders]" min="0" step="1" value="<?php echo esc_attr( ! empty( $item['vis_min_orders'] ) ? absint( $item['vis_min_orders'] ) : '' ); ?>" placeholder="0" />
								<span><?php esc_html_e( 'orders', 'my-account-dashboard-builder' ); ?></span>
							</label>
							<label class="acfw-inline-field">
								<span><?php esc_html_e( 'and', 'my-account-dashboard-builder' ); ?></span>
								<span class="acfw-money-row">
									<span class="acfw-money-symbol"><?php echo esc_html( function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '' ); ?></span>
									<input type="number" class="acfw-rule-input acfw-input-short" name="<?php echo esc_attr( $name ); ?>[vis_min_spent]" min="0" step="0.01" value="<?php echo esc_attr( ! empty( $item['vis_min_spent'] ) ? (float) $item['vis_min_spent'] : '' ); ?>" placeholder="0" />
								</span>
								<span><?php esc_html_e( 'spent', 'my-account-dashboard-builder' ); ?></span>
							</label>
						</span>
						<p class="acfw-hint"><?php esc_html_e( 'Leave a box empty to skip that rule.', 'my-account-dashboard-builder' ); ?></p>
					</div>
				</section>

				<?php // ---- Advanced ---- ?>
				<section class="acfw-section" id="<?php echo esc_attr( $uid . '-advanced' ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid . '-tab-advanced' ); ?>" data-section="advanced">
					<div class="acfw-field">
						<label for="<?php echo esc_attr( $uid . '-class' ); ?>"><?php esc_html_e( 'CSS class', 'my-account-dashboard-builder' ); ?></label>
						<input type="text" id="<?php echo esc_attr( $uid . '-class' ); ?>" name="<?php echo esc_attr( $name ); ?>[class]" value="<?php echo esc_attr( $item['class'] ?? '' ); ?>" spellcheck="false" />
						<p class="acfw-hint"><?php esc_html_e( 'Added to this item in the menu, for your own styles.', 'my-account-dashboard-builder' ); ?></p>
					</div>
					<div class="acfw-field">
						<span class="acfw-field-label"><?php esc_html_e( 'Item key', 'my-account-dashboard-builder' ); ?></span>
						<code class="acfw-key"><?php echo esc_html( $key ); ?></code>
						<p class="acfw-hint"><?php esc_html_e( 'Identifies this item in filters and in its menu class.', 'my-account-dashboard-builder' ); ?> <code>woocommerce-MyAccount-navigation-link--<?php echo esc_html( sanitize_html_class( $key ) ); ?></code></p>
					</div>
				</section>

			</div>
			<?php
		}
	}
}
