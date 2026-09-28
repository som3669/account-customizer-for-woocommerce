<?php
/**
 * WordPress Customizer: a pointer to the Design Studio.
 *
 * The account design is edited in My Account → Design, beside a live preview
 * of the account page. The Customizer keeps a small "My Account" section so
 * people who look for the design there find their way.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Customizer' ) ) {

	/**
	 * Registers the Customizer section that links to the Design Studio.
	 */
	class ACFW_Customizer {

		/**
		 * Section ID.
		 *
		 * @var string
		 */
		const SECTION = 'acfw_design_link';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'customize_register', array( $this, 'register' ) );
		}

		/**
		 * URL of the Design Studio.
		 *
		 * @return string
		 */
		public static function url() {
			return admin_url( 'admin.php?page=acfw-settings&tab=design' );
		}

		/**
		 * Register the section and its link.
		 *
		 * @param WP_Customize_Manager $wp_customize Customizer manager.
		 */
		public function register( $wp_customize ) {
			$wp_customize->add_section(
				self::SECTION,
				array(
					'title'       => __( 'My Account', 'my-account-dashboard-builder' ),
					'priority'    => 160,
					'capability'  => 'manage_woocommerce',
					'description' => sprintf(
						'<p>%1$s</p><p><a class="button button-primary" href="%2$s">%3$s</a></p>',
						esc_html__( 'The My Account page is designed in the Design Studio, with a live preview of the page, desktop, tablet and phone widths, and ready-made looks.', 'my-account-dashboard-builder' ),
						esc_url( self::url() ),
						esc_html__( 'Open the Design Studio', 'my-account-dashboard-builder' )
					),
				)
			);

			// A section only shows when it holds a control; this one carries the link text.
			$wp_customize->add_setting(
				'acfw_design_link',
				array(
					'type'              => 'option',
					'capability'        => 'manage_woocommerce',
					'sanitize_callback' => '__return_empty_string',
				)
			);
			$wp_customize->add_control(
				'acfw_design_link',
				array(
					'section' => self::SECTION,
					'type'    => 'hidden',
				)
			);
		}
	}
}
