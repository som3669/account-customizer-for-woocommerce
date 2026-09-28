<?php
/**
 * Tests for acfw_order_insert() and acfw_order_from_items(), which place an
 * item added with the canvas' "+" where "+" was clicked.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

class OrderInsertTest extends TestCase {

	/**
	 * Dashboard, a "More" group holding FAQ and Help, then Logout.
	 *
	 * @return array
	 */
	private function tree(): array {
		return array(
			'dashboard'       => array( 'type' => 'endpoint' ),
			'more'            => array(
				'type'     => 'group',
				'children' => array(
					'faq'  => array( 'type' => 'link' ),
					'help' => array( 'type' => 'page' ),
				),
			),
			'customer-logout' => array( 'type' => 'endpoint' ),
		);
	}

	public function test_lands_below_a_top_level_item(): void {
		$tree = acfw_order_insert( $this->tree(), 'rewards', 'endpoint', 'dashboard' );

		$this->assertSame( array( 'dashboard', 'rewards', 'more', 'customer-logout' ), array_keys( $tree ) );
		$this->assertSame( array( 'type' => 'endpoint' ), $tree['rewards'] );
	}

	public function test_lands_below_an_item_inside_a_group(): void {
		$tree = acfw_order_insert( $this->tree(), 'contact', 'link', 'faq', 'more' );

		$this->assertSame( array( 'faq', 'contact', 'help' ), array_keys( $tree['more']['children'] ) );
		$this->assertArrayNotHasKey( 'contact', $tree );
	}

	public function test_a_group_below_a_grouped_item_lands_below_the_group(): void {
		$tree = acfw_order_insert( $this->tree(), 'extras', 'group', 'faq', 'more' );

		$this->assertSame( array( 'dashboard', 'more', 'extras', 'customer-logout' ), array_keys( $tree ) );
		$this->assertSame( array( 'faq', 'help' ), array_keys( $tree['more']['children'] ) );
	}

	public function test_lands_at_the_end_of_a_group(): void {
		$tree = acfw_order_insert( $this->tree(), 'contact', 'link', '', 'more' );

		$this->assertSame( array( 'faq', 'help', 'contact' ), array_keys( $tree['more']['children'] ) );
	}

	public function test_an_empty_group_gets_its_first_item(): void {
		$tree = acfw_order_insert( array( 'more' => array( 'type' => 'group' ) ), 'faq', 'link', '', 'more' );

		$this->assertSame( array( 'faq' => array( 'type' => 'link' ) ), $tree['more']['children'] );
	}

	public function test_a_group_is_never_put_inside_a_group(): void {
		$tree = acfw_order_insert( $this->tree(), 'extras', 'group', '', 'more' );

		$this->assertSame( array( 'dashboard', 'more', 'extras', 'customer-logout' ), array_keys( $tree ) );
		$this->assertArrayNotHasKey( 'extras', $tree['more']['children'] );
	}

	public function test_unknown_spots_fall_back_to_the_end(): void {
		$tree = acfw_order_insert( $this->tree(), 'rewards', 'endpoint', 'gone', 'dashboard' );

		$this->assertSame( 'rewards', array_key_last( $tree ) );
		$this->assertArrayNotHasKey( 'children', $tree['dashboard'] );
	}

	public function test_order_from_items_keeps_groups_and_their_items(): void {
		$items = array(
			'dashboard' => array(
				'type'  => 'endpoint',
				'label' => 'Dashboard',
			),
			'more'      => array(
				'type'     => 'group',
				'label'    => 'More',
				'children' => array(
					'faq' => array(
						'type' => 'link',
						'url'  => 'https://example.com/faq',
					),
				),
			),
			'untyped'   => array( 'label' => 'Old item' ),
		);

		$this->assertSame(
			array(
				'dashboard' => array( 'type' => 'endpoint' ),
				'more'      => array(
					'type'     => 'group',
					'children' => array( 'faq' => array( 'type' => 'link' ) ),
				),
				'untyped'   => array( 'type' => 'endpoint' ),
			),
			acfw_order_from_items( $items )
		);
	}
}
