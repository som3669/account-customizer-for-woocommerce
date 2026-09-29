<?php
/**
 * Tests for the helpers behind customer fields, personal offers, group menus
 * and the newer visibility rules.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

use Brain\Monkey\Functions;

class PowerFeaturesTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'remove_accents' )->alias(
			function ( $text ) {
				return strtr( (string) $text, array( 'é' => 'e', 'è' => 'e', 'ü' => 'u', 'ñ' => 'n' ) );
			}
		);
		Functions\when( 'sanitize_textarea_field' )->alias(
			function ( $text ) {
				return trim( strip_tags( (string) $text ) );
			}
		);
		Functions\when( 'sanitize_email' )->alias(
			function ( $email ) {
				return (string) filter_var( (string) $email, FILTER_SANITIZE_EMAIL );
			}
		);
		Functions\when( 'is_email' )->alias(
			function ( $email ) {
				return false !== filter_var( (string) $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
			}
		);
		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);
	}

	// ---- "At most N orders" -------------------------------------------------------

	public function test_an_empty_max_orders_rule_is_off_but_zero_is_a_limit(): void {
		$this->assertNull( acfw_rule_max_orders( array() ) );
		$this->assertNull( acfw_rule_max_orders( array( 'vis_max_orders' => '' ) ) );
		$this->assertNull( acfw_rule_max_orders( array( 'vis_max_orders' => 'lots' ) ) );
		$this->assertSame( 0, acfw_rule_max_orders( array( 'vis_max_orders' => 0 ) ) );
		$this->assertSame( 0, acfw_rule_max_orders( array( 'vis_max_orders' => '0' ) ) );
		$this->assertSame( 5, acfw_rule_max_orders( array( 'vis_max_orders' => '5' ) ) );
	}

	public function test_a_posted_max_orders_value_keeps_empty_apart_from_zero(): void {
		$this->assertSame( '', acfw_sanitize_max_orders( '' ) );
		$this->assertSame( '', acfw_sanitize_max_orders( '  ' ) );
		$this->assertSame( '', acfw_sanitize_max_orders( array( 3 ) ) );
		$this->assertSame( 0, acfw_sanitize_max_orders( '0' ) );
		$this->assertSame( 0, acfw_sanitize_max_orders( '-4' ) );
		$this->assertSame( 12, acfw_sanitize_max_orders( ' 12 ' ) );
	}

	// ---- Visibility: at most N orders, inactive for N days --------------------------

	private function sign_in( int $id ): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'wp_get_current_user' )->justReturn(
			(object) array(
				'ID'         => $id,
				'roles'      => array( 'customer' ),
				'user_email' => 'jo@example.test',
			)
		);
	}

	public function test_no_orders_yet_hides_from_customers_who_have_ordered(): void {
		$this->sign_in( 42 );
		Functions\when( 'wc_get_customer_order_count' )->justReturn( 1 );

		$this->assertFalse( acfw_visibility_passes( array( 'vis_max_orders' => 0 ) ) );
		$this->assertTrue( acfw_visibility_passes( array( 'vis_max_orders' => 1 ) ) );
		$this->assertTrue( acfw_visibility_passes( array( 'vis_max_orders' => '' ) ), 'An empty limit is no rule' );
	}

	public function test_a_visitor_passes_the_max_orders_rule(): void {
		$this->sign_in( 0 );
		Functions\when( 'wc_get_customer_order_count' )->justReturn( 0 );

		$this->assertTrue( acfw_visibility_passes( array( 'vis_max_orders' => 0 ) ), 'A first-order offer may show to guests' );
	}

	public function test_inactive_days_needs_an_order_older_than_the_limit(): void {
		$this->sign_in( 43 );
		Functions\when( 'wc_get_orders' )->justReturn( array() );
		Functions\when( 'wc_get_order_statuses' )->justReturn( array() );
		$latest = time() - 40 * DAY_IN_SECONDS;
		Functions\when( 'get_user_meta' )->justReturn(
			array(
				'time'        => time(),
				'by_status'   => array( 'wc-completed' => 1 ),
				'total'       => 1,
				'latest'      => 7,
				'latest_time' => $latest,
			)
		);

		$this->assertTrue( acfw_visibility_passes( array( 'vis_inactive_days' => 30 ) ) );
		$this->assertFalse( acfw_visibility_passes( array( 'vis_inactive_days' => 60 ) ) );
	}

	public function test_inactive_days_fails_for_customers_without_orders(): void {
		$this->sign_in( 44 );
		Functions\when( 'wc_get_orders' )->justReturn( array() );
		Functions\when( 'wc_get_order_statuses' )->justReturn( array() );
		Functions\when( 'get_user_meta' )->justReturn(
			array(
				'time'        => time(),
				'by_status'   => array(),
				'latest_time' => 0,
			)
		);

		$this->assertFalse( acfw_visibility_passes( array( 'vis_inactive_days' => 30 ) ) );
	}

	// ---- Personal offers ---------------------------------------------------------------

	public function test_an_offer_prefix_is_capitals_and_digits(): void {
		$this->assertSame( 'WELCOME10', acfw_offer_prefix( 'welcome-10' ) );
		$this->assertSame( 'ABCDEFGHIJKL', acfw_offer_prefix( 'abcdefghijklmnop' ) );
		$this->assertSame( 'OFFER', acfw_offer_prefix( '' ) );
		$this->assertSame( 'OFFER', acfw_offer_prefix( '--' ) );
		$this->assertSame( 'OFFER', acfw_offer_prefix( array( 'x' ) ) );
	}

	// ---- Badges ------------------------------------------------------------------------

	public function test_a_badge_whose_tags_are_all_empty_is_hidden(): void {
		$user = (object) array(
			'ID'         => 90,
			'first_name' => 'Jo',
			'last_name'  => '',
		);

		$this->assertSame( 'New', acfw_badge_text( 'New', $user ) );
		$this->assertSame( 'Hi Jo', acfw_badge_text( 'Hi {first_name}', $user ) );
		$this->assertSame( '', acfw_badge_text( '{last_name} pts', $user ), 'An empty value hides the badge' );
		$this->assertSame( '', acfw_badge_text( '{loyalty_tier} tier', $user ), 'An unknown tag hides the badge' );
		$this->assertSame( 'Jo', acfw_badge_text( '{first_name}{last_name}', $user ), 'One filled tag is enough' );
	}

	// ---- Customer fields ---------------------------------------------------------------

	public function test_field_definitions_get_clean_unique_keys(): void {
		$fields = acfw_fields_sanitize_definitions(
			array(
				array( 'label' => 'Café name' ),
				array(
					'label' => 'Other',
					'key'   => 'cafe_name',
				),
				array( 'label' => '' ),
				'not a field',
				array(
					'label' => '???',
					'key'   => '???',
				),
			)
		);

		$this->assertSame( array( 'cafe_name', 'cafe_name_2', 'field' ), array_column( $fields, 'key' ) );
	}

	public function test_field_types_are_allow_listed_and_choices_need_options(): void {
		$fields = acfw_fields_sanitize_definitions(
			array(
				array(
					'label' => 'A',
					'type'  => 'script',
				),
				array(
					'label'   => 'B',
					'type'    => 'select',
					'options' => "Red\r\nBlue\n\nRed\rGreen",
				),
				array(
					'label'   => 'C',
					'type'    => 'radio',
					'options' => '',
				),
			)
		);

		$this->assertSame( 'text', $fields[0]['type'] );
		$this->assertSame( 'select', $fields[1]['type'] );
		$this->assertSame( array( 'Red', 'Blue', 'Green' ), $fields[1]['options'], 'Any line ending, no blanks, no repeats' );
		$this->assertSame( 'text', $fields[2]['type'], 'A choice with no options becomes a text field' );
		$this->assertSame( array(), $fields[0]['options'] );
	}

	public function test_field_places_default_to_off(): void {
		$fields = acfw_fields_sanitize_definitions(
			array(
				array(
					'label'  => 'A',
					'places' => array( 'register' => '1' ),
				),
			)
		);

		$this->assertSame(
			array(
				'register' => true,
				'account'  => false,
				'order'    => false,
			),
			$fields[0]['places']
		);
	}

	public function test_field_values_are_cleaned_for_their_type(): void {
		$select = array(
			'type'    => 'select',
			'options' => array( 'Red', 'Blue' ),
		);

		$this->assertSame( 'Blue', acfw_field_sanitize_value( $select, 'Blue' ) );
		$this->assertSame( '', acfw_field_sanitize_value( $select, 'Purple' ) );
		$this->assertSame( '', acfw_field_sanitize_value( array( 'type' => 'number' ), '12a' ) );
		$this->assertSame( '12.5', acfw_field_sanitize_value( array( 'type' => 'number' ), ' 12.5 ' ) );
		$this->assertSame( '', acfw_field_sanitize_value( array( 'type' => 'date' ), '2026-02-30' ) );
		$this->assertSame( '2028-02-29', acfw_field_sanitize_value( array( 'type' => 'date' ), '2028-02-29' ) );
		$this->assertSame( '+44 (0)20 7946-0000', acfw_field_sanitize_value( array( 'type' => 'tel' ), '+44 (0)20 7946-0000<b>' ) );
		$this->assertSame( 'yes', acfw_field_sanitize_value( array( 'type' => 'checkbox' ), '1' ) );
		$this->assertSame( '', acfw_field_sanitize_value( array( 'type' => 'checkbox' ), '0' ) );
		$this->assertSame( '', acfw_field_sanitize_value( array( 'type' => 'text' ), array( 'x' ) ) );
	}

	public function test_is_date_checks_the_calendar(): void {
		$this->assertTrue( acfw_is_date( '2026-09-29' ) );
		$this->assertFalse( acfw_is_date( '2026-13-01' ) );
		$this->assertFalse( acfw_is_date( '29/09/2026' ) );
		$this->assertFalse( acfw_is_date( '' ) );
	}

	public function test_field_validation_messages(): void {
		$required = array(
			'label'    => 'VAT number',
			'type'     => 'text',
			'required' => true,
		);

		$this->assertSame( 'VAT number is a required field.', acfw_field_validate( $required, '  ' ) );
		$this->assertSame( '', acfw_field_validate( $required, 'GB123' ) );
		$this->assertSame( '', acfw_field_validate( array( 'label' => 'Note' ), '' ), 'An empty optional field is fine' );
		$this->assertSame(
			'Work email: enter a valid value.',
			acfw_field_validate(
				array(
					'label' => 'Work email',
					'type'  => 'email',
				),
				'not-an-email'
			)
		);
		$this->assertSame(
			'Terms is a required field.',
			acfw_field_validate(
				array(
					'label'    => 'Terms',
					'type'     => 'checkbox',
					'required' => true,
				),
				'0'
			),
			'An unticked required box is empty'
		);
	}

	// ---- Group menus: order trees ------------------------------------------------------

	private function tree(): array {
		return array(
			'dashboard'       => array( 'type' => 'endpoint' ),
			'shop'            => array(
				'type'     => 'group',
				'children' => array(
					'orders'    => array( 'type' => 'endpoint' ),
					'downloads' => array( 'type' => 'endpoint' ),
				),
			),
			'customer-logout' => array( 'type' => 'endpoint' ),
		);
	}

	public function test_taking_a_key_out_of_a_tree_finds_it_in_groups(): void {
		$tree = acfw_order_without( $this->tree(), 'orders' );

		$this->assertSame( array( 'downloads' ), array_keys( $tree['shop']['children'] ) );
		$this->assertSame( array( 'dashboard', 'shop', 'customer-logout' ), array_keys( $tree ) );
	}

	public function test_flattening_a_tree_lists_group_children(): void {
		$this->assertSame(
			array( 'dashboard', 'shop', 'orders', 'downloads', 'customer-logout' ),
			array_keys( acfw_flatten_order_tree( $this->tree() ) )
		);
	}

	public function test_items_can_be_dropped_from_a_built_menu(): void {
		$items = array(
			'dashboard' => array( 'label' => 'Dashboard' ),
			'shop'      => array(
				'label'    => 'Shop',
				'children' => array(
					'orders'    => array( 'label' => 'Orders' ),
					'downloads' => array( 'label' => 'Downloads' ),
				),
			),
		);

		$out = acfw_items_without( $items, array( 'downloads', 'dashboard' ) );

		$this->assertSame( array( 'shop' ), array_keys( $out ) );
		$this->assertSame( array( 'orders' ), array_keys( $out['shop']['children'] ) );
		$this->assertSame( $items, acfw_items_without( $items, array() ) );
	}

	public function test_log_out_moves_back_to_the_end(): void {
		$tree = acfw_order_insert( $this->tree(), 'wholesale', 'endpoint' );

		$this->assertSame( 'wholesale', array_key_last( $tree ), 'Inserted at the end, after Log out' );
		$this->assertSame(
			array( 'dashboard', 'shop', 'wholesale', 'customer-logout' ),
			array_keys( acfw_order_logout_last( $tree ) )
		);
		$this->assertSame( array( 'a' => 1 ), acfw_order_logout_last( array( 'a' => 1 ) ) );
	}
}
