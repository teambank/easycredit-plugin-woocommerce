<?php

namespace Netzkollektiv\EasyCredit\Methods;

defined('ABSPATH') || exit;

class Rechnung extends AbstractMethod
{
    protected $name = 'easycredit_rechnung';

    public function __construct($plugin_file, $integration = null)
    {
        parent::__construct($plugin_file, $integration);
        $this->place_order_button_label = __('Continue to Bill Payment', 'wc-easycredit');
    }

    public function get_payment_method_data()
    {
        $data = parent::get_payment_method_data();
        $data['paymentType'] = 'BILL';
        return $data;
    }
}
