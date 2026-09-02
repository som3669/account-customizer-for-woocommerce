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
			list( $cc_css_url, $cc_css_ver ) = acfw_asset_src( 'css/customize-controls.css' );
			list( $cc_js_url, $cc_js_ver )   = acfw_asset_src( 'js/customize-controls.js' );
			wp_enqueue_style( 'acfw-customize-controls', $cc_css_url, array(), $cc_css_ver );
			wp_enqueue_script( 'acfw-customize-controls', $cc_js_url, array( 'jquery', 'customize-controls' ), $cc_js_ver, true );
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

			require_once ACFW_DIR . 'includes/customize/class-acfw-customize-controls.php';

			$wp_customize->register_control_type( 'ACFW_Customize_Toggle' );
			$wp_customize->register_control_type( 'ACFW_Customize_Slider' );
			$wp_customize->register_control_type( 'ACFW_Customize_ButtonSet' );
			$wp_customize->register_control_type( 'ACFW_Customize_ImageRadio' );

			$wp_customize->add_panel(
				self::PANEL,
				array(
					'title'    => __( 'My Account', 'my-account-dashboard-builder' ),
					'priority' => 1,
				)
			);

			$sections = array(
				'acfw_avatar' => __( 'Avatar', 'my-account-dashboard-builder' ),
				'acfw_nav'    => __( 'Navigation', 'my-account-dashboard-builder' ),
				'acfw_style'  => __( 'Layout & Colors', 'my-account-dashboard-builder' ),
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
			$this->add_image_radio(
				$wp_customize,
				'acfw_menu_position',
				'vertical-left',
				__( 'Menu position', 'my-account-dashboard-builder' ),
				array(
					'vertical-left'  => array(
						'name'  => __( 'Left', 'my-account-dashboard-builder' ),
						'image' => $this->img( 'vertical-left.svg' ),
					),
					'vertical-right' => array(
						'name'  => __( 'Right', 'my-account-dashboard-builder' ),
						'image' => $this->img( 'vertical-right.svg' ),
					),
					'horizontal'     => array(
						'name'  => __( 'Top', 'my-account-dashboard-builder' ),
						'image' => $this->img( 'top-horizontal.svg' ),
					),
				)
			);
			$this->add_buttonset( $wp_customize, 'acfw_menu_style', 'simple', __( 'Menu style', 'my-account-dashboard-builder' ), acfw_menu_styles() );
			$this->add_toggle( $wp_customize, 'acfw_show_icons', 'yes', __( 'Show menu icons', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_show_counts', 'yes', __( 'Show item counts', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_dashboard_tiles', 'no', __( 'Dashboard quick-link tiles', 'my-account-dashboard-builder' ) );
			$this->add_text( $wp_customize, 'acfw_dashboard_title', '', __( 'Dashboard title', 'my-account-dashboard-builder' ) );
			$this->add_buttonset(
				$wp_customize,
				'acfw_dashboard_align',
				'left',
				__( 'Dashboard content position', 'my-account-dashboard-builder' ),
				array(
					'left'   => __( 'Left', 'my-account-dashboard-builder' ),
					'center' => __( 'Middle', 'my-account-dashboard-builder' ),
					'right'  => __( 'Right', 'my-account-dashboard-builder' ),
				)
			);
			$this->add_toggle( $wp_customize, 'acfw_dashboard_stats', 'no', __( 'Dashboard stat widgets', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_stat_orders', 'yes', __( '• Total orders', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_stat_pending', 'yes', __( '• Pending orders', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_stat_spent', 'yes', __( '• Total spent', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_stat_downloads', 'yes', __( '• Downloads', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_stat_refunds', 'no', __( '• Refunds', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_stat_points', 'no', __( '• Reward points', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_stat_latest', 'no', __( '• Latest order', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_stat_piechart', 'no', __( '• Orders pie chart', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_menu_search', 'no', __( 'Menu search box', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_collapsible', 'no', __( 'Collapsible icon rail', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_pin_enable', 'no', __( 'Let customers pin favorites', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_profile_meter', 'no', __( 'Profile completeness meter', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_sticky_menu', 'no', __( 'Sticky menu', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_logout_confirm', 'no', __( 'Confirm before logout', 'my-account-dashboard-builder' ) );
			$this->add_buttonset(
				$wp_customize,
				'acfw_active_indicator',
				'bar',
				__( 'Active indicator', 'my-account-dashboard-builder' ),
				array(
					'bar'       => __( 'Bar', 'my-account-dashboard-builder' ),
					'underline' => __( 'Underline', 'my-account-dashboard-builder' ),
					'dot'       => __( 'Dot', 'my-account-dashboard-builder' ),
					'none'      => __( 'None', 'my-account-dashboard-builder' ),
				)
			);
			$this->add_buttonset(
				$wp_customize,
				'acfw_hover_anim',
				'none',
				__( 'Hover animation', 'my-account-dashboard-builder' ),
				array(
					'none'  => __( 'None', 'my-account-dashboard-builder' ),
					'slide' => __( 'Slide', 'my-account-dashboard-builder' ),
					'grow'  => __( 'Grow', 'my-account-dashboard-builder' ),
				)
			);
			$this->add_toggle( $wp_customize, 'acfw_group_open', 'no', __( 'Expand groups by default', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_ajax_navigation', 'no', __( 'AJAX navigation', 'my-account-dashboard-builder' ) );

			// ---- Layout & Colors ----
			$this->section = 'acfw_style';
			$this->add_color( $wp_customize, 'acfw_accent_color', '#2563eb', __( 'Accent color', 'my-account-dashboard-builder' ) );
			$this->add_color( $wp_customize, 'acfw_text_color', '#383838', __( 'Text color', 'my-account-dashboard-builder' ) );
			$this->add_color( $wp_customize, 'acfw_active_color', '', __( 'Active color', 'my-account-dashboard-builder' ) );
			$this->add_color( $wp_customize, 'acfw_menu_bg', '', __( 'Menu item background', 'my-account-dashboard-builder' ) );
			$this->add_color( $wp_customize, 'acfw_hover_bg', '', __( 'Hover background', 'my-account-dashboard-builder' ) );
			$this->add_slider( $wp_customize, 'acfw_menu_radius', 8, __( 'Corner radius', 'my-account-dashboard-builder' ), 0, 24 );
			$this->add_slider( $wp_customize, 'acfw_menu_gap', 4, __( 'Item spacing', 'my-account-dashboard-builder' ), 0, 24 );
			$this->add_slider( $wp_customize, 'acfw_item_padding', 11, __( 'Item padding', 'my-account-dashboard-builder' ), 4, 28 );
			$this->add_slider( $wp_customize, 'acfw_font_size', 15, __( 'Font size', 'my-account-dashboard-builder' ), 11, 22 );
			$this->add_buttonset(
				$wp_customize,
				'acfw_font_weight',
				'500',
				__( 'Font weight', 'my-account-dashboard-builder' ),
				array(
					'400' => __( 'Normal', 'my-account-dashboard-builder' ),
					'500' => __( 'Medium', 'my-account-dashboard-builder' ),
					'600' => __( 'Bold', 'my-account-dashboard-builder' ),
				)
			);
			$this->add_buttonset(
				$wp_customize,
				'acfw_font_family',
				'inherit',
				__( 'Font family', 'my-account-dashboard-builder' ),
				array(
					'inherit' => __( 'Theme', 'my-account-dashboard-builder' ),
					'system'  => __( 'System', 'my-account-dashboard-builder' ),
					'serif'   => __( 'Serif', 'my-account-dashboard-builder' ),
					'mono'    => __( 'Mono', 'my-account-dashboard-builder' ),
				)
			);
			$this->add_buttonset(
				$wp_customize,
				'acfw_color_scheme',
				'light',
				__( 'Color scheme', 'my-account-dashboard-builder' ),
				array(
					'auto'  => __( 'Auto ( follow visitor OS )', 'my-account-dashboard-builder' ),
					'light' => __( 'Light', 'my-account-dashboard-builder' ),
					'dark'  => __( 'Dark', 'my-account-dashboard-builder' ),
				)
			);
			$this->add_css( $wp_customize, 'acfw_custom_css', __( 'Custom CSS', 'my-account-dashboard-builder' ) );

			// ---- Avatar ----
			$this->section = 'acfw_avatar';
			$this->add_toggle( $wp_customize, 'acfw_avatar_enable', 'no', __( 'Show avatar', 'my-account-dashboard-builder' ) );
			$this->add_image( $wp_customize, 'acfw_avatar_image', '', __( 'Custom avatar image', 'my-account-dashboard-builder' ), __( 'Overrides the gravatar. Leave empty to use the customer avatar.', 'my-account-dashboard-builder' ) );
			$this->add_image_radio(
				$wp_customize,
				'acfw_avatar_shape',
				'circle',
				__( 'Avatar shape', 'my-account-dashboard-builder' ),
				array(
					'circle' => array(
						'name'  => __( 'Circle', 'my-account-dashboard-builder' ),
						'image' => $this->img( 'circle-profile.svg' ),
					),
					'square' => array(
						'name'  => __( 'Square', 'my-account-dashboard-builder' ),
						'image' => $this->img( 'square-profile.svg' ),
					),
				)
			);
			$this->add_image_radio(
				$wp_customize,
				'acfw_avatar_align',
				'center',
				__( 'Avatar alignment', 'my-account-dashboard-builder' ),
				array(
					'left'   => array(
						'name'  => __( 'Left', 'my-account-dashboard-builder' ),
						'image' => $this->img( 'align-left.svg' ),
					),
					'center' => array(
						'name'  => __( 'Center', 'my-account-dashboard-builder' ),
						'image' => $this->img( 'align-center.svg' ),
					),
					'right'  => array(
						'name'  => __( 'Right', 'my-account-dashboard-builder' ),
						'image' => $this->img( 'align-right.svg' ),
					),
				)
			);
			$this->add_slider( $wp_customize, 'acfw_avatar_size', 72, __( 'Avatar size', 'my-account-dashboard-builder' ), 32, 160 );
			$this->add_toggle( $wp_customize, 'acfw_avatar_show_name', 'yes', __( 'Show display name', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_avatar_show_role', 'no', __( 'Show user role', 'my-account-dashboard-builder' ) );
			$this->add_toggle( $wp_customize, 'acfw_avatar_upload', 'no', __( 'Let customers upload their own picture', 'my-account-dashboard-builder' ) );
			$this->add_slider( $wp_customize, 'acfw_avatar_upload_max', 2048, __( 'Maximum upload size (KB)', 'my-account-dashboard-builder' ), 256, 8192 );

			// Show avatar sub-options only when the avatar is enabled.
			$avatar_deps = array(
				'acfw_avatar_image',
				'acfw_avatar_shape',
				'acfw_avatar_align',
				'acfw_avatar_size',
				'acfw_avatar_show_name',
				'acfw_avatar_show_role',
				'acfw_avatar_upload',
				'acfw_avatar_upload_max',
			);
			foreach ( $avatar_deps as $dep ) {
				$ctrl = $wp_customize->get_control( $dep );
				if ( $ctrl ) {
					$ctrl->active_callback = function () {
						return 'yes' === get_option( 'acfw_avatar_enable', 'no' );
					};
				}
			}
		}

		/**
		 * Register a yes/no toggle.
		 */
		protected function add_text( $wp_customize, $id, $default_value, $label ) {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => $default_value,
					'transport'         => 'refresh',
					'sanitize_callback' => 'sanitize_text_field',
				)
			);
			$wp_customize->add_control(
				$id,
				array(
					'type'    => 'text',
					'section' => $this->section,
					'label'   => $label,
				)
			);
		}

		/**
		 * Register a yes/no toggle control.
		 */
		protected function add_toggle( $wp_customize, $id, $default_value, $label ) {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => $default_value,
					'transport'         => 'refresh',
					'sanitize_callback' => function ( $value ) {
						return 'yes' === $value ? 'yes' : 'no';
					},
				)
			);
			$wp_customize->add_control(
				new ACFW_Customize_Toggle(
					$wp_customize,
					$id,
					array(
						'section' => $this->section,
						'label'   => $label,
					)
				)
			);
		}

		/**
		 * Register a numeric slider ( px ).
		 */
		protected function add_slider( $wp_customize, $id, $default_value, $label, $min, $max ) {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => $default_value,
					'transport'         => 'refresh',
					'sanitize_callback' => 'absint',
				)
			);
			$wp_customize->add_control(
				new ACFW_Customize_Slider(
					$wp_customize,
					$id,
					array(
						'section'     => $this->section,
						'label'       => $label,
						'input_attrs' => array(
							'min'  => $min,
							'max'  => $max,
							'step' => 1,
						),
					)
				)
			);
		}

		/**
		 * Register a buttonset ( radio group ).
		 */
		protected function add_buttonset( $wp_customize, $id, $default_value, $label, $choices ) {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => $default_value,
					'transport'         => 'refresh',
					'sanitize_callback' => function ( $value ) use ( $choices ) {
						return array_key_exists( $value, $choices ) ? $value : '';
					},
				)
			);
			$wp_customize->add_control(
				new ACFW_Customize_ButtonSet(
					$wp_customize,
					$id,
					array(
						'section' => $this->section,
						'label'   => $label,
						'choices' => $choices,
					)
				)
			);
		}

		/**
		 * Register an image-radio.
		 */
		protected function add_image_radio( $wp_customize, $id, $default_value, $label, $choices ) {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => $default_value,
					'transport'         => 'refresh',
					'sanitize_callback' => function ( $value ) use ( $choices ) {
						return array_key_exists( $value, $choices ) ? $value : '';
					},
				)
			);
			$wp_customize->add_control(
				new ACFW_Customize_ImageRadio(
					$wp_customize,
					$id,
					array(
						'section' => $this->section,
						'label'   => $label,
						'choices' => $choices,
					)
				)
			);
		}

		/**
		 * Register an image ( media ) control storing a URL.
		 */
		protected function add_image( $wp_customize, $id, $default_value, $label, $description = '' ) {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => $default_value,
					'transport'         => 'refresh',
					'sanitize_callback' => 'esc_url_raw',
				)
			);
			$wp_customize->add_control(
				new WP_Customize_Image_Control(
					$wp_customize,
					$id,
					array(
						'section'     => $this->section,
						'label'       => $label,
						'description' => $description,
					)
				)
			);
		}

		/**
		 * Register a Custom CSS code editor ( falls back to a textarea ).
		 */
		protected function add_css( $wp_customize, $id, $label ) {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => '',
					'transport'         => 'refresh',
					'sanitize_callback' => 'wp_strip_all_tags',
				)
			);
			if ( class_exists( 'WP_Customize_Code_Editor_Control' ) ) {
				$wp_customize->add_control(
					new WP_Customize_Code_Editor_Control(
						$wp_customize,
						$id,
						array(
							'section'   => $this->section,
							'label'     => $label,
							'code_type' => 'text/css',
						)
					)
				);
			} else {
				$wp_customize->add_control(
					$id,
					array(
						'section' => $this->section,
						'label'   => $label,
						'type'    => 'textarea',
					)
				);
			}
		}

		/**
		 * Register a color control.
		 */
		protected function add_color( $wp_customize, $id, $default_value, $label ) {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => $default_value,
					'transport'         => 'refresh',
					'sanitize_callback' => 'sanitize_hex_color',
				)
			);
			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					$id,
					array(
						'section' => $this->section,
						'label'   => $label,
					)
				)
			);
		}
	}
}
