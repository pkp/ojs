<?php

/**
 * @file classes/notification/Notification.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSNotification
 * @ingroup notification
 * @see NotificationDAO
 * @brief OJS subclass for Notifications (defines OJS-specific types).
 */

/** Notification associative types. */
define('NOTIFICATION_TYPE_PUBLISHED_ISSUE', 		0x10000015);

// OJS-specific trivial notifications
define('NOTIFICATION_TYPE_BOOK_REQUESTED',			0x3000001);
define('NOTIFICATION_TYPE_BOOK_CREATED',			0x3000002);
define('NOTIFICATION_TYPE_BOOK_UPDATED',			0x3000003);
define('NOTIFICATION_TYPE_BOOK_DELETED',			0x3000004);
define('NOTIFICATION_TYPE_BOOK_MAILED',				0x3000005);
define('NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED',			0x3000006);
define('NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED',		0x3000007);
define('NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED',		0x3000008);
define('NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED',			0x3000009);
define('NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED',			0x300000A);

import('lib.pkp.classes.notification.PKPNotification');
import('lib.pkp.classes.notification.NotificationDAO');

class Notification extends PKPNotification {
}


