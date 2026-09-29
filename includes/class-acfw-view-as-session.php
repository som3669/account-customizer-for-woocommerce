<?php
/**
 * WooCommerce's session while a shop manager views My Account as a customer:
 * it lives in memory for the one request. Nothing is read from or written to
 * the sessions table or cookies, so the customer's own cart and session stay
 * exactly as they were.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_View_As_Session' ) && class_exists( 'WC_Session_Handler' ) ) {

	/**
	 * An in-memory WooCommerce session.
	 */
	class ACFW_View_As_Session extends WC_Session_Handler {

		/**
		 * Start empty, with no hooks: no cookie, no save at shutdown.
		 */
		public function init() {
			$this->_customer_id = (string) get_current_user_id();
			$this->_data        = array();
		}

		/**
		 * No cookie.
		 */
		public function init_session_cookie() {}

		/**
		 * No cookie.
		 */
		public function maybe_set_customer_session_cookie() {}

		/**
		 * No cookie.
		 *
		 * @param bool $set Whether to set it.
		 */
		public function set_customer_session_cookie( $set ) {}

		/**
		 * Nothing stored.
		 *
		 * @param string $old_session_key Previous key.
		 */
		public function save_data( $old_session_key = '' ) {}

		/**
		 * Nothing read.
		 *
		 * @param string $customer_id   Customer.
		 * @param mixed  $default_value Default.
		 * @return mixed
		 */
		public function get_session( $customer_id, $default_value = false ) {
			return $default_value;
		}

		/**
		 * Nothing to delete.
		 *
		 * @param string $customer_id Customer.
		 */
		public function delete_session( $customer_id ) {}

		/**
		 * Nothing to update.
		 *
		 * @param string $customer_id Customer.
		 * @param int    $timestamp   Time.
		 */
		public function update_session_timestamp( $customer_id, $timestamp ) {}

		/**
		 * Forget the in-memory data only.
		 */
		public function destroy_session() {
			$this->_data = array();
		}

		/**
		 * Forget the in-memory data only.
		 */
		public function forget_session() {
			$this->_data = array();
		}

		/**
		 * Nothing to clean up.
		 */
		public function destroy_session_if_empty() {}

		/**
		 * Nothing to clean up.
		 */
		public function cleanup_sessions() {}
	}
}
