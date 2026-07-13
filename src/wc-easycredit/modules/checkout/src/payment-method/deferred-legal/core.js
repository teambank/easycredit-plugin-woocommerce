import { dispatch, select, subscribe } from "@wordpress/data";
import { CHECKOUT_STORE_KEY, VALIDATION_STORE_KEY } from "@woocommerce/block-data";

export const createDeferredLegalValidation = ({
	isDeferredLegalValidationErrorKey,
	checkboxSelectors,
}) => {
	const clearDeferredLegalValidationErrors = () => {
		const store = select(VALIDATION_STORE_KEY);
		const errors = store?.getValidationErrors?.() ?? {};

		Object.keys(errors).forEach((key) => {
			if (isDeferredLegalValidationErrorKey(key)) {
				dispatch(VALIDATION_STORE_KEY).clearValidationError(key);
			}
		});
	};

	const hasNonDeferredLegalValidationErrors = () => {
		const errors =
			select(VALIDATION_STORE_KEY)?.getValidationErrors?.() ?? {};

		return Object.keys(errors).some(
			(key) => !isDeferredLegalValidationErrorKey(key),
		);
	};

	const hasDeferredLegalValidationErrors = () => {
		const errors =
			select(VALIDATION_STORE_KEY)?.getValidationErrors?.() ?? {};

		return Object.keys(errors).some((key) =>
			isDeferredLegalValidationErrorKey(key),
		);
	};

	const temporarilyBypassDeferredLegalCheckboxes = () => {
		if (!checkboxSelectors) {
			return;
		}

		document.querySelectorAll(checkboxSelectors).forEach((input) => {
			if (input instanceof HTMLInputElement && !input.checked) {
				input.click();
			}
		});
	};

	const suppressDeferredLegalValidationDuring = async (callback) => {
		const unsubscribe = subscribe(() => {
			clearDeferredLegalValidationErrors();
		});

		try {
			await callback();
		} finally {
			unsubscribe();
		}
	};

	const waitForCheckoutRedirect = async (timeoutMs = 5000) => {
		const deadline = Date.now() + timeoutMs;

		while (Date.now() < deadline) {
			const redirectUrl = select(CHECKOUT_STORE_KEY)?.getRedirectUrl?.();

			if (
				redirectUrl ||
				/ratenkauf\.easycredit\.de/i.test(window.location.href)
			) {
				return true;
			}

			await new Promise((resolve) => setTimeout(resolve, 100));
		}

		return false;
	};

	return {
		clearDeferredLegalValidationErrors,
		hasNonDeferredLegalValidationErrors,
		hasDeferredLegalValidationErrors,
		temporarilyBypassDeferredLegalCheckboxes,
		suppressDeferredLegalValidationDuring,
		waitForCheckoutRedirect,
	};
};
