<?php

/**
 * @file classes/notification/NotificationManager.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPNotificationManager
 * @ingroup notification
 * @see NotificationDAO
 * @see Notification
 * @brief Class for Notification Manager.
 */

import('lib.pkp.classes.notification.PKPNotificationManager');

class NotificationManager extends PKPNotificationManager {
	/* @var array Cache each user's most privileged role for each submission */
	var $privilegedRoles;


	/**
	 * Construct a URL for the notification based on its type and associated object
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @return string
	 */
	function getNotificationUrl($request, $notification) {
		$router = $request->getRouter();
		$dispatcher = $router->getDispatcher();
		$contextDao = Application::getContextDAO();
		$context = $contextDao->getById($notification->getContextId());

		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_PUBLISHED_ISSUE:
				return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'issue', 'current');
			default:
				return parent::getNotificationUrl($request, $notification);
		}
	}

	/**
	 * Construct the contents for the notification based on its type and associated object
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @return string
	 */
	function getNotificationMessage($request, $notification) {
		// Allow hooks to override default behavior
		$message = null;
		HookRegistry::call('NotificationManager::getNotificationMessage', array(&$notification, &$message));
		if($message) return $message;

		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_PUBLISHED_ISSUE:
				return __('notification.type.issuePublished');
			case NOTIFICATION_TYPE_BOOK_REQUESTED:
				return __('plugins.generic.booksForReview.notification.bookRequested');
			case NOTIFICATION_TYPE_BOOK_CREATED:
				return __('plugins.generic.booksForReview.notification.bookCreated');
			case NOTIFICATION_TYPE_BOOK_UPDATED:
				return __('plugins.generic.booksForReview.notification.bookUpdated');
			case NOTIFICATION_TYPE_BOOK_DELETED:
				return __('plugins.generic.booksForReview.notification.bookDeleted');
			case NOTIFICATION_TYPE_BOOK_MAILED:
				return __('plugins.generic.booksForReview.notification.bookMailed');
			case NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED:
				return __('plugins.generic.booksForReview.notification.settingsSaved');
			case NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED:
				return __('plugins.generic.booksForReview.notification.submissionAssigned');
			case NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED:
				return __('plugins.generic.booksForReview.notification.authorAssigned');
			case NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED:
				return __('plugins.generic.booksForReview.notification.authorDenied');
			case NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED:
				return __('plugins.generic.booksForReview.notification.authorRemoved');
			default:
				return parent::getNotificationMessage($request, $notification);
		}
	}

	/**
	 * Helper function to get an article title from a notification's associated object
	 * @param $notification
	 * @return string
	 */
	function _getArticleTitle($notification) {
		assert($notification->getAssocType() == ASSOC_TYPE_SUBMISSION);
		assert(is_numeric($notification->getAssocId()));
		$articleDao = DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$article = $articleDao->getById($notification->getAssocId());
		if (!$article) return null;
		return $article->getLocalizedTitle();
	}

	/**
	 * get notification style class
	 * @param $notification Notification
	 * @return string
	 */
	function getStyleClass($notification) {
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_BOOK_REQUESTED:
			case NOTIFICATION_TYPE_BOOK_CREATED:
			case NOTIFICATION_TYPE_BOOK_UPDATED:
			case NOTIFICATION_TYPE_BOOK_DELETED:
			case NOTIFICATION_TYPE_BOOK_MAILED:
			case NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED:
			case NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED:
					return 'notifySuccess';
			default: return parent::getStyleClass($notification);
		}
	}

	/**
	 * Return a CSS class containing the icon of this notification type
	 * @param $notification Notification
	 * @return string
	 */
	function getIconClass($notification) {
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_PUBLISHED_ISSUE:
				return 'notifyIconPublished';
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				return 'notifyIconNewAnnouncement';
			case NOTIFICATION_TYPE_BOOK_REQUESTED:
			case NOTIFICATION_TYPE_BOOK_CREATED:
			case NOTIFICATION_TYPE_BOOK_UPDATED:
			case NOTIFICATION_TYPE_BOOK_DELETED:
			case NOTIFICATION_TYPE_BOOK_MAILED:
			case NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED:
			case NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED:
				return 'notifyIconSuccess';
			default: return parent::getIconClass($notification);
		}
	}

	/**
	 * @copydoc PKPNotificationManager::getMgrDelegate()
	 */
	protected function getMgrDelegate($notificationType, $assocType, $assocId) {
		switch ($notificationType) {
			case NOTIFICATION_TYPE_APPROVE_SUBMISSION:
			case NOTIFICATION_TYPE_VISIT_CATALOG:
				assert($assocType == ASSOC_TYPE_SUBMISSION && is_numeric($assocId));
				import('classes.notification.managerDelegate.ApproveSubmissionNotificationManager');
				return new ApproveSubmissionNotificationManager($notificationType);
			case NOTIFICATION_TYPE_PUBLICATION_SCHEDULED:
				assert($assocType == ASSOC_TYPE_SUBMISSION && is_numeric($assocId));
				import('classes.notification.managerDelegate.EditingProductionStatusNotificationManager');
				return new EditingProductionStatusNotificationManager($notificationType);
		}
		// Otherwise, fall back on parent class
		return parent::getMgrDelegate($notificationType, $assocType, $assocId);
	}

}


