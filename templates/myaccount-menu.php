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
);

if ( ! empty( $theme ) ) {
	$wrap_classes[] = 'acfw-theme-' . sanitize_html_class( $theme );
}
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
				$open_class = ! empty( $item['open'] ) ? ' is-open' : '';
				?>
				<li class="acfw-menu-item acfw-type-group<?php echo esc_attr( $open_class ); ?>">
					<span class="acfw-group-toggle">
						<?php if ( ! empty( $item['icon_url'] ) ) : ?>
							<img class="acfw-icon acfw-icon-img" src="<?php echo esc_url( $item['icon_url'] ); ?>" alt="" />
						<?php elseif ( ! empty( $item['icon'] ) ) : ?>
							<span class="acfw-icon dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
						<?php endif; ?>
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
