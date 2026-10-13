/**
 * Map CI matrix WordPress and WooCommerce versions to installable php-stubs
 * packages. wp-env can run a release before Composer stubs publish the same tag.
 *
 * szepeviktor/phpstan-wordpress 1.x only allows wordpress-stubs through ^6.0,
 * so WordPress 7 jobs stay on 6.9.4 even though stubs 7.x are published.
 * WooCommerce 11 stubs still accept ^6.0.
 */

function compareVersions(left, right) {
	const leftParts = String(left)
		.split(".")
		.map((part) => parseInt(part, 10) || 0);
	const rightParts = String(right)
		.split(".")
		.map((part) => parseInt(part, 10) || 0);
	const length = Math.max(leftParts.length, rightParts.length);

	for (let index = 0; index < length; index++) {
		const diff = (leftParts[index] || 0) - (rightParts[index] || 0);
		if (diff !== 0) {
			return diff;
		}
	}

	return 0;
}

function resolveAgainstCeiling(version, ceiling) {
	const requested = String(version);
	if (
		compareVersions(requested, ceiling) > 0 ||
		requested.split(".").length < 3
	) {
		return ceiling;
	}

	return requested;
}

const WORDPRESS_STUB_PHPSTAN_CEILING = "6.9.4";

const WOOCOMMERCE_STUB_CEILINGS = {
	"11.2": "11.2.0",
};

function resolveWordPressStubVersion(wordpressVersion) {
	const version = String(wordpressVersion);
	const [major] = version.split(".");

	if (Number(major) >= 7) {
		return WORDPRESS_STUB_PHPSTAN_CEILING;
	}

	return version;
}

function resolveWooCommerceStubVersion(woocommerceVersion) {
	const version = String(woocommerceVersion);
	const [major, minor] = version.split(".");
	const ceiling = WOOCOMMERCE_STUB_CEILINGS[`${major}.${minor ?? "0"}`];

	if (!ceiling) {
		return version;
	}

	return resolveAgainstCeiling(version, ceiling);
}

if (require.main === module) {
	const args = process.argv.slice(2);

	if (args[0] === "--woocommerce") {
		const woocommerceVersion = args[1];

		if (!woocommerceVersion) {
			console.error(
				"Usage: node scripts/resolve-composer-stub-versions.js --woocommerce <woocommerce-version>",
			);
			process.exit(1);
		}

		process.stdout.write(resolveWooCommerceStubVersion(woocommerceVersion));
	} else {
		const wordpressVersion = args[0];
		const woocommerceVersion = args[1];

		if (!wordpressVersion) {
			console.error(
				"Usage: node scripts/resolve-composer-stub-versions.js <wordpress-version> [woocommerce-version]",
			);
			process.exit(1);
		}

		process.stdout.write(
			resolveWordPressStubVersion(wordpressVersion, woocommerceVersion),
		);
	}
}

module.exports = {
	resolveWordPressStubVersion,
	resolveWooCommerceStubVersion,
};
