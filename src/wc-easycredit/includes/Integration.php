<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit;

use Teambank\EasyCreditApiV3 as ApiV3;

class Integration
{
    protected $plugin;

    protected $logger;

    protected $storage;

    protected $config;

    protected $checkout_validation;

    public function __construct(
        Plugin $plugin
    ) {
        $this->plugin = $plugin;
    }

    public function storage()
    {
        if ($this->storage == null) {
            if (function_exists('WC')) {
                if (function_exists('wc_load_cart') && null === WC()->cart) {
                    wc_load_cart();
                } elseif (null === WC()->session && method_exists(WC(), 'initialize_session')) {
                    WC()->initialize_session();
                }
            }

            $this->storage = new \Netzkollektiv\EasyCredit\Api\Storage(
                WC()->session,
                $this->logger()
            );
        }
        return $this->storage;
    }

    public function logger()
    {
        if ($this->logger == null) {
            $this->logger = new \Netzkollektiv\EasyCredit\Api\Logger($this->plugin);
        }
        return $this->logger;
    }

    public function config()
    {
        return ApiV3\Configuration::getDefaultConfiguration()
            ->setHost('https://ratenkauf.easycredit.de')
            ->setUsername($this->plugin->get_option('api_key'))
            ->setPassword($this->plugin->get_option('api_token'))
            ->setAccessToken($this->plugin->get_option('api_signature'));
    }

    public function checkout()
    {
        $logger = $this->logger();
        $config = $this->config();

        $client = new ApiV3\Client($logger);

        $webshopApi = new ApiV3\Service\WebshopApi(
            $client,
            $config
        );
        $transactionApi = new ApiV3\Service\TransactionApi(
            $client,
            $config
        );
        $installmentPlanApi = new ApiV3\Service\InstallmentplanApi(
            $client,
            $config
        );

        return new ApiV3\Integration\Checkout(
            $webshopApi,
            $transactionApi,
            $installmentPlanApi,
            $this->storage(),
            new ApiV3\Integration\Util\AddressValidator(),
            new ApiV3\Integration\Util\PrefixConverter(),
            $this->logger()
        );
    }

    public function checkout_validation()
    {
        if ($this->checkout_validation == null) {
            $this->checkout_validation = new \Netzkollektiv\EasyCredit\Api\CheckoutValidation($this);
        }

        return $this->checkout_validation;
    }

    public function quote_builder()
    {
        return new \Netzkollektiv\EasyCredit\Api\QuoteBuilder(
            $this->plugin,
            $this->storage()
        );
    }

    public function order_builder()
    {
        return new \Netzkollektiv\EasyCredit\Api\OrderBuilder(
            $this->plugin,
            $this->storage()
        );
    }

    public function merchant_client()
    {
        $logger = $this->logger();
        $config = $this->config()
            ->setHost('https://partner.easycredit-ratenkauf.de');
        $client = new ApiV3\Client($logger);

        return new ApiV3\Service\TransactionApi(
            $client,
            $config
        );
    }
}
