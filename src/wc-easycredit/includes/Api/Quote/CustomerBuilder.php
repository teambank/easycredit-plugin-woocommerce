<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api\Quote;

class CustomerBuilder
{
    protected $quote;
    protected $customer;

    public function getPrefix(): ?string
    {
        return null;
    }

    public function getFirstname()
    {
        return $this->nameFromBillingAddress('first_name', 'get_first_name');
    }

    public function getLastname()
    {
        return $this->nameFromBillingAddress('last_name', 'get_last_name');
    }

    /**
     * Checkout billing name wins. WooCommerce only copies it onto the account
     * name when that is still empty, so a logged-in customer can pay under a
     * different name than the profile. Fall back to the account name when the
     * checkout address has none.
     */
    protected function nameFromBillingAddress(string $addressKey, string $accountGetter): string
    {
        $address = $this->getBillingAddress();
        $name = isset($address[$addressKey]) ? trim((string) $address[$addressKey]) : '';
        if ($name !== '') {
            return $name;
        }
        if ($this->isLoggedIn()) {
            return trim((string) $this->customer->{$accountGetter}());
        }

        return '';
    }

    public function getEmail()
    {
        $address = $this->getBillingAddress();
        return $address['email'] ?? '';
    }

    public function getDob()
    {
        return null;
    }

    public function getCompany()
    {
        $billing = $this->getBillingAddress();
        $company = trim((string) ($billing['company'] ?? ''));
        if ($company !== '') {
            return $company;
        }

        $shipping = $this->getShippingAddress();

        return $shipping['company'] ?? '';
    }

    protected function getShippingAddress()
    {
        if ($this->quote instanceof \WC_Order) {
            $address = $this->quote->get_address('shipping');
            if (!empty(\array_filter($address))) {
                return $address;
            }

            return $this->quote->get_address('billing');
        } elseif ($this->quote instanceof \WC_Cart) {
            if (WC()->customer) {
                $address = WC()->customer->get_shipping();
                if (!empty(\array_filter($address))) {
                    return $address;
                }
            }
            if ($this->isLoggedIn()) {
                $address = $this->customer->get_shipping();
                if (!empty(\array_filter($address))) {
                    return $address;
                }
            }
        }

        return [];
    }

    public function getTelephone()
    {
        if ($this->quote instanceof \WC_Order) {
            return $this->quote->get_billing_phone();
        }
        $address = $this->getBillingAddress();
        return $address['phone'] ?? '';
    }

    protected function getBillingAddress()
    {
        if ($this->quote instanceof \WC_Order) {
            return $this->quote->get_address('billing');
        } elseif ($this->quote instanceof \WC_Cart) {
            // Use WC()->customer which has the current checkout values set via set_props()
            if (WC()->customer) {
                $address = WC()->customer->get_billing();
                if (!empty(\array_filter($address))) {
                    return $address;
                }
            }
            // Fallback to stored customer data if logged in
            if ($this->isLoggedIn()) {
                return $this->customer->get_billing();
            }
        }
        return [];
    }

    public function isLoggedIn()
    {
        return ($this->customer !== false && $this->customer->get_id());
    }

    public function getCreatedAt()
    {
        return (string)$this->customer->get_date_created();
    }

    public function build(
        $quote,
        $customer
    ) {
        $this->quote = $quote;
        $this->customer = $customer;

        return new \Teambank\EasyCreditApiV3\Model\Customer(\array_filter([
            'gender' => $this->getPrefix(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'birthDate' => $this->getDob(),
            'companyName' => $this->getCompany(),
            'contact' => ($this->getEmail()) ? new \Teambank\EasyCreditApiV3\Model\Contact([
                'email' => $this->getEmail(),
            ]) : null,
        ]));
    }
}
