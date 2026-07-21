<?php
/**
 * WordPress Customizer integration: live-preview design controls
 * grouped into Navigation / Layout & Colors / Avatar sections.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Customizer' ) ) {

	/**
	 * Registers a "My Account" Customizer panel with sections + custom controls.
	 */
	class ACFW_Customizer {

		const PANEL = 'acfw_panel';

		/**
		 * Section the add_* helpers currently target.
		 *
		 * @var string
		 */
		protected $section = 'acfw_nav';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'customize_register', array( $this, 'register' ) );
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_controls' ) );
		}

		/**
		 * Deep-link URL that opens the Customizer focused on our panel.
		 *
		 * @return string
		 */
		public static function url() {
			$preview = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
			return add_query_arg(
				array(
					'autofocus[panel]' => self::PANEL,
					'url'              => rawurlencode( $preview ),
				),
				admin_url( 'customize.php' )
			);
		}

		/**
		 * Enqueue the custom control scripts + styles.
		 */
		public function enqueue_controls() {
			wp_enqueue_style( 'acfw-customize-controls', ACFW_ASSETS_URL . '/css/customize-controls.css', array(), ACFW_VERSION );
			wp_enqueue_script( 'acfw-customize-controls', ACFW_ASSETS_URL . '/js/customize-controls.js', array( 'jquery', 'customize-controls' ), ACFW_VERSION, true );
		}

		/**
		 * Control image URL helper.
		 *
		 * @param string $file SVG file name.
		 * @return string
		 */
		protected function img( $file ) {
			return ACFW_ASSETS_URL . '/images/controls/' . $file;
		}

		/**
		 * Register panel, sections, settings and controls.
		 *
		 * @param WP_Customize_Manager $wp_customize Customizer manager.
		 */
		public function register( $wp_customize ) {

			require_once ACFW_DIR . 'includes/class-acfw-customize-controls.php';

			$wp_customize->register_control_type( 'ACFW_Customize_Toggle' );
			$wp_customize->register_control_type( 'ACFW_Customize_Slider' );
			$wp_customize->register_control_type( 'ACFW_Customize_ButtonSet' );
			$wp_customize->register_control_type( 'ACFW_Customize_ImageRadio' );

			$wp_customize->add_panel(
				self::PANEL,
				array(
					'title'    => __( 'My Account', 'account-customizer-for-woocommerce' ),
					'priority' => 1,
				)
			);

			$sections = array(
				'acfw_avatar' => __( 'Avatar', 'account-customizer-for-woocommerce' ),
				'acfw_nav'    => __( 'Navigation', 'account-customizer-for-woocommerce' ),
				'acfw_style'  => __( 'Layout & Colors', 'account-customizer-for-woocommerce' ),
			);
			$priority = 10;
			foreach ( $sections as $id => $title ) {
				$wp_customize->add_section(
					$id,
					array(
						'title'    => $title,
						'panel'    => self::PANEL,
						'priority' => $priority,
					)
				);
				$priority += 10;
			}

			// ---- Navigation ----
			$this->section = 'acfw_nav';
			$this->add_image_radio( $wp_customize, 'acfw_menu_position', 'vertical-left', __( 'Menu position', 'account-customizer-for-woocommerce' ), array(
				'vertical-left'  => array( 'name' => __( 'Left', 'account-customizer-for-woocommerce' ), 'image' => $this->img( 'vertical-left.svg' ) ),
				'vertical-right' => array( 'name' => __( 'Right', 'account-customizer-for-woocommerce' ), 'image' => $this->img( 'vertical-right.svg' ) ),
				'horizontal'     => array( 'name' => __( 'Top', 'account-customizer-for-woocommerce' ), 'image' => $this->img( 'top-horizontal.svg' ) ),
			) );
			$this->add_buttonset( $wp_customize, 'acfw_menu_layout', 'simple', __( 'Menu layout', 'account-customizer-for-woocommerce' ), array(
				'simple'     => __( 'Simple', 'account-customizer-for-woocommerce' ),
				'classic'    => __( 'Classic', 'account-customizer-for-woocommerce' ),
				'modern'     => __( 'Modern', 'account-customizer-for-woocommerce' ),
				'no-borders' => __( 'No borders', 'account-customizer-for-woocommerce' ),
			) );
			$this->add_toggle( $wp_customize, 'acfw_show_icons', 'yes', __( 'Show menu icons', 'account-customizer-for-woocommerce' ) );
			$this->add_toggle( $wp_customize, 'acfw_group_open', 'no', __( 'Expand groups by default', 'account-customizer-for-woocommerce' ) );
			$this->add_toggle( $wp_customize, 'acfw_ajax_navigation', 'no', __( 'AJAX navigation', 'account-customizer-for-woocommerce' ) );

			// ---- Layout & Colors ----
			$this->section = 'acfw_style';
			$this->add_color( $wp_customize, 'acfw_accent_color', '#2563eb', __( 'Accent color', 'account-customizer-for-woocommerce' ) );
			$this->add_color( $wp_customize, 'acfw_text_color', '#383838', __( 'Text color', 'account-customizer-for-woocommerce' ) );
			$this->add_slider( $wp_customize, 'acfw_menu_radius', 8, __( 'Corner radius', 'account-customizer-for-woocommerce' ), 0, 24 );
			$this->add_slider( $wp_customize, 'acfw_menu_gap', 4, __( 'Item spacing', 'account-customizer-for-woocommerce' ), 0, 24 );
			$this->add_slider( $wp_customize, 'acfw_item_padding', 11, __( 'Item padding', 'account-customizer-for-woocommerce' ), 4, 28 );
			$this->add_slider( $wp_customize, 'acfw_font_size', 15, __( 'Font size', 'account-customizer-for-woocommerce' ), 11, 22 );
			$this->add_buttonset( $wp_customize, 'acfw_font_weight', '500', __( 'Font weight', 'account-customizer-for-woocommerce' ), array(
				'400' => __( 'Normal', 'account-customizer-for-woocommerce' ),
				'500' => __( 'Medium', 'account-customizer-for-woocommerce' ),
				'600' => __( 'Bold', 'account-customizer-for-woocommerce' ),
			) );

			// ---- Avatar ----
			$this->section = 'acfw_avatar';
			$this->add_toggle( $wp_customize, 'acfw_avatar_enable', 'no', __( 'Show avatar', 'account-customizer-for-woocommerce' ) );
			$this->add_image( $wp_customize, 'acfw_avatar_image', '', __( 'Custom avatar image', 'account-customizer-for-woocommerce' ), __( 'Overrides the gravatar. Leave empty to use the customer avatar.', 'account-customizer-for-woocommerce' ) );
			$this->add_image_radio( $wp_customize, 'acfw_avatar_shape', 'circle', __( 'Avatar shape', 'account-customizer-for-woocommerce' ), array(
				'circle' => array( 'name' => __( 'Circle', 'account-customizer-for-woocommerce' ), 'image' => $this->img( 'circle-profile.svg' ) ),
				'square' => array( 'name' => __( 'Square', 'account-customizer-for-woocommerce' ), 'image' => $this->img( 'square-profile.svg' ) ),
			) );
			$this->add_image_radio( $wp_customize, 'acfw_avatar_align', 'center', __( 'Avatar alignment', 'account-customizer-for-woocommerce' ), array(
				'left'   => array( 'name' => __( 'Left', 'account-customizer-for-woocommerce' ), 'image' => $this->img( 'align-left.svg' ) ),
				'center' => array( 'name' => __( 'Center', 'account-customizer-for-woocommerce' ), 'image' => $this->img( 'align-center.svg' ) ),
				'right'  => array( 'name' => __( 'Right', 'account-customizer-for-woocommerce' ), 'image' => $this->img( 'align-right.svg' ) ),
			) );
			$this->add_slider( $wp_customize, 'acfw_avatar_size', 72, __( 'Avatar size', 'account-customizer-for-woocommerce' ), 32, 160 );
			$this->add_toggle( $wp_customize, 'acfw_avatar_show_name', 'yes', __( 'Show display name', 'account-customizer-for-woocommerce' ) );
			$this->add_toggle( $wp_customize, 'acfw_avatar_show_role', 'no', __( 'Show user role', 'account-customizer-for-woocommerce' ) );
		}

		/**
		 * Register a yes/no toggle.
		 */
		protected function add_toggle( $wp_customize, $id, $default, $label ) {
			$wp_customize->add_setting( $id, array(
				'type'              => 'option',
				'default'           => $default,
				'transport'         => 'refresh',
				'sanitize_callback' => function ( $value ) {
					return 'yes' === $value ? 'yes' : 'no';
				},
			) );
			$wp_customize->add_control( new ACFW_Customize_Toggle( $wp_customize, $id, array(
				'section' => $this->section,
				'label'   => $label,
			) ) );
		}

		/**
		 * Register a numeric slider ( px ).
		 */
		protected function add_slider( $wp_customize, $id, $default, $label, $min, $max ) {
			$wp_customize->add_setting( $id, array(
				'type'              => 'option',
				'default'           => $default,
				'transport'         => 'refresh',
				'sanitize_callback' => 'absint',
			) );
			$wp_customize->add_control( new ACFW_Customize_Slider( $wp_customize, $id, array(
				'section'     => $this->section,
				'label'       => $label,
				'input_attrs' => array( 'min' => $min, 'max' => $max, 'step' => 1 ),
			) ) );
		}

		/**
		 * Register a buttonset ( radio group ).
		 */
		protected function add_buttonset( $wp_customize, $id, $default, $label, $choices ) {
			$wp_customize->add_setting( $id, array(
				'type'              => 'option',
				'default'           => $default,
				'transport'         => 'refresh',
				'sanitize_callback' => function ( $value ) use ( $choices ) {
					return array_key_exists( $value, $choices ) ? $value : '';
				},
			) );
			$wp_customize->add_control( new ACFW_Customize_ButtonSet( $wp_customize, $id, array(
				'section' => $this->section,
				'label'   => $label,
				'choices' => $choices,
			) ) );
		}

		/**
		 * Register an image-radio.
		 */
		protected function add_image_radio( $wp_customize, $id, $default, $label, $choices ) {
			$wp_customize->add_setting( $id, array(
				'type'              => 'option',
				'default'           => $default,
				'transport'         => 'refresh',
				'sanitize_callback' => function ( $value ) use ( $choices ) {
					return array_key_exists( $value, $choices ) ? $value : '';
				},
			) );
			$wp_customize->add_control( new ACFW_Customize_ImageRadio( $wp_customize, $id, array(
				'section' => $this->section,
				'label'   => $label,
				'choices' => $choices,
			) ) );
		}

		/**
		 * Register an image ( media ) control storing a URL.
		 */
		protected function add_image( $wp_customize, $id, $default, $label, $description = '' ) {
			$wp_customize->add_setting( $id, array(
				'type'              => 'option',
				'default'           => $default,
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			) );
			$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $id, array(
				'section'     => $this->section,
				'label'       => $label,
				'description' => $description,
			) ) );
		}

		/**
		 * Register a color control.
		 */
		protected function add_color( $wp_customize, $id, $default, $label ) {
			$wp_customize->add_setting( $id, array(
				'type'              => 'option',
				'default'           => $default,
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, array(
				'section' => $this->section,
				'label'   => $label,
			) ) );
		}
	}
}
