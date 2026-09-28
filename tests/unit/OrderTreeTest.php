<?php
/**
 * Tests for acfw_sanitize_order_tree(), which cleans the menu order the
 * builder posts ( and an import brings in ) before it is stored.
 *
 * The order used to go through sanitize_textarea_field() as raw JSON, which
 * strips "%xx" octets and so dropped any item whose key held them.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

class OrderTreeTest extends TestCase {

	public function test_keys_with_octets_survive(): void {
		$key  = '%e0%a4%ae%e0%a5%87%e0%a4%b0%e0%a5%8b';
		$tree = acfw_sanitize_order_tree(
			array(
				'dashboard' => array( 'type' => 'endpoint' ),
				$key        => array( 'type' => 'endpoint' ),
			)
		);

		$this->assertSame( array( 'dashboard', $key ), array_keys( $tree ) );
	}

	public function test_unknown_types_fall_back_to_endpoint(): void {
		$tree = acfw_sanitize_order_tree(
			array(
				'a' => array( 'type' => 'shortcode' ),
				'b' => array(),
				'c' => array( 'type' => 'LINK' ),
			)
		);

		$this->assertSame( 'endpoint', $tree['a']['type'] );
		$this->assertSame( 'endpoint', $tree['b']['type'] );
		$this->assertSame( 'link', $tree['c']['type'] );
	}

	public function test_only_a_top_level_group_keeps_children(): void {
		$tree = acfw_sanitize_order_tree(
			array(
				'perks'  => array(
					'type'     => 'group',
					'children' => array(
						'rewards' => array( 'type' => 'endpoint' ),
						'inner'   => array(
							'type'     => 'group',
							'children' => array( 'deep' => array( 'type' => 'endpoint' ) ),
						),
					),
				),
				'orders' => array(
					'type'     => 'endpoint',
					'children' => array( 'x' => array( 'type' => 'endpoint' ) ),
				),
			)
		);

		$this->assertSame( array( 'rewards', 'inner' ), array_keys( $tree['perks']['children'] ) );
		$this->assertArrayNotHasKey( 'children', $tree['perks']['children']['inner'], 'Groups nest one level only' );
		$this->assertArrayNotHasKey( 'children', $tree['orders'], 'Only groups hold children' );
	}

	public function test_junk_is_dropped(): void {
		$this->assertSame( array(), acfw_sanitize_order_tree( 'not an array' ) );
		$this->assertSame( array(), acfw_sanitize_order_tree( null ) );

		$tree = acfw_sanitize_order_tree(
			array(
				''         => array( 'type' => 'endpoint' ),
				'<script>' => array( 'type' => 'endpoint' ),
				'ok'       => 'not a node',
				'fine'     => array( 'type' => 'page' ),
			)
		);

		// Tags strip to an empty key, which is dropped like any other.
		$this->assertSame( array( 'fine' ), array_keys( $tree ) );
	}

	public function test_flatten_lists_children_after_their_group(): void {
		$flat = acfw_flatten_items(
			array(
				'dashboard' => array( 'type' => 'endpoint' ),
				'perks'     => array(
					'type'     => 'group',
					'children' => array( 'rewards' => array( 'type' => 'endpoint' ) ),
				),
				'logout'    => array( 'type' => 'endpoint' ),
			)
		);

		$this->assertSame( array( 'dashboard', 'perks', 'rewards', 'logout' ), array_keys( $flat ) );
		$this->assertArrayNotHasKey( 'children', $flat['perks'] );
	}
}
