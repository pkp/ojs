/**
 * @file js/classes/linkAction/OpenWindowRequest.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OpenWindowRequest
 * @ingroup js_classes_linkAction
 *
 * @brief A simple action request that will follow the given URL.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.linkAction.LinkActionRequest
	 *
	 * @param {jQueryObject} $linkActionElement The element the link
	 *  action was attached to.
	 * @param {Object} options Configuration of the link action
	 *  request.
	 */
	$.pkp.classes.linkAction.OpenWindowRequest =
			function($linkActionElement, options) {

		this.parent($linkActionElement, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.linkAction.OpenWindowRequest,
			$.pkp.classes.linkAction.LinkActionRequest);


	//
	// Public methods
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.linkAction.OpenWindowRequest.prototype.activate =
			function(element, event) {

		var options = this.getOptions();
		window.open(options.url);

		return /** @type {boolean} */ this.parent('activate', element, event);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
