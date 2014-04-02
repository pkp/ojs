<?php

/**
 * @file classes/notification/NotificationManager.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
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
	/* @var $privilegedRoles array Cache each user's most privileged role for each article */
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
	function getNotificationUrl(&$request, &$notification) {
		$router =& $request->getRouter();
		$dispatcher =& $router->getDispatcher();
		$type = $notification->getType();

		switch ($type) {
			case NOTIFICATION_TYPE_ARTICLE_SUBMITTED:
				$role = $this->_getCachedRole($request, $notification);
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submission', $notification->getAssocId());
			case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_EDITOR, ROLE_ID_SECTION_EDITOR, ROLE_ID_AUTHOR));
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionEditing', $notification->getAssocId());
			case NOTIFICATION_TYPE_METADATA_MODIFIED:
				$role = $this->_getCachedRole($request, $notification);
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submission', $notification->getAssocId(), null, 'metadata');
			case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_EDITOR, ROLE_ID_SECTION_EDITOR, ROLE_ID_AUTHOR));
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionEditing', $notification->getAssocId(), null, 'layout');
			case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_EDITOR, ROLE_ID_SECTION_EDITOR, ROLE_ID_AUTHOR));
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionReview', $notification->getAssocId(), null, 'editorDecision');
			case NOTIFICATION_TYPE_LAYOUT_COMMENT:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_EDITOR, ROLE_ID_SECTION_EDITOR, ROLE_ID_AUTHOR));
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionEditing', $notification->getAssocId(), null, 'layout');
			case NOTIFICATION_TYPE_COPYEDIT_COMMENT:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_EDITOR, ROLE_ID_SECTION_EDITOR, ROLE_ID_AUTHOR));
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionEditing', $notification->getAssocId(), null, 'coypedit');
			case NOTIFICATION_TYPE_PROOFREAD_COMMENT:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_EDITOR, ROLE_ID_SECTION_EDITOR, ROLE_ID_AUTHOR));
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionEditing', $notification->getAssocId(), null, 'proofread');
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_EDITOR, ROLE_ID_SECTION_EDITOR, ROLE_ID_AUTHOR));
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionReview', $notification->getAssocId(), null, 'peerReview');
			case NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_EDITOR, ROLE_ID_SECTION_EDITOR, ROLE_ID_AUTHOR));
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionReview', $notification->getAssocId(), null, 'editorDecision');
			case NOTIFICATION_TYPE_USER_COMMENT:
				return $dispatcher->url($request, ROUTE_PAGE, null, 'comment', 'view', $notification->getAssocId());
			case NOTIFICATION_TYPE_PUBLISHED_ISSUE:
				return $dispatcher->url($request, ROUTE_PAGE, null, 'issue', 'current');
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_ANNOUNCEMENT);
				return $dispatcher->url($request, ROUTE_PAGE, null, 'announcement', 'view', array($notification->getAssocId()));
			default:
				return parent::getNotificationUrl($request, $notification);
		}
	}

	function _getCachedRole(&$request, &$notification, $validRoles = null) {
		assert($notification->getAssocType() == ASSOC_TYPE_ARTICLE);
		$articleId = (int) $notification->getAssocId();
		$userId = $notification->getUserId();

		// Check if we've already set the roles for this user and article, otherwise fetch them
		if(!isset($this->privilegedRoles[$userId][$articleId])) $this->privilegedRoles[$userId][$articleId] = $this->_getHighestPrivilegedRolesForArticle($request, $articleId);

		$roleDao =& DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */

		if(is_array($validRoles)) {
			// We've specified a list of roles that should be the only roles considered
			foreach ($this->privilegedRoles[$userId][$articleId] as $roleId) {
				// Get the first role that is in the validRoles list
				if (in_array($roleId, $validRoles)) {
					return $roleDao->getRolePath($roleId);
				}
			}
		} else {
			// Return first (most privileged) role
			$roleId = isset($this->privilegedRoles[$userId][$articleId][0]) ? $this->privilegedRoles[$userId][$articleId][0] : null;
			return $roleDao->getRolePath($roleId);
		}
	}

	/**
	 * Get a list of the most 'privileged' roles a user has associated with an article.  This will
	 *  determine the URL to point them to for notifications about articles.  Returns roles in
	 *  order of 'importance'
	 * @param $articleId
	 * @return array
	 */
	function _getHighestPrivilegedRolesForArticle(&$request, $articleId) {
		$user =& $request->getUser();
		$userId = $user->getId();
		$roleDao =& DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */
		$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */

		$roles = array();

		// Check if user is editor
		$article =& $articleDao->getArticle($articleId);
		if($article && Validation::isEditor($article->getJournalId())) {
			$roles[] = ROLE_ID_EDITOR;
		}

		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO'); /* @var $editAssignmentDao EditAssignmentDAO */
		$editAssignments =& $editAssignmentDao->getEditingSectionEditorAssignmentsByArticleId($articleId);
		while ($editAssignment =& $editAssignments->next()) {
			if ($userId == $editAssignment->getEditorId()) $roles[] = ROLE_ID_SECTION_EDITOR;
			unset($editAssignment);
		}

		// Check if user is copy/layout editor or proofreader
		$signoffDao =& DAORegistry::getDAO('SignoffDAO'); /* @var $signoffDao SignoffDAO */
		$copyedSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $articleId);
		if ($userId == $copyedSignoff->getUserId()) $roles[] = ROLE_ID_COPYEDITOR;

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
		if ($userId == $layoutSignoff->getUserId()) $roles[] = ROLE_ID_LAYOUT_EDITOR;

		$proofSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $articleId);
		if ($userId == $proofSignoff->getUserId()) $roles[] = ROLE_ID_PROOFREADER;

		// Check if user is author
		if ($article && $userId == $article->getUserId()) $roles[] = ROLE_ID_AUTHOR;

		// Check if user is reviewer
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
		$reviewAssignments =& $reviewAssignmentDao->getBySubmissionId($articleId);
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
	function getNotificationContents(&$request, &$notification) {
		$type = $notification->getType();
		assert(isset($type));

		$message = null;
		HookRegistry::call('NotificationManager::getNotificationContents', array(&$notification, &$message));
		if($message) return $message;

		switch ($type) {
			case NOTIFICATION_TYPE_ARTICLE_SUBMITTED:
				return __('notification.type.articleSubmitted', array('title' => $this->_getArticleTitle($notification)));
			case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				return __('notification.type.suppFileModified', array('title' => $this->_getArticleTitle($notification)));
			case NOTIFICATION_TYPE_METADATA_MODIFIED:
				return __('notification.type.metadataModified', array('title' => $this->_getArticleTitle($notification)));
			case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				return __('notification.type.galleyModified', array('title' => $this->_getArticleTitle($notification)));
			case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
				return __('notification.type.submissionComment', array('title' => $this->_getArticleTitle($notification)));
			case NOTIFICATION_TYPE_LAYOUT_COMMENT:
				return __('notification.type.layoutComment', array('title' => $this->_getArticleTitle($notification)));
			case NOTIFICATION_TYPE_COPYEDIT_COMMENT:
				return __('notification.type.copyeditComment', array('title' => $this->_getArticleTitle($notification)));
			case NOTIFICATION_TYPE_PROOFREAD_COMMENT:
				return __('notification.type.proofreadComment', array('title' => $this->_getArticleTitle($notification)));
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
				return __('notification.type.reviewerComment', array('title' => $this->_getArticleTitle($notification)));
			case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
				return __('notification.type.reviewerFormComment', array('title' => $this->_getArticleTitle($notification)));
			case NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT:
				return __('notification.type.editorDecisionComment', array('title' => $this->_getArticleTitle($notification)));
			case NOTIFICATION_TYPE_USER_COMMENT:
				return __('notification.type.userComment', array('title' => $this->_getArticleTitle($notification)));
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
				return parent::getNotificationContents($request, $notification);
		}
	}

	/**
	 * Helper function to get an article title from a notification's associated object
	 * @param $notification
	 * @return string
	 */
	function _getArticleTitle(&$notification) {
		assert($notification->getAssocType() == ASSOC_TYPE_ARTICLE);
		assert(is_numeric($notification->getAssocId()));
		$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$article =& $articleDao->getArticle($notification->getAssocId());
		if (!$article) return null;
		return $article->getLocalizedTitle();
	}

	/**
	 * get notification style class
	 * @param $notification Notification
	 * @return string
	 */
	function getStyleClass(&$notification) {
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
	function getIconClass(&$notification) {
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_ARTICLE_SUBMITTED:
				return 'notifyIconNewPage';
			case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				return 'notifyIconPageAttachment';
			case NOTIFICATION_TYPE_METADATA_MODIFIED:
			case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				return 'notifyIconEdit';
			case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
			case NOTIFICATION_TYPE_LAYOUT_COMMENT:
			case NOTIFICATION_TYPE_COPYEDIT_COMMENT:
			case NOTIFICATION_TYPE_PROOFREAD_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
			case NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT:
			case NOTIFICATION_TYPE_USER_COMMENT:
				return 'notifyIconNewComment';
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
	function getSubscriptionSettings(&$request) {
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
