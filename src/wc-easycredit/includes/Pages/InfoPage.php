<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Pages;

class InfoPage {

    const PAGE_ID = 'woocommerce_easycredit_infopage_page_id';

    public static function get_page_data()
    {
        return [
            self::PAGE_ID => [
                'name' => _x('easycredit-infopage', 'Page slug', 'woocommerce'),
                'title' => _x('easyCredit-Ratenkauf - Der einfachste Ratenkauf Deutschlands.', 'Page title', 'woocommerce'),
                'content' => '<easycredit-infopage></easycredit-infopage>',
            ],
        ];
    }
}