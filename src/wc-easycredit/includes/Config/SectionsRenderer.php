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
            $paymentMethodSections[$paymentGateway->id] = $paymentGateway->get_method_title();
        }

        return array_merge([
            'easycredit' => __('General', 'woocommerce'),
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
            $html .= '<a href="' . esc_url($url) . '" class="nav-tab ' . ($current_section === $id ? 'nav-tab-active' : '') . '">' . esc_html($label) . '</a> ';
        }

        $html .= '</nav>';

        return $html;        
    }
}