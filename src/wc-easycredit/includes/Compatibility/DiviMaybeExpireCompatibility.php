<?php

namespace Netzkollektiv\EasyCredit\Compatibility;

class DiviMaybeExpireCompatibility
{
    private const DIVI_ADDITIONAL_INFO_FRAME = 'ET_Builder_Module_Woocommerce_Checkout_Additional_Info::get_additional_info';

    /**
     * Divi compatibility: skip expiry while Divi renders checkout "Additional Info".
     * That render path can execute intermediate totals with shipping at zero.
     */
    public static function shouldSkipExpire()
    {
        $stackSummary = function_exists('wp_debug_backtrace_summary')
            ? wp_debug_backtrace_summary(null, 0, false)
            : 'n/a';

        if (is_array($stackSummary)) {
            return in_array(self::DIVI_ADDITIONAL_INFO_FRAME, $stackSummary, true);
        }

        return is_string($stackSummary)
            && strpos($stackSummary, self::DIVI_ADDITIONAL_INFO_FRAME) !== false;
    }
}
