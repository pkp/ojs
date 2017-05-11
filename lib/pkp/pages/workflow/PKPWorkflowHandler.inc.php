<?php

/**
 * @file pages/workflow/PKPWorkflowHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for the submssion workflow.
 */

import('classes.handler.Handler');
import('lib.pkp.classes.workflow.WorkflowStageDAO');


// import UI base classes
import('lib.pkp.classes.linkAction.LinkAction');
import('lib.pkp.classes.linkAction.request.AjaxModal');

abstract class PKPWorkflowHandler extends Handler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$router = $request->getRouter();
		$operation = $router->getRequestedOp($request);

		if ($operation == 'access') {
			// Authorize requested submission.
			import('lib.pkp.classes.security.authorization.internal.SubmissionRequiredPolicy');
			$this->addPolicy(new SubmissionRequiredPolicy($request, $args, 'submissionId'));

			// This policy will deny access if user has no accessible workflow stage.
			// Otherwise it will build an authorized object with all accessible
			// workflow stages and authorize user operation access.
			import('lib.pkp.classes.security.authorization.internal.UserAccessibleWorkflowStageRequiredPolicy');
			$this->addPolicy(new UserAccessibleWorkflowStageRequiredPolicy($request));
		} else {
			import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
			$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $this->identifyStageId($request, $args)));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request, $args) {
		$router = $request->getRouter();
		$operation = $router->getRequestedOp($request);

		if ($operation != 'access') {
			$this->setupTemplate($request);
		}

		// Call parent method.
		parent::initialize($request, $args);
	}


	//
	// Public handler methods
	//
	/**
	 * Redirect users to their most appropriate
	 * submission workflow stage.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function access($args, $request) {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');

		$currentStageId = $submission->getStageId();
		$accessibleWorkflowStages = $this->getAuthorizedContextObject(ASSOC_TYPE_ACCESSIBLE_WORKFLOW_STAGES);

		// Get the closest workflow stage that user has an assignment.
		$stagePath = null;
		$workingStageId = null;

		for ($workingStageId = $currentStageId; $workingStageId >= WORKFLOW_STAGE_ID_SUBMISSION; $workingStageId--) {
			if (array_key_exists($workingStageId, $accessibleWorkflowStages)) {
				break;
			}
		}

		// If no stage was found, user still have access to future stages of the
		// submission. Try to get the closest future workflow stage.
		if ($workingStageId == null) {
			for ($workingStageId = $currentStageId; $workingStageId <= WORKFLOW_STAGE_ID_PRODUCTION; $workingStageId++) {
				if (array_key_exists($workingStageId, $accessibleWorkflowStages)) {
					break;
				}
			}
		}

		assert(isset($workingStageId));

		$router = $request->getRouter();
		$request->redirectUrl($router->url($request, null, 'workflow', 'index', array($submission->getId(), $workingStageId)));
	}

	/**
	 * Show the workflow stage, with the stage path as an #anchor.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$this->_displayWorkflow($args, $request);
	}

	/**
	 * Show the submission stage.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submission($args, $request) {
		$this->_redirectToIndex($args, $request);
	}

	/**
	 * Show the external review stage.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function externalReview($args, $request) {
		$this->_redirectToIndex($args, $request);
	}

	/**
	 * Show the editorial stage
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function editorial(&$args, $request) {
		$this->_redirectToIndex($args, $request);
	}

	/**
	 * Show the production stage
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function production(&$args, $request) {
		$this->_redirectToIndex($args, $request);
	}

	/**
	 * Redirect all old stage paths to index
	 * @param $args array
	 * @param $request PKPRequest
	 */
	protected function _redirectToIndex(&$args, $request) {
		// Translate the operation to a workflow stage identifier.
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$router = $request->getRouter();
		$workflowPath = $router->getRequestedOp($request);
		$stageId = WorkflowStageDAO::getIdFromPath($workflowPath);
		$request->redirectUrl($router->url($request, null, 'workflow', 'index', array($submission->getId(), $stageId)));
	}

	/**
	 * Fetch JSON-encoded editor decision options.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function editorDecisionActions($args, $request) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
		$reviewRoundId = (int) $request->getUserVar('reviewRoundId');

		// Prepare the action arguments.
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);

		$actionArgs = array(
			'submissionId' => $submission->getId(),
			'stageId' => (int) $stageId,
		);

		// If a review round was specified, include it in the args;
		// must also check that this is the last round or decisions
		// cannot be recorded.
		if ($reviewRoundId) {
			$actionArgs['reviewRoundId'] = $reviewRoundId;
			$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
			$lastReviewRound = $reviewRoundDao->getLastReviewRoundBySubmissionId($submission->getId(), $stageId);
		}

		// If a review round was specified,

		// If there is an editor assigned, retrieve stage decisions.
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		if ($stageAssignmentDao->editorAssignedToStage($submission->getId(), $stageId) && (!$reviewRoundId || $reviewRoundId == $lastReviewRound->getId())) {
			import('classes.workflow.EditorDecisionActionsManager');
			$decisions = EditorDecisionActionsManager::getStageDecisions($stageId);
		} else {
			$decisions = array(); // None available
		}

		// Iterate through the editor decisions and create a link action for each decision.
		$editorActions = array();

		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		foreach($decisions as $decision => $action) {
			$actionArgs['decision'] = $decision;
			$editorActions[] = new LinkAction(
				$action['name'],
				new AjaxModal(
					$dispatcher->url(
						$request, ROUTE_COMPONENT, null,
						'modals.editorDecision.EditorDecisionHandler',
						$action['operation'], null, $actionArgs
					),
					__($action['title']),
					$action['titleIcon']
				),
				__($action['title'])
			);
		}

		// Assign the actions to the template.
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('editorActions', $editorActions);
		$templateMgr->assign('stageId', $stageId);
		return $templateMgr->fetchJson('workflow/editorialLinkActions.tpl');
	}

	/**
	 * Fetch the JSON-encoded submission header.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function submissionHeader($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		return $templateMgr->fetchJson('workflow/submissionHeader.tpl');
	}

	/**
	 * Fetch the JSON-encoded submission progress bar.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function submissionProgressBar($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();

		$workflowStages = WorkflowStageDAO::getWorkflowStageKeysAndPaths();
		$stageNotifications = array();
		foreach (array_keys($workflowStages) as $stageId) {
			$stageNotifications[$stageId] = $this->notificationOptionsByStage($request->getUser(), $stageId, $context->getId());
		}

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

		$editDecisionDao = DAORegistry::getDAO('EditDecisionDAO'); /* @var $editDecisionDao EditDecisionDAO */
		$stageDecisions = $editDecisionDao->getEditorDecisions($submission->getId());

		$stagesWithDecisions = array();
		foreach ($stageDecisions as $decision) {
			$stagesWithDecisions[$decision['stageId']] = $decision['stageId'];
		}

		$workflowStages = WorkflowStageDAO::getStageStatusesBySubmission($submission, $stagesWithDecisions, $stageNotifications);
		$templateMgr->assign('workflowStages', $workflowStages);
		if ($this->isSubmissionReady($submission)) {
			$templateMgr->assign('submissionIsReady', true);
		}

		return $templateMgr->fetchJson('workflow/submissionProgressBar.tpl');
	}

	/**
	 * Setup variables for the template
	 * @param $request Request
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_GRID, LOCALE_COMPONENT_PKP_EDITOR);

		$router = $request->getRouter();

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);

		// Construct array with workflow stages data.
		$workflowStages = WorkflowStageDAO::getWorkflowStageKeysAndPaths();

		$templateMgr = TemplateManager::getManager($request);

		// Assign the authorized submission.
		$templateMgr->assign('submission', $submission);

		// Assign workflow stages related data.
		$templateMgr->assign('stageId', $stageId);
		$templateMgr->assign('submissionStageId', $submission->getStageId());
		$templateMgr->assign('workflowStages', $workflowStages);

		import('controllers.modals.submissionMetadata.linkAction.SubmissionEntryLinkAction');
		$templateMgr->assign(
			'submissionEntryAction',
			new SubmissionEntryLinkAction($request, $submission->getId(), $stageId)
		);

		import('lib.pkp.controllers.informationCenter.linkAction.SubmissionInfoCenterLinkAction');
		$templateMgr->assign(
			'submissionInformationCenterAction',
			new SubmissionInfoCenterLinkAction($request, $submission->getId())
		);

		import('lib.pkp.controllers.modals.documentLibrary.linkAction.SubmissionLibraryLinkAction');
		$templateMgr->assign(
			'submissionLibraryAction',
			new SubmissionLibraryLinkAction($request, $submission->getId())
		);
	}

	//
	// Protected helper methods
	//

	/**
	 * Displays the workflow tab structure.
	 * @param $args array
	 * @param $request Request
	 */
	private function _displayWorkflow($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('workflow/workflow.tpl');
	}

	/**
	 * Translate the requested operation to a stage id.
	 * @param $request Request
	 * @param $args array
	 * @return integer One of the WORKFLOW_STAGE_* constants.
	 */
	protected function identifyStageId($request, $args) {
		if ($stageId = $request->getUserVar('stageId')) {
			return (int) $stageId;
		}

		// Maintain the old check for previous path urls
		$router = $request->getRouter();
		$workflowPath = $router->getRequestedOp($request);
		$stageId = WorkflowStageDAO::getIdFromPath($workflowPath);
		if ($stageId) {
			return $stageId;
		}

		// Finally, retrieve the requested operation, if the stage id is
		// passed in via an argument in the URL, like index/submissionId/stageId
		$stageId = $args[1];

		// Translate the operation to a workflow stage identifier.
		assert(WorkflowStageDAO::getPathFromId($stageId) !== null);
		return $stageId;
	}

	/**
	 * Determine if a particular stage has a notification pending.  If so, return true.
	 * This is used to set the CSS class of the submission progress bar.
	 * @param $user PKPUser
	 * @param $stageId integer
	 * @param $contextId integer
	 * @return boolean
	 */
	protected function notificationOptionsByStage($user, $stageId, $contextId) {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$notificationDao = DAORegistry::getDAO('NotificationDAO');

		$editorAssignmentNotificationType = $this->getEditorAssignmentNotificationTypeByStageId($stageId);

		$editorAssignments = $notificationDao->getByAssoc(ASSOC_TYPE_SUBMISSION, $submission->getId(), null, $editorAssignmentNotificationType, $contextId);

		// if the User has assigned TASKs in this stage check, return true
		if (!$editorAssignments->wasEmpty()) {
			return true;
		}

		// check for more specific notifications on those stages that have them.
		if ($stageId == WORKFLOW_STAGE_ID_PRODUCTION) {
			$submissionApprovalNotification = $notificationDao->getByAssoc(ASSOC_TYPE_SUBMISSION, $submission->getId(), null, NOTIFICATION_TYPE_APPROVE_SUBMISSION, $contextId);
			if (!$submissionApprovalNotification->wasEmpty()) {
				return true;
			}
		}

		if ($stageId == WORKFLOW_STAGE_ID_INTERNAL_REVIEW || $stageId == WORKFLOW_STAGE_ID_EXTERNAL_REVIEW) {
			$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
			$reviewRounds = $reviewRoundDao->getBySubmissionId($submission->getId(), $stageId);
			$notificationTypes = array(NOTIFICATION_TYPE_ALL_REVIEWS_IN);
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
	// Abstract protected methods.
	//
	/**
	* Return the editor assignment notification type based on stage id.
	* @param $stageId int
	* @return int
	*/
	abstract protected function getEditorAssignmentNotificationTypeByStageId($stageId);

	/**
	 * Checks whether or not the submission is ready to appear in catalog.
	 * @param $submission Submission
	 * @return boolean
	 */
	abstract protected function isSubmissionReady($submission);
}

?>
