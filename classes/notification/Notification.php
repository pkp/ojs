<?php

/**
 * @file classes/notification/Notification.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Notification
 *
 * @brief OJS subclass for Notifications (defines OJS-specific types).
 */

namespace APP\notification;

class Notification extends \PKP\notification\Notification
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
    public const NOTIFICATION_TYPE_OPEN_ACCESS = 0x300000B;
}
