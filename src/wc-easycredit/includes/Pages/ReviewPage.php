<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Pages;


class ReviewPage
{

    const SHORT_CODE = 'woocommerce_easycredit_checkout_review';

    const PAGE_ID = 'woocommerce_easycredit_checkout_review_page_id';

    private $plugin;

    private $integration;

    private $expressCheckout;

    public function __construct(
        $plugin,
        $integration,
        $expressCheckout
    ) {
        $this->plugin = $plugin;
        $this->integration = $integration;
        $this->expressCheckout = $expressCheckout;

        if (!is_admin()) {
            add_action('template_redirect', [$this, 'payment_review_before']);
            add_shortcode(self::SHORT_CODE, [$this, 'payment_review']);

            add_action('woocommerce_easycredit_order_item_totals', [$this, 'order_item_totals']);
        }
    }

    public static function get_page_data()
    {
        return [
            self::PAGE_ID => [
                'name' => _x('easycredit-checkout-review', 'Page slug', 'woocommerce'),
                'title' => _x('Review Order', 'Page title', 'woocommerce'),
                'content' => '[' . self::SHORT_CODE . ']',
            ],
        ];
    }

    public function payment_review_before()
    {
        if ((int)get_option(self::PAGE_ID) === (int)get_queried_object_id()) {
            try {
                $this->integration->checkout()->loadTransaction();
            } catch (\Exception $e) {
                return $this->plugin->handleError($e->getMessage());
            }
        }
    }

    public function payment_review()
    {
        if (is_admin() || defined('REST_REQUEST')) {
            return;
        }

        $transaction = $this->integration->checkout()->loadTransaction();

        if ($this->integration->storage()->get('express')) {
            $this->expressCheckout->create_order($transaction);
        }

        $order = $this->plugin->get_current_order();
        if (!$order) {
            return;
        }

        $order->set_payment_method(
            $this->plugin->get_method_by_payment_type($transaction->getTransaction()->getPaymentType())
        );

        ob_start();
        $this->plugin->load_template('review-order', [
            'paymentType' => $this->plugin->get_payment_type_by_method($order->get_payment_method()),
            'summary' => $this->integration->storage()->get('summary'),
            'confirm_url' => $this->get_confirm_url(),
            'order' => $order
        ]);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    private function get_page_uri()
    {
        $pageId = get_option(self::PAGE_ID);
        return get_permalink($pageId);
    }

    private function get_confirm_url()
    {
        $query_args = [
            'woo-' . WC_EASYCREDIT_ID . '-return' => true,
        ];
        return add_query_arg($query_args, $this->get_page_uri());
    }

    public function order_item_totals($order)
    {
        $interest = $this->integration->storage()->get('interest_amount');

        $_totals = [];
        foreach ($order->get_order_item_totals() as $key => $total) {
            if ($key == 'payment_method') {
                continue;
            }
            if ($key == 'order_total') {
                $_totals['interest'] = [
                    'label' => __('Interest:', 'wc-easycredit'),
                    'value' => wc_price($interest, ['currency', $order->get_currency()]),
                ];
                $total['value'] = $this->get_total_including_interest($order);
            }
            $_totals[$key] = $total;
        }
        return $_totals;
    }

    protected function get_total_including_interest($order)
    {
        $interest = $this->integration->storage()->get('interest_amount');

        $total = $order->get_total();
        $order->set_total($total + $interest);
        $_total = $order->get_formatted_order_total();
        $order->set_total($total);

        return $_total;
    }
}
