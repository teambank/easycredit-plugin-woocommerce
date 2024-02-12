<?php
namespace Netzkollektiv\EasyCredit\Gateway;

class Ratenkauf extends GatewayAbstract
{
    const PAYMENT_TYPE = 'INSTALLMENT_PAYMENT';

    public function _construct()
    {
        $this->id = 'easycredit-ratenkauf';

        $this->method_title = __('easyCredit-Ratenkauf', 'wc-easycredit');
        $this->method_description = __('easyCredit-Ratenkauf - jetzt die einfachste Teilzahlungslösung Deutschlands nutzen. Unser Credo einfach, fair und sicher gilt sowohl für Ratenkaufkunden als auch für Händler. Der schnelle, einfache und medienbruchfreie Prozess mit sofortiger Online-Bonitätsprüfung lässt sich sicher in den Onlineshop integrieren. Wir übernehmen das Ausfallrisiko und Sie können Ihren Umsatz bereits nach drei Tagen verbuchen.');

        $this->title = sprintf('<span class="gateway-subtitle">%s</span>', 'wc-easycredit');
        $this->order_button_text = __('Continue to pay by installments', 'wc-easycredit');
    }
}
