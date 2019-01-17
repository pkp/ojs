<?php

/**
 * @file controllers/modals/editorDecision/EditorDecisionHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditorDecisionHandler
 * @ingroup controllers_modals_editorDecision
 *
 * @brief Handle requests for editors to make a decision
 */

import('lib.pkp.classes.controllers.modals.editorDecision.PKPEditorDecisionHandler');

// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');

class EditorDecisionHandler extends PKPEditorDecisionHandler {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();

		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
			array_merge(array(
				'externalReview', 'saveExternalReview',
				'sendReviews', 'saveSendReviews',
				'promote', 'savePromote',
			), $this->_getReviewRoundOps())
		);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$stageId = (int) $request->getUserVar('stageId');
		import('lib.pkp.classes.security.authorization.EditorDecisionAccessPolicy');
		$this->addPolicy(new EditorDecisionAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public handler actions
	//
	/**
	 * Start a new review round
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function saveNewReviewRound($args, $request) {
		// FIXME: this can probably all be managed somewhere.
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		if ($stageId == WORKFLOW_STAGE_ID_EXTERNAL_REVIEW) {
			$redirectOp = WORKFLOW_STAGE_PATH_EXTERNAL_REVIEW;
		} else {
			$redirectOp = null; // Suppress scrutinizer warn
			assert(false);
		}

		return $this->_saveEditorDecision($args, $request, 'NewReviewRoundForm', $redirectOp, SUBMISSION_EDITOR_DECISION_RESUBMIT);
	}


	//
	// Private helper methods
	//
	protected function _saveGeneralPromote($args, $request) {
		// Redirect to the next workflow page after
		// promoting the submission.
		$decision = (int)$request->getUserVar('decision');

		$redirectOp = null;

		if ($decision == SUBMISSION_EDITOR_DECISION_ACCEPT) {
			$redirectOp = WORKFLOW_STAGE_PATH_EDITING;
		} elseif ($decision == SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW) {
			$redirectOp = WORKFLOW_STAGE_PATH_EXTERNAL_REVIEW;
		} elseif ($decision == SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION) {
			$redirectOp = WORKFLOW_STAGE_PATH_PRODUCTION;
		}

		// Make sure user has access to the workflow stage.
		import('lib.pkp.classes.workflow.WorkflowStageDAO');
		$redirectWorkflowStage = WorkflowStageDAO::getIdFromPath($redirectOp);
		$userAccessibleWorkflowStages = $this->getAuthorizedContextObject(ASSOC_TYPE_ACCESSIBLE_WORKFLOW_STAGES);
		if (!array_key_exists($redirectWorkflowStage, $userAccessibleWorkflowStages)) {
			$redirectOp = null;
		}

		return $this->_saveEditorDecision($args, $request, 'PromoteForm', $redirectOp);
	}

	/**
	 * Get editor decision notification type and level by decision.
	 * @param $decision int
	 * @return array
	 */
	protected function _getNotificationTypeByEditorDecision($decision) {
		switch ($decision) {
			case SUBMISSION_EDITOR_DECISION_ACCEPT:
				return NOTIFICATION_TYPE_EDITOR_DECISION_ACCEPT;
			case SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW:
				return NOTIFICATION_TYPE_EDITOR_DECISION_EXTERNAL_REVIEW;
			case SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS:
				return NOTIFICATION_TYPE_EDITOR_DECISION_PENDING_REVISIONS;
			case SUBMISSION_EDITOR_DECISION_RESUBMIT:
				return NOTIFICATION_TYPE_EDITOR_DECISION_RESUBMIT;
			case SUBMISSION_EDITOR_DECISION_DECLINE:
			case SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE:
				return NOTIFICATION_TYPE_EDITOR_DECISION_DECLINE;
			case SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION:
				return NOTIFICATION_TYPE_EDITOR_DECISION_SEND_TO_PRODUCTION;
			default:
				assert(false);
				return null;
		}
	}

	/**
	 * Get review-related stage IDs.
	 * @return array
	 */
	protected function _getReviewStages() {
		return array(WORKFLOW_STAGE_ID_INTERNAL_REVIEW, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW);
	}

	/**
	 * Get review-related decision notifications.
	 */
	protected function _getReviewNotificationTypes() {
		return array(NOTIFICATION_TYPE_PENDING_EXTERNAL_REVISIONS);
	}

	/**
	 * Get the fully-qualified import name for the given form name.
	 * @param $formName Class name for the desired form.
	 * @return string
	 */
	protected function _resolveEditorDecisionForm($formName) {
		switch($formName) {
			case 'InitiateExternalReviewForm':
				return "controllers.modals.editorDecision.form.$formName";
			default:
				return parent::_resolveEditorDecisionForm($formName);
		}
	}
}


