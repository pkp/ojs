<?php

/**
 * @file controllers/tab/workflow/PKPWorkflowTabHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPWorkflowTabHandler
 * @ingroup controllers_tab_workflow
 *
 * @brief Handle AJAX operations for workflow tabs.
 */

// Import the base Handler.
import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');

// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');

abstract class PKPWorkflowTabHandler extends Handler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT), array('fetchTab'));
	}


	//
	// Extended methods from Handler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		// Authorize stage id.
		import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
		$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $this->_identifyStageId($request)));

		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public handler operations
	//
	/**
	 * Fetch the specified workflow tab.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function fetchTab($args, $request) {

		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);

		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$templateMgr->assign('stageId', $stageId);

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$templateMgr->assign('submission', $submission);

		switch ($stageId) {
			case WORKFLOW_STAGE_ID_SUBMISSION:
				return $templateMgr->fetchJson('controllers/tab/workflow/submission.tpl');
			case WORKFLOW_STAGE_ID_INTERNAL_REVIEW:
			case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
				// Retrieve the authorized submission and stage id.
				$selectedStageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);

				// Get all review rounds for this submission, on the current stage.
				$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
				$reviewRoundsFactory = $reviewRoundDao->getBySubmissionId($submission->getId(), $selectedStageId);
				$reviewRoundsArray = $reviewRoundsFactory->toAssociativeArray();
				$lastReviewRound = $reviewRoundDao->getLastReviewRoundBySubmissionId($submission->getId(), $selectedStageId);

				// Get the review round number of the last review round to be used
				// as the current review round tab index, if we have review rounds.
				if ($lastReviewRound) {
					$lastReviewRoundNumber = $lastReviewRound->getRound();
					$lastReviewRoundId = $lastReviewRound->getId();
					$templateMgr->assign('lastReviewRoundNumber', $lastReviewRoundNumber);
				}

				// Add the round information to the template.
				$templateMgr->assign('reviewRounds', $reviewRoundsArray);
				$templateMgr->assign('reviewRoundOp', $this->_identifyReviewRoundOp($stageId));

				if ($submission->getStageId() == $selectedStageId && count($reviewRoundsArray) > 0) {
					$dispatcher = $request->getDispatcher();

					import('lib.pkp.classes.linkAction.request.AjaxModal');
					$newRoundAction = new LinkAction(
						'newRound',
						new AjaxModal(
							$dispatcher->url(
								$request, ROUTE_COMPONENT, null,
								'modals.editorDecision.EditorDecisionHandler',
								'newReviewRound', null, array(
									'submissionId' => $submission->getId(),
									'decision' => SUBMISSION_EDITOR_DECISION_RESUBMIT,
									'stageId' => $selectedStageId,
									'reviewRoundId' => $lastReviewRoundId
								)
							),
							__('editor.submission.newRound'),
							'modal_add_item'
						),
						__('editor.submission.newRound'),
						'add_item_small'
					);
					$templateMgr->assign('newRoundAction', $newRoundAction);
				}

				// Render the view.
				return $templateMgr->fetchJson('controllers/tab/workflow/review.tpl');
			case WORKFLOW_STAGE_ID_EDITING:
				// Assign banner notifications to the template.
				$notificationRequestOptions = array(
					NOTIFICATION_LEVEL_NORMAL => array(
						NOTIFICATION_TYPE_ASSIGN_COPYEDITOR => array(ASSOC_TYPE_SUBMISSION, $submission->getId()),
						NOTIFICATION_TYPE_AWAITING_COPYEDITS => array(ASSOC_TYPE_SUBMISSION, $submission->getId())),
					NOTIFICATION_LEVEL_TRIVIAL => array()
				);
				$templateMgr->assign('editingNotificationRequestOptions', $notificationRequestOptions);
				return $templateMgr->fetchJson('controllers/tab/workflow/editorial.tpl');
			case WORKFLOW_STAGE_ID_PRODUCTION:
				$templateMgr = TemplateManager::getManager($request);
				$notificationRequestOptions = $this->getProductionNotificationOptions($submission->getId());
				$representationDao = Application::getRepresentationDAO();
				$representations = $representationDao->getBySubmissionId($submission->getId());
				$templateMgr->assign('representations', $representations->toAssociativeArray());

				$templateMgr->assign('productionNotificationRequestOptions', $notificationRequestOptions);
				return $templateMgr->fetchJson('controllers/tab/workflow/production.tpl');
		}
	}

	/**
	 * Setup variables for the template
	 * @param $request Request
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_GRID, LOCALE_COMPONENT_PKP_EDITOR);

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);

		$templateMgr = TemplateManager::getManager($request);

		// Assign the authorized submission.
		$templateMgr->assign('submission', $submission);

		// Assign workflow stages related data.
		$templateMgr->assign('stageId', $stageId);
		$templateMgr->assign('submissionStageId', $submission->getStageId());

		// Get the right notifications type based on current stage id.
		$notificationMgr = new NotificationManager();
		$editorAssignmentNotificationType = $this->getEditorAssignmentNotificationTypeByStageId($stageId);

		// Define the workflow notification options.
		$notificationRequestOptions = array(
			NOTIFICATION_LEVEL_TASK => array(
				$editorAssignmentNotificationType => array(ASSOC_TYPE_SUBMISSION, $submission->getId())
			),
			NOTIFICATION_LEVEL_TRIVIAL => array()
		);

		$templateMgr->assign('workflowNotificationRequestOptions', $notificationRequestOptions);
	}

	/**
	 * Return the editor assignment notification type based on stage id.
	 * @param $stageId int
	 * @return int
	 */
	protected function getEditorAssignmentNotificationTypeByStageId($stageId) {
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

	/**
	 * Get all production notification options to be used in the production stage tab.
	 * @param $submissionId int
	 * @return array
	 */
	abstract protected function getProductionNotificationOptions($submissionId);

	/**
	 * Translate the requested operation to a stage id.
	 * @param $request Request
	 * @return integer One of the WORKFLOW_STAGE_* constants.
	 */
	private function _identifyStageId($request) {
		if ($stageId = $request->getUserVar('stageId')) {
			return (int) $stageId;
		}
	}

	/**
	 * Identifies the review round.
	 * @param int $stageId
	 * @return string
	 */
	private function _identifyReviewRoundOp($stageId) {
		switch ($stageId) {
			case WORKFLOW_STAGE_ID_INTERNAL_REVIEW:
				return 'internalReviewRound';
			case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
				return 'externalReviewRound';
			default:
				fatalError('unknown review round id.');
		}
	}
}

?>
