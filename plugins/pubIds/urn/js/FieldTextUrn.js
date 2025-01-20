/**
 * @defgroup plugins_pubIds_urn_js
 */
/**
 * @file plugins/pubIds/urn/js/FieldTextUrn.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief A Vue.js component for URN text form field, that is used for custom suffixes, and that considers adding a check number.
 */

pkp.registry.registerComponent('FieldTextUrn', {
	name: 'FieldTextUrn',
	extends: pkp.registry.getComponent('PkpFieldText'),
	template:
		'<div class="pkpFormField pkpFormField--text pkpFormField--urn" :class="classes">' +
		'			<form-field-label' +
		'				:controlId="controlId"' +
		'				:label="label"' +
		'				:localeLabel="localeLabel"' +
		'				:isRequired="isRequired"' +
		'				:requiredLabel="t(\'common.required\')"' +
		'				:multilingualLabel="multilingualLabel"' +
		'			/>' +
		'			<div' +
		'				v-if="isPrimaryLocale && description"' +
		'				class="pkpFormField__description"' +
		'				v-html="description"' +
		'				:id="describedByDescriptionId"' +
		'			/>' +
		'			<div class="pkpFormField__control" :class="controlClasses">' +
		'				<input' +
		'					class="pkpFormField__input pkpFormField--text__input pkpFormField--urn__input"' +
		'					ref="input"' +
		'					v-model="currentValue"' +
		'					:type="inputType"' +
		'					:id="controlId"' +
		'					:name="localizedName"' +
		'					:aria-describedby="describedByIds"' +
		'					:aria-invalid="!!errors.length"' +
		'					:required="isRequired"' +
		'					:style="inputStyles"' +
		'				/>' +
		'				<pkp-button' +
		'					v-if="applyCheckNumber"' +
		'					@click.prevent="addCheckNumber"' +
		'					:disabled="currentValue.length === 0"' +
		'				>' +
		'					{{ addCheckNumberLabel }}' +
		'				</pkp-button>' +
		'				<field-error' +
		'					v-if="errors.length"' +
		'					:id="describedByErrorId"' +
		'					:messages="errors"' +
		'				/>' +
		'			</div>' +
		'		</div>',
	props: {
		addCheckNumberLabel: {
			type: String,
			required: true,
		},
		urnPrefix: {
			type: String,
			required: true,
		},
		applyCheckNumber: {
			type: Boolean,
			required: true,
		},
	},
	methods: {
		/**
		 * Add a check number to the end of the URN
		 */
		addCheckNumber() {
			this.currentValue += $.pkp.plugins.generic.urn.getCheckNumber(
				this.currentValue || '',
				this.urnPrefix,
			);
		},
	},
});
