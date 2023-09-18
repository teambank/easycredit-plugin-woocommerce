<?php
global $wpdb;
$wpdb->query("UPDATE ".$wpdb->prefix."options Set 
    option_name = 'woocommerce_easycredit_settings' 
    WHERE option_name = 'woocommerce_ratenkaufbyeasycredit_settings';
");
$wpdb->query("UPDATE ".$wpdb->prefix."posts Set 
    post_content = REPLACE(post_content, '[woocommerce_ratenkaufbyeasycredit_checkout_review]', '[woocommerce_easycredit_checkout_review]')
    WHERE post_content LIKE '%[woocommerce_ratenkaufbyeasycredit_checkout_review]%' AND post_status = 'publish';
");