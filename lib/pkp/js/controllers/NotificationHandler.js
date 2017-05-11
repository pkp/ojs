/**
 * @file js/controllers/NotificationHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationHandler
 * @ingroup js_controllers
 *
 * @brief Handle in place notifications.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $notificationElement The html notification element.
	 * @param {Object} options Notification options.
	 */
	$.pkp.controllers.NotificationHandler =
			function($notificationElement, options) {
		this.parent($notificationElement, options);

		this.options_ = options;

		this.bind('notifyUser', this.fetchNotificationHandler_);

		// Hide the notification element.
		this.getHtmlElement().hide();

		// Trigger the notify user event without bubbling up.
		this.getHtmlElement().triggerHandler('notifyUser');
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.NotificationHandler,
			$.pkp.classes.Handler);


	//
	// Private properties.
	//
	/**
	 * The options to fetch a notification.
	 * @private
	 * @type {Object?}
	 */
	$.pkp.controllers.NotificationHandler.prototype.options_ = null;


	/**
	 * Time to hide trivial inplace notifications.
	 * @private
	 * @type {number?}
	 */
	$.pkp.controllers.NotificationHandler.prototype.trivialTimer_ = null;


	//
	// Private methods.
	//
	/**
	 * Handler to fetch the notification data.
	 * @private
	 */
	$.pkp.controllers.NotificationHandler.prototype.fetchNotificationHandler_ =
			function() {

		var requestOptions = {};
		requestOptions.requestOptions = this.options_.requestOptions;

		// Avoid race conditions with other notification controllers.
		$.ajax({
			type: 'POST',
			url: this.options_.fetchNotificationUrl,
			data: requestOptions,
			success: this.callbackWrapper(this.showNotificationResponseHandler_),
			dataType: 'json',
			async: false
		});
	};


	/**
	 * Callback to show the notification data in place.
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @private
	 */
	$.pkp.controllers.NotificationHandler.prototype.
			showNotificationResponseHandler_ = function(ajaxContext, jsonData) {
		// Delete any existing trivial notification timer.
		clearTimeout(this.trivialTimer_);

		var $notificationElement = this.getHtmlElement(),
				workingJsonData = this.handleJson(jsonData),
				inPlaceNotificationsData, newNotificationsData,
				trivialNotificationsId, notificationId, i;

		if (workingJsonData === false) {
			return;
		}
		if (workingJsonData.content.inPlace) {
			inPlaceNotificationsData = this.concatenateNotifications_(
					workingJsonData.content.inPlace);
			newNotificationsData = this.removeAlreadyShownNotifications_(
					/** @type {Object} */ (workingJsonData));

			$notificationElement.html(inPlaceNotificationsData);

			// We need to show the notification element now so the
			// visibility test can be executed.
			$notificationElement.show();

			trivialNotificationsId = this.getTrivialNotifications_(
					workingJsonData.content.inPlace);

			if (!(this.visibleWithoutScrolling_()) && newNotificationsData) {
				// In place notification is not visible. Let parent widgets
				// show the notification data.
				$notificationElement.parent().
						trigger('notifyUser', [newNotificationsData]);

				// Remove in place trivial notifications.
				for (i in trivialNotificationsId) {
					notificationId = trivialNotificationsId[i];
					$('#pkp_notification_' + notificationId,
							this.getHtmlElement()).remove();
				}
			}

			// After visibility test and possible trivial notifications
			// removal, we need to test if the in place notification widget
			// shows any notification. If not, hide it.
			if ($notificationElement.children().length === 0) {
				$notificationElement.hide();
			} else {
				// Add a timer to any trivial notifications
				// inside this widget.
				this.addTimerToNotifications(trivialNotificationsId);
			}

		} else {
			this.getHtmlElement().empty();
			this.getHtmlElement().hide();
		}
	};


	/**
	 * Check if the notification is inside the window are visible.
	 * @return {boolean} True iff the notification is visible.
	 * @private
	 */
	$.pkp.controllers.NotificationHandler.prototype.
			visibleWithoutScrolling_ = function() {
		var $notificationElement = this.getHtmlElement(),
				notificationTop = $notificationElement.offset().top,
				notificationMiddle = notificationTop + this.getHtmlElement().height() / 2,
				windowScrollTop = $(window).scrollTop(),
				windowBottom = windowScrollTop + $(window).height(),
				// Consider modals and its own scroll functionality.
				$parentModalContentWrapper = $notificationElement
						.parents('.ui-dialog-content'),
				modalContentTop, modalContentBottom;

		if ($parentModalContentWrapper.length > 0) {
			modalContentTop = $parentModalContentWrapper.offset().top;
			modalContentBottom = modalContentTop +
					$parentModalContentWrapper.height();
			if (notificationMiddle < modalContentTop ||
					notificationMiddle > modalContentBottom) {
				// The element is outside of the modal content wrapper area.
				return false;
			}
		}

		// Check if the element is inside of the visible window area.
		if (notificationMiddle < windowScrollTop ||
				notificationMiddle > windowBottom) {
			return false;
		} else {
			return true;
		}
	};


	/**
	 * Remove notification data from object that is already on page.
	 * @param {Object} notificationsData The notification data to perform
	 *  the deletion on.
	 * @return {Object|boolean} Notification data after deletion.
	 * @private
	 */
	$.pkp.controllers.NotificationHandler.prototype.
			removeAlreadyShownNotifications_ = function(notificationsData) {

		var workingNotificationsData = /** @type {{ content: {
				inPlace: Array, general: Array} }} */
				(notificationsData),
				emptyObject = true,
				levelId, notificationId, element;
		for (levelId in workingNotificationsData.content.inPlace) {
			for (notificationId in
					workingNotificationsData.content.inPlace[levelId]) {
				element = $('#pkp_notification_' + notificationId);
				if (element.length > 0) {
					delete workingNotificationsData.content.
							inPlace[levelId][notificationId];
					delete workingNotificationsData.content.
							general[levelId][notificationId];
				} else {
					emptyObject = false;
				}
			}
		}
		if (emptyObject) {
			return false;
		} else {
			return workingNotificationsData;
		}
	};


	/**
	 * Concatenate notification data in a string variable.
	 * @param {Object} notificationsData The notification data to assemble
	 *  the concatenation from.
	 * @return {string} The concatenated notification data.
	 * @private
	 */
	$.pkp.controllers.NotificationHandler.prototype.
			concatenateNotifications_ = function(notificationsData) {
		var returner = '', levelId, notificationId;
		for (levelId in notificationsData) {
			for (notificationId in notificationsData[levelId]) {
				// Concatenate all notifications.
				returner += notificationsData[levelId][notificationId];
			}
		}

		return returner;
	};


	/**
	 * Get all trivial notifications id inside the passed notifications.
	 * @param {Object} notificationsData The data returned from the fetch
	 * notification request.
	 * @return {Array} The trivial notifications id.
	 * @private
	 */
	$.pkp.controllers.NotificationHandler.prototype.
			getTrivialNotifications_ = function(notificationsData) {

		var trivialNotificationsId = [], levelId, notificationId;
		for (levelId in notificationsData) {
			if (levelId == 1) { // Trivial level.
				for (notificationId in notificationsData[levelId]) {
					trivialNotificationsId.push(notificationId);
				}
			}
		}

		return trivialNotificationsId;
	};


	/**
	 * Add a timer for passed notifications to hide them after a time.
	 * @param {Object} notificationsId Array with the notifications id
	 * that will receive the timer.
	 */
	$.pkp.controllers.NotificationHandler.prototype.
			addTimerToNotifications = function(notificationsId) {

		var removeNotification = function() {
			// "this" represents the notification element here.
			$(this).remove();
		};

		if (notificationsId.length) {
			this.trivialTimer_ = setTimeout(function() {
				var notificationId, $notification;
				for (notificationId in notificationsId) {
					$notification = $('#pkp_notification_' +
							notificationsId[notificationId]);
					$notification.fadeOut(400, removeNotification());
				}
			}, 6000);
		}
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
