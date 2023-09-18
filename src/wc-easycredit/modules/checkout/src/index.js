import {
	registerPaymentMethod,
	registerExpressPaymentMethod,
} from '@woocommerce/blocks-registry';

import paymentMethod from './payment-method';
import expressPaymentMethod from './express-payment-method';

registerPaymentMethod( paymentMethod );
registerExpressPaymentMethod( expressPaymentMethod );
