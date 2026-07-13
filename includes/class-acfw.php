<?php
/**
 * Main plugin singleton.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW' ) ) {

	/**
	 * Main plugin class.
	 */
	final class ACFW {

		/**
		 * Singleton instance.
		 *
		 * @var ACFW|null
		 */
		protected static $instance = null;

		/**
		 * Items model.
		 *
		 * @var ACFW_Items
		 */
		public $items;

		/**
		 * Frontend handler.
		 *
		 * @var ACFW_Frontend|null
		 */
		public $frontend = null;

		/**
		 * Admin handler.
		 *
		 * @var ACFW_Admin|null
		 */
		public $admin = null;

		/**
		 * Get the singleton instance.
		 *
		 * @return ACFW
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor: wire up the model and context handlers.
		 */
		private function __construct() {

			$this->items = new ACFW_Items();

			if ( is_admin() ) {
				require_once ACFW_DIR . 'includes/admin/class-acfw-admin.php';
				$this->admin = new ACFW_Admin();
			} else {
				require_once ACFW_DIR . 'includes/class-acfw-frontend.php';
				$this->frontend = new ACFW_Frontend();
			}
		}

		/**
		 * Prevent cloning.
		 */
		public function __clone() {}

		/**
		 * Prevent unserialize.
		 */
		public function __wakeup() {}
	}
}

/**
 * Accessor for the main plugin instance.
 *
 * @return ACFW
 */
function ACFW() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return ACFW::instance();
}
