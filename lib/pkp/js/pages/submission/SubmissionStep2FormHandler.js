/**
 * @defgroup js_pages_submission
 */
// Create the namespace.
jQuery.pkp.pages.submission = jQuery.pkp.pages.submission || { };

/**
 * @file js/pages/submission/SubmissionStep2FormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionStep2FormHandler
 * @ingroup js_pages_submission
 *
 * @brief Handle the submission step 2 form.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.pages.submission.SubmissionStep2FormHandler =
			function($form, options) {

		this.parent($form, options);

		this.bind('urlInDivLoaded', this.showFileUploadWizard_);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.submission.SubmissionStep2FormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	//
	// Private methods.
	//
	/**
	 * When the files grid is shown on step 2, click the 'add files'
	 *  link action.
	 * @private
	 * @param {HTMLElement} sourceElement The element that
	 *  issued the event.
	 * @param {Event} event The triggering event.
	 * @param {?string} data additional event data.
	 */
	$.pkp.pages.submission.SubmissionStep2FormHandler.
			prototype.showFileUploadWizard_ = function(sourceElement, event, data) {

		if (data == 'submissionFilesGridDiv') {
			$('#' + data).find('[id*="-addFile-button-"]').click();
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
