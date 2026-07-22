<?php
/**
 * My Account custom menu.
 *
 * Override: yourtheme/account-customizer-for-woocommerce/myaccount-menu.php
 *
 * @var array         $items    Visible menu items.
 * @var string        $current  Current endpoint key.
 * @var string        $position Menu position.
 * @var string        $layout   Menu layout.
 * @var ACFW_Frontend $frontend Frontend handler.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

$wrap_classes = array(
	'acfw-menu',
	'position-' . sanitize_html_class( $position ),
	'layout-' . sanitize_html_class( $layout ),
	'acfw-preset-' . sanitize_html_class( ! empty( $preset ) ? $preset : 'flat' ),
);

if ( ! empty( $theme ) ) {
	$wrap_classes[] = 'acfw-theme-' . sanitize_html_class( $theme );
}

if ( empty( $show_icons ) ) {
	$wrap_classes[] = 'acfw-hide-icons';
}
if ( ! empty( $sticky ) ) {
	$wrap_classes[] = 'acfw-sticky';
}
$wrap_classes[] = 'acfw-ind-' . sanitize_html_class( ! empty( $indicator ) ? $indicator : 'bar' );
$wrap_classes[] = 'acfw-anim-' . sanitize_html_class( ! empty( $anim ) ? $anim : 'none' );
$wrap_classes[] = 'acfw-scheme-' . sanitize_html_class( ! empty( $scheme ) ? $scheme : 'auto' );
if ( ! empty( $collapsible ) ) {
	$wrap_classes[] = 'acfw-collapsible';
}
if ( ! empty( $pinnable ) ) {
	$wrap_classes[] = 'acfw-pinnable';
}

$acfw_group_open = ! empty( $group_open );
?>
<button type="button" class="acfw-nav-toggle" aria-expanded="false" aria-controls="acfw-menu-list">
	<span class="dashicons dashicons-menu-alt"></span>
	<span class="acfw-nav-toggle-label"><?php esc_html_e( 'Account menu', 'account-customizer-for-woocommerce' ); ?></span>
</button>
<nav class="woocommerce-MyAccount-navigation <?php echo esc_attr( implode( ' ', $wrap_classes ) ); ?>">
	<span class="acfw-nav-backdrop"></span>
	<?php if ( ! empty( $collapsible ) ) : ?>
		<button type="button" class="acfw-collapse-toggle" aria-label="<?php esc_attr_e( 'Collapse menu', 'account-customizer-for-woocommerce' ); ?>"><span class="dashicons dashicons-arrow-left-alt2"></span></button>
	<?php endif; ?>
	<?php if ( ! empty( $search ) ) : ?>
		<input type="search" class="acfw-menu-search" placeholder="<?php esc_attr_e( 'Search…', 'account-customizer-for-woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Search menu', 'account-customizer-for-woocommerce' ); ?>" />
	<?php endif; ?>
	<ul id="acfw-menu-list">
		<?php
		foreach ( $items as $key => $item ) :

			$type = isset( $item['type'] ) ? $item['type'] : 'endpoint';

			if ( 'group' === $type ) :
				$open_class = ( ! empty( $item['open'] ) || $acfw_group_open ) ? ' is-open' : '';
				?>
				<li class="acfw-menu-item acfw-type-group<?php echo esc_attr( $open_class ); ?>">
					<span class="acfw-group-toggle">
						<?php echo acfw_icon_markup( $item['icon'] ?? '', $item['icon_url'] ?? '', 'acfw-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php echo esc_html( $item['label'] ); ?>
					</span>
					<?php if ( ! empty( $item['children'] ) ) : ?>
						<ul class="acfw-submenu">
							<?php
							foreach ( $item['children'] as $child_key => $child_item ) {
								$frontend->render_item( $child_key, $child_item );
							}
							?>
						</ul>
					<?php endif; ?>
				</li>
				<?php
			else :
				$frontend->render_item( $key, $item );
			endif;

		endforeach;
		?>
	</ul>
</nav>
