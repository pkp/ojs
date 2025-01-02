<?php

/**
 * @file classes/notification/NotificationManager.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NotificationManager
 *
 * @see Notification
 *
 * @brief Class for Notification Manager.
 */

namespace APP\notification;

use APP\core\Application;
use APP\facades\Repo;
use APP\notification\managerDelegate\ApproveSubmissionNotificationManager;
use APP\notification\Notification as AppNotification;
use PKP\core\PKPRequest;
use PKP\notification\Notification;
use PKP\notification\NotificationManagerDelegate;
use PKP\notification\PKPNotificationManager;
use PKP\plugins\Hook;

class NotificationManager extends PKPNotificationManager
{
    /** @var array Cache each user's most privileged role for each submission */
    public $privilegedRoles;


    /**
     * Construct a URL for the notification based on its type and associated object
     */
    public function getNotificationUrl(PKPRequest $request, Notification $notification): ?string
    {
        $router = $request->getRouter();
        $dispatcher = $router->getDispatcher();
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($notification->contextId);

        switch ($notification->type) {
            case AppNotification::NOTIFICATION_TYPE_PUBLISHED_ISSUE:
                return $dispatcher->url($request, Application::ROUTE_PAGE, $context->getPath(), 'issue', 'current');
            default:
                return parent::getNotificationUrl($request, $notification);
        }
    }

    /**
     * Construct the contents for the notification based on its type and associated object
     *
     * @hook NotificationManager::getNotificationMessage [[&$notification, &$message]]
     */
    public function getNotificationMessage(PKPRequest $request, Notification $notification): string|array|null
    {
        // Allow hooks to override default behavior
        $message = null;
        Hook::call('NotificationManager::getNotificationMessage', [&$notification, &$message]);
        return $message ?? match($notification->type) {

            AppNotification::NOTIFICATION_TYPE_PUBLISHED_ISSUE => __('notification.type.issuePublished'),
            AppNotification::NOTIFICATION_TYPE_BOOK_REQUESTED => __('plugins.generic.booksForReview.notification.bookRequested'),
            AppNotification::NOTIFICATION_TYPE_BOOK_CREATED => __('plugins.generic.booksForReview.notification.bookCreated'),
            AppNotification::NOTIFICATION_TYPE_BOOK_UPDATED => __('plugins.generic.booksForReview.notification.bookUpdated'),
            AppNotification::NOTIFICATION_TYPE_BOOK_DELETED => __('plugins.generic.booksForReview.notification.bookDeleted'),
            AppNotification::NOTIFICATION_TYPE_BOOK_MAILED => __('plugins.generic.booksForReview.notification.bookMailed'),
            AppNotification::NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED => __('plugins.generic.booksForReview.notification.settingsSaved'),
            AppNotification::NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED => __('plugins.generic.booksForReview.notification.submissionAssigned'),
            AppNotification::NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED => __('plugins.generic.booksForReview.notification.authorAssigned'),
            AppNotification::NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED => __('plugins.generic.booksForReview.notification.authorDenied'),
            AppNotification::NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED => __('plugins.generic.booksForReview.notification.authorRemoved'),
            default => parent::getNotificationMessage($request, $notification),
        };
    }

    /**
     * Helper function to get an article title from a notification's associated object
     */
    public function _getArticleTitle(Notification $notification): ?string
    {
        if ($notification->assocType != Application::ASSOC_TYPE_SUBMISSION) {
            throw new \Exception('Unexpected assoc type!');
        }
        $article = Repo::submission()->get($notification->assocId);
        return $article?->getCurrentPublication()?->getLocalizedFullTitle();
    }

    /**
     * get notification style class
     */
    public function getStyleClass(Notification $notification): string
    {
        return match($notification->type) {
            AppNotification::NOTIFICATION_TYPE_BOOK_REQUESTED,
            AppNotification::NOTIFICATION_TYPE_BOOK_CREATED,
            AppNotification::NOTIFICATION_TYPE_BOOK_UPDATED,
            AppNotification::NOTIFICATION_TYPE_BOOK_DELETED,
            AppNotification::NOTIFICATION_TYPE_BOOK_MAILED,
            AppNotification::NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED,
            AppNotification::NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED,
            AppNotification::NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED,
            AppNotification::NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED,
            AppNotification::NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED => 'notifySuccess',
            default => parent::getStyleClass($notification),
        };
    }

    /**
     * Return a CSS class containing the icon of this notification type
     */
    public function getIconClass(Notification $notification): string
    {
        return match ($notification->type) {
            AppNotification::NOTIFICATION_TYPE_PUBLISHED_ISSUE => 'notifyIconPublished',
            AppNotification::NOTIFICATION_TYPE_NEW_ANNOUNCEMENT => 'notifyIconNewAnnouncement',
            AppNotification::NOTIFICATION_TYPE_BOOK_REQUESTED,
            AppNotification::NOTIFICATION_TYPE_BOOK_CREATED,
            AppNotification::NOTIFICATION_TYPE_BOOK_UPDATED,
            AppNotification::NOTIFICATION_TYPE_BOOK_DELETED,
            AppNotification::NOTIFICATION_TYPE_BOOK_MAILED,
            AppNotification::NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED,
            AppNotification::NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED,
            AppNotification::NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED,
            AppNotification::NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED,
            AppNotification::NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED => 'notifyIconSuccess',
            default => parent::getIconClass($notification)
        };
    }

    /**
     * @copydoc PKPNotificationManager::getMgrDelegate()
     */
    protected function getMgrDelegate(int $notificationType, int $assocType, int $assocId): ?NotificationManagerDelegate
    {
        switch ($notificationType) {
            case Notification::NOTIFICATION_TYPE_APPROVE_SUBMISSION:
            case Notification::NOTIFICATION_TYPE_VISIT_CATALOG:
                if ($assocType != Application::ASSOC_TYPE_SUBMISSION) {
                    throw new \Exception('Unexpected assoc type!');
                }
                return new ApproveSubmissionNotificationManager($notificationType);
        }
        // Otherwise, fall back on parent class
        return parent::getMgrDelegate($notificationType, $assocType, $assocId);
    }

    /**
     * @copydoc PKPNotificationManager::getNotificationSettingsMap()
     */
    public function getNotificationSettingsMap(): array
    {
        return [
            AppNotification::NOTIFICATION_TYPE_PUBLISHED_ISSUE => [
                'settingName' => 'notificationPublishedIssue',
                'emailSettingName' => 'emailNotificationPublishedIssue',
                'settingKey' => 'notification.type.issuePublished',
            ],
            AppNotification::NOTIFICATION_TYPE_OPEN_ACCESS => [
                'settingName' => 'notificationOpenAccess',
                'emailSettingName' => 'emailNotificationOpenAccess',
                'settingKey' => 'notification.type.openAccess',
            ]
        ] + parent::getNotificationSettingsMap();
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\notification\NotificationManager', '\NotificationManager');
}
