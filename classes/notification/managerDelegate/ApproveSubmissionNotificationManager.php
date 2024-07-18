<?php

/**
 * @file classes/notification/managerDelegate/ApproveSubmissionNotificationManager.php
 *
 * Copyright (c) 2016-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ApproveSubmissionNotificationManager
 *
 * @brief Notification manager delegate that handles notifications related with
 * submission approval process.
 */

namespace APP\notification\managerDelegate;

use PKP\core\PKPRequest;
use PKP\notification\managerDelegate\PKPApproveSubmissionNotificationManager;
use PKP\notification\PKPNotification;

class ApproveSubmissionNotificationManager extends PKPApproveSubmissionNotificationManager
{
    /**
     * @copydoc PKPNotificationOperationManager::getNotificationTitle()
     */
    public function getNotificationTitle(PKPNotification $notification)
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
    public function getNotificationMessage(PKPRequest $request, PKPNotification $notification): ?string
    {
        return match($notification->getType()) {
            PKPNotification::NOTIFICATION_TYPE_FORMAT_NEEDS_APPROVED_SUBMISSION => __('notification.type.formatNeedsApprovedSubmission'),
            PKPNotification::NOTIFICATION_TYPE_APPROVE_SUBMISSION => __('notification.type.approveSubmission'),
            default => parent::getNotificationMessage($request, $notification)
        };
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\notification\managerDelegate\ApproveSubmissionNotificationManager', '\ApproveSubmissionNotificationManager');
}
