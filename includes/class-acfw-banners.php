<?php
/**
 * Banners model: store, query and render My Account banners.
 *
 * Storage:
 *  - acfw_banners : array keyed by slug of banner option arrays.
 * Assignment lives on each menu item ( banner_slug + banner_position ).
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Banners' ) ) {

	/**
	 * Manages reusable banners shown around endpoint content.
	 */
	class ACFW_Banners {

		const OPTION = 'acfw_banners';
		const TYPES  = array( 'widget', 'image' );

		/**
		 * Default option set for a banner.
		 *
		 * @return array
		 */
		public static function defaults() {
			return array(
				'type'          => 'widget',
				'title'         => '',
				'content'       => '',
				'image_url'     => '',
				'icon'          => '',
				'icon_source'   => 'choose', // choose | upload.
				'icon_url'      => '',
				'icon_width'    => 40,
				'widget_width'  => 250,
				'text_color'    => '#1d2327',
				'text_hover'    => '#111827',
				'bg_color'      => '#f3f6fb',
				'bg_hover'      => '#e9eefb',
				'border_color'  => '#e1e5ef',
				'border_hover'  => '#c7d2fe',
				'show_count'    => 'no',
				'link_type'     => 'none', // none | endpoint | external.
				'link_endpoint' => '',
				'link'          => '',
				'visibility'    => 'all', // all | roles.
				'roles'         => array(),
			);
		}

		/**
		 * Named color fields ( key => label ).
		 *
		 * @return array
		 */
		public static function color_fields() {
			return array(
				'text_color'   => __( 'Text', 'account-customizer-for-woocommerce' ),
				'text_hover'   => __( 'Text hover', 'account-customizer-for-woocommerce' ),
				'bg_color'     => __( 'Background', 'account-customizer-for-woocommerce' ),
				'bg_hover'     => __( 'Background hover', 'account-customizer-for-woocommerce' ),
				'border_color' => __( 'Borders', 'account-customizer-for-woocommerce' ),
				'border_hover' => __( 'Borders hover', 'account-customizer-for-woocommerce' ),
			);
		}

		/**
		 * Get all banners.
		 *
		 * @return array slug => options.
		 */
		public static function all() {
			$banners = get_option( self::OPTION, array() );
			return is_array( $banners ) ? $banners : array();
		}

		/**
		 * Get a single banner merged with defaults.
		 *
		 * @param string $slug Banner slug.
		 * @return array|null
		 */
		public static function get( $slug ) {
			$banners = self::all();
			if ( empty( $banners[ $slug ] ) ) {
				return null;
			}
			return wp_parse_args( $banners[ $slug ], self::defaults() );
		}

		/**
		 * Create or update a banner.
		 *
		 * @param string $slug Banner slug ( empty to generate from title ).
		 * @param array  $data Raw options.
		 * @return string Saved slug, or '' on failure.
		 */
		public static function save( $slug, $data ) {

			$title = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
			$slug  = $slug ? acfw_sanitize_key( $slug ) : acfw_sanitize_key( $title );

			if ( '' === $slug ) {
				return '';
			}

			$type      = isset( $data['type'] ) && in_array( $data['type'], self::TYPES, true ) ? $data['type'] : 'widget';
			$roles     = isset( $data['roles'] ) ? array_map( 'sanitize_key', (array) $data['roles'] ) : array();
			$link_type = isset( $data['link_type'] ) && in_array( $data['link_type'], array( 'none', 'endpoint', 'external' ), true ) ? $data['link_type'] : 'none';
			$defaults  = self::defaults();

			$clean = array(
				'type'          => $type,
				'title'         => $title,
				'content'       => isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '',
				'image_url'     => isset( $data['image_url'] ) ? esc_url_raw( $data['image_url'] ) : '',
				'icon'          => isset( $data['icon'] ) ? acfw_sanitize_icon( $data['icon'] ) : '',
				'icon_source'   => ( isset( $data['icon_source'] ) && 'upload' === $data['icon_source'] ) ? 'upload' : 'choose',
				'icon_url'      => isset( $data['icon_url'] ) ? esc_url_raw( $data['icon_url'] ) : '',
				'icon_width'    => isset( $data['icon_width'] ) ? min( 100, absint( $data['icon_width'] ) ) : 40,
				'widget_width'  => isset( $data['widget_width'] ) ? max( 200, min( 700, absint( $data['widget_width'] ) ) ) : 250,
				'text_color'    => self::hex( $data, 'text_color', $defaults ),
				'text_hover'    => self::hex( $data, 'text_hover', $defaults ),
				'bg_color'      => self::hex( $data, 'bg_color', $defaults ),
				'bg_hover'      => self::hex( $data, 'bg_hover', $defaults ),
				'border_color'  => self::hex( $data, 'border_color', $defaults ),
				'border_hover'  => self::hex( $data, 'border_hover', $defaults ),
				'show_count'    => ( isset( $data['show_count'] ) && 'yes' === $data['show_count'] ) ? 'yes' : 'no',
				'link_type'     => $link_type,
				'link_endpoint' => isset( $data['link_endpoint'] ) ? acfw_sanitize_key( $data['link_endpoint'] ) : '',
				'link'          => isset( $data['link'] ) ? esc_url_raw( $data['link'] ) : '',
				'visibility'    => empty( $roles ) ? 'all' : 'roles',
				'roles'         => $roles,
			);

			$banners          = self::all();
			$banners[ $slug ] = $clean;
			update_option( self::OPTION, $banners );

			return $slug;
		}

		/**
		 * Sanitize a hex field with fallback to its default.
		 *
		 * @param array  $data     Raw data.
		 * @param string $key      Field key.
		 * @param array  $defaults Defaults.
		 * @return string
		 */
		protected static function hex( $data, $key, $defaults ) {
			$val = isset( $data[ $key ] ) ? sanitize_hex_color( $data[ $key ] ) : '';
			return $val ? $val : $defaults[ $key ];
		}

		/**
		 * Delete a banner.
		 *
		 * @param string $slug Banner slug.
		 * @return bool
		 */
		public static function remove( $slug ) {
			$banners = self::all();
			if ( ! isset( $banners[ $slug ] ) ) {
				return false;
			}
			unset( $banners[ $slug ] );
			update_option( self::OPTION, $banners );
			return true;
		}

		/**
		 * Resolve a banner's link URL.
		 *
		 * @param array $banner Banner options.
		 * @return string
		 */
		protected static function link_url( $banner ) {
			if ( 'external' === $banner['link_type'] ) {
				return $banner['link'];
			}
			if ( 'endpoint' === $banner['link_type'] && $banner['link_endpoint'] && function_exists( 'wc_get_account_endpoint_url' ) ) {
				return wc_get_account_endpoint_url( $banner['link_endpoint'] );
			}
			return '';
		}

		/**
		 * Icon markup for a banner ( uploaded image or dashicon ).
		 *
		 * @param array $banner Banner options.
		 * @return string
		 */
		protected static function icon_markup( $banner ) {
			$w = absint( $banner['icon_width'] ) ? absint( $banner['icon_width'] ) : 40;
			if ( 'upload' === $banner['icon_source'] && $banner['icon_url'] ) {
				return sprintf( '<img class="acfw-banner-icon" src="%s" alt="" style="width:%dpx;height:auto;" />', esc_url( $banner['icon_url'] ), $w );
			}
			if ( $banner['icon'] && acfw_is_fa_icon( $banner['icon'] ) ) {
				return sprintf( '<i class="acfw-banner-icon %s" style="font-size:%dpx;"></i>', esc_attr( $banner['icon'] ), $w );
			}
			if ( $banner['icon'] ) {
				return sprintf( '<span class="acfw-banner-icon dashicons %s" style="font-size:%1$dpx;width:%1$dpx;height:%1$dpx;"></span>', esc_attr( $banner['icon'] ), $w ); // phpcs:ignore
			}
			return '';
		}

		/**
		 * Render a single banner by slug.
		 *
		 * @param string $slug Banner slug.
		 * @return string HTML markup ( empty string when hidden/missing ).
		 */
		public static function render( $slug ) {

			$banner = self::get( $slug );
			if ( null === $banner ) {
				return '';
			}

			// Role visibility.
			if ( 'roles' === $banner['visibility'] ) {
				if ( ! is_user_logged_in() ) {
					return '';
				}
				$user = wp_get_current_user();
				if ( ! array_intersect( (array) $banner['roles'], (array) $user->roles ) && ! current_user_can( 'administrator' ) ) {
					return '';
				}
			}

			$style = sprintf(
				'--acfw-b-text:%s;--acfw-b-text-hover:%s;--acfw-b-bg:%s;--acfw-b-bg-hover:%s;--acfw-b-border:%s;--acfw-b-border-hover:%s;',
				esc_attr( $banner['text_color'] ),
				esc_attr( $banner['text_hover'] ),
				esc_attr( $banner['bg_color'] ),
				esc_attr( $banner['bg_hover'] ),
				esc_attr( $banner['border_color'] ),
				esc_attr( $banner['border_hover'] )
			);
			if ( 'widget' === $banner['type'] && $banner['widget_width'] ) {
				$style .= 'max-width:' . absint( $banner['widget_width'] ) . 'px;';
			}

			$link  = self::link_url( $banner );
			$count = '';
			if ( 'yes' === $banner['show_count'] && is_user_logged_in() && function_exists( 'wc_get_customer_order_count' ) ) {
				$count = '<span class="acfw-banner-badge">' . esc_html( wc_get_customer_order_count( get_current_user_id() ) ) . '</span>';
			}

			ob_start();
			?>
			<div class="acfw-banner acfw-banner-<?php echo esc_attr( $banner['type'] ); ?>" style="<?php echo esc_attr( $style ); ?>">
				<?php if ( 'image' === $banner['type'] && $banner['image_url'] ) : ?>
					<?php if ( $link ) : ?>
						<a href="<?php echo esc_url( $link ); ?>"><img src="<?php echo esc_url( $banner['image_url'] ); ?>" alt="<?php echo esc_attr( $banner['title'] ); ?>" /></a>
					<?php else : ?>
						<img src="<?php echo esc_url( $banner['image_url'] ); ?>" alt="<?php echo esc_attr( $banner['title'] ); ?>" />
					<?php endif; ?>
				<?php else : ?>
					<div class="acfw-banner-inner">
						<?php echo self::icon_markup( $banner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<div class="acfw-banner-body">
							<?php if ( $banner['title'] ) : ?>
								<h3 class="acfw-banner-title"><?php echo esc_html( acfw_apply_smart_tags( $banner['title'] ) ); ?> <?php echo $count; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
							<?php endif; ?>
							<div class="acfw-banner-content"><?php echo wp_kses_post( wpautop( acfw_apply_smart_tags( $banner['content'] ) ) ); ?></div>
							<?php if ( $link ) : ?>
								<a class="acfw-banner-link" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Learn more', 'account-customizer-for-woocommerce' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php
			return ob_get_clean();
		}
	}
}
