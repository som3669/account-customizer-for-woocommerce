<?php
/**
 * Tests for the visibility rules shared by menu items and banners.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;

class VisibilityRulesTest extends TestCase {

	/**
	 * The customer the rules are checked against.
	 *
	 * @var object
	 */
	private $customer;

	protected function setUp(): void {
		parent::setUp();

		$this->customer = (object) array(
			'ID'         => 42,
			'roles'      => array( 'customer' ),
			'user_email' => 'jo@example.test',
		);

		// Nepal is UTC+05:45: a date window read in UTC opens 5h45m late there.
		Functions\when( 'wp_timezone' )->justReturn( new DateTimeZone( 'Asia/Kathmandu' ) );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'wp_get_current_user' )->justReturn( $this->customer );
		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);
	}

	private function local_time( string $local ): int {
		return ( new DateTimeImmutable( $local, new DateTimeZone( 'Asia/Kathmandu' ) ) )->getTimestamp();
	}

	public function test_the_date_window_uses_the_site_timezone(): void {
		// 00:30 on the start day, local time: already inside the window.
		$this->assertTrue( acfw_date_window_passes( '2026-10-01', '', $this->local_time( '2026-10-01 00:30:00' ) ) );
		$this->assertFalse( acfw_date_window_passes( '2026-10-01', '', $this->local_time( '2026-09-30 23:59:00' ) ) );
	}

	public function test_the_end_date_includes_the_whole_day(): void {
		$this->assertTrue( acfw_date_window_passes( '', '2026-10-31', $this->local_time( '2026-10-31 23:59:00' ) ) );
		$this->assertFalse( acfw_date_window_passes( '', '2026-10-31', $this->local_time( '2026-11-01 00:00:01' ) ) );
	}

	public function test_malformed_dates_are_ignored(): void {
		$now = $this->local_time( '2026-10-15 12:00:00' );
		$this->assertTrue( acfw_date_window_passes( 'next week', '2026-02-31', $now ) );
		$this->assertTrue( acfw_date_window_passes( '', '', $now ) );
	}

	public function test_id_lists_accept_arrays_and_text(): void {
		$this->assertSame( array( 12, 34, 5 ), acfw_parse_id_list( '12, 34 5,12' ) );
		$this->assertSame( array( 7, 9 ), acfw_parse_id_list( array( '7', 0, -9, 'x' ) ) );
		$this->assertSame( array(), acfw_parse_id_list( '' ) );
	}

	public function test_the_older_single_product_rule_still_counts(): void {
		$this->assertSame( array( 3, 8 ), acfw_rule_product_ids( array( 'vis_products' => array( 3 ), 'vis_product' => 8 ) ) );
	}

	public function test_no_rules_means_everyone(): void {
		$this->assertTrue( acfw_visibility_passes( array() ) );
	}

	public function test_roles(): void {
		$this->assertTrue( acfw_visibility_passes( array( 'visibility' => 'roles', 'usr_roles' => array( 'customer' ) ) ) );
		$this->assertFalse( acfw_visibility_passes( array( 'visibility' => 'roles', 'usr_roles' => array( 'wholesale' ) ) ) );
	}

	public function test_bought_any_of_the_products(): void {
		Functions\when( 'wc_customer_bought_product' )->alias(
			function ( $email, $user_id, $product_id ) {
				return 42 === $user_id && 55 === $product_id;
			}
		);

		$this->assertTrue( acfw_visibility_passes( array( 'vis_products' => array( 10, 55 ) ) ), 'One match is enough' );
		$this->assertFalse( acfw_visibility_passes( array( 'vis_products' => array( 10, 11 ) ) ) );
	}

	public function test_order_count_and_spend_thresholds(): void {
		Functions\when( 'wc_get_customer_order_count' )->justReturn( 3 );
		Functions\when( 'wc_get_customer_total_spent' )->justReturn( '149.99' );

		$this->assertTrue( acfw_visibility_passes( array( 'vis_min_orders' => 3 ) ) );
		$this->assertFalse( acfw_visibility_passes( array( 'vis_min_orders' => 4 ) ) );
		$this->assertTrue( acfw_visibility_passes( array( 'vis_min_spent' => 149.99 ) ) );
		$this->assertFalse( acfw_visibility_passes( array( 'vis_min_spent' => 150 ) ) );
	}

	public function test_every_rule_has_to_pass(): void {
		Functions\when( 'wc_get_customer_order_count' )->justReturn( 10 );

		$rules = array(
			'visibility'     => 'roles',
			'usr_roles'      => array( 'wholesale' ),
			'vis_min_orders' => 2,
		);

		$this->assertFalse( acfw_visibility_passes( $rules ) );
	}

	public function test_shop_managers_see_everything_but_the_date_window(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->assertTrue( acfw_visibility_passes( array( 'visibility' => 'roles', 'usr_roles' => array( 'wholesale' ), 'vis_min_orders' => 99 ) ) );
		$this->assertFalse( acfw_visibility_passes( array( 'vis_to' => '2000-01-01' ) ), 'An expired item is expired for them too' );
	}

	public function test_a_logged_out_visitor_fails_customer_rules(): void {
		Functions\when( 'wp_get_current_user' )->justReturn( (object) array( 'ID' => 0, 'roles' => array(), 'user_email' => '' ) );
		Functions\when( 'wc_get_customer_order_count' )->justReturn( 0 );

		$this->assertFalse( acfw_visibility_passes( array( 'vis_min_orders' => 1 ) ) );
		$this->assertFalse( acfw_visibility_passes( array( 'visibility' => 'roles', 'usr_roles' => array( 'customer' ) ) ) );
	}
}
