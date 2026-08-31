<?php
/**
 * Shared helper functions.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * The single "Menu style" choices ( merges the old layout + preset controls ).
 *
 * @return array slug => label
 */
function acfw_menu_styles() {
	return apply_filters(
		'acfw_menu_styles',
		array(
			'theme'   => __( 'Theme style', 'account-customizer-for-woocommerce' ),
			'simple'  => __( 'Simple', 'account-customizer-for-woocommerce' ),
			'classic' => __( 'Classic', 'account-customizer-for-woocommerce' ),
			'modern'  => __( 'Modern cards', 'account-customizer-for-woocommerce' ),
			'minimal' => __( 'Minimal', 'account-customizer-for-woocommerce' ),
			'pill'    => __( 'Pills', 'account-customizer-for-woocommerce' ),
			'tabs'    => __( 'Tabs', 'account-customizer-for-woocommerce' ),
		)
	);
}

/**
 * Resolve a "Menu style" slug to its underlying [ layout, preset ] classes.
 *
 * @param string $style Style slug.
 * @return array { string $layout, string $preset }
 */
function acfw_menu_style_resolve( $style ) {
	$map = array(
		'theme'   => array( 'theme', 'flat' ),
		'simple'  => array( 'simple', 'flat' ),
		'classic' => array( 'classic', 'boxed' ),
		'modern'  => array( 'modern', 'flat' ),
		'minimal' => array( 'no-borders', 'minimal' ),
		'pill'    => array( 'simple', 'pill' ),
		'tabs'    => array( 'tabs', 'flat' ),
	);
	return isset( $map[ $style ] ) ? $map[ $style ] : $map['simple'];
}

/**
 * Option keys that make up a "design preset".
 *
 * @return array
 */
function acfw_design_option_keys() {
	return apply_filters(
		'acfw_design_option_keys',
		array(
			'acfw_menu_position',
			'acfw_menu_style',
			'acfw_accent_color',
			'acfw_text_color',
			'acfw_active_color',
			'acfw_menu_bg',
			'acfw_hover_bg',
			'acfw_menu_radius',
			'acfw_menu_gap',
			'acfw_item_padding',
			'acfw_font_size',
			'acfw_font_weight',
			'acfw_font_family',
			'acfw_color_scheme',
			'acfw_active_indicator',
			'acfw_hover_anim',
			'acfw_show_icons',
			'acfw_show_counts',
			'acfw_group_open',
			'acfw_avatar_enable',
			'acfw_avatar_image',
			'acfw_avatar_shape',
			'acfw_avatar_align',
			'acfw_avatar_size',
			'acfw_avatar_show_name',
			'acfw_avatar_show_role',
			'acfw_avatar_upload',
			'acfw_avatar_upload_max',
			'acfw_custom_css',
		)
	);
}

/**
 * Curated, shipped design templates users can apply with one click.
 * Each template maps design option keys to values ( keys from acfw_design_option_keys ).
 *
 * @return array slug => array{ label, description, accent, options }
 */
function acfw_prebuilt_templates() {
	$templates = array(
		'classic-sidebar' => array(
			'label'       => __( 'Classic Sidebar', 'account-customizer-for-woocommerce' ),
			'description' => __( 'Left menu with a subtle active bar. The safe default.', 'account-customizer-for-woocommerce' ),
			'accent'      => '#2563eb',
			'options'     => array(
				'acfw_menu_position'    => 'vertical-left',
				'acfw_menu_style'       => 'simple',
				'acfw_accent_color'     => '#2563eb',
				'acfw_active_indicator' => 'bar',
				'acfw_menu_radius'      => 8,
				'acfw_show_icons'       => 'yes',
			),
		),
		'modern-cards'    => array(
			'label'       => __( 'Modern Cards', 'account-customizer-for-woocommerce' ),
			'description' => __( 'Raised cards with icon chips and rounded corners.', 'account-customizer-for-woocommerce' ),
			'accent'      => '#7c3aed',
			'options'     => array(
				'acfw_menu_position'    => 'vertical-left',
				'acfw_menu_style'       => 'modern',
				'acfw_accent_color'     => '#7c3aed',
				'acfw_active_indicator' => 'none',
				'acfw_hover_anim'       => 'grow',
				'acfw_menu_radius'      => 12,
				'acfw_menu_gap'         => 10,
				'acfw_show_icons'       => 'yes',
			),
		),
		'rounded-pills'   => array(
			'label'       => __( 'Rounded Pills', 'account-customizer-for-woocommerce' ),
			'description' => __( 'Soft pill items with a coloured active state.', 'account-customizer-for-woocommerce' ),
			'accent'      => '#0ea5e9',
			'options'     => array(
				'acfw_menu_position'    => 'vertical-left',
				'acfw_menu_style'       => 'pill',
				'acfw_accent_color'     => '#0ea5e9',
				'acfw_active_indicator' => 'none',
				'acfw_menu_radius'      => 24,
				'acfw_show_icons'       => 'yes',
			),
		),
		'tabbed-top'      => array(
			'label'       => __( 'Tabbed Top', 'account-customizer-for-woocommerce' ),
			'description' => __( 'Horizontal tab bar above the content.', 'account-customizer-for-woocommerce' ),
			'accent'      => '#16a34a',
			'options'     => array(
				'acfw_menu_position'    => 'horizontal',
				'acfw_menu_style'       => 'tabs',
				'acfw_accent_color'     => '#16a34a',
				'acfw_active_indicator' => 'underline',
				'acfw_show_icons'       => 'yes',
			),
		),
		'minimal'         => array(
			'label'       => __( 'Minimal', 'account-customizer-for-woocommerce' ),
			'description' => __( 'Flat, borderless list. Quiet and compact.', 'account-customizer-for-woocommerce' ),
			'accent'      => '#111827',
			'options'     => array(
				'acfw_menu_position'    => 'vertical-left',
				'acfw_menu_style'       => 'minimal',
				'acfw_accent_color'     => '#111827',
				'acfw_active_indicator' => 'none',
				'acfw_menu_radius'      => 4,
				'acfw_show_icons'       => 'no',
			),
		),
		'theme-native'    => array(
			'label'       => __( 'Theme Native', 'account-customizer-for-woocommerce' ),
			'description' => __( 'Drops plugin styling and inherits your theme.', 'account-customizer-for-woocommerce' ),
			'accent'      => '#64748b',
			'options'     => array(
				'acfw_menu_position' => 'vertical-left',
				'acfw_menu_style'    => 'theme',
				'acfw_show_icons'    => 'yes',
			),
		),
	);

	return apply_filters( 'acfw_prebuilt_templates', $templates );
}

/**
 * Default value for every design option ( matches the Customizer defaults ).
 * Used to reset the design to a known baseline before applying a template.
 *
 * @return array option_key => default_value
 */
function acfw_design_option_defaults() {
	return apply_filters(
		'acfw_design_option_defaults',
		array(
			'acfw_menu_position'     => 'vertical-left',
			'acfw_menu_style'        => 'simple',
			'acfw_accent_color'      => '#2563eb',
			'acfw_text_color'        => '#383838',
			'acfw_active_color'      => '',
			'acfw_menu_bg'           => '',
			'acfw_hover_bg'          => '',
			'acfw_menu_radius'       => 8,
			'acfw_menu_gap'          => 4,
			'acfw_item_padding'      => 11,
			'acfw_font_size'         => 15,
			'acfw_font_weight'       => '500',
			'acfw_font_family'       => 'inherit',
			'acfw_color_scheme'      => 'light',
			'acfw_active_indicator'  => 'bar',
			'acfw_hover_anim'        => 'none',
			'acfw_show_icons'        => 'yes',
			'acfw_show_counts'       => 'yes',
			'acfw_group_open'        => 'no',
			'acfw_avatar_enable'     => 'no',
			'acfw_avatar_image'      => '',
			'acfw_avatar_shape'      => 'circle',
			'acfw_avatar_align'      => 'center',
			'acfw_avatar_size'       => 72,
			'acfw_avatar_show_name'  => 'yes',
			'acfw_avatar_show_role'  => 'no',
			'acfw_avatar_upload'     => 'no',
			'acfw_avatar_upload_max' => 2048,
			'acfw_custom_css'        => '',
		)
	);
}

/**
 * Full FontAwesome icon list ( class strings ) used by icon pickers.
 *
 * @return array
 */
function acfw_icon_list() {
	$list = include ACFW_DIR . 'includes/helper/icon-list.php';
	return is_array( $list ) ? $list : array();
}

/**
 * Sanitize an icon class, preserving the space in multi-class icons
 * like "fas fa-cart" ( sanitize_html_class strips spaces ).
 *
 * @param string $value Raw icon class.
 * @return string
 */
function acfw_sanitize_icon( $value ) {
	$value = wp_strip_all_tags( (string) $value );
	return trim( preg_replace( '/[^a-z0-9 _-]/i', '', $value ) );
}

/**
 * Points balance ( WooCommerce Points & Rewards, when active ).
 *
 * @param WP_User $user User.
 * @return string
 */
function acfw_points_balance( $user ) {
	if ( empty( $user->ID ) ) {
		return '';
	}
	if ( function_exists( 'wc_points_rewards_get_users_points' ) ) {
		return (string) wc_points_rewards_get_users_points( $user->ID );
	}
	return '';
}

/**
 * Active membership plan name ( WooCommerce Memberships, when active ).
 *
 * @param WP_User $user User.
 * @return string
 */
function acfw_membership_plan( $user ) {
	if ( empty( $user->ID ) || ! function_exists( 'wc_memberships_get_user_active_memberships' ) ) {
		return '';
	}
	$memberships = wc_memberships_get_user_active_memberships( $user->ID );
	if ( ! empty( $memberships ) ) {
		$first = reset( $memberships );
		if ( is_object( $first ) && method_exists( $first, 'get_plan' ) ) {
			$plan = $first->get_plan();
			return $plan ? $plan->get_name() : '';
		}
	}
	return '';
}

/**
 * Whether an icon value is a FontAwesome class ( vs a dashicon ).
 *
 * @param string $icon Icon value.
 * @return bool
 */
function acfw_is_fa_icon( $icon ) {
	return is_string( $icon ) && false !== strpos( $icon, 'fa-' );
}

/**
 * Render an icon ( FontAwesome, dashicon or uploaded image ) with a wrapper class.
 *
 * @param string $icon      Icon class ( fa or dashicons ).
 * @param string $icon_url  Uploaded image URL ( takes priority ).
 * @param string $wrapper   Wrapper CSS class.
 * @return string
 */
function acfw_icon_markup( $icon, $icon_url = '', $wrapper = 'acfw-icon' ) {
	if ( ! empty( $icon_url ) ) {
		return sprintf( '<img class="%s acfw-icon-img" src="%s" alt="" />', esc_attr( $wrapper ), esc_url( $icon_url ) );
	}
	if ( acfw_is_fa_icon( $icon ) ) {
		return sprintf( '<i class="%s %s"></i>', esc_attr( $wrapper ), esc_attr( $icon ) );
	}
	if ( ! empty( $icon ) ) {
		return sprintf( '<span class="%s dashicons %s"></span>', esc_attr( $wrapper ), esc_attr( $icon ) );
	}
	return '';
}

/**
 * Sanitize a string into a safe item key / slug.
 *
 * @param string $value Raw value.
 * @return string
 */
function acfw_sanitize_key( $value ) {
	$value = sanitize_title( $value );
	return str_replace( '_', '-', $value );
}

/**
 * Block patterns ( core + theme + user ) for the standalone endpoint block editor.
 *
 * @return array
 */
function acfw_get_block_editor_patterns() {
	if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) {
		return array();
	}
	if ( function_exists( '_load_remote_block_patterns' ) ) {
		_load_remote_block_patterns();
	}
	if ( function_exists( '_load_remote_featured_patterns' ) ) {
		_load_remote_featured_patterns();
	}
	if ( function_exists( '_register_remote_theme_patterns' ) ) {
		_register_remote_theme_patterns();
	}

	$patterns = array_values( WP_Block_Patterns_Registry::get_instance()->get_all_registered() );

	$user_posts = get_posts(
		array(
			'post_type'      => 'wp_block',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	foreach ( $user_posts as $post ) {
		$patterns[] = array(
			'name'       => 'core/block/' . $post->ID,
			'id'         => $post->ID,
			'type'       => 'user',
			'title'      => $post->post_title,
			'content'    => $post->post_content,
			'inserter'   => true,
			'syncStatus' => get_post_meta( $post->ID, 'wp_pattern_sync_status', true ),
		);
	}

	return $patterns;
}

/**
 * Registered block pattern categories for the endpoint block editor.
 *
 * @return array
 */
function acfw_get_block_editor_pattern_categories() {
	if ( ! class_exists( 'WP_Block_Pattern_Categories_Registry' ) ) {
		return array();
	}
	return array_values( WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered() );
}

/**
 * User-defined pattern categories ( taxonomy ) for the endpoint block editor.
 *
 * @return array
 */
function acfw_get_block_editor_user_pattern_categories() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'wp_pattern_category',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}
	return array_map(
		static function ( $term ) {
			return array(
				'id'    => $term->term_id,
				'slug'  => $term->slug,
				'label' => $term->name,
			);
		},
		$terms
	);
}

/**
 * Default icon class for an item type ( used when no icon is set ).
 *
 * @param string $type Item type.
 * @return string
 */
function acfw_default_type_icon( $type ) {
	$map  = array(
		'group' => 'fas fa-folder',
		'page'  => 'fas fa-file-alt',
		'link'  => 'fas fa-link',
	);
	$icon = $map[ $type ] ?? 'dashicons-menu-alt';
	return apply_filters( 'acfw_default_type_icon', $icon, $type );
}

/**
 * Resolve an asset to its minified build ( unless SCRIPT_DEBUG ) with a
 * file-mtime cache-busting version.
 *
 * @param string $rel Path relative to the assets dir, e.g. 'css/admin.css'.
 * @return array { string $url, string|false $ver }
 */
function acfw_asset_src( $rel ) {
	$rel = ltrim( $rel, '/' );

	if ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
		$min = preg_replace( '/\.(css|js)$/', '.min.$1', $rel );
		if ( $min !== $rel && file_exists( ACFW_DIR . 'assets/' . $min ) ) {
			$rel = $min;
		}
	}

	$file = ACFW_DIR . 'assets/' . $rel;
	$ver  = file_exists( $file ) ? (string) filemtime( $file ) : ( defined( 'ACFW_VERSION' ) ? ACFW_VERSION : false );

	return array( ACFW_ASSETS_URL . '/' . $rel, $ver );
}

/**
 * Sanitize a colour value: 3/6-digit hex or rgb()/rgba() ( alpha supported ).
 * Returns '' for anything else.
 *
 * @param string $value Raw colour string.
 * @return string
 */
function acfw_sanitize_color( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	// Hex ( #rgb / #rrggbb ).
	$hex = sanitize_hex_color( $value );
	if ( $hex ) {
		return $hex;
	}
	// rgb() / rgba().
	if ( preg_match( '/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*(0|1|0?\.\d+)\s*)?\)$/i', $value, $m ) ) {
		$r = min( 255, (int) $m[1] );
		$g = min( 255, (int) $m[2] );
		$b = min( 255, (int) $m[3] );
		if ( isset( $m[4] ) && '' !== $m[4] ) {
			$a = (float) $m[4];
			$a = max( 0, min( 1, $a ) );
			return sprintf( 'rgba(%d, %d, %d, %s)', $r, $g, $b, rtrim( rtrim( sprintf( '%.2f', $a ), '0' ), '.' ) );
		}
		return sprintf( 'rgb(%d, %d, %d)', $r, $g, $b );
	}
	return '';
}

/**
 * Get the currently requested My Account endpoint key.
 *
 * Falls back to 'dashboard' when no endpoint query var is present.
 *
 * @return string
 */
function acfw_get_current_endpoint() {
	if ( ! function_exists( 'WC' ) || ! WC()->query ) {
		return 'dashboard';
	}

	$current = WC()->query->get_current_endpoint();

	return $current ? $current : 'dashboard';
}

/**
 * Locate a template, allowing theme overrides.
 *
 * Themes can override by placing files in
 * yourtheme/account-customizer-for-woocommerce/{template}.
 *
 * @param string $template Template filename.
 * @param array  $args     Variables passed to the template.
 */
function acfw_get_template( $template, $args = array() ) {

	if ( is_array( $args ) ) {
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- template variables.
	}

	$override = locate_template(
		array(
			'account-customizer-for-woocommerce/' . $template,
		)
	);

	$path = $override ? $override : ACFW_TEMPLATE_PATH . '/' . $template;

	if ( file_exists( $path ) ) {
		include $path;
	}
}

/**
 * Registered smart tags: token => human label.
 *
 * @return array
 */
function acfw_smart_tags() {
	return apply_filters(
		'acfw_smart_tags',
		array(
			'{display_name}'    => __( 'Display name', 'account-customizer-for-woocommerce' ),
			'{first_name}'      => __( 'First name', 'account-customizer-for-woocommerce' ),
			'{last_name}'       => __( 'Last name', 'account-customizer-for-woocommerce' ),
			'{username}'        => __( 'Username', 'account-customizer-for-woocommerce' ),
			'{user_email}'      => __( 'Email address', 'account-customizer-for-woocommerce' ),
			'{site_title}'      => __( 'Site title', 'account-customizer-for-woocommerce' ),
			'{order_count}'     => __( 'Order count', 'account-customizer-for-woocommerce' ),
			'{download_count}'  => __( 'Download count', 'account-customizer-for-woocommerce' ),
			'{last_login}'      => __( 'Last login date', 'account-customizer-for-woocommerce' ),
			'{points_balance}'  => __( 'Points balance', 'account-customizer-for-woocommerce' ),
			'{membership_plan}' => __( 'Membership plan', 'account-customizer-for-woocommerce' ),
		)
	);
}

/**
 * Dynamic item count for a menu key ( orders / downloads ), for badges.
 *
 * @param string $key  Endpoint key.
 * @param int    $uid  User ID ( 0 = current ).
 * @return int|null Count, or null when not a countable endpoint.
 */
function acfw_endpoint_count( $key, $uid = 0 ) {
	$uid = $uid ? $uid : get_current_user_id();
	if ( ! $uid ) {
		return null;
	}
	if ( 'orders' === $key && function_exists( 'wc_get_customer_order_count' ) ) {
		return (int) wc_get_customer_order_count( $uid );
	}
	if ( 'downloads' === $key && function_exists( 'wc_get_customer_available_downloads' ) ) {
		return count( wc_get_customer_available_downloads( $uid ) );
	}
	return null;
}

/**
 * Replace smart tags in a string with the current user's values.
 *
 * @param string       $content Raw content.
 * @param WP_User|null $user    User ( defaults to current ).
 * @return string
 */
function acfw_apply_smart_tags( $content, $user = null ) {

	if ( false === strpos( $content, '{' ) && false === strpos( $content, '%%' ) ) {
		return $content;
	}

	$user = $user ? $user : wp_get_current_user();

	$orders = 0;
	if ( ! empty( $user->ID ) && function_exists( 'wc_get_customer_order_count' ) ) {
		$orders = wc_get_customer_order_count( $user->ID );
	}
	$downloads  = ! empty( $user->ID ) ? acfw_endpoint_count( 'downloads', $user->ID ) : null;
	$last_login = ! empty( $user->ID ) ? get_user_meta( $user->ID, 'acfw_last_login', true ) : '';

	$map = array(
		'{display_name}'    => $user->display_name ?? '',
		'{first_name}'      => $user->first_name ?? '',
		'{last_name}'       => $user->last_name ?? '',
		'{username}'        => $user->user_login ?? '',
		'{user_email}'      => $user->user_email ?? '',
		'{site_title}'      => get_bloginfo( 'name' ),
		'{order_count}'     => (string) $orders,
		'{download_count}'  => (string) ( null === $downloads ? 0 : $downloads ),
		'{last_login}'      => $last_login ? date_i18n( get_option( 'date_format' ), (int) $last_login ) : '',
		'{points_balance}'  => acfw_points_balance( $user ),
		'{membership_plan}' => acfw_membership_plan( $user ),
		// Back-compat token.
		'%%customer_name%%' => $user->display_name ?? '',
	);

	$map = apply_filters( 'acfw_smart_tag_values', $map, $user );

	return str_replace( array_keys( $map ), array_map( 'esc_html', array_values( $map ) ), $content );
}

/**
 * Default option set for an endpoint item.
 *
 * @param string $key Item key.
 * @return array
 */
function acfw_default_endpoint_options( $key = '' ) {
	return array(
		'type'             => 'endpoint',
		'label'            => '',
		'slug'             => $key,
		'icon'             => '',
		'icon_url'         => '',
		'icon_source'      => 'choose',
		'active'           => true,
		'content'          => '',
		'editor_type'      => 'classic', // classic | block.
		'content_position' => 'before', // before | after | override.
		'visibility'       => 'all',    // all | roles.
		'usr_roles'        => array(),
		'class'            => '',
		'banner_slug'      => '',
		'banner_position'  => 'top',    // top | bottom.
	);
}

/**
 * Default option set for a group item.
 *
 * @return array
 */
function acfw_default_group_options() {
	return array(
		'type'        => 'group',
		'label'       => '',
		'icon'        => '',
		'icon_url'    => '',
		'icon_source' => 'choose',
		'active'      => true,
		'open'        => false,
		'visibility'  => 'all',
		'usr_roles'   => array(),
		'class'       => '',
		'children'    => array(),
	);
}

/**
 * Default option set for a link item.
 *
 * @return array
 */
function acfw_default_link_options() {
	return array(
		'type'         => 'link',
		'label'        => '',
		'icon'         => '',
		'icon_url'     => '',
		'icon_source'  => 'choose',
		'active'       => true,
		'url'          => '#',
		'target_blank' => false,
		'visibility'   => 'all',
		'usr_roles'    => array(),
		'class'        => '',
	);
}
