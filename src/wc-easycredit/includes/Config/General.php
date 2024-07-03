<?php

namespace Netzkollektiv\EasyCredit\Config;

use WC_Settings_API;

class General extends WC_Settings_API
{
    protected $fieldProvider;
    public $id = 'easycredit';

    public function __construct(
        FieldProvider $fieldProvider
    ) {
        $this->fieldProvider = $fieldProvider;

        $this->init_form_fields();
        if (is_admin()) {
            add_action(
                'woocommerce_update_options_checkout_' . $this->id,
                [$this, 'process_admin_options']
            );
        }
    }

    function init_form_fields()
    {
        $this->form_fields = $this->fieldProvider->get_fields_by_section('easycredit');
    }

    public function generate_marketingintro_html()
    {
        ob_start();
        include(dirname(__FILE__) . '/../../templates/template-marketing.php');
        $contents = ob_get_clean();

        return $contents;
    }

    public function generate_clickandcollectintro_html()
    {
        ob_start();
        include(dirname(__FILE__) . '/../../templates/template-click-and-collect.php');
        $contents = ob_get_clean();

        return $contents;
    }

    public function get_option($key, $empty_value = null)
    {
        $option = parent::get_option($key, $empty_value);
        if ($key == 'api_verify_credentials') {
            // always return default value for button
            return $this->get_field_default(
                $this->get_form_fields()[$key]
            );
        }
        return $option;
    }
    /*
    function admin_options()
    {
?>
        <h2><?php _e('easyCredit General Settings', 'woocommerce'); ?></h2>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table> <?php
    }
*/
    public function admin_options()
    {
        ob_start();
        parent::admin_options();
        $parent_options = ob_get_contents();
        ob_end_clean();

        $shipping_methods = '';
        foreach (WC()->shipping()->load_shipping_methods() as $method) {
            $selected = ($this->get_option('clickandcollect_shipping_method') == $method->id) ? 'selected="selected"' : '';
            $shipping_methods .= '<option value="' . $method->id . '" ' . $selected . '>' . $method->get_method_title() . '</option>';
        }

        $parent_options = preg_replace(
            '!(id="woocommerce_easycredit_clickandcollect_shipping_method".*?>)(.+?)(</select>)!s',
            '$1$2' . $shipping_methods . '$3',
            (string)$parent_options
        );

        $marketing_settings = [
            'express_checkout', 'widget', 'modal', 'card', 'flashbox', 'bar', 'clickandcollect'
        ];
        foreach ($marketing_settings as $marketing_setting) {
            preg_match(
                '!(<h3 class="wc-settings-sub-title " id="woocommerce_easycredit_marketing_components_' . $marketing_setting . '".*?>)(.+?)\K(<table class="form-table">)(.+?)(</table>)!s',
                (string)$parent_options,
                $html_extracted_matches
            );
            $parent_options = preg_replace(
                '!(<h3 class="wc-settings-sub-title " id="woocommerce_easycredit_marketing_components_' . $marketing_setting . '".*?>)(.+?)(<table class="form-table">)(.+?)(</table>)!s',
                '',
                (string)$parent_options
            );
            if (isset($html_extracted_matches[0])) {
                $parent_options = preg_replace(
                    '!(class="easycredit-marketing__content__settings settings-' . $marketing_setting . '".*?>)(.+?)(</div>)!s',
                    '$1' . $html_extracted_matches[0] . '$3',
                    (string)$parent_options
                );
            }
        }
?>
        <div class="easycredit-wrapper">
            <?php include(dirname(__FILE__) . '/../../templates/template-intro.php'); ?>
            <?php echo $parent_options; ?>
        </div>
<?php
    }
}
