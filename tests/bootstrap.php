<?php
/**
 * PHPUnit bootstrap.
 *
 * These are unit tests, not integration tests: WordPress is never loaded. The
 * handful of core functions the code under test calls are stubbed with Brain
 * Monkey in each test's setUp(), so the suite runs in milliseconds without a
 * database or a WordPress checkout.
 *
 * @package AccountCustomizerForWooCommerce
 */

require_once __DIR__ . '/../vendor/autoload.php';

// The files under test guard on ABSPATH and read the plugin constants.
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'ACFW_VERSION', '1.0.0' );
define( 'ACFW_DIR', dirname( __DIR__ ) . '/' );
define( 'ACFW_URL', 'http://example.test/wp-content/plugins/my-account-dashboard-builder/' );
define( 'ACFW_ASSETS_URL', ACFW_URL . 'assets' );
define( 'ACFW_SLUG', 'my-account-dashboard-builder' );
define( 'DAY_IN_SECONDS', 86400 );

require_once ACFW_DIR . 'includes/helper/functions-acfw.php';
// Class definitions only: nothing here runs a constructor or adds a hook.
require_once ACFW_DIR . 'includes/class-acfw-items.php';
require_once ACFW_DIR . 'includes/class-acfw-order-stats.php';
require_once ACFW_DIR . 'includes/class-acfw-view-as.php';
require_once ACFW_DIR . 'includes/class-acfw-banners.php';
require_once ACFW_DIR . 'includes/class-acfw-design.php';
require_once ACFW_DIR . 'includes/admin/class-acfw-import-export.php';
