<?php
/**
 * Tests for acfw_sanitize_color(), which guards every colour that reaches the
 * inline <style> block on the front end.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

class SanitizeColorTest extends TestCase {

	/**
	 * @dataProvider provide_valid_colors
	 */
	public function test_it_keeps_valid_colors( string $input, string $expected ): void {
		$this->assertSame( $expected, acfw_sanitize_color( $input ) );
	}

	public function provide_valid_colors(): array {
		return array(
			'short hex'            => array( '#fff', '#fff' ),
			'long hex'             => array( '#2563eb', '#2563eb' ),
			'uppercase hex'        => array( '#2563EB', '#2563EB' ),
			'hex with whitespace'  => array( '  #2563eb  ', '#2563eb' ),
			'rgb'                  => array( 'rgb(37, 99, 235)', 'rgb(37, 99, 235)' ),
			'rgb without spaces'   => array( 'rgb(37,99,235)', 'rgb(37, 99, 235)' ),
			'rgba'                 => array( 'rgba(37, 99, 235, 0.5)', 'rgba(37, 99, 235, 0.5)' ),
			'rgba leading dot'     => array( 'rgba(0,0,0,.25)', 'rgba(0, 0, 0, 0.25)' ),
			'rgba fully opaque'    => array( 'rgba(0,0,0,1)', 'rgba(0, 0, 0, 1)' ),
			'rgba transparent'     => array( 'rgba(0,0,0,0)', 'rgba(0, 0, 0, 0)' ),
			'channels are clamped' => array( 'rgb(999, 300, 256)', 'rgb(255, 255, 255)' ),
		);
	}

	/**
	 * Anything that is not a colour must come back as an empty string, never
	 * echoed through into the stylesheet.
	 *
	 * @dataProvider provide_invalid_colors
	 */
	public function test_it_rejects_everything_else( string $input ): void {
		$this->assertSame( '', acfw_sanitize_color( $input ) );
	}

	public function provide_invalid_colors(): array {
		return array(
			'empty'                => array( '' ),
			'whitespace only'      => array( '   ' ),
			'named colour'         => array( 'red' ),
			'missing hash'         => array( '2563eb' ),
			'wrong hex length'     => array( '#12345' ),
			'non-hex characters'   => array( '#gggggg' ),
			'css injection'        => array( '#fff; } body { display:none' ),
			'style tag escape'     => array( '</style><script>alert(1)</script>' ),
			'url()'                => array( 'url(http://evil.test/x.png)' ),
			'expression'           => array( 'expression(alert(1))' ),
			'rgb with too few'     => array( 'rgb(1, 2)' ),
			'rgb trailing garbage' => array( 'rgb(1,2,3) ; color:red' ),
			'alpha out of range'   => array( 'rgba(0,0,0,2)' ),
		);
	}

	public function test_it_tolerates_non_string_input(): void {
		$this->assertSame( '', acfw_sanitize_color( null ) );
		$this->assertSame( '', acfw_sanitize_color( 0 ) );
		$this->assertSame( '', acfw_sanitize_color( false ) );
	}
}
