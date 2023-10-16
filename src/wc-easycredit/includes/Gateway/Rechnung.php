<?php
namespace Netzkollektiv\EasyCredit\Gateway;

class Rechnung extends GatewayAbstract
{
    public function _construct()
    {
        $this->id = 'easycredit-rechnung';

        $this->method_title = __('easyCredit Rechnung', 'wc-easycredit');
        $this->method_description = __('easyCredit Rechnung - Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.');
    }
}