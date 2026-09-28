<?php
/**
 * Tests for the helpers that turn a label into a menu item key.
 *
 * A key becomes an option name, a WooCommerce query var and, by default, the
 * endpoint URL. Keys used to come straight from sanitize_title(), which let a
 * non-Latin label produce a key the saved order then lost, and let "Orders"
 * overwrite the Orders endpoint.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

use Brain\Monkey\Functions;

class ItemKeyTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'get_option' )->justReturn( false );
	}

	/**
	 * @dataProvider provide_labels
	 */
	public function test_ascii_slug( string $label, string $expected ): void {
		$this->assertSame( $expected, acfw_ascii_slug( $label ) );
	}

	public function provide_labels(): array {
		return array(
			'plain'                => array( 'Support', 'support' ),
			'spaces and case'      => array( 'My Wish List', 'my-wish-list' ),
			'underscores'          => array( 'gift_cards', 'gift-cards' ),
			'devanagari only'      => array( 'मेरो खाता', '' ),
			'mixed scripts'        => array( 'Support सहयोग', 'support' ),
			'cyrillic then latin'  => array( 'Заказы VIP', 'vip' ),
			'stray dashes trimmed' => array( '--Help--', 'help' ),
		);
	}

	public function test_a_label_without_latin_letters_gets_a_stable_ascii_key(): void {
		$first  = acfw_item_key_from_label( 'मेरो खाता', 'endpoint' );
		$second = acfw_item_key_from_label( 'मेरो खाता', 'endpoint' );

		$this->assertMatchesRegularExpression( '/^endpoint-[a-f0-9]{6}$/', $first );
		$this->assertSame( $first, $second, 'The same label must always give the same key' );
		$this->assertStringNotContainsString( '%', $first );
	}

	public function test_the_fallback_names_the_item_type(): void {
		$this->assertStringStartsWith( 'group-', acfw_item_key_from_label( '会員', 'group' ) );
		$this->assertStringStartsWith( 'banner-', acfw_item_key_from_label( '促销', 'banner' ) );
	}

	public function test_a_new_item_never_takes_a_woocommerce_endpoint_key(): void {
		$this->assertSame( 'orders-2', acfw_unique_item_key( 'Orders', 'link', array() ) );
		$this->assertSame( 'dashboard-2', acfw_unique_item_key( 'Dashboard', 'endpoint', array() ) );
	}

	public function test_a_new_item_skips_keys_and_slugs_already_in_the_menu(): void {
		$taken = array( 'support', 'support-2', 'help' );

		$this->assertSame( 'support-3', acfw_unique_item_key( 'Support', 'endpoint', $taken ) );
		$this->assertSame( 'help-2', acfw_unique_item_key( 'Help', 'endpoint', $taken ) );
	}

	public function test_a_new_item_skips_keys_left_behind_in_the_options_table(): void {
		Functions\when( 'get_option' )->alias(
			function ( $name, $fallback = false ) {
				return 'acfw_item_faq' === $name ? array( 'label' => 'FAQ' ) : $fallback;
			}
		);

		$this->assertSame( 'faq-2', acfw_unique_item_key( 'FAQ', 'endpoint', array() ) );
	}

	public function test_wordpress_query_vars_are_reserved(): void {
		// "order" and "page" are WordPress query vars; an endpoint with that
		// name would collide with core queries.
		$this->assertSame( 'order-2', acfw_unique_item_key( 'Order', 'endpoint', array() ) );
		$this->assertSame( 'page-2', acfw_unique_item_key( 'Page', 'endpoint', array() ) );
	}
}
