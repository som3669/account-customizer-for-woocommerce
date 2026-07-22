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
