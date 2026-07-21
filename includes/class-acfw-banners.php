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
				'type'       => 'widget',
				'title'      => '',
				'content'    => '',
				'image_url'  => '',
				'link'       => '',
				'bg_color'   => '#f3f6fb',
				'text_color' => '#1d2327',
				'visibility' => 'all', // all | roles.
				'roles'      => array(),
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

			$type  = isset( $data['type'] ) && in_array( $data['type'], self::TYPES, true ) ? $data['type'] : 'widget';
			$roles = isset( $data['roles'] ) ? array_map( 'sanitize_key', (array) $data['roles'] ) : array();

			$clean = array(
				'type'       => $type,
				'title'      => $title,
				'content'    => isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '',
				'image_url'  => isset( $data['image_url'] ) ? esc_url_raw( $data['image_url'] ) : '',
				'link'       => isset( $data['link'] ) ? esc_url_raw( $data['link'] ) : '',
				'bg_color'   => isset( $data['bg_color'] ) ? sanitize_hex_color( $data['bg_color'] ) : '#f3f6fb',
				'text_color' => isset( $data['text_color'] ) ? sanitize_hex_color( $data['text_color'] ) : '#1d2327',
				'visibility' => empty( $roles ) ? 'all' : 'roles',
				'roles'      => $roles,
			);

			$banners          = self::all();
			$banners[ $slug ] = $clean;
			update_option( self::OPTION, $banners );

			return $slug;
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
				'background:%s;color:%s;',
				esc_attr( $banner['bg_color'] ? $banner['bg_color'] : '#f3f6fb' ),
				esc_attr( $banner['text_color'] ? $banner['text_color'] : '#1d2327' )
			);

			ob_start();
			?>
			<div class="acfw-banner acfw-banner-<?php echo esc_attr( $banner['type'] ); ?>" style="<?php echo esc_attr( $style ); ?>">
				<?php if ( 'image' === $banner['type'] && $banner['image_url'] ) : ?>
					<?php if ( $banner['link'] ) : ?>
						<a href="<?php echo esc_url( $banner['link'] ); ?>"><img src="<?php echo esc_url( $banner['image_url'] ); ?>" alt="<?php echo esc_attr( $banner['title'] ); ?>" /></a>
					<?php else : ?>
						<img src="<?php echo esc_url( $banner['image_url'] ); ?>" alt="<?php echo esc_attr( $banner['title'] ); ?>" />
					<?php endif; ?>
				<?php else : ?>
					<?php if ( $banner['title'] ) : ?>
						<h3 class="acfw-banner-title"><?php echo esc_html( $banner['title'] ); ?></h3>
					<?php endif; ?>
					<div class="acfw-banner-content"><?php echo wp_kses_post( wpautop( $banner['content'] ) ); ?></div>
					<?php if ( $banner['link'] ) : ?>
						<a class="acfw-banner-link" href="<?php echo esc_url( $banner['link'] ); ?>"><?php esc_html_e( 'Learn more', 'account-customizer-for-woocommerce' ); ?></a>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<?php
			return ob_get_clean();
		}
	}
}
