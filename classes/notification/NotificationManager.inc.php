<?php

/**
 * @file classes/notification/NotificationManager.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPNotificationManager
 * @ingroup notification
 *
 * @see NotificationDAO
 * @see Notification
 * @brief Class for Notification Manager.
 */

namespace APP\notification;

use APP\core\Application;
use APP\facades\Repo;
use APP\notification\managerDelegate\ApproveSubmissionNotificationManager;
use PKP\notification\PKPNotificationManager;
use PKP\plugins\HookRegistry;

class NotificationManager extends PKPNotificationManager
{
    /** @var array Cache each user's most privileged role for each submission */
    public $privilegedRoles;


    /**
     * Construct a URL for the notification based on its type and associated object
     *
     * @param PKPRequest $request
     * @param Notification $notification
     *
     * @return string
     */
    public function getNotificationUrl($request, $notification)
    {
        $router = $request->getRouter();
        $dispatcher = $router->getDispatcher();
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($notification->getContextId());

        switch ($notification->getType()) {
            case Notification::NOTIFICATION_TYPE_PUBLISHED_ISSUE:
                return $dispatcher->url($request, Application::ROUTE_PAGE, $context->getPath(), 'issue', 'current');
            default:
                return parent::getNotificationUrl($request, $notification);
        }
    }

    /**
     * Construct the contents for the notification based on its type and associated object
     *
     * @param PKPRequest $request
     * @param Notification $notification
     *
     * @return string
     */
    public function getNotificationMessage($request, $notification)
    {
        // Allow hooks to override default behavior
        $message = null;
        HookRegistry::call('NotificationManager::getNotificationMessage', [&$notification, &$message]);
        if ($message) {
            return $message;
        }

        switch ($notification->getType()) {
            case Notification::NOTIFICATION_TYPE_PUBLISHED_ISSUE:
                return __('notification.type.issuePublished');
            case Notification::NOTIFICATION_TYPE_BOOK_REQUESTED:
                return __('plugins.generic.booksForReview.notification.bookRequested');
            case Notification::NOTIFICATION_TYPE_BOOK_CREATED:
                return __('plugins.generic.booksForReview.notification.bookCreated');
            case Notification::NOTIFICATION_TYPE_BOOK_UPDATED:
                return __('plugins.generic.booksForReview.notification.bookUpdated');
            case Notification::NOTIFICATION_TYPE_BOOK_DELETED:
                return __('plugins.generic.booksForReview.notification.bookDeleted');
            case Notification::NOTIFICATION_TYPE_BOOK_MAILED:
                return __('plugins.generic.booksForReview.notification.bookMailed');
            case Notification::NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED:
                return __('plugins.generic.booksForReview.notification.settingsSaved');
            case Notification::NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED:
                return __('plugins.generic.booksForReview.notification.submissionAssigned');
            case Notification::NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED:
                return __('plugins.generic.booksForReview.notification.authorAssigned');
            case Notification::NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED:
                return __('plugins.generic.booksForReview.notification.authorDenied');
            case Notification::NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED:
                return __('plugins.generic.booksForReview.notification.authorRemoved');
            default:
                return parent::getNotificationMessage($request, $notification);
        }
    }

    /**
     * Helper function to get an article title from a notification's associated object
     *
     * @param Notification $notification
     *
     * @return string
     */
    public function _getArticleTitle($notification)
    {
        assert($notification->getAssocType() == ASSOC_TYPE_SUBMISSION);
        assert(is_numeric($notification->getAssocId()));
        $article = Repo::submission()->get($notification->getAssocId());
        if (!$article) {
            return null;
        }
        return $article->getLocalizedTitle();
    }

    /**
     * get notification style class
     *
     * @param Notification $notification
     *
     * @return string
     */
    public function getStyleClass($notification)
    {
        switch ($notification->getType()) {
            case Notification::NOTIFICATION_TYPE_BOOK_REQUESTED:
            case Notification::NOTIFICATION_TYPE_BOOK_CREATED:
            case Notification::NOTIFICATION_TYPE_BOOK_UPDATED:
            case Notification::NOTIFICATION_TYPE_BOOK_DELETED:
            case Notification::NOTIFICATION_TYPE_BOOK_MAILED:
            case Notification::NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED:
            case Notification::NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED:
            case Notification::NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED:
            case Notification::NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED:
            case Notification::NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED:
                    return 'notifySuccess';
            default: return parent::getStyleClass($notification);
        }
    }

    /**
     * Return a CSS class containing the icon of this notification type
     *
     * @param Notification $notification
     *
     * @return string
     */
    public function getIconClass($notification)
    {
        switch ($notification->getType()) {
            case Notification::NOTIFICATION_TYPE_PUBLISHED_ISSUE:
                return 'notifyIconPublished';
            case Notification::NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
                return 'notifyIconNewAnnouncement';
            case Notification::NOTIFICATION_TYPE_BOOK_REQUESTED:
            case Notification::NOTIFICATION_TYPE_BOOK_CREATED:
            case Notification::NOTIFICATION_TYPE_BOOK_UPDATED:
            case Notification::NOTIFICATION_TYPE_BOOK_DELETED:
            case Notification::NOTIFICATION_TYPE_BOOK_MAILED:
            case Notification::NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED:
            case Notification::NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED:
            case Notification::NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED:
            case Notification::NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED:
            case Notification::NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED:
                return 'notifyIconSuccess';
            default: return parent::getIconClass($notification);
        }
    }

    /**
     * @copydoc PKPNotificationManager::getMgrDelegate()
     */
    protected function getMgrDelegate($notificationType, $assocType, $assocId)
    {
        switch ($notificationType) {
            case Notification::NOTIFICATION_TYPE_APPROVE_SUBMISSION:
            case Notification::NOTIFICATION_TYPE_VISIT_CATALOG:
                assert($assocType == ASSOC_TYPE_SUBMISSION && is_numeric($assocId));
                return new ApproveSubmissionNotificationManager($notificationType);
        }
        // Otherwise, fall back on parent class
        return parent::getMgrDelegate($notificationType, $assocType, $assocId);
    }

    /**
     * @copydoc PKPNotificationManager::getNotificationSettingsMap()
     */
    public function getNotificationSettingsMap()
    {
        $settingsMap = parent::getNotificationSettingsMap();
        $settingsMap[Notification::NOTIFICATION_TYPE_PUBLISHED_ISSUE] = [
            'settingName' => 'notificationPublishedIssue',
            'emailSettingName' => 'emailNotificationPublishedIssue',
            'settingKey' => 'notification.type.issuePublished',
        ];
        return $settingsMap;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\notification\NotificationManager', '\NotificationManager');
}
