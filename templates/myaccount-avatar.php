<?php
/**
 * My Account customer avatar block.
 *
 * Override: yourtheme/my-account-customizer/myaccount-avatar.php
 *
 * @var WP_User $user       Current user.
 * @var string  $avatar     Avatar <img> markup.
 * @var string  $shape      circle | square.
 * @var string  $align      left | center | right.
 * @var bool    $show_name    Show display name.
 * @var bool    $show_role    Show user role.
 * @var string  $role_label   Translated role label.
 * @var bool    $can_upload   Customer may upload their own picture.
 * @var bool    $has_uploaded Customer already has an uploaded picture.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

$acfw_can_upload   = ! empty( $can_upload );
$acfw_has_uploaded = ! empty( $has_uploaded );

$acfw_classes = array(
	'acfw-avatar-block',
	'align-' . sanitize_html_class( $align ),
	'shape-' . sanitize_html_class( $shape ),
);
if ( $acfw_can_upload ) {
	$acfw_classes[] = 'acfw-avatar-uploadable';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $acfw_classes ) ); ?>">
	<div class="acfw-avatar-img">
		<?php echo $avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() output. ?>
		<?php if ( $acfw_can_upload ) : ?>
			<button type="button" class="acfw-avatar-edit" aria-label="<?php esc_attr_e( 'Change profile picture', 'my-account-customizer' ); ?>">
				<span class="dashicons dashicons-camera"></span>
			</button>
			<span class="acfw-avatar-spinner" hidden></span>
		<?php endif; ?>
	</div>
	<?php if ( $acfw_can_upload ) : ?>
		<input type="file" class="acfw-avatar-file" accept="image/jpeg,image/png,image/gif,image/webp" hidden />
		<button type="button" class="acfw-avatar-remove"<?php echo $acfw_has_uploaded ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove picture', 'my-account-customizer' ); ?></button>
	<?php endif; ?>
	<?php if ( $show_name ) : ?>
		<div class="acfw-avatar-name"><?php echo esc_html( $user->display_name ); ?></div>
	<?php endif; ?>
	<?php if ( $show_role && '' !== $role_label ) : ?>
		<div class="acfw-avatar-role"><?php echo esc_html( $role_label ); ?></div>
	<?php endif; ?>
</div>
