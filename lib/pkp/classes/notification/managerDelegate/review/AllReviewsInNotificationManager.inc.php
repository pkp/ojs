<?php

/**
 * @file classes/notification/managerDelegate/review/AllReviewsInNotificationManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AllReviewsInNotificationManager
 * @ingroup classses_notification_managerDelegate_review
 *
 * @brief All reviews in notification types manager delegate.
 */

import('lib.pkp.classes.notification.managerDelegate.review.ReviewRoundNotificationManager');

class AllReviewsInNotificationManager extends ReviewRoundNotificationManager {

	/**
	 * Constructor.
	 * @param $notificationType int NOTIFICATION_TYPE_...
	 */
	function __construct($notificationType) {
		parent::__construct($notificationType);
	}


	/**
	 * @copydoc NotificationManagerDelegate::updateNotification()
	 */
	public function updateNotification($request, $userIds, $assocType, $assocId) {
		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
		$reviewRound = $reviewRoundDao->getById($assocId);

		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$stageAssignments = $stageAssignmentDao->getEditorsAssignedToStage($reviewRound->getSubmissionId(), $reviewRound->getStageId());

		$notificationDao = DAORegistry::getDAO('NotificationDAO'); /* @var $notificationDao NotificationDAO */
		$context = $request->getContext();
		$contextId = $context->getId();

		foreach ($stageAssignments as $stageAssignment) {
			$userId = $stageAssignment->getUserId();

			// Get any existing notification.
			$notificationFactory = $notificationDao->getByAssoc(
				ASSOC_TYPE_REVIEW_ROUND,
				$reviewRound->getId(), $userId,
				NOTIFICATION_TYPE_ALL_REVIEWS_IN,
				$contextId
			);

			$currentStatus = $reviewRound->getStatus();
			if (in_array($currentStatus, $reviewRoundDao->getEditorDecisionRoundStatus()) ||
			in_array($currentStatus, array(REVIEW_ROUND_STATUS_PENDING_REVIEWERS, REVIEW_ROUND_STATUS_PENDING_REVIEWS))) {
				// Editor has taken a decision in round or there are pending
				// reviews or no reviews. Delete any existing notification.
				if (!$notificationFactory->wasEmpty()) {
					$notification = $notificationFactory->next();
					$notificationDao->deleteObject($notification);
				}
			} else {
				// There is no currently decision in round. Also there is reviews,
				// but no pending reviews. Insert notification, if not already present.
				if ($notificationFactory->wasEmpty()) {
					$this->createNotification($request, $userId, NOTIFICATION_TYPE_ALL_REVIEWS_IN, $contextId,
						ASSOC_TYPE_REVIEW_ROUND, $reviewRound->getId(), NOTIFICATION_LEVEL_TASK);
				}
			}
		}
	}

	
	//
	// Protected methods.
	//
	/**
	 * @copydoc ReviewRoundNotificationManager::getMessageLocaleKey()
	 */
	protected function getMessageLocaleKey() {
		return 'notification.type.allReviewsIn';
	}	
}

?>
