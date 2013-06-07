/**
 * @defgroup js_controllers_tab_galley
 */
/**
 * @file js/controllers/tab/galley/GalleysTabHandler.js
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GalleysTabHandler
 * @ingroup js_controllers_tab_galley
 *
 * @brief A subclass of TabHandler for handling the galleys tabs.
 * It adds a listener for grid refreshes, so the tab interface can be reloaded.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.tab.galley =
			$.pkp.controllers.tab.galley || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.TabHandler
	 *
	 * @param {jQueryObject} $tabs A wrapped HTML element that
	 *  represents the tabbed interface.
	 * @param {Object} options Handler options.
	 */
	$.pkp.controllers.tab.galley.GalleysTabHandler =
			function($tabs, options) {
		if (options.currentGalleyTabId !== undefined) {
			var $linkId = 'galley' + options.currentGalleyTabId,
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
			$.pkp.controllers.tab.galley.GalleysTabHandler,
			$.pkp.controllers.TabHandler);


	//
	// Private properties
	//
	/**
	 * The URL for retrieving tabs.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.tab.galley.GalleysTabHandler.prototype.
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
	$.pkp.controllers.tab.galley.GalleysTabHandler.prototype.
			refreshTabsHandler_ = function(sourceElement, event) {

		if (this.tabsUrl_) {
			var publicationId = null,
					$element = this.getHtmlElement(),
					$selectedTabLink = $('li.ui-tabs-selected',
							this.getHtmlElement()).find('a'),
					publicationElementId;

			if ($selectedTabLink.length) {
				galleyElementId = $selectedTabLink.attr('id');
				galleyId = $.trim(galleyElementId.
						replace('galley', ' '));
			}

			$.get(this.tabsUrl_, {currentGalleyTabId: galleyId},
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
	$.pkp.controllers.tab.galley.GalleysTabHandler.prototype.
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
