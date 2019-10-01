/**
 * @defgroup js_controllers_tab_issueEntry
 */
/**
 * @file js/controllers/tab/issueEntry/IssueEntryTabHandler.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueEntryTabHandler
 * @ingroup js_controllers_tab_issueEntry
 *
 * @brief A subclass of TabHandler for handling the catalog entry tabs. It adds
 * a listener for grid refreshes, so the tab interface can be reloaded.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.tab.issueEntry =
			$.pkp.controllers.tab.issueEntry || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.TabHandler
	 *
	 * @param {jQueryObject} $tabs A wrapped HTML element that
	 *  represents the tabbed interface.
	 * @param {Object} options Handler options.
	 */
	$.pkp.controllers.tab.issueEntry.IssueEntryTabHandler =
			function($tabs, options) {

		if (options.selectedGalleyId) {
			options.selected =
					this.getTabPositionByGalleyId_(options.selectedGalleyId, $tabs);
		}

		this.parent($tabs, options);

		// Attach the tabs grid refresh handler.
		this.bind('gridRefreshRequested', this.gridRefreshRequested);

		if (options.tabsUrl) {
			this.tabsUrl_ = options.tabsUrl;
		}

		if (options.tabContentUrl) {
			this.tabContentUrl_ = options.tabContentUrl;
		}

		this.bind('gridInitialized', this.addGalleysGridRowActionHandlers_);
		this.publishEvent('gridInitialized');
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.tab.issueEntry.IssueEntryTabHandler,
			$.pkp.controllers.TabHandler);


	//
	// Private properties
	//
	/**
	 * The URL for retrieving a tab's content.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.tab.issueEntry.IssueEntryTabHandler.prototype.
			tabContentUrl_ = null;


	//
	// Public methods
	//
	/**
	 * This listens for grid refreshes from the galleys grid. It
	 * requests a list of the current galleys from the
	 * IssueEntryHandler and calls a callback which updates the tab state
	 * accordingly as they are changed.
	 *
	 * @param {HTMLElement} sourceElement The parent DIV element
	 *  which contains the tabs.
	 * @param {Event} event The triggered event (gridRefreshRequested).
	 */
	$.pkp.controllers.tab.issueEntry.IssueEntryTabHandler.prototype.
			gridRefreshRequested = function(sourceElement, event) {

		var $updateSourceElement = $(event.target),
				$element;

		if ($updateSourceElement.attr('id').match(/^galleysGridContainer/)) {

			if (this.tabsUrl_ && this.tabContentUrl_) {
				$.get(this.tabsUrl_, null, this.callbackWrapper(
						this.updateTabsHandler_), 'json');
			}
		}
	};


	//
	// Private methods
	//
	/**
	 * A callback to update the tabs on the interface.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} data A parsed JSON response object.
	 */
	$.pkp.controllers.tab.issueEntry.IssueEntryTabHandler.prototype.
			updateTabsHandler_ = function(ajaxContext, data) {

		var jsonData = /** @type {{galleys: Object}} */ (this.handleJson(data)),
				$element = this.getHtmlElement(),
				currentTabs = $element.find('li a'),
				currentIndexes = {},
				// only interested in galley tabs, so filter out the others
				regexp = /galley(\d+)/,
				i, j, id, match, url;

		for (j = 0; j < currentTabs.length; j++) {
			id = currentTabs[j].getAttribute('id');
			match = regexp.exec(id);
			if (match !== null) {
				// match[1] is the id of a current galley.
				// j also happens to be the zero-based index of the tab
				// position which will be useful if we have to remove it.
				currentIndexes[match[1]] = j;
			}
		}

		for (i in jsonData.galleys) {
			// i is the galleyId, galleys[i] is the localized name.
			if (!currentIndexes.hasOwnProperty(i)) {
				// this is a tab that has been added
				url = this.tabContentUrl_ + '&galleyId=' +
						encodeURIComponent(i);
				// replace dollar signs in $$$call$$$ so the .add() call
				// interpolates correctly. Is this a bug in jqueryUI?
				url = url.replace(/[$]/g, '$$$$');
				$element.tabs('add', url, jsonData.galleys[i]);
				$element.find('li a').filter(':last').
						attr('id', 'galley' + i);
			}
		}

		// now check our existing tabs to see if any should be removed
		for (i in currentIndexes) {
			// this is a tab that has been removed
			if (!jsonData.galleys.hasOwnProperty(i)) {
				$element.tabs('remove', currentIndexes[i]);
			} else { // tab still exists, update localized name if necessary
				$element.find('li a').filter('[id="galley' + i + '"]').
						html(jsonData.galleys[i]);
			}
		}
	};


	/**
	 * Add handlers to grid row links inside
	 * the galleys grid.
	 *
	 * @private
	 */
	$.pkp.controllers.tab.issueEntry.IssueEntryTabHandler.prototype.
			addGalleysGridRowActionHandlers_ = function() {

		var $galleysGrid = $('[id^="galleysGridContainer"]', this.getHtmlElement()),
				$links;

		if ($galleysGrid.length) {
			$links = $('a[id*="galleyTab"]', $galleysGrid);
			$links.click(this.callbackWrapper(this.galleysGridLinkClickHandler_));
		}
	};


	/**
	 * Galley grid link click handler to open a
	 * galley tab.
	 *
	 * @private
	 *
	 * @param {HTMLElement} sourceElement The clicked link.
	 * @param {Event} event The triggered event (click).
	 */
	$.pkp.controllers.tab.issueEntry.IssueEntryTabHandler.prototype.
			galleysGridLinkClickHandler_ = function(sourceElement, event) {

		var $grid = $('[id^="galleysGridContainer"]',
				this.getHtmlElement()).children('div'),
				gridHandler = $.pkp.classes.Handler.getHandler($grid),
				$gridRow = gridHandler.getParentRow($(sourceElement)),
				galleyId = gridHandler.getRowDataId($gridRow);

		this.getHtmlElement().tabs({
			active: /** @type {string} */ (this.getTabPositionByGalleyId_(
					galleyId, this.getHtmlElement()))});
	};


	/**
	 * Get the tab position using the passed galley id.
	 * @param {string|number} galleyId The galley id.
	 * @param {jQueryObject} $tabs The current tabs container element.
	 * @return {string|number|null} The galley tab position or null.
	 * @private
	 */
	$.pkp.controllers.tab.issueEntry.IssueEntryTabHandler.prototype.
			getTabPositionByGalleyId_ = function(galleyId, $tabs) {

		// Find the correspondent tab position.
		var $linkId = 'galley' + galleyId,
				$tab = $('#' + $linkId, $tabs).parent('li');

		if ($tab.length) {
			return $tabs.find('li').index($tab);
		} else {
			return null;
		}
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
