<?php

/**
 * @file controllers/modals/editorDecision/form/EditorDecisionForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditorDecisionForm
 * @ingroup controllers_modals_editorDecision_form
 *
 * @brief Base class for the editor decision forms.
 */

import('lib.pkp.classes.form.Form');

// Define review round and review stage id constants.
import('lib.pkp.classes.submission.reviewRound.ReviewRound');

class EditorDecisionForm extends Form {
	/** @var Submission The submission associated with the editor decision */
	var $_submission;

	/** @var int The stage ID where the decision is being made */
	var $_stageId;

	/** @var ReviewRound Only required when in review stages */
	var $_reviewRound;

	/** @var integer The decision being taken */
	var $_decision;


	/**
	 * Constructor.
	 * @param $submission Submission
	 * @param $stageId int
	 * @param $template string The template to display
	 * @param $reviewRound ReviewRound
	 */
	function __construct($submission, $decision, $stageId, $template, $reviewRound = null) {
		parent::__construct($template);
		$this->_submission = $submission;
		$this->_stageId = $stageId;
		$this->_reviewRound = $reviewRound;
		$this->_decision = $decision;

		// Validation checks for this form
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the decision
	 * @return integer
	 */
	function getDecision() {
		return $this->_decision;
	}

	/**
	 * Get the submission
	 * @return Submission
	 */
	function getSubmission() {
		return $this->_submission;
	}

	/**
	 * Get the stage Id
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get the review round object.
	 * @return ReviewRound
	 */
	function getReviewRound() {
		return $this->_reviewRound;
	}

	//
	// Overridden template methods from Form
	//
	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('selectedFiles'));
		parent::initData();
	}


	/**
	 * @see Form::fetch()
	 */
	function fetch($request) {
		$submission = $this->getSubmission();

		$reviewRound = $this->getReviewRound();
		if (is_a($reviewRound, 'ReviewRound')) {
			$this->setData('reviewRoundId', $reviewRound->getId());
		}

		$this->setData('stageId', $this->getStageId());

		$templateMgr = TemplateManager::getManager($request);
		$stageDecisions = EditorDecisionActionsManager::getStageDecisions($this->getStageId());
		$templateMgr->assign(array(
			'decisionData' => $stageDecisions[$this->getDecision()],
			'submissionId' => $submission->getId(),
			'submission' => $submission,
		));

		return parent::fetch($request);
	}


	//
	// Private helper methods
	//
	/**
	 * Initiate a new review round and add selected files
	 * to it. Also saves the new round to the submission.
	 * @param $submission Submission
	 * @param $stageId integer One of the WORKFLOW_STAGE_ID_* constants.
	 * @param $request Request
	 * @param $status integer One of the REVIEW_ROUND_STATUS_* constants.
	 * @return $newRound integer The round number of the new review round.
	 */
	function _initiateReviewRound($submission, $stageId, $request, $status = null) {

		// If we already have review round for this stage,
		// we create a new round after the last one.
		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO'); /* @var $reviewRoundDao ReviewRoundDAO */
		$lastReviewRound = $reviewRoundDao->getLastReviewRoundBySubmissionId($submission->getId(), $stageId);
		if ($lastReviewRound) {
			$newRound = $lastReviewRound->getRound() + 1;
		} else {
			// If we don't have any review round, we create the first one.
			$newRound = 1;
		}

		// Create a new review round.
		$reviewRound = $reviewRoundDao->build($submission->getId(), $stageId, $newRound, $status);

		// Check for a notification already in place for the current review round.
		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$notificationFactory = $notificationDao->getByAssoc(
			ASSOC_TYPE_REVIEW_ROUND,
			$reviewRound->getId(),
			null,
			NOTIFICATION_TYPE_REVIEW_ROUND_STATUS,
			$submission->getContextId()
		);

		// Create round status notification if there is no notification already.
		if ($notificationFactory->wasEmpty()) {
			$notificationMgr = new NotificationManager();
			$notificationMgr->createNotification(
				$request,
				null,
				NOTIFICATION_TYPE_REVIEW_ROUND_STATUS,
				$submission->getContextId(),
				ASSOC_TYPE_REVIEW_ROUND,
				$reviewRound->getId(),
				NOTIFICATION_LEVEL_NORMAL
			);
		}

		// Add the selected files to the new round.
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */

		// Bring in the SUBMISSION_FILE_* constants.
		import('lib.pkp.classes.submission.SubmissionFile');
		// Bring in the Manager (we need it).
		import('lib.pkp.classes.file.SubmissionFileManager');
		$submissionFileManager = new SubmissionFileManager($submission->getContextId(), $submission->getId());
		foreach (array('selectedFiles', 'selectedAttachments') as $userVar) {
			$selectedFiles = $this->getData($userVar);
			if(is_array($selectedFiles)) {
				foreach ($selectedFiles as $fileId) {
					// Retrieve the file last revision number.
					$revisionNumber = $submissionFileDao->getLatestRevisionNumber($fileId);
					list($newFileId, $newRevision) = $submissionFileManager->copyFileToFileStage($fileId, $revisionNumber, SUBMISSION_FILE_REVIEW_FILE, null, true);
					$submissionFileDao->assignRevisionToReviewRound($newFileId, $newRevision, $reviewRound);
				}
			}
		}

		return $newRound;
	}
}

?>
