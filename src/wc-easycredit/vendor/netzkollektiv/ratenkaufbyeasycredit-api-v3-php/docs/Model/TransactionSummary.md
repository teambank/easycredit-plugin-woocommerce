# # TransactionSummary

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**transactionId** | **string** | Unique functional transaction identifier (consists of 6 characters) | [optional]
**deviceIdentToken** | **string** |  | [optional]
**orderValue** | **float** | Amount of the order value in € ( &#x3D; Bestellwert in €) | [optional]
**interest** | **float** | Amount of the interest accrued in € ( &#x3D; anfallender Zinsbetrag in €) | [optional]
**nominalInterest** | **float** | ( &#x3D; nominalzins in €) | [optional]
**effectiveInterest** | **float** | ( &#x3D; effektivzins in €) | [optional]
**merchantSpecificInterest** | **float** | ( &#x3D; haendlerspezifischerZinssatz in €) | [optional]
**totalValue** | **float** | Amount of the total value in € ( &#x3D; Gesamtsumme in €) | [optional]
**decisionOutcome** | **string** | Outcome of the credit decision | [optional]
**decisionOutcomeText** | **string** | Text containing further information on the decision outcome ( &#x3D; entscheidungsergebnisTextbaustein) | [optional]
**numberOfInstallments** | **int** | Number of Installments defined in the payment plan ( &#x3D; anzahl der Raten) | [optional]
**minNumberOfInstallments** | **int** | minimum number of Installments defined in the payment plan ( &#x3D; minimaleLaufzeit) | [optional]
**maxNumberOfInstallments** | **int** | maximum number of Installments defined in the payment plan ( &#x3D; maximaleLaufzeit) | [optional]
**installment** | **float** | Amount in € of a single installment according to the payment plan ( &#x3D; betrag der Rate in €) | [optional]
**lastInstallment** | **float** | Amount in € of the last installment according to the payment plan ( &#x3D; betrag der letzten Rate in €) | [optional]
**firstInstallmentDate** | **\DateTime** | Date indicating the first installment payment ( &#x3D; terminErsteRate) | [optional]
**lastInstallmentDate** | **\DateTime** | Date indicating the last installment payment ( &#x3D; terminLetzteRate) | [optional]
**amortizationPlanText** | **string** | Text containing the amortization plan ( &#x3D; tilgungsplanText) | [optional]
**urlPreContractualInformation** | **string** | ( &#x3D; urlVorvertraglicheInformationen) | [optional]
**installmentPlans** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\InstallmentPlan[]**](InstallmentPlan.md) | List of possible installment payment plans | [optional]
**mtan** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\MTan**](MTan.md) |  | [optional]
**bankAccountCheck** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\BankAccountCheck**](BankAccountCheck.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
