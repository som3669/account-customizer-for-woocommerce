<?php
/**
 * Tests for "View as a customer", status badges and the dashboard arrangement.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

use ACFW_View_As;
use Brain\Monkey\Functions;
use ReflectionClass;

class PreviewCustomerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( '_n' )->alias(
			function ( $single, $plural, $n ) {
				return 1 === (int) $n ? $single : $plural;
			}
		);
		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);
		Functions\when( 'wp_unslash' )->returnArg();
		$this->reset_view_as();
	}

	protected function tearDown(): void {
		$this->reset_view_as();
		$_GET    = array();
		$_SERVER = array_diff_key( $_SERVER, array_flip( array( 'REQUEST_METHOD', 'REQUEST_URI' ) ) );
		parent::tearDown();
	}

	private function reset_view_as(): void {
		$ref = new ReflectionClass( ACFW_View_As::class );
		foreach ( array( 'real' => 0, 'target' => 0, 'refused' => false ) as $prop => $value ) {
			$p = $ref->getProperty( $prop );
			$p->setAccessible( true );
			$p->setValue( null, $value );
		}
	}

	// ---- Dashboard arrangement ------------------------------------------------------

	public function test_an_empty_arrangement_is_the_default_order(): void {
		$this->assertSame( 'notice,welcome,title,stats,meter,tiles,tracking,buyagain,others', acfw_sanitize_dashboard_layout( '' ) );
	}

	public function test_an_arrangement_keeps_known_parts_once_and_their_hidden_mark(): void {
		$this->assertSame(
			'notice,tiles,tracking,buyagain,others,-welcome,title,stats,meter',
			acfw_sanitize_dashboard_layout( ' Tiles , -welcome,stats,script,stats,-stats' ),
			'Unknown and repeated parts go; each missing one follows its default neighbour'
		);
	}

	public function test_a_part_missing_from_a_saved_arrangement_goes_after_its_neighbour(): void {
		// Parts a saved order does not know yet: each after the part before it
		// in the default order ( "meter" after "stats", "title" after "welcome" ).
		$this->assertSame(
			'notice,others,stats,meter,tiles,tracking,buyagain,welcome,title',
			acfw_sanitize_dashboard_layout( 'others,stats,welcome' )
		);
	}

	public function test_the_arrangement_in_use_reads_shown_and_hidden(): void {
		Functions\when( 'get_option' )->justReturn( 'stats,-welcome' );

		$layout = acfw_dashboard_layout();

		$this->assertSame( array( 'notice', 'stats', 'meter' ), array_slice( array_keys( $layout ), 0, 3 ) );
		$this->assertTrue( $layout['stats'] );
		$this->assertFalse( $layout['welcome'] );
		$this->assertTrue( $layout['others'] );
	}

	// ---- Why a rule hides something -------------------------------------------------

	private function customer( int $id = 42 ) {
		return (object) array(
			'ID'         => $id,
			'roles'      => array( 'customer' ),
			'user_email' => 'jo@example.test',
		);
	}

	public function test_every_failed_rule_is_listed_with_what_was_measured(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'wp_get_current_user' )->justReturn( $this->customer() );
		Functions\when( 'wc_get_customer_order_count' )->justReturn( 1 );
		Functions\when( 'wc_get_customer_total_spent' )->justReturn( '189' );

		$rules = array(
			'visibility'     => 'roles',
			'usr_roles'      => array( 'wholesale' ),
			'vis_min_orders' => 3,
			'vis_min_spent'  => 500,
		);
		$fails = acfw_visibility_check( $rules );

		$this->assertSame( array( 'roles', 'min_orders', 'min_spent' ), array_keys( $fails ) );
		$this->assertSame( array( 'need' => 3, 'has' => 1 ), $fails['min_orders'] );
		$this->assertSame( 189.0, $fails['min_spent']['has'] );
		$this->assertCount( 1, acfw_visibility_check( $rules, null, true ), 'Stops at the first when asked' );
		$this->assertFalse( acfw_visibility_passes( $rules ) );
	}

	public function test_managers_pass_but_the_check_still_explains(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_get_current_user' )->justReturn( $this->customer() );
		Functions\when( 'wc_get_customer_order_count' )->justReturn( 0 );

		$this->assertTrue( acfw_visibility_passes( array( 'vis_min_orders' => 2 ) ) );
		$this->assertArrayHasKey( 'min_orders', acfw_visibility_check( array( 'vis_min_orders' => 2 ) ) );
	}

	public function test_reasons_read_as_sentences(): void {
		Functions\when( 'wp_roles' )->justReturn(
			new class() {
				public function get_names() {
					return array(
						'customer'  => 'Customer',
						'wholesale' => 'Wholesale',
					);
				}
			}
		);
		Functions\when( 'translate_user_role' )->returnArg();

		$this->assertSame( 'Needs 3+ orders (has 1)', acfw_visibility_reason( 'min_orders', array( 'need' => 3, 'has' => 1 ) ) );
		$this->assertSame( 'Needs 1+ order (has 0)', acfw_visibility_reason( 'min_orders', array( 'need' => 1, 'has' => 0 ) ) );
		$this->assertSame( 'Only for customers with no orders yet (has 2)', acfw_visibility_reason( 'max_orders', array( 'need' => 0, 'has' => 2 ) ) );
		$this->assertSame( 'Only up to 2 orders (has 5)', acfw_visibility_reason( 'max_orders', array( 'need' => 2, 'has' => 5 ) ) );
		$this->assertSame( 'Needs 500 spent (has 189)', acfw_visibility_reason( 'min_spent', array( 'need' => 500, 'has' => 189 ) ) );
		$this->assertSame( 'Only for Wholesale (this customer is Customer)', acfw_visibility_reason( 'roles', array( 'need' => array( 'wholesale' ), 'has' => array( 'customer' ) ) ) );
		$this->assertSame( 'Only when the last order is over 90 days old (no orders yet)', acfw_visibility_reason( 'inactive', array( 'need' => 90, 'last' => 0 ) ) );
		$this->assertSame(
			'Only when the last order is over 90 days old (the last was 3 days ago)',
			acfw_visibility_reason( 'inactive', array( 'need' => 90, 'last' => time() - 3 * DAY_IN_SECONDS - 60 ) )
		);
	}

	// ---- Status badges -----------------------------------------------------------------

	public function test_status_badges_for_orders_to_pay_and_missing_details(): void {
		$user = (object) array(
			'ID'         => 77,
			'first_name' => 'Jo',
			'last_name'  => '',
			'user_email' => 'jo@example.test',
		);
		Functions\when( 'wp_get_current_user' )->justReturn( $user );
		Functions\when( 'wc_get_orders' )->justReturn( array() );
		Functions\when( 'wc_get_order_statuses' )->justReturn( array() );
		Functions\when( 'wc_get_page_permalink' )->justReturn( 'http://shop.test/my-account/' );
		Functions\when( 'wc_get_account_endpoint_url' )->alias(
			function ( $key ) {
				return 'http://shop.test/my-account/' . $key . '/';
			}
		);
		Functions\when( 'wc_get_endpoint_url' )->alias(
			function ( $key, $value ) {
				return 'http://shop.test/my-account/' . $key . '/' . $value . '/';
			}
		);
		Functions\when( 'get_user_meta' )->alias(
			function ( $uid, $key ) {
				if ( 'acfw_order_stats' === $key ) {
					return array(
						'time'        => time(),
						'by_status'   => array(
							'pending'   => 1,
							'failed'    => 1,
							'completed' => 3,
						),
						'total'       => 5,
						'latest'      => 9,
						'latest_time' => time(),
					);
				}
				return 'billing_phone' === $key ? '' : 'set';
			}
		);

		$badges = acfw_status_badges();

		$this->assertSame( '2 to pay', $badges['orders']['text'] );
		$this->assertSame( 'text', $badges['orders']['type'] );
		$this->assertSame( 'dot', $badges['edit-account']['type'] );
		$this->assertSame( 'Still missing: Last name', $badges['edit-account']['label'] );
		$this->assertSame( 'Still missing: Phone number', $badges['edit-address']['label'] );
		$this->assertArrayNotHasKey( 'returns', $badges, 'Returns off: no badge' );
	}

	public function test_no_status_badges_for_visitors(): void {
		Functions\when( 'wp_get_current_user' )->justReturn( (object) array( 'ID' => 0 ) );
		$this->assertSame( array(), acfw_status_badges() );
	}

	// ---- The signed preview link --------------------------------------------------------

	private function sign( int $real, int $target, int $exp ): string {
		return hash_hmac( 'sha256', 'acfw-view-as|' . $real . '|' . $target . '|' . $exp, 'salt' );
	}

	private function allow( array $staff = array( 1 ) ): void {
		Functions\when( 'wp_salt' )->justReturn( 'salt' );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		Functions\when( 'wp_doing_cron' )->justReturn( false );
		Functions\when( 'get_userdata' )->alias(
			function ( $id ) {
				return $id ? (object) array( 'ID' => (int) $id ) : false;
			}
		);
		Functions\when( 'user_can' )->alias(
			function ( $user, $cap ) use ( $staff ) {
				$id = is_object( $user ) ? (int) $user->ID : (int) $user;
				return in_array( $id, $staff, true );
			}
		);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI']    = '/my-account/';
	}

	private function link( int $target, int $exp, string $key ): void {
		$_GET = array(
			'acfw_view_as'  => (string) $target,
			'acfw_view_exp' => (string) $exp,
			'acfw_view_key' => $key,
		);
	}

	public function test_a_valid_link_shows_the_page_as_the_customer(): void {
		$this->allow();
		$exp = time() + 600;
		$this->link( 42, $exp, $this->sign( 1, 42, $exp ) );

		$this->assertSame( 42, ACFW_View_As::determine( 1 ) );
		$this->assertTrue( ACFW_View_As::active() );
		$this->assertSame( 1, ACFW_View_As::real_user_id() );
	}

	public function test_a_link_only_works_for_the_manager_it_was_made_for(): void {
		$this->allow( array( 1, 2 ) );
		$exp = time() + 600;
		$this->link( 42, $exp, $this->sign( 1, 42, $exp ) );

		$this->assertSame( 2, ACFW_View_As::determine( 2 ) );
		$this->assertFalse( ACFW_View_As::active() );
	}

	public function test_a_changed_customer_or_an_old_link_does_nothing(): void {
		$this->allow();
		$exp = time() + 600;
		$this->link( 43, $exp, $this->sign( 1, 42, $exp ) );
		$this->assertSame( 1, ACFW_View_As::determine( 1 ), 'Another customer, same signature' );

		$old = time() - 5;
		$this->link( 42, $old, $this->sign( 1, 42, $old ) );
		$this->assertSame( 1, ACFW_View_As::determine( 1 ), 'Expired' );
		$this->assertFalse( ACFW_View_As::active() );
	}

	public function test_staff_cannot_be_viewed_as(): void {
		$this->allow( array( 1, 5 ) );
		$exp = time() + 600;
		$this->link( 5, $exp, $this->sign( 1, 5, $exp ) );

		$this->assertSame( 1, ACFW_View_As::determine( 1 ) );
		$this->assertFalse( ACFW_View_As::active() );
	}

	public function test_a_customer_cannot_use_a_link(): void {
		$this->allow( array( 1 ) );
		$exp = time() + 600;
		$this->link( 42, $exp, $this->sign( 7, 42, $exp ) );

		$this->assertSame( 7, ACFW_View_As::determine( 7 ), 'User 7 is not a shop manager' );
	}

	public function test_a_form_sent_with_a_link_is_refused_not_run_as_the_customer(): void {
		$this->allow();
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$exp                       = time() + 600;
		$this->link( 42, $exp, $this->sign( 1, 42, $exp ) );

		$this->assertSame( 1, ACFW_View_As::determine( 1 ) );
		$this->assertFalse( ACFW_View_As::active() );
		$ref = new ReflectionClass( ACFW_View_As::class );
		$p   = $ref->getProperty( 'refused' );
		$p->setAccessible( true );
		$this->assertTrue( $p->getValue(), 'guard() will stop the request' );
	}

	public function test_admin_and_ajax_requests_are_never_switched(): void {
		$this->allow();
		Functions\when( 'wp_doing_ajax' )->justReturn( true );
		$exp = time() + 600;
		$this->link( 42, $exp, $this->sign( 1, 42, $exp ) );

		$this->assertSame( 1, ACFW_View_As::determine( 1 ) );
	}
}
