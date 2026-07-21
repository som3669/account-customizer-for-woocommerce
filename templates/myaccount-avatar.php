<?php
/**
 * My Account customer avatar block.
 *
 * Override: yourtheme/account-customizer-for-woocommerce/myaccount-avatar.php
 *
 * @var WP_User $user       Current user.
 * @var string  $avatar     Avatar <img> markup.
 * @var string  $shape      circle | square.
 * @var string  $align      left | center | right.
 * @var bool    $show_name  Show display name.
 * @var bool    $show_role  Show user role.
 * @var string  $role_label Translated role label.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

$acfw_classes = array(
	'acfw-avatar-block',
	'align-' . sanitize_html_class( $align ),
	'shape-' . sanitize_html_class( $shape ),
);
?>
<div class="<?php echo esc_attr( implode( ' ', $acfw_classes ) ); ?>">
	<div class="acfw-avatar-img">
		<?php echo $avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() output. ?>
	</div>
	<?php if ( $show_name ) : ?>
		<div class="acfw-avatar-name"><?php echo esc_html( $user->display_name ); ?></div>
	<?php endif; ?>
	<?php if ( $show_role && '' !== $role_label ) : ?>
		<div class="acfw-avatar-role"><?php echo esc_html( $role_label ); ?></div>
	<?php endif; ?>
</div>
