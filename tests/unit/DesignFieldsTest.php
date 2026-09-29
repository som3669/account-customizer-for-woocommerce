<?php
/**
 * Tests for ACFW_Design: the registry every Design Studio value is
 * sanitised through before it reaches the preview draft or the options.
 *
 * @package AccountCustomizerForWooCommerce
 */

namespace ACFW\Tests\Unit;

use ACFW_Design;
use Brain\Monkey\Functions;

class DesignFieldsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'esc_url_raw' )->returnArg();
	}

	public function test_every_field_belongs_to_a_group_and_has_a_valid_type(): void {
		$groups = array_keys( ACFW_Design::groups() );
		$types  = array( 'color', 'toggle', 'choice', 'range', 'text', 'css', 'image', 'layout' );

		foreach ( ACFW_Design::fields() as $key => $field ) {
			$this->assertStringStartsWith( 'acfw_', $key );
			$this->assertContains( $field['group'], $groups, "$key has an unknown group" );
			$this->assertContains( $field['type'], $types, "$key has an unknown type" );
			$this->assertArrayHasKey( 'default', $field, "$key has no default" );
		}
	}

	public function test_every_default_survives_its_own_sanitiser(): void {
		foreach ( ACFW_Design::fields() as $key => $field ) {
			$this->assertSame(
				is_int( $field['default'] ) ? $field['default'] : (string) $field['default'],
				ACFW_Design::sanitize( $field, $field['default'] ),
				"The default of $key does not round-trip"
			);
		}
	}

	public function test_parents_are_toggles(): void {
		$fields = ACFW_Design::fields();
		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['parent'] ) ) {
				$this->assertSame( 'toggle', $fields[ $field['parent'] ]['type'], "$key depends on a non-toggle" );
			}
		}
	}

	public function test_ranges_are_clamped_and_stepped(): void {
		$fields = ACFW_Design::fields();

		$this->assertSame( 24, ACFW_Design::sanitize( $fields['acfw_menu_radius'], 999 ) );
		$this->assertSame( 0, ACFW_Design::sanitize( $fields['acfw_menu_radius'], -5 ) );
		$this->assertSame( 2048, ACFW_Design::sanitize( $fields['acfw_avatar_upload_max'], 2100 ), 'Snaps to the 256 KB step' );
	}

	public function test_unknown_choices_fall_back_to_the_default(): void {
		$fields = ACFW_Design::fields();

		$this->assertSame( 'underline', ACFW_Design::sanitize( $fields['acfw_active_indicator'], 'underline' ) );
		$this->assertSame( 'bar', ACFW_Design::sanitize( $fields['acfw_active_indicator'], 'sparkles' ) );
	}

	public function test_colours_and_toggles(): void {
		$fields = ACFW_Design::fields();

		$this->assertSame( '#0f766e', ACFW_Design::sanitize( $fields['acfw_accent_color'], '#0f766e' ) );
		$this->assertSame( '', ACFW_Design::sanitize( $fields['acfw_accent_color'], 'red; background:url(x)' ) );
		$this->assertSame( 'yes', ACFW_Design::sanitize( $fields['acfw_menu_search'], 'yes' ) );
		$this->assertSame( 'no', ACFW_Design::sanitize( $fields['acfw_menu_search'], '1' ) );
	}

	public function test_custom_css_cannot_close_its_style_tag(): void {
		$fields = ACFW_Design::fields();

		$css = ACFW_Design::sanitize( $fields['acfw_custom_css'], '.a{color:red}</style><script>alert(1)</script>' );
		$this->assertStringNotContainsString( '</style>', $css );
		$this->assertStringNotContainsString( '<script', $css );
	}

	public function test_only_known_scalar_values_are_kept(): void {
		$clean = ACFW_Design::sanitize_values(
			array(
				'acfw_menu_radius'   => '12',
				'acfw_items_order'   => '{"x":{}}',
				'siteurl'            => 'https://evil.example',
				'acfw_menu_position' => array( 'horizontal' ),
			)
		);

		$this->assertSame( array( 'acfw_menu_radius' => 12 ), $clean );
	}
}
