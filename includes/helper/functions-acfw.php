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
			'theme'   => __( 'Theme style', 'my-account-dashboard-builder' ),
			'simple'  => __( 'Simple', 'my-account-dashboard-builder' ),
			'classic' => __( 'Classic', 'my-account-dashboard-builder' ),
			'modern'  => __( 'Modern cards', 'my-account-dashboard-builder' ),
			'minimal' => __( 'Minimal', 'my-account-dashboard-builder' ),
			'pill'    => __( 'Pills', 'my-account-dashboard-builder' ),
			'tabs'    => __( 'Tabs', 'my-account-dashboard-builder' ),
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
			'label'       => __( 'Classic Sidebar', 'my-account-dashboard-builder' ),
			'description' => __( 'Left menu with a subtle active bar. The safe default.', 'my-account-dashboard-builder' ),
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
			'label'       => __( 'Modern Cards', 'my-account-dashboard-builder' ),
			'description' => __( 'Raised cards with icon chips and rounded corners.', 'my-account-dashboard-builder' ),
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
			'label'       => __( 'Rounded Pills', 'my-account-dashboard-builder' ),
			'description' => __( 'Soft pill items with a coloured active state.', 'my-account-dashboard-builder' ),
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
			'label'       => __( 'Tabbed Top', 'my-account-dashboard-builder' ),
			'description' => __( 'Horizontal tab bar above the content.', 'my-account-dashboard-builder' ),
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
			'label'       => __( 'Minimal', 'my-account-dashboard-builder' ),
			'description' => __( 'Flat, borderless list. Quiet and compact.', 'my-account-dashboard-builder' ),
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
			'label'       => __( 'Theme Native', 'my-account-dashboard-builder' ),
			'description' => __( 'Drops plugin styling and inherits your theme.', 'my-account-dashboard-builder' ),
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
 * Design keys a starter template leaves alone.
 *
 * A template restyles the menu. It must not wipe the site owner's own Custom
 * CSS, switch customer avatar uploads off, or hide the avatar block and the
 * item counts, none of which a template sets.
 *
 * @return array
 */
function acfw_template_preserved_keys() {
	return apply_filters(
		'acfw_template_preserved_keys',
		array(
			'acfw_custom_css',
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
 * Icon picker choices: class => readable label ( "fas fa-cart-plus" => "Cart Plus" ).
 *
 * Built once per request; the admin pages print it once for every picker.
 *
 * @return array
 */
function acfw_icon_choices() {
	static $choices = null;
	if ( null === $choices ) {
		$choices = array();
		foreach ( acfw_icon_list() as $class ) {
			$choices[ $class ] = acfw_icon_label( $class );
		}
	}
	return $choices;
}

/**
 * Readable label for an icon class.
 *
 * @param string $class_name Icon class, e.g. "fas fa-cart-plus" or "dashicons-cart".
 * @return string
 */
function acfw_icon_label( $class_name ) {
	$name = preg_replace( '/^.*(fa|dashicons)-/', '', (string) $class_name );
	return ucwords( str_replace( '-', ' ', $name ) );
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
 * Resolve a user ID from a WP_User or a numeric ID.
 *
 * @param WP_User|int|null $user User object or ID.
 * @return int 0 when it does not map to a user.
 */
function acfw_user_id( $user ) {
	if ( is_object( $user ) && ! empty( $user->ID ) ) {
		return (int) $user->ID;
	}
	return is_numeric( $user ) ? absint( $user ) : 0;
}

/**
 * Points balance ( WooCommerce Points & Rewards, when active ).
 *
 * @param WP_User|int $user User object or ID.
 * @return string
 */
function acfw_points_balance( $user ) {
	$uid = acfw_user_id( $user );
	if ( ! $uid ) {
		return '';
	}
	if ( function_exists( 'wc_points_rewards_get_users_points' ) ) {
		return (string) wc_points_rewards_get_users_points( $uid );
	}
	return '';
}

/**
 * Active membership plan name ( WooCommerce Memberships, when active ).
 *
 * @param WP_User|int $user User object or ID.
 * @return string
 */
function acfw_membership_plan( $user ) {
	$uid = acfw_user_id( $user );
	if ( ! $uid || ! function_exists( 'wc_memberships_get_user_active_memberships' ) ) {
		return '';
	}
	$memberships = wc_memberships_get_user_active_memberships( $uid );
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
 * Sanitize a value into a plain ASCII slug ( letters, digits, dashes ).
 *
 * sanitize_title() percent-encodes characters it cannot transliterate, so a
 * Devanagari, CJK or Cyrillic label comes back as "%e0%a4%ae…". Those octets
 * are stripped: they make unreadable URLs, and WordPress' text sanitizers
 * delete them from the saved menu order, which used to drop the item.
 *
 * @param string $value Raw value.
 * @return string Possibly empty.
 */
function acfw_ascii_slug( $value ) {
	$slug = acfw_sanitize_key( (string) $value );
	$slug = preg_replace( '/%[a-f0-9]{2}/i', '', $slug );
	$slug = preg_replace( '/[^a-z0-9-]/', '', strtolower( $slug ) );
	return trim( preg_replace( '/-+/', '-', $slug ), '-' );
}

/**
 * Derive a menu item key from its label.
 *
 * Falls back to "{type}-{hash}" when the label has no ASCII letters or digits
 * at all, so every label yields a stable, URL-safe key.
 *
 * @param string $label Item label.
 * @param string $type  Item type.
 * @return string
 */
function acfw_item_key_from_label( $label, $type = 'endpoint' ) {
	$key = acfw_ascii_slug( $label );
	if ( '' === $key ) {
		$type = acfw_ascii_slug( $type );
		$key  = ( '' !== $type ? $type : 'item' ) . '-' . substr( md5( (string) $label ), 0, 6 );
	}
	return $key;
}

/**
 * Names a custom menu item must not use as its key or URL slug.
 *
 * Covers WooCommerce's own account endpoints ( keys and their configured
 * slugs ), the plugin's built-in endpoints and WordPress' public query vars:
 * an endpoint called "order" or "page" would collide with core queries.
 *
 * @return array
 */
function acfw_reserved_item_keys() {
	$reserved = array(
		// WooCommerce's own account and checkout endpoints, by key. Their
		// configured slugs are added below when WooCommerce is loaded.
		'dashboard',
		'orders',
		'view-order',
		'downloads',
		'edit-account',
		'edit-address',
		'payment-methods',
		'add-payment-method',
		'delete-payment-method',
		'set-default-payment-method',
		'lost-password',
		'customer-logout',
		'order-pay',
		'order-received',
		// This plugin's built-in endpoints.
		'buy-again',
		'recently-viewed',
		// WordPress query vars an endpoint name would collide with.
		'page',
		'paged',
		'feed',
		'embed',
		'attachment',
		'preview',
		'order',
		'orderby',
		'name',
		'author',
		'search',
		'error',
		'year',
		'monthnum',
		'day',
		'p',
		's',
		'm',
		'w',
	);

	if ( function_exists( 'WC' ) && WC()->query ) {
		foreach ( WC()->query->get_query_vars() as $var_key => $var_slug ) {
			$reserved[] = (string) $var_key;
			$reserved[] = (string) $var_slug;
		}
	}

	global $wp;
	if ( $wp instanceof WP && ! empty( $wp->public_query_vars ) ) {
		$reserved = array_merge( $reserved, (array) $wp->public_query_vars );
	}

	$reserved = apply_filters( 'acfw_reserved_item_keys', $reserved );

	return array_values( array_unique( array_filter( array_map( 'strval', (array) $reserved ) ) ) );
}

/**
 * A key for a new menu item that nothing else uses.
 *
 * @param string $label Label ( or base key ) to derive the key from.
 * @param string $type  Item type.
 * @param array  $taken Keys and slugs already used by the menu.
 * @return string
 */
function acfw_unique_item_key( $label, $type = 'endpoint', $taken = array() ) {
	$base  = acfw_item_key_from_label( $label, $type );
	$taken = array_merge( acfw_reserved_item_keys(), array_map( 'strval', (array) $taken ) );
	$key   = $base;
	$n     = 2;

	while ( in_array( $key, $taken, true ) || false !== get_option( 'acfw_item_' . $key, false ) ) {
		$key = $base . '-' . $n;
		++$n;
	}

	return $key;
}

/**
 * Sanitize the menu order tree posted by the builder ( or found in an import ).
 *
 * Keys go through acfw_sanitize_key() only, so keys saved by earlier versions
 * survive unchanged. Types are allow-listed, and only a top-level group may
 * hold children ( one level deep, no nested groups ).
 *
 * @param mixed $tree  Decoded tree: key => { type, children? }.
 * @param int   $depth Current depth ( internal ).
 * @return array
 */
function acfw_sanitize_order_tree( $tree, $depth = 0 ) {
	$clean = array();
	if ( ! is_array( $tree ) ) {
		return $clean;
	}

	$types = class_exists( 'ACFW_Items' ) ? ACFW_Items::ITEM_TYPES : array( 'endpoint', 'group', 'link', 'page' );

	foreach ( $tree as $key => $node ) {
		$key = acfw_sanitize_key( (string) $key );
		if ( '' === $key || ! is_array( $node ) ) {
			continue;
		}

		$type = isset( $node['type'] ) ? sanitize_key( (string) $node['type'] ) : 'endpoint';
		if ( ! in_array( $type, $types, true ) ) {
			$type = 'endpoint';
		}

		$entry = array( 'type' => $type );
		if ( 'group' === $type && 0 === $depth && ! empty( $node['children'] ) ) {
			$entry['children'] = acfw_sanitize_order_tree( $node['children'], 1 );
		}

		$clean[ $key ] = $entry;
	}

	return $clean;
}

/**
 * The menu order tree ( key => { type, children? } ) of a resolved items tree.
 *
 * @param array $items Items tree ( as ACFW_Items::get_items() returns it ).
 * @return array
 */
function acfw_order_from_items( $items ) {
	$order = array();
	foreach ( (array) $items as $key => $item ) {
		$node = array( 'type' => isset( $item['type'] ) ? (string) $item['type'] : 'endpoint' );
		if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
			$node['children'] = acfw_order_from_items( $item['children'] );
		}
		$order[ (string) $key ] = $node;
	}
	return $order;
}

/**
 * Put a new item into the menu order tree.
 *
 * It lands right below $after when that item is in the tree ( at the top
 * level or inside a group ), else at the end of the group $group, else at the
 * end of the menu. A group never goes inside another group: it lands below
 * that group instead.
 *
 * @param array  $order  Order tree: key => { type, children? }.
 * @param string $key    New item's key.
 * @param string $type   New item's type.
 * @param string $after  Key of the item to place it below ( optional ).
 * @param string $group  Key of the group to place it in ( optional ).
 * @return array
 */
function acfw_order_insert( $order, $key, $type, $after = '', $group = '' ) {
	$order = is_array( $order ) ? $order : array();
	$key   = (string) $key;
	$after = (string) $after;
	$group = (string) $group;
	$node  = array( 'type' => (string) $type );
	$nests = 'group' !== $type;

	if ( '' !== $after ) {
		if ( array_key_exists( $after, $order ) ) {
			return acfw_array_insert_after( $order, $after, $key, $node );
		}
		foreach ( $order as $group_key => $entry ) {
			if ( ! empty( $entry['children'] ) && is_array( $entry['children'] ) && array_key_exists( $after, $entry['children'] ) ) {
				if ( ! $nests ) {
					return acfw_array_insert_after( $order, (string) $group_key, $key, $node );
				}
				$order[ $group_key ]['children'] = acfw_array_insert_after( $entry['children'], $after, $key, $node );
				return $order;
			}
		}
	}

	if ( '' !== $group && isset( $order[ $group ]['type'] ) && 'group' === $order[ $group ]['type'] ) {
		if ( ! $nests ) {
			return acfw_array_insert_after( $order, $group, $key, $node );
		}
		$children                    = isset( $order[ $group ]['children'] ) && is_array( $order[ $group ]['children'] ) ? $order[ $group ]['children'] : array();
		$children[ $key ]            = $node;
		$order[ $group ]['children'] = $children;
		return $order;
	}

	$order[ $key ] = $node;
	return $order;
}

/**
 * Insert an entry into an associative array right after a given key.
 *
 * @param array  $entries Array.
 * @param string $after Existing key.
 * @param string $key   New key.
 * @param mixed  $value New value.
 * @return array The array with the entry added ( at the end when $after is missing ).
 */
function acfw_array_insert_after( $entries, $after, $key, $value ) {
	$out = array();
	foreach ( $entries as $k => $v ) {
		$out[ $k ] = $v;
		if ( (string) $k === (string) $after ) {
			$out[ $key ] = $value;
		}
	}
	if ( ! array_key_exists( $key, $out ) ) {
		$out[ $key ] = $value;
	}
	return $out;
}

/**
 * Flatten a menu items tree into key => item, children included.
 *
 * @param array $items Items tree ( as ACFW_Items::get_items() returns it ).
 * @return array
 */
function acfw_flatten_items( $items ) {
	$flat = array();
	foreach ( (array) $items as $key => $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$children = ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) ? $item['children'] : array();
		unset( $item['children'] );
		$flat[ $key ] = $item;
		foreach ( $children as $child_key => $child ) {
			if ( is_array( $child ) ) {
				unset( $child['children'] );
				$flat[ $child_key ] = $child;
			}
		}
	}
	return $flat;
}

/**
 * Parse a list of IDs ( array, or a comma / space separated string ).
 *
 * @param mixed $raw Raw list.
 * @return int[] Unique positive IDs, in the order given.
 */
function acfw_parse_id_list( $raw ) {
	if ( is_string( $raw ) || is_numeric( $raw ) ) {
		$raw = preg_split( '/[\s,]+/', (string) $raw );
	}
	$ids = array_filter( array_map( 'absint', (array) $raw ) );
	return array_values( array_unique( $ids ) );
}

/**
 * Product IDs a visibility rule set requires the customer to have bought.
 *
 * @param array $rules Rules ( vis_products, or vis_product from before 1.1 ).
 * @return int[]
 */
function acfw_rule_product_ids( $rules ) {
	$ids = acfw_parse_id_list( $rules['vis_products'] ?? array() );
	if ( ! empty( $rules['vis_product'] ) ) {
		$ids[] = absint( $rules['vis_product'] );
	}
	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Whether "now" falls inside a whole-day date window, in the site's timezone.
 *
 * @param string   $from Start date ( Y-m-d ), empty for none.
 * @param string   $to   End date ( Y-m-d ), empty for none.
 * @param int|null $now  Timestamp to test ( defaults to now ).
 * @return bool
 */
function acfw_date_window_passes( $from, $to, $now = null ) {
	$now   = null === $now ? time() : (int) $now;
	$zone  = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
	$start = acfw_day_timestamp( $from, $zone, false );
	$end   = acfw_day_timestamp( $to, $zone, true );

	if ( null !== $start && $now < $start ) {
		return false;
	}
	return null === $end || $now <= $end;
}

/**
 * Timestamp of the first or last second of a Y-m-d day in a timezone.
 *
 * @param string       $date       Date ( Y-m-d ).
 * @param DateTimeZone $zone       Timezone.
 * @param bool         $end_of_day Last second instead of the first.
 * @return int|null Null when the date is empty or malformed.
 */
function acfw_day_timestamp( $date, $zone, $end_of_day ) {
	$date = trim( (string) $date );
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
		return null;
	}
	$moment = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $date . ( $end_of_day ? ' 23:59:59' : ' 00:00:00' ), $zone );
	return $moment ? $moment->getTimestamp() : null;
}

/**
 * Whether a set of visibility rules lets a user see something.
 *
 * Shared by menu items and banners. Every rule is optional:
 *  - visibility + usr_roles: 'roles' with a role list limits it to those roles.
 *  - vis_from / vis_to: whole-day window in the site's timezone.
 *  - vis_products ( or the older single vis_product ): bought at least one.
 *  - vis_min_orders: at least this many orders.
 *  - vis_min_spent: at least this much spent in total.
 *
 * Shop managers pass every rule except the date window, so they can preview
 * the account area as any customer would see it.
 *
 * @param array        $rules Rules.
 * @param WP_User|null $user  User ( defaults to the current one ).
 * @return bool
 */
function acfw_visibility_passes( $rules, $user = null ) {
	$rules = is_array( $rules ) ? $rules : array();

	if ( ! acfw_date_window_passes( $rules['vis_from'] ?? '', $rules['vis_to'] ?? '' ) ) {
		return false;
	}

	if ( current_user_can( 'manage_woocommerce' ) ) {
		return true;
	}

	$user = ( $user instanceof WP_User ) ? $user : wp_get_current_user();
	$uid  = acfw_user_id( $user );

	if ( isset( $rules['visibility'] ) && 'roles' === $rules['visibility'] && ! empty( $rules['usr_roles'] ) ) {
		if ( ! array_intersect( (array) $rules['usr_roles'], (array) $user->roles ) ) {
			return false;
		}
	}

	$products = acfw_rule_product_ids( $rules );
	if ( $products && function_exists( 'wc_customer_bought_product' ) ) {
		$bought = false;
		foreach ( $products as $product_id ) {
			if ( $uid && wc_customer_bought_product( $user->user_email, $uid, $product_id ) ) {
				$bought = true;
				break;
			}
		}
		if ( ! $bought ) {
			return false;
		}
	}

	$min_orders = absint( $rules['vis_min_orders'] ?? 0 );
	if ( $min_orders && function_exists( 'wc_get_customer_order_count' ) ) {
		if ( ! $uid || (int) wc_get_customer_order_count( $uid ) < $min_orders ) {
			return false;
		}
	}

	$min_spent = (float) ( $rules['vis_min_spent'] ?? 0 );
	if ( $min_spent > 0 && function_exists( 'wc_get_customer_total_spent' ) ) {
		if ( ! $uid || (float) wc_get_customer_total_spent( $uid ) < $min_spent ) {
			return false;
		}
	}

	return true;
}

/**
 * The endpoint customers land on when they open My Account.
 *
 * @return string Endpoint key; 'dashboard' when none is set.
 */
function acfw_default_endpoint() {
	$endpoint = acfw_sanitize_key( (string) get_option( 'acfw_default_endpoint', 'dashboard' ) );
	if ( '' === $endpoint || 'customer-logout' === $endpoint ) {
		$endpoint = 'dashboard';
	}
	return (string) apply_filters( 'acfw_default_endpoint', $endpoint );
}

/**
 * URL of the account dashboard.
 *
 * When another endpoint is the landing page, the bare My Account URL
 * redirects there, so the dashboard moves to its own /dashboard/ endpoint.
 *
 * @return string
 */
function acfw_dashboard_url() {
	$base = wc_get_page_permalink( 'myaccount' );
	if ( 'dashboard' === acfw_default_endpoint() ) {
		return $base;
	}
	return wc_get_endpoint_url( 'dashboard', '', $base );
}

/**
 * Whether a menu item key is the page being viewed.
 *
 * Mirrors WooCommerce's own menu: Orders stays current on a single order,
 * Payment methods while adding one.
 *
 * @param string $key Item key.
 * @return bool
 */
function acfw_is_current_item( $key ) {
	$current = acfw_get_current_endpoint();
	if ( $key === $current ) {
		return true;
	}
	$aliases = array(
		'orders'          => array( 'view-order' ),
		'payment-methods' => array( 'add-payment-method' ),
	);
	return isset( $aliases[ $key ] ) && in_array( $current, $aliases[ $key ], true );
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
 * yourtheme/my-account-dashboard-builder/{template}.
 *
 * @param string $template Template filename.
 * @param array  $args     Variables passed to the template.
 */
function acfw_get_template( $template, $args = array() ) {

	// Every caller passes a literal file name. Enforce that structurally so a
	// variable can never reach the include: bare name, .php only, no traversal.
	$template = basename( (string) $template );

	if ( '' === $template || '.php' !== substr( $template, -4 ) || false !== strpos( $template, '..' ) ) {
		return;
	}

	if ( is_array( $args ) ) {
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- template variables.
	}

	$override = locate_template(
		array(
			'my-account-dashboard-builder/' . $template,
		)
	);

	$path = $override ? $override : ACFW_TEMPLATE_PATH . '/' . $template;
	$real = realpath( $path );

	if ( ! $real || ! is_file( $real ) ) {
		return;
	}

	// The resolved file must live in the plugin's template directory or in the
	// active theme ( where an override legitimately lives ).
	$roots = array_filter(
		array(
			realpath( ACFW_TEMPLATE_PATH ),
			realpath( get_stylesheet_directory() ),
			realpath( get_template_directory() ),
		)
	);

	foreach ( $roots as $root ) {
		if ( 0 === strpos( $real, $root . DIRECTORY_SEPARATOR ) ) {
			include $real; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- validated against an allowlist of roots above.
			return;
		}
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
			'{display_name}'    => __( 'Display name', 'my-account-dashboard-builder' ),
			'{first_name}'      => __( 'First name', 'my-account-dashboard-builder' ),
			'{last_name}'       => __( 'Last name', 'my-account-dashboard-builder' ),
			'{username}'        => __( 'Username', 'my-account-dashboard-builder' ),
			'{user_email}'      => __( 'Email address', 'my-account-dashboard-builder' ),
			'{site_title}'      => __( 'Site title', 'my-account-dashboard-builder' ),
			'{order_count}'     => __( 'Order count', 'my-account-dashboard-builder' ),
			'{download_count}'  => __( 'Download count', 'my-account-dashboard-builder' ),
			'{last_login}'      => __( 'Last login date', 'my-account-dashboard-builder' ),
			'{member_since}'    => __( 'Registration date', 'my-account-dashboard-builder' ),
			'{total_spent}'     => __( 'Total spent', 'my-account-dashboard-builder' ),
			'{cart_count}'      => __( 'Items in cart', 'my-account-dashboard-builder' ),
			'{billing_phone}'   => __( 'Billing phone', 'my-account-dashboard-builder' ),
			'{billing_city}'    => __( 'Billing city', 'my-account-dashboard-builder' ),
			'{billing_country}' => __( 'Billing country', 'my-account-dashboard-builder' ),
			'{points_balance}'  => __( 'Points balance', 'my-account-dashboard-builder' ),
			'{membership_plan}' => __( 'Membership plan', 'my-account-dashboard-builder' ),
			'{account_url}'     => __( 'My Account URL', 'my-account-dashboard-builder' ),
			'{shop_url}'        => __( 'Shop URL', 'my-account-dashboard-builder' ),
			'{site_url}'        => __( 'Site URL', 'my-account-dashboard-builder' ),
		)
	);
}

/**
 * Dynamic item count for a menu key ( orders / downloads ), for badges.
 *
 * Counted once per request: the menu, the dashboard tiles, the stats and the
 * smart tags can all ask for the same number on one page.
 *
 * @param string $key  Endpoint key.
 * @param int    $uid  User ID ( 0 = current ).
 * @return int|null Count, or null when not a countable endpoint.
 */
function acfw_endpoint_count( $key, $uid = 0 ) {
	static $memo = array();

	$uid = $uid ? (int) $uid : get_current_user_id();
	if ( ! $uid || ! in_array( $key, array( 'orders', 'downloads' ), true ) ) {
		return null;
	}

	$memo_key = $key . '|' . $uid;
	if ( array_key_exists( $memo_key, $memo ) ) {
		return $memo[ $memo_key ];
	}

	$count = null;
	if ( 'orders' === $key && function_exists( 'wc_get_customer_order_count' ) ) {
		$count = (int) wc_get_customer_order_count( $uid );
	} elseif ( 'downloads' === $key && function_exists( 'wc_get_customer_available_downloads' ) ) {
		$count = count( wc_get_customer_available_downloads( $uid ) );
	}

	$memo[ $memo_key ] = $count;
	return $count;
}

/**
 * Plain-text price ( wc_price() output without its markup or entities ).
 *
 * @param float $amount Amount.
 * @return string
 */
function acfw_plain_price( $amount ) {
	if ( ! function_exists( 'wc_price' ) ) {
		return (string) $amount;
	}
	return trim( html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' ) );
}

/**
 * Resolve one smart tag for a user.
 *
 * Values that cost a query are remembered for the rest of the request.
 *
 * @param string  $token Token, e.g. "{first_name}".
 * @param WP_User $user  User.
 * @return string|null Null for an unknown token ( it is left in place ).
 */
function acfw_smart_tag_value( $token, $user ) {
	static $memo = array();

	$uid      = acfw_user_id( $user );
	$memo_key = $uid . '|' . $token;
	$costly   = array( '{order_count}', '{download_count}', '{total_spent}', '{points_balance}', '{membership_plan}' );

	if ( in_array( $token, $costly, true ) && array_key_exists( $memo_key, $memo ) ) {
		return $memo[ $memo_key ];
	}

	switch ( $token ) {
		case '{display_name}':
		case '%%customer_name%%':
			$value = $user->display_name ?? '';
			break;
		case '{first_name}':
			$value = $user->first_name ?? '';
			break;
		case '{last_name}':
			$value = $user->last_name ?? '';
			break;
		case '{username}':
			$value = $user->user_login ?? '';
			break;
		case '{user_email}':
			$value = $user->user_email ?? '';
			break;
		case '{site_title}':
			$value = get_bloginfo( 'name' );
			break;
		case '{order_count}':
			$value = (string) ( $uid ? (int) acfw_endpoint_count( 'orders', $uid ) : 0 );
			break;
		case '{download_count}':
			$value = (string) ( $uid ? (int) acfw_endpoint_count( 'downloads', $uid ) : 0 );
			break;
		case '{last_login}':
			$last  = $uid ? (int) get_user_meta( $uid, 'acfw_last_login', true ) : 0;
			$value = $last ? wp_date( get_option( 'date_format' ), $last ) : '';
			break;
		case '{member_since}':
			$registered = ! empty( $user->user_registered ) ? strtotime( $user->user_registered . ' UTC' ) : false;
			$value      = $registered ? wp_date( get_option( 'date_format' ), $registered ) : '';
			break;
		case '{total_spent}':
			$value = ( $uid && function_exists( 'wc_get_customer_total_spent' ) ) ? acfw_plain_price( wc_get_customer_total_spent( $uid ) ) : '';
			break;
		case '{cart_count}':
			$value = ( function_exists( 'WC' ) && WC()->cart ) ? (string) WC()->cart->get_cart_contents_count() : '0';
			break;
		case '{billing_phone}':
			$value = $uid ? (string) get_user_meta( $uid, 'billing_phone', true ) : '';
			break;
		case '{billing_city}':
			$value = $uid ? (string) get_user_meta( $uid, 'billing_city', true ) : '';
			break;
		case '{billing_country}':
			$code      = $uid ? (string) get_user_meta( $uid, 'billing_country', true ) : '';
			$countries = ( '' !== $code && function_exists( 'WC' ) && WC()->countries ) ? WC()->countries->get_countries() : array();
			$value     = isset( $countries[ $code ] ) ? html_entity_decode( $countries[ $code ], ENT_QUOTES, 'UTF-8' ) : $code;
			break;
		case '{points_balance}':
			$value = acfw_points_balance( $uid );
			break;
		case '{membership_plan}':
			$value = acfw_membership_plan( $uid );
			break;
		case '{account_url}':
			$value = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
			break;
		case '{shop_url}':
			$value = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
			break;
		case '{site_url}':
			$value = home_url( '/' );
			break;
		default:
			$value = null;
	}

	if ( in_array( $token, $costly, true ) ) {
		$memo[ $memo_key ] = $value;
	}

	return $value;
}

/**
 * Replace smart tags in a string with the current user's values.
 *
 * Only the tokens present in the string are resolved, so an order or
 * download count is never queried for text that does not show it.
 *
 * @param string       $content Raw content.
 * @param WP_User|null $user    User ( defaults to current ).
 * @param bool         $escape  HTML-escape the values ( for HTML content ).
 *                              Pass false for plain text that is escaped
 *                              as a whole afterwards.
 * @return string
 */
function acfw_apply_smart_tags( $content, $user = null, $escape = true ) {

	$content = (string) $content;

	if ( false === strpos( $content, '{' ) && false === strpos( $content, '%%' ) ) {
		return $content;
	}

	$user = $user ? $user : wp_get_current_user();

	preg_match_all( '/\{[a-z0-9_]+\}/', $content, $found );
	$tokens = array_unique( $found[0] );
	if ( false !== strpos( $content, '%%customer_name%%' ) ) {
		// Back-compat token.
		$tokens[] = '%%customer_name%%';
	}

	$map = array();
	foreach ( $tokens as $token ) {
		$value = acfw_smart_tag_value( $token, $user );
		if ( null !== $value ) {
			$map[ $token ] = (string) $value;
		}
	}

	$map = apply_filters( 'acfw_smart_tag_values', $map, $user );
	if ( empty( $map ) || ! is_array( $map ) ) {
		return $content;
	}

	$values = array_map( 'strval', array_values( $map ) );
	if ( $escape ) {
		$values = array_map( 'esc_html', $values );
	}

	return str_replace( array_keys( $map ), $values, $content );
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
		'banner_slug'      => '',       // Deprecated single value, still read for data saved before 1.0.
		'banner_slugs'     => array(),
		'banner_position'  => 'top',    // top | bottom.
		'badge'            => '',       // Custom pill text; replaces the item count.
	) + acfw_default_rule_options();
}

/**
 * Default visibility rules shared by every item type.
 *
 * @return array
 */
function acfw_default_rule_options() {
	return array(
		'vis_from'       => '',
		'vis_to'         => '',
		'vis_products'   => array(),
		'vis_min_orders' => 0,
		'vis_min_spent'  => 0,
	);
}

/**
 * Inline SVG icons for the admin option controls.
 *
 * Drawn rather than shipped as images so they stay crisp on any display and
 * pick up the surrounding text colour through currentColor.
 *
 * @param string $name Icon name.
 * @return string SVG markup, or an empty string when the name is unknown.
 */
function acfw_ui_icon( $name ) {

	$open  = '<svg class="acfw-ui-icon" width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">';
	$close = '</svg>';

	$paths = array(
		// Four squares: the widget layout.
		'widget'   => '<rect x="2.5" y="2.5" width="6.5" height="6.5" rx="1.6" fill="currentColor"/><rect x="11" y="2.5" width="6.5" height="6.5" rx="1.6" fill="currentColor"/><rect x="2.5" y="11" width="6.5" height="6.5" rx="1.6" fill="currentColor"/><rect x="11" y="11" width="6.5" height="6.5" rx="1.6" fill="currentColor"/>',
		// Framed picture with a hill and a sun.
		'image'    => '<rect x="2.5" y="3.5" width="15" height="13" rx="2.2" stroke="currentColor" stroke-width="1.6"/><circle cx="7.3" cy="8" r="1.5" fill="currentColor"/><path d="M3.5 14.5 8 10.4l3.1 2.8 2.6-2.3 2.8 3.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
		// Cloud with an upward arrow.
		'upload'   => '<path d="M5.6 15.5a3.6 3.6 0 0 1-.3-7.2 4.7 4.7 0 0 1 9 .8 3.2 3.2 0 0 1-.6 6.4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 17.5V9.2m0 0L7.7 11.5M10 9.2l2.3 2.3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
		// Sheet of paper with lines: the classic editor.
		'classic'  => '<path d="M5 2.8h6.4L16 7.2v10a1.6 1.6 0 0 1-1.6 1.6H5A1.6 1.6 0 0 1 3.4 17V4.4A1.6 1.6 0 0 1 5 2.8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M11.2 3v4.4H16M6.4 11h7M6.4 14h4.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
		// Cube: the block editor.
		'block'    => '<path d="m10 2.6 6.4 3.6v7.6L10 17.4 3.6 13.8V6.2L10 2.6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m3.8 6.3 6.2 3.5 6.2-3.5M10 17.2V9.8" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
		// Solid dot, used to colour the notice-style options.
		'dot'      => '<circle cx="10" cy="10" r="5" fill="currentColor"/>',
		'dot-ring' => '<circle cx="10" cy="10" r="5.2" stroke="currentColor" stroke-width="1.8"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return $open . $paths[ $name ] . $close;
}

/**
 * Tags allowed when printing acfw_ui_icon() output.
 *
 * @return array
 */
function acfw_svg_kses() {
	$attrs = array(
		'class'           => true,
		'width'           => true,
		'height'          => true,
		'viewbox'         => true,
		'fill'            => true,
		'stroke'          => true,
		'stroke-width'    => true,
		'stroke-linecap'  => true,
		'stroke-linejoin' => true,
		'aria-hidden'     => true,
		'focusable'       => true,
		'xmlns'           => true,
		'x'               => true,
		'y'               => true,
		'rx'              => true,
		'cx'              => true,
		'cy'              => true,
		'r'               => true,
		'd'               => true,
	);

	return array(
		'svg'    => $attrs,
		'rect'   => $attrs,
		'circle' => $attrs,
		'path'   => $attrs,
	);
}

/**
 * Banners assigned to an item, as a list of slugs.
 *
 * Accepts both shapes: the multi-select `banner_slugs` array, and the single
 * `banner_slug` string saved by earlier versions.
 *
 * @param array $item Item options.
 * @return array
 */
function acfw_item_banner_slugs( $item ) {

	$slugs = array();

	if ( ! empty( $item['banner_slugs'] ) && is_array( $item['banner_slugs'] ) ) {
		$slugs = $item['banner_slugs'];
	} elseif ( ! empty( $item['banner_slug'] ) ) {
		$slugs = array( $item['banner_slug'] );
	}

	$slugs = array_values( array_unique( array_filter( array_map( 'strval', $slugs ) ) ) );

	// Drop anything that no longer exists, so a deleted banner cannot linger.
	if ( class_exists( 'ACFW_Banners' ) ) {
		$known = array_keys( ACFW_Banners::all() );
		$slugs = array_values( array_intersect( $slugs, $known ) );
	}

	return $slugs;
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
	) + acfw_default_rule_options();
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
		'badge'        => '',
	) + acfw_default_rule_options();
}

/**
 * Default option set for a page item ( links to an existing WordPress page ).
 *
 * @return array
 */
function acfw_default_page_options() {
	return array(
		'type'         => 'page',
		'label'        => '',
		'icon'         => '',
		'icon_url'     => '',
		'icon_source'  => 'choose',
		'active'       => true,
		'page_id'      => 0,
		'target_blank' => false,
		'visibility'   => 'all',
		'usr_roles'    => array(),
		'class'        => '',
		'badge'        => '',
	) + acfw_default_rule_options();
}
