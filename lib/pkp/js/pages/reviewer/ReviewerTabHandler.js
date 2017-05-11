/**
 * @defgroup js_controllers_tab_catalogEntry
 */
/**
 * @file js/pages/reviewer/ReviewerTabHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerTabHandler
 * @ingroup js_pages_reviewer
 *
 * @brief A subclass of TabHandler for handling the reviewer tabs.
 */
(function($) {

	/** @type {Object} */
	$.pkp.pages.reviewer =
			$.pkp.pages.reviewer || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.TabHandler
	 *
	 * @param {jQueryObject} $tabs A wrapped HTML element that
	 *  represents the tabbed interface.
	 * @param {Object} options Handler options.
	 */
	$.pkp.pages.reviewer.ReviewerTabHandler =
			function($tabs, options) {

		this.parent($tabs, options);

		this.reviewStep_ = options.reviewStep;

		// Attach the tabs grid refresh handler.
		this.bind('setStep', this.setStepHandler);

		this.getHtmlElement().tabs('option', 'disabled',
				this.getDisabledSteps(this.reviewStep_));
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.reviewer.ReviewerTabHandler,
			$.pkp.controllers.TabHandler);


	//
	// Private Properties
	//
	/**
	 * Review steps completed so far (read-only).
	 * @private
	 * @type {number?}
	 */
	$.pkp.pages.reviewer.ReviewerTabHandler.
			prototype.reviewStep_ = null;


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
	 * @param {number} stepNumber The new step number to view.
	 */
	$.pkp.pages.reviewer.ReviewerTabHandler.prototype.
			setStepHandler = function(sourceElement, event, stepNumber) {

		this.getHtmlElement().tabs('option', 'disabled',
				this.getDisabledSteps(stepNumber));
		this.getHtmlElement().tabs('option', 'active', stepNumber - 1);
	};


	/**
	 * Get a list of permitted tab indexes for the given review step
	 * number.
	 * @param {number} stepNumber The review step number (1-based).
	 * @return {Object} An array of permissible tab indexes (0-based).
	 */
	$.pkp.pages.reviewer.ReviewerTabHandler.prototype.
			getDisabledSteps = function(stepNumber) {

		switch (stepNumber) {
			case 1: return [1, 2, 3];
			case 2: return [2, 3];
			case 3: return [3];
			case 4: return [];
		}
		throw new Error('Illegal review step number.');
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
