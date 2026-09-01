<?php
/**
 * Tests for ACFW_Import_Export::sanitize_imported().
 *
 * An import must not be a way around the sanitisers the admin save path runs,
 * so every rich field that ends up echoed on the front end is re-filtered for
 * importers who cannot post unfiltered HTML.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

use ACFW_Import_Export;
use ReflectionMethod;

class ImportSanitizeTest extends TestCase {

	/**
	 * Call the protected static method under test.
	 *
	 * @param string $name  Option name.
	 * @param mixed  $value Imported value.
	 * @return mixed
	 */
	private function sanitize( string $name, $value ) {
		$method = new ReflectionMethod( ACFW_Import_Export::class, 'sanitize_imported' );
		$method->setAccessible( true );
		return $method->invoke( null, $name, $value );
	}

	public function test_it_filters_per_item_content(): void {
		$item = array(
			'type'    => 'endpoint',
			'label'   => 'Support',
			'content' => '<p>Hello</p><script>alert(1)</script>',
		);

		$clean = $this->sanitize( 'acfw_item_support', $item );

		$this->assertSame( '<p>Hello</p>', $clean['content'] );
		$this->assertSame( 'Support', $clean['label'], 'Untouched keys must survive' );
		$this->assertSame( 'endpoint', $clean['type'] );
	}

	public function test_it_filters_banner_content_and_title(): void {
		$banners = array(
			'sale' => array(
				'title'   => '<script>alert(1)</script>Summer sale',
				'content' => '<strong>20% off</strong><img src=x onerror=alert(1)>',
				'type'    => 'widget',
			),
		);

		$clean = $this->sanitize( 'acfw_banners', $banners );

		$this->assertStringNotContainsString( '<script', $clean['sale']['title'] );
		$this->assertStringNotContainsString( 'onerror', $clean['sale']['content'] );
		$this->assertStringContainsString( '20% off', $clean['sale']['content'] );
		$this->assertSame( 'widget', $clean['sale']['type'] );
	}

	public function test_it_filters_the_guest_message(): void {
		$clean = $this->sanitize( 'acfw_guest_message', 'Please log in<script>alert(1)</script>' );

		$this->assertSame( 'Please log in', $clean );
	}

	public function test_it_filters_the_dashboard_notice(): void {
		$clean = $this->sanitize( 'acfw_dashboard_notice', 'Free shipping<script>alert(1)</script>' );

		$this->assertSame( 'Free shipping', $clean );
	}

	public function test_it_leaves_plain_options_alone(): void {
		$this->assertSame( 'yes', $this->sanitize( 'acfw_buyagain_enable', 'yes' ) );
		$this->assertSame( '#2563eb', $this->sanitize( 'acfw_accent_color', '#2563eb' ) );
		$this->assertSame( array( 1, 2 ), $this->sanitize( 'acfw_items_order', array( 1, 2 ) ) );
	}

	public function test_it_survives_malformed_records(): void {
		$this->assertSame( 'not-an-array', $this->sanitize( 'acfw_item_x', 'not-an-array' ) );
		$this->assertSame(
			array( 'sale' => 'not-an-array' ),
			$this->sanitize( 'acfw_banners', array( 'sale' => 'not-an-array' ) )
		);
		$this->assertSame( array(), $this->sanitize( 'acfw_banners', array() ) );
	}
}
