<?php

/**
 * @file classes/notification/managerDelegate/ApproveSubmissionNotificationManager.inc.php
 *
 * Copyright (c) 2016-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ApproveSubmissionNotificationManager
 * @ingroup classes_notification_managerDelegate
 *
 * @brief Notification manager delegate that handles notifications related with
 * submission approval process.
 */

namespace APP\notification\managerDelegate;

use PKP\notification\managerDelegate\PKPApproveSubmissionNotificationManager;
use PKP\notification\PKPNotification;

class ApproveSubmissionNotificationManager extends PKPApproveSubmissionNotificationManager
{
    /**
     * @copydoc PKPNotificationOperationManager::getNotificationTitle()
     */
    public function getNotificationTitle($notification)
    {
        switch ($notification->getType()) {
            case PKPNotification::NOTIFICATION_TYPE_APPROVE_SUBMISSION:
            case PKPNotification::NOTIFICATION_TYPE_FORMAT_NEEDS_APPROVED_SUBMISSION:
                return __('notification.type.approveSubmissionTitle');
        }
    }

    /**
     * @copydoc PKPNotificationOperationManager::getNotificationMessage()
     */
    public function getNotificationMessage($request, $notification)
    {
        switch ($notification->getType()) {
            case PKPNotification::NOTIFICATION_TYPE_FORMAT_NEEDS_APPROVED_SUBMISSION:
                return __('notification.type.formatNeedsApprovedSubmission');
            case PKPNotification::NOTIFICATION_TYPE_APPROVE_SUBMISSION:
                return __('notification.type.approveSubmission');
        }

        return parent::getNotificationMessage($request, $notification);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\notification\managerDelegate\ApproveSubmissionNotificationManager', '\ApproveSubmissionNotificationManager');
}
