export const shouldDeferNativeTermsValidation = (paymentPlan) =>
	paymentPlan == null;

export const isNativeTermsValidationErrorKey = (key) =>
	key.startsWith("terms-and-conditions-");

export const NATIVE_TERMS_CHECKBOX_SELECTORS =
	'.wp-block-woocommerce-checkout-terms-block input#terms-and-conditions[type="checkbox"]';
