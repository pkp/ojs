/**
 * @file js/controllers/grid/notifications/NotificationsGridHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationsGridHandler
 * @ingroup js_controllers_grid
 *
 * @brief Category grid handler.
 */
(function($) {

	// Define the namespace.
	$.pkp.controllers.grid.notifications =
			$.pkp.controllers.grid.notifications || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.grid.GridHandler
	 *
	 * @param {jQueryObject} $grid The grid this handler is
	 *  attached to.
	 * @param {Object} options Grid handler configuration.
	 */
	$.pkp.controllers.grid.notifications.NotificationsGridHandler =
			function($grid, options) {
		$grid.find('a[id*="markNew"]').mousedown(
				this.callbackWrapper(this.markNewHandler_));

		$grid.find('a[id*="markRead"]').mousedown(
				this.callbackWrapper(this.markReadHandler_));

		$grid.find('a[id*="deleteNotifications"]').mousedown(
				this.callbackWrapper(this.deleteHandler_));

		this.parent($grid, options);
	};
	$.pkp.classes.Helper.inherits($.pkp.controllers.grid.notifications
			.NotificationsGridHandler, $.pkp.controllers.grid.GridHandler);


	//
	// Private properties
	//
	/**
	 * The "mark notifications as new" URL
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.grid.notifications.NotificationsGridHandler
			.prototype.markNewUrl_ = null;


	/**
	 * The "mark notifications as read" URL
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.grid.notifications.NotificationsGridHandler
			.prototype.markReadUrl_ = null;


	/**
	 * The "delete notifications" URL
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.grid.notifications.NotificationsGridHandler
			.prototype.deleteUrl_ = null;


	//
	// Extended methods from GridHandler
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.grid.notifications.NotificationsGridHandler.
			prototype.initialize = function(options) {

		// Save the URLs to interact with selected sets of notifications
		this.markNewUrl_ = options.markNewUrl;
		this.markReadUrl_ = options.markReadUrl;
		this.deleteUrl_ = options.deleteUrl;

		this.parent('initialize', options);
	};


	//
	// Private methods.
	//
	/**
	 * Get the array of selected notifications
	 * @private
	 * @return {Array} List of selected notification IDs.
	 */
	$.pkp.controllers.grid.notifications.NotificationsGridHandler.prototype.
			getSelectedNotifications_ = function() {
		var selectedElements = [];
		this.getHtmlElement().find('input:checkbox:checked').each(function() {
			selectedElements.push($(this).val());
		});
		return selectedElements;
	};


	/**
	 * Callback that will be activated when the "mark new" icon is clicked
	 *
	 * @private
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a button.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.grid.notifications.NotificationsGridHandler.prototype.
			markNewHandler_ = function(callingContext, opt_event) {
		$.post(this.markNewUrl_, {selectedElements: this.getSelectedNotifications_()},
				this.callbackWrapper(this.responseHandler_, null), 'json');

		return false;
	};


	/**
	 * Callback that will be activated when the "mark read" icon is clicked
	 *
	 * @private
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a button.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.grid.notifications.NotificationsGridHandler.prototype.
			markReadHandler_ = function(callingContext, opt_event) {
		$.post(this.markReadUrl_,
				{selectedElements: this.getSelectedNotifications_()},
				this.callbackWrapper(this.responseHandler_, null), 'json');

		return false;
	};


	/**
	 * Callback that will be activated when the "delete" icon is clicked
	 *
	 * @private
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a button.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.grid.notifications.NotificationsGridHandler.prototype.
			deleteHandler_ = function(callingContext, opt_event) {
		$.post(this.deleteUrl_, {selectedElements: this.getSelectedNotifications_()},
				this.callbackWrapper(this.responseHandler_, null), 'json');

		return false;
	};


	/**
	 * Callback after a response returns from the server.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 */
	$.pkp.controllers.grid.notifications.NotificationsGridHandler.prototype.
			responseHandler_ = function(ajaxContext, jsonData) {

		// Bounce the selected notification IDs back to the server
		// so that selections can be maintained
		var params = this.getFetchExtraParams();
		params.selectedNotificationIds = jsonData.content;
		this.setFetchExtraParams(params);

		// Pass through the JSON handler to cause the grid to be
		// refreshed.
		this.handleJson(jsonData);
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
