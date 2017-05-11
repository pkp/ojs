/**
 * @defgroup js_controllers_informationCenter
 */
// Create the modal namespace.
jQuery.pkp.controllers.informationCenter =
			jQuery.pkp.controllers.informationCenter || { };


/**
 * @file js/controllers/informationCenter/NotesHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotesHandler
 * @ingroup js_controllers_informationCenter
 *
 * @brief Information center "notes" tab handler.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $notesDiv A wrapped HTML element that
	 *  represents the "notes" interface element.
	 * @param {Object} options Tabbed modal options.
	 */
	$.pkp.controllers.informationCenter.NotesHandler =
			function($notesDiv, options) {
		this.parent($notesDiv, options);

		// Store the list fetch URLs for later
		this.fetchNotesUrl_ = options.fetchNotesUrl;
		this.fetchPastNotesUrl_ = options.fetchPastNotesUrl;
		// Bind for changes in the note list (e.g.  new note or delete)
		this.bind('formSubmitted', this.handleRefreshNoteList);

		// Load a list of the current notes.
		this.loadPastNoteList_();
		this.loadNoteList_();
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.informationCenter.NotesHandler,
			$.pkp.classes.Handler
	);


	//
	// Private properties
	//
	/**
	 * The URL to be called to fetch a list of notes.
	 * @private
	 * @type {string}
	 */
	$.pkp.controllers.informationCenter.NotesHandler.
			prototype.fetchNotesUrl_ = '';


	/**
	 * The URL to be called to fetch a list of prior notes.
	 * @private
	 * @type {string}
	 */
	$.pkp.controllers.informationCenter.NotesHandler.
			prototype.fetchPastNotesUrl_ = '';


	//
	// Public methods
	//
	/**
	 * Handle the "note added" event triggered by the
	 * note form whenever a new note is added.
	 *
	 * @param {$.pkp.controllers.form.AjaxFormHandler} callingForm The form
	 *  that triggered the event.
	 * @param {Event} event The upload event.
	 */
	$.pkp.controllers.informationCenter.NotesHandler.
			prototype.handleRefreshNoteList = function(callingForm, event) {
		$(callingForm).find('[id^="newNote"]').val('');
		this.loadNoteList_();
	};


	//
	// Private methods
	//
	$.pkp.controllers.informationCenter.NotesHandler.prototype.
			loadNoteList_ = function() {

		$.get(this.fetchNotesUrl_, this.callbackWrapper(this.setNoteList_), 'json');
	};

	$.pkp.controllers.informationCenter.NotesHandler.prototype.
			setNoteList_ = function(formElement, jsonData) {

		var processedJsonData = this.handleJson(jsonData);

		$('#notesList').replaceWith(processedJsonData.content);
		this.getHtmlElement().find('.showMore, .showLess').
				bind('click', this.switchViz);

		// Initialize an accordion for the "past notes" list, if it's
		// available (e.g. for a file information center).
		if (!$('#notesAccordion').hasClass('ui-accordion')) {
			$('#notesAccordion').accordion({ heightStyle: 'content', animate: 200 });
		} else {
			// this is a refresh.  Since the accordion exists, we must destroy
			// and then recreate it or the content looks unstyled.
			$('#notesAccordion')
					.accordion('destroy')
					.accordion({ heightStyle: 'content', animate: 200 });
		}
	};


	$.pkp.controllers.informationCenter.NotesHandler.prototype.
			loadPastNoteList_ = function() {

		// Only attempt to load the past note list if it's in the UI
		if ($('#pastNotesList').length) {
			$.get(this.fetchPastNotesUrl_,
					this.callbackWrapper(this.setPastNoteList_), 'json');
		}
	};


	$.pkp.controllers.informationCenter.NotesHandler.prototype.
			setPastNoteList_ = function(formElement, jsonData) {

		var processedJsonData = this.handleJson(jsonData);
		$('#pastNotesList').replaceWith(jsonData.content);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
