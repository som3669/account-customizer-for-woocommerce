<?php
/**
 * Abstract base class for an admin settings tab.
 *
 * Holds the shared UI helper methods used by the concrete tab classes and
 * declares the render/handle contract implemented by each tab.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Admin_Tab' ) ) {

	/**
	 * Base class every admin tab extends.
	 */
	abstract class ACFW_Admin_Tab {

		const PAGE  = 'acfw-settings';
		const NONCE = 'acfw_admin_action';

		/**
		 * Render the tab's content.
		 */
		abstract public function render();

		/**
		 * Handle a POST action for this tab.
		 *
		 * Tabs override this to process their own actions. The default is a
		 * no-op so tabs without save handlers can be iterated safely.
		 *
		 * @param string     $action Sanitized action slug.
		 * @param ACFW_Items $items  Menu items manager.
		 */
		public function handle( $action, $items ) {}

		/**
		 * Render a range slider control with a px value bubble.
		 *
		 * @param string $name    Option / field name.
		 * @param int    $current Current value.
		 * @param int    $min     Minimum.
		 * @param int    $max     Maximum.
		 */
		protected function slider( $name, $current, $min = 0, $max = 24 ) {
			$current = '' === $current || null === $current ? $min : (int) $current;
			printf(
				'<input type="range" name="%1$s" min="%2$d" max="%3$d" step="1" value="%4$d" oninput="this.nextElementSibling.textContent=this.value+\'px\'" /><span class="acfw-range-val">%4$dpx</span>',
				esc_attr( $name ),
				(int) $min,
				(int) $max,
				(int) $current
			);
		}

		/**
		 * Info tooltip icon markup for a field label.
		 *
		 * @param string $text Tooltip text.
		 * @return string
		 */
		protected function tip( $text ) {
			return ' <span class="acfw-tip dashicons dashicons-info-outline" title="' . esc_attr( $text ) . '"></span>';
		}

		/**
		 * Render a media uploader box ( dropzone-style: click / drag to pick from
		 * the media library, or paste a URL ) with live preview.
		 *
		 * @param string $name  Field name storing the image URL.
		 * @param string $value Current URL.
		 */
		protected function uploader( $name, $value ) {
			$has = ! empty( $value );
			?>
			<div class="acfw-uploader">
				<div class="acfw-uploader-box <?php echo $has ? 'has-image' : ''; ?>">
					<div class="acfw-uploader-empty">
						<span class="dashicons dashicons-upload"></span>
						<p><?php esc_html_e( 'Drag & drop or', 'my-account-customizer' ); ?> <button type="button" class="acfw-uploader-browse acfw-media-btn"><?php esc_html_e( 'upload a file', 'my-account-customizer' ); ?></button></p>
					</div>
					<div class="acfw-uploader-preview">
						<img class="acfw-media-preview" src="<?php echo esc_url( $value ); ?>" alt="" <?php echo $has ? '' : 'hidden'; ?> />
						<button type="button" class="acfw-uploader-remove" title="<?php esc_attr_e( 'Remove', 'my-account-customizer' ); ?>">&times;</button>
					</div>
				</div>
				<div class="acfw-media-row">
					<input type="url" name="<?php echo esc_attr( $name ); ?>" class="acfw-media-input" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'Paste image URL', 'my-account-customizer' ); ?>" />
					<button type="button" class="button acfw-media-btn"><span class="dashicons dashicons-admin-media"></span> <?php esc_html_e( 'Media library', 'my-account-customizer' ); ?></button>
				</div>
			</div>
			<?php
		}

		/**
		 * Round swatch color control with popover picker + alpha + hex input.
		 * Vanilla port of the reference plugin's banner colour control.
		 *
		 * @param string $name  Field name.
		 * @param string $value Current colour ( hex or rgba ).
		 * @param string $label Swatch caption.
		 */
		protected function color_control( $name, $value, $label = '' ) {
			$value = trim( (string) $value );
			?>
			<span class="acfw-swatch">
				<span class="acfw-bcp-root">
					<button type="button" class="acfw-bcp-control" aria-label="<?php echo esc_attr( $label ? $label : __( 'Select colour', 'my-account-customizer' ) ); ?>">
						<span class="acfw-bcp-swatch" style="--acfw-bcp-color: <?php echo esc_attr( $value ? $value : 'transparent' ); ?>;"></span>
					</button>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>" class="acfw-bcp-input" value="<?php echo esc_attr( $value ); ?>" />
				</span>
				<?php if ( $label ) : ?>
					<span class="acfw-swatch-label"><?php echo esc_html( $label ); ?></span>
				<?php endif; ?>
			</span>
			<?php
		}

		/**
		 * Render a visual image-radio control ( thumbnail option picker ).
		 *
		 * @param string $name    Option / field name.
		 * @param string $current Current value.
		 * @param array  $choices  value => array( 'label' => .., 'img' => file ).
		 * @param string $fallback Value used when nothing is stored.
		 */
		protected function image_radio( $name, $current, $choices, $fallback = '' ) {
			$current = ( '' === $current || null === $current ) ? $fallback : $current;
			echo '<div class="acfw-radio-group acfw-image-radio" role="radiogroup">';
			foreach ( $choices as $value => $choice ) {
				$id     = sanitize_html_class( $name . '-' . $value );
				$active = (string) $current === (string) $value;
				printf(
					'<label class="acfw-image-card%1$s" for="%2$s"><input type="radio" id="%2$s" name="%3$s" value="%4$s" %5$s /><img src="%6$s" alt="" /><span class="acfw-image-label">%7$s</span></label>',
					$active ? ' is-active' : '',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					checked( $current, $value, false ),
					esc_url( ACFW_ASSETS_URL . '/images/controls/' . $choice['img'] ),
					esc_html( $choice['label'] )
				);
			}
			echo '</div>';
		}

		/**
		 * Render a segmented buttonset control (styled radio group).
		 *
		 * @param string $name    Option / field name.
		 * @param string $current Current value.
		 * @param array  $choices  value => label pairs.
		 * @param string $fallback Value used when nothing is stored.
		 */
		protected function buttonset( $name, $current, $choices, $fallback = '' ) {
			$current = ( '' === $current || null === $current ) ? $fallback : $current;
			echo '<div class="acfw-radio-group" role="radiogroup">';
			foreach ( $choices as $value => $label ) {
				$active = (string) $current === (string) $value;
				printf(
					'<label class="acfw-radio-box%1$s"><input type="radio" name="%2$s" value="%3$s" %4$s /><span class="acfw-radio-dot"></span><span class="acfw-radio-text">%5$s</span></label>',
					$active ? ' is-active' : '',
					esc_attr( $name ),
					esc_attr( $value ),
					checked( $current, $value, false ),
					esc_html( $label )
				);
			}
			echo '</div>';
		}

		/**
		 * Build the icon markup for an item (uploaded image or dashicon).
		 *
		 * @param array  $item          Item options.
		 * @param string $wrapper_class Wrapper class.
		 * @return string
		 */
		protected function icon_markup( $item, $wrapper_class ) {
			$icon_url = $item['icon_url'] ?? '';
			$upload   = 'upload' === ( $item['icon_source'] ?? 'choose' );
			$icon     = ( ! $upload && ! empty( $item['icon'] ) ) ? $item['icon'] : '';
			if ( empty( $icon_url ) && '' === $icon ) {
				$type = $item['type'] ?? 'endpoint';
				$icon = acfw_default_type_icon( $type );
			}
			return acfw_icon_markup( $icon, $icon_url, $wrapper_class );
		}

		/**
		 * Curated list of dashicons offered in the icon picker.
		 *
		 * @return array
		 */
		protected function icon_choices() {
			$choices = array();
			foreach ( acfw_icon_list() as $class ) {
				// Derive a readable label from the fa-* name.
				$name              = preg_replace( '/^.*fa-/', '', $class );
				$choices[ $class ] = ucwords( str_replace( '-', ' ', $name ) );
			}
			return $choices;
		}
	}
}
