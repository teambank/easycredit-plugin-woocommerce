import { replicateForm } from "./utils";

const buildAdditionalParams = (detail) => {
	let additional = {};
	detail.express = "1";
	for (let [key, value] of Object.entries(detail)) {
		additional["easycredit[" + key + "]"] = value;
	}
	return additional
}

const submitExpressForm = function (e) {
	let form;

	const target = e.target

	// product detail page: get add to cart form
	const summary = target.closest(".summary");
	if (summary instanceof HTMLElement) {
		form = summary.querySelector("form.cart");
	}

	// cart page: get add to cart form
	if (!form) {
		form = document.querySelector("form.cart");
	}

	// cart page (classic shortcode): get add to cart form
	if (!form) {
		form = document.querySelector("form.woocommerce-cart-form");
	}
	if (!(form instanceof HTMLFormElement)) {
		return;
	}

	const additional = buildAdditionalParams(e.detail);

	const addToCartButton = form.querySelector(
		'button[name="add-to-cart"], button.single_add_to_cart_button',
	);
	if (addToCartButton) {
		if (addToCartButton.getAttribute("value")) {
			additional["add-to-cart"] = addToCartButton.getAttribute("value");
		}

		let replicatedForm;
		if ((replicatedForm = replicateForm(form, additional))) {
			replicatedForm.submit();
		}
		return;
	}

	// cart page: submit
	if (target.closest(".wc-proceed-to-checkout") && target.dataset.url) {
		const params = new URLSearchParams(additional).toString();
		window.location.href = target.dataset.url + "?" + params;
		return;
	}

	window.alert(
		"Die Express-Zahlung mit easyCredit konnte nicht gestartet werden.",
	);
	console.error(
		"easyCredit payment could not be started. Please check the integration.",
	);
};

const handleVariationSwitch = () => {
	const forms = document.querySelectorAll("form.variations_form");
	forms.forEach((form) => {
		jQuery(form).on("show_variation", function ( event, variation ) {
			const button = document.querySelector("easycredit-express-button");
			if (!(button instanceof HTMLElement)) {
				return;
			}

			button.style.display = "block";
			button.setAttribute(
				"amount",
				variation && variation.is_in_stock ? variation.display_price : 1,
			);
		});

		form.addEventListener("hide_variation", function () {
			const button = document.querySelector("easycredit-express-button");
			if (!(button instanceof HTMLElement)) {
				return;
			}
			button.style.display = "none";
		});
	});	
}

export const handleExpressButton = async (element: HTMLElement) => {
	/*
	if (
		element.closest(".wc-block-components-express-payment")
	) {
		return;
	}
	*/
	document.body.addEventListener(
		"submit",
		(e) => {
			if (
				e instanceof CustomEvent &&
				e.target &&
				(e.target as HTMLElement).tagName === "EASYCREDIT-EXPRESS-BUTTON"
			) {
				e.preventDefault();
				submitExpressForm(e);
			}
		},
		true,
	);

	handleVariationSwitch()
};