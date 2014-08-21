/**
 * @defgroup js_pages_search
 */
/**
 * @file js/pages/search/SearchFormHandler.js
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SearchFormHandler
 *
 * @brief Form handler that handles the search form. It checks whether
 *  at least one search query term has been entered before submitting
 *  the form. It also handles instant search (if enabled).
 */
(function($) {

	/** @type {Object} */
	$.pkp.pages = $.pkp.pages || {};


	/** @type {Object} */
	$.pkp.pages.search = $.pkp.pages.search || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.FormHandler
	 *
	 * @param {jQueryObject} $form The wrapped HTML form element.
	 * @param {Object} options Configuration of the form handler.
	 */
	$.pkp.pages.search.SearchFormHandler = function($form, options) {
		// Focus the main query field and select all text.
		$form.find('input[name="query"]').focus().select();

		// Configure the form handler.
		options.submitHandler = this.submitForm;
		options.trackFormChanges = false;
		options.transformButtons = false;
		this.parent($form, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.search.SearchFormHandler,
			$.pkp.controllers.form.FormHandler);


	//
	// Public methods
	//
	/**
	 * Internal callback called after form validation to handle form
	 * submission.
	 *
	 * @param {Object} validator The validator plug-in.
	 * @param {HTMLElement} formElement The wrapped HTML form.
	 */
	/*jslint unparam: true*/
	$.pkp.pages.search.SearchFormHandler.prototype.submitForm =
			function(validator, formElement) {
		var $form, allBlank, formFields, i, max;

		$form = this.getHtmlElement();

		formFields = [
			'query', 'authors', 'title', 'abstract', 'discipline', 'subject',
			'type', 'coverage', 'indexTerms', 'suppFiles', 'galleyFullText'];
		for (i = 0, max = formFields.length; i < max; i++) {
			allBlank = $form.find('input[name="' + formFields[i] + '"]').val() == '';
			if (!allBlank) {
				break;
			}
		}

		if (allBlank) {
			alert($.pkp.locale.search_noKeywordError);
			return;
		}

		this.submitFormWithoutValidation(validator);
	};
	/*jslint unparam: false*/

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
