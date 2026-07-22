<?php
/**
 * Shared helper functions.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Full FontAwesome icon list ( class strings ) used by icon pickers.
 *
 * @return array
 */
function acfw_icon_list() {
	$list = include ACFW_DIR . 'includes/icon-list.php';
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
			'{display_name}' => __( 'Display name', 'account-customizer-for-woocommerce' ),
			'{first_name}'   => __( 'First name', 'account-customizer-for-woocommerce' ),
			'{last_name}'    => __( 'Last name', 'account-customizer-for-woocommerce' ),
			'{username}'     => __( 'Username', 'account-customizer-for-woocommerce' ),
			'{user_email}'   => __( 'Email address', 'account-customizer-for-woocommerce' ),
			'{site_title}'   => __( 'Site title', 'account-customizer-for-woocommerce' ),
			'{order_count}'  => __( 'Order count', 'account-customizer-for-woocommerce' ),
			'{download_count}' => __( 'Download count', 'account-customizer-for-woocommerce' ),
			'{last_login}'   => __( 'Last login date', 'account-customizer-for-woocommerce' ),
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
		'{display_name}' => $user->display_name ?? '',
		'{first_name}'   => $user->first_name ?? '',
		'{last_name}'    => $user->last_name ?? '',
		'{username}'     => $user->user_login ?? '',
		'{user_email}'   => $user->user_email ?? '',
		'{site_title}'   => get_bloginfo( 'name' ),
		'{order_count}'  => (string) $orders,
		'{download_count}' => (string) ( null === $downloads ? 0 : $downloads ),
		'{last_login}'   => $last_login ? date_i18n( get_option( 'date_format' ), (int) $last_login ) : '',
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
 * @param string $key Item key.
 * @return array
 */
function acfw_default_group_options( $key = '' ) {
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
 * @param string $key Item key.
 * @return array
 */
function acfw_default_link_options( $key = '' ) {
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
