<?php
namespace Netzkollektiv\EasyCredit\Config;

class SectionsRenderer {

    protected $configGeneralSection;

    protected $paymentGateways;

    protected $page_id;

    public function __construct (
        $configGeneralSection,
        array $paymentGateways
    ) {
        $this->configGeneralSection = $configGeneralSection;
        $this->paymentGateways = $paymentGateways;

        $this->add_section_tabs();

        add_action('woocommerce_settings_checkout', function () {
            echo $this->output();
        });
    }

    protected function add_section_tabs () {
        add_action(
            'woocommerce_sections_checkout', 
            function () {
                echo $this->render();
            },
            20
        );
    }

    protected function get_sections () {

        $paymentMethodSections = [];
        foreach ($this->paymentGateways as $paymentGateway) {
            $paymentMethodSections[$paymentGateway->id] = $paymentGateway->get_title();
        }

        return array_merge([
            'easycredit' => 'General',
        ], $paymentMethodSections);
    }

    protected function output () {
        global $current_section;
        if ($current_section === 'easycredit') {
            $this->configGeneralSection->admin_options();
        }
    }

    protected function render() {
        global $current_section;

        $sectionPrefix = 'easycredit';
        if (strncmp($current_section, $sectionPrefix, strlen($sectionPrefix)) !== 0) {
            return;
        }

        $html = '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">';

        foreach ($this->get_sections() as $id => $label) {
            $url = admin_url('admin.php?page=wc-settings&tab=checkout&section=' . (string) $id);
            /*if (in_array($id, array(Settings::CONNECTION_TAB_ID, CreditCardGateway::ID, Settings::PAY_LATER_TAB_ID), true)) {
                // We need section=ppcp-gateway for the webhooks page because it is not a gateway,
                // and for DCC because otherwise it will not render the page if gateway is not available (country/currency).
                // Other gateways render fields differently, and their pages are not expected to work when gateway is not available.
                $url = admin_url('admin.php?page=wc-settings&tab=checkout&section=ppcp-gateway&' . self::KEY . '=' . $id);
            }*/
            $html .= '<a href="' . esc_url($url) . '" class="nav-tab ' . ($current_section === $id ? 'nav-tab-active' : '') . '">' . esc_html($label) . '</a> ';
        }

        $html .= '</nav>';

        return $html;        
    }
}