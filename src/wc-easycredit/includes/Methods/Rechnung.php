<?php

namespace Netzkollektiv\EasyCredit\Methods;

defined('ABSPATH') || exit;

class Rechnung extends AbstractMethod
{
    protected $name = 'easycredit_rechnung';

    public function get_payment_method_data()
    {
        $data = parent::get_payment_method_data();
        $data['paymentType'] = 'BILL';
        return $data;
    }
}
