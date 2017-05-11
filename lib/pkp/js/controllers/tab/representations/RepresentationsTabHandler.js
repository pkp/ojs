/**
 * @defgroup js_controllers_tab_representations
 */
/**
 * @file js/controllers/tab/representations/RepresentationsTabHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RepresentationsTabHandler
 * @ingroup js_controllers_tab_representations
 *
 * @brief A subclass of TabHandler for handling the representation tabs.
 * It adds a listener for grid refreshes, so the tab interface can be reloaded.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.tab.representations =
			$.pkp.controllers.tab.representations || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.TabHandler
	 *
	 * @param {jQueryObject} $tabs A wrapped HTML element that
	 *  represents the tabbed interface.
	 * @param {Object} options Handler options.
	 */
	$.pkp.controllers.tab.representations.RepresentationsTabHandler =
			function($tabs, options) {
		if (options.currentRepresentationTabId !== undefined) {
			var $linkId = 'representation' + options.currentRepresentationTabId,
					$tab = $('#' + $linkId, $tabs).parent('li');

			if ($tab.length) {
				options.selected = $tabs.children().children().index($tab);
			}
		}

		this.parent($tabs, options);

		this.tabsUrl_ = options.tabsUrl;
		this.bind('refreshTabs', this.refreshTabsHandler_);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.tab.representations.RepresentationsTabHandler,
			$.pkp.controllers.TabHandler);


	//
	// Private properties
	//
	/**
	 * The URL for retrieving tabs.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.tab.representations.RepresentationsTabHandler.prototype.
			tabsUrl_ = null;


	//
	// Private methods
	//
	/**
	 * Tab refresh handler.
	 *
	 * @private
	 *
	 * @param {HTMLElement} sourceElement The parent DIV element
	 *  which contains the tabs.
	 * @param {Event} event The triggered event (refreshTabs).
	 */
	$.pkp.controllers.tab.representations.RepresentationsTabHandler.prototype.
			refreshTabsHandler_ = function(sourceElement, event) {

		if (this.tabsUrl_) {
			var representationId = null,
					$element = this.getHtmlElement(),
					$selectedTabLink = $('li.ui-tabs-selected',
							this.getHtmlElement()).find('a'),
					elementId;

			if ($selectedTabLink.length) {
				elementId = $selectedTabLink.attr('id');
				representationId = $.trim(elementId.
						replace('representation', ' '));
			}

			$.get(this.tabsUrl_, {currentRepresentationTabId: representationId},
					this.callbackWrapper(this.updateTabsHandler_), 'json');
		}
	};


	/**
	 * A callback to update the tabs on the interface.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} data A parsed JSON response object.
	 */
	$.pkp.controllers.tab.representations.RepresentationsTabHandler.prototype.
			updateTabsHandler_ = function(ajaxContext, data) {

		this.trigger('gridRefreshRequested');

		var jsonData = this.handleJson(data),
				$tabs = this.getHtmlElement();

		if (jsonData !== false) {
			// Replace the grid content
			$tabs.replaceWith(jsonData.content);
		}
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
