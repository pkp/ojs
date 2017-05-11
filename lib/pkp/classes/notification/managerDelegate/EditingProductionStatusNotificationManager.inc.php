<?php

/**
 * @file classes/notification/managerDelegate/EditingProductionStatusNotificationManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditingProductionStatusNotificationManager
 * @ingroup classses_notification_managerDelegate
 *
 * @brief Editing and productionstatus notifications types manager delegate.
 */

import('lib.pkp.classes.notification.NotificationManagerDelegate');

class EditingProductionStatusNotificationManager extends NotificationManagerDelegate {

	/**
	 * Constructor.
	 * @param $notificationType int NOTIFICATION_TYPE_...
	 */
	function __construct($notificationType) {
		parent::__construct($notificationType);
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationMessage()
	 */
	public function getNotificationMessage($request, $notification) {
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_ASSIGN_COPYEDITOR:
				return __('notification.type.assignCopyeditors');
			case NOTIFICATION_TYPE_AWAITING_COPYEDITS:
				return __('notification.type.awaitingCopyedits');
			case NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER:
				return __('notification.type.assignProductionUser');
			case NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS:
				return __('notification.type.awaitingRepresentations');
			default:
				assert(false);
		}
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationUrl()
	 */
	public function getNotificationUrl($request, $notification) {
		$url = parent::getNotificationUrl($request, $notification);
		$dispatcher = Application::getDispatcher();
		$contextDao = Application::getContextDAO();
		$context = $contextDao->getById($notification->getContextId());

		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_ASSIGN_COPYEDITOR:
			case NOTIFICATION_TYPE_AWAITING_COPYEDITS:
			case NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER:
			case NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS:
				assert($notification->getAssocType() == ASSOC_TYPE_SUBMISSION && is_numeric($notification->getAssocId()));
				return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'workflow', 'access', $notification->getAssocId());
			default:
				assert(false);
		}
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getStyleClass()
	 */
	public function getStyleClass($notification) {
		return NOTIFICATION_STYLE_CLASS_INFORMATION;
	}

	/**
	 * @copydoc NotificationManagerDelegate::updateNotification()
	 */
	public function updateNotification($request, $userIds, $assocType, $assocId) {
		$context = $request->getContext();
		$contextId = $context->getId();

		assert($assocType == ASSOC_TYPE_SUBMISSION);
		$submissionId = $assocId;
		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($submissionId);

		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$editorStageAssignments = $stageAssignmentDao->getEditorsAssignedToStage($submissionId, $submission->getStageId());

		// Get the copyediting and production discussions
		$queryDao = DAORegistry::getDAO('QueryDAO'); /* @var $queryDao QueryDAO */
		$editingQueries = $queryDao->getByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId, WORKFLOW_STAGE_ID_EDITING);
		$productionQueries = $queryDao->getByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId, WORKFLOW_STAGE_ID_PRODUCTION);

		// Get the copyedited files
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.submission.SubmissionFile');
		$copyeditedFiles = $submissionFileDao->getLatestRevisions($submissionId, SUBMISSION_FILE_COPYEDIT);

		// Get representations
		$representationDao = Application::getRepresentationDAO();
		$representations = $representationDao->getBySubmissionId($submissionId, $contextId);

		$notificationType = $this->getNotificationType();

		foreach ($editorStageAssignments as $editorStageAssignment) {
			switch ($submission->getStageId()) {
				case WORKFLOW_STAGE_ID_PRODUCTION:
					if ($notificationType == NOTIFICATION_TYPE_ASSIGN_COPYEDITOR || $notificationType == NOTIFICATION_TYPE_AWAITING_COPYEDITS) {
						// Remove 'assign a copyeditor' and 'awaiting copyedits' notification
						$this->_removeNotification($submissionId, $editorStageAssignment->getUserId(), $notificationType, $contextId);
					} else {
						// If there is a representation
						if (!$representations->wasEmpty()) {
							// Remove 'assign a production user' and 'awaiting representations' notification
							$this->_removeNotification($submissionId, $editorStageAssignment->getUserId(), $notificationType, $contextId);
						} else {
							// If a production user is assigned i.e. there is a production discussion
							if (!$productionQueries->wasEmpty()) {
								if ($notificationType == NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS) {
									// Add 'awaiting representations' notification
									$this->_createNotification(
										$request,
										$submissionId,
										$editorStageAssignment->getUserId(),
										$notificationType,
										$contextId
									);
								} elseif ($notificationType == NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER) {
									// Remove 'assign a production user' notification
									$this->_removeNotification($submissionId, $editorStageAssignment->getUserId(), $notificationType, $contextId);
								}
							} else {
								if ($notificationType == NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER) {
									// Add 'assign a user' notification
									$this->_createNotification(
										$request,
										$submissionId,
										$editorStageAssignment->getUserId(),
										$notificationType,
										$contextId
									);
								} elseif ($notificationType == NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS) {
									// Remove 'awaiting representations' notification
									$this->_removeNotification($submissionId, $editorStageAssignment->getUserId(), $notificationType, $contextId);
								}
							}
						}
					}
					break;
				case WORKFLOW_STAGE_ID_EDITING:
					if (!empty($copyeditedFiles)) {
						// Remove 'assign a copyeditor' and 'awaiting copyedits' notification
						$this->_removeNotification($submissionId, $editorStageAssignment->getUserId(), $notificationType, $contextId);
					} else {
						// If a copyeditor is assigned i.e. there is a copyediting discussion
						if (!$editingQueries->wasEmpty()) {
							if ($notificationType == NOTIFICATION_TYPE_AWAITING_COPYEDITS) {
								// Add 'awaiting copyedits' notification
								$this->_createNotification(
									$request,
									$submissionId,
									$editorStageAssignment->getUserId(),
									$notificationType,
									$contextId
								);
							} elseif ($notificationType == NOTIFICATION_TYPE_ASSIGN_COPYEDITOR) {
								// Remove 'assign a copyeditor' notification
								$this->_removeNotification($submissionId, $editorStageAssignment->getUserId(), $notificationType, $contextId);
							}
						} else {
							if ($notificationType == NOTIFICATION_TYPE_ASSIGN_COPYEDITOR) {
								// Add 'assign a copyeditor' notification
								$this->_createNotification(
									$request,
									$submissionId,
									$editorStageAssignment->getUserId(),
									$notificationType,
									$contextId
								);
							} elseif ($notificationType == NOTIFICATION_TYPE_AWAITING_COPYEDITS) {
								// Remove 'awaiting copyedits' notification
								$this->_removeNotification($submissionId, $editorStageAssignment->getUserId(), $notificationType, $contextId);
							}
						}
					}
					break;
				default:
					assert(false);
			}
		}
	}

	//
	// Helper methods.
	//
	/**
	 * Remove a notification.
	 * @param $submissionId int
	 * @param $userId int
	 * @param $notificationType int NOTIFICATION_TYPE_
	 * @param $contextId int
	 */
	function _removeNotification($submissionId, $userId, $notificationType, $contextId) {
		$notificationDao = DAORegistry::getDAO('NotificationDAO'); /* @var $notificationDao NotificationDAO */
		$notificationDao->deleteByAssoc(
			ASSOC_TYPE_SUBMISSION,
			$submissionId,
			$userId,
			$notificationType,
			$contextId
		);
	}

	/**
	 * Create a notification if none exists.
	 * @param $request PKPRequest
	 * @param $submissionId int
	 * @param $userId int
	 * @param $notificationType int NOTIFICATION_TYPE_
	 * @param $contextId int
	 */
	function _createNotification($request, $submissionId, $userId, $notificationType, $contextId) {
		$notificationDao = DAORegistry::getDAO('NotificationDAO'); /* @var $notificationDao NotificationDAO */
		$notificationFactory = $notificationDao->getByAssoc(
			ASSOC_TYPE_SUBMISSION,
			$submissionId,
			$userId,
			$notificationType,
			$contextId
		);
		if ($notificationFactory->wasEmpty()) {
			$notificationMgr = new NotificationManager();
			$notificationMgr->createNotification(
				$request,
				$userId,
				$notificationType,
				$contextId,
				ASSOC_TYPE_SUBMISSION,
				$submissionId,
				NOTIFICATION_LEVEL_NORMAL,
				null,
				true
			);
		}
	}

}

?>
