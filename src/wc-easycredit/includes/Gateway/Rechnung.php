<?php

namespace Netzkollektiv\EasyCredit\Gateway;

class Rechnung extends GatewayAbstract
{
    public $PAYMENT_TYPE = 'BILL';

    public function _construct()
    {
        $this->id = 'easycredit_rechnung';
        $this->method_title = 'easyCredit-Rechnung';
        $this->method_description = $this->method_title . ' - With easyCredit, you can now offer your customers the option of invoice purchase in addition to the classic installment purchase. The payment term is <strong>30 days in the future</strong>.';

        add_action('init', [$this, 'init_translatable_props']);
        add_action('wp', [$this, 'init_runtime_props']);
    }

    public function init_translatable_props()
    {
        $this->method_description = $this->method_title . ' - ' . __('With easyCredit, you can now offer your customers the option of invoice purchase in addition to the classic installment purchase. The payment term is <strong>30 days in the future</strong>.', 'wc-easycredit');
    }

    public function init_runtime_props()
    {
        if ($this->integration->storage()->get('summary') === null) {
            $this->order_button_text = __('Continue to Bill Payment', 'wc-easycredit');
        }
    }
}
