<?php
/**
 * Tests for acfw_sanitize_icon(), which guards the class strings printed into
 * the menu markup's class attribute.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

class SanitizeIconTest extends TestCase {

	/**
	 * @dataProvider provide_icons
	 */
	public function test_it_sanitizes( string $input, string $expected ): void {
		$this->assertSame( $expected, acfw_sanitize_icon( $input ) );
	}

	public function provide_icons(): array {
		return array(
			'font awesome class'  => array( 'fas fa-user', 'fas fa-user' ),
			'dashicon class'      => array( 'dashicons dashicons-cart', 'dashicons dashicons-cart' ),
			'underscores kept'    => array( 'my_icon-2', 'my_icon-2' ),
			'trimmed'             => array( '  fas fa-user  ', 'fas fa-user' ),
			'empty'               => array( '', '' ),
			'quotes dropped'      => array( 'fa" onmouseover="alert(1)', 'fa onmouseoveralert1' ),
			'angle brackets gone' => array( '<span>fas</span>', 'fas' ),
			'script stripped'     => array( '<script>alert(1)</script>fas', 'fas' ),
			'dots dropped'        => array( 'fa.fa-user', 'fafa-user' ),
			'slashes dropped'     => array( 'fa/fa-user', 'fafa-user' ),
			'semicolons dropped'  => array( 'fas;color:red', 'fascolorred' ),
		);
	}

	/**
	 * The output is used inside a double-quoted HTML attribute, so it must never
	 * be able to close that attribute.
	 */
	public function test_output_can_never_break_out_of_an_attribute(): void {
		$payloads = array(
			'" onload="alert(1)',
			"' onload='alert(1)",
			'><img src=x onerror=alert(1)>',
			'fas fa-user" autofocus onfocus="alert(1)',
		);

		foreach ( $payloads as $payload ) {
			$clean = acfw_sanitize_icon( $payload );
			$this->assertDoesNotMatchRegularExpression( '/["\'<>=()]/', $clean, 'Leaked a dangerous character' );
		}
	}
}
