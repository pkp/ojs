/**
 * @defgroup js_controllers_tab_workflow
 */
/**
 * @file js/controllers/tab/workflow/WorkflowTabHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowTabHandler
 * @ingroup js_controllers_tab_workflow
 *
 * @brief A subclass of TabHandler for handling requests to load stages
 * of the workflow.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.tab.workflow =
			$.pkp.controllers.tab.workflow || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.TabHandler
	 *
	 * @param {jQueryObject} $tabs A wrapped HTML element that
	 *  represents the tabbed interface.
	 * @param {Object} options Handler options.
	 */
	$.pkp.controllers.tab.workflow.WorkflowTabHandler =
			function($tabs, options) {

		var pageUrl, stage, pattern, i, tabAnchors, matches;
		this.parent($tabs, options);

		pageUrl = document.location.toString();
		matches = pageUrl.match('workflow/([^/]+)/');
		if (matches) {
			stage = matches[1];
			tabAnchors = $tabs.find('li a');
			for (i = 0; i < tabAnchors.length; i++) {
				pattern = new RegExp(stage);
				if (tabAnchors[i].getAttribute('class').match(pattern)) {
					options.selected = i;
				}
			}
		}
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.tab.workflow.WorkflowTabHandler,
			$.pkp.controllers.TabHandler);

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
