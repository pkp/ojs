<?php

/**
 * @file classes/notification/NotificationManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
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
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_SUCCESS:
				return __('gifts.giftRedeemed');
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_NO_GIFT_TO_REDEEM:
				return __('gifts.noGiftToRedeem');
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_ALREADY_REDEEMED:
				return __('gifts.giftAlreadyRedeemed');
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_INVALID:
				return __('gifts.giftNotValid');
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_TYPE_INVALID:
				return __('gifts.subscriptionTypeNotValid');
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_NON_EXPIRING:
				return __('gifts.subscriptionNonExpiring');
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
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_SUCCESS:
					return 'notifySuccess';
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_NO_GIFT_TO_REDEEM:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_ALREADY_REDEEMED:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_INVALID:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_TYPE_INVALID:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_NON_EXPIRING:
					return 'notifyError';
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
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_SUCCESS:
				return 'notifyIconSuccess';
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_NO_GIFT_TO_REDEEM:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_ALREADY_REDEEMED:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_INVALID:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_TYPE_INVALID:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_NON_EXPIRING:
				return 'notifyIconError';
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
                }
                // Otherwise, fall back on parent class
                return parent::getMgrDelegate($notificationType, $assocType, $assocId);
        }
}

?>
