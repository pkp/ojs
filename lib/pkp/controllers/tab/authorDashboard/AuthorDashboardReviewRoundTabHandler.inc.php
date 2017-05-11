<?php

/**
 * @file controllers/tab/authorDashboard/AuthorDashboardReviewRoundTabHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorDashboardReviewRoundTabHandler
 * @ingroup controllers_tab_authorDashboard
 *
 * @brief Handle AJAX operations for review round tabs on author dashboard page.
 */

// Import the base Handler.
import('pages.authorDashboard.AuthorDashboardHandler');
import('lib.pkp.classes.core.JSONMessage');

class AuthorDashboardReviewRoundTabHandler extends AuthorDashboardHandler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(ROLE_ID_AUTHOR), array('fetchReviewRoundInfo'));
	}


	//
	// Extended methods from Handler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$stageId = (int)$request->getUserVar('stageId');

		// Authorize stage id.
		import('lib.pkp.classes.security.authorization.internal.WorkflowStageRequiredPolicy');
		$this->addPolicy(new WorkflowStageRequiredPolicy($stageId));

		// We need a review round id in request.
		import('lib.pkp.classes.security.authorization.internal.ReviewRoundRequiredPolicy');
		$this->addPolicy(new ReviewRoundRequiredPolicy($request, $args));

		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public handler operations
	//
	/**
	 * Fetch information for the author on the specified review round
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function fetchReviewRoundInfo($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);

		$reviewRound = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ROUND);
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		if ($stageId !== WORKFLOW_STAGE_ID_INTERNAL_REVIEW && $stageId !== WORKFLOW_STAGE_ID_EXTERNAL_REVIEW) {
			fatalError('Invalid Stage Id');
		}

		$templateMgr->assign(array(
			'stageId' => $stageId,
			'reviewRoundId' => $reviewRound->getId(),
			'submission' => $submission,
			'reviewRoundNotificationRequestOptions' => array(
				NOTIFICATION_LEVEL_NORMAL => array(
					NOTIFICATION_TYPE_REVIEW_ROUND_STATUS => array(ASSOC_TYPE_REVIEW_ROUND, $reviewRound->getId())),
				NOTIFICATION_LEVEL_TRIVIAL => array()
			),
		));


		// Editor has taken an action and sent an email; Display the email
		import('classes.workflow.EditorDecisionActionsManager');
		if(EditorDecisionActionsManager::getEditorTakenActionInReviewRound($reviewRound)) {
			$submissionEmailLogDao = DAORegistry::getDAO('SubmissionEmailLogDAO');
			$user = $request->getUser();
			$templateMgr->assign(array(
				'submissionEmails' => $submissionEmailLogDao->getByEventType($submission->getId(), SUBMISSION_EMAIL_EDITOR_NOTIFY_AUTHOR, $user->getId()),
				'showReviewAttachments' => true,
			));
		}

		return $templateMgr->fetchJson('authorDashboard/reviewRoundInfo.tpl');
	}
}

?>
