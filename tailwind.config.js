/** @type {import('tailwindcss').Config} */

const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
	content: ['./index.html', './**/*.vue', './**/*.tpl'],
	theme: {
		backgroundColor: {
			dark: '#002C40',
			medium: '#EAEDEE',
			lightest: '#FFFFFF',
			blur: 'rgba(0,0,0,0.5)',
		},
		textColor: {
			base: '#222222',
			'on-light': '#01354F',
			light: '#777777',
		},
		borderColor: {
			dark: '#696969',
			light: '#DDDDDD',
			darkest: '#000000',
		},
		borderRadius: {
			DEFAULT: '2px',
		},
		colors: {
			primary: '#006798',
			'state-error': '#D00A0A',
			'state-success': '#00B24E',
			'action-negative': '#D00A6C',
			'stage-desk-review': '#9B6FF8',
			'stage-in-review': '#EA9B32',
			'stage-copyediting': '#F66AAF',
			'stage-production': '#4AC7E2',
			'stage-scheduled-for-publishing': '#DED15D',
			'stage-incomplete-submission': '#777777',
			'stage-published': '#00B24E',
			'stage-declined': '#D00A0A',
			'profile-1': '#AB7D94',
			'profile-2': '#598D70',
			'profile-3': '#9B7CDC',
			'profile-4': '#89AAE0',
			'profile-5': '#EBDA68',
			'profile-6': '#BD726C',
		},
		fontFamily: {
			// this sets default font
			sans: ['"Noto Sans"', ...defaultTheme.fontFamily.sans],
		},
		fontSize: {
			'text-2xs-normal': [
				'0.625rem',
				{
					lineHeight: '0.75rem',
					fontWeight: '400',
				},
			],
			'text-xs-light': [
				'0.6875rem',
				{
					lineHeight: '0.875rem',
					fontWeight: '300',
				},
			],
			'text-xs-normal': [
				'0.6875rem',
				{
					lineHeight: '0.875rem',
					fontWeight: '400',
				},
			],
			'text-sm-normal': [
				'0.75rem',
				{
					lineHeight: '1rem',
					fontWeight: '400',
				},
			],
			'text-base-normal': [
				'0.875rem',
				{
					lineHeight: '1.25rem',
					fontWeight: '400',
				},
			],
			'text-base-medium': [
				'0.875rem',
				{
					lineHeight: '1.25rem',
					fontWeight: '500',
				},
			],
			'text-base-semibold': [
				'0.875rem',
				{
					lineHeight: '1.25rem',
					fontWeight: '600',
				},
			],
			'text-base-bold': [
				'0.875rem',
				{
					lineHeight: '1.25rem',
					fontWeight: '700',
				},
			],
			'text-lg-medium': [
				'1rem',
				{
					lineHeight: '1.5rem',
					fontWeight: '500',
				},
			],
			'text-lg-bold': [
				'1rem',
				{
					lineHeight: '1.5rem',
					fontWeight: '700',
				},
			],
			'text-xl-bold': [
				'1.125rem',
				{
					lineHeight: '1.75rem',
					fontWeight: '700',
				},
			],
			'text-2xl-normal': [
				'1.25rem',
				{
					lineHeight: '1.75rem',
					fontWeight: '400',
				},
			],
			'text-2xl-medium': [
				'1.25rem',
				{
					lineHeight: '1.75rem',
					fontWeight: '500',
				},
			],
			'text-2xl-bold': [
				'1.25rem',
				{
					lineHeight: '1.75rem',
					fontWeight: '700',
				},
			],
			'text-3xl-bold': [
				'1.5rem',
				{
					lineHeight: '2rem',
					fontWeight: '700',
				},
			],
			'text-4xl-bold': [
				'2.25rem',
				{
					lineHeight: '2.5rem',
					fontWeight: '700',
				},
			],
		},
	},
	plugins: [],
};
