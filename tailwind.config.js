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
			dark: '#222222',
			'on-light': '#01354F',
			light: '#777777',
			lightest: '#FFFFFF',
		},
		borderColor: {
			dark: '#696969',
			light: '#DDDDDD',
			darkest: '#000000',
		},
		borderRadius: {
			DEFAULT: '4px',
		},
		boxShadow: {
			DEFAULT: '0 0 4px rgba(0, 0, 0, 0.5);',
			down: '',
		},
		colors: {
			primary: '#006798',
			white: '#FFFFFF',
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
			transparent: 'transparent',
		},
		fontFamily: {
			// this sets default font
			sans: ['"Noto Sans"', ...defaultTheme.fontFamily.sans],
		},
		fontSize: {
			'xs-normal': [
				'0.625rem' /** 10px */,
				{
					lineHeight: '0.75rem',
					fontWeight: '400',
				},
			],
			'sm-light': [
				'0.6875rem' /** 11px */,
				{
					lineHeight: '0.875rem',
					fontWeight: '300',
				},
			],
			'sm-normal': [
				'0.6875rem' /** 11px */,
				{
					lineHeight: '0.875rem',
					fontWeight: '400',
				},
			],
			'base-normal': [
				'0.75rem' /** 12px */,
				{
					lineHeight: '1rem',
					fontWeight: '400',
				},
			],
			'lg-normal': [
				'0.875rem' /** 14px */,
				{
					lineHeight: '1.25rem',
					fontWeight: '400',
				},
			],
			'lg-medium': [
				'0.875rem' /** 14px */,
				{
					lineHeight: '1.25rem',
					fontWeight: '500',
				},
			],
			'lg-semibold': [
				'0.875rem' /** 14px */,
				{
					lineHeight: '1.25rem',
					fontWeight: '600',
				},
			],
			'lg-bold': [
				'0.875rem' /** 14px */,
				{
					lineHeight: '1.25rem',
					fontWeight: '700',
				},
			],
			'xl-medium': [
				'1rem' /** 16px */,
				{
					lineHeight: '1.5rem',
					fontWeight: '500',
				},
			],
			'xl-bold': [
				'1rem' /** 16px */,
				{
					lineHeight: '1.5rem',
					fontWeight: '700',
				},
			],
			'2xl-bold': [
				'1.125rem' /** 18px */,
				{
					lineHeight: '1.75rem',
					fontWeight: '700',
				},
			],
			'3xl-normal': [
				'1.25rem' /** 20px */,
				{
					lineHeight: '1.75rem',
					fontWeight: '400',
				},
			],
			'3xl-medium': [
				'1.25rem' /** 20px */,
				{
					lineHeight: '1.75rem',
					fontWeight: '500',
				},
			],
			'3xl-bold': [
				'1.25rem' /** 20px */,
				{
					lineHeight: '1.75rem',
					fontWeight: '700',
				},
			],
			'4xl-bold': [
				'1.5rem' /** 24px */,
				{
					lineHeight: '2rem',
					fontWeight: '700',
				},
			],
			'5xl-bold': [
				'2.25rem' /** 36px */,
				{
					lineHeight: '2.5rem',
					fontWeight: '700',
				},
			],
		},
	},
	plugins: [],
};
