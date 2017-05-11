/**
 * @defgroup js_controllers_grid_queries
 */
/**
 * @file js/controllers/grid/queries/ReadQueryHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReadQueryHandler
 * @ingroup js_controllers_grid_queries
 *
 * @brief Handler for a "read query" modal
 *
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.grid.queries =
			$.pkp.controllers.grid.queries || { };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $containerElement The HTML element encapsulating
	 *  the carousel container.
	 * @param {Object} options Handler options.
	 */
	$.pkp.controllers.grid.queries.ReadQueryHandler =
			function($containerElement, options) {

		this.fetchNoteFormUrl_ = options.fetchNoteFormUrl;
		this.fetchParticipantsListUrl_ = options.fetchParticipantsListUrl;

		$containerElement.find('.openNoteForm a').click(
				this.callbackWrapper(this.showNoteFormHandler_));

		$containerElement.bind('dataChanged',
				this.callbackWrapper(this.reloadParticipantsList_));

		this.loadParticipantsList();
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.grid.queries.ReadQueryHandler,
			$.pkp.classes.Handler);


	//
	// Private properties
	//
	/**
	 * The URL to be called to fetch a new note form for a query.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.grid.queries.ReadQueryHandler.
			prototype.fetchNoteFormUrl_ = null;


	/**
	 * The URL to be called to fetch a list of participants.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.grid.queries.ReadQueryHandler.
			prototype.fetchParticipantsListUrl_ = null;


	//
	// Public methods
	//
	/**
	 * Load the participants list.
	 */
	$.pkp.controllers.grid.queries.ReadQueryHandler.prototype.
			loadParticipantsList = function() {
		$.get(this.fetchParticipantsListUrl_,
				this.callbackWrapper(this.showFetchedParticipantsList_), 'json');
	};


	//
	// Private methods
	//
	/**
	 * Event handler that is called when the "new note" button is clicked.
	 * @param {HTMLElement} element The checkbox input element.
	 * @private
	 */
	$.pkp.controllers.grid.queries.ReadQueryHandler.prototype.
			showNoteFormHandler_ = function(element) {
		$(element).parents('.openNoteForm').addClass('is_loading');
		$.get(this.fetchNoteFormUrl_,
				this.callbackWrapper(this.showFetchedNoteForm_), 'json');
	};


	/**
	 * Event handler that is called when the new note form is ready.
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @private
	 */
	$.pkp.controllers.grid.queries.ReadQueryHandler.prototype.
			showFetchedNoteForm_ = function(ajaxContext, jsonData) {

		var processedJsonData = this.handleJson(jsonData),
				$noteFormContainer = $('#newNotePlaceholder', this.getHtmlElement());

		$('.openNoteForm.is_loading', this.getHtmlElement()).remove();
		$noteFormContainer.html(processedJsonData.content);
	};


	/**
	 * Event handler that is called when the participants list fetch is complete.
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @private
	 */
	$.pkp.controllers.grid.queries.ReadQueryHandler.prototype.
			showFetchedParticipantsList_ = function(ajaxContext, jsonData) {

		var processedJsonData = this.handleJson(jsonData),
				$participantsListContainer = $(
				'#participantsListPlaceholder', this.getHtmlElement());

		$participantsListContainer.children().remove();
		$participantsListContainer.append(processedJsonData.content);
	};


	/**
	 * Handler to update the participants list on change.
	 * @param {HTMLElement} sourceElement The element that issued the
	 * "dataChanged" event.
	 * @param {Event} event The "dataChanged" event.
	 * @param {HTMLElement} triggerElement The element that triggered
	 * the "dataChanged" event.
	 * @private
	 */
	$.pkp.controllers.grid.queries.ReadQueryHandler.prototype.
			reloadParticipantsList_ = function(sourceElement, event, triggerElement) {
		this.loadParticipantsList();
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
