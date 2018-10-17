/**
 * @defgroup plugins_generic_lucene_js
 */
/**
 * @file plugins/generic/lucene/js/LuceneAutocompleteHandler.js
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LuceneAutocompleteHandler
 * @ingroup plugins_generic_lucene_js
 *
 * @brief Controller for lucene autocomplete.
 */
(function($) {

	/** @type {Object} */
	$.pkp.plugins.generic.lucene = $.pkp.plugins.generic.lucene || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.AutocompleteHandler
	 *
	 * @param {jQueryObject} $autocompleteField the wrapped HTML input element.
	 * @param {Object} options options to be passed
	 *  into the jqueryUI SimpleSearchForm plugin.
	 */
	$.pkp.plugins.generic.lucene.LuceneAutocompleteHandler =
			function($autocompleteField, options) {

		options.minLength = 1;
		options.searchForm = options.searchForm || 'searchForm';
		this.searchForm_ = options.searchForm;
		this.parent($autocompleteField, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.plugins.generic.lucene.LuceneAutocompleteHandler,
			$.pkp.controllers.AutocompleteHandler);


	//
	// Private Properties
	//
	/**
	 * The text input inside the autocomplete div that holds the label.
	 * @private
	 * @type {string}
	 */
	$.pkp.plugins.generic.lucene.LuceneAutocompleteHandler.prototype.
			searchForm_ = '';


	//
	// Public Methods
	//
	/**
	 * Fetch autocomplete results.
	 * @param {HTMLElement} callingElement The calling HTML element.
	 * @param {Object} request The autocomplete search request.
	 * @param {Function} response The response handler function.
	 */
	$.pkp.plugins.generic.lucene.LuceneAutocompleteHandler.prototype.
			fetchAutocomplete = function(callingElement, request, response) {
		var $textInput;

		$textInput = this.textInput;
		$textInput.addClass('spinner');
		$.post(this.getAutocompleteUrl(), $('#' + this.searchForm_).serialize(),
				function(data) {
					$textInput.removeClass('spinner');
					response(data.content);
				}, 'json');
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
