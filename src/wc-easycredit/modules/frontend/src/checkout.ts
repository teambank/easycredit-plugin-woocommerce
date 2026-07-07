/* eslint-env jquery */
import { getEasycreditCheckoutFromEvent } from "./utils";

const ADDRESS_FIELDS = [
	"first_name",
	"last_name",
	"company",
	"address_1",
	"address_2",
	"city",
	"state",
	"postcode",
	"country",
	"email",
	"phone",
] as const;

const getFormFieldValue = (form: HTMLFormElement, name: string): string => {
	const field = form.elements.namedItem(name);

	if (field instanceof RadioNodeList) {
		return field.value ?? "";
	}

	if (
		field instanceof HTMLInputElement ||
		field instanceof HTMLSelectElement ||
		field instanceof HTMLTextAreaElement
	) {
		return field.value ?? "";
	}

	return "";
};

const collectCheckoutAddresses = (form: HTMLFormElement) => {
	const billing: Record<string, string> = {};
	const shipping: Record<string, string> = {};
	const shipToDifferent = (
		form.querySelector("#ship-to-different-address-checkbox") as HTMLInputElement | null
	)?.checked;

	for (const field of ADDRESS_FIELDS) {
		billing[field] = getFormFieldValue(form, `billing_${field}`);

		if (shipToDifferent) {
			shipping[field] = getFormFieldValue(form, `shipping_${field}`);
		}
	}

	if (!shipToDifferent) {
		for (const field of ADDRESS_FIELDS) {
			if (field === "email" || field === "phone") {
				continue;
			}
			shipping[field] = billing[field] ?? "";
		}
	}

	return { billing, shipping };
};

const applyValidationResponse = (
	message: string,
	invalidated: boolean,
): void => {
	document.querySelectorAll("easycredit-checkout").forEach((element) => {
		if (!(element instanceof HTMLElement)) {
			return;
		}

		element.setAttribute("alert", message);
		(element as HTMLElement & { alert?: string }).alert = message;

		if (message || invalidated) {
			element.removeAttribute("payment-plan");
		}
	});
};

const fetchCheckoutValidation = async (form: HTMLFormElement) => {
	const { billing, shipping } = collectCheckoutAddresses(form);
	const response = await fetch("/wp-json/easycredit/v1/checkout-validation", {
		method: "POST",
		headers: {
			"Content-Type": "application/json",
		},
		body: JSON.stringify({ billing, shipping }),
	});

	if (!response.ok) {
		return { message: "", invalidated: false };
	}

	return response.json();
};

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

const submitCheckoutForm = async (component: HTMLElement, e: CustomEvent) => {
	const form = component.closest("form");
	if (!(form instanceof HTMLFormElement)) {
		return;
	}

	if (!validateCheckoutForm(form)) {
		component.dispatchEvent(new Event("closeModal"));
		return;
	}

	try {
		const validation = await fetchCheckoutValidation(form);
		applyValidationResponse(
			validation.message || "",
			Boolean(validation.invalidated),
		);

		if (validation.message || validation.invalidated) {
			component.dispatchEvent(new Event("closeModal"));
			return;
		}
	} catch {
		// Fall back to server-side validation on submit.
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
	let validationTimeout: number | undefined;
	let validationRequest = 0;

	const scheduleValidation = (form: HTMLFormElement) => {
		window.clearTimeout(validationTimeout);
		validationTimeout = window.setTimeout(async () => {
			const requestId = ++validationRequest;

			try {
				const validation = await fetchCheckoutValidation(form);
				if (requestId !== validationRequest) {
					return;
				}

				applyValidationResponse(
					validation.message || "",
					Boolean(validation.invalidated),
				);
			} catch {
				if (requestId === validationRequest) {
					applyValidationResponse("", false);
				}
			}
		}, 400);
	};

	const shouldValidateAddressField = (target: Element): boolean => {
		return (
			target.closest(".woocommerce-billing-fields") !== null ||
			target.closest(".woocommerce-shipping-fields") !== null ||
			target.matches("#billing_company") ||
			target.matches("#shipping_company") ||
			target.matches("#ship-to-different-address-checkbox")
		);
	};

	document.addEventListener(
		"easycredit-submit",
		(e) => {
			const component = getEasycreditCheckoutFromEvent(e);
			if (!component || !(e instanceof CustomEvent)) {
				return;
			}

			e.preventDefault();
			void submitCheckoutForm(component, e);
		},
		true,
	);
	checkout.addEventListener("change", (event) => {
		const target = event.target;
		if (!(target instanceof Element) || !(checkout instanceof HTMLFormElement)) {
			return;
		}

		if (shouldValidateAddressField(target)) {
			scheduleValidation(checkout);
		}
	});
	checkout.addEventListener("input", (event) => {
		const target = event.target;
		if (!(target instanceof Element) || !(checkout instanceof HTMLFormElement)) {
			return;
		}

		if (
			target.matches("#billing_company") ||
			target.matches("#shipping_company") ||
			target.closest(".woocommerce-billing-fields") ||
			target.closest(".woocommerce-shipping-fields")
		) {
			scheduleValidation(checkout);
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