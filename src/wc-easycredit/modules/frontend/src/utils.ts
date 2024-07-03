export const watchForSelector = function (selector, cb) {
	const observer = new MutationObserver(function (mutations) {
		mutations.forEach(function (mutation) {
			mutation.addedNodes.forEach(function (node) {
				if (node.nodeType !== Node.ELEMENT_NODE) {
					return;
				}
				if (!(node instanceof HTMLElement)) {
					return;
				}
				if (node.tagName === selector.toUpperCase()) {
					return cb(node);
				}

				const el = node.querySelector(selector);
				if (el) {
					return cb(el);
				}
			});
		});
	});
	observer.observe(document, { subtree: true, childList: true });
};


export async function waitForComponentReady(
	selector: string,
): Promise<Element | null> {	
    const isHydrated = (component) => {
		return component instanceof HTMLElement &&
			component.classList.contains("hydrated")
	}

	return new Promise((resolve) => {
		let component = document.querySelector(selector);
		if (component === null) {
			return
		}
		if (isHydrated(component)) {
			return resolve(component);
		}

		const observer = new MutationObserver((mutations) => {
			mutations.forEach((mutation) => {
				if (mutation.type !== "attributes") {
					return
				}
				component = document.querySelector(selector);
				if (isHydrated(component)) {
					observer.disconnect();
					return resolve(component);
				}
			});
		});
		observer.observe(component, { attributes: true });
	});
}

/*
export async function waitForComponentReady(
	component: HTMLElement,
): Promise<void> {
	return new Promise((resolve) => {
		const checkHydrated = () => {
			if (
				component.shadowRoot &&
				component.shadowRoot?.childNodes.length > 0
			) {
				resolve();
			} else {
				setTimeout(checkHydrated, 100); // Check again in 100ms
			}
		};
		checkHydrated();
	});
}
*/

export const replicateForm = (
	buyForm: HTMLFormElement,
	additionalData: Record<string, string>,
): HTMLFormElement | false => {
	if (!(buyForm instanceof HTMLFormElement)) {
		return false;
	}

	const action = buyForm.getAttribute("action");
	const method = buyForm.getAttribute("method");

	if (!action || !method) {
		return false;
	}

	const form = document.createElement("form");
	form.setAttribute("action", action);
	form.setAttribute("method", method);
	form.style.display = "none";

	const formData = new FormData(buyForm);
	for (const [key, value] of Object.entries(additionalData)) {
		formData.set(key, value);
	}

	for (const key of formData.keys()) {
		const field = document.createElement("input");
		field.setAttribute("type", "hidden");
		field.setAttribute("name", key);
		field.setAttribute("value", formData.get(key) as string); // TypeScript type assertion
		form.appendChild(field);
	}

	document.body.appendChild(form);

	return form;
};

export const waitForLoadEvent = (): Promise<void> => {
	return new Promise((resolve) => {
		window.addEventListener("load", () => {
			resolve();
		});
	});
};
