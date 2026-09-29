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
		 * The group menu being drawn ( '' = the main menu ).
		 *
		 * @var string
		 */
		protected $profile = '';

		/**
		 * Limit a new item to a group menu's roles.
		 *
		 * @param array  $data    Item settings.
		 * @param string $profile Group menu.
		 * @return array
		 */
		protected function for_group( $data, $profile ) {
			$group = ACFW_Profiles::get( $profile );
			if ( $group && ! empty( $group['roles'] ) ) {
				$data['usr_roles']  = array_values( (array) $group['roles'] );
				$data['visibility'] = 'roles';
			}
			return $data;
		}

		/**
		 * Say where an item added from a group menu went.
		 *
		 * @param string $label Item label.
		 */
		protected function group_notice( $label ) {
			$this->add_notice(
				sprintf(
					/* translators: %s: menu item label. */
					__( '"%s" was added to the main menu too, shown only to this group\'s roles. Change that under its Visibility.', 'my-account-dashboard-builder' ),
					$label
				),
				'success'
			);
		}

		/**
		 * "Menu for": the main menu or a group menu, and managing group menus.
		 *
		 * @param string $profile Current group menu.
		 */
		protected function profile_bar( $profile ) {
			$profiles = ACFW_Profiles::all();
			$current  = '' !== $profile ? ACFW_Profiles::get( $profile ) : null;
			$names    = wp_roles()->get_names();
			$roles    = array();
			foreach ( (array) ( $current['roles'] ?? array() ) as $role ) {
				$roles[] = isset( $names[ $role ] ) ? translate_user_role( $names[ $role ] ) : $role;
			}
			?>
			<div class="acfw-card acfw-profile-bar">
				<form method="get" class="acfw-profile-switch">
					<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>" />
					<input type="hidden" name="tab" value="items" />
					<label for="acfw-profile-select"><?php esc_html_e( 'Menu for', 'my-account-dashboard-builder' ); ?></label>
					<select id="acfw-profile-select" name="profile" onchange="this.form.submit()">
						<option value=""><?php esc_html_e( 'Everyone (main menu)', 'my-account-dashboard-builder' ); ?></option>
						<?php foreach ( $profiles as $slug => $item ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $profile, $slug ); ?>><?php echo esc_html( $item['label'] ?? $slug ); ?></option>
						<?php endforeach; ?>
					</select>
					<noscript><button type="submit" class="button"><?php esc_html_e( 'Show', 'my-account-dashboard-builder' ); ?></button></noscript>
				</form>
				<p class="acfw-profile-note">
					<?php if ( $current ) : ?>
						<?php /* translators: %s: role names. */ ?>
						<?php echo esc_html( sprintf( __( 'Customers with the role %s get this menu instead of the main one. Item settings are shared with the main menu.', 'my-account-dashboard-builder' ), implode( ', ', $roles ) ) ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Give a customer group, such as wholesale buyers, a menu of its own.', 'my-account-dashboard-builder' ); ?>
					<?php endif; ?>
				</p>
				<span class="acfw-profile-actions">
					<?php if ( $current ) : ?>
						<button type="button" class="button" data-acfw-open="acfw-profile-dialog-edit"><?php esc_html_e( 'Name and roles', 'my-account-dashboard-builder' ); ?></button>
						<form method="post" class="acfw-inline-form">
							<?php wp_nonce_field( self::NONCE ); ?>
							<input type="hidden" name="acfw_action" value="profile_delete" />
							<input type="hidden" name="acfw_profile" value="<?php echo esc_attr( $profile ); ?>" />
							<button type="submit" class="button-link acfw-danger-link" data-acfw-confirm="<?php esc_attr_e( 'Delete this menu? Its customers get the main menu again.', 'my-account-dashboard-builder' ); ?>"><?php esc_html_e( 'Delete this menu', 'my-account-dashboard-builder' ); ?></button>
						</form>
					<?php endif; ?>
					<button type="button" class="button" data-acfw-open="acfw-profile-dialog-new"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> <?php esc_html_e( 'Menu for a customer group', 'my-account-dashboard-builder' ); ?></button>
				</span>
			</div>
			<?php
			$this->profile_dialog( 'acfw-profile-dialog-new', '', array() );
			if ( $current ) {
				$this->profile_dialog( 'acfw-profile-dialog-edit', $profile, $current );
			}
		}

		/**
		 * The create / edit dialog of a group menu.
		 *
		 * @param string $id      Element id.
		 * @param string $slug    Group menu, '' for a new one.
		 * @param array  $profile Its settings.
		 */
		protected function profile_dialog( $id, $slug, $profile ) {
			$picked = (array) ( $profile['roles'] ?? array() );
			?>
			<div class="acfw-add-overlay acfw-dialog" id="<?php echo esc_attr( $id ); ?>" hidden>
				<form method="post" class="acfw-add-pop" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $id . '-title' ); ?>">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="profile_save" />
					<input type="hidden" name="profile_slug" value="<?php echo esc_attr( $slug ); ?>" />
					<input type="hidden" name="acfw_profile" value="<?php echo esc_attr( $slug ); ?>" />
					<div class="acfw-add-head">
						<h3 class="acfw-add-title" id="<?php echo esc_attr( $id . '-title' ); ?>"><?php echo esc_html( '' === $slug ? __( 'A menu for a customer group', 'my-account-dashboard-builder' ) : __( 'Name and roles', 'my-account-dashboard-builder' ) ); ?></h3>
						<button type="button" class="acfw-add-close" data-acfw-close aria-label="<?php esc_attr_e( 'Close', 'my-account-dashboard-builder' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
					</div>
					<?php if ( '' === $slug ) : ?>
						<p class="acfw-add-where"><?php esc_html_e( 'It starts as a copy of the main menu. Take items out, add others and reorder it; item settings stay shared.', 'my-account-dashboard-builder' ); ?></p>
					<?php endif; ?>
					<label class="acfw-add-label-field">
						<span><?php esc_html_e( 'Name', 'my-account-dashboard-builder' ); ?></span>
						<input type="text" name="profile_label" value="<?php echo esc_attr( $profile['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Wholesale', 'my-account-dashboard-builder' ); ?>" required />
					</label>
					<fieldset class="acfw-profile-roles">
						<legend><?php esc_html_e( 'Customers with any of these roles', 'my-account-dashboard-builder' ); ?></legend>
						<?php foreach ( wp_roles()->get_names() as $role => $role_label ) : ?>
							<label><input type="checkbox" name="profile_roles[]" value="<?php echo esc_attr( $role ); ?>" <?php checked( in_array( $role, $picked, true ) ); ?> /> <?php echo esc_html( translate_user_role( $role_label ) ); ?></label>
						<?php endforeach; ?>
					</fieldset>
					<div class="acfw-add-actions">
						<button type="button" class="button" data-acfw-close><?php esc_html_e( 'Cancel', 'my-account-dashboard-builder' ); ?></button>
						<button type="submit" class="button button-primary"><?php echo esc_html( '' === $slug ? __( 'Create menu', 'my-account-dashboard-builder' ) : __( 'Save', 'my-account-dashboard-builder' ) ); ?></button>
					</div>
				</form>
			</div>
			<?php
		}

		/**
		 * Items of the main menu this group menu leaves out, to add back.
		 *
		 * @param array $missing Key => item.
		 */
		protected function missing_list( $missing ) {
			$missing = array_filter(
				$missing,
				function ( $item ) {
					return 'group' !== ( $item['type'] ?? '' );
				}
			);
			if ( ! $missing ) {
				return;
			}
			?>
			<div class="acfw-profile-missing">
				<h3><?php esc_html_e( 'Not in this menu', 'my-account-dashboard-builder' ); ?></h3>
				<ul>
					<?php foreach ( $missing as $key => $item ) : ?>
						<li>
							<span><?php echo esc_html( $item['label'] ?? $key ); ?></span>
							<button type="submit" form="acfw-profile-add-form" class="button-link" name="item_key" value="<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'Add to this menu', 'my-account-dashboard-builder' ); ?></button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		}

		/**
		 * Handle POST actions for the menu-items builder.
		 *
		 * @param string     $action Sanitized action slug.
		 * @param ACFW_Items $items  Menu items manager.
		 */
		public function handle( $action, $items ) {
			// Nonce is verified in ACFW_Admin::handle_actions() before dispatch.
			// phpcs:disable WordPress.Security.NonceVerification.Missing

			// A group menu being edited: orders go to it, and saving lands back on it.
			$profile = isset( $_POST['acfw_profile'] ) ? sanitize_key( wp_unslash( $_POST['acfw_profile'] ) ) : '';
			$profile = ( '' !== $profile && ACFW_Profiles::get( $profile ) ) ? $profile : '';
			if ( '' !== $profile ) {
				$this->redirect_args['profile'] = $profile;
			}
			$item_key = isset( $_POST['item_key'] ) ? acfw_sanitize_key( sanitize_title( wp_unslash( $_POST['item_key'] ) ) ) : '';

			switch ( $action ) {

				case 'profile_save':
					$slug  = isset( $_POST['profile_slug'] ) ? sanitize_key( wp_unslash( $_POST['profile_slug'] ) ) : '';
					$label = isset( $_POST['profile_label'] ) ? sanitize_text_field( wp_unslash( $_POST['profile_label'] ) ) : '';
					$roles = isset( $_POST['profile_roles'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['profile_roles'] ) ) : array();
					if ( '' === $label || ! $roles ) {
						$this->add_notice( __( 'Give the menu a name and pick at least one role.', 'my-account-dashboard-builder' ), 'error' );
						break;
					}
					// A new menu starts as a copy of the main one.
					$this->redirect_args['profile'] = ACFW_Profiles::save( $slug, $label, $roles, acfw_order_from_items( $items->get_items() ) );
					break;

				case 'profile_delete':
					if ( '' !== $profile ) {
						ACFW_Profiles::delete( $profile );
						unset( $this->redirect_args['profile'] );
					}
					break;

				case 'profile_remove':
					if ( '' !== $profile && '' !== $item_key ) {
						ACFW_Profiles::remove_item( $profile, $item_key );
					}
					break;

				case 'profile_add':
					if ( '' !== $profile && '' !== $item_key ) {
						$flat = acfw_flatten_items( $items->get_items() );
						ACFW_Profiles::add_item( $profile, $item_key, $flat[ $item_key ]['type'] ?? 'endpoint' );
						$this->redirect_args['select'] = $item_key;
					}
					break;

				case 'add_item':
					// The add form of earlier versions ( the canvas posts save_all instead ).
					$added = $this->add_from_canvas(
						array(
							'type'  => isset( $_POST['item_type'] ) ? sanitize_key( wp_unslash( $_POST['item_type'] ) ) : 'endpoint',
							'label' => isset( $_POST['item_label'] ) ? sanitize_text_field( wp_unslash( $_POST['item_label'] ) ) : '',
						),
						$items
					);
					if ( '' !== $added ) {
						$this->redirect_args['select'] = $added;
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
								'label'             => isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : '',
								'icon_source'       => 'upload' === $icon_source ? 'upload' : 'choose',
								'icon'              => ( 'upload' !== $icon_source && isset( $data['icon'] ) ) ? acfw_sanitize_icon( $data['icon'] ) : '',
								'icon_url'          => ( 'upload' === $icon_source && isset( $data['icon_url'] ) ) ? esc_url_raw( $data['icon_url'] ) : '',
								'class'             => isset( $data['class'] ) ? sanitize_html_class( $data['class'] ) : '',
								'active'            => ! empty( $data['active'] ),
								'content'           => $content,
								'editor_type'       => $editor_type,
								'content_position'  => isset( $data['content_position'] ) ? sanitize_key( $data['content_position'] ) : 'before',
								'usr_roles'         => $roles,
								'visibility'        => empty( $roles ) ? 'all' : 'roles',
								'url'               => isset( $data['url'] ) ? esc_url_raw( $data['url'] ) : '',
								'page_id'           => isset( $data['page_id'] ) ? absint( $data['page_id'] ) : 0,
								'target_blank'      => ! empty( $data['target_blank'] ),
								'open'              => ! empty( $data['open'] ),
								'banner_slugs'      => isset( $data['banner_slugs'] ) && is_array( $data['banner_slugs'] )
									? array_values( array_filter( array_map( 'acfw_sanitize_key', $data['banner_slugs'] ) ) )
									: array(),
								'banner_slug'       => '',
								'banner_position'   => ( isset( $data['banner_position'] ) && 'bottom' === $data['banner_position'] ) ? 'bottom' : 'top',
								'slug'              => $slug,
								'badge'             => isset( $data['badge'] ) ? $this->clip( sanitize_text_field( $data['badge'] ), 40 ) : '',
								'vis_from'          => isset( $data['vis_from'] ) ? preg_replace( '/[^0-9-]/', '', $data['vis_from'] ) : '',
								'vis_to'            => isset( $data['vis_to'] ) ? preg_replace( '/[^0-9-]/', '', $data['vis_to'] ) : '',
								// The single-product field became a product list; older data is folded in on read.
								'vis_product'       => 0,
								'vis_products'      => acfw_parse_id_list( $data['vis_products'] ?? array() ),
								'vis_min_orders'    => isset( $data['vis_min_orders'] ) ? absint( $data['vis_min_orders'] ) : 0,
								'vis_max_orders'    => acfw_sanitize_max_orders( $data['vis_max_orders'] ?? '' ),
								'vis_min_spent'     => isset( $data['vis_min_spent'] ) ? $this->money( $data['vis_min_spent'] ) : 0,
								'vis_inactive_days' => isset( $data['vis_inactive_days'] ) ? absint( $data['vis_inactive_days'] ) : 0,
							),
							false
						);
					}

					// The tree is decoded as-is, then sanitized node by node. A text
					// sanitizer on the raw JSON would strip "%xx" octets out of keys.
					$raw   = isset( $_POST['acfw_order'] ) ? wp_unslash( $_POST['acfw_order'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- decoded, then sanitized by acfw_sanitize_order_tree() in save_order().
					$order = is_string( $raw ) ? json_decode( $raw, true ) : null;
					if ( '' !== $profile && is_array( $order ) ) {
						ACFW_Profiles::save_tree( $profile, $order );
					} elseif ( is_array( $order ) && ! empty( $order ) ) {
						$items->save_order( $order );
					} else {
						$items->build( true );
					}

					$selected = isset( $_POST['acfw_selected'] ) ? acfw_sanitize_key( sanitize_title( wp_unslash( $_POST['acfw_selected'] ) ) ) : '';

					// "+" in the canvas posts the whole form, so what was typed elsewhere
					// is saved first; then the new item goes where "+" was clicked.
					if ( isset( $_POST['acfw_add_submit'], $_POST['acfw_add'] ) && is_array( $_POST['acfw_add'] ) ) {
						$added    = $this->add_from_canvas( wp_unslash( $_POST['acfw_add'] ), $items, $profile ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each field sanitized in add_from_canvas().
						$selected = '' !== $added ? $added : $selected;
					}

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
							if ( '' !== $profile ) {
								$data = $this->for_group( $data, $profile );
							}
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
							if ( '' !== $profile ) {
								// The copy also joins the group menu being edited, below the original.
								ACFW_Profiles::save_tree( $profile, acfw_order_insert( ACFW_Profiles::tree( $profile ), $newkey, $type, $src ) );
								$this->group_notice( $data['label'] );
							}
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
		 * Add the item asked for in the canvas' "+" popover, at the spot it was
		 * opened from: below an item, at the end of a group, or at the end.
		 *
		 * @param array      $add     Popover fields ( type, label, after, parent ), unslashed.
		 * @param ACFW_Items $items   Menu items manager.
		 * @param string     $profile Group menu being edited ( '' = the main menu ).
		 * @return string The new item's key, or '' when there was no label.
		 */
		protected function add_from_canvas( $add, $items, $profile = '' ) {
			$label = isset( $add['label'] ) ? sanitize_text_field( (string) $add['label'] ) : '';
			if ( '' === $label ) {
				return '';
			}
			$type   = isset( $add['type'] ) ? sanitize_key( (string) $add['type'] ) : 'endpoint';
			$type   = in_array( $type, ACFW_Items::ITEM_TYPES, true ) ? $type : 'endpoint';
			$after  = isset( $add['after'] ) ? acfw_sanitize_key( (string) $add['after'] ) : '';
			$parent = isset( $add['parent'] ) ? acfw_sanitize_key( (string) $add['parent'] ) : '';

			// Never reuse a key: "Orders" must not overwrite the Orders endpoint.
			$key  = acfw_unique_item_key( $label, $type, $items->used_names() );
			$data = array(
				'label'  => $label,
				'slug'   => $key,
				'active' => true,
			);
			if ( '' === $profile ) {
				$items->save_item( $key, $type, $data, false );
				$items->save_order( acfw_order_insert( acfw_order_from_items( $items->get_items() ), $key, $type, $after, $parent ) );
				return $key;
			}

			// Every item lives in the main menu; one added from a group menu is
			// seen there only by that group's roles.
			$items->save_item( $key, $type, $this->for_group( $data, $profile ), false );
			$items->save_order( acfw_order_logout_last( acfw_order_insert( acfw_order_from_items( $items->get_items() ), $key, $type, $after, $parent ) ) );
			ACFW_Profiles::save_tree( $profile, acfw_order_logout_last( acfw_order_insert( ACFW_Profiles::tree( $profile ), $key, $type, $after, $parent ) ) );
			$this->group_notice( $label );

			return $key;
		}

		/**
		 * Render the Menu Items tab: the customer's menu as an editable canvas,
		 * with an inspector for the item being edited.
		 *
		 * The canvas is drawn with the storefront's own stylesheet ( scoped into
		 * .acfw-canvas-page ) and the Design Studio's tokens, so what the admin
		 * arranges here is what customers get.
		 */
		public function render() {

			// The group menu being edited ( ?profile= ), drawn in place of the main one.
			$profile       = isset( $_GET['profile'] ) ? sanitize_key( wp_unslash( $_GET['profile'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which menu to show.
			$profile       = ( '' !== $profile && ACFW_Profiles::get( $profile ) ) ? $profile : '';
			$this->profile = $profile;
			$main          = acfw_flatten_items( ACFW()->items->get_items() );
			if ( '' !== $profile ) {
				ACFW_Profiles::apply( $profile );
			}

			$items                   = ACFW()->items->get_items();
			list( $layout, $preset ) = acfw_menu_style_resolve( get_option( 'acfw_menu_style', 'simple' ) );

			// A menu across the top of the page ( or Tabs ) leaves no room to show
			// what sits inside a group, so the canvas draws it as a list.
			$as_list = 'tabs' === $layout || 'horizontal' === get_option( 'acfw_menu_position', 'vertical-left' );
			if ( 'tabs' === $layout ) {
				list( $layout, $preset ) = acfw_menu_style_resolve( 'simple' );
			}

			$nav_classes = array(
				'woocommerce-MyAccount-navigation',
				'acfw-menu',
				'acfw-canvas-menu',
				'position-vertical-left',
				'layout-' . sanitize_html_class( $layout ),
				'acfw-preset-' . sanitize_html_class( $preset ),
				'acfw-ind-' . sanitize_html_class( get_option( 'acfw_active_indicator', 'bar' ) ),
				'acfw-anim-none',
				'acfw-scheme-' . sanitize_html_class( get_option( 'acfw_color_scheme', 'light' ) ),
			);
			if ( 'no' === get_option( 'acfw_show_icons', 'yes' ) ) {
				$nav_classes[] = 'acfw-hide-icons';
			}
			?>
			<div class="acfw-builder acfw-canvas-builder">

				<?php // In a group menu, Delete takes the item out of that menu only. ?>
				<form method="post" class="acfw-delete-form" style="display:none;"<?php echo '' !== $profile ? ' data-confirm="' . esc_attr__( 'Take this item out of this menu? It stays in the main menu.', 'my-account-dashboard-builder' ) . '"' : ''; ?>>
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="<?php echo '' !== $profile ? 'profile_remove' : 'remove_item'; ?>" />
					<input type="hidden" name="acfw_profile" value="<?php echo esc_attr( $profile ); ?>" />
					<input type="hidden" name="item_key" class="acfw-delete-key" value="" />
				</form>

				<form method="post" class="acfw-duplicate-form" style="display:none;">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="duplicate_item" />
					<input type="hidden" name="acfw_profile" value="<?php echo esc_attr( $profile ); ?>" />
					<input type="hidden" name="item_key" class="acfw-duplicate-key" value="" />
				</form>

				<form method="post" id="acfw-profile-add-form" style="display:none;">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="profile_add" />
					<input type="hidden" name="acfw_profile" value="<?php echo esc_attr( $profile ); ?>" />
				</form>

				<?php $this->profile_bar( $profile ); ?>

				<form method="post" class="acfw-items-form" novalidate>
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="acfw_action" value="save_all" />
					<input type="hidden" name="acfw_order" class="acfw-order-input" value="" />
					<input type="hidden" name="acfw_selected" class="acfw-selected-input" value="" />
					<input type="hidden" name="acfw_profile" value="<?php echo esc_attr( $profile ); ?>" />

					<div class="acfw-canvas-layout">

						<section class="acfw-card acfw-canvas" aria-labelledby="acfw-canvas-title">
							<div class="acfw-canvas-head">
								<div class="acfw-canvas-heading">
									<h2 id="acfw-canvas-title"><?php esc_html_e( 'Your account menu', 'my-account-dashboard-builder' ); ?></h2>
									<p id="acfw-canvas-help"><?php esc_html_e( 'Drawn the way customers see it. Drag an item to move it or into a group; click it to edit it.', 'my-account-dashboard-builder' ); ?></p>
								</div>
								<div class="acfw-panel-tools">
									<span class="acfw-search-wrap">
										<span class="dashicons dashicons-search" aria-hidden="true"></span>
										<input type="search" class="acfw-item-search" placeholder="<?php esc_attr_e( 'Find an item…', 'my-account-dashboard-builder' ); ?>" aria-label="<?php esc_attr_e( 'Find a menu item', 'my-account-dashboard-builder' ); ?>" />
									</span>
									<button type="button" class="acfw-filter-toggle" aria-pressed="false" title="<?php esc_attr_e( 'Show switched-on items only', 'my-account-dashboard-builder' ); ?>" aria-label="<?php esc_attr_e( 'Show switched-on items only', 'my-account-dashboard-builder' ); ?>">
										<span class="dashicons dashicons-filter" aria-hidden="true"></span>
									</button>
								</div>
							</div>

							<div class="acfw-canvas-stage">
								<div class="acfw-canvas-page">
									<nav class="<?php echo esc_attr( implode( ' ', $nav_classes ) ); ?>" aria-labelledby="acfw-canvas-title" aria-describedby="acfw-canvas-help acfw-canvas-keys">
										<ul class="acfw-sortable acfw-sortable-root">
											<?php
											foreach ( $items as $key => $item ) {
												$this->render_item_row( $key, $item );
											}
											?>
										</ul>
										<p class="acfw-list-empty" hidden><?php esc_html_e( 'No menu items match that search.', 'my-account-dashboard-builder' ); ?></p>
										<button type="button" class="acfw-canvas-add-end acfw-canvas-add" aria-haspopup="dialog" aria-expanded="false">
											<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
											<?php esc_html_e( 'Add to menu', 'my-account-dashboard-builder' ); ?>
										</button>
									</nav>
								</div>
							</div>

							<?php
							if ( '' !== $profile ) {
								$this->missing_list( array_diff_key( $main, acfw_flatten_items( $items ) ) );
							}
							?>
							<p class="acfw-canvas-keys" id="acfw-canvas-keys">
								<?php
								printf(
									/* translators: 1: "Alt + ↑ / ↓" keys, 2: "Alt + → / ←" keys. */
									esc_html__( 'Keyboard: %1$s moves the focused item, %2$s moves it into or out of a group.', 'my-account-dashboard-builder' ),
									'<kbd>Alt</kbd> + <kbd>&uarr;</kbd> / <kbd>&darr;</kbd>',
									'<kbd>Alt</kbd> + <kbd>&rarr;</kbd> / <kbd>&larr;</kbd>'
								);
								?>
							</p>

							<?php if ( $as_list ) : ?>
								<p class="acfw-canvas-note"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><?php esc_html_e( 'Customers see this menu across the top of the page. It is drawn as a list here so what sits in each group stays easy to edit.', 'my-account-dashboard-builder' ); ?></p>
							<?php endif; ?>

						</section>

						<aside class="acfw-card acfw-builder-detail acfw-inspector" aria-label="<?php esc_attr_e( 'Item settings', 'my-account-dashboard-builder' ); ?>">
							<div class="acfw-detail-empty">
								<span class="dashicons dashicons-edit" aria-hidden="true"></span>
								<p><?php esc_html_e( 'Pick an item in the menu to edit it.', 'my-account-dashboard-builder' ); ?></p>
							</div>
							<?php $this->render_item_details( $items ); ?>
						</aside>
					</div>

					<div class="acfw-form-footer acfw-savebar">
						<span class="acfw-savebar-status" role="status"><span class="acfw-savebar-dirty" hidden><?php esc_html_e( 'Unsaved changes', 'my-account-dashboard-builder' ); ?></span></span>
						<button type="submit" class="button button-primary acfw-save-all">
							<span class="dashicons dashicons-saved" aria-hidden="true"></span> <?php esc_html_e( 'Save menu', 'my-account-dashboard-builder' ); ?>
						</button>
					</div>

					<?php // After the save button, so Enter in a field still saves ( the first submit button is the default ). ?>
					<div class="acfw-add-overlay" hidden>
						<div class="acfw-add-pop" role="dialog" aria-modal="true" aria-labelledby="acfw-add-title" aria-describedby="acfw-add-where">
							<div class="acfw-add-head">
								<h3 id="acfw-add-title" class="acfw-add-title"><?php esc_html_e( 'Add to the menu', 'my-account-dashboard-builder' ); ?></h3>
								<button type="button" class="acfw-add-close" aria-label="<?php esc_attr_e( 'Close', 'my-account-dashboard-builder' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
							</div>
							<p class="acfw-add-where" id="acfw-add-where"><?php esc_html_e( 'It goes at the end of the menu. Drag it anywhere afterwards, or into a group.', 'my-account-dashboard-builder' ); ?></p>
							<div class="acfw-segments acfw-add-types" role="radiogroup" aria-label="<?php esc_attr_e( 'What to add', 'my-account-dashboard-builder' ); ?>">
								<?php
								$acfw_types = array(
									'endpoint' => array( __( 'Endpoint', 'my-account-dashboard-builder' ), __( 'A page of its own under My Account, with your content.', 'my-account-dashboard-builder' ) ),
									'group'    => array( __( 'Group', 'my-account-dashboard-builder' ), __( 'A heading that opens to show the items you drop into it.', 'my-account-dashboard-builder' ) ),
									'link'     => array( __( 'Link', 'my-account-dashboard-builder' ), __( 'Any address, on this site or another.', 'my-account-dashboard-builder' ) ),
									'page'     => array( __( 'Page', 'my-account-dashboard-builder' ), __( 'One of your WordPress pages.', 'my-account-dashboard-builder' ) ),
								);
								foreach ( $acfw_types as $acfw_type => $acfw_meta ) :
									?>
									<label class="acfw-segment">
										<input type="radio" name="acfw_add[type]" value="<?php echo esc_attr( $acfw_type ); ?>" data-hint="<?php echo esc_attr( $acfw_meta[1] ); ?>" <?php checked( 'endpoint', $acfw_type ); ?> />
										<span><?php echo esc_html( $acfw_meta[0] ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="acfw-add-hint"><?php echo esc_html( $acfw_types['endpoint'][1] ); ?></p>
							<label class="acfw-add-label-field">
								<span><?php esc_html_e( 'Label', 'my-account-dashboard-builder' ); ?></span>
								<input type="text" name="acfw_add[label]" class="acfw-add-label" autocomplete="off" />
							</label>
							<p class="acfw-add-error" role="alert" hidden><?php esc_html_e( 'Give the item a label first.', 'my-account-dashboard-builder' ); ?></p>
							<div class="acfw-add-actions">
								<button type="button" class="button acfw-add-cancel"><?php esc_html_e( 'Cancel', 'my-account-dashboard-builder' ); ?></button>
								<button type="submit" class="button button-primary acfw-add-submit" name="acfw_add_submit" value="1"><?php esc_html_e( 'Add item', 'my-account-dashboard-builder' ); ?></button>
							</div>
							<p class="acfw-add-note"><?php esc_html_e( 'Your other changes are saved with it.', 'my-account-dashboard-builder' ); ?></p>
						</div>
					</div>
				</form>
			</div>
			<?php
		}

		/**
		 * Every item's settings pane, group children included ( one is shown at a time ).
		 *
		 * @param array $items Items tree.
		 */
		protected function render_item_details( $items ) {
			foreach ( $items as $key => $item ) {
				$this->render_item_detail( $key, $item );
				if ( ! empty( $item['children'] ) ) {
					$this->render_item_details( $item['children'] );
				}
			}
		}

		/**
		 * Who can see an item, in words ( "Customer · 2+ orders" ).
		 *
		 * @param array $item Item options.
		 * @return string[] One phrase per rule.
		 */
		protected function rule_summary( $item ) {
			$parts = array();

			if ( ! empty( $item['usr_roles'] ) ) {
				$names = wp_roles()->get_names();
				$roles = array();
				foreach ( (array) $item['usr_roles'] as $role ) {
					$roles[] = isset( $names[ $role ] ) ? translate_user_role( $names[ $role ] ) : $role;
				}
				$parts[] = implode( ', ', $roles );
			}

			$orders = absint( $item['vis_min_orders'] ?? 0 );
			if ( $orders ) {
				/* translators: %d: minimum number of orders. */
				$parts[] = sprintf( _n( '%d+ order', '%d+ orders', $orders, 'my-account-dashboard-builder' ), $orders );
			}

			$max = acfw_rule_max_orders( $item );
			if ( 0 === $max ) {
				$parts[] = __( 'No orders yet', 'my-account-dashboard-builder' );
			} elseif ( null !== $max ) {
				/* translators: %d: maximum number of orders. */
				$parts[] = sprintf( _n( 'At most %d order', 'At most %d orders', $max, 'my-account-dashboard-builder' ), $max );
			}

			$idle = absint( $item['vis_inactive_days'] ?? 0 );
			if ( $idle ) {
				/* translators: %d: number of days. */
				$parts[] = sprintf( _n( 'No order in %d day', 'No order in %d days', $idle, 'my-account-dashboard-builder' ), $idle );
			}

			$spent = (float) ( $item['vis_min_spent'] ?? 0 );
			if ( $spent > 0 ) {
				/* translators: %s: minimum amount spent, with its currency. */
				$parts[] = sprintf( __( '%s+ spent', 'my-account-dashboard-builder' ), acfw_plain_price( $spent ) );
			}

			$products = count( acfw_rule_product_ids( $item ) );
			if ( 1 === $products ) {
				$parts[] = __( 'Bought 1 product', 'my-account-dashboard-builder' );
			} elseif ( $products > 1 ) {
				/* translators: %d: number of products. */
				$parts[] = sprintf( __( 'Bought one of %d products', 'my-account-dashboard-builder' ), $products );
			}

			$from = ! empty( $item['vis_from'] ) ? strtotime( $item['vis_from'] ) : false;
			$to   = ! empty( $item['vis_to'] ) ? strtotime( $item['vis_to'] ) : false;
			if ( $from && $to ) {
				/* translators: 1: first day, 2: last day. */
				$parts[] = sprintf( __( '%1$s – %2$s', 'my-account-dashboard-builder' ), date_i18n( 'j M', $from ), date_i18n( 'j M', $to ) );
			} elseif ( $from ) {
				/* translators: %s: first day. */
				$parts[] = sprintf( __( 'From %s', 'my-account-dashboard-builder' ), date_i18n( 'j M', $from ) );
			} elseif ( $to ) {
				/* translators: %s: last day. */
				$parts[] = sprintf( __( 'Until %s', 'my-account-dashboard-builder' ), date_i18n( 'j M', $to ) );
			}

			return $parts;
		}

		/**
		 * One item in the canvas, drawn with the storefront's markup ( a link,
		 * or a button for a group ) so the storefront's styles apply, plus the
		 * editing tools beside it. Groups recurse into their children.
		 *
		 * @param string $key  Item key.
		 * @param array  $item Item options.
		 */
		protected function render_item_row( $key, $item ) {

			$type       = $item['type'] ?? 'endpoint';
			$is_default = ACFW()->items->is_default( $key );
			$active     = ! empty( $item['active'] );
			$label      = (string) $item['label'];
			$rules      = $this->rule_summary( $item );
			$off        = class_exists( 'ACFW_Commerce' ) && in_array( (string) $key, ACFW_Commerce::disabled_keys(), true );
			$classes    = array( 'acfw-menu-item', 'acfw-node', 'acfw-type-' . $type );
			if ( ! $active ) {
				$classes[] = 'is-inactive';
			}
			if ( 'group' === $type ) {
				$classes[] = 'is-open';
			}

			ob_start();
			echo $this->icon_markup( $item, 'acfw-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
			?>
			<span class="acfw-label"><?php echo esc_html( $label ); ?></span>
			<span class="acfw-canvas-off dashicons dashicons-hidden" title="<?php esc_attr_e( 'Hidden from the menu', 'my-account-dashboard-builder' ); ?>"></span>
			<span class="acfw-canvas-rules" title="<?php echo esc_attr( implode( ' · ', $rules ) ); ?>"<?php echo $rules ? '' : ' hidden'; ?>><span class="dashicons dashicons-lock" aria-hidden="true"></span><span class="acfw-canvas-rules-text"><?php echo esc_html( implode( ' · ', $rules ) ); ?></span></span>
			<?php if ( $off ) : ?>
				<span class="acfw-canvas-flag" title="<?php esc_attr_e( 'This feature is switched off in Settings, so customers do not see this item.', 'my-account-dashboard-builder' ); ?>"><?php esc_html_e( 'Off in Settings', 'my-account-dashboard-builder' ); ?></span>
			<?php endif; ?>
			<?php if ( 'group' !== $type ) : ?>
				<span class="acfw-count acfw-count-text"<?php echo empty( $item['badge'] ) ? ' hidden' : ''; ?>><?php echo esc_html( $item['badge'] ?? '' ); ?></span>
			<?php endif; ?>
			<?php
			$inner = ob_get_clean();
			?>
			<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-key="<?php echo esc_attr( $key ); ?>" data-type="<?php echo esc_attr( $type ); ?>">
				<?php if ( 'group' === $type ) : ?>
					<button type="button" class="acfw-group-toggle acfw-canvas-link" aria-pressed="false"><?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above. ?></button>
				<?php else : ?>
					<a href="#<?php echo esc_attr( 'item-' . $key ); ?>" class="acfw-canvas-link" role="button" aria-pressed="false"><?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above. ?></a>
				<?php endif; ?>

				<?php // Duplicate and Delete show on hover ( and on the item being edited ); the switch always shows. ?>
				<span class="acfw-canvas-tools">
					<?php /* translators: %s: menu item label. */ ?>
					<button type="button" class="acfw-canvas-tool acfw-node-duplicate" data-key="<?php echo esc_attr( $key ); ?>" title="<?php esc_attr_e( 'Duplicate', 'my-account-dashboard-builder' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Duplicate %s', 'my-account-dashboard-builder' ), $label ) ); ?>"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button>
					<?php if ( '' !== $this->profile ) : ?>
						<?php /* translators: %s: menu item label. */ ?>
						<button type="button" class="acfw-canvas-tool acfw-node-remove" data-key="<?php echo esc_attr( $key ); ?>" title="<?php esc_attr_e( 'Take out of this menu', 'my-account-dashboard-builder' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Take %s out of this menu', 'my-account-dashboard-builder' ), $label ) ); ?>"><span class="dashicons dashicons-remove" aria-hidden="true"></span></button>
					<?php elseif ( $is_default ) : ?>
						<button type="button" class="acfw-canvas-tool acfw-node-remove is-disabled" disabled aria-disabled="true" title="<?php esc_attr_e( 'Built-in items can be switched off but not deleted', 'my-account-dashboard-builder' ); ?>" aria-label="<?php esc_attr_e( 'Built-in items can be switched off but not deleted', 'my-account-dashboard-builder' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
					<?php else : ?>
						<?php /* translators: %s: menu item label. */ ?>
						<button type="button" class="acfw-canvas-tool acfw-node-remove" data-key="<?php echo esc_attr( $key ); ?>" title="<?php esc_attr_e( 'Delete', 'my-account-dashboard-builder' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Delete %s', 'my-account-dashboard-builder' ), $label ) ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
					<?php endif; ?>
					<label class="acfw-switch" title="<?php esc_attr_e( 'Show in the menu', 'my-account-dashboard-builder' ); ?>">
						<?php /* translators: %s: menu item label. */ ?>
						<input type="checkbox" class="acfw-active-proxy" data-key="<?php echo esc_attr( $key ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Show %s in the menu', 'my-account-dashboard-builder' ), $label ) ); ?>" <?php checked( $active ); ?> />
						<span class="acfw-switch-slider"></span>
					</label>
				</span>

				<?php if ( 'group' === $type ) : ?>
					<ul class="acfw-submenu acfw-sortable acfw-sortable-children" data-empty="<?php esc_attr_e( 'Drag items here', 'my-account-dashboard-builder' ); ?>">
						<?php
						if ( ! empty( $item['children'] ) ) {
							foreach ( $item['children'] as $child_key => $child_item ) {
								$this->render_item_row( $child_key, $child_item );
							}
						}
						?>
					</ul>
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
			$count += null !== acfw_rule_max_orders( $item ) ? 1 : 0;
			$count += ! empty( $item['vis_inactive_days'] ) ? 1 : 0;
			$count += ( (float) ( $item['vis_min_spent'] ?? 0 ) > 0 ) ? 1 : 0;
			return $count;
		}

		/**
		 * The address an item points to, shown in the inspector.
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
					<label class="acfw-switch acfw-inspector-switch" title="<?php esc_attr_e( 'Show in the menu', 'my-account-dashboard-builder' ); ?>">
						<?php /* translators: %s: menu item label. */ ?>
						<input type="checkbox" class="acfw-active-proxy" data-key="<?php echo esc_attr( $key ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Show %s in the menu', 'my-account-dashboard-builder' ), $item['label'] ) ); ?>" <?php checked( $active ); ?> />
						<span class="acfw-switch-slider"></span>
					</label>
					<?php /* translators: %s: menu item label. */ ?>
					<button type="button" class="acfw-icon-btn acfw-node-duplicate" data-key="<?php echo esc_attr( $key ); ?>" title="<?php esc_attr_e( 'Duplicate', 'my-account-dashboard-builder' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Duplicate %s', 'my-account-dashboard-builder' ), $item['label'] ) ); ?>"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button>
					<?php if ( ACFW()->items->is_default( $key ) ) : ?>
						<button type="button" class="acfw-icon-btn acfw-node-remove is-disabled" disabled aria-disabled="true" title="<?php esc_attr_e( 'Built-in items can be switched off but not deleted', 'my-account-dashboard-builder' ); ?>" aria-label="<?php esc_attr_e( 'Built-in items can be switched off but not deleted', 'my-account-dashboard-builder' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
					<?php else : ?>
						<?php /* translators: %s: menu item label. */ ?>
						<button type="button" class="acfw-icon-btn acfw-node-remove" data-key="<?php echo esc_attr( $key ); ?>" title="<?php esc_attr_e( 'Delete', 'my-account-dashboard-builder' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Delete %s', 'my-account-dashboard-builder' ), $item['label'] ) ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
					<?php endif; ?>
					<button type="button" class="acfw-back-to-menu">
						<span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
						<?php esc_html_e( 'Back to menu', 'my-account-dashboard-builder' ); ?>
					</button>
				</div>

				<?php $acfw_url = $this->preview_url( $key, $item, $type ); ?>
				<div class="acfw-inspector-meta" data-base="<?php echo esc_attr( $base ); ?>">
					<code class="acfw-inspector-url"<?php echo '' === $acfw_url ? ' hidden' : ''; ?>><?php echo esc_html( $acfw_url ); ?></code>
					<span class="acfw-inspector-off"<?php echo $active ? ' hidden' : ''; ?>><span class="dashicons dashicons-hidden" aria-hidden="true"></span><?php esc_html_e( 'Hidden from the menu', 'my-account-dashboard-builder' ); ?></span>
				</div>

				<?php
				$acfw_tabs = array();
				foreach ( $sections as $section => $section_label ) {
					$acfw_tabs[ $section ] = array( $section_label, $section_icons[ $section ] ?? 'admin-generic' );
				}
				$this->section_tabs( $uid, $acfw_tabs, __( 'Item settings', 'my-account-dashboard-builder' ), array( 'visibility' => $rules ) );
				?>

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
					<p class="acfw-section-intro"><?php esc_html_e( 'Everyone sees this item until a rule below narrows it down. A customer has to pass every rule you set. Shop managers skip the rules so they can preview the item, except the dates: outside them it is hidden for everyone.', 'my-account-dashboard-builder' ); ?></p>

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

					<?php $this->product_rule_field( $name . '[vis_products]', $item, $uid ); ?>

					<?php $this->order_rule_fields( $name . '[%s]', $item, $uid ); ?>
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
