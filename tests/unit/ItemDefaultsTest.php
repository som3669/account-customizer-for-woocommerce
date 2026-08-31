<?php
/**
 * Tests for the default option sets behind every menu item.
 *
 * The frontend reads these keys without isset() guards in places, so a missing
 * key is a notice on a customer-facing page.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

class ItemDefaultsTest extends TestCase {

	public function test_endpoint_defaults_carry_the_key_as_the_slug(): void {
		$options = acfw_default_endpoint_options( 'support' );

		$this->assertSame( 'endpoint', $options['type'] );
		$this->assertSame( 'support', $options['slug'] );
		$this->assertTrue( $options['active'] );
	}

	public function test_endpoint_defaults_work_without_a_key(): void {
		$options = acfw_default_endpoint_options();

		$this->assertSame( '', $options['slug'] );
	}

	/**
	 * @dataProvider provide_default_sets
	 */
	public function test_every_item_type_shares_the_common_keys( array $options, string $type ): void {
		$this->assertSame( $type, $options['type'] );

		foreach ( array( 'label', 'icon', 'icon_url', 'icon_source', 'active', 'visibility', 'usr_roles', 'class' ) as $key ) {
			$this->assertArrayHasKey( $key, $options, "Missing '$key' in the $type defaults" );
		}

		$this->assertSame( 'choose', $options['icon_source'] );
		$this->assertSame( 'all', $options['visibility'] );
		$this->assertSame( array(), $options['usr_roles'], 'Empty roles must mean "everyone"' );
	}

	public function provide_default_sets(): array {
		return array(
			'endpoint' => array( acfw_default_endpoint_options( 'orders' ), 'endpoint' ),
			'group'    => array( acfw_default_group_options(), 'group' ),
			'link'     => array( acfw_default_link_options(), 'link' ),
		);
	}

	public function test_groups_start_collapsed_and_childless(): void {
		$options = acfw_default_group_options();

		$this->assertFalse( $options['open'] );
		$this->assertSame( array(), $options['children'] );
	}

	public function test_links_do_not_open_in_a_new_tab_by_default(): void {
		$options = acfw_default_link_options();

		$this->assertSame( '#', $options['url'] );
		$this->assertFalse( $options['target_blank'] );
	}
}
