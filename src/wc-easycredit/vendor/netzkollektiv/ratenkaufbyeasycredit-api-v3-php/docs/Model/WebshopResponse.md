# # WebshopResponse

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**maxFinancingAmount** | **int** |  | [optional]
**minFinancingAmount** | **int** |  | [optional]
**interestRate** | **float** |  | [optional]
**availability** | **bool** | true if financing is available from this webshop | [optional]
**testMode** | **bool** | true if the webshop is in test mode | [optional]
**privacyApprovalForm** | **string** | form for privacy approval (zustimmungDatenuebertragungPaymentPage) | [optional]
**declarationOfConsent** | **string** | (zustimmungEinwilligungserklaerungPaymentPage) | [optional]
**illustrativeExample** | **string** | (repraesentativesBeispiel) | [optional]
**productDetails** | **string** | (produktangaben) | [optional]
**uuid** | **string** | request-id | [optional]
**flexprice** | **bool** | true if the shop has an active flexprice or a flexprice time period is planned for the future | [optional] [default to false]
**installmentPaymentActive** | **bool** |  | [optional]
**billPaymentActive** | **bool** |  | [optional]
**minBillingValue** | **float** |  | [optional]
**maxBillingValue** | **float** |  | [optional]
**minInstallmentValue** | **float** |  | [optional]
**maxInstallmentValue** | **float** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
