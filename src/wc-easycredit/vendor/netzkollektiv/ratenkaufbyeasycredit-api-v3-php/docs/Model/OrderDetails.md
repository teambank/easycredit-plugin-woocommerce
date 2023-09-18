# # OrderDetails

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**orderValue** | **float** | Amount in € |
**orderId** | **string** | Shop transaction identifier (allows the shop to store its own reference for the transaction) | [optional]
**numberOfProductsInShoppingCart** | **int** | anzahlProdukteImWarenkorb | [optional]
**withoutFlexprice** | **bool** | Indicator if a flexprice should NOT be used if available | [optional] [default to false]
**invoiceAddress** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\InvoiceAddress**](InvoiceAddress.md) |  | [optional]
**shippingAddress** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\ShippingAddress**](ShippingAddress.md) |  | [optional]
**shoppingCartInformation** | [**\Teambank\RatenkaufByEasyCreditApiV3\Model\ShoppingCartInformationItem[]**](ShoppingCartInformationItem.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
