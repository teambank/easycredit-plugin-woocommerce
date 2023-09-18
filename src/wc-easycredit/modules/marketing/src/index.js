import { registerBlockType } from '@wordpress/blocks';
import { MediaUpload, InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';

registerBlockType( 'easycredit-ratenkauf/marketing-card', {
	edit: ( props ) => {
		const attributes = props.attributes;

		if ( attributes.cover ) {
			return createElement(
				'img',
				{
					src:
						ecPluginUrl +
						'assets/img/easycredit-marketing-card.png',
				},
				null
			);
		}

		const onSelectImage = function ( media ) {
			return props.setAttributes( {
				mediaURL: media.url,
				mediaID: Number( media.id ),
			} );
		};

		const onRemoveImage = function () {
			return props.setAttributes( {
				mediaURL: null,
				mediaID: null,
			} );
		};

		return createElement(
			'div',
			useBlockProps( { className: props.className } ),
			createElement(
				'easycredit-box-listing',
				{
					src: attributes.mediaURL,
				},
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{
							title: __(
								'Image',
								'woocommerce-gateway-ratenkaufbyeasycredit'
							),
							initialOpen: true,
						},
						createElement( MediaUpload, {
							onSelect: onSelectImage,
							type: 'image',
							value: attributes.mediaID,
							render: ( obj ) => {
								if ( ! attributes.mediaID ) {
									return createElement(
										Button,
										{
											className:
												'components-button editor-post-featured-image__toggle',
											onClick: obj.open,
										},
										__(
											'Upload Image',
											'woocommerce-gateway-ratenkaufbyeasycredit'
										)
									);
								}
								return createElement(
									'div',
									{},
									createElement( Button, {
										className:
											'components-button editor-post-featured-image__preview',
										onClick: obj.open,
										style: {
											marginBottom: '1em',
											height: '150px',
											backgroundImage:
												'url(' +
												attributes.mediaURL +
												')',
											backgroundPosition: 'center',
											backgroundRepeat: 'no-repeat',
											backgroundSize: 'contain',
										},
									} ),
									createElement(
										Button,
										{
											className:
												'components-button is-secondary',
											onClick: obj.open,
										},
										__(
											'Replace Image',
											'woocommerce-gateway-ratenkaufbyeasycredit'
										)
									),
									createElement(
										Button,
										{
											className:
												'components-button is-link is-destructive',
											onClick: onRemoveImage,
											style: {
												marginTop: '1em',
												display: 'block',
											},
										},
										__(
											'Remove Image',
											'woocommerce-gateway-ratenkaufbyeasycredit'
										)
									)
								);
							},
						} )
					)
				)
			)
		);
	},
	save: ( props ) => {
		const attributes = props.attributes;

		return createElement(
			'div',
			useBlockProps.save( { className: props.className } ),
			createElement(
				'easycredit-box-listing',
				{
					src: attributes.mediaURL,
				},
				null
			)
		);
	},
} );