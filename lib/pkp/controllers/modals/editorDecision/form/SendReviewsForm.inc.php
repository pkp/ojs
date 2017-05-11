<?php

/**
 * @file controllers/modals/editorDecision/form/SendReviewsForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SendReviewsForm
 * @ingroup controllers_modals_editorDecision_form
 *
 * @brief Form to request additional work from the author (Request revisions or
 *  resubmit for review), or to decline the submission.
 */

import('lib.pkp.controllers.modals.editorDecision.form.EditorDecisionWithEmailForm');

// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');

class SendReviewsForm extends EditorDecisionWithEmailForm {
	/**
	 * Constructor.
	 * @param $submission Submission
	 * @param $decision int
	 * @param $stageId int
	 * @param $reviewRound ReviewRound
	 */
	function __construct($submission, $decision, $stageId, $reviewRound = null) {
		if (!in_array($decision, $this->_getDecisions())) {
			fatalError('Invalid decision!');
		}

		$this->setSaveFormOperation('saveSendReviews');

		parent::__construct(
			$submission, $decision, $stageId,
			'controllers/modals/editorDecision/form/sendReviewsForm.tpl', $reviewRound
		);
	}


	//
	// Implement protected template methods from Form
	//
	/**
	 * @copydoc Form::initData()
	 */
	function initData($args, $request) {
		$actionLabels = EditorDecisionActionsManager::getActionLabels($this->_getDecisions());

		return parent::initData($args, $request, $actionLabels);
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute($args, $request) {
		// Retrieve the submission.
		$submission = $this->getSubmission();

		// Get this form decision actions labels.
		$actionLabels = EditorDecisionActionsManager::getActionLabels($this->_getDecisions());

		// Record the decision.
		$reviewRound = $this->getReviewRound();
		$decision = $this->getDecision();
		$stageId = $this->getStageId();
		import('lib.pkp.classes.submission.action.EditorAction');
		$editorAction = new EditorAction();
		$editorAction->recordDecision($request, $submission, $decision, $actionLabels, $reviewRound, $stageId);

		// Identify email key and status of round.
		switch ($decision) {
			case SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS:
				$emailKey = 'EDITOR_DECISION_REVISIONS';
				$status = REVIEW_ROUND_STATUS_REVISIONS_REQUESTED;
				break;

			case SUBMISSION_EDITOR_DECISION_RESUBMIT:
				$emailKey = 'EDITOR_DECISION_RESUBMIT';
				$status = REVIEW_ROUND_STATUS_RESUBMITTED;
				break;

			case SUBMISSION_EDITOR_DECISION_DECLINE:
				$emailKey = 'SUBMISSION_UNSUITABLE';
				$status = REVIEW_ROUND_STATUS_DECLINED;
				break;

			default:
				fatalError('Unsupported decision!');
		}

		$this->_updateReviewRoundStatus($submission, $status, $reviewRound);

		// Send email to the author.
		$this->_sendReviewMailToAuthor($submission, $emailKey, $request);
	}

	//
	// Private functions
	//
	/**
	 * Get this form decisions.
	 * @return array
	 */
	function _getDecisions() {
		return array(
			SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS,
			SUBMISSION_EDITOR_DECISION_RESUBMIT,
			SUBMISSION_EDITOR_DECISION_DECLINE
		);
	}
}

?>
