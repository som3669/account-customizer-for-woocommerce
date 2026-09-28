<?php
/**
 * Tests for ACFW_Banners::save().
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

use ACFW_Banners;
use Brain\Monkey\Functions;

class BannerSaveTest extends TestCase {

	/**
	 * Banners stored before the save.
	 *
	 * @var array
	 */
	private $stored = array();

	/**
	 * What save() wrote to acfw_banners.
	 *
	 * @var array|null
	 */
	private $written = null;

	protected function setUp(): void {
		parent::setUp();

		$this->stored  = array();
		$this->written = null;

		Functions\when( 'get_option' )->alias(
			function ( $name, $fallback = false ) {
				return 'acfw_banners' === $name ? $this->stored : $fallback;
			}
		);
		Functions\when( 'update_option' )->alias(
			function ( $name, $value ) {
				if ( 'acfw_banners' === $name ) {
					$this->written = $value;
				}
				return true;
			}
		);
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);
	}

	public function test_translucent_colours_are_kept(): void {
		ACFW_Banners::save(
			'',
			array(
				'title'    => 'Promo',
				'bg_color' => 'rgba(37, 99, 235, 0.5)',
			)
		);

		$this->assertSame( 'rgba(37, 99, 235, 0.5)', $this->written['promo']['bg_color'] );
	}

	public function test_an_invalid_colour_falls_back_to_the_default(): void {
		ACFW_Banners::save(
			'',
			array(
				'title'      => 'Promo',
				'text_color' => 'url(javascript:alert(1))',
			)
		);

		$this->assertSame( ACFW_Banners::defaults()['text_color'], $this->written['promo']['text_color'] );
	}

	public function test_a_new_banner_never_replaces_one_with_the_same_name(): void {
		$this->stored = array(
			'promo' => array(
				'title'   => 'Promo',
				'content' => 'The first one',
			),
		);

		$slug = ACFW_Banners::save(
			'',
			array(
				'title'   => 'Promo',
				'content' => 'The second one',
			)
		);

		$this->assertSame( 'promo-2', $slug );
		$this->assertSame( 'The first one', $this->written['promo']['content'] );
		$this->assertSame( 'The second one', $this->written['promo-2']['content'] );
	}

	public function test_saving_an_existing_banner_updates_it_in_place(): void {
		$this->stored = array( 'promo' => array( 'title' => 'Promo' ) );

		$slug = ACFW_Banners::save( 'promo', array( 'title' => 'Promo, renamed' ) );

		$this->assertSame( 'promo', $slug );
		$this->assertSame( array( 'promo' ), array_keys( $this->written ) );
	}

	public function test_a_non_latin_name_gets_an_ascii_slug(): void {
		$slug = ACFW_Banners::save( '', array( 'title' => 'बिक्री' ) );

		$this->assertMatchesRegularExpression( '/^banner-[a-f0-9]{6}$/', $slug );
	}

	public function test_the_badge_source_is_allow_listed(): void {
		ACFW_Banners::save(
			'',
			array(
				'title'        => 'Cart',
				'count_source' => 'cart',
			)
		);
		$this->assertSame( 'cart', $this->written['cart']['count_source'] );

		ACFW_Banners::save(
			'',
			array(
				'title'        => 'Other',
				'count_source' => 'passwords',
			)
		);
		$this->assertSame( 'orders', $this->written['other']['count_source'] );
	}
}
