<?php
/**
 * A single My Account menu item.
 *
 * Override: yourtheme/my-account-dashboard-builder/myaccount-menu-item.php
 *
 * @var string   $key        Item key.
 * @var array    $item       Item options.
 * @var string   $url        Resolved URL.
 * @var string   $target     Anchor target attribute (may be empty).
 * @var string   $classes    Space-separated CSS classes for the <li>.
 * @var int|null $count      Item count ( orders / downloads ), or null.
 * @var string   $badge      Custom badge text, shown instead of the count.
 * @var bool     $is_current Whether this item is the page being viewed.
 * @var bool     $pinnable   Whether customers can pin items to the top.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

$acfw_current = ! empty( $is_current ) || false !== strpos( $classes, 'is-active' );
?>
<li class="<?php echo esc_attr( $classes ); ?>">
	<a href="<?php echo esc_url( $url ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute string. ?><?php echo $acfw_current ? ' aria-current="page"' : ''; ?>>
		<?php
		echo acfw_icon_markup( $item['icon'] ?? '', $item['icon_url'] ?? '', 'acfw-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		?>
		<span class="acfw-label"><?php echo esc_html( $item['label'] ); ?></span>
		<?php if ( isset( $badge ) && '' !== (string) $badge ) : ?>
			<span class="acfw-count acfw-count-text"><?php echo esc_html( $badge ); ?></span>
		<?php elseif ( isset( $count ) && null !== $count ) : ?>
			<span class="acfw-count"><?php echo esc_html( $count ); ?></span>
		<?php endif; ?>
	</a>
	<?php
	if ( ! empty( $pinnable ) ) :
		/* translators: %s: menu item label. */
		$acfw_pin_label = sprintf( __( 'Pin %s to the top', 'my-account-dashboard-builder' ), $item['label'] );
		?>
		<button type="button" class="acfw-pin" data-key="<?php echo esc_attr( $key ); ?>" aria-pressed="false" aria-label="<?php echo esc_attr( $acfw_pin_label ); ?>"><span class="dashicons dashicons-star-empty" aria-hidden="true"></span></button>
	<?php endif; ?>
</li>
