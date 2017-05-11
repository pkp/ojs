/**
 * @file js/controllers/TabHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TabHandler
 * @ingroup js_controllers
 *
 * @brief A basic handler for a tabbed set of pages.
 *
 * See <http://jqueryui.com/demos/tabs/> for documentation on JQuery tabs.
 * Attach this handler to a div that contains a <ul> with a <li> for each tab
 * to be created.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $tabs A wrapped HTML element that
	 *  represents the tabbed interface.
	 * @param {Object} options Handler options.
	 */
	$.pkp.controllers.TabHandler = function($tabs, options) {
		var pageUrl, pageAnchor, pattern, pageAnchors, tabAnchors, i;

		this.parent($tabs, options);

		// Attach the tabs event handlers.
		this.bind('tabsbeforeactivate', this.tabsBeforeActivate);
		this.bind('tabsactivate', this.tabsActivate);
		this.bind('tabscreate', this.tabsCreate);
		this.bind('tabsbeforeload', this.tabsBeforeLoad);
		this.bind('tabsload', this.tabsLoad);
		this.bind('containerReloadRequested', this.tabsReloadRequested);
		this.bind('addTab', this.addTab);

		if (options.emptyLastTab) {
			this.emptyLastTab_ = options.emptyLastTab;
		}

		// if the page has been loaded with an #anchor
		// determine what tab that is for and set the
		// options.selected value to it so it gets used
		// when tabs() are initialized.
		pageUrl = document.location.toString();
		if (pageUrl.match('#')) {
			pageAnchor = pageUrl.split('#')[1];
			tabAnchors = $tabs.find('li a');
			for (i = 0; i < tabAnchors.length; i++) {
				if (pageAnchor == tabAnchors[i].getAttribute('name')) {
					// Matched on anchor name.
					options.selected = i;
				}
			}
		}

		// Render the tabs as jQueryUI tabs.
		$tabs.tabs({
			// Enable AJAX-driven tabs with JSON messages.
			ajaxOptions: {
				cache: false,
				dataFilter: this.callbackWrapper(this.dataFilter)
			},
			disabled: options.disabled,
			active: options.selected
		});

		// Load tabs when focused. This ensures that links which use anchor
		// elements (eg - #backIssues) will load the tab even if the current
		// page is already visible.
		// See: https://github.com/pkp/pkp-lib/issues/1787
		$tabs.children('.ui-tabs-nav').find('li > a').focus(function(e) {
			$(this).click();
		});
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.TabHandler, $.pkp.classes.Handler);


	//
	// Private properties
	//
	/**
	 * The current tab.
	 * @private
	 * @type {jQueryObject}
	 */
	$.pkp.controllers.TabHandler.prototype.$currentTab_ = null;


	/**
	 * The current tab index.
	 * @private
	 * @type {number}
	 */
	$.pkp.controllers.TabHandler.prototype.currentTabIndex_ = 0;


	/**
	 * Whether to empty the previous tab when switching to a new one
	 * @private
	 * @type {boolean}
	 */
	$.pkp.controllers.TabHandler.prototype.emptyLastTab_ = false;


	//
	// Public methods
	//
	/**
	 * Event handler that is called when a tab is selected.
	 *
	 * @param {HTMLElement} tabsElement The tab element that triggered
	 *  the event.
	 * @param {Event} event The triggered event.
	 * @param {jQueryObject} ui The tabs ui data.
	 * @return {boolean} Should return true to continue tab loading.
	 */
	$.pkp.controllers.TabHandler.prototype.tabsBeforeActivate =
			function(tabsElement, event, ui) {

		var unsavedForm = false;
		this.$currentTab_.find('form').each(function(index) {

			var handler = $.pkp.classes.Handler.getHandler($('#' + $(this).attr('id')));
			if (handler.formChangesTracked) {
				unsavedForm = true;
				return false; // found an unsaved form, no need to continue with each().
			}
		});

		this.$currentTab_.find('.hasDatepicker').datepicker('hide');

		if (unsavedForm) {
			if (!confirm($.pkp.locale.form_dataHasChanged)) {
				return false;
			} else {
				this.trigger('unregisterAllForms');
			}
		}

		if (this.emptyLastTab_) {
			// bind a single (i.e. one()) error event handler to prevent
			// propagation if the tab being unloaded no longer exists.
			// We cannot simply getHandler() since that in of itself throws
			// an Error.
			$(window).one('error', function(msg, url, line) { return false; });
			if (this.$currentTab_) {
				this.$currentTab_.empty();
			}
		}
		return true;
	};


	/**
	 * Event handler that is called when a tab is created.
	 *
	 * @param {HTMLElement} tabsElement The tab element that triggered
	 *  the event.
	 * @param {Event} event The triggered event.
	 * @param {jQueryObject} ui The tabs ui data.
	 * @return {boolean} Should return true to continue tab loading.
	 */
	$.pkp.controllers.TabHandler.prototype.tabsCreate =
			function(tabsElement, event, ui) {

		// Save the tab index.
		this.currentTabIndex_ = ui.tab.index();

		// Save a reference to the current panel.
		this.$currentTab_ = ui.panel.jquery ? ui.panel : $(ui.panel);

		return true;
	};


	/**
	 * Event handler that is called when a tab is activated
	 *
	 * @param {HTMLElement} tabsElement The tab element that triggered
	 *  the event.
	 * @param {Event} event The triggered event.
	 * @param {jQueryObject} ui The tabs ui data.
	 * @return {boolean} Should return true to continue tab loading.
	 */
	$.pkp.controllers.TabHandler.prototype.tabsActivate =
			function(tabsElement, event, ui) {

		// Save the tab index.
		this.currentTabIndex_ = ui.newTab.index();

		// Save a reference to the current panel.
		this.$currentTab_ = ui.newPanel.jquery ? ui.newPanel : $(ui.newPanel);

		return true;
	};


	/**
	 * Event handler that is called after a remote tab was loaded.
	 *
	 * @param {HTMLElement} tabsElement The tab element that triggered
	 *  the event.
	 * @param {Event} event The triggered event.
	 * @param {jQueryObject} ui The tabs ui data.
	 * @return {boolean} Should return true to continue tab loading.
	 */
	$.pkp.controllers.TabHandler.prototype.tabsLoad =
			function(tabsElement, event, ui) {
		return true;
	};


	/**
	 * Callback that that is triggered before the tab is loaded.
	 *
	 * @param {HTMLElement} tabsElement The tab element that triggered
	 *  the event.
	 * @param {Event} event The triggered event.
	 * @param {jQueryObject} ui The tabs ui data.
	 */
	$.pkp.controllers.TabHandler.prototype.tabsBeforeLoad =
			function(tabsElement, event, ui) {

		// Initialize AJAX settings for loading tab content remotely
		ui.ajaxSettings.cache = false;
		ui.ajaxSettings.dataFilter = this.callbackWrapper(this.dataFilter);
	};


	/**
	 * Callback that processes AJAX data returned by the server before
	 * it is displayed in a tab.
	 *
	 * @param {Object} ajaxOptions The options object from which the
	 *  callback originated.
	 * @param {string} jsonString Unparsed JSON data returned from the server.
	 * @return {string} The tab mark-up.
	 */
	$.pkp.controllers.TabHandler.prototype.dataFilter =
			function(ajaxOptions, jsonString) {

		var jsonData = this.handleJson($.parseJSON(jsonString));
		if (jsonData === false) {
			return '';
		}
		return jsonData.content;
	};


	/**
	 * Callback that processes data returned by the server when
	 * a 'tabsReloadRequested' event is bubbled up from a widget
	 * on a tab.
	 *
	 * This is useful if you have a tabbed form on a modal and you
	 * wish to reload the entire tabbed interface when one form is
	 * submitted. Since this reloads the templates for the tabs you
	 * have the opportunity to alter their state.
	 *
	 * @param {HTMLElement} divElement The parent DIV element
	 *  which contains the tabs.
	 * @param {Event} event The triggered event (tabsReloadRequested).
	 * @param {{tabsUrl: string}} jsonContent The tabs ui data.
	 */
	$.pkp.controllers.TabHandler.prototype.tabsReloadRequested =
			function(divElement, event, jsonContent) {

		var $element = this.getHtmlElement();
		$.get(jsonContent.tabsUrl, function(data) {
			var jsonData = $.parseJSON(data);
			$element.replaceWith(jsonData.content);
		});
	};


	/**
	 * Callback that processes data returned by the server when
	 * an 'addTab' event is received.
	 *
	 * This is useful e.g. when the results of a form handler
	 * should be sent to a different tab in the containing tabset.
	 *
	 * @param {HTMLElement} divElement The parent DIV element
	 *  which contains the tabs.
	 * @param {Event} event The triggered event (addTab).
	 * @param {{url: string, title: string}} jsonContent The tabs ui data.
	 */
	$.pkp.controllers.TabHandler.prototype.addTab =
			function(divElement, event, jsonContent) {

		var $element = this.getHtmlElement(),
				numTabs = $element.children('ul').children('li').length + 1,
				$anchorElement = $('<a/>')
						.text(jsonContent.title)
						.attr('href', jsonContent.url),
				$closeSpanElement = $('<a/>')
						.addClass('close')
						.text($.pkp.locale.common_close)
						.attr('href', '#'),
				$liElement = $('<li/>')
						.append($anchorElement)
						.append($closeSpanElement);

		// Get the "close" button working
		$closeSpanElement.click(function() {
			var $liElement = $(this).closest('li'),
					$divElement = $('#' + $liElement.attr('aria-controls')),
					thisTabIndex, unsavedForm;

			// Check to see if any unsaved changes need to be confirmed
			unsavedForm = false;
			$divElement.find('form').each(function() {
				var handler = $.pkp.classes.Handler.getHandler($(this));
				if (handler.formChangesTracked) {
					// Confirm before proceeding
					if (!confirm($.pkp.locale.form_dataHasChanged)) {
						unsavedForm = true;
						return false;
					}
				}
			});

			if (!unsavedForm) {
				$divElement.find('form').each(function() {
					var handler = $.pkp.classes.Handler.getHandler($(this));
					if (handler) {
						handler.unregisterForm();
					}
				});

				// If the panel being closed is currently selected, move off first.
				thisTabIndex = $liElement.eq(0).index();
				if ($element.tabs('option', 'active') == thisTabIndex) {
					$element.tabs('option', 'active', thisTabIndex - 1);
				}

				$liElement.remove();
				$divElement.remove();

				$element.tabs('refresh');
			}
		});

		// Add the new tab element and refresh the tab set.
		$element.children('ul').append($liElement);
		$element.tabs('refresh');
		$element.tabs('option', 'active', numTabs - 1);
	};


	//
	// Protected methods
	//
	/**
	 * Get the current tab.
	 * @protected
	 * @return {jQueryObject} The current tab.
	 */
	$.pkp.controllers.TabHandler.prototype.getCurrentTab = function() {
		return this.$currentTab_;
	};


	/**
	 * Get the current tab index.
	 * @protected
	 * @return {number} The current tab index.
	 */
	$.pkp.controllers.TabHandler.prototype.getCurrentTabIndex = function() {
		return this.currentTabIndex_;
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
