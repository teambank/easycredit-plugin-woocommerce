import { useRef, useEffect } from '@wordpress/element';
import { useSelect } from "@wordpress/data";
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const config = getSetting( 'ratenkaufbyeasycredit_data' );

const Checkout = ( { billing, eventRegistration, activePaymentMethod } ) => {
	const { onCheckoutFail, onCheckoutValidation } = eventRegistration;

	const hasValidationErrors = useSelect((select) =>
		select(VALIDATION_STORE_KEY).hasValidationErrors()
	);

	const ecCheckout = useRef( null );
	const privacyApproved = useRef( false );

	const emulateSubmitCheckout = () => {
		document
			.querySelector( '.wc-block-components-checkout-place-order-button' )
			.dispatchEvent(
				new window.MouseEvent( 'click', { bubbles: true } )
			);
	};

	/*
	 * submit checkout if easycredit-checkout triggers submit event
	 */
	useEffect( () => {
		if ( ! ecCheckout.current ) {
			return;
		}
		ecCheckout.current.addEventListener( 'submit', () => {
			if (hasValidationErrors) {
				// checkout will not submit => reset submit button loading animation
				ecCheckout.current.dispatchEvent(new Event("closeModal"));
			}
			privacyApproved.current = true;
			emulateSubmitCheckout();
		} );
	}, [] );

	/*
	 * open privacy approval modal if main checkout submit button is clicked
	 */
	useEffect( () => {
		if ( activePaymentMethod !== config.id ) {
			return true;
		}

		const unsubscribe = onCheckoutValidation( () => {
			if ( ! ecCheckout.current ) {
				return true;
			}
			if ( privacyApproved.current ) {
				return true;
			}

			ecCheckout.current.dispatchEvent( new Event( 'openModal' ) );
			return {
				errorMessage: 'Bitte stimmen Sie der Datenübermittlung zu.',
			};
		} );
		return unsubscribe;
	}, [ onCheckoutValidation, activePaymentMethod, privacyApproved ] );

	useEffect( () => {
		if ( activePaymentMethod !== config.id ) {
			return true;
		}

		const unsubscribe = onCheckoutFail( () => {
			if ( ! ecCheckout.current ) {
				return;
			}

			ecCheckout.current.dispatchEvent( new Event( 'closeModal' ) );
		} );
		return unsubscribe;
	}, [ onCheckoutFail, activePaymentMethod ] );

	return (
		<easycredit-checkout
			ref={ ecCheckout }
			webshop-id={ decodeEntities( config.apiKey ) }
			amount={ billing.cartTotal.value / 100 }
		></easycredit-checkout>
	);
};

const methodConfiguration = {
	name: 'ratenkaufbyeasycredit',
	content: <Checkout />, // checkout view
	edit: <Checkout />, // admin view
	canMakePayment: () => {
		return config.enabled;
	},
	paymentMethodId: config.id,
	label: <easycredit-checkout-label></easycredit-checkout-label>,
	ariaLabel: 'easycredit',
	placeOrderButtonLabel: __( 'Continue to pay by installments' ),
};

export default methodConfiguration;
