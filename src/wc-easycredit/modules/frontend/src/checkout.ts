/* eslint-env jquery */
import { getEasycreditCheckoutFromEvent } from "./utils";

const validateCheckoutForm = (form: HTMLFormElement): boolean => {
	const $form = jQuery(form);
	let hasError = false;
	const $termsCheckbox = $form.find('input[name="terms"]:visible');

	if ($termsCheckbox.length) {
		$termsCheckbox.closest(".form-row").removeClass("woocommerce-invalid");
	}

	$form.find(".input-text, select, input:checkbox").trigger("validate");

	if ($termsCheckbox.length) {
		$termsCheckbox.closest(".form-row").removeClass("woocommerce-invalid");
	}

	if (
		$form
			.find(".woocommerce-invalid:visible")
			.not($termsCheckbox.closest(".form-row"))
			.length > 0
	) {
		hasError = true;
	}

	$form.find(".validate-required:visible").each(function () {
		const $field = jQuery(this);
		const $input = $field.find("input.input-text, select, input:checkbox");

		if (!$input.length) {
			return;
		}

		// Terms are accepted programmatically on easyCredit submit (see submitCheckoutForm).
		if ($input.is('input[name="terms"]')) {
			return;
		}

		const isEmpty = $input.is(":checkbox")
			? !$input.is(":checked")
			: $input.val() === "" || $input.val() === null;

		if (isEmpty) {
			hasError = true;
			$field.addClass("woocommerce-invalid woocommerce-invalid-required-field");
		}
	});

	if (hasError) {
		const $firstInvalidField = $form.find(".woocommerce-invalid:visible").first();
		if ($firstInvalidField.length) {
			jQuery("html, body").animate(
				{
					scrollTop: $firstInvalidField.offset().top - 100,
				},
				500,
			);
		}
	}

	return !hasError;
};

const submitCheckoutForm = (component: HTMLElement, e: CustomEvent) => {
	const form = component.closest("form");
	if (!(form instanceof HTMLFormElement)) {
		return;
	}

	if (!validateCheckoutForm(form)) {
		component.dispatchEvent(new Event("closeModal"));
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
	document.addEventListener(
		"easycredit-submit",
		(e) => {
			const component = getEasycreditCheckoutFromEvent(e);
			if (!component || !(e instanceof CustomEvent)) {
				return;
			}

			e.preventDefault();
			submitCheckoutForm(component, e);
		},
		true,
	);
	checkout.addEventListener("change", (event) => {
		const target = event.target;
		if (
			target instanceof Element &&
			(target.closest(".woocommerce-billing-fields") ||
				target.closest(".woocommerce-shipping-fields") ||
				target.matches("#billing_company") ||
				target.matches("#shipping_company"))
		) {
			jQuery(target).trigger("update_checkout");
		}
	});
	checkout.addEventListener("input", (event) => {
		const target = event.target;
		if (
			target instanceof Element &&
			(target.matches("#billing_company") || target.matches("#shipping_company"))
		) {
			jQuery(target).trigger("update_checkout");
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