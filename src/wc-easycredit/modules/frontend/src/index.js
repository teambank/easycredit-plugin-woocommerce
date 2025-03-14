import { waitForLoadEvent } from "./utils";
import { handleExpressButton } from "./express";
import { handleCheckout, handleCheckoutMethods } from "./checkout";
import { handleMarketingComponents } from "./marketing";
import { handleWidget } from "./widget";

const methods = {
	easycredit_ratenkauf: "INSTALLMENT",
	easycredit_rechnung: "BILL",
};

(async () => {
	await waitForLoadEvent();

	// Initialize all components
	handleExpressButton();
	handleMarketingComponents();
	handleWidget();

	// Handle checkout if checkout form or customer payment page form exists
	const wooCommerceCheckout = document.querySelector("form.woocommerce-checkout, form#order_review");
	if (wooCommerceCheckout) {
		handleCheckout(wooCommerceCheckout);
		for (const [paymentMethod, paymentType] of Object.entries(methods)) {
			handleCheckoutMethods(wooCommerceCheckout, paymentMethod, paymentType);
		}
	}
})().catch(error => {
	console.error('EasyCredit initialization failed:', error);
});
