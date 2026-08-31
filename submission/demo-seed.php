<?php
/**
 * Demo-site seeder — NOT part of the plugin, never ship this in the zip.
 *
 * Drop it in the WordPress root of the demo site, set ACFW_SEED_TOKEN below,
 * hit https://demo.example.com/demo-seed.php?k=YOUR_TOKEN once, then DELETE IT.
 *
 * Creates the state the marketplace reviewer needs: a customer with a completed
 * order, a second processing order, and every optional feature switched on.
 *
 * @package AccountCustomizerForWooCommerce
 */

define( 'ACFW_SEED_TOKEN', 'change-me-before-use' );

require_once __DIR__ . '/wp-load.php';

if ( ! isset( $_GET['k'] ) || ! hash_equals( ACFW_SEED_TOKEN, wp_unslash( $_GET['k'] ) ) ) {
	wp_die( 'nope' );
}

header( 'Content-Type: text/plain' );

$acfw_seed_options = array(
	'acfw_buyagain_enable' => 'yes',
	'acfw_recent_enable'   => 'yes',
	'acfw_tracking_enable' => 'yes',
	'acfw_dashboard_stats' => 'yes',
	'acfw_dashboard_tiles' => 'yes',
	'acfw_profile_meter'   => 'yes',
	'acfw_avatar_upload'   => 'yes',
	'acfw_avatar_enable'   => 'yes',
	'acfw_show_icons'      => 'yes',
	'acfw_show_counts'     => 'yes',
);

foreach ( $acfw_seed_options as $acfw_name => $acfw_value ) {
	update_option( $acfw_name, $acfw_value );
}
echo "features enabled\n";

// Reviewer login. Change the password before handing the site over.
$acfw_login = 'reviewer';
$acfw_pass  = 'ChangeThisPassword123!';
$acfw_user  = get_user_by( 'login', $acfw_login );

if ( ! $acfw_user ) {
	$acfw_uid = wp_insert_user(
		array(
			'user_login' => $acfw_login,
			'user_pass'  => $acfw_pass,
			'user_email' => 'reviewer@example.test',
			'first_name' => 'Jamie',
			'last_name'  => 'Fletcher',
			'role'       => 'customer',
		)
	);
	if ( is_wp_error( $acfw_uid ) ) {
		echo 'user error: ' . $acfw_uid->get_error_message() . "\n";
		exit;
	}
	echo "customer created: $acfw_uid / $acfw_pass\n";
} else {
	$acfw_uid = $acfw_user->ID;
	echo "customer exists: $acfw_uid\n";
}

$acfw_address = array(
	'first_name' => 'Jamie',
	'last_name'  => 'Fletcher',
	'company'    => 'Fletcher Studio',
	'address_1'  => '14 Kingsway',
	'city'       => 'Manchester',
	'postcode'   => 'M1 4AH',
	'country'    => 'GB',
	'phone'      => '+44 161 496 0212',
	'email'      => 'reviewer@example.test',
);
foreach ( $acfw_address as $acfw_key => $acfw_value ) {
	update_user_meta( $acfw_uid, 'billing_' . $acfw_key, $acfw_value );
}
echo "billing address set ( drives the profile completeness meter )\n";

$acfw_existing = wc_get_orders(
	array(
		'customer_id' => $acfw_uid,
		'limit'       => 1,
		'return'      => 'ids',
	)
);

if ( ! $acfw_existing ) {
	$acfw_products = get_posts(
		array(
			'post_type'      => 'product',
			'posts_per_page' => 3,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		)
	);

	// Completed order: powers the Buy again tab and the spend/order stats.
	$acfw_order = wc_create_order( array( 'customer_id' => $acfw_uid ) );
	foreach ( $acfw_products as $acfw_pid ) {
		$acfw_product = wc_get_product( $acfw_pid );
		if ( $acfw_product && $acfw_product->is_purchasable() ) {
			$acfw_order->add_product( $acfw_product, 1 );
		}
	}
	$acfw_order->set_address( $acfw_address, 'billing' );
	$acfw_order->calculate_totals();
	$acfw_order->set_status( 'completed' );
	$acfw_order->save();
	echo 'completed order: ' . $acfw_order->get_id() . "\n";

	// Processing order: gives the order-tracking widget something to show.
	$acfw_order2 = wc_create_order( array( 'customer_id' => $acfw_uid ) );
	$acfw_first  = wc_get_product( $acfw_products[0] );
	if ( $acfw_first ) {
		$acfw_order2->add_product( $acfw_first, 2 );
	}
	$acfw_order2->set_address( $acfw_address, 'billing' );
	$acfw_order2->calculate_totals();
	$acfw_order2->set_status( 'processing' );
	$acfw_order2->save();
	echo 'processing order: ' . $acfw_order2->get_id() . "\n";
} else {
	echo 'orders already exist: ' . implode( ',', $acfw_existing ) . "\n";
}

update_option( 'acfw_flush_rewrite_rules', 1 );
if ( function_exists( 'ACFW' ) && ACFW()->items ) {
	ACFW()->items->build( true );
}
flush_rewrite_rules();

echo "rewrite rules flushed\ndone — now delete this file\n";
