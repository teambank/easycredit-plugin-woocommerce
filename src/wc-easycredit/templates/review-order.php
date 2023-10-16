<?php
/** @var WC_Order $order */
/** @var WC_Easycredit_Gateway_Abstract $gateway */

if (!defined('ABSPATH')) {
    exit;
}
?>

<p><?php _e('Please review and confirm your order', 'wc-easycredit'); ?></p>

<div class="easycredit-review-container">
	<?php wc_get_template('order/order-details-customer.php', [
	    'order' => $order,
	]); ?>

	<section class="woocommerce-payment-details easycredit-payment-details">

			<section class="woocommerce-columns woocommerce-columns--2 col2-set addresses">
				<div class="woocommerce-column woocommerce-column--1 col-1">

					<h2 class="woocommerce-column__title">
						<?php _e(
						    'Payment Method',
						    'wc-easycredit'
						);
					?>
					</h2>
					<easycredit-checkout-label>
					</easycredit-checkout-label>
					<easycredit-checkout payment-plan="<?php echo htmlspecialchars($gateway->get_storage()->get('summary')); ?>">
					</easycredit-checkout>
				</div>

			</section>
	</section>
</div>

<?php $order_items = $order->get_items(apply_filters('woocommerce_purchase_order_item_types', 'line_item')); ?>
<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">

	<thead>
		<tr>
			<th class="woocommerce-table__product-name product-name"><?php _e('Product', 'woocommerce'); ?></th>
			<th class="woocommerce-table__product-table product-total"><?php _e('Total', 'woocommerce'); ?></th>
		</tr>
	</thead>

	<tbody>
		<?php
            foreach ($order_items as $item_id => $item) {
                $product = is_callable([$item, 'get_product']) ? $item->get_product() : false;
                $product = apply_filters('woocommerce_order_item_product', $product, $item);

                wc_get_template('order/order-details-item.php', [
                    'order' => $order,
                    'item_id' => $item_id,
                    'item' => $item,
                    'show_purchase_note' => 0,
                    'purchase_note' => $product ? $product->get_purchase_note() : '',
                    'product' => $product,
                ]);
            }
            ?>
		<?php do_action('woocommerce_order_items_table', $order); ?>
	</tbody>

	<tfoot>
		<?php
    	$totals = apply_filters(
        	'woocommerce_easycredit_order_item_totals',
        	$order
    	);
		foreach ($totals as $key => $total) : ?>
            <tr>
                <th scope="row"><?php echo $total['label']; ?></th>
                <td><?php echo $total['value']; ?></td>
            </tr>
		<?php endforeach; ?>
		<?php if ($order->get_customer_note()) : ?>
			<tr>
				<th><?php _e('Note:', 'woocommerce'); ?></th>
				<td><?php echo wptexturize($order->get_customer_note()); ?></td>
			</tr>
		<?php endif; ?>
	</tfoot>
</table>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url($gateway->get_confirm_url()); ?>" enctype="multipart/form-data">
	<div class="easycredit checkout-review-button">

        <?php wc_get_template('checkout/terms.php'); ?>
	
		<?php do_action('woocommerce_pay_order_before_submit'); ?>
		
		<?php $order_button_text = apply_filters(
		    'woocommerce_pay_order_button_text',
		    __('Place order', 'woocommerce')
		); ?>
	
		<?php echo apply_filters('woocommerce_pay_order_button_html', '<input name="woo-' . $gateway->id . '-confirm" type="submit" class="button alt" id="place_order" value="' . esc_attr($order_button_text) . '" data-value="' . esc_attr($order_button_text) . '" />'); ?>
	
		<?php do_action('woocommerce_pay_order_after_submit'); ?>
		<?php wp_nonce_field('woocommerce-easycredit-pay'); ?>
	</div>
</form>
