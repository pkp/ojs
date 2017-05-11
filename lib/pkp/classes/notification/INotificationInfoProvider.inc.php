<?php

/**
 * @file classes/notification/INotificationInfoProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class INotificationInfoProvider
 * @ingroup notification
 * @brief Interface to retrieve notification presentation information.
 */

define('NOTIFICATION_STYLE_CLASS_WARNING', 'notifyWarning');
define('NOTIFICATION_STYLE_CLASS_INFORMATION', 'notifyInfo');
define('NOTIFICATION_STYLE_CLASS_SUCCESS', 'notifySuccess');
define('NOTIFICATION_STYLE_CLASS_ERROR', 'notifyError');
define('NOTIFICATION_STYLE_CLASS_FORM_ERROR', 'notifyFormError');
define('NOTIFICATION_STYLE_CLASS_FORBIDDEN', 'notifyForbidden');
define('NOTIFICATION_STYLE_CLASS_HELP', 'notifyHelp');

interface INotificationInfoProvider {

	/**
	 * Get a URL for the notification.
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @return string
	 */
	public function getNotificationUrl($request, $notification);

	/**
	 * Get the notification message. Only return translated locale
	 * key strings.
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @return string
	 */
	public function getNotificationMessage($request, $notification);

	/**
	 * Get the notification contents. Content is anything that's
	 * more than text, like presenting link actions inside fetched
	 * template files.
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @return string
	 */
	public function getNotificationContents($request, $notification);

	/**
	 * Get the notification title.
	 * @param $notification Notification
	 * @return string
	 */
	public function getNotificationTitle($notification);

	/**
	 * Get the notification style class.
	 * @param $notification Notification
	 * @return string
	 */
	public function getStyleClass($notification);

	/**
	 * Get the notification icon class.
	 * @param $notification Notification
	 * @return string
	 */
	public function getIconClass($notification);

	/**
	 * Whether any notification with the passed notification type
	 * is visible to all users or not.
	 * @param $notificationType int
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 * @return boolean
	 */
	public function isVisibleToAllUsers($notificationType, $assocType, $assocId);
}

?>
