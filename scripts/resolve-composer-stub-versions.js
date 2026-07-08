/**
 * Map CI matrix WordPress versions to installable php-stubs/wordpress-stubs
 * versions. wp-env can run WordPress 7.0 while Composer stubs still lag behind.
 */
function resolveWordPressStubVersion(wordpressVersion) {
	const [major] = String(wordpressVersion).split(".").map(Number);

	if (major >= 7) {
		return "6.9.4";
	}

	return String(wordpressVersion);
}

if (require.main === module) {
	const wordpressVersion = process.argv[2];

	if (!wordpressVersion) {
		console.error("Usage: node scripts/resolve-composer-stub-versions.js <wordpress-version>");
		process.exit(1);
	}

	process.stdout.write(resolveWordPressStubVersion(wordpressVersion));
}

module.exports = {
	resolveWordPressStubVersion,
};
