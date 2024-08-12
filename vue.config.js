var path = require('path');
const CopyPlugin = require("copy-webpack-plugin");

module.exports = {
	css: {
		extract: {
			filename: '../styles/build.css'
		}
	},
	chainWebpack: config => {
		// Don't create an index.html file
		config.plugins.delete('html');
		config.plugins.delete('preload');
		config.plugins.delete('prefetch');

		// Don't copy the /public dir
		config.plugins.delete('copy');
	},
	configureWebpack: {
		// Don't create a map file for the js package
		devtool: false,
		entry: [
			'@/styles/_global.less',
			'./js/load.js'
		],
		optimization: {
			// Don't split vendor and app into separate JS files
			splitChunks: false
		},
		output: {
			filename: 'build.js',
			// Prevent lots of files from being created when running npm run dev
			hotUpdateChunkFilename: 'hot-updates/hot-update.js',
			hotUpdateMainFilename: 'hot-updates/hot-update.json'
		},
		plugins: [
			new CopyPlugin({
				patterns: [
					{
						from: 'lib/ui-library/public/styles/tinymce',
						to: '../lib/pkp/styles/tinymce'
					}
				]
			})
		],
		resolve: {
			alias: {
				'@': path.resolve(__dirname, 'lib/ui-library/src')
			}
		},
		watch: false
	},
	outputDir: path.resolve(__dirname, 'js'),
	runtimeCompiler: true,
	// Part of the vue2-dropzone library is not transpiled
	// as part of the normal build process, which results
	// in errors in < IE 11. This directive makes sure the
	// dependencies are included when babel transpiles code
	// See: https://github.com/rowanwins/vue-dropzone/issues/439
	// See: https://stackoverflow.com/a/58949645/1723499
	transpileDependencies: ['vue2-dropzone']
};
