<?php

/**
 * @file pages/workflow/WorkflowHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for the submssion workflow.
 */

import('lib.pkp.pages.workflow.PKPWorkflowHandler');

// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');

class WorkflowHandler extends PKPWorkflowHandler {
	/**
	 * Constructor
	 */
	function WorkflowHandler() {
		parent::PKPWorkflowHandler();

		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array(
				'access', 'submission',
				'editorDecisionActions', // Submission & review
				'externalReview', // review
				'editorial',
				'production', 'galleysTab', // Production
				'submissionProgressBar'
			)
		);
	}


	//
	// Public handler methods
	//
	/**
	 * Show the production stage
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function production(&$args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$submission =& $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$notificationRequestOptions = array(
			NOTIFICATION_LEVEL_NORMAL => array(
				NOTIFICATION_TYPE_VISIT_CATALOG => array(ASSOC_TYPE_SUBMISSION, $submission->getId()),
				NOTIFICATION_TYPE_APPROVE_SUBMISSION => array(ASSOC_TYPE_SUBMISSION, $submission->getId()),
			),
			NOTIFICATION_LEVEL_TRIVIAL => array()
		);

		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galleys =& $galleyDao->getGalleysByArticle($submission->getId());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('galleys', $galleys);

		$templateMgr->assign('productionNotificationRequestOptions', $notificationRequestOptions);
		$templateMgr->display('workflow/production.tpl');
	}

	/**
	 * Fetch the JSON-encoded submission progress bar.
	 * @param $args array
	 * @param $request Request
	 */
	function submissionProgressBar($args, $request) {
		// Assign the actions to the template.
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$workflowStages = $userGroupDao->getWorkflowStageKeysAndPaths();
		$stageNotifications = array();
		foreach (array_keys($workflowStages) as $stageId) {
			$stageNotifications[$stageId] = $this->_notificationOptionsByStage($request->getUser(), $stageId, $context->getId());
		}

		$templateMgr->assign('stageNotifications', $stageNotifications);

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getPublishedArticleById($submission->getId());
		if ($publishedArticle) { // check to see if there os a published article
			$templateMgr->assign('submissionIsReady', true);
		}
		return $templateMgr->fetchJson('workflow/submissionProgressBar.tpl');
	}

	/**
	 * Show the production stage accordion contents
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function galleysTab(&$args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$submission =& $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$galleys =& $galleyDao->getGalleysByArticle($submission->getId());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('galleys', $galleys->toAssociativeArray());
		$templateMgr->assign('currentGalleyTabId', (int) $request->getUserVar('currentGalleyTabId'));

		return $templateMgr->fetchJson('workflow/galleysTab.tpl');
	}

	/**
	 * Determine if a particular stage has a notification pending.  If so, return true.
	 * This is used to set the CSS class of the submission progress bar.
	 * @param PKPUser $user
	 * @param int $stageId
	 */
	function _notificationOptionsByStage(&$user, $stageId, $contextId) {

		$submission =& $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$notificationDao = DAORegistry::getDAO('NotificationDAO');

		$signOffNotificationType = $this->_getSignoffNotificationTypeByStageId($stageId);
		$editorAssignmentNotificationType = $this->_getEditorAssignmentNotificationTypeByStageId($stageId);

		$editorAssignments = $notificationDao->getByAssoc(ASSOC_TYPE_SUBMISSION, $submission->getId(), null, $editorAssignmentNotificationType, $contextId);
		if (isset($signOffNotificationType)) {
			$signoffAssignments = $notificationDao->getByAssoc(ASSOC_TYPE_SUBMISSION, $submission->getId(), $user->getId(), $signOffNotificationType, $contextId);
		}

		// if the User has assigned TASKs in this stage check, return true
		if (!$editorAssignments->wasEmpty() || (isset($signoffAssignments) && !$signoffAssignments->wasEmpty())) {
			return true;
		}

		// check for more specific notifications on those stages that have them.
		if ($stageId == WORKFLOW_STAGE_ID_PRODUCTION) {
			$submissionApprovalNotification = $notificationDao->getByAssoc(ASSOC_TYPE_SUBMISSION, $submission->getId(), null, NOTIFICATION_TYPE_APPROVE_SUBMISSION, $contextId);
			if (!$submissionApprovalNotification->wasEmpty()) {
				return true;
			}
		}

		if ($stageId == WORKFLOW_STAGE_ID_EXTERNAL_REVIEW) {
			$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
			$reviewRounds = $reviewRoundDao->getBySubmissionId($submission->getId(), $stageId);
			$notificationTypes = array(NOTIFICATION_TYPE_REVIEW_ROUND_STATUS, NOTIFICATION_TYPE_ALL_REVIEWS_IN);
			while ($reviewRound = $reviewRounds->next()) {
				foreach ($notificationTypes as $type) {
					$notifications = $notificationDao->getByAssoc(ASSOC_TYPE_REVIEW_ROUND, $reviewRound->getId(), null, $type, $contextId);
					if (!$notifications->wasEmpty()) {
						return true;
					}
				}
			}
		}

		return false;
	}

	//
	// Protected helper methods
	//
	/**
	 * Return the editor assignment notification type based on stage id.
	 * @param $stageId int
	 * @return int
	 */
	protected function _getEditorAssignmentNotificationTypeByStageId($stageId) {
		switch ($stageId) {
			case WORKFLOW_STAGE_ID_SUBMISSION:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION;
			case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW;
			case WORKFLOW_STAGE_ID_EDITING:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING;
			case WORKFLOW_STAGE_ID_PRODUCTION:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION;
		}
		return null;
	}
}

?>
