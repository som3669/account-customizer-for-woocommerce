<?php
/**
 * Customer avatar upload: lets logged-in customers set their own profile
 * picture from the My Account page and overrides the Gravatar everywhere.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Avatar' ) ) {

	/**
	 * Handles the customer-uploaded avatar: storage, AJAX, output and privacy.
	 */
	class ACFW_Avatar {

		/**
		 * User meta key holding the uploaded avatar's attachment ID.
		 *
		 * @var string
		 */
		const META = 'acfw_avatar_id';

		/**
		 * AJAX nonce action.
		 *
		 * @var string
		 */
		const NONCE = 'acfw_avatar';

		/**
		 * Image MIME types a customer may upload.
		 *
		 * @var array
		 */
		const MIMES = array(
			'jpg|jpeg' => 'image/jpeg',
			'png'      => 'image/png',
			'gif'      => 'image/gif',
			'webp'     => 'image/webp',
		);

		/**
		 * Hook up AJAX handlers, the avatar filter and privacy tools.
		 */
		public function __construct() {
			add_action( 'wp_ajax_acfw_avatar_upload', array( $this, 'ajax_upload' ) );
			add_action( 'wp_ajax_acfw_avatar_remove', array( $this, 'ajax_remove' ) );
			add_filter( 'get_avatar_data', array( $this, 'filter_avatar_data' ), 20, 2 );
			add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
			add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
		}

		/**
		 * Is the customer avatar upload feature enabled?
		 *
		 * @return bool
		 */
		public static function enabled() {
			return 'yes' === get_option( 'acfw_avatar_upload', 'no' );
		}

		/**
		 * Maximum upload size in bytes ( admin-configured, in KB ).
		 *
		 * @return int
		 */
		public static function max_bytes() {
			$kb = absint( get_option( 'acfw_avatar_upload_max', 2048 ) );
			$kb = $kb ? $kb : 2048;
			return $kb * 1024;
		}

		/**
		 * URL of a user's uploaded avatar, if any.
		 *
		 * @param int    $user_id User ID.
		 * @param string $size    Registered image size.
		 * @return string Empty string when the user has no uploaded avatar.
		 */
		public static function url( $user_id, $size = 'thumbnail' ) {
			$id = (int) get_user_meta( $user_id, self::META, true );
			if ( ! $id ) {
				return '';
			}
			$url = wp_get_attachment_image_url( $id, $size );
			return $url ? $url : '';
		}

		/**
		 * Override the avatar URL with the customer's uploaded image.
		 *
		 * @param array $args        get_avatar_data() args ( includes 'url' ).
		 * @param mixed $id_or_email User ID, email, WP_User, WP_Post or WP_Comment.
		 * @return array
		 */
		public function filter_avatar_data( $args, $id_or_email ) {

			$user_id = $this->resolve_user_id( $id_or_email );
			if ( ! $user_id ) {
				return $args;
			}

			$size = ! empty( $args['size'] ) ? (int) $args['size'] : 96;
			$url  = self::url( $user_id, $size > 150 ? 'medium' : 'thumbnail' );
			if ( '' === $url ) {
				return $args;
			}

			$args['url']          = $url;
			$args['found_avatar'] = true;
			return $args;
		}

		/**
		 * Resolve a WordPress user ID from the many shapes get_avatar accepts.
		 *
		 * @param mixed $id_or_email Identifier passed by core.
		 * @return int 0 when it does not map to a user.
		 */
		protected function resolve_user_id( $id_or_email ) {
			if ( $id_or_email instanceof WP_User ) {
				return (int) $id_or_email->ID;
			}
			if ( $id_or_email instanceof WP_Post ) {
				return (int) $id_or_email->post_author;
			}
			if ( $id_or_email instanceof WP_Comment ) {
				return ! empty( $id_or_email->user_id ) ? (int) $id_or_email->user_id : 0;
			}
			if ( is_numeric( $id_or_email ) ) {
				return (int) $id_or_email;
			}
			if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
				$user = get_user_by( 'email', $id_or_email );
				return $user ? (int) $user->ID : 0;
			}
			return 0;
		}

		/**
		 * AJAX: store a customer-uploaded avatar.
		 */
		public function ajax_upload() {

			if ( ! self::enabled() || ! is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'my-account-customizer' ) ), 403 );
			}
			check_ajax_referer( self::NONCE, 'nonce' );

			if ( empty( $_FILES['avatar']['name'] ) || ! isset( $_FILES['avatar']['error'] ) || UPLOAD_ERR_OK !== (int) $_FILES['avatar']['error'] ) {
				wp_send_json_error( array( 'message' => __( 'No file was uploaded.', 'my-account-customizer' ) ) );
			}

			$file = $_FILES['avatar']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated below.

			// Size guard.
			if ( (int) $file['size'] > self::max_bytes() ) {
				wp_send_json_error(
					array(
						/* translators: %s: maximum size, e.g. "2 MB". */
						'message' => sprintf( __( 'The image is too large. Maximum size is %s.', 'my-account-customizer' ), size_format( self::max_bytes() ) ),
					)
				);
			}

			// Extension / MIME allowlist ( ignores the browser-declared type ).
			$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], self::MIMES );
			if ( empty( $check['ext'] ) || empty( $check['type'] ) || ! in_array( $check['type'], self::MIMES, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Please upload a JPG, PNG, GIF or WebP image.', 'my-account-customizer' ) ) );
			}

			// Confirm the bytes really are an image.
			$dims = @getimagesize( $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- invalid image returns false, handled next.
			if ( false === $dims ) {
				wp_send_json_error( array( 'message' => __( 'The file is not a valid image.', 'my-account-customizer' ) ) );
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$user_id = get_current_user_id();

			add_filter( 'upload_mimes', array( $this, 'restrict_mimes' ) );
			$attachment_id = media_handle_upload(
				'avatar',
				0,
				array( 'post_author' => $user_id ),
				array(
					'test_form' => false,
					'mimes'     => self::MIMES,
				)
			);
			remove_filter( 'upload_mimes', array( $this, 'restrict_mimes' ) );

			if ( is_wp_error( $attachment_id ) ) {
				wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
			}

			$this->delete_stored_attachment( $user_id );
			update_user_meta( $user_id, self::META, (int) $attachment_id );

			$size = absint( get_option( 'acfw_avatar_size', 72 ) );
			wp_send_json_success(
				array(
					'url'     => self::url( $user_id, $size > 150 ? 'medium' : 'thumbnail' ),
					'message' => __( 'Profile picture updated.', 'my-account-customizer' ),
				)
			);
		}

		/**
		 * AJAX: remove the customer's uploaded avatar.
		 */
		public function ajax_remove() {

			if ( ! self::enabled() || ! is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'my-account-customizer' ) ), 403 );
			}
			check_ajax_referer( self::NONCE, 'nonce' );

			$user_id = get_current_user_id();
			$this->delete_stored_attachment( $user_id );
			delete_user_meta( $user_id, self::META );

			wp_send_json_success(
				array(
					'url'     => get_avatar_url( $user_id, array( 'force_default' => false ) ),
					'message' => __( 'Profile picture removed.', 'my-account-customizer' ),
				)
			);
		}

		/**
		 * Constrain uploads to image types during our own upload only.
		 *
		 * @param array $mimes Allowed MIME map ( replaced wholesale ).
		 * @return array
		 */
		public function restrict_mimes( $mimes ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- filter signature.
			unset( $mimes );
			return self::MIMES;
		}

		/**
		 * Delete a user's previously stored avatar attachment.
		 *
		 * @param int $user_id User ID.
		 */
		protected function delete_stored_attachment( $user_id ) {
			$old = (int) get_user_meta( $user_id, self::META, true );
			if ( $old ) {
				wp_delete_attachment( $old, true );
			}
		}

		/**
		 * Register the privacy exporter for the uploaded avatar.
		 *
		 * @param array $exporters Registered exporters.
		 * @return array
		 */
		public function register_exporter( $exporters ) {
			$exporters['my-account-customizer'] = array(
				'exporter_friendly_name' => __( 'Account Customizer avatar', 'my-account-customizer' ),
				'callback'               => array( $this, 'export_data' ),
			);
			return $exporters;
		}

		/**
		 * Export the uploaded avatar URL for a given email.
		 *
		 * @param string $email Email address.
		 * @return array
		 */
		public function export_data( $email ) {
			$user = get_user_by( 'email', $email );
			$data = array();
			if ( $user ) {
				$url = self::url( $user->ID, 'full' );
				if ( '' !== $url ) {
					$data[] = array(
						'group_id'    => 'acfw_avatar',
						'group_label' => __( 'Profile picture', 'my-account-customizer' ),
						'item_id'     => 'acfw-avatar',
						'data'        => array(
							array(
								'name'  => __( 'Uploaded profile picture', 'my-account-customizer' ),
								'value' => $url,
							),
						),
					);
				}
			}
			return array(
				'data' => $data,
				'done' => true,
			);
		}

		/**
		 * Register the privacy eraser for the uploaded avatar.
		 *
		 * @param array $erasers Registered erasers.
		 * @return array
		 */
		public function register_eraser( $erasers ) {
			$erasers['my-account-customizer'] = array(
				'eraser_friendly_name' => __( 'Account Customizer avatar', 'my-account-customizer' ),
				'callback'             => array( $this, 'erase_data' ),
			);
			return $erasers;
		}

		/**
		 * Erase the uploaded avatar for a given email.
		 *
		 * @param string $email Email address.
		 * @return array
		 */
		public function erase_data( $email ) {
			$user    = get_user_by( 'email', $email );
			$removed = false;
			if ( $user && get_user_meta( $user->ID, self::META, true ) ) {
				$this->delete_stored_attachment( $user->ID );
				delete_user_meta( $user->ID, self::META );
				$removed = true;
			}
			return array(
				'items_removed'  => $removed,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
	}
}
