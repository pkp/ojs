<?php

/**
 * @file classes/notification/managerDelegate/PendingRevisionsNotificationManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PendingRevisionsNotificationManager
 * @ingroup managerDelegate
 *
 * @brief Pending revision notification types manager delegate.
 */

import('lib.pkp.classes.notification.NotificationManagerDelegate');
import('lib.pkp.classes.workflow.WorkflowStageDAO');

class PendingRevisionsNotificationManager extends NotificationManagerDelegate {

	/**
	 * Constructor.
	 * @param $notificationType int NOTIFICATION_TYPE_...
	 */
	function __construct($notificationType) {
		parent::__construct($notificationType);
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getStyleClass()
	 */
	public function getStyleClass($notification) {
		return NOTIFICATION_STYLE_CLASS_WARNING;
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationUrl()
	 */
	public function getNotificationUrl($request, $notification) {
		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($notification->getAssocId());

		$stageData = $this->_getStageDataByType();
		$operation = $stageData['path'];

		import('lib.pkp.controllers.grid.submissions.SubmissionsListGridCellProvider');
		return SubmissionsListGridCellProvider::getUrlByUserRoles($request, $submission, $notification->getUserId(), $stageData['path']);
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationMessage()
	 */
	public function getNotificationMessage($request, $notification) {
		$stageData = $this->_getStageDataByType();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION); // For stage constants
		$stageKey = $stageData['translationKey'];

		return __('notification.type.pendingRevisions', array('stage' => __($stageKey)));
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationContents()
	 */
	public function getNotificationContents($request, $notification) {
		$stageData = $this->_getStageDataByType();
		$stageId = $stageData['id'];
		$submissionId = $notification->getAssocId();

		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($submissionId);
		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
		$lastReviewRound = $reviewRoundDao->getLastReviewRoundBySubmissionId($submission->getId(), $stageId);

		import('lib.pkp.controllers.api.file.linkAction.AddRevisionLinkAction');
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR); // editor.review.uploadRevision

		$uploadFileAction = new AddRevisionLinkAction(
			$request, $lastReviewRound, array(ROLE_ID_AUTHOR)
		);

		return $this->fetchLinkActionNotificationContent($uploadFileAction, $request);
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationTitle()
	 */
	public function getNotificationTitle($notification) {
		$stageData = $this->_getStageDataByType();
		$stageKey = $stageData['translationKey'];
		return __('notification.type.pendingRevisions.title', array('stage' => __($stageKey)));
	}

	/**
	 * @copydoc NotificationManagerDelegate::updateNotification()
	 */
	public function updateNotification($request, $userIds, $assocType, $assocId) {
		$userId = current($userIds);
		$submissionId = $assocId;
		$stageData = $this->_getStageDataByType();
		if ($stageData == null) return;
		$expectedStageId = $stageData['id'];

		$editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
		$pendingRevisionDecision = $editDecisionDao->findValidPendingRevisionsDecision($submissionId, $expectedStageId);
		$removeNotifications = false;

		if ($pendingRevisionDecision) {
			if ($editDecisionDao->responseExists($pendingRevisionDecision, $submissionId)) {
				// Some user already uploaded a revision. Flag to delete any existing notification.
				$removeNotifications = true;
			} else {
				$context = $request->getContext();
				$notificationDao = DAORegistry::getDAO('NotificationDAO'); /* @var $notificationDao NotificationDAO */
				$notificationFactory = $notificationDao->getByAssoc(
					ASSOC_TYPE_SUBMISSION,
					$submissionId,
					$userId,
					NOTIFICATION_TYPE_EDITOR_DECISION_PENDING_REVISIONS,
					$context->getId()
				);
				if ($notificationFactory->wasEmpty()) {
					// Create or update a pending revision task notification.
					$notificationDao = DAORegistry::getDAO('NotificationDAO'); /* @var $notificationDao NotificationDAO */
					$notificationDao->build(
						$context->getId(),
						NOTIFICATION_LEVEL_TASK,
						$this->getNotificationType(),
						ASSOC_TYPE_SUBMISSION,
						$submissionId,
						$userId
					);
				}
			}
		} else {
			// No pending revision decision or other later decision overriden it.
			// Flag to delete any existing notification.
			$removeNotifications = true;
		}

		if ($removeNotifications) {
			$context = $request->getContext();
			$notificationDao = DAORegistry::getDAO('NotificationDAO');
			$notificationDao->deleteByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId, $userId, $this->getNotificationType(), $context->getId());
			$notificationDao->deleteByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId, $userId, NOTIFICATION_TYPE_EDITOR_DECISION_PENDING_REVISIONS, $context->getId());
		}
	}


	//
	// Private helper methods.
	//
	/**
	 * Get the data for an workflow stage by
	 * pending revisions notification type.
	 * @return string
	 */
	private function _getStageDataByType() {
		$stagesData = WorkflowStageDAO::getWorkflowStageKeysAndPaths();

		switch ($this->getNotificationType()) {
			case NOTIFICATION_TYPE_PENDING_INTERNAL_REVISIONS:
				return array_key_exists(WORKFLOW_STAGE_ID_INTERNAL_REVIEW, $stagesData) ? $stagesData[WORKFLOW_STAGE_ID_INTERNAL_REVIEW] : null;
			case NOTIFICATION_TYPE_PENDING_EXTERNAL_REVISIONS:
				return $stagesData[WORKFLOW_STAGE_ID_EXTERNAL_REVIEW];
			default:
				assert(false);
		}
	}
}

?>
