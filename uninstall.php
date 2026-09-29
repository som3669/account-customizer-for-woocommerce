<?php
/**
 * Uninstall cleanup: removes every trace of the plugin.
 *
 * Deleting the plugin drops all `acfw_` options and transients, the usage
 * table, return requests ( with their photos ), the user meta keys the plugin
 * writes, and the avatar images customers uploaded through it. Coupons made
 * for personal offers are store coupons customers may still hold, so they
 * stay. Nothing is removed on deactivation.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * User meta keys written by the plugin ( global: shared across a network ).
 *
 * @var array
 */
$acfw_user_meta = array( 'acfw_avatar_id', 'acfw_last_login', 'acfw_order_stats', 'acfw_addresses' );

/**
 * User meta key prefixes: one key per offer ( acfw_offer_{banner} ) and per
 * customer field ( acfw_field_{key} ).
 *
 * @var array
 */
$acfw_user_meta_prefixes = array( 'acfw_offer_', 'acfw_field_' );

/**
 * Delete the plugin's per-site data: options, transients and avatar files.
 *
 * User meta is network-wide, so it is purged once after every site is done.
 *
 * @return void
 */
function acfw_uninstall_site() {

	global $wpdb;

	// Delete the attachments behind customer-uploaded avatars. The IDs are
	// network-wide; get_post_type() filters out those belonging to other sites.
	$acfw_avatar_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
			'acfw_avatar_id'
		)
	);

	foreach ( (array) $acfw_avatar_ids as $acfw_avatar_id ) {
		$acfw_avatar_id = (int) $acfw_avatar_id;
		if ( $acfw_avatar_id && 'attachment' === get_post_type( $acfw_avatar_id ) ) {
			wp_delete_attachment( $acfw_avatar_id, true );
		}
	}

	// Return requests, and the photos customers sent with them.
	$acfw_returns = get_posts(
		array(
			'post_type'   => 'acfw_return',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);
	foreach ( $acfw_returns as $acfw_return ) {
		$acfw_photos = get_children(
			array(
				'post_parent' => $acfw_return,
				'post_type'   => 'attachment',
				'fields'      => 'ids',
			)
		);
		foreach ( $acfw_photos as $acfw_photo ) {
			wp_delete_attachment( (int) $acfw_photo, true );
		}
		wp_delete_post( (int) $acfw_return, true );
	}

	// Usage counts for Insights.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}acfw_stats" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- fixed table name.

	// Every plugin option is prefixed `acfw_`; presets, banners, items and
	// design settings are all covered by the one pattern.
	$acfw_like = $wpdb->esc_like( 'acfw_' ) . '%';
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $acfw_like )
	);

	// Transients live under a prefixed option name ( _transient_acfw_… ),
	// which the pattern above does not match.
	delete_transient( 'acfw_import_notice' );
	foreach ( array( '_transient_acfw_', '_transient_timeout_acfw_' ) as $acfw_prefix ) {
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $acfw_prefix ) . '%' )
		);
	}

	wp_cache_flush();
}

if ( is_multisite() ) {
	$acfw_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $acfw_site_ids as $acfw_site_id ) {
		switch_to_blog( $acfw_site_id );
		acfw_uninstall_site();
		restore_current_blog();
	}
} else {
	acfw_uninstall_site();
}

foreach ( $acfw_user_meta as $acfw_meta_key ) {
	delete_metadata( 'user', 0, $acfw_meta_key, '', true );
}

// uninstall.php runs inside a WordPress function, not at global scope.
global $wpdb;
foreach ( $acfw_user_meta_prefixes as $acfw_meta_prefix ) {
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $wpdb->esc_like( $acfw_meta_prefix ) . '%' )
	);
}
wp_cache_flush();
