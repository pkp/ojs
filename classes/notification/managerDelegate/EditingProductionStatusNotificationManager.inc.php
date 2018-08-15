<?php

/**
 * @file classes/notification/managerDelegate/EditingProductionStatusNotificationManager.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditingProductionStatusNotificationManager
 * @ingroup classses_notification_managerDelegate
 *
 * @brief Editing and productionstatus notifications types manager delegate.
 */

import('lib.pkp.classes.notification.managerDelegate.PKPEditingProductionStatusNotificationManager');

class EditingProductionStatusNotificationManager extends PKPEditingProductionStatusNotificationManager {

	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationMessage()
	 */
	public function getNotificationMessage($request, $notification) {
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_PUBLICATION_SCHEDULED:
				return __('notification.type.publicationScheduled');
		}
		return parent::getNotificationMessage($request, $notification);
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationUrl()
	 */
	public function getNotificationUrl($request, $notification) {
		$dispatcher = Application::getDispatcher();
		$contextDao = Application::getContextDAO();
		$context = $contextDao->getById($notification->getContextId());

		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_PUBLICATION_SCHEDULED:
				assert($notification->getAssocType() == ASSOC_TYPE_SUBMISSION && is_numeric($notification->getAssocId()));
				return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'workflow', 'access', $notification->getAssocId());
		}
		return parent::getNotificationUrl($request, $notification);
	}

	/**
	 * @copydoc NotificationManagerDelegate::updateNotification()
	 */
	public function updateNotification($request, $userIds, $assocType, $assocId) {
		$notificationType = $this->getNotificationType();
		switch ($notificationType) {
			case NOTIFICATION_TYPE_PUBLICATION_SCHEDULED:
				assert($assocType == ASSOC_TYPE_SUBMISSION);
				$submissionId = $assocId;
				$submissionDao = Application::getSubmissionDAO();
				$submission = $submissionDao->getById($submissionId);
				if ($submission->getStageId() == WORKFLOW_STAGE_ID_PRODUCTION) {
					$context = $request->getContext();
					$contextId = $context->getId();
					$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
					$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
					$editorStageAssignments = $stageAssignmentDao->getEditorsAssignedToStage($submissionId, $submission->getStageId());
					foreach ($editorStageAssignments as $editorStageAssignment) {
						$publishedArticle = $publishedArticleDao->getByArticleId($submissionId, $contextId);
						if ($publishedArticle) {
							$this->_createNotification(
								$request,
								$submissionId,
								$editorStageAssignment->getUserId(),
								$notificationType,
								$contextId
							);
						} else {
							$this->_removeNotification($submissionId, $editorStageAssignment->getUserId(), $notificationType, $contextId);
						}
					}
				}
				break;
			default:
				parent::updateNotification($request, $userIds, $assocType, $assocId);
		}
	}

}


