jQuery((function(e){let t;e("#woocommerce_easycredit_api_verify_credentials").click((function(){const t=e(this);t.prop("disabled",!0);const a=e("#woocommerce_easycredit_api_key").val(),i=e("#woocommerce_easycredit_api_token").val(),c=e("#woocommerce_easycredit_api_signature").val();e.getJSON(wc_easycredit_config.url+"?action=wc_easycredit_verify_credentials",{api_key:a,api_token:i,api_signature:c},(e=>{t.prop("disabled",!1),window.alert(e.msg)}))}));const a=e('input[name$="_src"]'),i=t=>{t.each((function(){const t=e(this).val();e(this).siblings(".ec-img").remove(),e('<div class="ec-img"><img src="'+t+'"><a href="#void" class="ec-delete-img">Remove image</a><br><a href="#void" class="btn btn-primary ec-upload-img">Upload Image</a></div>').insertAfter(e(this))}))};i(a),e(".easycredit-marketing .form-table").on("click",".ec-upload-img",(function(a){a.preventDefault();const c=e(this).closest(".form-table").find('input[name$="_src"]');t||(t=wp.media.frames.file_frame=wp.media({title:"Choose Image",button:{text:"Choose Image"},multiple:!1})),t.off("select"),t.on("select",(()=>{const e=t.state().get("selection").first().toJSON();c.val(e.url),i(c)})),t.open()})),e(".easycredit-marketing .form-table").on("click",".ec-delete-img",(function(t){t.preventDefault(),e(this).closest(".form-table").find('input[name$="_src"]').val(""),i(a)}));const c=()=>document.querySelectorAll(".easycredit-marketing__tabs .easycredit-marketing__tab");c().forEach((t=>{e(t).on("click",(function(){(t=>{const a=c(),i=document.querySelectorAll(".easycredit-marketing__tab-content");a.forEach((a=>{e(a).attr("data-target")===t?e(a).addClass("active"):e(a).removeClass("active")})),i.forEach((a=>{e(a).attr("data-tab")===t?e(a).addClass("active"):e(a).removeClass("active")}))})(e(this).attr("data-target"))}))})),e("#woocommerce_easycredit_api_key").length>0&&(elBillPayment=e("#ecbill .bill"),"undefined"!=typeof ecMethodsStatus?("no"===ecMethodsStatus.easycredit_ratenkauf&&elBillPayment[0].classList.add("easycredit_ratenkauf-not-active"),"no"===ecMethodsStatus.easycredit_rechnung&&elBillPayment[0].classList.add("easycredit_rechnung-not-active")):console.error("ecMethodsStatus is not defined."),(e=>{if(0!==e.length)return fetch("https://ratenkauf.easycredit.de/api/payment/v3/webshop/"+e).then((e=>e.ok?e.json():Promise.reject(e))).then((e=>e)).catch((e=>{console.error("Error fetching webshop info:",e)}))})(e("#woocommerce_easycredit_api_key").val()).then((t=>{elBillPayment=e("#ecbill .bill"),!0===t.billPaymentActive?e("#easycredit-activate-invoice-button-inactive").remove():(e("#easycredit-activate-invoice-button-active").remove(),elBillPayment[0].classList.add("easycredit_rechnung-not-allowed"))})))}));