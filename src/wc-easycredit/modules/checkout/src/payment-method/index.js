import { createElement, useRef, useEffect, useState } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { decodeEntities } from "@wordpress/html-entities";
import { getSetting } from "@woocommerce/settings";
import { dispatch, select } from "@wordpress/data";
import { VALIDATION_STORE_KEY } from "@woocommerce/block-data";

const PRIVACY_VALIDATION_ERROR = {
	type: "error",
	message: "Bitte stimmen Sie der Datenübermittlung zu.",
};

const clearNativeTermsValidationErrors = () => {
	const store = select(VALIDATION_STORE_KEY);
	const errors = store?.getValidationErrors?.() ?? {};

	Object.keys(errors).forEach((key) => {
		if (key.startsWith("terms-and-conditions-")) {
			dispatch(VALIDATION_STORE_KEY).clearValidationError(key);
		}
	});
};

const shouldDeferNativeTermsValidation = (paymentPlan) => paymentPlan == null;

const emulateSubmitCheckout = async () => {
	let button;
	do {
		const selector = ".wc-block-components-checkout-place-order-button";
		button = document.querySelector(selector);
		if (!button) {
			console.error(
				`Could not submit order. Submit button with selector "$(selector)" was not found`,
			);
			return;
		}
		await new Promise((r) => setTimeout(r, 100));
	} while (button.disabled);

	button.dispatchEvent(
		new window.MouseEvent("click", { bubbles: true }),
	);
};

export const getMethodConfiguration = (name) => {
	const config = getSetting(name + "_data");

	const Checkout = ({ billing, shippingData, eventRegistration, activePaymentMethod }) => {
		const { onCheckoutFail, onCheckoutValidation } = eventRegistration;

		const [validationMessage, setValidationMessage] = useState("");
		const validationMessageRef = useRef("");

		const ecCheckout = useRef(null);
		const [paymentPlan, setPaymentPlan] = useState(config.paymentPlan);
		const paymentPlanRef = useRef(paymentPlan);
		const privacyApproved = useRef(paymentPlan != null);
		
		// Create a hash from cart data (amount + addresses) to detect changes
		const createCartHash = (amount, billingAddress, shippingAddress) => {
			return JSON.stringify({
				amount,
				billingAddress,
				shippingAddress: shippingAddress || {},
			});
		};
		
		const prevCartHash = useRef(
			createCartHash(
				billing.cartTotal.value,
				billing.billingAddress,
				shippingData.shippingAddress
			)
		);

		const billingRef = useRef(billing.billingAddress);
		const shippingRef = useRef(shippingData.shippingAddress);

		const applyValidationResponse = (response) => {
			const message = response?.message || "";
			const invalidated = Boolean(response?.invalidated);

			setValidationMessage(message);

			if (message || invalidated) {
				setPaymentPlan(null);
				privacyApproved.current = false;
			}
		};

		useEffect(() => {
			validationMessageRef.current = validationMessage;
		}, [validationMessage]);

		useEffect(() => {
			paymentPlanRef.current = paymentPlan;
		}, [paymentPlan]);

		useEffect(() => {
			billingRef.current = billing.billingAddress;
		}, [billing.billingAddress]);

		useEffect(() => {
			shippingRef.current = shippingData.shippingAddress;
		}, [shippingData.shippingAddress]);

		/*
		 * reset payment plan if amount or address changes (using cart hash)
		 */
		useEffect(() => {
			const currentCartHash = createCartHash(
				billing.cartTotal.value,
				billing.billingAddress,
				shippingData.shippingAddress
			);

			if (prevCartHash.current !== currentCartHash) {
				setPaymentPlan(null);
				privacyApproved.current = false;
				prevCartHash.current = currentCartHash;
			}
		}, [billing.cartTotal.value, billing.billingAddress, shippingData.shippingAddress]);

		useEffect(() => {
			let cancelled = false;

			const timeoutId = window.setTimeout(() => {
				apiFetch({
					path: "/easycredit/v1/checkout-validation",
					method: "POST",
					data: {
						billing: billing.billingAddress,
						shipping: shippingData.shippingAddress,
					},
				})
					.then((response) => {
						if (!cancelled) {
							applyValidationResponse(response);
						}
					})
					.catch(() => {
						if (!cancelled) {
							applyValidationResponse({ message: "", invalidated: false });
						}
					});
			}, 400);

			return () => {
				cancelled = true;
				window.clearTimeout(timeoutId);
			};
		}, [
			billing.billingAddress,
			shippingData.shippingAddress,
			billing.cartTotal.value,
		]);

		/*
		 * submit checkout if easycredit-checkout triggers easycredit-submit event
		 */
		useEffect(() => {
			const handler = async (e) => {
				if (
					!e.target.matches('easycredit-checkout') ||
					e.target.getAttribute('payment-type') !== config.paymentType
				) {
					return;
				}

				e.preventDefault();

				let message = validationMessageRef.current;
				let invalidated = false;
				try {
					const response = await apiFetch({
						path: "/easycredit/v1/checkout-validation",
						method: "POST",
						data: {
							billing: billingRef.current,
							shipping: shippingRef.current,
						},
					});
					applyValidationResponse(response);
					message = response.message || "";
					invalidated = Boolean(response.invalidated);
				} catch {
					message = "";
					invalidated = false;
				}

				if (message || invalidated) {
					e.target.dispatchEvent(new Event("closeModal"));
					return;
				}

				if (shouldDeferNativeTermsValidation(paymentPlanRef.current)) {
					clearNativeTermsValidationErrors();
				}

				privacyApproved.current = true;
				await emulateSubmitCheckout();
			};

			document.addEventListener('easycredit-submit', handler, true);
			return () => {
				document.removeEventListener('easycredit-submit', handler, true);
			};
		}, []);

		/*
		 * open privacy approval modal if main checkout submit button is clicked
		 */
		useEffect(() => {
			if (activePaymentMethod !== config.id) {
				return undefined;
			}

			const unsubscribe = onCheckoutValidation(() => {
				if (!ecCheckout.current) {
					return true;
				}

				if (shouldDeferNativeTermsValidation(paymentPlanRef.current)) {
					clearNativeTermsValidationErrors();
				}

				const message = validationMessageRef.current;
				if (message) {
					return {
						type: "error",
						message,
					};
				}

				if (
					privacyApproved.current ||
					name !== "easycredit_ratenkauf"
				) {
					return true;
				}

				ecCheckout.current.dispatchEvent(new Event("openModal"));
				return PRIVACY_VALIDATION_ERROR;
			});
			return unsubscribe;
		}, [onCheckoutValidation, activePaymentMethod]);

		useEffect(() => {
			if (activePaymentMethod !== config.id) {
				return undefined;
			}

			const unsubscribe = onCheckoutFail(() => {
				if (!ecCheckout.current) {
					return;
				}

				ecCheckout.current.dispatchEvent(new Event("closeModal"));
			});
			return unsubscribe;
		}, [onCheckoutFail, activePaymentMethod]);

		return createElement(
			'div',
			null,
			createElement('easycredit-checkout', {
				ref: ecCheckout,
				'webshop-id': decodeEntities(config.apiKey),
				amount: billing.cartTotal.value / 100,
				'payment-type': config.paymentType,
				'payment-plan': paymentPlan,
				alert: validationMessage,
			}),
			// Keep hidden content so Woo does not collapse empty accordion body.
			createElement('span', { style: { display: 'none' } }, 'Checkout Component')
		);
	};

	const CheckoutLabel = () => {
		return createElement('easycredit-checkout-label', {
			'payment-type': config.paymentType,
		});
	};

	let methodConfiguration = {
		name: name,
		content: createElement(Checkout), // checkout view
		edit: createElement(Checkout), // admin view
		canMakePayment: () => {
			return config.enabled;
		},
		paymentMethodId: config.id,
		label: createElement(CheckoutLabel),
		ariaLabel: "easycredit",
		supports: {features: config.supports}
	};

	if (config.placeOrderButtonLabel && !config.paymentPlan) {
		methodConfiguration = {
			...methodConfiguration,
			placeOrderButtonLabel: decodeEntities(config.placeOrderButtonLabel),
		};
	}
	
	return methodConfiguration;
};
