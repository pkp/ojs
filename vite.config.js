import {defineConfig} from 'vite';
import Vue from '@vitejs/plugin-vue';
import path from 'path';
import copy from 'rollup-plugin-copy';

export default defineConfig({
	plugins: [
		Vue(),
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
		// TODO conditionally for dev mode
		sourcemap: 'inline',
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
});
