const getMeta = (key, container: HTMLElement | Document | null = null, element: HTMLElement | null = null) => {
		let meta;

		if (container === null) {
			container = document;
		}

		const selector = "meta[name=easycredit-" + key + "]";

		if (element instanceof HTMLElement) {
			let box;
			if ((box = element.closest("li.product"))) {
				if ((meta = box.querySelector(selector))) {
					return meta.content;
				}
			}
		}
		if ((meta = container.querySelector(selector))) {
			return meta.content;
		}
		return null;
	}

const processSelector = (selector) => {
    const regExp = /(.+) easycredit-widget(\[.+?\])$/;

    let match;
    if ((match = selector.match(regExp))) {
        const attributes = match[2]
            .split("]")
            .map((item) => item.slice(1).split("="))
            .filter(([k, v]) => k)
            .reduce((acc, [k, v]) => ({ ...acc, [k]: v }), {});

        return {
            selector: match[1],
            attributes: attributes,
        };
    }
    return {
        selector: selector,
    };
}

const applyWidget = (container, element, attributes) => {
	let amount = getMeta("amount", container, element);

	if (null === amount || isNaN(amount)) {
		const priceContainer = element.parentNode;
		amount =
			priceContainer && priceContainer.querySelector("[itemprop=price]")
				? priceContainer.querySelector("[itemprop=price]").content
				: null;
	}

	if (null === amount || isNaN(amount)) {
		return;
	}

	let widget = document.createElement("easycredit-widget");
	widget.setAttribute("webshop-id", getMeta("api-key"));
	widget.setAttribute("amount", amount);
	widget.setAttribute("payment-types", getMeta('payment-types'));

	if (attributes) {
		for (const [name, value] of Object.entries(attributes)) {
			widget.setAttribute(name, value as string);
		}
	}
	element.appendChild(widget);
};

export const handleWidget = () => {

    const selector = getMeta('widget-selector')
    const apiKey = getMeta('api-key')

    if (!selector ||
        !apiKey
    ) {
        return
    }

    let processedSelector = processSelector(selector);
    let elements = document.querySelectorAll(processedSelector.selector);
    elements.forEach((element) => {
        applyWidget(document, element, processedSelector.attributes);
    });
	handleVariationSwitch();
}

const handleVariationSwitch = () => {
	const forms = document.querySelectorAll("form.variations_form");
	forms.forEach((form) => {
		jQuery(form).on("show_variation", function ( event, variation ) {
			const widget = event.currentTarget.closest('.product.product-type-variable')?.querySelector('easycredit-widget');
			if (!widget) {
				return;
			}

			if (variation?.display_price) {
				widget.setAttribute("amount", variation.is_in_stock ? variation.display_price : 1);
			}
		});
	});
}