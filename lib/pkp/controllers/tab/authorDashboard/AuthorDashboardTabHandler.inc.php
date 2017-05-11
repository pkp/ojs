<?php

/**
 * @file controllers/tab/authorDashboard/AuthorDashboardTabHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorDashboardTabHandler
 * @ingroup controllers_tab_authorDashboard
 *
 * @brief Handle AJAX operations for authorDashboard tabs.
 */

// Import the base Handler.
import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');

class AuthorDashboardTabHandler extends Handler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(ROLE_ID_AUTHOR), array('fetchTab'));
	}


	//
	// Extended methods from Handler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.AuthorDashboardAccessPolicy');
		$this->addPolicy(new AuthorDashboardAccessPolicy($request, $args, $roleAssignments), true);

		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public handler operations
	//
	/**
	 * Fetch the specified authorDashboard tab.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function fetchTab($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);

		$stageId = $request->getUserVar('stageId');
		$templateMgr->assign('stageId', $stageId);

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$templateMgr->assign('submission', $submission);

		// Import submission file to define file stages.
		import('lib.pkp.classes.submission.SubmissionFile');

		// Workflow-stage specific "upload file" action.
		$currentStage = $submission->getStageId();
		$fileStage = $this->_fileStageFromWorkflowStage($currentStage);

		$templateMgr->assign('lastReviewRoundNumber', $this->_getLastReviewRoundNumber($submission, $currentStage));

		if (in_array($stageId, array(WORKFLOW_STAGE_ID_INTERNAL_REVIEW, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW))) {
			$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
			$templateMgr->assign('reviewRounds', $reviewRoundDao->getBySubmissionId($submission->getId(), $stageId));

			// Get the last review round.
			$lastReviewRound = $reviewRoundDao->getLastReviewRoundBySubmissionId($submission->getId(), $currentStage);

			// Create and assign add file link action.
			if ($fileStage && is_a($lastReviewRound, 'ReviewRound')) {
				import('lib.pkp.controllers.api.file.linkAction.AddFileLinkAction');
				$templateMgr->assign('uploadFileAction', new AddFileLinkAction(
					$request, $submission->getId(), $currentStage,
					array(ROLE_ID_AUTHOR), null, $fileStage, null, null, $lastReviewRound->getId()));
			}
		}

		// If the submission is in or past the editorial stage,
		// assign the editor's copyediting emails to the template
		$submissionEmailLogDao = DAORegistry::getDAO('SubmissionEmailLogDAO');
		$user = $request->getUser();

		// Define the notification options.
		$templateMgr->assign(
			'authorDashboardNotificationRequestOptions',
			$this->_getNotificationRequestOptions($submission)
		);

		switch ($stageId) {
			case WORKFLOW_STAGE_ID_SUBMISSION:
				return $templateMgr->fetchJson('controllers/tab/authorDashboard/submission.tpl');
			case WORKFLOW_STAGE_ID_INTERNAL_REVIEW:
				return $templateMgr->fetchJson('controllers/tab/authorDashboard/internalReview.tpl');
			case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
				return $templateMgr->fetchJson('controllers/tab/authorDashboard/externalReview.tpl');
			case WORKFLOW_STAGE_ID_EDITING:
				$templateMgr->assign('copyeditingEmails', $submissionEmailLogDao->getByEventType($submission->getId(), SUBMISSION_EMAIL_COPYEDIT_NOTIFY_AUTHOR, $user->getId()));
				return $templateMgr->fetchJson('controllers/tab/authorDashboard/editorial.tpl');
			case WORKFLOW_STAGE_ID_PRODUCTION:
				$representationDao = Application::getRepresentationDAO();
				$templateMgr->assign(array(
					'productionEmails' => $submissionEmailLogDao->getByEventType($submission->getId(), SUBMISSION_EMAIL_PROOFREAD_NOTIFY_AUTHOR, $user->getId()),
					'representations' => $representationDao->getBySubmissionId($submission->getId())->toArray(),
				));
				return $templateMgr->fetchJson('controllers/tab/authorDashboard/production.tpl');
		}
	}

	/**
	 * Get the last review round numbers in an array by stage name.
	 * @param $submission Submission
	 * @param $stageId int WORKFLOW_STAGE_ID_...
	 * @return int Round number, 0 if none.
	 */
	protected function _getLastReviewRoundNumber($submission, $stageId) {
		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
		$lastExternalReviewRound = $reviewRoundDao->getLastReviewRoundBySubmissionId($submission->getId(), $stageId);
		if ($lastExternalReviewRound) {
			return $lastExternalReviewRound->getRound();
		}
		return 0;
	}

	/**
	 * Get the notification request options.
	 * @param $submission Submission
	 * @return array
	 */
	protected function _getNotificationRequestOptions($submission) {
		$submissionAssocTypeAndIdArray = array(ASSOC_TYPE_SUBMISSION, $submission->getId());
		return array(
			NOTIFICATION_LEVEL_TASK => array(
				NOTIFICATION_TYPE_PENDING_EXTERNAL_REVISIONS => $submissionAssocTypeAndIdArray),
			NOTIFICATION_LEVEL_NORMAL => array(
				NOTIFICATION_TYPE_EDITOR_DECISION_ACCEPT => $submissionAssocTypeAndIdArray,
				NOTIFICATION_TYPE_EDITOR_DECISION_EXTERNAL_REVIEW => $submissionAssocTypeAndIdArray,
				NOTIFICATION_TYPE_EDITOR_DECISION_PENDING_REVISIONS => $submissionAssocTypeAndIdArray,
				NOTIFICATION_TYPE_EDITOR_DECISION_RESUBMIT => $submissionAssocTypeAndIdArray,
				NOTIFICATION_TYPE_EDITOR_DECISION_DECLINE => $submissionAssocTypeAndIdArray,
				NOTIFICATION_TYPE_EDITOR_DECISION_SEND_TO_PRODUCTION => $submissionAssocTypeAndIdArray),
			NOTIFICATION_LEVEL_TRIVIAL => array()
		);
	}

	/**
	 * Get the SUBMISSION_FILE_... file stage based on the current
	 * WORKFLOW_STAGE_... workflow stage.
	 * @param $currentStage int WORKFLOW_STAGE_...
	 * @return int SUBMISSION_FILE_...
	 */
	protected function _fileStageFromWorkflowStage($currentStage) {
		switch ($currentStage) {
			case WORKFLOW_STAGE_ID_SUBMISSION:
				return SUBMISSION_FILE_SUBMISSION;
			case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
				return SUBMISSION_FILE_REVIEW_REVISION;
			case WORKFLOW_STAGE_ID_EDITING:
				return SUBMISSION_FILE_FINAL;
			default:
				return null;
		}
	}
}

?>
