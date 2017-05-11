/**
 * @file js/pages/header/HeaderHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HeaderHandler
 * @ingroup js_pages_index
 *
 * @brief Handler for the site header.
 *
 */
(function($) {

	/** @type {Object} */
	$.pkp.pages = $.pkp.pages || { header: { } };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $headerElement The HTML element encapsulating
	 *  the header.
	 * @param {{requestedPage: string,
	 *  fetchUnreadNotificationsCountUrl: string}} options Handler options.
	 */
	$.pkp.pages.header.HeaderHandler =
			function($headerElement, options) {

		this.options_ = options;
		this.parent($headerElement, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.header.HeaderHandler,
			$.pkp.classes.Handler);


	/**
	 * Site handler options.
	 * @private
	 * @type {{requestedPage: string}?}
	 */
	$.pkp.pages.header.HeaderHandler.prototype.options_ = null;


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
