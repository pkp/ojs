/**
 * @defgroup js_controllers_tab_settings_managementSettings
 */
/**
 * @file js/controllers/tab/settings/managementSettings/UsersAndRolesTabHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsersAndRolesTabHandler
 * @ingroup js_controllers_tab_settings_managementSettings
 *
 * @brief A subclass of TabHandler for handling the users and roles tabs. Adds
 * a listener for grid refreshes, so the tab interface can be reloaded.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.tab.settings.managementSettings =
			$.pkp.controllers.tab.managementSettings || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.TabHandler
	 *
	 * @param {jQueryObject} $tabs A wrapped HTML element that
	 *  represents the tabbed interface.
	 * @param {Object} options Handler options.
	 */
	$.pkp.controllers.tab.settings.managementSettings.UsersAndRolesTabHandler =
			function($tabs, options) {

		this.parent($tabs, options);

		this.bind('confirmationModalConfirmed',
				this.confirmationModalConfirmedHandler_);

		if (options.userGridContentUrl) {
			this.userGridContentUrl_ = options.userGridContentUrl;
		}
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.tab.settings.managementSettings.UsersAndRolesTabHandler,
			$.pkp.controllers.TabHandler);


	//
	// Private properties
	//
	/**
	 * The URL for retrieving a tab's content.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.tab.settings.managementSettings.UsersAndRolesTabHandler.
			prototype.userGridContentUrl_ = null;


	//
	// Private methods
	//
	/**
	 * This listens for grid refreshes from the users grid.
	 *
	 * @private
	 *
	 * @param {HTMLElement} sourceElement The parent DIV element
	 *  which contains the tabs.
	 * @param {Event} event The triggered event (confirmationModalConfirmed).
	 * @param {?string} data additional event data.
	 */
	$.pkp.controllers.tab.settings.managementSettings.UsersAndRolesTabHandler.
			prototype.confirmationModalConfirmedHandler_ =
					function(sourceElement, event, data) {

		var $element, jsonObject, prop;

		jsonObject = /** @type {{content: {oldUserId: string}}} */ $.parseJSON(data);

		if (this.userGridContentUrl_ && jsonObject.content.oldUserId) {
			$element = this.getHtmlElement();
			$.get(this.userGridContentUrl_ + '&oldUserId=' +
					encodeURIComponent(jsonObject.content.oldUserId), null,
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
	$.pkp.controllers.tab.settings.managementSettings.UsersAndRolesTabHandler.
			prototype.updateTabsHandler_ = function(ajaxContext, data) {

		var jsonData = /** @type {{content: string}} */ (this.handleJson(data)),
				$element = this.getHtmlElement(),
				currentTab = $element.find('div').first();
		$(currentTab).replaceWith(jsonData.content);
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
