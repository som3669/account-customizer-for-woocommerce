<?php
/**
 * WordPress Customizer integration: live-preview design controls
 * bound to the same options used by the admin Style / Avatar tabs.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Customizer' ) ) {

	/**
	 * Registers a "My Account Design" Customizer section with live preview.
	 */
	class ACFW_Customizer {

		const SECTION = 'acfw_design';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'customize_register', array( $this, 'register' ) );
		}

		/**
		 * Deep-link URL that opens the Customizer focused on our section,
		 * previewing the My Account page.
		 *
		 * @return string
		 */
		public static function url() {
			$preview = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
			return add_query_arg(
				array(
					'autofocus[section]' => self::SECTION,
					'url'                => rawurlencode( $preview ),
				),
				admin_url( 'customize.php' )
			);
		}

		/**
		 * Register section, settings and controls.
		 *
		 * @param WP_Customize_Manager $wp_customize Customizer manager.
		 */
		public function register( $wp_customize ) {

			$wp_customize->add_section(
				self::SECTION,
				array(
					'title'    => __( 'My Account Design', 'account-customizer-for-woocommerce' ),
					'priority' => 160,
				)
			);

			$yes_no = array(
				'no'  => __( 'No', 'account-customizer-for-woocommerce' ),
				'yes' => __( 'Yes', 'account-customizer-for-woocommerce' ),
			);

			// Selects.
			$this->add_select( $wp_customize, 'acfw_menu_position', 'vertical-left', __( 'Menu position', 'account-customizer-for-woocommerce' ), array(
				'vertical-left'  => __( 'Left', 'account-customizer-for-woocommerce' ),
				'vertical-right' => __( 'Right', 'account-customizer-for-woocommerce' ),
				'horizontal'     => __( 'Top', 'account-customizer-for-woocommerce' ),
			) );
			$this->add_select( $wp_customize, 'acfw_menu_layout', 'simple', __( 'Menu layout', 'account-customizer-for-woocommerce' ), array(
				'simple'     => __( 'Simple', 'account-customizer-for-woocommerce' ),
				'classic'    => __( 'Classic', 'account-customizer-for-woocommerce' ),
				'modern'     => __( 'Modern', 'account-customizer-for-woocommerce' ),
				'no-borders' => __( 'No borders', 'account-customizer-for-woocommerce' ),
			) );

			// Colors.
			$this->add_color( $wp_customize, 'acfw_accent_color', '#2563eb', __( 'Accent color', 'account-customizer-for-woocommerce' ) );
			$this->add_color( $wp_customize, 'acfw_text_color', '#383838', __( 'Text color', 'account-customizer-for-woocommerce' ) );

			// Numbers.
			$this->add_number( $wp_customize, 'acfw_menu_radius', 8, __( 'Corner radius (px)', 'account-customizer-for-woocommerce' ), 0, 24 );
			$this->add_number( $wp_customize, 'acfw_menu_gap', 4, __( 'Item spacing (px)', 'account-customizer-for-woocommerce' ), 0, 24 );
			$this->add_number( $wp_customize, 'acfw_item_padding', 11, __( 'Item padding (px)', 'account-customizer-for-woocommerce' ), 4, 28 );
			$this->add_number( $wp_customize, 'acfw_font_size', 15, __( 'Font size (px)', 'account-customizer-for-woocommerce' ), 11, 22 );
			$this->add_select( $wp_customize, 'acfw_font_weight', '500', __( 'Font weight', 'account-customizer-for-woocommerce' ), array(
				'400' => __( 'Normal', 'account-customizer-for-woocommerce' ),
				'500' => __( 'Medium', 'account-customizer-for-woocommerce' ),
				'600' => __( 'Bold', 'account-customizer-for-woocommerce' ),
			) );

			// Behaviour.
			$this->add_select( $wp_customize, 'acfw_ajax_navigation', 'no', __( 'AJAX navigation', 'account-customizer-for-woocommerce' ), $yes_no );

			// Avatar.
			$this->add_select( $wp_customize, 'acfw_avatar_enable', 'no', __( 'Show avatar', 'account-customizer-for-woocommerce' ), $yes_no );
			$this->add_select( $wp_customize, 'acfw_avatar_shape', 'circle', __( 'Avatar shape', 'account-customizer-for-woocommerce' ), array(
				'circle' => __( 'Circle', 'account-customizer-for-woocommerce' ),
				'square' => __( 'Square', 'account-customizer-for-woocommerce' ),
			) );
			$this->add_select( $wp_customize, 'acfw_avatar_align', 'center', __( 'Avatar alignment', 'account-customizer-for-woocommerce' ), array(
				'left'   => __( 'Left', 'account-customizer-for-woocommerce' ),
				'center' => __( 'Center', 'account-customizer-for-woocommerce' ),
				'right'  => __( 'Right', 'account-customizer-for-woocommerce' ),
			) );
			$this->add_number( $wp_customize, 'acfw_avatar_size', 72, __( 'Avatar size (px)', 'account-customizer-for-woocommerce' ), 32, 160 );
			$this->add_select( $wp_customize, 'acfw_avatar_show_name', 'yes', __( 'Show display name', 'account-customizer-for-woocommerce' ), $yes_no );
			$this->add_select( $wp_customize, 'acfw_avatar_show_role', 'no', __( 'Show user role', 'account-customizer-for-woocommerce' ), $yes_no );
		}

		/**
		 * Register a select-backed option setting + control.
		 */
		protected function add_select( $wp_customize, $id, $default, $label, $choices ) {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => $default,
					'transport'         => 'refresh',
					'sanitize_callback' => function ( $value ) use ( $choices ) {
						return array_key_exists( $value, $choices ) ? $value : '';
					},
				)
			);
			$wp_customize->add_control(
				$id,
				array(
					'section' => self::SECTION,
					'label'   => $label,
					'type'    => 'select',
					'choices' => $choices,
				)
			);
		}

		/**
		 * Register a color-backed option setting + color control.
		 */
		protected function add_color( $wp_customize, $id, $default, $label ) {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => $default,
					'transport'         => 'refresh',
					'sanitize_callback' => 'sanitize_hex_color',
				)
			);
			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					$id,
					array(
						'section' => self::SECTION,
						'label'   => $label,
					)
				)
			);
		}

		/**
		 * Register a number-backed option setting + control.
		 */
		protected function add_number( $wp_customize, $id, $default, $label, $min, $max ) {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => $default,
					'transport'         => 'refresh',
					'sanitize_callback' => 'absint',
				)
			);
			$wp_customize->add_control(
				$id,
				array(
					'section'     => self::SECTION,
					'label'       => $label,
					'type'        => 'number',
					'input_attrs' => array(
						'min'  => $min,
						'max'  => $max,
						'step' => 1,
					),
				)
			);
		}
	}
}
