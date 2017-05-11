/**
 * @file js/classes/linkAction/EventAction.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EventAction
 * @ingroup js_classes_linkAction
 *
 * @brief A simple action request that triggers a Javascript event.
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
	$.pkp.classes.linkAction.EventAction =
			function($linkActionElement, options) {
		this.parent($linkActionElement, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.linkAction.EventAction,
			$.pkp.classes.linkAction.LinkActionRequest);


	//
	// Public methods
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.linkAction.EventAction.prototype.activate =
			function(element, event) {
		$(this.options.target).trigger(this.options.event,
				/** @type {Array} */ (this.options));
		return /** @type {boolean} */ (this.parent('activate', element, event));
	};


	/**
	 * Determine whether or not the link action should be debounced.
	 * @return {boolean} Whether or not to debounce the link action.
	 */
	$.pkp.classes.linkAction.EventAction.prototype.shouldDebounce =
			function() {
		return false;
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
