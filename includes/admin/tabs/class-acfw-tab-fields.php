<?php
/**
 * Fields tab: custom customer fields.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Tab_Fields' ) ) {

	/**
	 * Define the fields customers fill in.
	 */
	class ACFW_Tab_Fields extends ACFW_Admin_Tab {

		/**
		 * Save the field list.
		 *
		 * @param string     $action Action slug.
		 * @param ACFW_Items $items  Menu items manager.
		 */
		public function handle( $action, $items ) {
			if ( 'save_fields' !== $action ) {
				return;
			}
			// Nonce checked in ACFW_Admin::handle_actions(); each value is sanitized in acfw_fields_sanitize_definitions().
			$raw = isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
			update_option( ACFW_Fields::OPTION, acfw_fields_sanitize_definitions( array_values( $raw ) ) );
		}

		/**
		 * Render the tab.
		 */
		public function render() {
			$fields = ACFW_Fields::all();
			$blank  = array(
				'key'         => '',
				'label'       => '',
				'type'        => 'text',
				'required'    => false,
				'placeholder' => '',
				'help'        => '',
				'options'     => array(),
				'places'      => array(
					'register' => true,
					'account'  => true,
					'order'    => true,
				),
			);
			?>
			<form method="post" class="acfw-fields-form acfw-items-form" novalidate>
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="acfw_action" value="save_fields" />

				<section class="acfw-card acfw-fields-head">
					<h2><?php esc_html_e( 'Customer fields', 'my-account-dashboard-builder' ); ?></h2>
					<p><?php esc_html_e( 'Ask for more when customers register or edit their account: a phone number, a company or VAT number, a birthday. Answers are kept on the customer, shown on their profile and their orders, and included when they ask for a copy of their data.', 'my-account-dashboard-builder' ); ?></p>
				</section>

				<div class="acfw-fields-list" data-next="<?php echo esc_attr( count( $fields ) ); ?>">
					<?php
					foreach ( $fields as $i => $field ) {
						$this->card( (string) $i, $field, false );
					}
					?>
				</div>
				<p class="acfw-fields-empty"<?php echo $fields ? ' hidden' : ''; ?>><?php esc_html_e( 'No fields yet. Add the first one below.', 'my-account-dashboard-builder' ); ?></p>

				<button type="button" class="acfw-fields-add acfw-canvas-add-end">
					<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
					<?php esc_html_e( 'Add a field', 'my-account-dashboard-builder' ); ?>
				</button>

				<template id="acfw-field-template"><?php $this->card( '__i__', $blank, true ); ?></template>

				<div class="acfw-form-footer acfw-savebar">
					<span class="acfw-savebar-status" role="status"><span class="acfw-savebar-dirty" hidden><?php esc_html_e( 'Unsaved changes', 'my-account-dashboard-builder' ); ?></span></span>
					<button type="submit" class="button button-primary acfw-save-all"><span class="dashicons dashicons-saved" aria-hidden="true"></span> <?php esc_html_e( 'Save fields', 'my-account-dashboard-builder' ); ?></button>
				</div>
			</form>
			<?php
		}

		/**
		 * One field's editor card.
		 *
		 * @param string $i     Index in the posted list.
		 * @param array  $field Field.
		 * @param bool   $fresh A field being added ( its key follows its label ).
		 */
		protected function card( $i, $field, $fresh ) {
			$name = 'fields[' . $i . ']';
			$uid  = 'acfw-f' . $i;
			?>
			<div class="acfw-card acfw-field-card"<?php echo $fresh ? ' data-fresh="1"' : ''; ?>>
				<div class="acfw-field-card-head">
					<span class="acfw-field-drag dashicons dashicons-move" title="<?php esc_attr_e( 'Drag to reorder', 'my-account-dashboard-builder' ); ?>" aria-hidden="true"></span>
					<strong class="acfw-field-card-title"><?php echo esc_html( '' !== $field['label'] ? $field['label'] : __( 'New field', 'my-account-dashboard-builder' ) ); ?></strong>
					<code class="acfw-field-card-tag"><?php echo esc_html( '' !== $field['key'] ? '{field_' . $field['key'] . '}' : '' ); ?></code>
					<button type="button" class="acfw-icon-btn acfw-node-remove acfw-field-remove" title="<?php esc_attr_e( 'Remove this field', 'my-account-dashboard-builder' ); ?>" aria-label="<?php esc_attr_e( 'Remove this field', 'my-account-dashboard-builder' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
				</div>
				<div class="acfw-field-card-grid">
					<label class="acfw-fc">
						<span><?php esc_html_e( 'Label', 'my-account-dashboard-builder' ); ?></span>
						<input type="text" class="acfw-fc-label" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( $field['label'] ); ?>" required />
					</label>
					<label class="acfw-fc">
						<span><?php esc_html_e( 'Key', 'my-account-dashboard-builder' ); ?></span>
						<input type="text" class="acfw-fc-key" name="<?php echo esc_attr( $name ); ?>[key]" value="<?php echo esc_attr( $field['key'] ); ?>" pattern="[a-z0-9_]+" spellcheck="false" autocomplete="off" />
					</label>
					<label class="acfw-fc">
						<span><?php esc_html_e( 'Type', 'my-account-dashboard-builder' ); ?></span>
						<select class="acfw-fc-type" name="<?php echo esc_attr( $name ); ?>[type]">
							<?php foreach ( ACFW_Fields::types() as $type => $type_label ) : ?>
								<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $field['type'], $type ); ?>><?php echo esc_html( $type_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="acfw-fc acfw-fc-inline">
						<span class="acfw-switch"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[required]" value="1" <?php checked( ! empty( $field['required'] ) ); ?> /><span class="acfw-switch-slider"></span></span>
						<span><?php esc_html_e( 'Required', 'my-account-dashboard-builder' ); ?></span>
					</label>
					<label class="acfw-fc">
						<span><?php esc_html_e( 'Placeholder', 'my-account-dashboard-builder' ); ?></span>
						<input type="text" name="<?php echo esc_attr( $name ); ?>[placeholder]" value="<?php echo esc_attr( $field['placeholder'] ); ?>" />
					</label>
					<label class="acfw-fc">
						<span><?php esc_html_e( 'Help text', 'my-account-dashboard-builder' ); ?></span>
						<input type="text" name="<?php echo esc_attr( $name ); ?>[help]" value="<?php echo esc_attr( $field['help'] ); ?>" />
					</label>
					<label class="acfw-fc acfw-fc-wide acfw-fc-options"<?php echo in_array( $field['type'], array( 'select', 'radio' ), true ) ? '' : ' hidden'; ?>>
						<span><?php esc_html_e( 'Options, one per line', 'my-account-dashboard-builder' ); ?></span>
						<textarea name="<?php echo esc_attr( $name ); ?>[options]" rows="3"><?php echo esc_textarea( implode( "\n", $field['options'] ) ); ?></textarea>
					</label>
					<fieldset class="acfw-fc acfw-fc-wide acfw-fc-places">
						<legend><?php esc_html_e( 'Show it on', 'my-account-dashboard-builder' ); ?></legend>
						<?php
						$places = array(
							'register' => __( 'Registration form', 'my-account-dashboard-builder' ),
							'account'  => __( 'Account details', 'my-account-dashboard-builder' ),
							'order'    => __( 'The customer’s orders (admin)', 'my-account-dashboard-builder' ),
						);
						foreach ( $places as $place => $place_label ) :
							?>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[places][<?php echo esc_attr( $place ); ?>]" value="1" <?php checked( ! empty( $field['places'][ $place ] ) ); ?> /> <?php echo esc_html( $place_label ); ?></label>
						<?php endforeach; ?>
					</fieldset>
				</div>
			</div>
			<?php
		}
	}
}
