<?php
$option_key = 'woocommerce_ratenkaufbyeasycredit_settings';
$option = get_option($option_key);
if (isset($option['title'])) {
    $option['title'] = str_ireplace('ratenkauf by easyCredit', 'easyCredit-Ratenkauf', $option['title']);
}
update_option($option_key, $option);