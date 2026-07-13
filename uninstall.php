<?php
/**
 * Uninstall cleanup.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Remove global options.
$options = array(
	'acfw_items_order',
	'acfw_flush_rewrite_rules',
	'acfw_menu_position',
	'acfw_menu_layout',
	'acfw_ajax_navigation',
	'acfw_default_endpoint',
	'acfw_accent_color',
	'acfw_text_color',
	'acfw_menu_radius',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Remove per-item options (acfw_item_*).
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'acfw\\_item\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
