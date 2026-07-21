<?php
/**
 * A single My Account menu item.
 *
 * Override: yourtheme/account-customizer-for-woocommerce/myaccount-menu-item.php
 *
 * @var string $key     Item key.
 * @var array  $item    Item options.
 * @var string $url     Resolved URL.
 * @var string $target  Anchor target attribute (may be empty).
 * @var string $classes Space-separated CSS classes for the <li>.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;
?>
<li class="<?php echo esc_attr( $classes ); ?>">
	<a href="<?php echo esc_url( $url ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute string. ?>>
		<?php
		echo acfw_icon_markup( $item['icon'] ?? '', $item['icon_url'] ?? '', 'acfw-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		?>
		<span class="acfw-label"><?php echo esc_html( $item['label'] ); ?></span>
	</a>
</li>
