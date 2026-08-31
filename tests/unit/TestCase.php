<?php
/**
 * Shared test case: boots Brain Monkey and stubs the core functions the
 * plugin's pure helpers call.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Faithful enough stand-ins for the core functions under test.
		Functions\when( 'sanitize_hex_color' )->alias(
			function ( $color ) {
				if ( '' === $color || null === $color ) {
					return '';
				}
				return preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ? $color : null;
			}
		);

		Functions\when( 'wp_strip_all_tags' )->alias(
			function ( $string, $remove_breaks = false ) {
				$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $string );
				$string = strip_tags( $string );
				return $remove_breaks ? trim( preg_replace( '/[\r\n\t ]+/', ' ', $string ) ) : trim( $string );
			}
		);

		Functions\when( 'sanitize_text_field' )->alias(
			function ( $string ) {
				return trim( strip_tags( (string) $string ) );
			}
		);

		// Stands in for kses: drops script/style blocks and event attributes.
		Functions\when( 'wp_kses_post' )->alias(
			function ( $string ) {
				$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $string );
				return preg_replace( '/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $string );
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
