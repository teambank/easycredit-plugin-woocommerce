<?php

/** @var WC_Easycredit_Gateway_Abstract $easyCredit */
/** @var string $easyCreditWebshopId */
/** @var string $easyCreditError */
/** @var string $easyCreditAmount */
/** @var string $easyCreditPaymentType */

$id = esc_attr($easyCredit->id); ?>
<easycredit-checkout webshop-id="<?php echo $easyCreditWebshopId; ?>" payment-type="<?php echo $easyCreditPaymentType; ?>" alert="<?php echo $easyCreditError; ?>" amount="<?php echo $easyCreditAmount; ?>" is-active="true" />