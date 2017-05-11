<?php

/**
 * @file classes/notification/PKPNotificationManager.inc.php
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

import('lib.pkp.classes.notification.PKPNotificationOperationManager');
import('lib.pkp.classes.workflow.WorkflowStageDAO');

class PKPNotificationManager extends PKPNotificationOperationManager {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Construct a URL for the notification based on its type and associated object
	 * @copydoc PKPNotificationOperationManager::getNotificationContents()
	 */
	public function getNotificationUrl($request, $notification) {
		$url = parent::getNotificationUrl($request, $notification);
		$dispatcher = Application::getDispatcher();
		$contextDao = Application::getContextDAO();
		$context = $contextDao->getById($notification->getContextId());

		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_COPYEDIT_ASSIGNMENT:
			case NOTIFICATION_TYPE_LAYOUT_ASSIGNMENT:
			case NOTIFICATION_TYPE_INDEX_ASSIGNMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_SUBMISSION && is_numeric($notification->getAssocId()));
				return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'workflow', 'access', $notification->getAssocId());
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_REVIEW_ASSIGNMENT && is_numeric($notification->getAssocId()));
				$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
				$reviewAssignment = $reviewAssignmentDao->getById($notification->getAssocId());
				$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
				$operation = $reviewAssignment->getStageId()==WORKFLOW_STAGE_ID_INTERNAL_REVIEW?WORKFLOW_STAGE_PATH_INTERNAL_REVIEW:WORKFLOW_STAGE_PATH_EXTERNAL_REVIEW;
				return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'workflow', $operation, $reviewAssignment->getSubmissionId());
			case NOTIFICATION_TYPE_REVIEW_ASSIGNMENT:
				$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
				$reviewAssignment = $reviewAssignmentDao->getById($notification->getAssocId());
				return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'reviewer', 'submission', $reviewAssignment->getSubmissionId());
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_ANNOUNCEMENT);
				$announcementDao = DAORegistry::getDAO('AnnouncementDAO'); /* @var $announcementDao AnnouncementDAO */
				$announcement = $announcementDao->getById($notification->getAssocId()); /* @var $announcement Announcement */
				$context = $contextDao->getById($announcement->getAssocId());
				return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'announcement', 'view', array($notification->getAssocId()));
			case NOTIFICATION_TYPE_CONFIGURE_PAYMENT_METHOD:
				return __('notification.type.configurePaymentMethod');
			default:
				$delegateResult = $this->getByDelegate(
					$notification->getType(),
					$notification->getAssocType(),
					$notification->getAssocId(),
					__FUNCTION__,
					array($request, $notification)
				);

				if ($delegateResult) $url = $delegateResult;

				return $url;
		}
	}

	/**
	 * Return a message string for the notification based on its type
	 * and associated object.
	 * @copydoc PKPNotificationOperationManager::getNotificationContents()
	 */
	public function getNotificationMessage($request, $notification) {
		$message = parent::getNotificationMessage($request, $notification);
		$type = $notification->getType();
		assert(isset($type));
		$submissionDao = Application::getSubmissionDAO();

		switch ($type) {
			case NOTIFICATION_TYPE_SUCCESS:
			case NOTIFICATION_TYPE_ERROR:
			case NOTIFICATION_TYPE_WARNING:
				if (!is_null($this->getNotificationSettings($notification->getId()))) {
					$notificationSettings = $this->getNotificationSettings($notification->getId());
					return $notificationSettings['contents'];
				} else {
					return __('common.changesSaved');
				}
			case NOTIFICATION_TYPE_FORM_ERROR:
			case NOTIFICATION_TYPE_ERROR:
				$notificationSettings = $this->getNotificationSettings($notification->getId());
				assert(!is_null($notificationSettings['contents']));
				return $notificationSettings['contents'];
			case NOTIFICATION_TYPE_PLUGIN_ENABLED:
				return $this->_getTranslatedKeyWithParameters('common.pluginEnabled', $notification->getId());
			case NOTIFICATION_TYPE_PLUGIN_DISABLED:
				return $this->_getTranslatedKeyWithParameters('common.pluginDisabled', $notification->getId());
			case NOTIFICATION_TYPE_LOCALE_INSTALLED:
				return $this->_getTranslatedKeyWithParameters('admin.languages.localeInstalled', $notification->getId());
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_ANNOUNCEMENT);
				return __('notification.type.newAnnouncement');
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_REVIEW_ASSIGNMENT && is_numeric($notification->getAssocId()));
				$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
				$reviewAssignment = $reviewAssignmentDao->getById($notification->getAssocId());
				$submission = $submissionDao->getById($reviewAssignment->getSubmissionId()); /* @var $submission Submission */
				return __('notification.type.reviewerComment', array('title' => $submission->getLocalizedTitle()));
			case NOTIFICATION_TYPE_COPYEDIT_ASSIGNMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_SUBMISSION && is_numeric($notification->getAssocId()));
				$submission = $submissionDao->getById($notification->getAssocId());
				return __('notification.type.copyeditorRequest', array('title' => $submission->getLocalizedTitle()));
			case NOTIFICATION_TYPE_LAYOUT_ASSIGNMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_SUBMISSION && is_numeric($notification->getAssocId()));
				$submission = $submissionDao->getById($notification->getAssocId());
				return __('notification.type.layouteditorRequest', array('title' => $submission->getLocalizedTitle()));
			case NOTIFICATION_TYPE_INDEX_ASSIGNMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_SUBMISSION && is_numeric($notification->getAssocId()));
				$submission = $submissionDao->getById($notification->getAssocId());
				return __('notification.type.indexRequest', array('title' => $submission->getLocalizedTitle()));
			case NOTIFICATION_TYPE_REVIEW_ASSIGNMENT:
				return __('notification.type.reviewAssignment');
			case NOTIFICATION_TYPE_REVIEW_ROUND_STATUS:
				assert($notification->getAssocType() == ASSOC_TYPE_REVIEW_ROUND && is_numeric($notification->getAssocId()));
				$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
				$reviewRound = $reviewRoundDao->getById($notification->getAssocId());
				$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');

				AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR); // load review round status keys.
				$user = $request->getUser();
				$stageAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($reviewRound->getSubmissionId(), ROLE_ID_AUTHOR, null, $user->getId());
				$isAuthor = $stageAssignments->getCount()>0;
				$stageAssignments->close();
				return __($reviewRound->getStatusKey($isAuthor));
			default:
				$delegateResult = $this->getByDelegate(
					$notification->getType(),
					$notification->getAssocType(),
					$notification->getAssocId(),
					__FUNCTION__,
					array($request, $notification)
				);

				if ($delegateResult) $message = $delegateResult;

				return $message;
		}
	}

	/**
	 * Using the notification message, construct, if needed, any additional
	 * content for the notification body. If a specific notification type
	 * is not defined, it will return the parent method return value.
	 * Define a notification type case on this method only if you need to
	 * present more than just text in notification. If you need to define
	 * just a locale key, use the getNotificationMessage method only.
	 * @copydoc PKPNotificationOperationManager::getNotificationContents()
	 */
	public function getNotificationContents($request, $notification) {
		$content = parent::getNotificationContents($request, $notification);
		$type = $notification->getType();
		assert(isset($type));

		switch ($type) {
			case NOTIFICATION_TYPE_FORM_ERROR:
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->assign('errors', $content);
				return $templateMgr->fetch('controllers/notification/formErrorNotificationContent.tpl');
			case NOTIFICATION_TYPE_ERROR:
				if (is_array($content)) {
					$templateMgr->assign('errors', $content);
					return $templateMgr->fetch('controllers/notification/errorNotificationContent.tpl');
				} else {
					return $content;
				}
			default:
				$delegateResult = $this->getByDelegate(
					$notification->getType(),
					$notification->getAssocType(),
					$notification->getAssocId(),
					__FUNCTION__,
					array($request, $notification)
				);

				if ($delegateResult) $content = $delegateResult;
				return $content;
		}
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationContents()
	 */
	public function getNotificationTitle($notification) {
		$title = parent::getNotificationTitle($notification);
		$type = $notification->getType();
		assert(isset($type));

		switch ($type) {
			case NOTIFICATION_TYPE_REVIEW_ROUND_STATUS:
				$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
				$reviewRound = $reviewRoundDao->getById($notification->getAssocId());
				return __('notification.type.roundStatusTitle', array('round' => $reviewRound->getRound()));
			case NOTIFICATION_TYPE_FORM_ERROR:
				return __('form.errorsOccurred');
			default:
				$delegateResult = $this->getByDelegate(
					$notification->getType(),
					$notification->getAssocType(),
					$notification->getAssocId(),
					__FUNCTION__,
					array($notification)
				);

				if ($delegateResult) $title = $delegateResult;
				return $title;
		}
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationContents()
	 */
	public function getStyleClass($notification) {
		$styleClass = parent::getStyleClass($notification);
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_SUCCESS: return NOTIFICATION_STYLE_CLASS_SUCCESS;
			case NOTIFICATION_TYPE_WARNING: return NOTIFICATION_STYLE_CLASS_WARNING;
			case NOTIFICATION_TYPE_ERROR: return NOTIFICATION_STYLE_CLASS_ERROR;
			case NOTIFICATION_TYPE_INFORMATION: return NOTIFICATION_STYLE_CLASS_INFORMATION;
			case NOTIFICATION_TYPE_FORBIDDEN: return NOTIFICATION_STYLE_CLASS_FORBIDDEN;
			case NOTIFICATION_TYPE_HELP: return NOTIFICATION_STYLE_CLASS_HELP;
			case NOTIFICATION_TYPE_FORM_ERROR: return NOTIFICATION_STYLE_CLASS_FORM_ERROR;
			case NOTIFICATION_TYPE_REVIEW_ROUND_STATUS:	return NOTIFICATION_STYLE_CLASS_INFORMATION;
			default:
				$delegateResult = $this->getByDelegate(
					$notification->getType(),
					$notification->getAssocType(),
					$notification->getAssocId(),
					__FUNCTION__,
					array($notification)
				);
				if ($delegateResult) $styleClass = $delegateResult;
				return $styleClass;
		}
	}

	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationContents()
	 */
	public function getIconClass($notification) {
		$iconClass = parent::getIconClass($notification);
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_SUCCESS: return 'notifyIconSuccess';
			case NOTIFICATION_TYPE_WARNING: return 'notifyIconWarning';
			case NOTIFICATION_TYPE_ERROR: return 'notifyIconError';
			case NOTIFICATION_TYPE_INFORMATION: return 'notifyIconInfo';
			case NOTIFICATION_TYPE_FORBIDDEN: return 'notifyIconForbidden';
			case NOTIFICATION_TYPE_HELP: return 'notifyIconHelp';
			default:
				$delegateResult = $this->getByDelegate(
					$notification->getType(),
					$notification->getAssocType(),
					$notification->getAssocId(),
					__FUNCTION__,
					array($notification)
				);
				if ($delegateResult) $iconClass = $delegateResult;
				return $iconClass;
		}
	}

	/**
	 * @copydoc PKPNotificationOperationManager::isVisibleToAllUsers()
	 */
	public function isVisibleToAllUsers($notificationType, $assocType, $assocId) {
		$isVisible = parent::isVisibleToAllUsers($notificationType, $assocType, $assocId);
		switch ($notificationType) {
			case NOTIFICATION_TYPE_REVIEW_ROUND_STATUS:
			case NOTIFICATION_TYPE_APPROVE_SUBMISSION:
			case NOTIFICATION_TYPE_VISIT_CATALOG:
			case NOTIFICATION_TYPE_CONFIGURE_PAYMENT_METHOD:
				$isVisible = true;
				break;
			default:
				$delegateResult = $this->getByDelegate(
					$notificationType,
					$assocType,
					$assocId,
					__FUNCTION__,
					array($notificationType, $assocType, $assocId)
				);
				if (!is_null($delegateResult)) $isVisible = $delegateResult;
				break;
		}
		return $isVisible;
	}

	/**
	 * Update notifications by type using a delegate. If you want to be able to use
	 * this method to update notifications associated with a certain type, you need
	 * to first create a manager delegate and define it in getMgrDelegate() method.
	 * @param $request PKPRequest
	 * @param $notificationTypes array The type(s) of the notification(s) to
	 * be updated.
	 * @param $userIds array|null The notification user(s) id(s), or null for all.
	 * @param $assocType int ASSOC_TYPE_... The notification associated object type.
	 * @param $assocId int The notification associated object id.
	 * @return mixed Return false if no operation is executed or the last operation
	 * returned value.
	 */
	final public function updateNotification($request, $notificationTypes, $userIds, $assocType, $assocId) {
		$returner = false;
		foreach ($notificationTypes as $type) {
			$managerDelegate = $this->getMgrDelegate($type, $assocType, $assocId);
			if (!is_null($managerDelegate) && is_a($managerDelegate, 'NotificationManagerDelegate')) {
				$returner = $managerDelegate->updateNotification($request, $userIds, $assocType, $assocId);
			} else {
				assert(false);
			}
		}

		return $returner;
	}


	//
	// Protected methods
	//
	/**
	 * Get the notification manager delegate based on the passed notification type.
	 * @param $notificationType int
	 * @param $assocType int
	 * @param $assocId int
	 * @return mixed Null or NotificationManagerDelegate
	 */
	protected function getMgrDelegate($notificationType, $assocType, $assocId) {
		switch ($notificationType) {
			case NOTIFICATION_TYPE_SUBMISSION_SUBMITTED:
			case NOTIFICATION_TYPE_METADATA_MODIFIED:
			case NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_REQUIRED:
				assert($assocType == ASSOC_TYPE_SUBMISSION && is_numeric($assocId));
				import('lib.pkp.classes.notification.managerDelegate.SubmissionNotificationManager');
				return new SubmissionNotificationManager($notificationType);
			case NOTIFICATION_TYPE_NEW_QUERY:
			case NOTIFICATION_TYPE_QUERY_ACTIVITY:
				import('lib.pkp.classes.notification.managerDelegate.QueryNotificationManager');
				return new QueryNotificationManager($notificationType);
			case NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION:
			case NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW:
			case NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING:
			case NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION:
				assert($assocType == ASSOC_TYPE_SUBMISSION && is_numeric($assocId));
				import('lib.pkp.classes.notification.managerDelegate.EditorAssignmentNotificationManager');
				return new EditorAssignmentNotificationManager($notificationType);
			case NOTIFICATION_TYPE_EDITOR_DECISION_ACCEPT:
			case NOTIFICATION_TYPE_EDITOR_DECISION_EXTERNAL_REVIEW:
			case NOTIFICATION_TYPE_EDITOR_DECISION_PENDING_REVISIONS:
			case NOTIFICATION_TYPE_EDITOR_DECISION_RESUBMIT:
			case NOTIFICATION_TYPE_EDITOR_DECISION_DECLINE:
			case NOTIFICATION_TYPE_EDITOR_DECISION_SEND_TO_PRODUCTION:
				assert($assocType == ASSOC_TYPE_SUBMISSION && is_numeric($assocId));
				import('lib.pkp.classes.notification.managerDelegate.EditorDecisionNotificationManager');
				return new EditorDecisionNotificationManager($notificationType);
			case NOTIFICATION_TYPE_PENDING_INTERNAL_REVISIONS:
			case NOTIFICATION_TYPE_PENDING_EXTERNAL_REVISIONS:
				assert($assocType == ASSOC_TYPE_SUBMISSION && is_numeric($assocId));
				import('lib.pkp.classes.notification.managerDelegate.PendingRevisionsNotificationManager');
				return new PendingRevisionsNotificationManager($notificationType);
			case NOTIFICATION_TYPE_ALL_REVISIONS_IN:
				assert($assocType == ASSOC_TYPE_REVIEW_ROUND && is_numeric($assocId));
				import('lib.pkp.classes.notification.managerDelegate.review.AllRevisionsInNotificationManager');
				return new AllRevisionsInNotificationManager($notificationType);
			case NOTIFICATION_TYPE_ALL_REVIEWS_IN:
				assert($assocType == ASSOC_TYPE_REVIEW_ROUND && is_numeric($assocId));
				import('lib.pkp.classes.notification.managerDelegate.review.AllReviewsInNotificationManager');
				return new AllReviewsInNotificationManager($notificationType);
			case NOTIFICATION_TYPE_ASSIGN_COPYEDITOR:
			case NOTIFICATION_TYPE_AWAITING_COPYEDITS:
			case NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER:
			case NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS:
				assert($assocType == ASSOC_TYPE_SUBMISSION && is_numeric($assocId));
				import('lib.pkp.classes.notification.managerDelegate.EditingProductionStatusNotificationManager');
				return new EditingProductionStatusNotificationManager($notificationType);
		}
		return null; // No delegate required, let calling context handle null.
	}

	/**
	 * Try to use a delegate to retrieve a notification data that's defined
	 * by the implementation of the
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @param $operationName string
	 */
	protected function getByDelegate($notificationType, $assocType, $assocId, $operationName, $parameters) {
		$delegate = $this->getMgrDelegate($notificationType, $assocType, $assocId);
		if (is_a($delegate, 'NotificationManagerDelegate')) {
			return call_user_func_array(array($delegate, $operationName), $parameters);
		} else {
			return null;
		}
	}


	//
	// Private helper methods.
	//
	/**
	 * Return notification settings.
	 * @param $notificationId int
	 * @return Array
	 */
	private function getNotificationSettings($notificationId) {
		$notificationSettingsDao = DAORegistry::getDAO('NotificationSettingsDAO'); /* @var $notificationSettingsDao NotificationSettingsDAO */
		$notificationSettings = $notificationSettingsDao->getNotificationSettings($notificationId);
		if (empty($notificationSettings)) {
			return null;
		} else {
			return $notificationSettings;
		}
	}

	/**
	 * Helper function to get a translated string from a notification with parameters
	 * @param $key string
	 * @param $notificationId int
	 * @return String
	 */
	private function _getTranslatedKeyWithParameters($key, $notificationId) {
		$params = $this->getNotificationSettings($notificationId);
		return __($key, $this->getParamsForCurrentLocale($params));
	}
}

?>
