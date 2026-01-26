import { useRef, useEffect, useState } from "@wordpress/element";
import { useSelect } from "@wordpress/data";
import { VALIDATION_STORE_KEY, CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
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

	const Checkout = ({ billing, shippingData, eventRegistration, activePaymentMethod }) => {
		const { onCheckoutFail, onCheckoutValidation } = eventRegistration;

		const hasValidationErrors = useSelect((select) =>
			select(VALIDATION_STORE_KEY).hasValidationErrors()
		);

		const ecCheckout = useRef(null);
		const [paymentPlan, setPaymentPlan] = useState(config.paymentPlan);
		const privacyApproved = useRef(paymentPlan !== null);
		
		// Create a hash from cart data (amount + addresses) to detect changes
		const createCartHash = (amount, billingAddress, shippingAddress) => {
			return JSON.stringify({
				amount,
				billingAddress,
				shippingAddress: shippingAddress || {}
			});
		};
		
		const prevCartHash = useRef(
			createCartHash(
				billing.cartTotal.value,
				billing.billingAddress,
				shippingData.shippingAddress
			)
		);

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

		/*
		 * update place order button label based on payment plan state
		 */
		useEffect(() => {
			if (activePaymentMethod !== config.id) {
				return;
			}

			const button = document.querySelector('.wc-block-components-checkout-place-order-button');
			if (!button || !config.placeOrderButtonLabel) {
				return;
			}

			// Store default label on first render
			if (!button.hasAttribute('data-default-label')) {
				button.setAttribute('data-default-label', button.textContent);
			}

			// Show custom label when there's no payment plan, show default when payment plan exists
			if (!paymentPlan) {
				button.textContent = decodeEntities(config.placeOrderButtonLabel);
			} else {
				const defaultLabel = button.getAttribute('data-default-label');
				if (defaultLabel) {
					button.textContent = defaultLabel;
				}
			}
		}, [paymentPlan, activePaymentMethod]);

		/*
		 * submit checkout if easycredit-checkout triggers easycredit-submit event
		 */
		useEffect(() => {
			if (currentHandler) {
				document.removeEventListener('easycredit-submit', currentHandler);
			}
			const handler = handleSubmit(privacyApproved, hasValidationErrors, emulateSubmitCheckout);
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
					payment-plan={paymentPlan}
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
