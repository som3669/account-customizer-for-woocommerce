<?php
/**
 * Buy Again endpoint: previously purchased products with a reorder button.
 *
 * Override: yourtheme/my-account-customizer/buy-again.php
 *
 * @var array  $products List of array{ product, variation_id, quantity, order_id }.
 * @var string $nonce    Reorder AJAX nonce.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $products ) ) {
	wc_print_notice( esc_html__( 'You have no past orders to reorder from yet.', 'my-account-customizer' ), 'notice' );
	return;
}

$acfw_seen_orders = array();
?>
<div class="acfw-buyagain">
	<table class="acfw-buyagain-table shop_table">
		<thead>
			<tr>
				<th class="acfw-ba-product" colspan="2"><?php esc_html_e( 'Product', 'my-account-customizer' ); ?></th>
				<th class="acfw-ba-price"><?php esc_html_e( 'Price', 'my-account-customizer' ); ?></th>
				<th class="acfw-ba-action">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $products as $acfw_row ) : ?>
				<?php $acfw_product = $acfw_row['product']; ?>
				<tr>
					<td class="acfw-ba-thumb">
						<a href="<?php echo esc_url( $acfw_product->get_permalink() ); ?>"><?php echo wp_kses_post( $acfw_product->get_image( 'woocommerce_thumbnail' ) ); ?></a>
					</td>
					<td class="acfw-ba-name">
						<a href="<?php echo esc_url( $acfw_product->get_permalink() ); ?>"><?php echo esc_html( $acfw_product->get_name() ); ?></a>
					</td>
					<td class="acfw-ba-price"><?php echo wp_kses_post( $acfw_product->get_price_html() ); ?></td>
					<td class="acfw-ba-action">
						<button type="button" class="button acfw-reorder-btn"
							data-product="<?php echo esc_attr( $acfw_product->get_id() ); ?>"
							data-qty="<?php echo esc_attr( max( 1, (int) $acfw_row['quantity'] ) ); ?>"
							data-nonce="<?php echo esc_attr( $nonce ); ?>">
							<?php esc_html_e( 'Add to cart', 'my-account-customizer' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php
	// Offer a "reorder whole order" button for each distinct recent order.
	foreach ( $products as $acfw_row ) {
		$acfw_oid = (int) $acfw_row['order_id'];
		if ( isset( $acfw_seen_orders[ $acfw_oid ] ) ) {
			continue;
		}
		$acfw_seen_orders[ $acfw_oid ] = true;
	}
	if ( count( $acfw_seen_orders ) > 0 ) :
		$acfw_last_order = (int) array_key_first( $acfw_seen_orders );
		?>
		<div class="acfw-buyagain-bulk">
			<button type="button" class="button alt acfw-reorder-order-btn"
				data-order="<?php echo esc_attr( $acfw_last_order ); ?>"
				data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<?php esc_html_e( 'Reorder my last order', 'my-account-customizer' ); ?>
			</button>
		</div>
	<?php endif; ?>
</div>
