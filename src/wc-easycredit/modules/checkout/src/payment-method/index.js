import { useRef, useEffect } from "@wordpress/element";
import { useSelect } from "@wordpress/data";
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { __ } from "@wordpress/i18n";
import { decodeEntities } from "@wordpress/html-entities";
import { getSetting } from "@woocommerce/settings";

export const getMethodConfiguration = (name) => {
	const config = getSetting(name + "_data");

	let currentHandler = null; // Store reference to current handler

	const handleSubmit = (privacyApproved, hasValidationErrors, emulateSubmitCheckout) => {
		currentHandler = (e) => {
			if (!e.target.matches('easycredit-checkout') ||
				e.target.getAttribute('payment-type') !== config.paymentType
			) {
				return;
			}

			if (hasValidationErrors) {
				// checkout will not submit => reset submit button loading animation
				e.target.dispatchEvent(new Event("closeModal"));
				return;
			}

			privacyApproved.current = true;
			emulateSubmitCheckout();
		};
		return currentHandler;
	}

	const Checkout = ({ billing, eventRegistration, activePaymentMethod }) => {
		const { onCheckoutFail, onCheckoutValidation } = eventRegistration;

		const hasValidationErrors = useSelect((select) =>
			select(VALIDATION_STORE_KEY).hasValidationErrors()
		);

		const ecCheckout = useRef(null);
		const privacyApproved = useRef(false);

		const emulateSubmitCheckout = async () => {
			let button;
			do {
				const selector =
					".wc-block-components-checkout-place-order-button";
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

		/*
		 * submit checkout if easycredit-checkout triggers easycredit-submit event
		 */
		useEffect(() => {
			if (currentHandler) {
				document.removeEventListener('easycredit-submit', currentHandler);
			}
			const handler = handleSubmit(privacyApproved, hasValidationErrors, emulateSubmitCheckout);
			console.log('adding listener for ' +  config.paymentType);
			document.addEventListener('easycredit-submit', handler);
		}, [hasValidationErrors]);

		/*
		 * open privacy approval modal if main checkout submit button is clicked
		 */
		useEffect(() => {
			if (activePaymentMethod !== config.id) {
				return true;
			}

			const unsubscribe = onCheckoutValidation(() => {
				if (!ecCheckout.current) {
					return true;
				}
				if (
					privacyApproved.current ||
					name !== "easycredit_ratenkauf"
				) {
					return true;
				}

				ecCheckout.current.dispatchEvent(new Event("openModal"));
				return {
					errorMessage: "Bitte stimmen Sie der Datenübermittlung zu.",
				};
			});
			return unsubscribe;
		}, [onCheckoutValidation, activePaymentMethod, privacyApproved]);

		useEffect(() => {
			if (activePaymentMethod !== config.id) {
				return true;
			}

			const unsubscribe = onCheckoutFail(() => {
				if (!ecCheckout.current) {
					return;
				}

				ecCheckout.current.dispatchEvent(new Event("closeModal"));
			});
			return unsubscribe;
		}, [onCheckoutFail, activePaymentMethod]);

		return (
			<div>
				<easycredit-checkout
					ref={ecCheckout}
					webshop-id={decodeEntities(config.apiKey)}
					amount={billing.cartTotal.value / 100}
					payment-type={config.paymentType}
				></easycredit-checkout>
				<span style={{ display: 'none' }}>
					 {/*
					 we need to trick the following wooCommerce css rule:
					 .wc-block-components-radio-control-accordion-content:has(>:only-child:empty) { display:none; }
					*/}
					Checkout Component
				</span>
			</div>
		);
	};

	const CheckoutLabel = () => {
		return (
			<easycredit-checkout-label
				payment-type={config.paymentType}
			></easycredit-checkout-label>
		);
	};

	let methodConfiguration = {
		name: name,
		content: <Checkout />, // checkout view
		edit: <Checkout />, // admin view
		canMakePayment: () => {
			return config.enabled;
		},
		paymentMethodId: config.id,
		label: <CheckoutLabel />,
		ariaLabel: "easycredit",
	};

	if (name === "easycredit_rechnung") {
		methodConfiguration = {
			...methodConfiguration,
			placeOrderButtonLabel: __("Continue to pay by invoice"),
		};
	}
	if (name === "easycredit_ratenkauf") {
		methodConfiguration = {
			...methodConfiguration,
			placeOrderButtonLabel: __("Continue to pay by installments"),
		};
	}
	return methodConfiguration;
};
