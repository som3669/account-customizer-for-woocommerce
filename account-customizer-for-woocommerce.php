<?php
/**
 * Plugin Name: My Account Customizer for WooCommerce
 * Plugin URI:  https://example.com/account-customizer-for-woocommerce
 * Description: Customize the WooCommerce "My Account" page: reorder the menu, add custom endpoints, groups and links, set per-endpoint content, control visibility by user role and restyle the whole area.
 * Version:     0.1.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: account-customizer-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.9
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'ACFW_VERSION', '0.1.0' );
define( 'ACFW_FILE', __FILE__ );
define( 'ACFW_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACFW_URL', plugin_dir_url( __FILE__ ) );
define( 'ACFW_ASSETS_URL', ACFW_URL . 'assets' );
define( 'ACFW_TEMPLATE_PATH', ACFW_DIR . 'templates' );
define( 'ACFW_SLUG', 'account-customizer-for-woocommerce' );

/**
 * Admin notice shown when WooCommerce is not active.
 */
function acfw_missing_wc_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			esc_html_e(
				'My Account Customizer for WooCommerce is enabled but requires WooCommerce to be installed and active.',
				'account-customizer-for-woocommerce'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Bootstrap the plugin once all plugins are loaded.
 */
function acfw_init() {

	load_plugin_textdomain(
		'account-customizer-for-woocommerce',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'acfw_missing_wc_notice' );
		return;
	}

	require_once ACFW_DIR . 'includes/functions-acfw.php';
	require_once ACFW_DIR . 'includes/class-acfw-items.php';
	require_once ACFW_DIR . 'includes/class-acfw.php';

	ACFW();
}
add_action( 'plugins_loaded', 'acfw_init', 11 );

/**
 * Record the user's last login time ( used by the {last_login} smart tag ).
 *
 * @param string  $login User login.
 * @param WP_User $user  User object.
 */
function acfw_record_last_login( $login, $user ) {
	if ( $user instanceof WP_User ) {
		update_user_meta( $user->ID, 'acfw_last_login', time() );
	}
}
add_action( 'wp_login', 'acfw_record_last_login', 10, 2 );

/**
 * Render the account menu markup ( shortcode + block callback ).
 *
 * @return string
 */
function acfw_account_menu_render() {
	if ( ! class_exists( 'ACFW' ) ) {
		return '';
	}
	$frontend = ( isset( ACFW()->frontend ) && ACFW()->frontend instanceof ACFW_Frontend ) ? ACFW()->frontend : null;
	if ( ! $frontend ) {
		require_once ACFW_DIR . 'includes/class-acfw-frontend.php';
		$frontend = new ACFW_Frontend();
	}
	return $frontend->menu_markup();
}
add_shortcode( 'acfw_account_menu', 'acfw_account_menu_render' );

/**
 * Register the "Account Menu" block ( dynamic, server-rendered ).
 */
function acfw_register_menu_block() {
	if ( function_exists( 'register_block_type' ) ) {
		register_block_type(
			'acfw/account-menu',
			array(
				'api_version'     => 3,
				'render_callback' => 'acfw_account_menu_render',
			)
		);
	}
}
add_action( 'init', 'acfw_register_menu_block' );

/**
 * Editor script so the block appears in the inserter ( uses ServerSideRender ).
 */
function acfw_block_editor_assets() {
	wp_enqueue_script(
		'acfw-account-menu-block',
		ACFW_ASSETS_URL . '/js/block.js',
		array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-i18n' ),
		ACFW_VERSION,
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'acfw_block_editor_assets' );

/**
 * Classic "Account Menu" widget.
 */
if ( class_exists( 'WP_Widget' ) ) {
	class ACFW_Menu_Widget extends WP_Widget {

		public function __construct() {
			parent::__construct(
				'acfw_menu_widget',
				__( 'Account Menu', 'account-customizer-for-woocommerce' ),
				array( 'description' => __( 'The customized WooCommerce My Account menu.', 'account-customizer-for-woocommerce' ) )
			);
		}

		public function widget( $args, $instance ) {
			if ( ! is_user_logged_in() ) {
				return;
			}
			$markup = acfw_account_menu_render();
			if ( '' === $markup ) {
				return;
			}
			echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in template.
			echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		public function form( $instance ) {
			$title = isset( $instance['title'] ) ? $instance['title'] : '';
			printf(
				'<p><label>%s <input class="widefat" name="%s" value="%s" /></label></p>',
				esc_html__( 'Title', 'account-customizer-for-woocommerce' ),
				esc_attr( $this->get_field_name( 'title' ) ),
				esc_attr( $title )
			);
		}

		public function update( $new_instance, $old_instance ) {
			return array( 'title' => sanitize_text_field( $new_instance['title'] ?? '' ) );
		}
	}

	add_action( 'widgets_init', function () {
		register_widget( 'ACFW_Menu_Widget' );
	} );
}

/**
 * Declare compatibility with WooCommerce features (HPOS + cart/checkout blocks).
 */
function acfw_declare_wc_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'acfw_declare_wc_compatibility' );

/**
 * Flag a rewrite-rules flush on activation.
 */
function acfw_activate() {
	update_option( 'acfw_flush_rewrite_rules', 1 );
}
register_activation_hook( __FILE__, 'acfw_activate' );

/**
 * Clean rewrite rules on deactivation.
 */
function acfw_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'acfw_deactivate' );
