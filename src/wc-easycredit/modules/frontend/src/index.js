import {
	waitForComponentReady,
	waitForLoadEvent,
	watchForSelector,
} from "./utils";
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
	handleExpressButton(document.querySelector("easycredit-express-button"));
})();

(async () => {
	await waitForLoadEvent();

	const wooCommerceCheckout = document.querySelector(
		"form.woocommerce-checkout",
	);

	if (!wooCommerceCheckout) {
		return;
	}
	handleCheckout(wooCommerceCheckout);
	for (const [paymentMethod, paymentType] of Object.entries(methods)) {
		handleCheckoutMethods(wooCommerceCheckout, paymentMethod, paymentType);
	}
})();

(async () => {
	await waitForLoadEvent();
	handleMarketingComponents();
})();

(async () => {
	await waitForLoadEvent();
	handleWidget();
})();
