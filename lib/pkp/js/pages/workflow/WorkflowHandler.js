/**
 * @defgroup js_pages_workflow
 */
/**
 * @file js/pages/workflow/WorkflowHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowHandler
 * @ingroup js_pages_workflow
 *
 * @brief Base handler for the workflow pages.
 *
 */
(function($) {

	/** @type {Object} */
	$.pkp.pages.workflow = $.pkp.pages.workflow || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $workflowElement The HTML element encapsulating
	 *  the production page.
	 * @param {Object} options Handler options.
	 */
	$.pkp.pages.workflow.WorkflowHandler =
			function($workflowElement, options) {

		this.parent($workflowElement, options);

		this.bind('stageParticipantsChanged', this.handleStageParticipantsChanged_);
		this.bind('dataChanged', this.dataChangedHandler_);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.workflow.WorkflowHandler,
			$.pkp.classes.Handler);


	//
	// Private functions
	//
	/**
	 * Potentially refresh workflow content on participant change.
	 *
	 * @param {jQueryObject} callingElement The calling element.
	 *  that triggered the event.
	 * @param {Event} event The event.
	 * @private
	 */
	$.pkp.pages.workflow.WorkflowHandler.prototype.handleStageParticipantsChanged_ =
			function(callingElement, event) {
		// Find and reload editor decision action and progress bar.
		var $elements, $stageTabs, matches, cssClass, stageId, sourceUrl,
				$editorDecisionActions = this.getHtmlElement()
				.find('.editorDecisionActions'),
				$progressBar = this.getHtmlElement().find('#submissionProgressBarDiv'),
				$stageTabContainer = this.getHtmlElement().find('#stageTabs');

		$stageTabs = $stageTabContainer.find('li');
		$stageTabs.each(function(index) {
			if ($(this).hasClass('ui-state-active')) {
				cssClass = $(this).find('a').attr('class');
				matches = cssClass.match(/stageId(\d)/);
				if (matches) {
					stageId = matches[1];
					var handler = $.pkp.classes.Handler.getHandler($progressBar);
					sourceUrl = handler.getSourceUrl();
					handler.setSourceUrl(sourceUrl.replace(/stageId=\d/, 'stageId=' +
							stageId));
				}
				return false;
			}
		});

		$elements = $editorDecisionActions.add($progressBar);

		$elements.each(function() {
			var handler = $.pkp.classes.Handler.getHandler($(this));
			handler.reload();
		});
	};


	/**
	 * Potentially refresh contained grid.
	 *
	 * @param {jQueryObject} callingElement The calling element.
	 *  that triggered the event.
	 * @param {Event} event The event.
	 * @param {Object} eventData Event data.
	 * @private
	 */
	$.pkp.pages.workflow.WorkflowHandler.prototype.dataChangedHandler_ =
			function(callingElement, event, eventData) {

		var $childAnchors = $(event.target, this.getHtmlElement()).children('a'),
				$formatsGrid;

		if ($childAnchors.length &&
				$childAnchors.attr('id').match(/submissionEntry/)) {
			// Refresh the format grid on this page, if any.
			$formatsGrid = $('[id^="formatsGridContainer"]',
					this.getHtmlElement()).children('div');
			$formatsGrid.trigger('dataChanged', [eventData]);
			$formatsGrid.trigger('notifyUser', [$formatsGrid]);
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
