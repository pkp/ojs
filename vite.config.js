import {defineConfig} from 'vite';
import Vue from '@vitejs/plugin-vue';
import path from 'path';
import copy from 'rollup-plugin-copy';
import i18nExtractKeys from './lib/pkp/tools/i18nExtractKeys.vite.js';

export default defineConfig(({mode}) => {
	// its very unclear how the plugin-vue is handling inProduction option
	// in any case its still heavily relying on NODE_ENV, thats why its being set
	// so for example the devtools support is enabled in development mode
	process.env.NODE_ENV = mode;
	return {
		plugins: [
			i18nExtractKeys({
				// existing in tpl files, to be replaced in future
				extraKeys: [
					'common.view',
					'common.close',
					'common.editItem',
					'stats.descriptionForStat',
					'common.commaListSeparator',
				],
			}),
			Vue({
				isProduction: mode === 'production',
				template: {
					compilerOptions: {
						// to keep vue2 behaviour where spaces between html tags are preserved
						whitespace: 'preserve',
					},
				},
			}),
			copy({
				targets: [
					{
						src: 'lib/ui-library/public/styles/tinymce/*',
						dest: 'lib/pkp/styles/tinymce',
					},
				],
				// run the copy task after writing the bundle
				hook: 'writeBundle',
			}),
		],
		publicDir: false,
		resolve: {
			alias: {
				'@': path.resolve(__dirname, 'lib/ui-library/src'),
				// use vue version with template compiler
				vue: 'vue/dist/vue.esm-bundler.js',
			},
		},
		build: {
			sourcemap: mode === 'development' ? 'inline' : false,
			target: ['chrome64', 'edge79', 'firefox67', 'safari12'],
			emptyOutDir: false,
			rollupOptions: {
				input: {
					build: './js/load.js',
				},
				output: {
					format: 'iife', // Set the format to IIFE
					entryFileNames: 'js/build.js',
					assetFileNames: (assetInfo) => {
						const info = assetInfo.name.split('.');
						const extType = info[info.length - 1];
						if (/\.(css)$/.test(assetInfo.name)) {
							return 'styles/build.css';
						}
						return `[name].${extType}`;
					},
					// Provide global variables to use in the UMD build
					// for externalized deps
					globals: {
						vue: 'pkp.Vue',
					},
				},
			},
			outDir: path.resolve(__dirname),
		},
	};
});
