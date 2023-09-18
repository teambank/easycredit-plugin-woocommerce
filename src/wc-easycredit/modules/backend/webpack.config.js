const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	...{
		entry: {
			index: path.resolve( process.cwd(), 'src', 'index.js' ),
			styles: path.resolve( process.cwd(), 'src', 'styles.css' ),
		},
	},
	plugins: [
		...defaultConfig.plugins,
		new RemoveEmptyScriptsPlugin( {
			stage: RemoveEmptyScriptsPlugin.STAGE_AFTER_PROCESS_PLUGINS,
		} ),
		new CleanWebpackPlugin( {
			protectWebpackAssets: false,
			cleanAfterEveryBuildPatterns: [ 'styles-rtl.css' ],
		} ),
	],
};
