(()=>{"use strict";const e=window.wp.blocks,t=window.wp.blockEditor,a=window.wp.components,n=window.wp.i18n,o=window.wp.element;(0,e.registerBlockType)("easycredit-ratenkauf/marketing-card",{edit:e=>{const r=e.attributes;if(r.cover)return(0,o.createElement)("img",{src:ecPluginUrl+"assets/img/easycredit-marketing-card.png"},null);const c=function(){return e.setAttributes({mediaURL:null,mediaID:null})};return(0,o.createElement)("div",(0,t.useBlockProps)({className:e.className}),(0,o.createElement)("easycredit-box-listing",{src:r.mediaURL},(0,o.createElement)(t.InspectorControls,null,(0,o.createElement)(a.PanelBody,{title:(0,n.__)("Image","woocommerce-gateway-ratenkaufbyeasycredit"),initialOpen:!0},(0,o.createElement)(t.MediaUpload,{onSelect:function(t){return e.setAttributes({mediaURL:t.url,mediaID:Number(t.id)})},type:"image",value:r.mediaID,render:e=>r.mediaID?(0,o.createElement)("div",{},(0,o.createElement)(a.Button,{className:"components-button editor-post-featured-image__preview",onClick:e.open,style:{marginBottom:"1em",height:"150px",backgroundImage:"url("+r.mediaURL+")",backgroundPosition:"center",backgroundRepeat:"no-repeat",backgroundSize:"contain"}}),(0,o.createElement)(a.Button,{className:"components-button is-secondary",onClick:e.open},(0,n.__)("Replace Image","woocommerce-gateway-ratenkaufbyeasycredit")),(0,o.createElement)(a.Button,{className:"components-button is-link is-destructive",onClick:c,style:{marginTop:"1em",display:"block"}},(0,n.__)("Remove Image","woocommerce-gateway-ratenkaufbyeasycredit"))):(0,o.createElement)(a.Button,{className:"components-button editor-post-featured-image__toggle",onClick:e.open},(0,n.__)("Upload Image","woocommerce-gateway-ratenkaufbyeasycredit"))})))))},save:e=>{const a=e.attributes;return(0,o.createElement)("div",t.useBlockProps.save({className:e.className}),(0,o.createElement)("easycredit-box-listing",{src:a.mediaURL},null))}})})();