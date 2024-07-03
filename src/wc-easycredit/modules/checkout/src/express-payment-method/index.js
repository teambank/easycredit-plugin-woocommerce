import { useRef, useEffect } from "@wordpress/element";
import { decodeEntities } from "@wordpress/html-entities";
import { getSetting } from "@woocommerce/settings";

const getMethods = () => {
	return Object.fromEntries(
		Object.entries(getSetting("paymentMethodData")).filter(([key, val]) =>
			key.match(/^easycredit_/),
		),
	);
};

const methods = getMethods();
const config = methods.easycredit_ratenkauf;

const ExpressButton = (props) => {
	const ecCheckoutButton = useRef(null);

	/*
	 * submit checkout if easycredit-checkout triggers submit event
	 */
	useEffect(() => {
		if (!ecCheckoutButton.current) {
			return;
		}
		ecCheckoutButton.current.addEventListener("submit", () => {
			window.location.href = config.expressUrl;
		});
	}, [ecCheckoutButton]);

	const amount = props.billing.cartTotal.value / 100;

	return (
		<easycredit-express-button
			ref={ecCheckoutButton}
			webshop-id={decodeEntities(config.apiKey)}
			amount={amount}
			payment-types={getConfigProperties("paymentType").join(",")}
		></easycredit-express-button>
	);
};

const getConfigProperties = (propertyName) => {
	return Object.entries(methods).map((method) => method[1][propertyName]);
};

const methodConfiguration = {
	name: "easycredit",
	content: <ExpressButton />,
	edit: <ExpressButton />,
	canMakePayment: () => {
		return getConfigProperties("enabled").some(Boolean);
	},
};

export default methodConfiguration;
