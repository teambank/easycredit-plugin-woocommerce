// wp-env can start WordPress in Docker or in the Playground runtime.
// This project only uses Docker. The Playground CLI pulls a PHP-WASM
// build for every supported PHP version, which is several hundred megabytes.
//
// package.json pins @wordpress/env at 11.17.0 and applies
// patches/@wordpress__env@11.17.0.patch. wp-env writes its own Dockerfiles
// and Compose file, and `docker compose pull` fetches every declared image.
// The patch leaves out phpMyAdmin unless it is enabled, and skips the
// compiler toolchain and PHPUnit unless Xdebug, SPX, or the tests
// environment needs them. @wordpress/env 12.0.0 still builds those layers.
function readPackage(pkg) {
	if (pkg.name === "@wordpress/env" && pkg.dependencies) {
		delete pkg.dependencies["@wp-playground/cli"];
	}

	return pkg;
}

module.exports = {
	hooks: {
		readPackage,
	},
};
