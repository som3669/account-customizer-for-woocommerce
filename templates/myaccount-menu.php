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

$acfw_group_open = ! empty( $group_open );
?>
<button type="button" class="acfw-nav-toggle" aria-expanded="false" aria-controls="acfw-menu-list">
	<span class="dashicons dashicons-menu-alt"></span>
	<span class="acfw-nav-toggle-label"><?php esc_html_e( 'Account menu', 'account-customizer-for-woocommerce' ); ?></span>
</button>
<nav class="woocommerce-MyAccount-navigation <?php echo esc_attr( implode( ' ', $wrap_classes ) ); ?>">
	<span class="acfw-nav-backdrop"></span>
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
