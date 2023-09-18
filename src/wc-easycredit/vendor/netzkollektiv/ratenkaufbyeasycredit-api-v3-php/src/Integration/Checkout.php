<?php
namespace Teambank\RatenkaufByEasyCreditApiV3\Integration;

use Teambank\RatenkaufByEasyCreditApiV3\Service\TransactionApi;
use Teambank\RatenkaufByEasyCreditApiV3\Service\WebshopApi;
use Teambank\RatenkaufByEasyCreditApiV3\Service\InstallmentplanApi;
use Teambank\RatenkaufByEasyCreditApiV3\Integration\Storage;
use Teambank\RatenkaufByEasyCreditApiV3\Integration\CheckoutInterface;
use Teambank\RatenkaufByEasyCreditApiV3\Integration\Util\AddressValidator;
use Teambank\RatenkaufByEasyCreditApiV3\Integration\Util\PrefixConverter;
use Teambank\RatenkaufByEasyCreditApiV3\ApiException;
use Teambank\RatenkaufByEasyCreditApiV3\Integration\InitializationException;
use Teambank\RatenkaufByEasyCreditApiV3\Model\Transaction;
use Teambank\RatenkaufByEasyCreditApiV3\Model\TransactionSummary;
use Teambank\RatenkaufByEasyCreditApiV3\Model\TransactionInformation;
use Teambank\RatenkaufByEasyCreditApiV3\Model\TransactionUpdate;
use Teambank\RatenkaufByEasyCreditApiV3\Model\IntegrationCheckRequest;
use Teambank\RatenkaufByEasyCreditApiV3\Model\InstallmentPlanRequest;
use Teambank\RatenkaufByEasyCreditApiV3\Model\Article;

class Checkout implements CheckoutInterface {

    protected $webshopApi;
    protected $transactionApi;
    protected $installmentplanApi;
    protected $storage;
    protected $addressValidator;
    protected $prefixConverter;
    protected $logger;

    public function __construct(
        WebshopApi $webshopApi,
        TransactionApi $transactionApi,
        InstallmentplanApi $installmentplanApi,
        StorageInterface $storage,
        AddressValidator $addressValidator,
        PrefixConverter $prefixConverter,
        $logger
    ) {
        $this->webshopApi = $webshopApi;
        $this->transactionApi = $transactionApi;
        $this->installmentplanApi = $installmentplanApi;
        $this->storage = $storage;
        $this->addressValidator = $addressValidator;
        $this->prefixConverter = $prefixConverter;
        $this->logger = $logger;
    }

    public function getRedirectUrl() {
        return $this->storage->get('redirect_url');
    }

    public function start(
        Transaction $request
    ) {
        $this->storage
            ->set('uniqid', uniqid());

        $result = $this->transactionApi->apiPaymentV3TransactionPost($request);

        $this->storage
            ->set('token', $result->getTechnicalTransactionId())
            ->set('transaction_id', $result->getTransactionId())
            ->set('authorized_amount', $request->getOrderDetails()->getOrderValue())
            ->set('address_hash', $this->addressValidator->hashAddress($request->getOrderDetails()->getShippingAddress()))
            ->set('redirect_url', $result->getRedirectUrl());

        return $this;
    }

    public function update(
        Transaction $request
    ) {

        $orderDetails = $request->getOrderDetails();

        $result = $this->transactionApi->apiPaymentV3TransactionTechnicalTransactionIdPatch(
            $this->storage->get('token'),
            new TransactionUpdate([
                'orderValue' => round($orderDetails->getOrderValue() - $this->storage->get('interest_amount'), 2),
                'numberOfProductsInShoppingCart' => $orderDetails->getNumberOfProductsInShoppingCart(), 
                'orderId' => $orderDetails->getOrderId(), 
                'shoppingCartInformation' => $orderDetails->getShoppingCartInformation(), 
                'financingTerm' => $request->getFinancingTerm() 
            ])
        );

        $this->storage
            ->set('authorized_amount', $result->getOrderValue())
            ->set('interest_amount', (float) $result->getInterest())
            ->set('summary', json_encode($result->jsonSerialize()));

        return $result;
    }

    public function finalizeExpress(
        Transaction $request
    ) {
       $this->storage
            ->set('address_hash', $this->addressValidator->hashAddress($request->getOrderDetails()->getShippingAddress()));
    }

    public function getConfig() {
        return $this->_api->getConfig();
    }

    public function isInitialized() {
        try {
            $this->_getToken();
            return true;
        } catch (InitializationException $e) {
            return false;
        }
    }

    protected function _getToken() {
        $token = $this->storage->get('token');

        if (empty($token)) {
            throw new InitializationException('easyCredit-Ratenkauf payment was not initialized');
        }
        return $token;
    }
    
    public function loadTransaction($txId = null) {
        if ($txId === null) {
            $txId = $this->_getToken();
        }

        $result = $this->transactionApi->apiPaymentV3TransactionTechnicalTransactionIdGet(
            $txId
        );

        switch ($result->getStatus()) {
            case TransactionInformation::STATUS_OPEN:
            case TransactionInformation::STATUS_DECLINED:
            case TransactionInformation::STATUS_EXPIRED:
                throw new InitializationException('easyCredit-Ratenkauf transaction is not valid, status: '.$result->getStatus());

            case TransactionInformation::STATUS_PREAUTHORIZED:
                $this->storage->set(
                    'interest_amount',
                    (float) $result->getDecision()->getInterest()
                )->set(
                    'summary',
                    json_encode($result->getDecision()->jsonSerialize())
                );
        }
        return $result;
    }

    public function isApproved() {
        $summary = json_decode($this->storage->get('summary'));
        if ($summary &&
            isset($summary->decisionOutcome) && 
            TransactionSummary::DECISION_OUTCOME_POSITIVE == $summary->decisionOutcome
        ) {
            return true;
        }
        return false;
    }

    public function authorize($orderId = null) {
        try {
            list($response, $statusCode) = $this->transactionApi->apiPaymentV3TransactionTechnicalTransactionIdAuthorizationPostWithHttpInfo(
                $this->_getToken(),
                new \Teambank\RatenkaufByEasyCreditApiV3\Model\AuthorizationRequest([
                    'orderId' => $orderId
                ])
            );
            if ((int) $statusCode === 202) {
                $this->storage->set(
                    'is_authorized', 1
                );              
                return true;
            }
        } catch (ApiException $e) {
            $this->logger->warning($e);
        }
        return false;
    }

    public function getInstallmentValues($amount) {
        return $this->installmentplanApi->apiRatenrechnerV3WebshopShopIdentifierInstallmentplansPost(
            $this->webshopApi->getConfig()->getUsername(),
            new InstallmentPlanRequest([
                'articles'=> [
                    new Article(['identifier' => 'single', 'price' => $amount ])
                ]
            ])
        );
    }

    public function getWebshopDetails() {
        return $this->webshopApi->apiPaymentV3WebshopGet();
    }

    public function verifyCredentials($apiKey, $apiToken, $apiSignature = null) {
        $this->webshopApi->getConfig()
            ->setUsername($apiKey)
            ->setPassword($apiToken)
            ->setAccessToken($apiSignature);

        try {
            $this->webshopApi->apiPaymentV3WebshopIntegrationcheckPost(
                new IntegrationCheckRequest(['message'=>''])
            );
        } catch (ApiException $e) {
            if ($e->getCode() === 401) {
                throw new ApiCredentialsInvalidException();
            }
            if ($e->getCode() === 403) {
                throw new ApiCredentialsNotActiveException();
            }
            throw $e;
        }
    }

    public function isAvailable(Transaction $request, $checkAmount = false) {

        $this->addressValidator->validate($request);

        if ($checkAmount) {
            $request = $request->getTransaction();
            try {
                $this->getInstallmentValues($request->getOrderDetails()->getOrderValue());
            } catch (ApiException $e) {
                if ($e->getCode() === 400) {
                    throw new AmountOutOfRange();
                }
                throw $e;
            }
        }

        return true;
    }

    public function verifyAddress(Transaction $request, $preCheck = false) {
        $initialHash = $this->storage->get('address_hash');

        $billingHash = null;
        if ($request->getOrderDetails()->getInvoiceAddress()) {
            $billingHash = $this->addressValidator->hashAddress(
                $request->getOrderDetails()->getInvoiceAddress()
            );
        }

        $shippingHash = $this->addressValidator->hashAddress(
            $request->getOrderDetails()->getShippingAddress()
        );

        return (
            ($preCheck || $initialHash === $shippingHash) &&
            ($billingHash === $shippingHash || $billingHash === null)
        );
    }

    public function isAmountValid(Transaction $request) {

        $amount = $request->getOrderDetails()->getOrderValue();
        $authorizedAmount = $this->storage->get('authorized_amount');
        $interestAmount = $this->storage->get('interest_amount');

        if (
            $amount === null ||
            $authorizedAmount === null ||
            $interestAmount === null ||
            round((float) $amount, 2) !== round((float) $authorizedAmount + (float) $interestAmount, 2)
        ) {
            $this->logger->debug('amount not valid: '.$amount.' (amount) !== '.$authorizedAmount.' (authorized) + '.$interestAmount.' (interest)');
            return false;
        }
        return true;
    }

    public function isValid(Transaction $request) {
        return $this->isAmountValid($request) &&
            $this->verifyAddress($request);
    }

    public function isPrefixValid($prefix) {
        return $this->prefixConverter->convert($prefix) !== null;
    }

    public function clear() {
        return $this->storage->clear();
    }
}
