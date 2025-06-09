/**
 * As starting point, this is just to build vue runtime, which can be requested for Reader UI #11468
 */
import {defineConfig} from 'vite';
import Vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig(({mode}) => {
	// its very unclear how the plugin-vue is handling inProduction option
	// in any case its still heavily relying on NODE_ENV, thats why its being set
	// so for example the devtools support is enabled in development mode
	process.env.NODE_ENV = mode;
	return {
		plugins: [
			Vue({
				isProduction: mode === 'production',
			}),
		],
		publicDir: false,
		resolve: {
			alias: {
				'@': path.resolve(__dirname, 'lib/ui-library/src'),
				// use vue version with template compiler
				vue: 'vue/dist/vue.esm-bundler.js',
			},
			// https://github.com/vitejs/vite/discussions/15906
			dedupe: ['pinia', 'vue'],
		},
		build: {
			sourcemap: mode === 'development' ? 'inline' : false,
			target: ['chrome66', 'edge79', 'firefox67', 'safari12'],
			emptyOutDir: false,
			cssCodeSplit: false,
			rollupOptions: {
				input: {
					build: './js/load_frontend.js',
				},
				output: {
					format: 'iife', // Set the format to IIFE
					entryFileNames: 'js/build_frontend.js',
					assetFileNames: (assetInfo) => {
						const info = assetInfo.name.split('.');
						const extType = info[info.length - 1];
						if (/\.(css)$/.test(assetInfo.name)) {
							return 'styles/build_frontend.css';
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
