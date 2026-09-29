<?php
/**
 * Address book: customers keep more addresses than WooCommerce's one billing
 * and one shipping address, on the Addresses page of My Account.
 *
 * Any saved address can become the default shipping or billing address, which
 * both the classic and the block checkout fill in. The classic checkout also
 * gets a "Saved addresses" picker above its billing and shipping forms.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Addresses' ) ) {

	/**
	 * Saved addresses.
	 */
	class ACFW_Addresses {

		/**
		 * User meta holding the book ( id => fields ).
		 *
		 * @var string
		 */
		const META = 'acfw_addresses';

		/**
		 * Form nonce action.
		 *
		 * @var string
		 */
		const NONCE = 'acfw_address';

		/**
		 * Most addresses one customer can keep.
		 *
		 * @var int
		 */
		const LIMIT = 20;

		/**
		 * Address fields kept ( WooCommerce's names without the prefix ).
		 *
		 * @var string[]
		 */
		const FIELDS = array( 'first_name', 'last_name', 'company', 'country', 'address_1', 'address_2', 'city', 'state', 'postcode', 'phone' );

		/**
		 * Hook the Addresses page and the checkout picker.
		 */
		public function __construct() {
			if ( ! self::enabled() ) {
				return;
			}
			add_action( 'woocommerce_account_edit-address_endpoint', array( $this, 'render' ), 20 );
			add_action( 'template_redirect', array( $this, 'handle' ) );
			add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'picker_billing' ) );
			add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'picker_shipping' ) );
			add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
			add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
		}

		/**
		 * Is the address book switched on?
		 *
		 * @return bool
		 */
		public static function enabled() {
			return 'yes' === get_option( 'acfw_addressbook_enable', 'no' );
		}

		/**
		 * A customer's saved addresses.
		 *
		 * @param int $user_id User.
		 * @return array id => fields ( with a label ).
		 */
		public static function all( $user_id ) {
			$book = get_user_meta( $user_id, self::META, true );
			return is_array( $book ) ? $book : array();
		}

		/**
		 * One address as the store prints it.
		 *
		 * @param array $address Fields.
		 * @return string HTML with line breaks.
		 */
		public static function formatted( $address ) {
			if ( ! function_exists( 'WC' ) ) {
				return '';
			}
			return (string) WC()->countries->get_formatted_address( array_intersect_key( $address, array_flip( self::FIELDS ) ) );
		}

		/**
		 * The Addresses page, below WooCommerce's two addresses.
		 *
		 * @param string $load_address billing | shipping while one is being edited.
		 */
		public function render( $load_address = '' ) {
			if ( '' !== (string) $load_address ) {
				return;
			}
			$uid     = get_current_user_id();
			$book    = self::all( $uid );
			$editing = isset( $_GET['acfw_address'] ) ? sanitize_key( wp_unslash( $_GET['acfw_address'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which address to edit.
			$base    = wc_get_account_endpoint_url( 'edit-address' );
			if ( wp_script_is( 'wc-country-select', 'registered' ) ) {
				wp_enqueue_script( 'wc-country-select' );
			}
			?>
			<section class="acfw-addressbook" id="acfw-addressbook">
				<h3><?php esc_html_e( 'More addresses', 'my-account-dashboard-builder' ); ?></h3>
				<p class="acfw-addressbook-intro"><?php esc_html_e( 'Keep the addresses you use often. Make one your shipping or billing address in a click; at checkout you can also pick one from the list.', 'my-account-dashboard-builder' ); ?></p>

				<?php if ( $book ) : ?>
					<div class="acfw-addressbook-grid">
						<?php foreach ( $book as $id => $address ) : ?>
							<article class="acfw-address-card">
								<h4><?php echo esc_html( $address['label'] ?? '' ); ?></h4>
								<address><?php echo wp_kses_post( self::formatted( $address ) ); ?></address>
								<div class="acfw-address-actions">
									<a href="<?php echo esc_url( add_query_arg( 'acfw_address', $id, $base ) . '#acfw-address-form' ); ?>"><?php esc_html_e( 'Edit', 'my-account-dashboard-builder' ); ?></a>
									<?php
									foreach (
										array(
											'use_shipping' => __( 'Use for shipping', 'my-account-dashboard-builder' ),
											'use_billing'  => __( 'Use for billing', 'my-account-dashboard-builder' ),
											'delete'       => __( 'Delete', 'my-account-dashboard-builder' ),
										) as $act => $act_label
									) :
										?>
										<form method="post">
											<?php wp_nonce_field( self::NONCE ); ?>
											<input type="hidden" name="acfw_address_id" value="<?php echo esc_attr( $id ); ?>" />
											<button type="submit" class="acfw-link-button" name="acfw_address_action" value="<?php echo esc_attr( $act ); ?>"<?php echo 'delete' === $act ? ' data-acfw-confirm="' . esc_attr__( 'Delete this address?', 'my-account-dashboard-builder' ) . '"' : ''; ?>><?php echo esc_html( $act_label ); ?></button>
										</form>
									<?php endforeach; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( 'new' === $editing || isset( $book[ $editing ] ) ) : ?>
					<?php $this->form( 'new' === $editing ? '' : $editing, $book[ $editing ] ?? array() ); ?>
				<?php elseif ( count( $book ) < self::LIMIT ) : ?>
					<p><a class="<?php echo esc_attr( acfw_button_class() ); ?>" href="<?php echo esc_url( add_query_arg( 'acfw_address', 'new', $base ) . '#acfw-address-form' ); ?>"><?php esc_html_e( 'Add an address', 'my-account-dashboard-builder' ); ?></a></p>
				<?php endif; ?>
			</section>
			<?php
		}

		/**
		 * The add / edit form ( WooCommerce's own address fields ).
		 *
		 * @param string $id      Address ID, '' for a new one.
		 * @param array  $address Current fields.
		 */
		protected function form( $id, $address ) {
			// After a failed save, show what was typed ( the nonce was checked in handle() ).
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST['acfw_address_action'] ) ) {
				$address['label'] = isset( $_POST['acfw_address_label'] ) ? sanitize_text_field( wp_unslash( $_POST['acfw_address_label'] ) ) : '';
				foreach ( self::FIELDS as $field ) {
					$address[ $field ] = isset( $_POST[ 'shipping_' . $field ] ) ? wc_clean( wp_unslash( $_POST[ 'shipping_' . $field ] ) ) : '';
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
			$country                  = ! empty( $address['country'] ) ? $address['country'] : WC()->countries->get_base_country();
			$fields                   = WC()->countries->get_address_fields( $country, 'shipping_' );
			$fields['shipping_phone'] = array(
				'label'    => __( 'Phone', 'my-account-dashboard-builder' ),
				'type'     => 'tel',
				'required' => false,
				'class'    => array( 'form-row-wide' ),
				'priority' => 100,
			);
			?>
			<form method="post" class="acfw-address-form" id="acfw-address-form">
				<h4><?php echo esc_html( $id ? __( 'Edit address', 'my-account-dashboard-builder' ) : __( 'New address', 'my-account-dashboard-builder' ) ); ?></h4>
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="acfw_address_id" value="<?php echo esc_attr( $id ); ?>" />
				<?php
				woocommerce_form_field(
					'acfw_address_label',
					array(
						'label'       => __( 'Name for this address', 'my-account-dashboard-builder' ),
						'placeholder' => __( 'e.g. Work, Mum’s house', 'my-account-dashboard-builder' ),
						'required'    => true,
						'class'       => array( 'form-row-wide' ),
					),
					$address['label'] ?? ''
				);
				?>
				<div class="woocommerce-address-fields">
					<div class="woocommerce-address-fields__field-wrapper">
						<?php foreach ( $fields as $key => $field ) : ?>
							<?php woocommerce_form_field( $key, $field, $address[ substr( $key, 9 ) ] ?? '' ); ?>
						<?php endforeach; ?>
					</div>
				</div>
				<p>
					<button type="submit" class="<?php echo esc_attr( acfw_button_class() ); ?>" name="acfw_address_action" value="save"><?php esc_html_e( 'Save address', 'my-account-dashboard-builder' ); ?></button>
					<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>"><?php esc_html_e( 'Cancel', 'my-account-dashboard-builder' ); ?></a>
				</p>
			</form>
			<?php
		}

		/**
		 * Save, delete or use an address.
		 */
		public function handle() {
			if ( empty( $_POST['acfw_address_action'] ) || ! is_user_logged_in() ) {
				return;
			}
			check_admin_referer( self::NONCE );
			$uid    = get_current_user_id();
			$book   = self::all( $uid );
			$action = sanitize_key( wp_unslash( $_POST['acfw_address_action'] ) );
			$id     = isset( $_POST['acfw_address_id'] ) ? sanitize_key( wp_unslash( $_POST['acfw_address_id'] ) ) : '';
			$back   = wc_get_account_endpoint_url( 'edit-address' );

			if ( 'save' === $action ) {
				$address = array( 'label' => isset( $_POST['acfw_address_label'] ) ? sanitize_text_field( wp_unslash( $_POST['acfw_address_label'] ) ) : '' );
				foreach ( self::FIELDS as $field ) {
					$address[ $field ] = isset( $_POST[ 'shipping_' . $field ] ) ? wc_clean( wp_unslash( $_POST[ 'shipping_' . $field ] ) ) : '';
				}
				$missing = array();
				foreach ( WC()->countries->get_address_fields( $address['country'], 'shipping_' ) as $key => $field ) {
					if ( ! empty( $field['required'] ) && '' === (string) ( $address[ substr( $key, 9 ) ] ?? '' ) ) {
						$missing[] = $field['label'];
					}
				}
				if ( '' === $address['label'] ) {
					array_unshift( $missing, __( 'Name for this address', 'my-account-dashboard-builder' ) );
				}
				if ( $missing ) {
					/* translators: %s: list of field labels. */
					wc_add_notice( sprintf( __( 'Please fill in: %s.', 'my-account-dashboard-builder' ), implode( ', ', $missing ) ), 'error' );
					return; // Show the form again with what was typed.
				}
				if ( '' === $id || ! isset( $book[ $id ] ) ) {
					if ( count( $book ) >= self::LIMIT ) {
						wc_add_notice( __( 'You have the most addresses you can keep. Delete one first.', 'my-account-dashboard-builder' ), 'error' );
						wp_safe_redirect( $back );
						exit;
					}
					$id = 'a' . strtolower( wp_generate_password( 8, false, false ) );
				}
				$book[ $id ] = $address;
				update_user_meta( $uid, self::META, $book );
				wc_add_notice( __( 'Address saved.', 'my-account-dashboard-builder' ), 'success' );
			} elseif ( isset( $book[ $id ] ) && 'delete' === $action ) {
				unset( $book[ $id ] );
				update_user_meta( $uid, self::META, $book );
				wc_add_notice( __( 'Address deleted.', 'my-account-dashboard-builder' ), 'success' );
			} elseif ( isset( $book[ $id ] ) && in_array( $action, array( 'use_shipping', 'use_billing' ), true ) ) {
				$type     = 'use_billing' === $action ? 'billing' : 'shipping';
				$customer = new WC_Customer( $uid );
				foreach ( self::FIELDS as $field ) {
					$setter = 'set_' . $type . '_' . $field;
					if ( is_callable( array( $customer, $setter ) ) ) {
						$customer->$setter( $book[ $id ][ $field ] ?? '' );
					}
				}
				$customer->save();
				wc_add_notice( 'billing' === $type ? __( 'That is now your billing address.', 'my-account-dashboard-builder' ) : __( 'That is now your shipping address.', 'my-account-dashboard-builder' ), 'success' );
			}
			wp_safe_redirect( $back );
			exit;
		}

		/**
		 * Picker above the checkout's billing form.
		 */
		public function picker_billing() {
			$this->picker( 'billing' );
		}

		/**
		 * Picker above the checkout's shipping form.
		 */
		public function picker_shipping() {
			$this->picker( 'shipping' );
		}

		/**
		 * "Saved addresses" on the classic checkout.
		 *
		 * @param string $type billing | shipping.
		 */
		protected function picker( $type ) {
			if ( ! is_user_logged_in() ) {
				return;
			}
			$book = self::all( get_current_user_id() );
			if ( ! $book ) {
				return;
			}
			$id = 'acfw_saved_' . $type;
			?>
			<p class="form-row form-row-wide acfw-address-picker">
				<label for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Saved addresses', 'my-account-dashboard-builder' ); ?></label>
				<select id="<?php echo esc_attr( $id ); ?>" data-acfw-prefix="<?php echo esc_attr( $type . '_' ); ?>">
					<option value=""><?php esc_html_e( 'Choose an address to fill in…', 'my-account-dashboard-builder' ); ?></option>
					<?php foreach ( $book as $address ) : ?>
						<option value="<?php echo esc_attr( wp_json_encode( array_intersect_key( $address, array_flip( self::FIELDS ) ) ) ); ?>"><?php echo esc_html( $address['label'] ?? '' ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<?php
			static $script = false;
			if ( ! $script ) {
				$script = true;
				wc_enqueue_js(
					"jQuery( document.body ).on( 'change', '.acfw-address-picker select', function () {
						var raw = this.value, prefix = jQuery( this ).data( 'acfw-prefix' );
						if ( ! raw ) { return; }
						var a = JSON.parse( raw );
						jQuery( '#' + prefix + 'country' ).val( a.country || '' ).trigger( 'change' );
						jQuery.each( a, function ( key, value ) {
							if ( 'country' !== key ) { jQuery( '#' + prefix + key ).val( value ).trigger( 'change' ); }
						} );
						jQuery( document.body ).trigger( 'update_checkout' );
					} );"
				);
			}
		}

		/**
		 * Privacy exporter registration.
		 *
		 * @param array $exporters Exporters.
		 * @return array
		 */
		public function register_exporter( $exporters ) {
			$exporters['acfw-addresses'] = array(
				'exporter_friendly_name' => __( 'Saved addresses', 'my-account-dashboard-builder' ),
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
			$erasers['acfw-addresses'] = array(
				'eraser_friendly_name' => __( 'Saved addresses', 'my-account-dashboard-builder' ),
				'callback'             => array( $this, 'erase' ),
			);
			return $erasers;
		}

		/**
		 * Export saved addresses.
		 *
		 * @param string $email Email.
		 * @return array
		 */
		public function export( $email ) {
			$user  = get_user_by( 'email', $email );
			$items = array();
			foreach ( $user ? self::all( $user->ID ) : array() as $id => $address ) {
				$items[] = array(
					'group_id'    => 'acfw-addresses',
					'group_label' => __( 'Saved addresses', 'my-account-dashboard-builder' ),
					'item_id'     => 'acfw-address-' . $id,
					'data'        => array(
						array(
							'name'  => $address['label'] ?? '',
							'value' => wp_strip_all_tags( str_replace( '<br/>', ', ', self::formatted( $address ) ) ),
						),
					),
				);
			}
			return array(
				'data' => $items,
				'done' => true,
			);
		}

		/**
		 * Erase saved addresses.
		 *
		 * @param string $email Email.
		 * @return array
		 */
		public function erase( $email ) {
			$user = get_user_by( 'email', $email );
			return array(
				'items_removed'  => $user ? delete_user_meta( $user->ID, self::META ) : false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
	}
}
