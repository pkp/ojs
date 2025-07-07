<?php

/**
 * @file classes/notification/form/NotificationSettingsForm.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NotificationSettingsForm
 *
 * @brief Form to edit notification settings.
 */

namespace APP\notification\form;

use APP\notification\Notification;
use PKP\context\Context;
use PKP\notification\form\PKPNotificationSettingsForm;

class NotificationSettingsForm extends PKPNotificationSettingsForm
{
    /**
     * @copydoc PKPNotificationSettingsForm::getNotificationSettingsCategories()
     */
    public function getNotificationSettingCategories(?Context $context = null)
    {
        $categories = parent::getNotificationSettingCategories($context);
        for ($i = 0; $i < count($categories); $i++) {
            if ($categories[$i]['categoryKey'] === 'notification.type.public') {
                $categories[$i]['settings'][] = Notification::NOTIFICATION_TYPE_PUBLISHED_ISSUE;
                $categories[$i]['settings'][] = Notification::NOTIFICATION_TYPE_OPEN_ACCESS;
                break;
            }
        }
        return $categories;
    }
}
