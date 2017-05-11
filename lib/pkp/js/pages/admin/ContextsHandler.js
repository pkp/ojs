/**
 * @defgroup js_pages_admin
 */
/**
 * @file js/pages/admin/ContextsHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextsHandler
 * @ingroup js_pages_admin
 *
 * @brief Handler for the hosted contexts page.
 *
 */
(function($) {

	/** @type {Object} */
	$.pkp.pages.admin = $.pkp.pages.admin || { };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $contexts The HTML element encapsulating
	 *  the contexts page.
	 * @param {Object} options Handler options.
	 */
	$.pkp.pages.admin.ContextsHandler =
			function($contexts, options) {

		var $linkActionElement = $('#openWizard a');

		if ($linkActionElement) {
			// Hide the link to users.
			$linkActionElement.attr('style', 'display:none');
		}

		this.parent($contexts, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.admin.ContextsHandler,
			$.pkp.classes.Handler);


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
