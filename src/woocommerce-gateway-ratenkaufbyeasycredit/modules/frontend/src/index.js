/* eslint-env jquery */
jQuery( function ( $ ) {
	const widgetSelector = $( 'meta[name=easycredit-widget-selector]' ).attr(
		'content'
	);

	const widget = $( '<easycredit-widget />' ).attr( {
		'webshop-id': $( 'meta[name=easycredit-api-key]' ).attr( 'content' ),
		amount: $( 'meta[name=easycredit-widget-price]' ).attr( 'content' ),
	} );
	$( widgetSelector )
		.filter( function () {
			return (
				$( this ).css( 'visibility' ) !== 'hidden' &&
				$( this ).css( 'opacity' ) !== 0
			);
		} )
		.first()
		.after( widget );

	$( 'form.variations_form' ).on(
		'show_variation',
		function ( event, variation ) {
			if ( variation.display_price ) {
				widget
					.get( 0 )
					.setAttribute( 'amount', variation.display_price );
			}
		}
	);

	$( '.woocommerce-checkout' ).on( 'change', '#billing_company', function () {
		if ( $( this ).closest( '.woocommerce-billing-fields' ).length === 0 ) {
			return;
		}
		$( this ).trigger( 'update_checkout' );
	} );

	const onHydrated = ( selector, cb ) => {
		if ( ! document.querySelector( selector ) ) {
			return;
		}

		window.setTimeout( () => {
			if (
				! document
					.querySelector( selector )
					.classList.contains( 'hydrated' )
			) {
				return onHydrated( selector, cb );
			}
			cb( selector );
		}, 50 );
	};

	const watchForSelector = ( selector, cb ) => {
		const observer = new window.MutationObserver( ( mutations ) => {
			mutations.forEach( function ( mutation ) {
				mutation.addedNodes.forEach( function ( node ) {
					if ( node.nodeType !== 1 ) {
						return;
					}

					let el;
					if ( ( el = node.querySelector( selector ) ) ) {
						cb( selector, el );
					}
				} );
			} );
		} );
		observer.observe( document, { subtree: true, childList: true } );
	};

	const handleShippingPaymentConfirm = ( selector ) => {
		onHydrated( selector, () => {
			$( selector ).submit( function ( e ) {
				const form = $( this ).closest('form');
				form.append(
					'<input type="hidden" name="easycredit[submit]" value="1" />'
				);
				form.append(
					'<input type="hidden" name="terms" value="On" />'
				);
				form.append(
					'<input type="hidden" name="legal" value="On" />'
				);
				if ( e.detail && e.detail.numberOfInstallments ) {
					form.append(
						'<input type="hidden" name="easycredit[number-of-installments]" value="' +
							e.detail.numberOfInstallments +
							'" />'
					);
				}
				form.submit();

				return false;
			} );

			$( 'form.checkout' ).on( 'checkout_place_order', () => {
				if (
					! $( 'easycredit-checkout' ).is( ':visible' ) ||
					! $( 'easycredit-checkout' ).prop( 'isActive' ) ||
					$( 'easycredit-checkout' ).prop( 'paymentPlan' ) !== '' ||
					$( 'easycredit-checkout' ).prop( 'alert' ) !== ''
				) {
					return true;
				}

				if (
					$( this ).find( 'input[name="easycredit[submit]"]' )
						.length > 0
				) {
					return true;
				}

				$( selector )
					.get( 0 )
					.dispatchEvent( new Event( 'openModal' ) );
				return false;
			} );
			$( document.body ).on( 'checkout_error', () => {
				$( selector )
					.get( 0 )
					.dispatchEvent( new Event( 'closeModal' ) );
			} );
		} );
	};

	watchForSelector(
		'form[name=checkout] easycredit-checkout, form#order_review easycredit-checkout',
		handleShippingPaymentConfirm
	);
	onHydrated(
		'form[name=checkout] easycredit-checkout, form#order_review easycredit-checkout',
		handleShippingPaymentConfirm
	);

	const replicateForm = ( buyForm, additionalData ) => {
		if ( ! buyForm ) {
			return false;
		}

		const form = document.createElement( 'form' );
		form.setAttribute( 'action', buyForm.getAttribute( 'action' ) );
		form.setAttribute( 'method', buyForm.getAttribute( 'method' ) );
		form.style.display = 'none';

		const formData = new FormData( buyForm );
		for ( const prop in additionalData ) {
			formData.set( prop, additionalData[ prop ] );
		}

		for ( const key of formData.keys() ) {
			const field = document.createElement( 'input' );
			field.setAttribute( 'name', key );
			field.setAttribute( 'value', formData.get( key ) );
			form.append( field );
		}

		document.querySelector( 'body' ).append( form );

		return form;
	};

	const handleExpressButton = ( selector ) => {
		// exclude buttons handled by wooCommerce Blocks
		if (
			$( selector ).closest( '.wc-block-components-express-payment' )
				.length > 0
		) {
			return;
		}

		onHydrated( selector, () => {
			$( selector ).submit( () => {
				let form = $( this ).closest( '.summary' ).find( 'form.cart' );
				if ( form.length === 0 ) {
					form = $( 'body' ).find( 'form.cart' );
				}

				const addToCartButton = document.querySelector(
					'button[name="add-to-cart"], button.single_add_to_cart_button'
				);
				if ( addToCartButton ) {
					const additional = {
						'easycredit-express': 1,
					};
					if ( addToCartButton.getAttribute( 'value' ) ) {
						additional[ 'add-to-cart' ] =
							addToCartButton.getAttribute( 'value' );
					}

					replicateForm( form.get( 0 ), additional ).submit();
					return;
				}

				if (
					$( this ).closest( '.wc-proceed-to-checkout' ).length > 0
				) {
					window.location.href = $( this ).data( 'url' );
					return;
				}
				/* eslint-disable no-alert */
				window.alert(
					'Der easyCredit-Ratenkauf konnte nicht gestartet werden.'
				);
			} );
		} );
	};

	const $form = $( 'form.variations_form' );
	$form.on( 'show_variation', ( event, variation, purchasable ) => {
		const button = $( 'easycredit-express-button' ).show().get( 0 );
		if ( button ) {
			button.setAttribute(
				'amount',
				purchasable && variation.is_in_stock
					? variation.display_price
					: 1
			);
		}
	} );
	$form.on( 'hide_variation', () => {
		$( 'easycredit-express-button' ).hide();
	} );

	watchForSelector( 'easycredit-express-button', handleExpressButton );
	onHydrated( 'easycredit-express-button', handleExpressButton );

	const styleCardListing = () => {
		const card = document.querySelector(
			'easycredit-box-listing.easycredit-box-listing-adjusted'
		);

		if ( card ) {
			const siblings = ( n ) =>
				[ ...n.parentElement.children ].filter( ( c ) => c !== n );
			const siblingsCard = siblings( card );

			const cardWidth = siblingsCard[ 0 ].clientWidth;
			const cardHeight = siblingsCard[ 0 ].clientHeight;
			const cardClasses = siblingsCard[ 0 ].classList;

			card.style.width = cardWidth + 'px';
			card.style.height = cardHeight + 'px';
			card.style.visibility = 'hidden';
			card.classList = card.classList + ' ' + cardClasses;

			if ( siblingsCard[ 0 ].tagName === 'LI' ) {
				card.style.display = 'list-item';
				card.style.listStyle = 'none';

				if ( card.parentElement.tagName === 'UL' ) {
					card.parentElement.classList =
						card.parentElement.classList +
						' easycredit-card-columns-adjusted';
				}
			}
		}
	};

	const styleCardListingHydrated = () => {
		const card = document.querySelector(
			'easycredit-box-listing.easycredit-box-listing-adjusted'
		);

		if ( card ) {
			card.shadowRoot.querySelector( '.ec-box-listing' ).style.maxWidth =
				'100%';
			card.shadowRoot.querySelector( '.ec-box-listing' ).style.height =
				'100%';
			card.shadowRoot.querySelector(
				'.ec-box-listing__image'
			).style.minHeight = '100%';
			card.style.visibility = '';
		}
	};

	const positionCardInListing = () => {
		const card = document.querySelector( 'easycredit-box-listing' );

		if ( card ) {
			const siblings = ( n ) =>
				[ ...n.parentElement.children ].filter( ( c ) => c !== n );
			const siblingsCard = siblings( card );

			const position = card.getAttribute( 'position' );
			const previousPosition =
				typeof position === 'undefined' ? null : Number( position - 1 );
			const appendAfterPosition =
				typeof position === 'undefined' ? null : Number( position - 2 );

			if ( ! position || previousPosition <= 0 ) {
				// do nothing
			} else if ( appendAfterPosition in siblingsCard ) {
				siblingsCard[ appendAfterPosition ].after( card );
			} else {
				card.parentElement.append( card );
			}
		}
	};

	styleCardListing();
	onHydrated( 'easycredit-box-listing', styleCardListingHydrated );
	positionCardInListing();
} );
