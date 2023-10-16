<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api\Quote;

class AddressBuilder
{
    protected $address = null;

    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    public function build($address)
    {
        $this->address['firstName'] = $address['first_name'];
        $this->address['lastName'] = $address['last_name'];
        $this->address['address'] = $address['address_1'];
        $this->address['additionalAddressInformation'] = isset($address['address_2']) && !empty($address['address_2']) ? $address['address_2'] : null;
        $this->address['zip'] = $address['postcode'];
        $this->address['city'] = $address['city'];
        $this->address['country'] = $address['country'];

        return $this->address;
    }
}
