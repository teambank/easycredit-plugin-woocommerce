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
        if (!$this->isLoggedIn()) {
            $address = $this->getBillingAddress();
            return $address['first_name'] ?? '';
        }
        return $this->customer->get_first_name();
    }

    public function getLastname()
    {
        if (!$this->isLoggedIn()) {
            $address = $this->getBillingAddress();
            return $address['last_name'] ?? '';
        }
        return $this->customer->get_last_name();
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
        $address = $this->getBillingAddress();
        return $address['company'] ?? '';
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
