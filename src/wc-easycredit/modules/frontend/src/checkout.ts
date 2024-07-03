/* eslint-env jquery */
const submitCheckoutForm = (e: CustomEvent) => {
	const form = (e.target as HTMLElement).closest("form");
	if (!(form instanceof HTMLFormElement)) {
		return;
	}

	const inputs = [
		{ name: "easycredit[submit]", value: "1" },
		{ name: "terms", value: "On" },
		{ name: "legal", value: "On" },
	];

	if (e.detail && e.detail.numberOfInstallments) {
		inputs.push({
			name: "easycredit[number-of-installments]",
			value: e.detail.numberOfInstallments,
		});
	}

	inputs.forEach((input) => {
		const hiddenInput = document.createElement("input");
		hiddenInput.type = "hidden";
		hiddenInput.name = input.name;
		hiddenInput.value = input.value;
		form.appendChild(hiddenInput);
	});

	jQuery(form).submit(); // we need jQuery here, because wooCommerce listens for the custom submit event
};

const getComponent = (paymentType) => {
	return document.querySelector(
		'easycredit-checkout[payment-type="' + paymentType + '"]',
	) as HTMLElement;
};

export const handleCheckout = (checkout) => {
	document.body.addEventListener(
		"submit",
		(e) => {
			if (
				e instanceof CustomEvent &&
				e.target &&
				(e.target as HTMLElement).tagName === "EASYCREDIT-CHECKOUT"
			) {
				e.preventDefault();
				submitCheckoutForm(e);
			}
		},
		true,
	);
	checkout.addEventListener("change", (event) => {
		const billingCompany = event.target;
		if (
			billingCompany instanceof Element &&
			billingCompany &&
			billingCompany.closest(".woocommerce-billing-fields")
		) {
			jQuery(billingCompany).trigger("update_checkout");
		}
	});
}

export const handleCheckoutMethods = (checkout, paymentMethod, paymentType) => {
	const $checkout = jQuery(checkout);
	$checkout.on("checkout_place_order_" + paymentMethod, () => {
		const component = getComponent(paymentType);

		if (
			component.style.display === "none" || // Check if the component is not visible
			!component.isActive || // Check if the component is not active
			component.paymentPlan || // Check if the component has a payment plan
			component.alert !== "" // Check if the component's alert is not an empty string
		) {
			return true;
		}

		if (checkout.querySelector('input[name="easycredit[submit]"]')) {
			return true;
		}

      	component.scrollIntoView({ behavior: "smooth" });

		if (paymentType === 'INSTALLMENT') {
			component.dispatchEvent(new Event("openModal"));
		}
		return false;
	});

	if (paymentType === 'INSTALLMENT') {
		jQuery( document.body ).on( 'checkout_error', () => {
			getComponent(paymentType).dispatchEvent(new Event("closeModal"));
		});
	}
};