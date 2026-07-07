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
	if (document.readyState === "complete") {
		return Promise.resolve();
	}

	return new Promise((resolve) => {
		window.addEventListener("load", () => resolve(), { once: true });
	});
};

export const getEasycreditCheckoutFromEvent = (
	event: Event,
): HTMLElement | null => {
	if (!(event instanceof CustomEvent)) {
		return null;
	}

	const path =
		typeof event.composedPath === "function" ? event.composedPath() : [event.target];

	for (const node of path) {
		if (node instanceof HTMLElement && node.tagName === "EASYCREDIT-CHECKOUT") {
			return node;
		}
	}

	return null;
};
