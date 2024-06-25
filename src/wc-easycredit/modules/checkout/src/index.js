import {
	registerPaymentMethod,
	registerExpressPaymentMethod,
} from "@woocommerce/blocks-registry";

import { getMethodConfiguration } from "./payment-method";
import expressPaymentMethod from "./express-payment-method";

registerPaymentMethod(getMethodConfiguration("easycredit_rechnung"));
registerPaymentMethod(getMethodConfiguration("easycredit_ratenkauf"));
registerExpressPaymentMethod(expressPaymentMethod);
