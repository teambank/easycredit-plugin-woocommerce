# # Transaction

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**financingTerm** | **int** | &#39; Duration in months, depending on individual shop conditions and order value (please check your ratenkauf widget). Will be set to default value if not available. &#39; | [optional]
**orderDetails** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\OrderDetails**](OrderDetails.md) |  |
**shopsystem** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\Shopsystem**](Shopsystem.md) |  | [optional]
**customer** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\Customer**](Customer.md) |  | [optional]
**customerRelationship** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\CustomerRelationship**](CustomerRelationship.md) |  | [optional]
**consent** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\Consent**](Consent.md) |  | [optional]
**redirectLinks** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\RedirectLinks**](RedirectLinks.md) |  | [optional]
**paymentType** | **string** | experimental | [optional] [default to 'INSTALLMENT_PAYMENT']
**paymentSwitchPossible** | **bool** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
