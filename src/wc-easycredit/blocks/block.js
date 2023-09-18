( function ( blocks, blockEditor, element, i18n, components ) {
    var el = element.createElement;
    var __ = i18n.__;
    var MediaUpload = blockEditor.MediaUpload;
    var BlockControls = blockEditor.BlockControls;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var useBlockProps = blockEditor.useBlockProps;

    blocks.registerBlockType( 'easycredit-ratenkauf/marketing-card', {
        edit: function ( props ) {
            var attributes = props.attributes;

            if ( attributes.cover ) {
                return el( 'img', { 'src': ecPluginUrl + 'assets/img/easycredit-marketing-card.png' }, null );
            }

            var onSelectImage = function (media) {
                return props.setAttributes({
                    mediaURL: media.url,
                    mediaID: Number( media.id )
                })
            }

            var onRemoveImage = function () {
                return props.setAttributes({
                    mediaURL: null,
                    mediaID: null
                })
            }

            return el('div',
                useBlockProps( { className: props.className } ),
                el('easycredit-box-listing',
                    {
                        src: attributes.mediaURL
                    },
                    el(InspectorControls,
                        null,
                        el(PanelBody, { title: __( 'Image', 'woocommerce-gateway-ratenkaufbyeasycredit' ), initialOpen: true },
                            el(MediaUpload, {
                                onSelect: onSelectImage,
                                type: 'image',
                                value: attributes.mediaID,
                                render: function (obj) {
                                    if ( !attributes.mediaID ) {
                                        return el(
                                            components.Button, {
                                                className: 'components-button editor-post-featured-image__toggle',
                                                onClick: obj.open
                                            },
                                            __( 'Upload Image', 'woocommerce-gateway-ratenkaufbyeasycredit' )
                                        )
                                    } else {
                                        return el('div', {},
                                            el(components.Button, {
                                                className: 'components-button editor-post-featured-image__preview',
                                                onClick: obj.open,
                                                style: { marginBottom: '1em', height: '150px', backgroundImage: 'url(' + attributes.mediaURL + ')', backgroundPosition: 'center', backgroundRepeat: 'no-repeat', backgroundSize: 'contain' }
                                            }),
                                            el(components.Button, {
                                                    className: 'components-button is-secondary',
                                                    onClick: obj.open
                                                },
                                                __( 'Replace Image', 'woocommerce-gateway-ratenkaufbyeasycredit' )
                                            ),
                                            el(components.Button, {
                                                    className: 'components-button is-link is-destructive',
                                                    onClick: onRemoveImage,
                                                    style: { marginTop: '1em', display: 'block' }
                                                },
                                                __( 'Remove Image', 'woocommerce-gateway-ratenkaufbyeasycredit' )
                                            ),
                                        )
                                    }
                                }
                            })
                        ),
                    )
                )
            );
        },
        save: function ( props ) {
            var attributes = props.attributes;

            return el('div',
                useBlockProps.save( { className: props.className } ),
                el('easycredit-box-listing',
                    {
                        src: attributes.mediaURL
                    },
                    null
                )
            );
        },
    } );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element, window.wp.i18n, window.wp.components );
