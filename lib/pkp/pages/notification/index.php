<?php

/**
 * @defgroup pages_notification Notification Pages
 */

/**
 * @file pages/notification/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_notification
 * @brief Handle requests for viewing notifications.
 *
 */


switch ($op) {
	case 'index':
	case 'delete':
	case 'settings':
	case 'saveSettings':
	case 'getNotificationFeedUrl':
	case 'notificationFeed':
	case 'subscribeMailList':
	case 'saveSubscribeMailList':
	case 'mailListSubscribed':
	case 'confirmMailListSubscription':
	case 'unsubscribeMailList':
	case 'fetchNotification':
		define('HANDLER_CLASS', 'NotificationHandler');
		import('lib.pkp.pages.notification.NotificationHandler');
		break;
}

?>
