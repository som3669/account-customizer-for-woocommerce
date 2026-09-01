<?php
/**
 * Recently Viewed endpoint: products the customer recently looked at.
 *
 * Override: yourtheme/my-account-customizer/recently-viewed.php
 *
 * @var array $products List of WC_Product, newest first.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $products ) ) {
	wc_print_notice( esc_html__( 'You have not viewed any products yet.', 'my-account-customizer' ), 'notice' );
	return;
}

global $product;
$acfw_saved_product = $product;
?>
<div class="acfw-recent acfw-cw-grid">
	<?php
	foreach ( $products as $acfw_item ) :
		$product = $acfw_item; // Set the loop global so WC's add-to-cart button works.
		?>
		<div class="acfw-cw-card">
			<a href="<?php echo esc_url( $acfw_item->get_permalink() ); ?>" class="acfw-cw-thumb"><?php echo wp_kses_post( $acfw_item->get_image( 'woocommerce_thumbnail' ) ); ?></a>
			<a href="<?php echo esc_url( $acfw_item->get_permalink() ); ?>" class="acfw-cw-name"><?php echo esc_html( $acfw_item->get_name() ); ?></a>
			<span class="acfw-cw-price"><?php echo wp_kses_post( $acfw_item->get_price_html() ); ?></span>
			<?php woocommerce_template_loop_add_to_cart(); ?>
		</div>
		<?php
	endforeach;
	$product = $acfw_saved_product;
	?>
</div>
