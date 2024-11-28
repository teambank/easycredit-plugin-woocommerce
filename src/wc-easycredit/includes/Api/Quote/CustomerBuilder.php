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
            return $this->quote->get_address('billing')['first_name'];
        }
        return $this->customer->get_first_name();
    }

    public function getLastname()
    {
        if (!$this->isLoggedIn()) {
            return $this->quote->get_address('billing')['last_name'];
        }
        return $this->customer->get_last_name();
    }

    public function getEmail()
    {
        return $this->quote->get_address('billing')['email'];
    }

    public function getDob()
    {
        return null;
    }

    public function getCompany()
    {
        return $this->quote->get_address('billing')['company'];
    }

    public function getTelephone()
    {
        return $this->quote->get_billing_phone();
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
        \WC_Order $quote,
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
