import { createDeferredLegalValidation } from "./core";
import {
	isNativeTermsValidationErrorKey,
	NATIVE_TERMS_CHECKBOX_SELECTORS,
	shouldDeferNativeTermsValidation,
} from "./native-terms";
import {
	GERMANIZED_LEGAL_CHECKBOX_SELECTORS,
	isGermanizedLegalValidationErrorKey,
} from "../compat/germanized-legal";

const DEFERRED_LEGAL_CLICK_BYPASS_SELECTORS = [
	NATIVE_TERMS_CHECKBOX_SELECTORS,
	GERMANIZED_LEGAL_CHECKBOX_SELECTORS,
]
	.filter(Boolean)
	.join(", ");

const isDeferredLegalValidationErrorKey = (key) =>
	isNativeTermsValidationErrorKey(key) ||
	isGermanizedLegalValidationErrorKey(key);

const deferredLegalValidation = createDeferredLegalValidation({
	isDeferredLegalValidationErrorKey,
	checkboxSelectors: DEFERRED_LEGAL_CLICK_BYPASS_SELECTORS,
});

export {
	shouldDeferNativeTermsValidation,
	isDeferredLegalValidationErrorKey,
	DEFERRED_LEGAL_CLICK_BYPASS_SELECTORS as DEFERRED_LEGAL_CHECKBOX_SELECTORS,
};

export const {
	clearDeferredLegalValidationErrors,
	hasNonDeferredLegalValidationErrors,
	hasDeferredLegalValidationErrors,
	temporarilyBypassDeferredLegalCheckboxes,
	suppressDeferredLegalValidationDuring,
	waitForCheckoutRedirect,
} = deferredLegalValidation;
