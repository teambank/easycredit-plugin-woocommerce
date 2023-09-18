import { useRef, useEffect } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const config = getSetting( 'ratenkaufbyeasycredit_data' );

const ExpressButton = ( props ) => {
	const ecCheckoutButton = useRef( null );

	/*
	 * submit checkout if easycredit-checkout triggers submit event
	 */
	useEffect( () => {
		if ( ! ecCheckoutButton.current ) {
			return;
		}
		ecCheckoutButton.current.addEventListener( 'submit', () => {
			window.location.href = config.expressUrl;
		} );
	}, [ ecCheckoutButton ] );

	const amount = props.billing.cartTotal.value / 100;
	return (
		<easycredit-express-button
			ref={ ecCheckoutButton }
			webshop-id={ decodeEntities( config.apiKey ) }
			amount={ amount }
		></easycredit-express-button>
	);
};

const methodConfiguration = {
	name: 'ratenkaufbyeasycredit_express',
	content: <ExpressButton />,
	edit: <ExpressButton />,
	canMakePayment: () => {
		return config.enabled;
	},
	paymentMethodId: config.id,
};

export default methodConfiguration;
