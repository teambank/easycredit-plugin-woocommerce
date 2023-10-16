<?php

namespace Netzkollektiv\EasyCredit\Config;

use WC_Settings_API;

class General extends WC_Settings_API
{
    protected $fieldProvider;

    public function __construct(
        FieldProvider $fieldProvider
    ) {
        $this->fieldProvider = $fieldProvider;

        $this->init_form_fields();
    }

    function init_form_fields()
    {
        $this->form_fields = $this->fieldProvider->get_fields_by_section('easycredit');
    }

    function admin_options()
    {
?>
        <h2><?php _e('easyCredit General Settings', 'woocommerce'); ?></h2>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table> <?php
    }
}
