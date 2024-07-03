/* eslint-env jquery */
/* global wc_easycredit_config */
jQuery(function ($) {
	// eslint-disable-next-line camelcase
	if (typeof wc_easycredit_config === "undefined") {
		return;
	}

	const prefix = "wc_easycredit_";
	const getBaseUrl = function (action) {
		return (
			wc_easycredit_config.url + "?action=" + prefix + action // eslint-disable-line camelcase
		);
	};

	$("#woocommerce_easycredit_api_verify_credentials").click(function () {
		const button = $(this);
		button.prop("disabled", true);
		const apiKey = $("#woocommerce_easycredit_api_key").val();
		const apiToken = $("#woocommerce_easycredit_api_token").val();
		const apiSignature = $("#woocommerce_easycredit_api_signature").val();

		$.getJSON(
			getBaseUrl("verify_credentials"),
			{
				api_key: apiKey,
				api_token: apiToken,
				api_signature: apiSignature,
			},
			(r) => {
				button.prop("disabled", false);
				window.alert(r.msg); // eslint-disable-line no-alert
			},
		);
	});

	let customUploader;
	const targets = $('input[name$="_src"]');
	const showChosenImages = (elems) => {
		elems.each(function () {
			const src = $(this).val();

			$(this).siblings(".ec-img").remove();
			$(
				'<div class="ec-img"><img src="' +
					src +
					'"><a href="#void" class="ec-delete-img">Remove image</a><br><a href="#void" class="btn btn-primary ec-upload-img">Upload Image</a></div>',
			).insertAfter($(this));
		});
	};

	showChosenImages(targets);

	$(".easycredit-marketing .form-table").on(
		"click",
		".ec-upload-img",
		function (e) {
			e.preventDefault();

			const target = $(this)
				.closest(".form-table")
				.find('input[name$="_src"]');

			if (!customUploader) {
				customUploader = wp.media.frames.file_frame = wp.media({
					title: "Choose Image",
					button: {
						text: "Choose Image",
					},
					multiple: false,
				});
			}

			customUploader.off("select");
			customUploader.on("select", () => {
				const attachment = customUploader
					.state()
					.get("selection")
					.first()
					.toJSON();
				target.val(attachment.url);
				showChosenImages(target);
			});

			customUploader.open();
		},
	);
	$(".easycredit-marketing .form-table").on(
		"click",
		".ec-delete-img",
		function (e) {
			e.preventDefault();

			const target = $(this)
				.closest(".form-table")
				.find('input[name$="_src"]');
			target.val("");
			showChosenImages(targets);
		},
	);

	const getTabs = () => {
		const tabs = document.querySelectorAll(
			".easycredit-marketing__tabs .easycredit-marketing__tab",
		);

		return tabs;
	};
	const getTabContents = () => {
		const tabContents = document.querySelectorAll(
			".easycredit-marketing__tab-content",
		);

		return tabContents;
	};
	const selectTab = (target) => {
		const tabs = getTabs();
		const tabContents = getTabContents();

		tabs.forEach((tab) => {
			if ($(tab).attr("data-target") === target) {
				$(tab).addClass("active");
			} else {
				$(tab).removeClass("active");
			}
		});

		tabContents.forEach((content) => {
			if ($(content).attr("data-tab") === target) {
				$(content).addClass("active");
			} else {
				$(content).removeClass("active");
			}
		});
	};
	const initTabs = () => {
		const tabs = getTabs();

		tabs.forEach((tab) => {
			$(tab).on("click", function () {
				const target = $(this).attr("data-target");
				selectTab(target);
			});
		});
	};

	initTabs();
});
