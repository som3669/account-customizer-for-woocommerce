<?php
/**
 * How to use: a guide to every feature, opened from the header on any tab of
 * My Account. Short steps per feature, each with a link to where it is done,
 * a search, and the section for the tab being shown open first.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Guide' ) ) {

	/**
	 * The admin guide.
	 */
	class ACFW_Guide {

		/**
		 * A link to a tab of My Account.
		 *
		 * @param string $tab     Tab.
		 * @param string $section Settings section.
		 * @return string
		 */
		protected static function tab_url( $tab, $section = '' ) {
			$url = admin_url( 'admin.php?page=' . ACFW_Admin_Tab::PAGE . '&tab=' . $tab );
			return '' !== $section ? $url . '&section=' . $section : $url;
		}

		/**
		 * The guide's sections, in reading order.
		 *
		 * Each: title, icon ( dashicon ), tabs ( where it opens by default ),
		 * intro, steps ( may hold <strong>, <em>, <code>, <kbd> ), link ( url, label ).
		 *
		 * @return array[]
		 */
		public static function sections() {
			$sections = array(
				'start'    => array(
					'title' => __( 'Get started', 'my-account-dashboard-builder' ),
					'icon'  => 'flag',
					'tabs'  => array(),
					'intro' => __( 'Four steps to a finished account area. Nothing reaches customers until you save.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( '<strong>Arrange the menu</strong> on Menu Items: drag items, switch them on or off, click one to edit it.', 'my-account-dashboard-builder' ),
						__( '<strong>Give it your look</strong> in Design: pick a look, adjust colours, the menu and the dashboard, then <em>Save design</em>.', 'my-account-dashboard-builder' ),
						__( '<strong>Switch on what your store needs</strong> in Settings: Buy again, order tracking, returns, an address book, a privacy page.', 'my-account-dashboard-builder' ),
						__( '<strong>Check it as a customer</strong>: <em>⋯ → Preview</em>, then pick a customer in <em>View as</em>.', 'my-account-dashboard-builder' ),
					),
					'link'  => array( self::tab_url( 'items' ), __( 'Open Menu Items', 'my-account-dashboard-builder' ) ),
				),
				'items'    => array(
					'title' => __( 'Menu Items', 'my-account-dashboard-builder' ),
					'icon'  => 'menu-alt',
					'tabs'  => array( 'items' ),
					'intro' => __( 'The menu is drawn the way customers see it, in your saved design.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( '<strong>Move</strong> an item by dragging it, also into or out of a group. From the keyboard: <kbd>Alt</kbd> + arrow keys.', 'my-account-dashboard-builder' ),
						__( '<strong>Add to menu</strong> (under the menu) adds an <em>endpoint</em> (a page of its own inside My Account), a <em>group</em>, a <em>link</em> or a <em>page</em>.', 'my-account-dashboard-builder' ),
						__( '<strong>Click an item</strong> to edit its label, icon, badge, URL and the content shown on its page (Classic or Block editor, before, after or instead of WooCommerce’s).', 'my-account-dashboard-builder' ),
						__( '<strong>Visibility</strong> decides who sees it: roles, dates, products bought, at least / at most N orders, amount spent, days since the last order. The line under the item sums the rules up.', 'my-account-dashboard-builder' ),
						__( 'Each row has its <strong>on/off switch</strong>; Duplicate and Delete show on hover. Built-in items can be switched off but not deleted.', 'my-account-dashboard-builder' ),
						__( '<strong>Save menu</strong> saves every item and the order at once.', 'my-account-dashboard-builder' ),
					),
					'link'  => array( self::tab_url( 'items' ), __( 'Open Menu Items', 'my-account-dashboard-builder' ) ),
				),
				'groups'   => array(
					'title' => __( 'Menus for customer groups', 'my-account-dashboard-builder' ),
					'icon'  => 'groups',
					'tabs'  => array( 'items' ),
					'intro' => __( 'Give wholesale buyers, members or any role a menu of their own.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( 'On Menu Items, click <strong>+ Menu for a customer group</strong>, name it and tick the roles. It starts as a copy of the main menu.', 'my-account-dashboard-builder' ),
						__( 'Switch menus with <strong>Menu for</strong>. In a group menu, the row’s button takes an item out (it stays in the main menu); <em>Not in this menu</em> puts it back.', 'my-account-dashboard-builder' ),
						__( 'Items you add in a group menu also join the main menu, seen there only by that group’s roles.', 'my-account-dashboard-builder' ),
						__( 'The <strong>pencil</strong> renames the menu or changes its roles; the <strong>bin</strong> deletes it and its customers get the main menu again.', 'my-account-dashboard-builder' ),
					),
					'link'  => array( self::tab_url( 'items' ), __( 'Open Menu Items', 'my-account-dashboard-builder' ) ),
				),
				'design'   => array(
					'title' => __( 'Design Studio', 'my-account-dashboard-builder' ),
					'icon'  => 'art',
					'tabs'  => array( 'design' ),
					'intro' => __( 'Controls on the left, your real My Account page on the right.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( '<strong>Start from a look</strong>, then change the <em>Look</em>, <em>Menu</em>, <em>Profile card</em> and <em>Dashboard</em> controls. Hover <strong>ⓘ</strong> for what each does.', 'my-account-dashboard-builder' ),
						__( 'The preview follows as you go; <strong>Desktop / Tablet / Phone</strong> set its width.', 'my-account-dashboard-builder' ),
						__( '<strong>Arrange the dashboard</strong> (Dashboard): drag the greeting, heading, numbers, profile meter, tiles, tracking and Buy again into order, and switch any off.', 'my-account-dashboard-builder' ),
						__( '<strong>Status badges</strong> (Menu) show “1 to pay” on Orders, “1 open” on Returns and a dot where details are missing.', 'my-account-dashboard-builder' ),
						__( '<strong>Save design</strong> publishes; <em>Discard changes</em> goes back. <em>Save current as a look</em> keeps it for later.', 'my-account-dashboard-builder' ),
					),
					'link'  => array( self::tab_url( 'design' ), __( 'Open Design', 'my-account-dashboard-builder' ) ),
				),
				'viewas'   => array(
					'title' => __( 'View as a customer', 'my-account-dashboard-builder' ),
					'icon'  => 'visibility',
					'tabs'  => array( 'design' ),
					'intro' => __( 'See My Account exactly as one customer does, and why something is hidden from them.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( 'Open <strong>⋯ → Preview</strong> on any tab, or use <strong>View as</strong> above the Design preview.', 'my-account-dashboard-builder' ),
						__( 'Search a customer by name or email. You see their menu or group menu, their badges, offers and dashboard.', 'my-account-dashboard-builder' ),
						__( 'The bar at the bottom lists what is hidden from them and why, e.g. <em>Needs 3+ orders (has 1)</em>.', 'my-account-dashboard-builder' ),
						__( 'It only looks: buttons and forms are off and nothing changes for the customer. Staff accounts cannot be viewed as.', 'my-account-dashboard-builder' ),
					),
					'link'  => array( self::tab_url( 'design' ), __( 'Open Design', 'my-account-dashboard-builder' ) ),
				),
				'banners'  => array(
					'title' => __( 'Banners and personal offers', 'my-account-dashboard-builder' ),
					'icon'  => 'megaphone',
					'tabs'  => array( 'banners' ),
					'intro' => __( 'Promotions and messages at the top or bottom of account pages.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( '<strong>Add banner</strong>: a <em>Widget</em> (icon, title, text, count badge) or an <em>Image</em>, with an optional link.', 'my-account-dashboard-builder' ),
						__( '<strong>Visibility</strong> takes the same rules as menu items, plus show from / until.', 'my-account-dashboard-builder' ),
						__( '<strong>Offer</strong>: <em>Personal coupon</em> gives each customer who sees the banner a one-use code of their own. Put <code>{offer_code}</code>, <code>{offer_amount}</code> or <code>{offer_expiry}</code> in its text.', 'my-account-dashboard-builder' ),
						__( 'Show it on a page: Menu Items → the item → <strong>Content → Show banners</strong>, at the top or bottom.', 'my-account-dashboard-builder' ),
					),
					'link'  => array( self::tab_url( 'banners' ), __( 'Open Banners', 'my-account-dashboard-builder' ) ),
				),
				'fields'   => array(
					'title' => __( 'Customer fields', 'my-account-dashboard-builder' ),
					'icon'  => 'forms',
					'tabs'  => array( 'fields' ),
					'intro' => __( 'Ask for more when customers register or edit their account.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( '<strong>Add a field</strong>, give it a label (its key follows) and a type: text, date, dropdown, choices, tick box and more.', 'my-account-dashboard-builder' ),
						__( 'Tick where it shows: the <em>registration form</em>, <em>account details</em>, and the <em>order screen</em> in the admin.', 'my-account-dashboard-builder' ),
						__( 'Answers show on the user’s profile and work as smart tags, e.g. <code>{field_vat}</code>. <strong>Save fields</strong> when done.', 'my-account-dashboard-builder' ),
					),
					'link'  => array( self::tab_url( 'fields' ), __( 'Open Fields', 'my-account-dashboard-builder' ) ),
				),
				'returns'  => array(
					'title' => __( 'Cancelling and returns', 'my-account-dashboard-builder' ),
					'icon'  => 'undo',
					'tabs'  => array( 'returns' ),
					'intro' => __( 'Let customers cancel unshipped orders and ask to return items.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( 'Switch them on in <strong>Settings → Orders & privacy</strong>: how many hours customers may cancel for, how many days returns stay open, and the reasons they pick from.', 'my-account-dashboard-builder' ),
						__( 'Customers find <em>Cancel</em> and <em>Return items</em> on their orders. A return takes quantities, a reason, a comment and a photo; you are emailed.', 'my-account-dashboard-builder' ),
						__( 'Answer on the <strong>Returns</strong> tab: set a status, add a message, and the customer is emailed. Refunds are made from the order as usual.', 'my-account-dashboard-builder' ),
					),
					'link'  => array( self::tab_url( 'returns' ), __( 'Open Returns', 'my-account-dashboard-builder' ) ),
				),
				'extras'   => array(
					'title' => __( 'Address book and privacy page', 'my-account-dashboard-builder' ),
					'icon'  => 'shield',
					'tabs'  => array(),
					'intro' => __( 'Two self-service pages, switched on in Settings → Orders & privacy.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( '<strong>Address book</strong>: customers keep more addresses on the Addresses page, make one their shipping or billing address in a click, and pick one at the classic checkout.', 'my-account-dashboard-builder' ),
						__( '<strong>Privacy page</strong>: customers ask for a copy of their data or for it to be erased. WordPress emails them to confirm; you finish it under <em>Tools → Export / Erase Personal Data</em>.', 'my-account-dashboard-builder' ),
					),
					'link'  => array( self::tab_url( 'general', 'orders' ), __( 'Open Orders & privacy', 'my-account-dashboard-builder' ) ),
				),
				'insights' => array(
					'title' => __( 'Insights', 'my-account-dashboard-builder' ),
					'icon'  => 'chart-bar',
					'tabs'  => array( 'insights' ),
					'intro' => __( 'What customers use in their account area.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( 'Switch on <strong>Record usage</strong> in Settings → General. Only logged-in customers are counted, as daily totals.', 'my-account-dashboard-builder' ),
						__( 'See page views per day, pages nobody opens, banner clicks and click rate, and the codes and sales from each personal offer, for 7, 30 or 90 days.', 'my-account-dashboard-builder' ),
					),
					'link'  => array( self::tab_url( 'insights' ), __( 'Open Insights', 'my-account-dashboard-builder' ) ),
				),
				'anywhere' => array(
					'title' => __( 'The menu elsewhere on your site', 'my-account-dashboard-builder' ),
					'icon'  => 'admin-links',
					'tabs'  => array(),
					'intro' => __( 'Show the account menu outside My Account.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( '<strong>Appearance → Menus</strong> (classic themes): the <em>My Account</em> box adds a Log in / My account link, and one with the customer’s account pages as a dropdown.', 'my-account-dashboard-builder' ),
						__( 'Anywhere else: the <code>[acfw_account_menu]</code> shortcode, the <strong>Account Menu</strong> block or the Account Menu widget.', 'my-account-dashboard-builder' ),
					),
					'link'  => current_theme_supports( 'menus' ) ? array( admin_url( 'nav-menus.php' ), __( 'Open Menus', 'my-account-dashboard-builder' ) ) : array(),
				),
				'settings' => array(
					'title' => __( 'Settings and tools', 'my-account-dashboard-builder' ),
					'icon'  => 'admin-generic',
					'tabs'  => array( 'general', 'tools' ),
					'intro' => __( 'How the account area behaves, and your configuration as a whole.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( '<strong>General</strong>: AJAX navigation, the page customers land on, login and logout redirects, a guest message, a dashboard notice, Buy again, Recently viewed, Order tracking and Record usage.', 'my-account-dashboard-builder' ),
						__( '<strong>Import / Export</strong> copies the whole setup between sites as a JSON file.', 'my-account-dashboard-builder' ),
						__( '<strong>Presets & Reset</strong> applies a saved look, or puts everything back to the defaults.', 'my-account-dashboard-builder' ),
					),
					'link'  => array( self::tab_url( 'general' ), __( 'Open Settings', 'my-account-dashboard-builder' ) ),
				),
				'tags'     => array(
					'title' => __( 'Smart tags', 'my-account-dashboard-builder' ),
					'icon'  => 'tag',
					'tabs'  => array(),
					'intro' => __( 'Placeholders filled in for each customer: in page content, banners, the dashboard heading and badges.', 'my-account-dashboard-builder' ),
					'steps' => array(
						__( 'Names: <code>{first_name}</code>, <code>{last_name}</code>, <code>{display_name}</code>, <code>{user_email}</code>.', 'my-account-dashboard-builder' ),
						__( 'Numbers: <code>{order_count}</code>, <code>{download_count}</code>, <code>{total_spent}</code>, <code>{cart_count}</code>, <code>{points_balance}</code>.', 'my-account-dashboard-builder' ),
						__( 'More: <code>{member_since}</code>, <code>{last_login}</code>, <code>{billing_city}</code>, <code>{shop_url}</code>, and <code>{field_…}</code> for your customer fields.', 'my-account-dashboard-builder' ),
						__( 'In the Classic editor, the <strong>Add smart tags</strong> button beside Add Media inserts them.', 'my-account-dashboard-builder' ),
					),
					'link'  => array(),
				),
			);

			/**
			 * Filter the sections of the How to use guide.
			 *
			 * @param array[] $sections id => array{ title, icon, tabs, intro, steps, link }.
			 */
			return apply_filters( 'acfw_guide_sections', $sections );
		}

		/**
		 * The guide, closed until the header's How to use button opens it.
		 *
		 * @param string $tab Tab being shown.
		 */
		public static function render( $tab ) {
			$sections = self::sections();
			$open     = 'start';
			foreach ( $sections as $id => $section ) {
				if ( in_array( $tab, (array) ( $section['tabs'] ?? array() ), true ) ) {
					$open = $id;
					break;
				}
			}
			$allowed = array(
				'strong' => array(),
				'em'     => array(),
				'code'   => array(),
				'kbd'    => array(),
			);
			?>
			<div class="acfw-guide" id="acfw-guide" hidden>
				<div class="acfw-guide-panel" role="dialog" aria-modal="true" aria-labelledby="acfw-guide-title">
					<div class="acfw-guide-head">
						<span class="acfw-guide-mark dashicons dashicons-editor-help" aria-hidden="true"></span>
						<div class="acfw-guide-heading">
							<h2 id="acfw-guide-title"><?php esc_html_e( 'How to use', 'my-account-dashboard-builder' ); ?></h2>
							<p><?php esc_html_e( 'Every feature in a few steps, with a link to where it is done.', 'my-account-dashboard-builder' ); ?></p>
						</div>
						<button type="button" class="acfw-guide-close" data-acfw-guide-close aria-label="<?php esc_attr_e( 'Close the guide', 'my-account-dashboard-builder' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
					</div>
					<div class="acfw-guide-search">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<input type="search" class="acfw-guide-search-input" placeholder="<?php esc_attr_e( 'Search the guide, e.g. coupon', 'my-account-dashboard-builder' ); ?>" aria-label="<?php esc_attr_e( 'Search the guide', 'my-account-dashboard-builder' ); ?>" />
					</div>
					<div class="acfw-guide-body">
						<?php foreach ( $sections as $id => $section ) : ?>
							<details class="acfw-guide-section" data-guide="<?php echo esc_attr( $id ); ?>"<?php echo $open === $id ? ' open' : ''; ?>>
								<summary>
									<span class="acfw-guide-icon dashicons dashicons-<?php echo esc_attr( $section['icon'] ?? 'info-outline' ); ?>" aria-hidden="true"></span>
									<span class="acfw-guide-title"><?php echo esc_html( $section['title'] ); ?></span>
									<span class="acfw-guide-chevron dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
								</summary>
								<div class="acfw-guide-content">
									<?php if ( ! empty( $section['intro'] ) ) : ?>
										<p class="acfw-guide-intro"><?php echo esc_html( $section['intro'] ); ?></p>
									<?php endif; ?>
									<ol class="acfw-guide-steps">
										<?php foreach ( (array) $section['steps'] as $step ) : ?>
											<li><?php echo wp_kses( $step, $allowed ); ?></li>
										<?php endforeach; ?>
									</ol>
									<?php if ( ! empty( $section['link'][0] ) ) : ?>
										<a class="button acfw-guide-link" href="<?php echo esc_url( $section['link'][0] ); ?>"><?php echo esc_html( $section['link'][1] ); ?><span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span></a>
									<?php endif; ?>
								</div>
							</details>
						<?php endforeach; ?>
						<p class="acfw-guide-empty" hidden><?php esc_html_e( 'Nothing in the guide matches that. Try another word.', 'my-account-dashboard-builder' ); ?></p>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
