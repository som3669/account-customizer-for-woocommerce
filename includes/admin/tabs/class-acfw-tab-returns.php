<?php
/**
 * Returns tab: answer customers' return requests.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Tab_Returns' ) ) {

	/**
	 * The list of return requests.
	 */
	class ACFW_Tab_Returns extends ACFW_Admin_Tab {

		/**
		 * Update a request's status.
		 *
		 * @param string     $action Action slug.
		 * @param ACFW_Items $items  Menu items manager.
		 */
		public function handle( $action, $items ) {
			if ( 'update_return' !== $action ) {
				return;
			}
			// Nonce checked in ACFW_Admin::handle_actions().
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$id     = isset( $_POST['return_id'] ) ? absint( wp_unslash( $_POST['return_id'] ) ) : 0;
			$status = isset( $_POST['return_status'] ) ? sanitize_key( wp_unslash( $_POST['return_status'] ) ) : '';
			$reply  = isset( $_POST['return_reply'] ) ? sanitize_textarea_field( wp_unslash( $_POST['return_reply'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing
			if ( ! ACFW_Returns::set_status( $id, $status, $reply ) ) {
				$this->add_notice( __( 'That return request could not be updated.', 'my-account-dashboard-builder' ), 'error' );
			}
		}

		/**
		 * Render the tab.
		 */
		public function render() {
			$filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a read-only filter.
			$labels = ACFW_Returns::statuses();
			$args   = array(
				'post_type'   => ACFW_Returns::CPT,
				'post_status' => 'publish',
				'numberposts' => 100,
			);
			if ( isset( $labels[ $filter ] ) ) {
				$args['meta_key']   = '_acfw_status'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['meta_value'] = $filter; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			}
			$requests = get_posts( $args );
			$base     = admin_url( 'admin.php?page=' . self::PAGE . '&tab=returns' );
			?>
			<div class="acfw-returns-admin">
				<section class="acfw-card acfw-insights-head">
					<div>
						<h2><?php esc_html_e( 'Returns', 'my-account-dashboard-builder' ); ?></h2>
						<?php if ( ACFW_Returns::returns_enabled() ) : ?>
							<p><?php esc_html_e( 'Customers ask to return items from My Account. Set a status and the customer is emailed; a message you add goes in the email.', 'my-account-dashboard-builder' ); ?></p>
						<?php else : ?>
							<p>
								<?php esc_html_e( 'Returns are switched off, so customers cannot ask for one.', 'my-account-dashboard-builder' ); ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '&tab=general&section=orders' ) ); ?>"><?php esc_html_e( 'Switch them on in Settings.', 'my-account-dashboard-builder' ); ?></a>
							</p>
						<?php endif; ?>
					</div>
					<nav class="acfw-segments acfw-insights-period" aria-label="<?php esc_attr_e( 'Show', 'my-account-dashboard-builder' ); ?>">
						<a class="acfw-segment-link<?php echo '' === $filter ? ' is-active' : ''; ?>" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'All', 'my-account-dashboard-builder' ); ?></a>
						<?php foreach ( $labels as $status => $label ) : ?>
							<a class="acfw-segment-link<?php echo $status === $filter ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'status', $status, $base ) ); ?>"><?php echo esc_html( $label ); ?></a>
						<?php endforeach; ?>
					</nav>
				</section>

				<?php if ( ! $requests ) : ?>
					<p class="acfw-card acfw-insights-empty"><?php esc_html_e( 'No return requests here.', 'my-account-dashboard-builder' ); ?></p>
				<?php endif; ?>

				<?php foreach ( $requests as $request ) : ?>
					<?php
					$order   = wc_get_order( (int) get_post_meta( $request->ID, '_acfw_order_id', true ) );
					$status  = (string) get_post_meta( $request->ID, '_acfw_status', true );
					$photo   = (int) get_post_meta( $request->ID, '_acfw_photo', true );
					$comment = (string) get_post_meta( $request->ID, '_acfw_comment', true );
					$reply   = (string) get_post_meta( $request->ID, '_acfw_reply', true );
					$user    = get_userdata( (int) $request->post_author );
					?>
					<article class="acfw-card acfw-return-card">
						<header class="acfw-return-head">
							<span class="acfw-return-status acfw-return-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $labels[ $status ] ?? $status ); ?></span>
							<?php if ( $order ) : ?>
								<?php /* translators: %s: order number. */ ?>
								<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>"><?php echo esc_html( sprintf( __( 'Order #%s', 'my-account-dashboard-builder' ), $order->get_order_number() ) ); ?></a>
							<?php endif; ?>
							<span class="acfw-return-who"><?php echo esc_html( $user ? $user->display_name . ' · ' . $user->user_email : '' ); ?></span>
							<span class="acfw-return-date"><?php echo esc_html( get_the_date( '', $request ) ); ?></span>
						</header>
						<p class="acfw-return-items-line"><strong><?php echo esc_html( ACFW_Returns::items_text( $request->ID, $order ) ); ?></strong> — <?php echo esc_html( (string) get_post_meta( $request->ID, '_acfw_reason', true ) ); ?></p>
						<?php if ( '' !== $comment ) : ?>
							<blockquote class="acfw-return-comment"><?php echo esc_html( $comment ); ?></blockquote>
						<?php endif; ?>
						<?php if ( $photo ) : ?>
							<a class="acfw-return-photo" href="<?php echo esc_url( (string) wp_get_attachment_url( $photo ) ); ?>" target="_blank" rel="noopener"><?php echo wp_get_attachment_image( $photo, 'thumbnail' ); ?></a>
						<?php endif; ?>
						<form method="post" class="acfw-return-update">
							<?php wp_nonce_field( self::NONCE ); ?>
							<input type="hidden" name="acfw_action" value="update_return" />
							<input type="hidden" name="return_id" value="<?php echo esc_attr( $request->ID ); ?>" />
							<label>
								<span><?php esc_html_e( 'Status', 'my-account-dashboard-builder' ); ?></span>
								<select name="return_status">
									<?php foreach ( $labels as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="acfw-return-reply-field">
								<span><?php esc_html_e( 'Message to the customer (optional)', 'my-account-dashboard-builder' ); ?></span>
								<textarea name="return_reply" rows="2" placeholder="<?php esc_attr_e( 'e.g. Please send it to our warehouse at …', 'my-account-dashboard-builder' ); ?>"><?php echo esc_textarea( $reply ); ?></textarea>
							</label>
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Update and email the customer', 'my-account-dashboard-builder' ); ?></button>
						</form>
					</article>
				<?php endforeach; ?>
			</div>
			<?php
		}
	}
}
