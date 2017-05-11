/**
 * @file js/classes/features/CollapsibleGridFeature.js
 *
 * Copyright (c) 2016-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CollapsibleGridFeature
 * @ingroup js_classes_features
 *
 * @brief Adds collapse/expand functionality to grids.
 */
(function($) {


	/**
	 * @constructor
	 * @inheritDoc
	 * @extends $.pkp.classes.features.Feature
	 */
	$.pkp.classes.features.CollapsibleGridFeature =
			function(gridHandler, options) {
		this.parent(gridHandler, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.features.CollapsibleGridFeature,
			$.pkp.classes.features.Feature);


	//
	// Getter and setters.
	//
	/**
	 * Get the collapse/expand control link selector.
	 * @return {string}
	 */
	$.pkp.classes.features.CollapsibleGridFeature.prototype.getControlSelector =
			function() {
		return "a[id^='collapsibleGridControl-expandGridControlLink-button-']";
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.CollapsibleGridFeature.prototype.init =
			function() {
		$(this.getControlSelector(), this.getGridHtmlElement()).
				click(this.callbackWrapper(this.toggleGridClickHandler_, this));
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.features.CollapsibleGridFeature.prototype.
			addFeatureHtml = function($gridElement, options) {
		var castOptions = /** @type {{collapsibleLink: string?}} */ (options);
		$gridElement.find('div.grid_header_bar').prepend(castOptions.collapsibleLink);
	};


	//
	// Private helper methods.
	//
	/**
	 * Collapse/expand grid.
	 * @private
	 * @param {Object} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.classes.features.CollapsibleGridFeature.prototype.
			toggleGridClickHandler_ = function(callingContext, opt_event) {
		var $control = this.getGridHtmlElement().find(this.getControlSelector());

		this.getGridHtmlElement().find('div.grid_header').siblings().toggle();
		$control.toggleClass('expand_all').toggleClass('collapse_all');

		// Hide the search controls, if they are visible.
		this.getGridHtmlElement().
				find('div.grid_header_bar .search_extras_collapse').click();

		// Toggle all grid actions.
		this.getGridHtmlElement().find('div.grid_header span.options').toggle();

		return false;
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
