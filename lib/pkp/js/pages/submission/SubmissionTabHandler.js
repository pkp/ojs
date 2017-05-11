/**
 * @defgroup js_controllers_tab_catalogEntry
 */
/**
 * @file js/pages/submission/SubmissionTabHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionTabHandler
 * @ingroup js_pages_submission
 *
 * @brief A subclass of TabHandler for handling the submission tabs.
 */
(function($) {

	/** @type {Object} */
	$.pkp.pages.submission =
			$.pkp.pages.submission || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.TabHandler
	 *
	 * @param {jQueryObject} $tabs A wrapped HTML element that
	 *  represents the tabbed interface.
	 * @param {Object} options Handler options.
	 */
	$.pkp.pages.submission.SubmissionTabHandler =
			function($tabs, options) {

		this.parent($tabs, options);

		this.submissionProgress_ = options.submissionProgress;
		this.cancelUrl_ = options.cancelUrl;
		this.cancelConfirmText_ = options.cancelConfirmText;

		// Attach handlers.
		this.bind('setStep', this.setStepHandler);
		this.bind('formCanceled', this.formCanceledHandler);

		this.getHtmlElement().tabs('option', 'disabled',
				this.getDisabledSteps(this.submissionProgress_));
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.submission.SubmissionTabHandler,
			$.pkp.controllers.TabHandler);


	//
	// Private Properties
	//
	/**
	 * The submission's progress
	 * @private
	 * @type {number?}
	 */
	$.pkp.pages.submission.SubmissionTabHandler.
			prototype.submissionProgress_ = null;


	/**
	 * The cancel URL
	 * @private
	 * @type {string?}
	 */
	$.pkp.pages.submission.SubmissionTabHandler.
			prototype.cancelUrl_ = null;


	/**
	 * The cancel confirmation text
	 * @private
	 * @type {string?}
	 */
	$.pkp.pages.submission.SubmissionTabHandler.
			prototype.cancelConfirmText_ = null;


	//
	// Public methods
	//
	/**
	 * This listens for events from the contained form. It moves to the
	 * next tab.
	 *
	 * @param {HTMLElement} sourceElement The parent DIV element
	 *  which contains the tabs.
	 * @param {Event} event The triggered event (gridRefreshRequested).
	 * @param {number} submissionProgress The new submission progress.
	 */
	$.pkp.pages.submission.SubmissionTabHandler.prototype.
			setStepHandler = function(sourceElement, event, submissionProgress) {

		this.getHtmlElement().tabs('option', 'disabled',
				this.getDisabledSteps(submissionProgress));
		this.getHtmlElement().tabs('option', 'active', submissionProgress - 1);
	};


	/**
	 * Handle form cancellation events.
	 * @param {HTMLElement} sourceElement The parent DIV element
	 *  which contains the tabs.
	 * @param {Event} event The triggered event (gridRefreshRequested).
	 * @param {number} submissionProgress The new submission progress.
	 */
	$.pkp.pages.submission.SubmissionTabHandler.prototype.
			formCanceledHandler = function(sourceElement, event, submissionProgress) {

		if (confirm(this.cancelConfirmText_)) {
			window.location = this.cancelUrl_;
		}
	};


	/**
	 * Get a list of permitted tab indexes for the given submission step
	 * number.
	 * @param {number} submissionProgress The submission step number (1-based) or
	 * 0 for completion.
	 * @return {Object} An array of permissible tab indexes (0-based).
	 */
	$.pkp.pages.submission.SubmissionTabHandler.prototype.
			getDisabledSteps = function(submissionProgress) {

		switch (parseInt(submissionProgress, 10)) {
			case 0: return []; // Completed
			case 1: return [1, 2, 3, 4, 5];
			case 2: return [2, 3, 4, 5];
			case 3: return [3, 4, 5];
			case 4: return [4, 5];
			case 5: return [];
		}
		throw new Error('Illegal submission step number!');
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
