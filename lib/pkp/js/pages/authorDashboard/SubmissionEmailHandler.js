/**
 * @defgroup js_pages_authorDashboard
 */


/**
 * @file js/pages/authorDashboard/SubmissionEmailHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEmailHandler
 * @ingroup js_pages_authorDashboard
 *
 * @brief Handler for reading monograph emails within the author dashboard.
 */
(function($) {

	/** @type {Object} */
	$.pkp.pages.authorDashboard = $.pkp.pages.authorDashboard || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.linkAction.LinkActionHandler
	 *
	 * @param {jQueryObject} $submissionEmailContainer The container for
	 *  the monograph email link.
	 * @param {Object} options Handler options.
	 */
	$.pkp.pages.authorDashboard.SubmissionEmailHandler =
			function($submissionEmailContainer, options) {

		this.parent($submissionEmailContainer, options);

		$submissionEmailContainer.find('a[id^="submissionEmail"]').click(
				this.callbackWrapper(this.activateAction));
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.authorDashboard.SubmissionEmailHandler,
			$.pkp.controllers.linkAction.LinkActionHandler);


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
