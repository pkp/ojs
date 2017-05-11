/**
 * @file js/classes/TinyMCEHelper.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TinyMCEHelper
 * @ingroup js_classes
 *
 * @brief TinyMCE helper methods
 */
(function($) {


	/**
	 * Helper singleton
	 * @constructor
	 *
	 * @extends $.pkp.classes.ObjectProxy
	 */
	$.pkp.classes.TinyMCEHelper = function() {
		throw new Error('Trying to instantiate the TinyMCEHelper singleton!');
	};


	//
	// Public static methods.
	//
	/**
	 * Get the list of variables and their descriptions for a specified field.
	 * @param {string} selector The textarea field's selector.
	 * @return {?Object} Map of variableName: variableDisplayName entries.
	 */
	$.pkp.classes.TinyMCEHelper.prototype.getVariableMap =
			function(selector) {

		var variablesEncoded = $(selector).attr('data-variables'),
				variablesParsed;

		// If we found the data attribute, decode and return it.
		if (variablesEncoded !== undefined) {
			return $.parseJSON(decodeURIComponent(
					/** @type {string} */ (variablesEncoded)));
		}

		// If we could not find the data attribute, return an empty list.
		return [];
	};


	/**
	 * Generate an element to represent a PKP variable (e.g. primary contact name
	 * in setup) within the TinyMCE editor.
	 * @param {string} variableSymbolic The variable symbolic name.
	 * @param {string} variableName The human-readable name for the variable.
	 * @return {jQueryObject} JQuery DOM representing the PKP variable.
	 */
	$.pkp.classes.TinyMCEHelper.prototype.getVariableElement =
			function(variableSymbolic, variableName) {

		return $('<div/>').append($('<span/>')
				.addClass('pkpTag mceNonEditable')
				.attr('data-symbolic', variableSymbolic)
				.text(variableName));
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
