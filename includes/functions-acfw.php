<?php
/**
 * Shared helper functions.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

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
		)
	);
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

	$map = array(
		'{display_name}' => $user->display_name ?? '',
		'{first_name}'   => $user->first_name ?? '',
		'{last_name}'    => $user->last_name ?? '',
		'{username}'     => $user->user_login ?? '',
		'{user_email}'   => $user->user_email ?? '',
		'{site_title}'   => get_bloginfo( 'name' ),
		'{order_count}'  => (string) $orders,
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
