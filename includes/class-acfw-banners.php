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
				'type'                => 'widget',
				'title'               => '',
				'content'             => '',
				'image_url'           => '',
				'icon'                => '',
				'icon_source'         => 'choose', // choose | upload.
				'icon_url'            => '',
				'icon_width'          => 40,
				'widget_width'        => 250,
				'text_color'          => '#1d2327',
				'text_hover'          => '#111827',
				'bg_color'            => '#f3f6fb',
				'bg_hover'            => '#e9eefb',
				'border_color'        => '#e1e5ef',
				'border_hover'        => '#c7d2fe',
				'show_count'          => 'no',
				'count_source'        => 'orders', // orders | downloads | cart | points.
				'link_type'           => 'none', // none | endpoint | external.
				'link_endpoint'       => '',
				'link'                => '',
				'link_text'           => '',
				'visibility'          => 'all', // all | roles.
				'roles'               => array(),
				'vis_from'            => '',
				'vis_to'              => '',
				// Order-based rules, shared with menu items.
				'vis_products'        => array(),
				'vis_min_orders'      => 0,
				'vis_max_orders'      => '',
				'vis_min_spent'       => 0,
				'vis_inactive_days'   => 0,
				// Personal offer ( widget banners ): a coupon of their own per customer.
				'offer'               => 'no',
				'offer_type'          => 'percent', // percent | fixed_cart.
				'offer_amount'        => 10,
				'offer_days'          => 14,       // 0 = no end.
				'offer_min'           => 0,
				'offer_free_shipping' => 'no',
				'offer_prefix'        => 'THANKS',
			);
		}

		/**
		 * Named color fields ( key => label ).
		 *
		 * @return array
		 */
		public static function color_fields() {
			return array(
				'text_color'   => __( 'Text', 'my-account-dashboard-builder' ),
				'text_hover'   => __( 'Text hover', 'my-account-dashboard-builder' ),
				'bg_color'     => __( 'Background', 'my-account-dashboard-builder' ),
				'bg_hover'     => __( 'Background hover', 'my-account-dashboard-builder' ),
				'border_color' => __( 'Borders', 'my-account-dashboard-builder' ),
				'border_hover' => __( 'Borders hover', 'my-account-dashboard-builder' ),
			);
		}

		/**
		 * What the item-count badge can show ( key => label ).
		 *
		 * @return array
		 */
		public static function count_sources() {
			return array(
				'orders'    => __( 'Orders', 'my-account-dashboard-builder' ),
				'downloads' => __( 'Downloads', 'my-account-dashboard-builder' ),
				'cart'      => __( 'Items in cart', 'my-account-dashboard-builder' ),
				'points'    => __( 'Reward points', 'my-account-dashboard-builder' ),
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

			if ( $slug ) {
				$slug = acfw_sanitize_key( $slug );
			} elseif ( '' !== $title ) {
				// A new banner: derive an ASCII slug from its name, and never take
				// one an existing banner already uses.
				$slug     = acfw_item_key_from_label( $title, 'banner' );
				$existing = array_keys( self::all() );
				$base     = $slug;
				$n        = 2;
				while ( in_array( $slug, $existing, true ) ) {
					$slug = $base . '-' . $n;
					++$n;
				}
			}

			if ( '' === (string) $slug ) {
				return '';
			}

			$type      = isset( $data['type'] ) && in_array( $data['type'], self::TYPES, true ) ? $data['type'] : 'widget';
			$roles     = isset( $data['roles'] ) ? array_map( 'sanitize_key', (array) $data['roles'] ) : array();
			$link_type = isset( $data['link_type'] ) && in_array( $data['link_type'], array( 'none', 'endpoint', 'external' ), true ) ? $data['link_type'] : 'none';
			$defaults  = self::defaults();

			$clean = array(
				'type'              => $type,
				'title'             => $title,
				'content'           => isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '',
				'image_url'         => isset( $data['image_url'] ) ? esc_url_raw( $data['image_url'] ) : '',
				'icon'              => isset( $data['icon'] ) ? acfw_sanitize_icon( $data['icon'] ) : '',
				'icon_source'       => ( isset( $data['icon_source'] ) && 'upload' === $data['icon_source'] ) ? 'upload' : 'choose',
				'icon_url'          => isset( $data['icon_url'] ) ? esc_url_raw( $data['icon_url'] ) : '',
				'icon_width'        => isset( $data['icon_width'] ) ? min( 100, absint( $data['icon_width'] ) ) : 40,
				'widget_width'      => isset( $data['widget_width'] ) ? max( 200, min( 700, absint( $data['widget_width'] ) ) ) : 250,
				'text_color'        => self::hex( $data, 'text_color', $defaults ),
				'text_hover'        => self::hex( $data, 'text_hover', $defaults ),
				'bg_color'          => self::hex( $data, 'bg_color', $defaults ),
				'bg_hover'          => self::hex( $data, 'bg_hover', $defaults ),
				'border_color'      => self::hex( $data, 'border_color', $defaults ),
				'border_hover'      => self::hex( $data, 'border_hover', $defaults ),
				'show_count'        => ( isset( $data['show_count'] ) && 'yes' === $data['show_count'] ) ? 'yes' : 'no',
				'count_source'      => ( isset( $data['count_source'] ) && array_key_exists( $data['count_source'], self::count_sources() ) ) ? $data['count_source'] : 'orders',
				'link_type'         => $link_type,
				'link_endpoint'     => isset( $data['link_endpoint'] ) ? acfw_sanitize_key( $data['link_endpoint'] ) : '',
				'link'              => isset( $data['link'] ) ? esc_url_raw( $data['link'] ) : '',
				'link_text'         => isset( $data['link_text'] ) ? sanitize_text_field( $data['link_text'] ) : '',
				'visibility'        => empty( $roles ) ? 'all' : 'roles',
				'roles'             => $roles,
				'vis_from'          => isset( $data['vis_from'] ) ? preg_replace( '/[^0-9-]/', '', (string) $data['vis_from'] ) : '',
				'vis_to'            => isset( $data['vis_to'] ) ? preg_replace( '/[^0-9-]/', '', (string) $data['vis_to'] ) : '',
				'vis_products'      => acfw_parse_id_list( $data['vis_products'] ?? array() ),
				'vis_min_orders'    => absint( $data['vis_min_orders'] ?? 0 ),
				'vis_max_orders'    => acfw_sanitize_max_orders( $data['vis_max_orders'] ?? '' ),
				'vis_min_spent'     => self::money( $data['vis_min_spent'] ?? 0 ),
				'vis_inactive_days' => absint( $data['vis_inactive_days'] ?? 0 ),
			);

			$offer_type = ( isset( $data['offer_type'] ) && 'fixed_cart' === $data['offer_type'] ) ? 'fixed_cart' : 'percent';
			$amount     = self::money( $data['offer_amount'] ?? 0 );
			$clean     += array(
				'offer'               => ( 'widget' === $type && isset( $data['offer'] ) && 'yes' === $data['offer'] ) ? 'yes' : 'no',
				'offer_type'          => $offer_type,
				'offer_amount'        => 'percent' === $offer_type ? min( 100, $amount ) : $amount,
				'offer_days'          => min( 365, absint( $data['offer_days'] ?? 14 ) ),
				'offer_min'           => self::money( $data['offer_min'] ?? 0 ),
				'offer_free_shipping' => ( isset( $data['offer_free_shipping'] ) && 'yes' === $data['offer_free_shipping'] ) ? 'yes' : 'no',
				'offer_prefix'        => acfw_offer_prefix( $data['offer_prefix'] ?? '' ),
			);

			$banners          = self::all();
			$banners[ $slug ] = $clean;
			update_option( self::OPTION, $banners );

			return $slug;
		}

		/**
		 * A money amount in the store's decimal format, zero or more.
		 *
		 * @param mixed $raw Raw value.
		 * @return float
		 */
		protected static function money( $raw ) {
			$raw = is_scalar( $raw ) ? (string) $raw : '0';
			$raw = function_exists( 'wc_format_decimal' ) ? wc_format_decimal( $raw ) : $raw;
			return max( 0, (float) $raw );
		}

		/**
		 * Sanitize a colour field ( hex or rgb/rgba ) with fallback to its default.
		 *
		 * @param array  $data     Raw data.
		 * @param string $key      Field key.
		 * @param array  $defaults Defaults.
		 * @return string
		 */
		protected static function hex( $data, $key, $defaults ) {
			$val = isset( $data[ $key ] ) ? acfw_sanitize_color( $data[ $key ] ) : '';
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
		 * The number a banner's count badge shows for the current customer.
		 *
		 * @param string $source orders | downloads | cart | points.
		 * @return string Empty when there is nothing to count.
		 */
		protected static function badge_count( $source ) {
			$uid = get_current_user_id();
			switch ( $source ) {
				case 'downloads':
					$count = acfw_endpoint_count( 'downloads', $uid );
					break;
				case 'cart':
					$count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : null;
					break;
				case 'points':
					$count = acfw_points_balance( $uid );
					break;
				default:
					$count = acfw_endpoint_count( 'orders', $uid );
			}
			return null === $count ? '' : (string) $count;
		}

		/**
		 * A banner's visibility rules, in the shape acfw_visibility_passes() reads.
		 *
		 * @param array $banner Banner.
		 * @return array
		 */
		public static function rules( $banner ) {
			$banner = wp_parse_args( (array) $banner, self::defaults() );
			return array(
				'visibility'        => $banner['visibility'],
				'usr_roles'         => (array) $banner['roles'],
				'vis_from'          => $banner['vis_from'],
				'vis_to'            => $banner['vis_to'],
				'vis_products'      => (array) $banner['vis_products'],
				'vis_min_orders'    => $banner['vis_min_orders'],
				'vis_max_orders'    => $banner['vis_max_orders'],
				'vis_min_spent'     => $banner['vis_min_spent'],
				'vis_inactive_days' => $banner['vis_inactive_days'],
			);
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

			// The same rules as menu items: roles, dates, purchases, order history.
			$rules       = self::rules( $banner );
			$needs_login = 'roles' === $banner['visibility'] || acfw_rule_product_ids( $rules ) || $banner['vis_min_orders'] || null !== acfw_rule_max_orders( $rules ) || $banner['vis_min_spent'] || $banner['vis_inactive_days'];
			$visible     = ( ! $needs_login || is_user_logged_in() ) && acfw_visibility_passes( $rules );
			if ( ! apply_filters( 'acfw_banner_is_visible', $visible, $banner, $slug ) ) {
				return '';
			}

			// A personal offer shows only while the customer's code is unused.
			$offer = null;
			if ( 'widget' === $banner['type'] && 'yes' === $banner['offer'] && class_exists( 'ACFW_Offers' ) ) {
				$offer = ACFW_Offers::for_viewer( $slug, $banner );
				if ( null === $offer ) {
					return '';
				}
			}

			if ( class_exists( 'ACFW_Stats' ) && ACFW_Stats::counts_visitor() ) {
				ACFW_Stats::bump( 'banner_view', $slug );
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
			if ( 'yes' === $banner['show_count'] && is_user_logged_in() ) {
				$number = self::badge_count( $banner['count_source'] );
				if ( '' !== $number ) {
					$count = '<span class="acfw-banner-badge">' . esc_html( $number ) . '</span>';
				}
			}
			$link_text = '' !== (string) $banner['link_text']
				? ACFW_I18n::translate( 'banner_' . $slug . '_link_text', $banner['link_text'] )
				: __( 'Learn more', 'my-account-dashboard-builder' );

			ob_start();
			?>
			<div class="acfw-banner acfw-banner-<?php echo esc_attr( $banner['type'] ); ?><?php echo $offer ? ' acfw-banner-offer' : ''; ?>" data-acfw-banner="<?php echo esc_attr( $slug ); ?>" style="<?php echo esc_attr( $style ); ?>">
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
								<h3 class="acfw-banner-title"><?php echo esc_html( acfw_apply_smart_tags( ACFW_I18n::translate( 'banner_' . $slug . '_title', $banner['title'] ), null, false ) ); ?> <?php echo $count; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
							<?php endif; ?>
							<?php $acfw_text = ACFW_I18n::translate( 'banner_' . $slug . '_content', $banner['content'] ); ?>
							<?php $acfw_text = $offer ? strtr( $acfw_text, $offer['vars'] ) : $acfw_text; ?>
							<div class="acfw-banner-content"><?php echo wp_kses_post( wpautop( acfw_apply_smart_tags( $acfw_text ) ) ); ?></div>
							<?php
							if ( $offer ) {
								echo $offer['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in ACFW_Offers::markup().
							}
							?>
							<?php if ( $link ) : ?>
								<a class="acfw-banner-link" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $link_text ); ?></a>
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
