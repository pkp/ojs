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
	 * Helper function to get an article title from a notification's associated object
	 * @param $notification
	 * @return string
	 */
	function _getArticleTitle($notification) {
		assert($notification->getAssocType() == ASSOC_TYPE_SUBMISSION);
		assert(is_numeric($notification->getAssocId()));
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$article = $submissionDao->getById($notification->getAssocId());
		if (!$article) return null;
		return $article->getLocalizedTitle();
	}

	/**
	 * Return a CSS class containing the icon of this notification type
	 * @param $notification Notification
	 * @return string
	 */
	function getIconClass($notification) {
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				return 'notifyIconNewAnnouncement';
			default: return parent::getIconClass($notification);
		}
	}

	/**
	 * @copydoc PKPNotificationManager::getMgrDelegate()
	 */
	protected function getMgrDelegate($notificationType, $assocType, $assocId) {
		switch ($notificationType) {
			case NOTIFICATION_TYPE_APPROVE_SUBMISSION:
				assert($assocType == ASSOC_TYPE_SUBMISSION && is_numeric($assocId));
				import('classes.notification.managerDelegate.ApproveSubmissionNotificationManager');
				return new ApproveSubmissionNotificationManager($notificationType);
		}
		// Otherwise, fall back on parent class
		return parent::getMgrDelegate($notificationType, $assocType, $assocId);
	}

}


