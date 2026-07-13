const CLASSIC_CHECKOUT_SLUG = "classic-checkout";
const CLASSIC_CHECKOUT_CONTENT = "[woocommerce_checkout]";
const CLASSIC_CHECKOUT_OPTION = "easycredit_classic_checkout_page_id";

function parseOutput(output) {
	return String(output ?? "")
		.split("\n")
		.map((line) => line.trim())
		.filter(Boolean)
		.filter(
			(line) =>
				!line.startsWith("ℹ") &&
				!line.startsWith("✔") &&
				!line.includes("Ran `") &&
				!line.startsWith("Deprecated:") &&
				!line.startsWith("Success:"),
		)
		.pop();
}

function parsePageId(output) {
	const parsed = parseOutput(output);
	return parsed && /^\d+$/.test(parsed) ? parsed : "";
}

function runWpCliSafe(runWpCli, command) {
	try {
		return runWpCli(command);
	} catch {
		return "";
	}
}

/**
 * @param {(command: string) => string} runWpCli
 * @returns {"classic_checkout_created" | "classic_checkout_exists" | "classic_checkout_failed"}
 */
function ensureClassicCheckoutPage(runWpCli) {
	let pageId = parsePageId(
		runWpCli(
			`wp post list --post_type=page --name=${CLASSIC_CHECKOUT_SLUG} --field=ID --format=ids --posts_per_page=1`,
		),
	);

	if (!pageId) {
		const optionPageId = parsePageId(
			runWpCliSafe(runWpCli, `wp option get ${CLASSIC_CHECKOUT_OPTION}`),
		);
		if (optionPageId) {
			const optionSlug = parseOutput(
				runWpCliSafe(runWpCli, `wp post get ${optionPageId} --field=post_name`),
			);
			const optionStatus = parseOutput(
				runWpCliSafe(runWpCli, `wp post get ${optionPageId} --field=post_status`),
			);
			if (
				optionSlug === CLASSIC_CHECKOUT_SLUG &&
				optionStatus === "publish"
			) {
				pageId = optionPageId;
			}
		}
	}

	if (!pageId) {
		pageId = parsePageId(
			runWpCli(
				`wp post create --post_type=page --post_title="Klassische Kasse" --post_name="${CLASSIC_CHECKOUT_SLUG}" --post_content='${CLASSIC_CHECKOUT_CONTENT}' --post_status=publish --porcelain`,
			),
		);

		if (!pageId) {
			return "classic_checkout_failed";
		}

		runWpCli(`wp option update ${CLASSIC_CHECKOUT_OPTION} ${pageId}`);
		runWpCli("wp rewrite flush");
		return "classic_checkout_created";
	}

	runWpCli(
		`wp post update ${pageId} --post_content='${CLASSIC_CHECKOUT_CONTENT}' --post_status=publish`,
	);
	runWpCli(`wp option update ${CLASSIC_CHECKOUT_OPTION} ${pageId}`);
	runWpCli("wp rewrite flush");

	return "classic_checkout_exists";
}

module.exports = {
	CLASSIC_CHECKOUT_SLUG,
	CLASSIC_CHECKOUT_CONTENT,
	CLASSIC_CHECKOUT_OPTION,
	ensureClassicCheckoutPage,
};
