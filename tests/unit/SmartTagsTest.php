<?php
/**
 * Tests for acfw_apply_smart_tags().
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

use Brain\Monkey\Functions;

class SmartTagsTest extends TestCase {

	/**
	 * A customer with a distinct ID per test ( costly values are memoised per user ).
	 *
	 * @param int   $id     User ID.
	 * @param array $fields Extra fields.
	 * @return object
	 */
	private function customer( int $id, array $fields = array() ) {
		return (object) array_merge(
			array(
				'ID'              => $id,
				'display_name'    => 'Jo Bloggs',
				'first_name'      => 'Jo',
				'last_name'       => 'Bloggs',
				'user_login'      => 'jo',
				'user_email'      => 'jo@example.test',
				'user_registered' => '2024-03-05 10:00:00',
			),
			$fields
		);
	}

	public function test_text_without_tokens_is_returned_untouched(): void {
		$this->assertSame( 'Welcome back', acfw_apply_smart_tags( 'Welcome back', $this->customer( 100 ) ) );
	}

	public function test_only_the_tokens_in_the_text_are_resolved(): void {
		// Neither count may be queried for text that shows neither.
		Functions\expect( 'wc_get_customer_order_count' )->never();
		Functions\expect( 'wc_get_customer_available_downloads' )->never();

		$this->assertSame( 'Hi Jo', acfw_apply_smart_tags( 'Hi {first_name}', $this->customer( 101 ) ) );
	}

	public function test_values_are_escaped_for_html_unless_asked_not_to(): void {
		$user = $this->customer( 102, array( 'first_name' => '<b>O\'Neil</b>' ) );

		$this->assertSame( 'Hi &lt;b&gt;O&#039;Neil&lt;/b&gt;', acfw_apply_smart_tags( 'Hi {first_name}', $user ) );
		$this->assertSame( 'Hi <b>O\'Neil</b>', acfw_apply_smart_tags( 'Hi {first_name}', $user, false ) );
	}

	public function test_unknown_tokens_stay_in_place(): void {
		$this->assertSame( 'Tier: {loyalty_tier}', acfw_apply_smart_tags( 'Tier: {loyalty_tier}', $this->customer( 103 ) ) );
	}

	public function test_the_filter_can_add_tokens(): void {
		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				if ( 'acfw_smart_tag_values' === $hook ) {
					$value['{loyalty_tier}'] = 'Gold';
				}
				return $value;
			}
		);

		$this->assertSame( 'Tier: Gold', acfw_apply_smart_tags( 'Tier: {loyalty_tier}', $this->customer( 104 ) ) );
	}

	public function test_total_spent_is_plain_text(): void {
		Functions\when( 'wc_get_customer_total_spent' )->justReturn( '12.5' );
		Functions\when( 'wc_price' )->justReturn( '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&pound;</span>12.50</bdi></span>' );

		$this->assertSame( 'Spent £12.50', acfw_apply_smart_tags( 'Spent {total_spent}', $this->customer( 105 ), false ) );
	}

	public function test_order_count_is_counted_once_per_request(): void {
		Functions\expect( 'wc_get_customer_order_count' )->once()->andReturn( 7 );
		$user = $this->customer( 106 );

		$this->assertSame( '7 orders', acfw_apply_smart_tags( '{order_count} orders', $user ) );
		$this->assertSame( 'You have 7', acfw_apply_smart_tags( 'You have {order_count}', $user ) );
	}

	public function test_the_older_customer_name_token_still_works(): void {
		$this->assertSame( 'Dear Jo Bloggs', acfw_apply_smart_tags( 'Dear %%customer_name%%', $this->customer( 107 ) ) );
	}
}
