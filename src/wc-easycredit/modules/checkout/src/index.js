import {
	registerPaymentMethod,
	registerExpressPaymentMethod,
} from "@woocommerce/blocks-registry";

import { getMethodConfiguration } from "./payment-method";
import expressPaymentMethod from "./express-payment-method";

const billPaymentMethod = getMethodConfiguration("easycredit_rechnung");
registerPaymentMethod(billPaymentMethod);

const installmentPaymentMethod = getMethodConfiguration("easycredit_ratenkauf");
registerPaymentMethod(installmentPaymentMethod);

if (
	billPaymentMethod.name === expressPaymentMethod.name ||
	installmentPaymentMethod.name === expressPaymentMethod.name
) {
	throw new Error(
		"express method and normal payment method may not have the same name",
	);
}
registerExpressPaymentMethod(expressPaymentMethod);
