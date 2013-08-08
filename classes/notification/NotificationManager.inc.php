<?php

/**
 * @file classes/notification/NotificationManager.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
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
	/* @var $privilegedRoles array Cache each user's most privileged role for each submission */
	var $privilegedRoles;

	/**
	 * Constructor.
	 */
	function NotificationManager() {
		parent::PKPNotificationManager();
	}


	/**
	 * Construct a URL for the notification based on its type and associated object
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @return string
	 */
	function getNotificationUrl($request, $notification) {
		$router = $request->getRouter();
		$dispatcher = $router->getDispatcher();
		$context = $request->getContext();

		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_PUBLISHED_ISSUE:
				return $dispatcher->url($request, ROUTE_PAGE, null, 'issue', 'current');
			default:
				return parent::getNotificationUrl($request, $notification);
		}
	}

	function _getCachedRole($request, $notification, $validRoles = null) {
		assert($notification->getAssocType() == ASSOC_TYPE_SUBMISSION);
		$articleId = (int) $notification->getAssocId();
		$userId = $notification->getUserId();

		// Check if we've already set the roles for this user and article, otherwise fetch them
		if(!isset($this->privilegedRoles[$userId][$articleId])) $this->privilegedRoles[$userId][$articleId] = $this->_getHighestPrivilegedRolesForArticle($request, $articleId);

		$roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */

		if(is_array($validRoles)) {
			// We've specified a list of roles that should be the only roles considered
			foreach ($this->privilegedRoles[$userId][$articleId] as $roleId) {
				// Get the first role that is in the validRoles list
				if (in_array($roleId, $validRoles)) {
					$role = $roleDao->newDataObject();
					$role->setId($roleId);
					return $role->getPath();
				}
			}
		} else {
			// Return first (most privileged) role
			$roleId = isset($this->privilegedRoles[$userId][$articleId][0]) ? $this->privilegedRoles[$userId][$articleId][0] : null;
			$role = $roleDao->newDataObject();
			$role->setId($roleId);
			return $role->getPath();
		}
	}

	/**
	 * Get a list of the most 'privileged' roles a user has associated with an article.  This will
	 *  determine the URL to point them to for notifications about articles.  Returns roles in
	 *  order of 'importance'
	 * @param $articleId
	 * @return array
	 */
	function _getHighestPrivilegedRolesForArticle($request, $articleId) {
		$user = $request->getUser();
		$userId = $user->getId();
		$articleDao = DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */

		$roles = array();

		// Check if user is editor
		$article = $articleDao->getById($articleId);
		if($article && Validation::isEditor($article->getJournalId())) {
			$roles[] = ROLE_ID_EDITOR;
		}

		// two stage check for section editors.
		// 1.  verify that the user generally has the SUB_EDITOR role in the journal...
		// 2.  verify that the user is assigned to a section in the journal.
		$roleDao = DAORegistry::getDAO('RoleDAO');
		if ($roleDao->userHasRole($article->getJournalId(), $userId, ROLE_ID_SUB_EDITOR)) {
			$sectionDao = DAORegistry::getDAO('SectionDAO');
			$editorSections = $sectionDao->getEditorSections($article->getJournalId());
			if (array_key_exists($userId, $editorSections)) {
				$roles[] = ROLE_ID_SUB_EDITOR;
			}
		}

		// Check if user is author
		if ($article && $userId == $article->getUserId()) $roles[] = ROLE_ID_AUTHOR;

		// Check if user is reviewer
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
		$reviewAssignments = $reviewAssignmentDao->getBySubmissionId($articleId);
		foreach ($reviewAssignments as $reviewAssignment) {
			if ($userId == $reviewAssignment->getReviewerId()) $roles[] = ROLE_ID_REVIEWER;
		}

		return $roles;
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
	 * Returns an array of information on the journal's subscription settings
	 * @return array
	 */
	function getSubscriptionSettings($request) {
		$journal = $request->getJournal();
		if (!$journal) return array();

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);

		$settings = array('subscriptionsEnabled' => $paymentManager->acceptSubscriptionPayments(),
				'allowRegReviewer' => $journal->getSetting('allowRegReviewer'),
				'allowRegAuthor' => $journal->getSetting('allowRegAuthor'));

		return $settings;
	}
}

?>
