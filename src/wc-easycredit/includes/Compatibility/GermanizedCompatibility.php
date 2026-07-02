<?php

declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Compatibility;

use Netzkollektiv\EasyCredit\Plugin;

/**
 * Prevent Germanized from sending instant order confirmations (and reducing stock)
 * while the customer is still at easyCredit finishing financing.
 */
class GermanizedCompatibility
{
    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;

        add_filter('woocommerce_gzd_instant_order_confirmation', [$this, 'delay_until_payment_complete'], 10, 2);
        add_filter('woocommerce_germanized_send_instant_order_confirmation', [$this, 'delay_until_payment_complete'], 10, 2);
    }

    /**
     * @param bool $send_confirmation
     * @param \WC_Order|int|null $order
     */
    public function delay_until_payment_complete($send_confirmation, $order = null)
    {
        if (!$send_confirmation) {
            return $send_confirmation;
        }

        $order = $this->resolve_order($order);
        if (!$order || !$order->needs_payment()) {
            return $send_confirmation;
        }

        if ($this->plugin->is_easycredit_method($order->get_payment_method())) {
            return false;
        }

        return $send_confirmation;
    }

    /**
     * @param \WC_Order|int|null $order
     */
    private function resolve_order($order): ?\WC_Order
    {
        if ($order instanceof \WC_Order) {
            return $order;
        }

        if (is_numeric($order)) {
            $order = wc_get_order((int) $order);
            return $order instanceof \WC_Order ? $order : null;
        }

        return null;
    }
}
