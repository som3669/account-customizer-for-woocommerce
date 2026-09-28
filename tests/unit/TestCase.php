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

		// Like sanitize_title_with_dashes(): lowercase, dashes for spaces, and
		// every non-ASCII byte percent-encoded ( "म" becomes "%e0%a4%ae" ).
		Functions\when( 'sanitize_title' )->alias(
			function ( $title ) {
				$title = strtolower( trim( strip_tags( (string) $title ) ) );
				$title = preg_replace_callback(
					'/[\x80-\xff]/',
					function ( $m ) {
						return '%' . bin2hex( $m[0] );
					},
					$title
				);
				$title = preg_replace( '/%(?![a-f0-9]{2})/', '', $title );
				$title = preg_replace( '/[^%a-z0-9 _-]/', '', $title );
				$title = preg_replace( '/\s+/', '-', $title );
				return trim( preg_replace( '/-+/', '-', $title ), '-' );
			}
		);

		Functions\when( 'sanitize_key' )->alias(
			function ( $key ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
			}
		);

		Functions\when( '__' )->returnArg();

		Functions\when( 'esc_html' )->alias(
			function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
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
