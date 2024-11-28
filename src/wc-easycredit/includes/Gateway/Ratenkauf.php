<?php

namespace Netzkollektiv\EasyCredit\Gateway;

class Ratenkauf extends GatewayAbstract
{
    public $PAYMENT_TYPE = 'INSTALLMENT';

    public function _construct()
    {
        $this->id = 'easycredit_ratenkauf';

        add_action('init', [$this, 'init_translatable_props']);
        $this->method_title = 'easyCredit-Ratenkauf';
    }

    public function init_translatable_props() {
        $this->method_description = __('easyCredit-Ratenkauf - Join today to get the simplest way of partial payment for your POS and E-Commerce. easyCredit-Ratenkauf gives you the opportunity to offer installments as an additional payment method in your German WooCommerce store.','wc-easycredit');
        $this->order_button_text = __('Continue to pay by installments', 'wc-easycredit');
    }
}
