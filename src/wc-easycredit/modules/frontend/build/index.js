(()=>{"use strict";const e=()=>new Promise((e=>{window.addEventListener("load",(()=>{e()}))})),t=async e=>{document.body.addEventListener("submit",(e=>{e instanceof CustomEvent&&e.target&&"EASYCREDIT-EXPRESS-BUTTON"===e.target.tagName&&(e.preventDefault(),function(e){let t;const n=e.target,r=n.closest(".summary");if(r instanceof HTMLElement&&(t=r.querySelector("form.cart")),t||(t=document.querySelector("form.cart")),t||(t=document.querySelector("form.woocommerce-cart-form")),!(t instanceof HTMLFormElement))return;const o=(e=>{let t={};e.express="1";for(let[n,r]of Object.entries(e))t["easycredit["+n+"]"]=r;return t})(e.detail),a=t.querySelector('button[name="add-to-cart"], button.single_add_to_cart_button');if(a){let e;return a.getAttribute("value")&&(o["add-to-cart"]=a.getAttribute("value")),void((e=((e,t)=>{if(!(e instanceof HTMLFormElement))return!1;const n=e.getAttribute("action"),r=e.getAttribute("method");if(!n||!r)return!1;const o=document.createElement("form");o.setAttribute("action",n),o.setAttribute("method",r),o.style.display="none";const a=new FormData(e);for(const[e,n]of Object.entries(t))a.set(e,n);for(const e of a.keys()){const t=document.createElement("input");t.setAttribute("type","hidden"),t.setAttribute("name",e),t.setAttribute("value",a.get(e)),o.appendChild(t)}return document.body.appendChild(o),o})(t,o))&&e.submit())}if(n.closest(".wc-proceed-to-checkout")&&n.dataset.url){const e=new URLSearchParams(o).toString();window.location.href=n.dataset.url+"?"+e}else window.alert("Die Express-Zahlung mit easyCredit konnte nicht gestartet werden."),console.error("easyCredit payment could not be started. Please check the integration.")}(e))}),!0),document.querySelectorAll("form.variations_form").forEach((e=>{jQuery(e).on("show_variation",(function(e,t){const n=document.querySelector("easycredit-express-button");n instanceof HTMLElement&&(n.style.display="block",n.setAttribute("amount",t&&t.is_in_stock?t.display_price:1))})),e.addEventListener("hide_variation",(function(){const e=document.querySelector("easycredit-express-button");e instanceof HTMLElement&&(e.style.display="none")}))}))},n=e=>document.querySelector('easycredit-checkout[payment-type="'+e+'"]'),r=(e,t,r)=>{jQuery(e).on("checkout_place_order_"+t,(()=>{const t=n(r);return!("none"!==t.style.display&&t.isActive&&!t.paymentPlan&&""===t.alert&&!e.querySelector('input[name="easycredit[submit]"]')&&(t.scrollIntoView({behavior:"smooth"}),"INSTALLMENT"===r&&t.dispatchEvent(new Event("openModal")),1))})),"INSTALLMENT"===r&&jQuery(document.body).on("checkout_error",(()=>{n(r).dispatchEvent(new Event("closeModal"))}))},o=(e,t=null,n=null)=>{let r;null===t&&(t=document);const o="meta[name=easycredit-"+e+"]";if(n instanceof HTMLElement){let e;if((e=n.closest("li.product"))&&(r=e.querySelector(o)))return r.content}return(r=t.querySelector(o))?r.content:null},a={easycredit_ratenkauf:"INSTALLMENT",easycredit_rechnung:"BILL"};(async()=>{await e(),t(document.querySelector("easycredit-express-button"))})(),(async()=>{await e();const t=document.querySelector("form.woocommerce-checkout");if(t){n=t,document.body.addEventListener("submit",(e=>{e instanceof CustomEvent&&e.target&&"EASYCREDIT-CHECKOUT"===e.target.tagName&&(e.preventDefault(),(e=>{const t=e.target.closest("form");if(!(t instanceof HTMLFormElement))return;const n=[{name:"easycredit[submit]",value:"1"},{name:"terms",value:"On"},{name:"legal",value:"On"}];e.detail&&e.detail.numberOfInstallments&&n.push({name:"easycredit[number-of-installments]",value:e.detail.numberOfInstallments}),n.forEach((e=>{const n=document.createElement("input");n.type="hidden",n.name=e.name,n.value=e.value,t.appendChild(n)})),jQuery(t).submit()})(e))}),!0),n.addEventListener("change",(e=>{const t=e.target;t instanceof Element&&t&&t.closest(".woocommerce-billing-fields")&&jQuery(t).trigger("update_checkout")}));for(const[e,n]of Object.entries(a))r(t,e,n)}var n})(),(async()=>{await e(),(async()=>{await(()=>{const e=document.querySelector("easycredit-box-listing.easycredit-box-listing-adjusted");if(!(e&&e instanceof HTMLElement&&e.parentElement))return;const t=[...e.parentElement.children].filter((t=>t!==e))[0],n=t.clientWidth,r=t.clientHeight,o=t.className;e.style.width=n+"px",e.style.height=r+"px",e.style.visibility="hidden",e.className=e.className+" "+o,"LI"===t.tagName&&(e.style.display="list-item",e.style.listStyle="none","UL"===e.parentElement.tagName&&(e.parentElement.className=e.parentElement.className+" easycredit-card-columns-adjusted"))})(),await(async()=>{await customElements.whenDefined("easycredit-box-listing");const e=document.querySelector("easycredit-box-listing.easycredit-box-listing-adjusted");if(!(e instanceof HTMLElement&&e.shadowRoot))return;e.style.visibility="";const t=e.shadowRoot.querySelector(".ec-box-listing");t instanceof HTMLElement&&(t.style.maxWidth="100%",t.style.height="100%");const n=e.shadowRoot.querySelector(".ec-box-listing__image");n instanceof HTMLElement&&(n.style.minHeight="100%")})(),await(()=>{const e=document.querySelector("easycredit-box-listing");if(!(e instanceof HTMLElement&&e.parentElement))return;const t=[...e.parentElement.children].filter((t=>t!==e)),n=e.getAttribute("position"),r=n?Number(n)-1:0,o=r?Number(n)-2:0;!n||r<=0||(t[o]?t[o].after(e):e.parentElement.append(e))})()})()})(),(async()=>{await e(),(()=>{const e=o("widget-selector"),t=o("api-key");if(!e||!t)return;let n=(e=>{let t;if(t=e.match(/(.+) easycredit-widget(\[.+?\])$/)){const e=t[2].split("]").map((e=>e.slice(1).split("="))).filter((([e,t])=>e)).reduce(((e,[t,n])=>({...e,[t]:n})),{});return{selector:t[1],attributes:e}}return{selector:e}})(e);document.querySelectorAll(n.selector).forEach((e=>{((e,t,n)=>{let r=o("amount",e,t);if(null===r||isNaN(r)){const e=t.parentNode;r=e&&e.querySelector("[itemprop=price]")?e.querySelector("[itemprop=price]").content:null}if(null===r||isNaN(r))return;let a=document.createElement("easycredit-widget");if(a.setAttribute("webshop-id",o("api-key")),a.setAttribute("amount",r),a.setAttribute("payment-types",o("payment-types")),n)for(const[e,t]of Object.entries(n))a.setAttribute(e,t);t.appendChild(a)})(document,e,n.attributes)}))})()})()})();