<?php

namespace Netzkollektiv\EasyCredit\Methods;

defined('ABSPATH') || exit;

class Ratenkauf extends AbstractMethod
{
    protected $name = 'easycredit_ratenkauf';

    public function get_payment_method_data()
    {
        $data = parent::get_payment_method_data();
        $data['paymentType'] = 'INSTALLMENT';
        return $data;
    }
}
