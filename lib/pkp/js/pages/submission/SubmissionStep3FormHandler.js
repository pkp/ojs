/**
 *
 *//**
* @defgroup js_pages_submission
*/
// Create the namespace.
jQuery.pkp.pages.submission = jQuery.pkp.pages.submission || { };

/**
* @file js/pages/submission/SubmissionStep3FormHandler.js
*
* Copyright (c) 2014-2017 Simon Fraser University
* Copyright (c) 2000-2017 John Willinsky
* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
*
* @class SubmissionStep3FormHandler
* @ingroup js_pages_submission
*
* @brief Handle the submission step 3 form.
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
	$.pkp.pages.submission.SubmissionStep3FormHandler =
			function($form, options) {

		this.parent($form, options);

		// Bind the handler for the "elements changed" event.
		this.bind('gridRefreshRequested', this.fetchChaptersGrid_);

		// Save supplied options
		this.authorsGridContainer_ = options.authorsGridContainer;
		this.chaptersGridContainer_ = options.chaptersGridContainer;
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.submission.SubmissionStep3FormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	//
	// Private properties
	//
	/**
	 * The id of the div where the chapters grid should go.
	 * @private
	 * @type {string}
	 */
	$.pkp.pages.submission.SubmissionStep3FormHandler.
			prototype.chaptersGridContainer_ = '';


	//
	// Private methods.
	//
	/**
	 * Handler the data changed event from the author's grid
	 * @private
	 * @param {$.pkp.pages.submission.SubmissionStep3FormHandler} submissionForm
	 * the Submission Form this is attached to.
	 * @param {Event} event A "gridRefreshRequested" event.
	 */
	$.pkp.pages.submission.SubmissionStep3FormHandler.
			prototype.fetchChaptersGrid_ = function(submissionForm, event) {
		// redraw the chapters grid if it was the authors grid.
		var $eventTarget = event.target;

		if ($eventTarget.id == this.authorsGridContainer_) {
			$('#' + this.chaptersGridContainer_).find('.pkp_controllers_grid')
					.trigger('dataChanged');
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
