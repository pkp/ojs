/**
 * @defgroup js_pages_search
 */
/**
 * @file js/pages/search/SearchFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
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
	$.pkp.pages.search = $.pkp.pages.search || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.FormHandler
	 *
	 * @param {jQueryObject} $form The wrapped HTML form element.
	 * @param {{
	 *  instantSearch: boolean
	 *  }} options Configuration of the search form handler.
	 */
	$.pkp.pages.search.SearchFormHandler = function($form, options) {
		// Focus the main query field and select all text in it.
		// NB: We have to check for two inputs to support
		// the special auto-complete fields.
		var $queryInput = $form.find('input[name="query_input"]');
		if ($queryInput.length === 0) {
			// Auto-complete is switched off.
			$queryInput = $form.find('input[name="query"]');
		}

		// Configure the form handler.
		options.submitHandler = this.submitForm;
		options.trackFormChanges = options.instantSearch;
		options.transformButtons = false;
		this.parent($form, options);

		// Configure instant search.
		if (options.instantSearch === true) {
			// Bind the instant search handler to key
			// events on all input fields.
			$(':input', $form)
				.keyup(this.callbackWrapper(this.instantSearch));
		}
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.search.SearchFormHandler,
			$.pkp.controllers.form.FormHandler);


	//
	// Protected properties
	//
	/**
	 * A timeout handler identifying the instant search timeout.
	 * @protected
	 * @type {Object}
	 */
	$.pkp.pages.search.SearchFormHandler.instantSearchTimeout = null;


	//
	// Public methods
	//
	/**
	 * Internal callback called after a form change event when
	 * instant search is active.
	 *
	 * @param {HTMLElement} formElement The form element that generated the event.
	 * @param {Event} event The "formChanged" event.
	 */
	$.pkp.pages.search.SearchFormHandler.prototype.instantSearch =
			function(formElement, event) {
		var searchHandler, $form;

		this.formChangesTracked = false;

		// Make sure that there is a minimum time lag
		// between two search requests. This avoids
		// request congestion when someone is typing
		// (or auto-selecting) fast.
		clearTimeout(this.instantSearchTimeout);
		this.instantSearchTimeout = setTimeout(
				this.callbackWrapper(
						function() {
							// Only trigger instant search
							// if we have at least 3 characters
							// in a field. This avoids irrelevant
							// search requests.
							if (this.hasData_(3)) {
								// Signal an instant search request to the search
								// handler.
								this.setInstantSearch_(true);
								$form = this.getHtmlElement();
								$.post(
										$form.attr('action'),
										$form.serialize(),
										this.callbackWrapper(
												this.handleInstantSearchResponse
										),
										'html'
								);
							}
						}
				),
				500 // Half a second timeout.
				);
	};


	/**
	 * Internal callback called after form validation to handle the
	 * response to a form submission.
	 *
	 * You can override this handler if you want to do custom handling
	 * of a form response.
	 *
	 * @param {HTMLElement} formElement The wrapped HTML form.
	 * @param {string} resultList The HTML returned from the server.
	 */
	$.pkp.pages.search.SearchFormHandler.prototype.handleInstantSearchResponse =
			function(formElement, resultList) {

		// Make sure that we actually got table content.
		if (resultList.trim().substr(0, 4) == '<tr>') {
			// Replace the results table.
			$('#results table.listing').html(resultList);
		}
	};


	/**
	 * Internal callback called after form validation to handle form
	 * submission.
	 *
	 * @param {Object} validator The validator plug-in.
	 * @param {HTMLElement} formElement The wrapped HTML form.
	 */
	$.pkp.pages.search.SearchFormHandler.prototype.submitForm =
			function(validator, formElement) {

		// Check that we have at least one search keyword.
		if (!this.hasData_(1)) {
			alert($.pkp.locale.search_noKeywordError);
			return;
		}

		// This is not an instant search request.
		this.setInstantSearch_(false);

		this.submitFormWithoutValidation(validator);
	};


	//
	// Private methods
	//
	/**
	 * Internal method checking whether fields contain data.
	 *
	 * @param {number} minLen The min length of a field entry
	 *  so that it is considered "non-blank".
	 * @return {boolean} True if the form contains data.
	 * @private
	 */
	$.pkp.pages.search.SearchFormHandler.prototype.hasData_ =
			function(minLen) {
		var $form, hasData = false, formFields, i, numFields, fieldLength;

		$form = this.getHtmlElement();

		// Run through all search fields and check whether at least one
		// of them contains a keyword longer than "minLen".
		formFields = [
			'query', 'authors', 'title', 'abstract', 'discipline', 'subject',
			'type', 'coverage', 'indexTerms', 'galleyFullText'
		];
		for (i = 0, numFields = formFields.length; i < numFields; i++) {
			fieldLength = $form.find('input[name="' + formFields[i] + '"]')
				.val().length;
			hasData = (fieldLength >= minLen);
			if (hasData) {
				break;
			}
		}

		return hasData;
	};


	/**
	 * Method to (create and) set the instant search flag.
	 *
	 * @param {boolean} flag The value of the instant search flag.
	 * @private
	 */
	$.pkp.pages.search.SearchFormHandler.prototype.setInstantSearch_ =
			function(flag) {

		var $instantSearch = $('#instantSearch');

		if ($instantSearch.length === 0 && flag === true) {
			// Add a hidden input field to signal an
			// instant search request to the search handler.
			$instantSearch = $('<input>').attr({
				type: 'hidden',
				id: 'instantSearch',
				name: 'instantSearch'
			}).appendTo(this.getHtmlElement());
		}

		if ($instantSearch.length > 0) {
			// Set the instant search input.
			$instantSearch.val(flag ? '1' : '0');
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
