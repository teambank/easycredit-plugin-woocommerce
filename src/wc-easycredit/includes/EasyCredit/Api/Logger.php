<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

class Logger
{
    protected $_gateway;

    public function __construct(\WC_Settings_API $gateway)
    {
        $this->_gateway = $gateway;
    }

    public function __call(string $name, array $arguments)
    {
        if (
            $this->_gateway->get_option('debug') != 'yes' &&
            $name === 'debug'
        ) {
            return;
        }
        \wc_get_logger()->{$name}($arguments[0], [
            'source' => $this->_gateway->id,
        ]);
    }
}
