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
