<?php
/**
 * Custom customer fields ( phone, company, VAT number, birthday … ) on the
 * registration form and in Account details, stored on the customer and shown
 * on their profile and on their orders in the admin.
 *
 * Fields are defined on the Fields tab ( option acfw_fields ). Values live in
 * user meta acfw_field_{key}, are exported and erased with WordPress's
 * privacy tools, and can be shown with the {field_{key}} smart tag.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Fields' ) ) {

	/**
	 * Custom customer fields.
	 */
	class ACFW_Fields {

		/**
		 * Option holding the field definitions.
		 *
		 * @var string
		 */
		const OPTION = 'acfw_fields';

		/**
		 * User meta prefix.
		 *
		 * @var string
		 */
		const META = 'acfw_field_';

		/**
		 * Field types ( type => label ).
		 *
		 * @return array
		 */
		public static function types() {
			return array(
				'text'     => __( 'Text', 'my-account-dashboard-builder' ),
				'textarea' => __( 'Paragraph', 'my-account-dashboard-builder' ),
				'email'    => __( 'Email', 'my-account-dashboard-builder' ),
				'tel'      => __( 'Phone', 'my-account-dashboard-builder' ),
				'number'   => __( 'Number', 'my-account-dashboard-builder' ),
				'date'     => __( 'Date', 'my-account-dashboard-builder' ),
				'select'   => __( 'Dropdown', 'my-account-dashboard-builder' ),
				'radio'    => __( 'Choice', 'my-account-dashboard-builder' ),
				'checkbox' => __( 'Tick box', 'my-account-dashboard-builder' ),
			);
		}

		/**
		 * Hook the forms, the profile screen, orders, smart tags and privacy.
		 */
		public function __construct() {
			if ( ! self::all() ) {
				return;
			}
			add_action( 'woocommerce_register_form', array( $this, 'register_form' ) );
			add_filter( 'woocommerce_registration_errors', array( $this, 'register_errors' ) );
			add_action( 'woocommerce_created_customer', array( $this, 'register_save' ) );

			add_action( 'woocommerce_edit_account_form', array( $this, 'account_form' ) );
			add_action( 'woocommerce_save_account_details_errors', array( $this, 'account_errors' ) );
			add_action( 'woocommerce_save_account_details', array( $this, 'account_save' ) );

			add_action( 'show_user_profile', array( $this, 'profile_fields' ) );
			add_action( 'edit_user_profile', array( $this, 'profile_fields' ) );
			add_action( 'personal_options_update', array( $this, 'profile_save' ) );
			add_action( 'edit_user_profile_update', array( $this, 'profile_save' ) );

			add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'order_fields' ) );

			add_filter( 'acfw_smart_tag_value', array( $this, 'smart_tag' ), 10, 3 );
			add_filter( 'acfw_smart_tags', array( $this, 'smart_tag_list' ) );

			add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
			add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
		}

		/**
		 * Every field, in order.
		 *
		 * @return array
		 */
		public static function all() {
			$fields = get_option( self::OPTION, array() );
			return is_array( $fields ) ? acfw_fields_sanitize_definitions( $fields ) : array();
		}

		/**
		 * The fields shown in one place.
		 *
		 * @param string $place register | account | order.
		 * @return array
		 */
		public static function in( $place ) {
			return array_filter(
				self::all(),
				function ( $field ) use ( $place ) {
					return ! empty( $field['places'][ $place ] );
				}
			);
		}

		/**
		 * One field as WooCommerce form markup.
		 *
		 * @param array  $field Field.
		 * @param string $value Current value.
		 */
		protected static function render_field( $field, $value ) {
			$args = array(
				'type'        => $field['type'],
				'label'       => $field['label'],
				'required'    => ! empty( $field['required'] ),
				'placeholder' => $field['placeholder'],
				'description' => $field['help'],
				'class'       => array( 'form-row-wide', 'acfw-custom-field' ),
			);
			if ( in_array( $field['type'], array( 'select', 'radio' ), true ) ) {
				$args['options'] = array( '' => __( 'Choose…', 'my-account-dashboard-builder' ) ) + array_combine( $field['options'], $field['options'] );
				if ( 'radio' === $field['type'] ) {
					unset( $args['options'][''] );
				}
			}
			if ( 'checkbox' === $field['type'] ) {
				$args['label'] = $field['label'];
				$value         = 'yes' === $value ? 1 : 0;
			}
			woocommerce_form_field( self::META . $field['key'], $args, $value );
		}

		/**
		 * The posted value of a field, sanitized for its type.
		 *
		 * @param array $field Field.
		 * @return string
		 */
		protected static function posted( $field ) {
			$name = self::META . $field['key'];
			$raw  = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- WooCommerce checked the form's nonce; sanitized next.
			return acfw_field_sanitize_value( $field, $raw );
		}

		/**
		 * What was typed into a field, before cleaning ( for the checks ).
		 *
		 * @param array $field Field.
		 * @return string
		 */
		protected static function typed( $field ) {
			$name = self::META . $field['key'];
			return isset( $_POST[ $name ] ) && is_scalar( $_POST[ $name ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checked the form's nonce.
		}

		/**
		 * Check every field of a place, adding errors to $errors.
		 *
		 * @param string   $place  register | account.
		 * @param WP_Error $errors Errors.
		 */
		protected static function validate( $place, $errors ) {
			foreach ( self::in( $place ) as $field ) {
				$message = acfw_field_validate( $field, self::typed( $field ) );
				if ( '' !== $message ) {
					$errors->add( 'acfw_field_' . $field['key'], $message );
				}
			}
		}

		/**
		 * Save every field of a place for a user.
		 *
		 * @param string $place   register | account | profile.
		 * @param int    $user_id User ID.
		 */
		protected static function store( $place, $user_id ) {
			$fields = 'profile' === $place ? self::all() : self::in( $place );
			foreach ( $fields as $field ) {
				update_user_meta( $user_id, self::META . $field['key'], self::posted( $field ) );
			}
		}

		/**
		 * Registration form.
		 */
		public function register_form() {
			foreach ( self::in( 'register' ) as $field ) {
				self::render_field( $field, self::posted( $field ) );
			}
		}

		/**
		 * Registration checks.
		 *
		 * @param WP_Error $errors Errors.
		 * @return WP_Error
		 */
		public function register_errors( $errors ) {
			self::validate( 'register', $errors );
			return $errors;
		}

		/**
		 * Save on registration.
		 *
		 * @param int $customer_id New customer.
		 */
		public function register_save( $customer_id ) {
			self::store( 'register', $customer_id );
		}

		/**
		 * Account details form.
		 */
		public function account_form() {
			$uid = get_current_user_id();
			foreach ( self::in( 'account' ) as $field ) {
				self::render_field( $field, (string) get_user_meta( $uid, self::META . $field['key'], true ) );
			}
		}

		/**
		 * Account details checks.
		 *
		 * @param WP_Error $errors Errors ( by reference in WooCommerce ).
		 */
		public function account_errors( $errors ) {
			self::validate( 'account', $errors );
		}

		/**
		 * Save Account details.
		 *
		 * @param int $user_id User.
		 */
		public function account_save( $user_id ) {
			self::store( 'account', $user_id );
		}

		/**
		 * The customer's fields on their WordPress profile screen ( admins ).
		 *
		 * @param WP_User $user User.
		 */
		public function profile_fields( $user ) {
			if ( ! current_user_can( 'edit_user', $user->ID ) ) {
				return;
			}
			?>
			<h2><?php esc_html_e( 'Customer fields', 'my-account-dashboard-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php foreach ( self::all() as $field ) : ?>
					<?php
					$name  = self::META . $field['key'];
					$value = (string) get_user_meta( $user->ID, $name, true );
					?>
					<tr>
						<th><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
						<td>
							<?php if ( 'textarea' === $field['type'] ) : ?>
								<textarea name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" rows="3" class="regular-text"><?php echo esc_textarea( $value ); ?></textarea>
							<?php elseif ( in_array( $field['type'], array( 'select', 'radio' ), true ) ) : ?>
								<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>">
									<option value=""><?php esc_html_e( '—', 'my-account-dashboard-builder' ); ?></option>
									<?php foreach ( $field['options'] as $option ) : ?>
										<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $value, $option ); ?>><?php echo esc_html( $option ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php elseif ( 'checkbox' === $field['type'] ) : ?>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( 'yes', $value ); ?> />
							<?php else : ?>
								<input type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php
		}

		/**
		 * Save from the profile screen ( core checked the nonce ).
		 *
		 * @param int $user_id User.
		 */
		public function profile_save( $user_id ) {
			if ( current_user_can( 'edit_user', $user_id ) ) {
				self::store( 'profile', $user_id );
			}
		}

		/**
		 * The customer's fields on an order, under the billing address.
		 *
		 * @param WC_Order $order Order.
		 */
		public function order_fields( $order ) {
			$uid = $order ? (int) $order->get_customer_id() : 0;
			if ( ! $uid ) {
				return;
			}
			$rows = array();
			foreach ( self::in( 'order' ) as $field ) {
				$value = (string) get_user_meta( $uid, self::META . $field['key'], true );
				if ( '' !== $value ) {
					$rows[ $field['label'] ] = 'checkbox' === $field['type'] ? __( 'Yes', 'my-account-dashboard-builder' ) : $value;
				}
			}
			if ( ! $rows ) {
				return;
			}
			echo '<div class="acfw-order-fields">';
			foreach ( $rows as $label => $value ) {
				echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
			}
			echo '</div>';
		}

		/**
		 * {field_key} smart tags.
		 *
		 * @param string|null $value Value so far.
		 * @param string      $token Token.
		 * @param WP_User     $user  User.
		 * @return string|null
		 */
		public function smart_tag( $value, $token, $user ) {
			if ( null !== $value || 0 !== strpos( $token, '{field_' ) ) {
				return $value;
			}
			$key = substr( $token, 7, -1 );
			foreach ( self::all() as $field ) {
				if ( $field['key'] === $key ) {
					$uid = acfw_user_id( $user );
					$raw = $uid ? (string) get_user_meta( $uid, self::META . $key, true ) : '';
					return 'checkbox' === $field['type'] ? ( 'yes' === $raw ? __( 'Yes', 'my-account-dashboard-builder' ) : '' ) : $raw;
				}
			}
			return $value;
		}

		/**
		 * List the field tags in the smart tag menu.
		 *
		 * @param array $tags Token => label.
		 * @return array
		 */
		public function smart_tag_list( $tags ) {
			foreach ( self::all() as $field ) {
				$tags[ '{field_' . $field['key'] . '}' ] = $field['label'];
			}
			return $tags;
		}

		/**
		 * Privacy exporter registration.
		 *
		 * @param array $exporters Exporters.
		 * @return array
		 */
		public function register_exporter( $exporters ) {
			$exporters['acfw-fields'] = array(
				'exporter_friendly_name' => __( 'Customer fields', 'my-account-dashboard-builder' ),
				'callback'               => array( $this, 'export' ),
			);
			return $exporters;
		}

		/**
		 * Privacy eraser registration.
		 *
		 * @param array $erasers Erasers.
		 * @return array
		 */
		public function register_eraser( $erasers ) {
			$erasers['acfw-fields'] = array(
				'eraser_friendly_name' => __( 'Customer fields', 'my-account-dashboard-builder' ),
				'callback'             => array( $this, 'erase' ),
			);
			return $erasers;
		}

		/**
		 * Export a customer's field values.
		 *
		 * @param string $email Email.
		 * @return array
		 */
		public function export( $email ) {
			$user = get_user_by( 'email', $email );
			$data = array();
			if ( $user ) {
				foreach ( self::all() as $field ) {
					$value = (string) get_user_meta( $user->ID, self::META . $field['key'], true );
					if ( '' !== $value ) {
						$data[] = array(
							'name'  => $field['label'],
							'value' => $value,
						);
					}
				}
			}
			return array(
				'data' => $data ? array(
					array(
						'group_id'    => 'acfw-fields',
						'group_label' => __( 'Customer fields', 'my-account-dashboard-builder' ),
						'item_id'     => 'acfw-fields-' . ( $user ? $user->ID : 0 ),
						'data'        => $data,
					),
				) : array(),
				'done' => true,
			);
		}

		/**
		 * Erase a customer's field values.
		 *
		 * @param string $email Email.
		 * @return array
		 */
		public function erase( $email ) {
			$user    = get_user_by( 'email', $email );
			$removed = false;
			if ( $user ) {
				foreach ( self::all() as $field ) {
					$removed = delete_user_meta( $user->ID, self::META . $field['key'] ) || $removed;
				}
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
