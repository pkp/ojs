/* eslint-env node */
require('@rushstack/eslint-patch/modern-module-resolution');

module.exports = {
	root: true,
	env: {
		node: true,
	},
	extends: [
		'plugin:vue/vue3-essential',
		'eslint:recommended',
		'@vue/eslint-config-prettier/skip-formatting',
	],
	globals: {
		$: true,
		pkp: true,
	},
	parserOptions: {
		ecmaVersion: 'latest',
	},
	rules: {
		'vue/multi-word-component-names': 'off',
		'no-unused-vars': [
			'error',
			{
				args: 'none',
			},
		],
	},
};
