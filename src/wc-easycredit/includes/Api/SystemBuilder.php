<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

class SystemBuilder
{
    public function getSystemVendor()
    {
        global $wp_version;
        return 'Wordpress ' . $wp_version . ', wooCommerce';
    }

    public function getSystemVersion()
    {
        if (\class_exists('\WooCommerce')) {
            global $woocommerce;
            return $woocommerce->version;
        }
    }

    public function getModuleVersion()
    {
        return \WC_EASYCREDIT_VERSION;
    }

    public function build()
    {
        return new \Teambank\EasyCreditApiV3\Model\Shopsystem([
            'shopSystemManufacturer' => \implode(' ', [$this->getSystemVendor(), $this->getSystemVersion()]),
            'shopSystemModuleVersion' => $this->getModuleVersion(),
        ]);
    }
}
