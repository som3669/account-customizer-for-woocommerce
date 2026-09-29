<?php
/**
 * Design Studio: the registry of design options, the per-user preview draft,
 * the Studio's AJAX endpoints and the front-end preview mode.
 *
 * Every design option is described once in fields(): its group, control type,
 * default, bounds and whether the preview can apply it without a reload. The
 * Studio renders from it, sanitises through it, and the preview reads the
 * draft through it.
 *
 * Preview: while an admin edits, the Studio keeps their unsaved values in a
 * transient. The account page opened with ?acfw_preview=1 by that admin reads
 * those values instead of the saved options, so the iframe shows the draft.
 * Nobody else is affected, and nothing is stored until "Save design".
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Design' ) ) {

	/**
	 * Design options, draft storage and preview.
	 */
	class ACFW_Design {

		/**
		 * AJAX nonce action.
		 *
		 * @var string
		 */
		const NONCE = 'acfw_design';

		/**
		 * Query arg that puts the account page in preview mode.
		 *
		 * @var string
		 */
		const PREVIEW_ARG = 'acfw_preview';

		/**
		 * Hook the AJAX endpoints and the preview mode.
		 */
		public function __construct() {
			add_action( 'wp_ajax_acfw_design_draft', array( $this, 'ajax_draft' ) );
			add_action( 'wp_ajax_acfw_design_save', array( $this, 'ajax_save' ) );
			add_action( 'wp_ajax_acfw_design_discard', array( $this, 'ajax_discard' ) );
			add_action( 'wp_ajax_acfw_design_save_look', array( $this, 'ajax_save_look' ) );
			add_action( 'init', array( $this, 'maybe_preview' ), 1 );
		}

		/**
		 * Every design option, keyed by option name.
		 *
		 * Keys per field: group, type ( color | toggle | choice | range | text |
		 * css | image ), label, default, and where relevant choices, min, max,
		 * step, hint. `live` is true when the preview can apply the value
		 * without reloading the page. `tip` ( from tips() ) is the tooltip on
		 * the control's label.
		 *
		 * @return array
		 */
		public static function fields() {
			$toggle = function ( $group, $label, $default_value, $live = false, $hint = '' ) {
				return array(
					'group'   => $group,
					'type'    => 'toggle',
					'label'   => $label,
					'default' => $default_value,
					'live'    => $live,
					'hint'    => $hint,
				);
			};

			$fields = array(
				// ---- Look: colour, type and shape, across the menu and widgets.
				'acfw_accent_color'      => array(
					'group'   => 'look',
					'type'    => 'color',
					'label'   => __( 'Accent', 'my-account-dashboard-builder' ),
					'default' => '#2563eb',
					'live'    => true,
					'hint'    => __( 'Icons, the current page, badges and links.', 'my-account-dashboard-builder' ),
				),
				'acfw_text_color'        => array(
					'group'   => 'look',
					'type'    => 'color',
					'label'   => __( 'Text', 'my-account-dashboard-builder' ),
					'default' => '#383838',
					'live'    => true,
					'hint'    => __( 'Left at the default, the menu keeps your theme’s text colour.', 'my-account-dashboard-builder' ),
				),
				'acfw_color_scheme'      => array(
					'group'   => 'look',
					'type'    => 'choice',
					'label'   => __( 'Colour scheme', 'my-account-dashboard-builder' ),
					'default' => 'light',
					'live'    => true,
					'choices' => array(
						'light' => __( 'Light', 'my-account-dashboard-builder' ),
						'dark'  => __( 'Dark', 'my-account-dashboard-builder' ),
						'auto'  => __( 'Follow device', 'my-account-dashboard-builder' ),
					),
				),
				'acfw_font_family'       => array(
					'group'   => 'look',
					'type'    => 'choice',
					'label'   => __( 'Typeface', 'my-account-dashboard-builder' ),
					'default' => 'inherit',
					'live'    => true,
					'choices' => array(
						'inherit' => __( 'Theme', 'my-account-dashboard-builder' ),
						'system'  => __( 'System', 'my-account-dashboard-builder' ),
						'serif'   => __( 'Serif', 'my-account-dashboard-builder' ),
						'mono'    => __( 'Mono', 'my-account-dashboard-builder' ),
					),
				),
				'acfw_font_size'         => array(
					'group'   => 'look',
					'type'    => 'range',
					'label'   => __( 'Text size', 'my-account-dashboard-builder' ),
					'default' => 15,
					'min'     => 11,
					'max'     => 22,
					'unit'    => 'px',
					'live'    => true,
				),
				'acfw_font_weight'       => array(
					'group'   => 'look',
					'type'    => 'choice',
					'label'   => __( 'Weight', 'my-account-dashboard-builder' ),
					'default' => '500',
					'live'    => true,
					'choices' => array(
						'400' => __( 'Regular', 'my-account-dashboard-builder' ),
						'500' => __( 'Medium', 'my-account-dashboard-builder' ),
						'600' => __( 'Bold', 'my-account-dashboard-builder' ),
					),
				),
				'acfw_menu_radius'       => array(
					'group'   => 'look',
					'type'    => 'range',
					'label'   => __( 'Roundness', 'my-account-dashboard-builder' ),
					'default' => 8,
					'min'     => 0,
					'max'     => 24,
					'unit'    => 'px',
					'live'    => true,
				),
				'acfw_item_padding'      => array(
					'group'   => 'look',
					'type'    => 'range',
					'label'   => __( 'Item height', 'my-account-dashboard-builder' ),
					'default' => 11,
					'min'     => 4,
					'max'     => 28,
					'unit'    => 'px',
					'live'    => true,
				),
				'acfw_menu_gap'          => array(
					'group'   => 'look',
					'type'    => 'range',
					'label'   => __( 'Space between items', 'my-account-dashboard-builder' ),
					'default' => 4,
					'min'     => 0,
					'max'     => 24,
					'unit'    => 'px',
					'live'    => true,
				),

				// ---- Menu: its shape, and how items react.
				'acfw_menu_style'        => array(
					'group'   => 'menu',
					'type'    => 'choice',
					'label'   => __( 'Style', 'my-account-dashboard-builder' ),
					'default' => 'simple',
					'live'    => true,
					'choices' => acfw_menu_styles(),
				),
				'acfw_menu_position'     => array(
					'group'   => 'menu',
					'type'    => 'choice',
					'label'   => __( 'Placement', 'my-account-dashboard-builder' ),
					'default' => 'vertical-left',
					'live'    => true,
					'choices' => array(
						'vertical-left'  => __( 'Left of the content', 'my-account-dashboard-builder' ),
						'vertical-right' => __( 'Right of the content', 'my-account-dashboard-builder' ),
						'horizontal'     => __( 'Above the content', 'my-account-dashboard-builder' ),
					),
				),
				'acfw_active_indicator'  => array(
					'group'   => 'menu',
					'type'    => 'choice',
					'label'   => __( 'Current page marker', 'my-account-dashboard-builder' ),
					'default' => 'bar',
					'live'    => true,
					'choices' => array(
						'bar'       => __( 'Bar', 'my-account-dashboard-builder' ),
						'underline' => __( 'Underline', 'my-account-dashboard-builder' ),
						'dot'       => __( 'Dot', 'my-account-dashboard-builder' ),
						'none'      => __( 'Colour only', 'my-account-dashboard-builder' ),
					),
				),
				'acfw_hover_anim'        => array(
					'group'   => 'menu',
					'type'    => 'choice',
					'label'   => __( 'On hover', 'my-account-dashboard-builder' ),
					'default' => 'none',
					'live'    => true,
					'choices' => array(
						'none'  => __( 'Tint only', 'my-account-dashboard-builder' ),
						'slide' => __( 'Nudge', 'my-account-dashboard-builder' ),
						'grow'  => __( 'Lift', 'my-account-dashboard-builder' ),
					),
				),
				'acfw_active_color'      => array(
					'group'   => 'menu',
					'type'    => 'color',
					'label'   => __( 'Current page colour', 'my-account-dashboard-builder' ),
					'default' => '',
					'live'    => true,
					'hint'    => __( 'Empty uses the accent.', 'my-account-dashboard-builder' ),
				),
				'acfw_menu_bg'           => array(
					'group'   => 'menu',
					'type'    => 'color',
					'label'   => __( 'Item background', 'my-account-dashboard-builder' ),
					'default' => '',
					'live'    => true,
				),
				'acfw_hover_bg'          => array(
					'group'   => 'menu',
					'type'    => 'color',
					'label'   => __( 'Hover background', 'my-account-dashboard-builder' ),
					'default' => '',
					'live'    => true,
					'hint'    => __( 'Empty uses a tint of the accent.', 'my-account-dashboard-builder' ),
				),
				'acfw_show_icons'        => $toggle( 'menu', __( 'Icons', 'my-account-dashboard-builder' ), 'yes', true ),
				'acfw_show_counts'       => $toggle( 'menu', __( 'Order and download counts', 'my-account-dashboard-builder' ), 'yes' ),
				'acfw_menu_search'       => $toggle( 'menu', __( 'Search box', 'my-account-dashboard-builder' ), 'no' ),
				'acfw_pin_enable'        => $toggle( 'menu', __( 'Let customers pin favourites', 'my-account-dashboard-builder' ), 'no' ),
				'acfw_collapsible'       => $toggle( 'menu', __( 'Collapse to icons', 'my-account-dashboard-builder' ), 'no', false, __( 'A button that shrinks the menu to an icon rail.', 'my-account-dashboard-builder' ) ),
				'acfw_sticky_menu'       => $toggle( 'menu', __( 'Stay in view on scroll', 'my-account-dashboard-builder' ), 'no', true ),
				'acfw_status_badges'     => $toggle( 'menu', __( 'Status badges', 'my-account-dashboard-builder' ), 'yes', false, __( 'Orders to pay, open returns, and a dot where details are missing.', 'my-account-dashboard-builder' ) ),
				'acfw_group_open'        => $toggle( 'menu', __( 'Open every group', 'my-account-dashboard-builder' ), 'no' ),
				'acfw_logout_confirm'    => $toggle( 'menu', __( 'Ask before logging out', 'my-account-dashboard-builder' ), 'no' ),

				// ---- Profile card: the avatar block above the menu.
				'acfw_avatar_enable'     => $toggle( 'profile', __( 'Show the profile card', 'my-account-dashboard-builder' ), 'no' ),
				'acfw_avatar_image'      => array(
					'group'   => 'profile',
					'type'    => 'image',
					'label'   => __( 'Default picture', 'my-account-dashboard-builder' ),
					'default' => '',
					'live'    => false,
					'hint'    => __( 'Shown until a customer uploads their own. Empty uses Gravatar.', 'my-account-dashboard-builder' ),
				),
				'acfw_avatar_shape'      => array(
					'group'   => 'profile',
					'type'    => 'choice',
					'label'   => __( 'Picture shape', 'my-account-dashboard-builder' ),
					'default' => 'circle',
					'live'    => false,
					'choices' => array(
						'circle' => __( 'Round', 'my-account-dashboard-builder' ),
						'square' => __( 'Square', 'my-account-dashboard-builder' ),
					),
				),
				'acfw_avatar_align'      => array(
					'group'   => 'profile',
					'type'    => 'choice',
					'label'   => __( 'Alignment', 'my-account-dashboard-builder' ),
					'default' => 'center',
					'live'    => false,
					'choices' => array(
						'left'   => __( 'Start', 'my-account-dashboard-builder' ),
						'center' => __( 'Centre', 'my-account-dashboard-builder' ),
						'right'  => __( 'End', 'my-account-dashboard-builder' ),
					),
				),
				'acfw_avatar_size'       => array(
					'group'   => 'profile',
					'type'    => 'range',
					'label'   => __( 'Picture size', 'my-account-dashboard-builder' ),
					'default' => 72,
					'min'     => 32,
					'max'     => 160,
					'unit'    => 'px',
					'live'    => true,
				),
				'acfw_avatar_show_name'  => $toggle( 'profile', __( 'Name', 'my-account-dashboard-builder' ), 'yes' ),
				'acfw_avatar_show_role'  => $toggle( 'profile', __( 'Role', 'my-account-dashboard-builder' ), 'no' ),
				'acfw_avatar_upload'     => $toggle( 'profile', __( 'Customers can upload a picture', 'my-account-dashboard-builder' ), 'no' ),
				'acfw_avatar_upload_max' => array(
					'group'   => 'profile',
					'type'    => 'range',
					'label'   => __( 'Largest upload', 'my-account-dashboard-builder' ),
					'default' => 2048,
					'min'     => 256,
					'max'     => 8192,
					'step'    => 256,
					'unit'    => 'KB',
					'live'    => false,
				),

				// ---- Dashboard: what the first page of My Account shows.
				'acfw_dashboard_title'   => array(
					'group'   => 'dashboard',
					'type'    => 'text',
					'label'   => __( 'Heading', 'my-account-dashboard-builder' ),
					'default' => '',
					'live'    => false,
					'hint'    => __( 'Smart tags work, e.g. Welcome back, {first_name}!', 'my-account-dashboard-builder' ),
				),
				'acfw_dashboard_align'   => array(
					'group'   => 'dashboard',
					'type'    => 'choice',
					'label'   => __( 'Alignment', 'my-account-dashboard-builder' ),
					'default' => 'left',
					'live'    => false,
					'choices' => array(
						'left'   => __( 'Start', 'my-account-dashboard-builder' ),
						'center' => __( 'Centre', 'my-account-dashboard-builder' ),
						'right'  => __( 'End', 'my-account-dashboard-builder' ),
					),
				),
				'acfw_dashboard_layout'  => array(
					'group'   => 'dashboard',
					'type'    => 'layout',
					'label'   => __( 'Arrange the dashboard', 'my-account-dashboard-builder' ),
					'default' => acfw_sanitize_dashboard_layout( '' ),
					'live'    => true,
					'hint'    => __( 'Drag the parts into the order customers see them, or use the arrows.', 'my-account-dashboard-builder' ),
				),
				'acfw_dashboard_stats'   => $toggle( 'dashboard', __( 'Account numbers', 'my-account-dashboard-builder' ), 'no', false, __( 'Cards with the customer’s orders, spend and downloads.', 'my-account-dashboard-builder' ) ),
				'acfw_stat_orders'       => $toggle( 'dashboard', __( 'Total orders', 'my-account-dashboard-builder' ), 'yes' ),
				'acfw_stat_pending'      => $toggle( 'dashboard', __( 'Orders in progress', 'my-account-dashboard-builder' ), 'yes' ),
				'acfw_stat_spent'        => $toggle( 'dashboard', __( 'Total spent', 'my-account-dashboard-builder' ), 'yes' ),
				'acfw_stat_downloads'    => $toggle( 'dashboard', __( 'Downloads', 'my-account-dashboard-builder' ), 'yes' ),
				'acfw_stat_refunds'      => $toggle( 'dashboard', __( 'Refunds', 'my-account-dashboard-builder' ), 'no' ),
				'acfw_stat_points'       => $toggle( 'dashboard', __( 'Reward points', 'my-account-dashboard-builder' ), 'no' ),
				'acfw_stat_latest'       => $toggle( 'dashboard', __( 'Latest order', 'my-account-dashboard-builder' ), 'no' ),
				'acfw_stat_piechart'     => $toggle( 'dashboard', __( 'Orders by status chart', 'my-account-dashboard-builder' ), 'no' ),
				'acfw_dashboard_tiles'   => $toggle( 'dashboard', __( 'Shortcut tiles', 'my-account-dashboard-builder' ), 'no', false, __( 'A tile for every page in the menu.', 'my-account-dashboard-builder' ) ),
				'acfw_profile_meter'     => $toggle( 'dashboard', __( 'Profile completeness', 'my-account-dashboard-builder' ), 'no' ),

				// ---- Custom CSS.
				'acfw_custom_css'        => array(
					'group'   => 'code',
					'type'    => 'css',
					'label'   => __( 'Custom CSS', 'my-account-dashboard-builder' ),
					'default' => '',
					'live'    => false,
					'hint'    => __( 'Loaded on account pages after the plugin’s own styles.', 'my-account-dashboard-builder' ),
				),
			);

			// Stat cards only apply while the numbers are on.
			foreach ( array( 'acfw_stat_orders', 'acfw_stat_pending', 'acfw_stat_spent', 'acfw_stat_downloads', 'acfw_stat_refunds', 'acfw_stat_points', 'acfw_stat_latest', 'acfw_stat_piechart' ) as $stat ) {
				$fields[ $stat ]['parent'] = 'acfw_dashboard_stats';
			}
			foreach ( array( 'acfw_avatar_image', 'acfw_avatar_shape', 'acfw_avatar_align', 'acfw_avatar_size', 'acfw_avatar_show_name', 'acfw_avatar_show_role', 'acfw_avatar_upload' ) as $avatar ) {
				$fields[ $avatar ]['parent'] = 'acfw_avatar_enable';
			}
			$fields['acfw_avatar_upload_max']['parent'] = 'acfw_avatar_upload';

			foreach ( self::tips() as $tip_key => $tip ) {
				if ( isset( $fields[ $tip_key ] ) ) {
					$fields[ $tip_key ]['tip'] = $tip;
				}
			}

			/**
			 * Filter the design options the Studio edits.
			 *
			 * @param array $fields option name => field definition.
			 */
			return apply_filters( 'acfw_design_fields', $fields );
		}

		/**
		 * What each control does, in a sentence: the tooltip on its label.
		 *
		 * @return array option name => text
		 */
		public static function tips() {
			return array(
				'acfw_accent_color'      => __( 'The main colour of the account area.', 'my-account-dashboard-builder' ),
				'acfw_text_color'        => __( 'The colour of the menu labels.', 'my-account-dashboard-builder' ),
				'acfw_color_scheme'      => __( 'A light or dark menu and widgets. Follow device switches with the customer’s system setting.', 'my-account-dashboard-builder' ),
				'acfw_font_family'       => __( 'The typeface of the menu. Theme keeps your theme’s font.', 'my-account-dashboard-builder' ),
				'acfw_font_size'         => __( 'How big the menu labels are.', 'my-account-dashboard-builder' ),
				'acfw_font_weight'       => __( 'How heavy the menu labels are.', 'my-account-dashboard-builder' ),
				'acfw_menu_radius'       => __( 'How rounded the corners of menu items, cards and badges are.', 'my-account-dashboard-builder' ),
				'acfw_item_padding'      => __( 'The space above and below each menu label. More makes taller items.', 'my-account-dashboard-builder' ),
				'acfw_menu_gap'          => __( 'The space between one menu item and the next.', 'my-account-dashboard-builder' ),
				'acfw_menu_style'        => __( 'The overall look of the menu: its borders, backgrounds and shape.', 'my-account-dashboard-builder' ),
				'acfw_menu_position'     => __( 'Where the menu sits beside the page content. Above lays the items out in a row.', 'my-account-dashboard-builder' ),
				'acfw_active_indicator'  => __( 'How the page the customer is on is marked in the menu.', 'my-account-dashboard-builder' ),
				'acfw_hover_anim'        => __( 'What a menu item does when the pointer is over it.', 'my-account-dashboard-builder' ),
				'acfw_active_color'      => __( 'The colour of the current page’s label and marker.', 'my-account-dashboard-builder' ),
				'acfw_menu_bg'           => __( 'A background colour for every menu item. Empty leaves them see-through.', 'my-account-dashboard-builder' ),
				'acfw_hover_bg'          => __( 'The background of a menu item under the pointer.', 'my-account-dashboard-builder' ),
				'acfw_show_icons'        => __( 'Show the icon beside each menu label.', 'my-account-dashboard-builder' ),
				'acfw_show_counts'       => __( 'Show how many orders and downloads the customer has, beside those items.', 'my-account-dashboard-builder' ),
				'acfw_menu_search'       => __( 'A search box above the menu that filters its items as the customer types.', 'my-account-dashboard-builder' ),
				'acfw_pin_enable'        => __( 'Customers can star items to keep them at the top of their own menu.', 'my-account-dashboard-builder' ),
				'acfw_collapsible'       => __( 'Customers can shrink the menu to a rail of icons.', 'my-account-dashboard-builder' ),
				'acfw_sticky_menu'       => __( 'The menu stays on screen while the customer scrolls a long page.', 'my-account-dashboard-builder' ),
				'acfw_group_open'        => __( 'Every group starts open, not only the one holding the current page.', 'my-account-dashboard-builder' ),
				'acfw_logout_confirm'    => __( 'Customers confirm before they are logged out.', 'my-account-dashboard-builder' ),
				'acfw_avatar_enable'     => __( 'A card above the menu with the customer’s picture and name.', 'my-account-dashboard-builder' ),
				'acfw_avatar_image'      => __( 'The picture shown for customers who have none of their own.', 'my-account-dashboard-builder' ),
				'acfw_avatar_shape'      => __( 'The shape of the profile picture.', 'my-account-dashboard-builder' ),
				'acfw_avatar_align'      => __( 'Where the picture and name sit in the card.', 'my-account-dashboard-builder' ),
				'acfw_avatar_size'       => __( 'How big the profile picture is.', 'my-account-dashboard-builder' ),
				'acfw_avatar_show_name'  => __( 'Show the customer’s name under the picture.', 'my-account-dashboard-builder' ),
				'acfw_avatar_show_role'  => __( 'Show the customer’s role under their name.', 'my-account-dashboard-builder' ),
				'acfw_avatar_upload'     => __( 'Customers can upload their own picture from the card.', 'my-account-dashboard-builder' ),
				'acfw_avatar_upload_max' => __( 'The biggest picture file a customer can upload.', 'my-account-dashboard-builder' ),
				'acfw_dashboard_title'   => __( 'The heading at the top of the dashboard.', 'my-account-dashboard-builder' ),
				'acfw_dashboard_align'   => __( 'Where the dashboard heading sits.', 'my-account-dashboard-builder' ),
				'acfw_dashboard_stats'   => __( 'A row of number cards at the top of the dashboard.', 'my-account-dashboard-builder' ),
				'acfw_stat_orders'       => __( 'How many orders the customer has placed.', 'my-account-dashboard-builder' ),
				'acfw_stat_pending'      => __( 'Orders that are not finished yet.', 'my-account-dashboard-builder' ),
				'acfw_stat_spent'        => __( 'How much the customer has spent in all.', 'my-account-dashboard-builder' ),
				'acfw_stat_downloads'    => __( 'How many files the customer can download.', 'my-account-dashboard-builder' ),
				'acfw_stat_refunds'      => __( 'How many refunds the customer has had.', 'my-account-dashboard-builder' ),
				'acfw_stat_points'       => __( 'The customer’s reward points balance.', 'my-account-dashboard-builder' ),
				'acfw_stat_latest'       => __( 'The customer’s most recent order.', 'my-account-dashboard-builder' ),
				'acfw_stat_piechart'     => __( 'A chart of the customer’s orders by status.', 'my-account-dashboard-builder' ),
				'acfw_dashboard_tiles'   => __( 'A grid of shortcuts to the pages in the menu.', 'my-account-dashboard-builder' ),
				'acfw_profile_meter'     => __( 'How complete the customer’s profile is, and what is still missing.', 'my-account-dashboard-builder' ),
				'acfw_dashboard_layout'  => __( 'The order of the parts of the dashboard, and which of them show.', 'my-account-dashboard-builder' ),
				'acfw_status_badges'     => __( 'Badges that update themselves: “1 to pay” on Orders, “1 open” on Returns, and a dot on Account details or Addresses while something is missing.', 'my-account-dashboard-builder' ),
				'acfw_custom_css'        => __( 'Your own CSS, for anything the controls here do not cover.', 'my-account-dashboard-builder' ),
			);
		}

		/**
		 * Studio groups, in display order.
		 *
		 * @return array slug => array{ label, icon }
		 */
		public static function groups() {
			return array(
				'look'      => array(
					'label' => __( 'Look', 'my-account-dashboard-builder' ),
					'icon'  => 'art',
				),
				'menu'      => array(
					'label' => __( 'Menu', 'my-account-dashboard-builder' ),
					'icon'  => 'menu-alt',
				),
				'profile'   => array(
					'label' => __( 'Profile card', 'my-account-dashboard-builder' ),
					'icon'  => 'id',
				),
				'dashboard' => array(
					'label' => __( 'Dashboard', 'my-account-dashboard-builder' ),
					'icon'  => 'chart-bar',
				),
				'code'      => array(
					'label' => __( 'Custom CSS', 'my-account-dashboard-builder' ),
					'icon'  => 'editor-code',
				),
			);
		}

		/**
		 * Sanitise one value for its field.
		 *
		 * @param array $field Field definition.
		 * @param mixed $value Raw value.
		 * @return mixed
		 */
		public static function sanitize( $field, $value ) {
			switch ( $field['type'] ) {
				case 'color':
					$color = sanitize_hex_color( (string) $value );
					return $color ? $color : '';
				case 'toggle':
					return 'yes' === $value || true === $value ? 'yes' : 'no';
				case 'choice':
					$value = (string) $value;
					return array_key_exists( $value, $field['choices'] ) ? $value : $field['default'];
				case 'range':
					$min  = (int) $field['min'];
					$max  = (int) $field['max'];
					$step = isset( $field['step'] ) ? max( 1, (int) $field['step'] ) : 1;
					$num  = (int) round( (float) $value / $step ) * $step;
					return max( $min, min( $max, $num ) );
				case 'css':
					return wp_strip_all_tags( (string) $value );
				case 'layout':
					return acfw_sanitize_dashboard_layout( $value );
				case 'image':
					return esc_url_raw( (string) $value );
				case 'text':
				default:
					return sanitize_text_field( (string) $value );
			}
		}

		/**
		 * Sanitise a set of values, keeping only known design options.
		 *
		 * @param array $values Raw option name => value.
		 * @return array
		 */
		public static function sanitize_values( $values ) {
			$fields = self::fields();
			$clean  = array();
			foreach ( (array) $values as $key => $value ) {
				if ( isset( $fields[ $key ] ) && is_scalar( $value ) ) {
					$clean[ $key ] = self::sanitize( $fields[ $key ], $value );
				}
			}
			return $clean;
		}

		/**
		 * The saved value of every design option.
		 *
		 * @return array
		 */
		public static function saved_values() {
			$values = array();
			foreach ( self::fields() as $key => $field ) {
				$values[ $key ] = self::sanitize( $field, get_option( $key, $field['default'] ) );
			}
			return $values;
		}

		/**
		 * Transient holding the current admin's unsaved design.
		 *
		 * @return string
		 */
		public static function draft_key() {
			// Viewing as a customer, the draft is still the shop manager's.
			$uid = class_exists( 'ACFW_View_As' ) && ACFW_View_As::active() ? ACFW_View_As::real_user_id() : get_current_user_id();
			return 'acfw_design_draft_' . $uid;
		}

		/**
		 * Is this request the Studio's preview of the account page?
		 *
		 * @return bool
		 */
		public static function is_preview() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag; only honoured for shop managers, who only see their own draft.
			if ( ! isset( $_GET[ self::PREVIEW_ARG ] ) || ! is_user_logged_in() ) {
				return false;
			}
			// Viewing as a customer inside the Studio: the shop manager is behind it.
			if ( class_exists( 'ACFW_View_As' ) && ACFW_View_As::active() ) {
				return user_can( ACFW_View_As::real_user_id(), 'manage_woocommerce' );
			}
			return current_user_can( 'manage_woocommerce' );
		}

		/**
		 * The URL the Studio previews.
		 *
		 * @return string
		 */
		public static function preview_url() {
			$base = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
			return add_query_arg( self::PREVIEW_ARG, '1', $base );
		}

		/**
		 * In preview mode, read the admin's draft instead of the saved options.
		 */
		public function maybe_preview() {
			if ( is_admin() || wp_doing_ajax() || ! self::is_preview() ) {
				return;
			}

			$draft = get_transient( self::draft_key() );
			if ( is_array( $draft ) ) {
				foreach ( self::sanitize_values( $draft ) as $key => $value ) {
					add_filter(
						'pre_option_' . $key,
						static function () use ( $value ) {
							return $value;
						}
					);
				}
			}

			// Links in the preview load for real; keep the page itself still.
			add_filter(
				'pre_option_acfw_ajax_navigation',
				static function () {
					return 'no';
				}
			);
			add_filter( 'show_admin_bar', '__return_false' );
			add_action( 'template_redirect', 'nocache_headers' );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_preview' ), 20 );
		}

		/**
		 * The preview's side of the Studio: applies live changes sent by the
		 * Studio, and keeps links inside the account area and in preview mode.
		 */
		public function enqueue_preview() {
			list( $url, $ver ) = acfw_asset_src( 'js/design-preview.js' );
			wp_enqueue_script( 'acfw-design-preview', $url, array(), $ver, true );

			$styles = array();
			foreach ( array_keys( acfw_menu_styles() ) as $style ) {
				$styles[ $style ] = acfw_menu_style_resolve( $style );
			}

			wp_localize_script(
				'acfw-design-preview',
				'acfwPreview',
				array(
					'origin' => untrailingslashit( admin_url() ),
					'scope'  => ACFW_Frontend::token_scope(),
					'styles' => $styles,
					'fonts'  => ACFW_Frontend::font_stacks(),
					'base'   => wp_make_link_relative( wc_get_page_permalink( 'myaccount' ) ),
					'arg'    => self::PREVIEW_ARG,
				)
			);
		}

		/**
		 * Stop unless the request comes from a shop manager with a valid nonce.
		 */
		protected function guard() {
			check_ajax_referer( self::NONCE, 'nonce' );
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to change the design.', 'my-account-dashboard-builder' ) ), 403 );
			}
		}

		/**
		 * Design values posted by the Studio, as a JSON object.
		 *
		 * @return array
		 */
		protected function posted_values() {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked in guard(); every value is sanitised per field in sanitize_values().
			$raw    = isset( $_POST['values'] ) ? wp_unslash( $_POST['values'] ) : '';
			$values = is_string( $raw ) ? json_decode( $raw, true ) : null;
			return is_array( $values ) ? self::sanitize_values( $values ) : array();
		}

		/**
		 * AJAX: keep the current admin's unsaved design for the preview.
		 */
		public function ajax_draft() {
			$this->guard();
			set_transient( self::draft_key(), $this->posted_values(), DAY_IN_SECONDS );
			wp_send_json_success();
		}

		/**
		 * AJAX: save the design.
		 */
		public function ajax_save() {
			$this->guard();

			foreach ( $this->posted_values() as $key => $value ) {
				update_option( $key, $value );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked in guard().
			$look = isset( $_POST['look'] ) ? sanitize_key( wp_unslash( $_POST['look'] ) ) : '';
			if ( '' !== $look ) {
				update_option( 'acfw_active_template', $look );
			}

			delete_transient( self::draft_key() );

			wp_send_json_success(
				array(
					'values'  => self::saved_values(),
					'message' => __( 'Design saved.', 'my-account-dashboard-builder' ),
				)
			);
		}

		/**
		 * AJAX: drop the unsaved design.
		 */
		public function ajax_discard() {
			$this->guard();
			delete_transient( self::draft_key() );
			wp_send_json_success( array( 'values' => self::saved_values() ) );
		}

		/**
		 * AJAX: keep the current design as a named look ( a preset ).
		 */
		public function ajax_save_look() {
			$this->guard();

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked in guard().
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( '' === $name ) {
				wp_send_json_error( array( 'message' => __( 'Give the look a name.', 'my-account-dashboard-builder' ) ) );
			}

			$values  = $this->posted_values();
			$look    = array( '__label' => $name );
			$presets = get_option( 'acfw_presets', array() );
			$presets = is_array( $presets ) ? $presets : array();
			foreach ( acfw_design_option_keys() as $key ) {
				if ( array_key_exists( $key, $values ) ) {
					$look[ $key ] = $values[ $key ];
				}
			}

			$slug = acfw_item_key_from_label( $name, 'look' );
			$base = $slug;
			$n    = 2;
			while ( isset( $presets[ $slug ] ) ) {
				$slug = $base . '-' . $n;
				++$n;
			}
			$presets[ $slug ] = $look;
			update_option( 'acfw_presets', $presets );

			wp_send_json_success(
				array(
					'slug'    => $slug,
					'look'    => $look,
					/* translators: %s: look name. */
					'message' => sprintf( __( 'Saved "%s" to your looks.', 'my-account-dashboard-builder' ), $name ),
				)
			);
		}
	}
}
