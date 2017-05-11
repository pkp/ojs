/**
 * @defgroup js_controllers_modals_editorDecision_form
 */
/**
 * @file js/controllers/modals/editorDecision/form/EditorDecisionFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditorDecisionFormHandler
 * @ingroup js_controllers_modals_editorDecision_form
 *
 * @brief Handle editor decision forms.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.modals = $.pkp.controllers.modals ||
			{ editorDecision: {form: { } } };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {{
	 *  peerReviewUrl: string?
	 *  }} options form options
	 */
	$.pkp.controllers.modals.editorDecision.form.EditorDecisionFormHandler =
			function($form, options) {

		this.parent($form, options);

		if (options.peerReviewUrl !== null) {
			this.peerReviewUrl_ = options.peerReviewUrl;
			$('#importPeerReviews', $form).click(
					this.callbackWrapper(this.importPeerReviews));
		}
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.modals.editorDecision.form.EditorDecisionFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	//
	// Private properties
	//
	/**
	 * The URL of the "fetch peer reviews" operation.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.modals.editorDecision.form.EditorDecisionFormHandler.
			peerReviewUrl_ = null;


	//
	// Public methods
	//
	/**
	 * Retrieve reviews from the server.
	 *
	 * @param {HTMLElement} button The "import reviews" button.
	 * @param {Event} event The click event.
	 * @return {boolean} Return false to abort normal click event.
	 */
	$.pkp.controllers.modals.editorDecision.form.EditorDecisionFormHandler.
			prototype.importPeerReviews = function(button, event) {

		$.getJSON(this.peerReviewUrl_, this.callbackWrapper(this.insertPeerReviews));
		return false;
	};


	/**
	 * Insert the peer reviews that have been returned from the server
	 * into the form.
	 *
	 * @param {Object} ajaxOptions The options that were passed into
	 *  the AJAX call.
	 * @param {Object} jsonData The data returned from the server.
	 */
	$.pkp.controllers.modals.editorDecision.form.EditorDecisionFormHandler.
			prototype.insertPeerReviews = function(ajaxOptions, jsonData) {

		var processedJsonData = this.handleJson(jsonData),
				$form = this.getHtmlElement(),
				$textArea = $('textarea[id^="personalMessage"]', $form),
				editor = tinyMCE.get(/** @type {string} */ ($textArea.attr('id'))),
				currentContent = editor.getContent();

		if (processedJsonData !== false) {
			// Add the peer review text to the personal message to the author.
			currentContent = $textArea.val();
			editor.setContent(
					currentContent + processedJsonData.content + '<br>');
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
