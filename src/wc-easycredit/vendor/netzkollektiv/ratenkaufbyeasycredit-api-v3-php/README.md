# ratenkauf by easyCredit API v3 PHP Library

The ratenkauf by easyCredit API v3 Library is the official PHP library for using the following ratenkauf by easyCredit API's:

 * Payment API v3 (https://ratenkauf.easycredit.de/api/payment/v3/openapi)
 * Calculator API v3 (https://ratenkauf.easycredit.de/api/ratenrechner/v3/openapi)
 * Merchant API v3 (https://partner.easycredit-ratenkauf.de/api/merchant/v3/openapi) 

## Installation & Usage

### Requirements

 * PHP >= 7.0

### Composer

To install the API Library via [Composer](https://getcomposer.org/), add the following to `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/netzkollektiv/ratenkaufbyeasycredit-api-v3-php.git"
    }
  ],
  "require": {
    "netzkollektiv/ratenkaufbyeasycredit-api-v3-php": "*@dev"
  }
}
```

Then run `composer install`

### Manual Installation

Download the files and include `autoload.php`:

```php
<?php
require_once('/path/to/ratenkaufbyeasycredit-api-v3-php/vendor/autoload.php');
```

## Getting Started

Please follow the [installation procedure](#installation--usage) and then run the following:

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



// Configure HTTP basic authorization: basicAuth
$config = Teambank\RatenkaufByEasyCreditApiV3\Configuration::getDefaultConfiguration()
              ->setHost('https://ratenkauf.easycredit.de')
              ->setUsername('1.de.1234.1') // use your "Webshop-ID"
              ->setPassword('YOUR_API_KEY'); // use your "API-Kennwort"


$apiInstance = new Teambank\RatenkaufByEasyCreditApiV3\Api\DocumentApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$billingDateFrom = new \DateTime("2013-10-20T19:20:30+01:00"); // \DateTime | set by default to the last month if not specified
$billingDateTo = new \DateTime("2013-10-20T19:20:30+01:00"); // \DateTime | set by default to billingDateFrom + one month if not specified
$documentType = array('documentType_example'); // string[] | set by default to all options if not specified
$fileType = array('fileType_example'); // string[] | set by default to all options if not specified

try {
    $result = $apiInstance->apiMerchantV3DocumentsGet($billingDateFrom, $billingDateTo, $documentType, $fileType);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DocumentApi->apiMerchantV3DocumentsGet: ', $e->getMessage(), PHP_EOL;
}

```

## API Endpoints

All URIs are relative to *https://partner.easycredit-ratenkauf.de*

Class | Method | HTTP request | Description
------------ | ------------- | ------------- | -------------
*DocumentApi* | [**apiMerchantV3DocumentsGet**](docs/Api/DocumentApi.md#apimerchantv3documentsget) | **GET** /api/merchant/v3/documents | Download billing documents of a merchant.
*InstallmentplanApi* | [**apiRatenrechnerV3WebshopShopIdentifierInstallmentplansPost**](docs/Api/InstallmentplanApi.md#apiratenrechnerv3webshopshopidentifierinstallmentplanspost) | **POST** /api/ratenrechner/v3/webshop/{shopIdentifier}/installmentplans | Calculates the installmentplan
*TransactionApi* | [**apiMerchantV3TransactionGet**](docs/Api/TransactionApi.md#apimerchantv3transactionget) | **GET** /api/merchant/v3/transaction | Find transactions of a merchant according to some search parameters.
*TransactionApi* | [**apiMerchantV3TransactionTransactionIdCapturePost**](docs/Api/TransactionApi.md#apimerchantv3transactiontransactionidcapturepost) | **POST** /api/merchant/v3/transaction/{transactionId}/capture | Report a capture for a transaction according to its unique functional identifier
*TransactionApi* | [**apiMerchantV3TransactionTransactionIdGet**](docs/Api/TransactionApi.md#apimerchantv3transactiontransactionidget) | **GET** /api/merchant/v3/transaction/{transactionId} | Retrieve a transaction of a merchant according to a unique functional identifier
*TransactionApi* | [**apiMerchantV3TransactionTransactionIdRefundPost**](docs/Api/TransactionApi.md#apimerchantv3transactiontransactionidrefundpost) | **POST** /api/merchant/v3/transaction/{transactionId}/refund | Report a refund for a transaction according to its unique functional identifier
*TransactionApi* | [**apiPaymentV3TransactionPost**](docs/Api/TransactionApi.md#apipaymentv3transactionpost) | **POST** /api/payment/v3/transaction | Initiates a transaction based on the given request
*TransactionApi* | [**apiPaymentV3TransactionTechnicalTransactionIdAuthorizationPost**](docs/Api/TransactionApi.md#apipaymentv3transactiontechnicaltransactionidauthorizationpost) | **POST** /api/payment/v3/transaction/{technicalTransactionId}/authorization | Authorizes a transaction after finishing the process in a webshop
*TransactionApi* | [**apiPaymentV3TransactionTechnicalTransactionIdGet**](docs/Api/TransactionApi.md#apipaymentv3transactiontechnicaltransactionidget) | **GET** /api/payment/v3/transaction/{technicalTransactionId} | Get the necessary information about the transaction
*TransactionApi* | [**apiPaymentV3TransactionTechnicalTransactionIdPatch**](docs/Api/TransactionApi.md#apipaymentv3transactiontechnicaltransactionidpatch) | **PATCH** /api/payment/v3/transaction/{technicalTransactionId} | Updates a transaction based on the given request
*TransactionApi* | [**apiPaymentV3TransactionTechnicalTransactionIdSwitchPaymentMethodPost**](docs/Api/TransactionApi.md#apipaymentv3transactiontechnicaltransactionidswitchpaymentmethodpost) | **POST** /api/payment/v3/transaction/{technicalTransactionId}/switchPaymentMethod | Switch payment method
*WebshopApi* | [**apiPaymentV3WebshopGet**](docs/Api/WebshopApi.md#apipaymentv3webshopget) | **GET** /api/payment/v3/webshop | Get the necessary information about the webshop
*WebshopApi* | [**apiPaymentV3WebshopIntegrationcheckPost**](docs/Api/WebshopApi.md#apipaymentv3webshopintegrationcheckpost) | **POST** /api/payment/v3/webshop/integrationcheck | Verifies the correctness of the merchant&#39;s authentication credentials and, if enabled, the body signature
*WebshopApi* | [**apiPaymentV3WebshopWebshopIdGet**](docs/Api/WebshopApi.md#apipaymentv3webshopwebshopidget) | **GET** /api/payment/v3/webshop/{webshopId} | Get the necessary information about the webshop

## Models

- [Address](docs/Model/Address.md)
- [Article](docs/Model/Article.md)
- [ArticleNumberItem](docs/Model/ArticleNumberItem.md)
- [AuthenticationError](docs/Model/AuthenticationError.md)
- [AuthorizationRequest](docs/Model/AuthorizationRequest.md)
- [AuthorizationStatusResponse](docs/Model/AuthorizationStatusResponse.md)
- [Bank](docs/Model/Bank.md)
- [BankAccountCheck](docs/Model/BankAccountCheck.md)
- [Booking](docs/Model/Booking.md)
- [CalculatorInstallmentPlan](docs/Model/CalculatorInstallmentPlan.md)
- [CaptureRequest](docs/Model/CaptureRequest.md)
- [Consent](docs/Model/Consent.md)
- [ConstraintViolation](docs/Model/ConstraintViolation.md)
- [ConstraintViolationViolationsInner](docs/Model/ConstraintViolationViolationsInner.md)
- [Contact](docs/Model/Contact.md)
- [Customer](docs/Model/Customer.md)
- [CustomerRelationship](docs/Model/CustomerRelationship.md)
- [DeviceIdentToken](docs/Model/DeviceIdentToken.md)
- [Employment](docs/Model/Employment.md)
- [InstallmentPlan](docs/Model/InstallmentPlan.md)
- [InstallmentPlanRequest](docs/Model/InstallmentPlanRequest.md)
- [InstallmentPlanResponse](docs/Model/InstallmentPlanResponse.md)
- [IntegrationCheckRequest](docs/Model/IntegrationCheckRequest.md)
- [IntegrationCheckResponse](docs/Model/IntegrationCheckResponse.md)
- [Interests](docs/Model/Interests.md)
- [InvoiceAddress](docs/Model/InvoiceAddress.md)
- [MTan](docs/Model/MTan.md)
- [Message](docs/Model/Message.md)
- [OrderDetails](docs/Model/OrderDetails.md)
- [PaginationInfo](docs/Model/PaginationInfo.md)
- [PaymentConstraintViolation](docs/Model/PaymentConstraintViolation.md)
- [PaymentConstraintViolationViolationsInner](docs/Model/PaymentConstraintViolationViolationsInner.md)
- [PaymentPlan](docs/Model/PaymentPlan.md)
- [Plan](docs/Model/Plan.md)
- [RedirectLinks](docs/Model/RedirectLinks.md)
- [RedirectLinksSI](docs/Model/RedirectLinksSI.md)
- [Refund](docs/Model/Refund.md)
- [RefundBooking](docs/Model/RefundBooking.md)
- [RefundRequest](docs/Model/RefundRequest.md)
- [ServerError](docs/Model/ServerError.md)
- [ShippingAddress](docs/Model/ShippingAddress.md)
- [ShoppingCartInformationItem](docs/Model/ShoppingCartInformationItem.md)
- [Shopsystem](docs/Model/Shopsystem.md)
- [Transaction](docs/Model/Transaction.md)
- [TransactionCustomer](docs/Model/TransactionCustomer.md)
- [TransactionInformation](docs/Model/TransactionInformation.md)
- [TransactionInitResponse](docs/Model/TransactionInitResponse.md)
- [TransactionListInfo](docs/Model/TransactionListInfo.md)
- [TransactionOrderDetails](docs/Model/TransactionOrderDetails.md)
- [TransactionResponse](docs/Model/TransactionResponse.md)
- [TransactionSummary](docs/Model/TransactionSummary.md)
- [TransactionUpdate](docs/Model/TransactionUpdate.md)
- [TransmitMtan](docs/Model/TransmitMtan.md)
- [WebshopResponse](docs/Model/WebshopResponse.md)

## Authorization

### basicAuth

- **Type**: HTTP basic authentication

## Tests

To run the tests, use:

```bash
composer install
vendor/bin/phpunit
```

## Author



## About this package

This PHP package is automatically generated by the [OpenAPI Generator](https://openapi-generator.tech) project:

- API version: `V3.147.0`
- Build package: `org.openapitools.codegen.languages.PhpClientCodegen`
