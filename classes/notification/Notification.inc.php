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
 *
 * @see NotificationDAO
 * @brief OJS subclass for Notifications (defines OJS-specific types).
 */

namespace APP\notification;

use PKP\notification\PKPNotification;

class Notification extends PKPNotification
{
    public const NOTIFICATION_TYPE_PUBLISHED_ISSUE = 0x10000015;

    // OJS-specific trivial notifications
    public const NOTIFICATION_TYPE_BOOK_REQUESTED = 0x3000001;
    public const NOTIFICATION_TYPE_BOOK_CREATED = 0x3000002;
    public const NOTIFICATION_TYPE_BOOK_UPDATED = 0x3000003;
    public const NOTIFICATION_TYPE_BOOK_DELETED = 0x3000004;
    public const NOTIFICATION_TYPE_BOOK_MAILED = 0x3000005;
    public const NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED = 0x3000006;
    public const NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED = 0x3000007;
    public const NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED = 0x3000008;
    public const NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED = 0x3000009;
    public const NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED = 0x300000A;
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\notification\Notification', '\Notification');
    define('NOTIFICATION_TYPE_PUBLISHED_ISSUE', \Notification::NOTIFICATION_TYPE_PUBLISHED_ISSUE);
}
