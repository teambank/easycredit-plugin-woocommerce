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
}


/*
    const widget = document.createElement("easycredit-widget");
    widget.setAttribute(
        "webshop-id",
        apiKey,
    );
    widget.setAttribute(
        "amount",
        amount,
    );

    const widgets = document.querySelectorAll(selector);
    for (let i = 0; i < widgets.length; i++) {
        const el = widgets[i];
        if (
            el instanceof HTMLElement &&
            el.parentNode &&
            window.getComputedStyle(el).visibility !== "hidden" &&
            el.style.opacity !== "0"
        ) {
            el.parentNode.insertBefore(widget.cloneNode(true), el.nextSibling);
            break;
        }
    }

    const variationsForm = document.querySelector("form.variations_form")
    if (!variationsForm) {
        return
    }
    variationsForm.addEventListener("show_variation", function (event) {
        if (!(event instanceof CustomEvent)) {
            return
        }

        const variation = event.detail;
        if (variation && variation.display_price) {
            widget.setAttribute("amount", variation.display_price);
        }
    }); 
}



	initWidget(container) {
		const selector = this.getMeta("widget-selector", container);
		if (selector === null) {
			return;
		}
		if (this.getMeta("api-key") === null) {
			return;
		}

		let processedSelector = this.processSelector(selector);

		let elements = container.querySelectorAll(processedSelector.selector);
		elements.forEach((element) => {
			this.applyWidget(container, element, processedSelector.attributes);
		});
	}


	getMeta(key, container = null, element = null) {
		let meta;

		if (container === null) {
			container = document;
		}

		const selector = "meta[name=easycredit-" + key + "]";

		if (element) {
			let box;
			if ((box = element.closest(".cms-listing-col"))) {
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
}
    */