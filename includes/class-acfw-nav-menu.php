<?php
/**
 * Account links in the site's menus ( Appearance → Menus ).
 *
 * - WooCommerce's "WooCommerce endpoints" box lists custom endpoints by their
 *   menu label.
 * - A "My Account" box adds two links that change with the visitor:
 *   "Log in" / "My account", and "My account" with the customer's own
 *   account pages under it ( the same items, and rules, as their account menu ).
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Nav_Menu' ) ) {

	/**
	 * Site menu integration.
	 */
	class ACFW_Nav_Menu {

		/**
		 * URL of the Log in / My account link.
		 *
		 * @var string
		 */
		const ACCOUNT = '#acfw-account';

		/**
		 * URL of the My account link with the account pages under it.
		 *
		 * @var string
		 */
		const MENU = '#acfw-account-menu';

		/**
		 * Hook the menu screen and menu output.
		 */
		public function __construct() {
			add_filter( 'woocommerce_custom_nav_menu_items', array( $this, 'label_endpoints' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
			add_action( 'admin_head-nav-menus.php', array( $this, 'add_meta_box' ) );
			add_filter( 'wp_nav_menu_objects', array( $this, 'resolve' ), 20 );
		}

		/**
		 * Custom endpoints by their menu label in WooCommerce's endpoints box.
		 *
		 * @param array $items Endpoint key => title.
		 * @return array
		 */
		public function label_endpoints( $items ) {
			foreach ( acfw_flatten_items( ACFW()->items->get_items() ) as $key => $item ) {
				if ( 'endpoint' === ( $item['type'] ?? 'endpoint' ) && ! ACFW()->items->is_default( $key ) && ! empty( $item['label'] ) ) {
					$items[ $key ] = $item['label'];
				}
			}
			return $items;
		}

		/**
		 * Add the My Account box to Appearance → Menus.
		 */
		public function add_meta_box() {
			add_meta_box( 'acfw-account-nav', __( 'My Account', 'my-account-dashboard-builder' ), array( $this, 'meta_box' ), 'nav-menus', 'side', 'low' );
		}

		/**
		 * The links this box offers ( URL => title ).
		 *
		 * @return array
		 */
		protected static function links() {
			return array(
				self::ACCOUNT => __( 'Log in / My account', 'my-account-dashboard-builder' ),
				self::MENU    => __( 'My account, with its pages', 'my-account-dashboard-builder' ),
			);
		}

		/**
		 * The box: the same markup as the core link boxes, so "Add to menu" works.
		 */
		public function meta_box() {
			?>
			<div id="posttype-acfw-account" class="posttypediv">
				<p><?php esc_html_e( 'Links that change with the visitor: “Log in” for guests, their account for customers.', 'my-account-dashboard-builder' ); ?></p>
				<div id="tabs-panel-acfw-account" class="tabs-panel tabs-panel-active">
					<ul id="acfw-account-checklist" class="categorychecklist form-no-clear">
						<?php
						$i = -1;
						foreach ( self::links() as $url => $title ) :
							?>
							<li>
								<label class="menu-item-title"><input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-object-id]" value="<?php echo esc_attr( $i ); ?>" /> <?php echo esc_html( $title ); ?></label>
								<input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-type]" value="custom" />
								<input type="hidden" class="menu-item-title" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-title]" value="<?php echo esc_attr( $title ); ?>" />
								<input type="hidden" class="menu-item-url" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-url]" value="<?php echo esc_attr( $url ); ?>" />
								<input type="hidden" class="menu-item-classes" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-classes]" value="acfw-account-link" />
							</li>
							<?php
							--$i;
						endforeach;
						?>
					</ul>
				</div>
				<p class="button-controls wp-clearfix">
					<span class="add-to-menu">
						<button type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to menu', 'my-account-dashboard-builder' ); ?>" name="add-post-type-menu-item" id="submit-posttype-acfw-account"><?php esc_html_e( 'Add to menu', 'my-account-dashboard-builder' ); ?></button>
						<span class="spinner"></span>
					</span>
				</p>
			</div>
			<?php
		}

		/**
		 * Turn the two links into what the visitor should see.
		 *
		 * @param array $items Menu item objects, in order.
		 * @return array
		 */
		public function resolve( $items ) {
			if ( ! function_exists( 'wc_get_page_permalink' ) ) {
				return $items;
			}
			$account  = wc_get_page_permalink( 'myaccount' );
			$defaults = self::links();
			$next_id  = -1;
			$out      = array();

			foreach ( (array) $items as $item ) {
				$url = isset( $item->url ) ? (string) $item->url : '';
				if ( self::ACCOUNT !== $url && self::MENU !== $url ) {
					$out[] = $item;
					continue;
				}

				$item->url = $account;
				if ( ! is_user_logged_in() ) {
					$item->title = __( 'Log in', 'my-account-dashboard-builder' );
					$out[]       = $item;
					continue;
				}

				// A title the owner changed is kept; ours becomes "My account".
				if ( in_array( $item->title, $defaults, true ) ) {
					$item->title = __( 'My account', 'my-account-dashboard-builder' );
				}
				$out[] = $item;

				if ( self::MENU !== $url || ! ACFW()->frontend ) {
					continue;
				}
				$children = array();
				foreach ( acfw_flatten_items( ACFW()->frontend->visible_items() ) as $key => $child ) {
					if ( 'group' === ( $child['type'] ?? '' ) ) {
						continue;
					}
					$link                        = clone $item;
					$link->ID                    = $next_id;
					$link->db_id                 = $next_id;
					$link->object_id             = $next_id;
					$link->menu_item_parent      = (string) $item->db_id;
					$link->title                 = $child['label'];
					$link->url                   = acfw_item_url( (string) $key, $child );
					$link->target                = ! empty( $child['target_blank'] ) ? '_blank' : '';
					$link->classes               = array( 'acfw-account-link-child', 'acfw-account-' . sanitize_html_class( (string) $key ) );
					$link->current               = function_exists( 'is_account_page' ) && is_account_page() && acfw_is_current_item( (string) $key );
					$link->current_item_ancestor = false;
					$link->current_item_parent   = false;
					$children[]                  = $link;
					--$next_id;
				}
				if ( $children ) {
					$item->classes = array_merge( (array) $item->classes, array( 'menu-item-has-children' ) );
					$out           = array_merge( $out, $children );
				}
			}
			return $out;
		}
	}
}
