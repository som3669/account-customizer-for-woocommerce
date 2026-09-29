<?php
/**
 * Design Studio tab: pick a look, tune it, and watch the real account page
 * change beside the controls.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Tab_Design' ) ) {

	/**
	 * Renders the Design Studio. Saving happens over AJAX ( ACFW_Design ).
	 */
	class ACFW_Tab_Design extends ACFW_Admin_Tab {

		/**
		 * Saved design values, while the Studio renders.
		 *
		 * @var array
		 */
		protected $saved = array();

		/**
		 * Density presets: item height ( padding ) and space between items.
		 *
		 * @return array slug => array{ label, padding, gap }
		 */
		public static function densities() {
			return array(
				'compact'     => array(
					'label'   => __( 'Compact', 'my-account-dashboard-builder' ),
					'padding' => 7,
					'gap'     => 2,
				),
				'comfortable' => array(
					'label'   => __( 'Comfortable', 'my-account-dashboard-builder' ),
					'padding' => 11,
					'gap'     => 4,
				),
				'roomy'       => array(
					'label'   => __( 'Roomy', 'my-account-dashboard-builder' ),
					'padding' => 15,
					'gap'     => 8,
				),
			);
		}

		/**
		 * The looks on offer: the built-in ones, then the owner's saved presets.
		 *
		 * Each look carries the full set of values it would give the design, so
		 * choosing one previews exactly what saving it would store.
		 *
		 * @param array $saved Saved design values.
		 * @return array slug => array{ label, description, accent, values, custom }
		 */
		protected function looks( $saved ) {
			$fields    = ACFW_Design::fields();
			$preserved = acfw_template_preserved_keys();
			$looks     = array();

			// A look starts from the design defaults, except what a look never sets.
			$base = $saved;
			foreach ( acfw_design_option_defaults() as $key => $default ) {
				if ( isset( $fields[ $key ] ) && ! in_array( $key, $preserved, true ) ) {
					$base[ $key ] = ACFW_Design::sanitize( $fields[ $key ], $default );
				}
			}

			foreach ( acfw_prebuilt_templates() as $slug => $tpl ) {
				$values = $base;
				foreach ( (array) $tpl['options'] as $key => $value ) {
					if ( isset( $fields[ $key ] ) ) {
						$values[ $key ] = ACFW_Design::sanitize( $fields[ $key ], $value );
					}
				}
				$looks[ $slug ] = array(
					'label'       => $tpl['label'],
					'description' => $tpl['description'],
					'accent'      => $tpl['accent'],
					'values'      => $values,
					'custom'      => false,
				);
			}

			$presets = get_option( 'acfw_presets', array() );
			foreach ( is_array( $presets ) ? $presets : array() as $slug => $preset ) {
				if ( ! is_array( $preset ) ) {
					continue;
				}
				$values = $saved;
				foreach ( $preset as $key => $value ) {
					if ( isset( $fields[ $key ] ) && is_scalar( $value ) ) {
						$values[ $key ] = ACFW_Design::sanitize( $fields[ $key ], $value );
					}
				}
				$looks[ 'preset-' . $slug ] = array(
					'label'       => isset( $preset['__label'] ) ? (string) $preset['__label'] : (string) $slug,
					'description' => __( 'Saved by you', 'my-account-dashboard-builder' ),
					'accent'      => $values['acfw_accent_color'] ?? '#2563eb',
					'values'      => $values,
					'custom'      => true,
				);
			}

			return $looks;
		}

		/**
		 * Render the Studio.
		 */
		public function render() {
			$fields      = ACFW_Design::fields();
			$saved       = ACFW_Design::saved_values();
			$this->saved = $saved;
			$looks       = $this->looks( $saved );
			$active      = get_option( 'acfw_active_template', '' );

			// Each visit starts from what is saved; an old draft would only confuse.
			delete_transient( ACFW_Design::draft_key() );

			$config = array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( ACFW_Design::NONCE ),
				'saved'     => $saved,
				'fields'    => array_map(
					static function ( $field ) {
						return array(
							'type'    => $field['type'],
							'live'    => ! empty( $field['live'] ),
							'default' => $field['default'],
							'parent'  => $field['parent'] ?? '',
						);
					},
					$fields
				),
				'looks'     => array_map(
					static function ( $look ) {
						return $look['values'];
					},
					$looks
				),
				'densities' => self::densities(),
				'i18n'      => array(
					'saved'      => __( 'All changes saved', 'my-account-dashboard-builder' ),
					'unsaved'    => __( 'Unsaved changes', 'my-account-dashboard-builder' ),
					'saving'     => __( 'Saving…', 'my-account-dashboard-builder' ),
					'error'      => __( 'That did not save. Check your connection and try again.', 'my-account-dashboard-builder' ),
					'custom'     => __( 'Custom', 'my-account-dashboard-builder' ),
					/* translators: %s: contrast ratio, e.g. 4.8:1. */
					'contrastOk' => __( 'Current page text contrast %s, readable (WCAG AA).', 'my-account-dashboard-builder' ),
					/* translators: %s: contrast ratio, e.g. 2.9:1. */
					'contrastLo' => __( 'Current page text contrast %s, below the 4.5:1 WCAG AA minimum. Try a darker accent.', 'my-account-dashboard-builder' ),
					'lookName'   => __( 'Name this look', 'my-account-dashboard-builder' ),
				),
			);
			?>
			<div class="acfw-studio" data-preview="<?php echo esc_url( ACFW_Design::preview_url() ); ?>">
				<script type="application/json" id="acfw-studio-config"><?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>

				<section class="acfw-card acfw-studio-looks" aria-labelledby="acfw-looks-title">
					<div class="acfw-studio-looks-head">
						<div>
							<h2 id="acfw-looks-title"><?php esc_html_e( 'Start from a look', 'my-account-dashboard-builder' ); ?></h2>
							<p><?php esc_html_e( 'Pick one to try it in the preview. Your site only changes when you save.', 'my-account-dashboard-builder' ); ?></p>
						</div>
						<div class="acfw-save-look">
							<button type="button" class="button acfw-save-look-toggle" aria-expanded="false">
								<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
								<?php esc_html_e( 'Save current as a look', 'my-account-dashboard-builder' ); ?>
							</button>
							<span class="acfw-save-look-form" hidden>
								<input type="text" class="acfw-save-look-name" maxlength="60" aria-label="<?php esc_attr_e( 'Name this look', 'my-account-dashboard-builder' ); ?>" placeholder="<?php esc_attr_e( 'Name this look', 'my-account-dashboard-builder' ); ?>" />
								<button type="button" class="button button-primary acfw-save-look-confirm"><?php esc_html_e( 'Save look', 'my-account-dashboard-builder' ); ?></button>
								<button type="button" class="button-link acfw-save-look-cancel"><?php esc_html_e( 'Cancel', 'my-account-dashboard-builder' ); ?></button>
							</span>
						</div>
					</div>
					<div class="acfw-looks">
						<?php foreach ( $looks as $slug => $look ) : ?>
							<?php $this->render_look( $slug, $look, $slug === $active ); ?>
						<?php endforeach; ?>
					</div>
				</section>

				<div class="acfw-studio-body">
					<div class="acfw-card acfw-studio-rail">
						<?php foreach ( ACFW_Design::groups() as $group => $meta ) : ?>
							<details class="acfw-studio-group" data-group="<?php echo esc_attr( $group ); ?>"<?php echo 'look' === $group ? ' open' : ''; ?>>
								<summary>
									<span class="dashicons dashicons-<?php echo esc_attr( $meta['icon'] ); ?>" aria-hidden="true"></span>
									<span class="acfw-studio-group-label"><?php echo esc_html( $meta['label'] ); ?></span>
								</summary>
								<div class="acfw-studio-fields">
									<?php
										$owned = self::layout_options();
									$subhead   = false;
									foreach ( $fields as $key => $field ) {
										if ( $group !== $field['group'] || in_array( $key, $owned, true ) ) {
											continue;
										}
										if ( 'acfw_dashboard_stats' === ( $field['parent'] ?? '' ) && ! $subhead ) {
											// The number cards' own switches, under the arrangement.
											$subhead = true;
											echo '<div class="acfw-sf acfw-sf-subhead" data-parent="acfw_dashboard_stats"><span class="acfw-sf-label">' . esc_html__( 'Account numbers show', 'my-account-dashboard-builder' ) . '</span></div>';
										}
										if ( 'acfw_item_padding' === $key ) {
											// Density leads; the two sliders sit behind "Fine-tune".
											$this->render_density( $saved );
										}
										$this->render_field( $key, $field, $saved[ $key ] );
										if ( 'acfw_menu_gap' === $key ) {
											echo '</div></details>';
										}
									}
									?>
								</div>
							</details>
						<?php endforeach; ?>
					</div>

					<div class="acfw-card acfw-studio-stage">
						<div class="acfw-studio-toolbar">
							<div class="acfw-devices" role="group" aria-label="<?php esc_attr_e( 'Preview width', 'my-account-dashboard-builder' ); ?>">
								<?php
								$devices = array(
									'desktop' => array( __( 'Desktop', 'my-account-dashboard-builder' ), 'desktop' ),
									'tablet'  => array( __( 'Tablet', 'my-account-dashboard-builder' ), 'tablet' ),
									'phone'   => array( __( 'Phone', 'my-account-dashboard-builder' ), 'smartphone' ),
								);
								foreach ( $devices as $device => $meta ) :
									?>
									<button type="button" class="acfw-device<?php echo 'desktop' === $device ? ' is-active' : ''; ?>" data-device="<?php echo esc_attr( $device ); ?>" aria-pressed="<?php echo 'desktop' === $device ? 'true' : 'false'; ?>">
										<span class="dashicons dashicons-<?php echo esc_attr( $meta[1] ); ?>" aria-hidden="true"></span>
										<span class="acfw-device-label"><?php echo esc_html( $meta[0] ); ?></span>
									</button>
								<?php endforeach; ?>
							</div>
							<code class="acfw-studio-where"><?php echo esc_html( wp_make_link_relative( wc_get_page_permalink( 'myaccount' ) ) ); ?></code>
							<span class="acfw-studio-toolbar-spacer"></span>
							<span class="acfw-view-as">
								<label class="acfw-view-as-label" for="acfw-view-as-studio"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php esc_html_e( 'View as', 'my-account-dashboard-builder' ); ?></label>
								<select id="acfw-view-as-studio" class="acfw-view-as-select" data-preview-arg="<?php echo esc_attr( ACFW_Design::PREVIEW_ARG ); ?>">
									<option value="0" selected><?php esc_html_e( 'Yourself', 'my-account-dashboard-builder' ); ?></option>
								</select>
							</span>
							<button type="button" class="button acfw-studio-reload" title="<?php esc_attr_e( 'Reload the preview', 'my-account-dashboard-builder' ); ?>" aria-label="<?php esc_attr_e( 'Reload the preview', 'my-account-dashboard-builder' ); ?>">
								<span class="dashicons dashicons-update" aria-hidden="true"></span>
							</button>
						</div>
						<div class="acfw-studio-canvas">
							<div class="acfw-studio-frame" data-device="desktop">
								<iframe src="<?php echo esc_url( ACFW_Design::preview_url() ); ?>" title="<?php esc_attr_e( 'Preview of the My Account page', 'my-account-dashboard-builder' ); ?>"></iframe>
							</div>
						</div>
						<p class="acfw-studio-note" data-self="<?php esc_attr_e( 'The preview is your own account, so pages you open here show your orders. Links outside My Account and Log out are switched off.', 'my-account-dashboard-builder' ); ?>" data-other="<?php esc_attr_e( 'Viewing as a customer: their menu, rules, badges and offers apply, and a bar lists what is hidden from them. Nothing changes for them.', 'my-account-dashboard-builder' ); ?>"><?php esc_html_e( 'The preview is your own account, so pages you open here show your orders. Links outside My Account and Log out are switched off.', 'my-account-dashboard-builder' ); ?></p>
					</div>
				</div>

				<div class="acfw-studio-bar">
					<span class="acfw-studio-status" role="status"><?php esc_html_e( 'All changes saved', 'my-account-dashboard-builder' ); ?></span>
					<button type="button" class="button acfw-studio-discard" disabled><?php esc_html_e( 'Discard changes', 'my-account-dashboard-builder' ); ?></button>
					<button type="button" class="button button-primary acfw-studio-save" disabled>
						<span class="dashicons dashicons-saved" aria-hidden="true"></span>
						<?php esc_html_e( 'Save design', 'my-account-dashboard-builder' ); ?>
					</button>
				</div>
			</div>
			<?php
		}

		/**
		 * One look card: a miniature of the menu it gives.
		 *
		 * @param string $slug    Look slug.
		 * @param array  $look    Look definition.
		 * @param bool   $in_use  Whether it is the saved look.
		 */
		protected function render_look( $slug, $look, $in_use ) {
			$v     = $look['values'];
			$icons = isset( $v['acfw_show_icons'] ) ? 'no' !== $v['acfw_show_icons'] : true;
			?>
			<button type="button" class="acfw-look<?php echo $in_use ? ' is-in-use' : ''; ?>" data-look="<?php echo esc_attr( $slug ); ?>" aria-pressed="false" style="--acfw-look-accent:<?php echo esc_attr( $look['accent'] ); ?>;">
				<?php $this->mock( $v['acfw_menu_style'] ?? 'simple', $v['acfw_menu_position'] ?? 'vertical-left', $v['acfw_active_indicator'] ?? 'bar', $icons ); ?>
				<span class="acfw-look-body">
					<span class="acfw-look-name"><?php echo esc_html( $look['label'] ); ?></span>
					<span class="acfw-look-desc"><?php echo esc_html( $look['description'] ); ?></span>
				</span>
				<?php if ( $in_use ) : ?>
					<span class="acfw-look-tag"><?php esc_html_e( 'In use', 'my-account-dashboard-builder' ); ?></span>
				<?php endif; ?>
			</button>
			<?php
		}

		/**
		 * A miniature account menu, drawn in CSS ( .acfw-mock, shared with the
		 * look cards and the style picker ).
		 *
		 * @param string $style      Menu style slug.
		 * @param string $position   Menu position.
		 * @param string $indicator  Current page marker.
		 * @param bool   $icons      Show icons.
		 * @param int    $count      How many rows to draw.
		 */
		protected function mock( $style, $position, $indicator, $icons = true, $count = 4 ) {
			list( $layout, $preset ) = acfw_menu_style_resolve( $style );
			$is_top                  = 'tabs' === $layout || 'horizontal' === $position;
			$pos                     = $is_top ? 'top' : ( 'vertical-right' === $position ? 'right' : 'left' );
			$rows                    = array( 'fas fa-tachometer-alt', 'fas fa-shopping-cart', 'fas fa-download', 'fas fa-user', 'fas fa-map-marker-alt' );
			?>
			<span class="acfw-mock pos-<?php echo esc_attr( $pos ); ?> lay-<?php echo esc_attr( sanitize_html_class( $layout ) ); ?> pre-<?php echo esc_attr( sanitize_html_class( $preset ) ); ?> ind-<?php echo esc_attr( sanitize_html_class( $indicator ) ); ?>" aria-hidden="true">
				<span class="acfw-mock-nav">
					<?php for ( $i = 0; $i < $count; $i++ ) : ?>
						<span class="acfw-mock-item<?php echo 0 === $i ? ' is-active' : ''; ?>">
							<?php if ( $icons ) : ?>
								<i class="acfw-mock-icon <?php echo esc_attr( $rows[ $i ] ); ?>"></i>
							<?php endif; ?>
							<span class="acfw-mock-label"></span>
						</span>
					<?php endfor; ?>
				</span>
				<span class="acfw-mock-content">
					<span class="acfw-mock-line w1"></span>
					<span class="acfw-mock-line w2"></span>
					<span class="acfw-mock-line w3"></span>
				</span>
			</span>
			<?php
		}

		/**
		 * The Density control ( compact / comfortable / roomy ), which opens a
		 * "Fine-tune" disclosure holding the two spacing sliders.
		 *
		 * @param array $saved Saved values.
		 */
		protected function render_density( $saved ) {
			$current = 'custom';
			foreach ( self::densities() as $slug => $density ) {
				if ( (int) $saved['acfw_item_padding'] === $density['padding'] && (int) $saved['acfw_menu_gap'] === $density['gap'] ) {
					$current = $slug;
				}
			}
			?>
			<div class="acfw-sf acfw-sf-density">
				<span class="acfw-sf-label" id="acfw-density-label"><?php esc_html_e( 'Density', 'my-account-dashboard-builder' ); ?><?php echo $this->help( __( 'How much room each menu item gets. Fine-tune sets the item height and the gaps yourself.', 'my-account-dashboard-builder' ), 'acfw-density' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in help(). ?></span>
				<div class="acfw-segments" role="radiogroup" aria-labelledby="acfw-density-label" aria-describedby="acfw-density-tip">
					<?php foreach ( self::densities() as $slug => $density ) : ?>
						<label class="acfw-segment">
							<input type="radio" name="acfw_studio_density" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $current, $slug ); ?> />
							<span><?php echo esc_html( $density['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
			<details class="acfw-finetune"<?php echo 'custom' === $current ? ' open' : ''; ?>>
				<summary><?php esc_html_e( 'Fine-tune spacing', 'my-account-dashboard-builder' ); ?></summary>
				<div class="acfw-finetune-body">
			<?php
		}

		/**
		 * Design options switched on and off from the dashboard arrangement's
		 * rows ( stats, profile meter, tiles ) instead of controls of their own.
		 *
		 * @return string[]
		 */
		public static function layout_options() {
			$options = array();
			foreach ( acfw_dashboard_blocks() as $block ) {
				if ( ! empty( $block['option'] ) ) {
					$options[] = $block['option'];
				}
			}
			return $options;
		}

		/**
		 * One Studio control.
		 *
		 * @param string $key   Option name.
		 * @param array  $field Field definition.
		 * @param mixed  $value Saved value.
		 */
		protected function render_field( $key, $field, $value ) {
			$id     = 'acfw-sf-' . str_replace( '_', '-', substr( $key, 5 ) );
			$parent = $field['parent'] ?? '';
			$attrs  = sprintf(
				' data-key="%1$s" data-type="%2$s"%3$s',
				esc_attr( $key ),
				esc_attr( $field['type'] ),
				$parent ? ' data-parent="' . esc_attr( $parent ) . '"' : ''
			);
			$hint   = ! empty( $field['hint'] ) ? '<p class="acfw-sf-hint">' . esc_html( $field['hint'] ) . '</p>' : '';
			$help   = $this->help( $field['tip'] ?? '', $id );
			$descr  = $help ? ' aria-describedby="' . esc_attr( $id . '-tip' ) . '"' : '';

			switch ( $field['type'] ) {
				case 'toggle':
					?>
					<div class="acfw-sf acfw-sf-toggle"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
						<label class="acfw-sf-toggle-row" for="<?php echo esc_attr( $id ); ?>">
							<span class="acfw-sf-label"><?php echo esc_html( $field['label'] ); ?><?php echo $help; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in help(). ?></span>
							<span class="acfw-switch">
								<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" class="acfw-sf-input"<?php echo $descr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?> <?php checked( 'yes', $value ); ?> />
								<span class="acfw-switch-slider"></span>
							</span>
						</label>
						<?php echo $hint; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
					</div>
					<?php
					break;

				case 'color':
					?>
					<div class="acfw-sf acfw-sf-color"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
						<span class="acfw-sf-head">
							<label class="acfw-sf-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $help; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in help(). ?></label>
							<?php $acfw_reset = sprintf( /* translators: %s: control label. */ __( 'Reset %s', 'my-account-dashboard-builder' ), $field['label'] ); ?>
							<button type="button" class="acfw-sf-reset" title="<?php echo esc_attr( $acfw_reset ); ?>" aria-label="<?php echo esc_attr( $acfw_reset ); ?>"><span class="dashicons dashicons-image-rotate" aria-hidden="true"></span></button>
						</span>
						<span class="acfw-sf-color-row">
							<input type="color" class="acfw-sf-swatch" value="<?php echo esc_attr( $value ? $value : ( $field['default'] ? $field['default'] : '#ffffff' ) ); ?>" aria-label="<?php echo esc_attr( $field['label'] ); ?>" tabindex="-1" />
							<input type="text" id="<?php echo esc_attr( $id ); ?>" class="acfw-sf-input acfw-sf-hex"<?php echo $descr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?> value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $field['default'] ? $field['default'] : __( 'Default', 'my-account-dashboard-builder' ) ); ?>" spellcheck="false" autocomplete="off" maxlength="7" />
						</span>
						<?php echo $hint; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
						<?php if ( 'acfw_accent_color' === $key ) : ?>
							<p class="acfw-sf-contrast" aria-live="polite"></p>
						<?php endif; ?>
					</div>
					<?php
					break;

				case 'range':
					$step = isset( $field['step'] ) ? (int) $field['step'] : 1;
					?>
					<div class="acfw-sf acfw-sf-range"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
						<label class="acfw-sf-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $help; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in help(). ?></label>
						<span class="acfw-sf-range-row">
							<input type="range" id="<?php echo esc_attr( $id ); ?>" class="acfw-sf-input"<?php echo $descr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?> min="<?php echo esc_attr( $field['min'] ); ?>" max="<?php echo esc_attr( $field['max'] ); ?>" step="<?php echo esc_attr( $step ); ?>" value="<?php echo esc_attr( $value ); ?>" />
							<output class="acfw-sf-output" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $value . ( $field['unit'] ?? '' ) ); ?></output>
						</span>
						<?php echo $hint; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
					</div>
					<?php
					break;

				case 'choice':
					$visual = in_array( $key, array( 'acfw_menu_style', 'acfw_menu_position', 'acfw_active_indicator' ), true );
					?>
					<div class="acfw-sf acfw-sf-choice<?php echo $visual ? ' acfw-sf-visual' : ''; ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
						<span class="acfw-sf-label" id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $help; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in help(). ?></span>
						<div class="<?php echo $visual ? 'acfw-tiles-pick acfw-pick-' . esc_attr( str_replace( '_', '-', substr( $key, 5 ) ) ) : 'acfw-segments'; ?>" role="radiogroup" aria-labelledby="<?php echo esc_attr( $id ); ?>"<?php echo $descr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
							<?php foreach ( $field['choices'] as $choice => $choice_label ) : ?>
								<label class="<?php echo $visual ? 'acfw-tile-pick' : 'acfw-segment'; ?>">
									<input type="radio" class="acfw-sf-input" name="<?php echo esc_attr( 'studio_' . $key ); ?>" value="<?php echo esc_attr( $choice ); ?>" <?php checked( (string) $value, (string) $choice ); ?> />
									<?php if ( 'acfw_menu_style' === $key ) : ?>
										<?php $this->mock( $choice, 'vertical-left', 'bar', true, 3 ); ?>
									<?php elseif ( 'acfw_menu_position' === $key ) : ?>
										<span class="acfw-place acfw-place-<?php echo esc_attr( $choice ); ?>" aria-hidden="true"><i></i><b></b></span>
									<?php elseif ( 'acfw_active_indicator' === $key ) : ?>
										<span class="acfw-marker acfw-marker-<?php echo esc_attr( $choice ); ?>" aria-hidden="true"><i></i></span>
									<?php endif; ?>
									<span class="acfw-choice-text"><?php echo esc_html( $choice_label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
						<?php echo $hint; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
					</div>
					<?php
					break;

				case 'image':
					?>
					<div class="acfw-sf acfw-sf-image"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
						<label class="acfw-sf-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $help; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in help(). ?></label>
						<span class="acfw-sf-image-row">
							<img class="acfw-sf-thumb" src="<?php echo esc_url( $value ); ?>" alt=""<?php echo $value ? '' : ' hidden'; ?> />
							<input type="url" id="<?php echo esc_attr( $id ); ?>" class="acfw-sf-input"<?php echo $descr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?> value="<?php echo esc_attr( $value ); ?>" placeholder="https://" />
							<button type="button" class="button acfw-sf-media"><?php esc_html_e( 'Choose', 'my-account-dashboard-builder' ); ?></button>
						</span>
						<?php echo $hint; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
					</div>
					<?php
					break;

				case 'layout':
					$blocks = acfw_dashboard_blocks();
					?>
					<div class="acfw-sf acfw-sf-layout"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
						<span class="acfw-sf-label" id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $help; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in help(). ?></span>
						<ol class="acfw-layout-list" aria-labelledby="<?php echo esc_attr( $id ); ?>"<?php echo $descr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
							<?php
							foreach ( explode( ',', (string) $value ) as $token ) :
								$part = ltrim( $token, '-' );
								if ( ! isset( $blocks[ $part ] ) ) {
									continue;
								}
								$block  = $blocks[ $part ];
								$option = $block['option'] ?? '';
								$on     = '' !== $option ? 'yes' === ( $this->saved[ $option ] ?? 'no' ) : 0 !== strpos( $token, '-' );
								?>
								<li class="acfw-layout-row" data-part="<?php echo esc_attr( $part ); ?>">
									<span class="acfw-layout-handle dashicons dashicons-menu" aria-hidden="true"></span>
									<span class="acfw-layout-text">
										<span class="acfw-layout-name"><?php echo esc_html( $block['label'] ); ?></span>
										<?php if ( ! empty( $block['note'] ) ) : ?>
											<span class="acfw-layout-note"><?php echo esc_html( $block['note'] ); ?></span>
										<?php endif; ?>
									</span>
									<span class="acfw-layout-move">
										<?php /* translators: %s: dashboard part. */ ?>
										<button type="button" class="acfw-layout-up" aria-label="<?php echo esc_attr( sprintf( __( 'Move %s up', 'my-account-dashboard-builder' ), $block['label'] ) ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
										<?php /* translators: %s: dashboard part. */ ?>
										<button type="button" class="acfw-layout-down" aria-label="<?php echo esc_attr( sprintf( __( 'Move %s down', 'my-account-dashboard-builder' ), $block['label'] ) ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
									</span>
									<label class="acfw-switch">
										<?php /* translators: %s: dashboard part. */ ?>
										<input type="checkbox" class="acfw-layout-switch" data-option="<?php echo esc_attr( $option ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Show %s', 'my-account-dashboard-builder' ), $block['label'] ) ); ?>" <?php checked( $on ); ?> />
										<span class="acfw-switch-slider"></span>
									</label>
								</li>
							<?php endforeach; ?>
						</ol>
						<?php echo $hint; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
					</div>
					<?php
					break;

				case 'css':
					?>
					<div class="acfw-sf acfw-sf-css"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
						<label class="acfw-sf-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $help; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in help(). ?></label>
						<textarea id="<?php echo esc_attr( $id ); ?>" class="acfw-sf-input code"<?php echo $descr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?> rows="10" spellcheck="false"><?php echo esc_textarea( $value ); ?></textarea>
						<?php echo $hint; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
					</div>
					<?php
					break;

				case 'text':
				default:
					?>
					<div class="acfw-sf acfw-sf-text"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
						<label class="acfw-sf-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $help; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in help(). ?></label>
						<input type="text" id="<?php echo esc_attr( $id ); ?>" class="acfw-sf-input"<?php echo $descr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?> value="<?php echo esc_attr( $value ); ?>" />
						<?php echo $hint; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
					</div>
					<?php
			}
		}
	}
}
