<?php
if (!defined('ABSPATH')) {
    exit;
}

use Teambank\RatenkaufByEasyCreditApiV3\ApiException;
use Teambank\RatenkaufByEasyCreditApiV3\Model\CaptureRequest;
use Teambank\RatenkaufByEasyCreditApiV3\Model\ConstraintViolation;
use Teambank\RatenkaufByEasyCreditApiV3\Model\RefundRequest;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class WC_Easycredit_Order_Management
{
    protected $_field = 'merchant-status';
    protected $plugin;
    protected $plugin_url;
    protected $gateway;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->plugin_url = $plugin->plugin_url;
        $this->gateway = $this->plugin->get_gateway();

        /* Wordpress Approach: HPOS disabled or older version */
        add_action('manage_shop_order_posts_custom_column', function($column, $order) {
            if ($column !== 'order_status') {
                return;
            }
            $this->add_order_column_content($column, $order);

        }, 20, 2);

        /* HPOS Approach */
        add_filter('woocommerce_shop_order_list_table_columns', function ($columns) {
            $columns['easycredit_status_icon'] = 'Zahlungsstatus';
            return $columns;
        });
        add_action('woocommerce_shop_order_list_table_custom_column', function ($column, $order) {
            if ('easycredit_status_icon' !== $column) {
                return;
            }
            $this->add_order_column_content($column, $order);
        }, 10, 2);

        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'add_status_after_shipping_address'], 10, 1);
        add_action('admin_enqueue_scripts', [$this, 'require_transaction_manager']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);

        foreach (['shipped', 'refunded'] as $state) {
            if ($this->gateway->get_option('mark_' . $state) == 'yes') {
                $status = $this->gateway->get_option('mark_' . $state . '_status');
                $status = str_replace('wc-', '', $status);

                add_action('woocommerce_order_status_' . $status, [$this, 'mark_' . $state], 10, 2);
            }
        }
    }

    public function get_field()
    {
        return $this->gateway->id . '-' . $this->_field;
    }

    public function get_endpoint_vars()
    {
        return [
            'endpoints' => [
                'get' => get_rest_url(null, 'easycredit/v1/transaction?ids={transactionId}'),
                'list' => get_rest_url(null, 'easycredit/v1/transactions?ids={transactionId}'),
                'capture' => get_rest_url(null, 'easycredit/v1/capture?id={transactionId}'),
                'refund' => get_rest_url(null, 'easycredit/v1/refund?id={transactionId}'),
            ],
            'request_config' => [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ],
        ];
    }

    public function require_transaction_manager()
    {
        wp_register_script('easycredit_transaction_manager', '');
        wp_enqueue_script('easycredit_transaction_manager');
        wp_add_inline_script('easycredit_transaction_manager', 'window.ratenkaufbyeasycreditOrderManagementConfig = ' . json_encode($this->get_endpoint_vars()));
    }

    public function add_status_after_shipping_address($order)
    {
        $content = $this->get_order_status_icon($order);
        if ($content) {
            echo $content;
        }
    }

    public function add_order_column_content($column, $order)
    {
        $order = $this->get_order($order);
        $content = $this->get_order_status_icon($order);
        if ($content) {
            echo $content;
        }
    }

    public function add_meta_boxes($post_type)
    {
        $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && 
            wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        if ($screen === 'shop_order' && $post_type !== 'shop_order') {
            return;
        }

        add_meta_box(
            'easycredit-merchant-status',
            __('Order Management', 'wc-easycredit'),
            [$this, 'add_order_management_meta_box'],
            $screen,
            'side',
            'core'
        );
    }

    public function add_order_management_meta_box($post = null)
    {
        $order = $this->get_order($post->ID);
        if ($order->get_payment_method() != $this->plugin->id) {
            return;
        }
        ?>
            <easycredit-merchant-manager 
                tx-id="<?php echo $order->get_meta($this->gateway->id . '-transaction-id'); ?>" 
                date="<?php echo $order->get_date_created()->format('Y-m-d'); ?>"    
            />
        <?php
    }

    public function get_order_status_icon($order)
    {
        if ($order->get_payment_method() !== $this->plugin->id) {
            return;
        }

        return '<easycredit-merchant-status-widget  
            tx-id="' . $order->get_meta($this->gateway->id . '-transaction-id') . '" 
            date="' . $order->get_date_created()->format('Y-m-d') . '" 
        ></easycredit-merchant-status-widget>';
    }

    public function mark_shipped($order_id, $order)
    {
        if ($this->gateway->id !== $order->get_payment_method()) {
            return;
        }

        try {
            try {
                $txId = $order->get_transaction_id();
                if (!$txId) {
                    throw new \Exception(__('The transaction id of this transaction is not available. This usually happens if the webhook which confirms the transaction is not working properly.', 'wc-easycredit'));
                }

                $this->gateway->get_merchant_client()
                    ->apiMerchantV3TransactionTransactionIdCapturePost(
                        $txId,
                        new CaptureRequest([])
                    );
                $order->add_order_note(__('Shipment automatically set in easyCredit-Ratenkauf', 'wc-easycredit'));
            } catch (ApiException $e) {
                if ($e->getResponseObject() instanceof ConstraintViolation) {
                    $error = 'easyCredit-Ratenkauf: ';
                    foreach ($e->getResponseObject()->getViolations() as $violation) {
                        $error .= $violation->getMessage();
                    }
                    throw new \Exception($error);
                }
                throw $e;
            }
        } catch (\Exception $e) {
            $order->add_order_note(sprintf(__('Shipment update failed with message: %s', 'wc-easycredit'), $e->getMessage()));
        }
    }

    public function mark_refunded($order_id, $order)
    {
        if ($this->gateway->id !== $order->get_payment_method()) {
            return;
        }

        try {
            try {
                $txId = $order->get_transaction_id();
                if (!$txId) {
                    throw new \Exception(__('The transaction id of this transaction is not available. This usually happens if the webhook which confirms the transaction is not working properly.', 'wc-easycredit'));
                }

                $this->gateway->get_merchant_client()
                    ->apiMerchantV3TransactionTransactionIdRefundPost(
                        $txId,
                        new RefundRequest([
                            'value' => $order->get_total(),
                        ])
                    );
                $order->add_order_note(__('Refund automatically set in easyCredit-Ratenkauf', 'wc-easycredit'));
            } catch (ApiException $e) {
                if ($e->getResponseObject() instanceof ConstraintViolation) {
                    $error = 'easyCredit-Ratenkauf: ';
                    foreach ($e->getResponseObject()->getViolations() as $violation) {
                        $error .= $violation->getMessage();
                    }
                    throw new \Exception($error);
                }
                throw $e;
            }
        } catch (\Exception $e) {
            $order->add_order_note(sprintf(__('Refund update failed with message: %s', 'wc-easycredit'), $e->getMessage()));
        }
    }

    protected function get_order($post = null)
    {
        if ($post === null) {
            global $post;
        }
        if ($post instanceof WP_Post) {
            $post = $post->ID;
        }
        return wc_get_order($post);
    }
}
