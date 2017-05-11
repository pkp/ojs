<?php

/**
 * @file controllers/grid/users/reviewer/form/EditReviewForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditReviewForm
 * @ingroup controllers_grid_users_reviewer_form
 *
 * @brief Allow the editor to limit the available files to an assigned
 * reviewer after the assignment has taken place.
 */

import('lib.pkp.classes.form.Form');

class EditReviewForm extends Form {
	/** @var ReviewAssignment */
	var $_reviewAssignment;

	/** @var ReviewRound */
	var $_reviewRound;

	/**
	 * Constructor.
	 * @param $reviewAssignment ReviewAssignment
	 */
	function __construct($reviewAssignment) {
		$this->_reviewAssignment = $reviewAssignment;
		assert(is_a($this->_reviewAssignment, 'ReviewAssignment'));

		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
		$this->_reviewRound = $reviewRoundDao->getById($reviewAssignment->getReviewRoundId());
		assert(is_a($this->_reviewRound, 'ReviewRound'));

		parent::__construct('controllers/grid/users/reviewer/form/editReviewForm.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'responseDueDate', 'required', 'editor.review.errorAddingReviewer'));
		$this->addCheck(new FormValidator($this, 'reviewDueDate', 'required', 'editor.review.errorAddingReviewer'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	//
	// Overridden template methods
	//
	/**
	 * Initialize form data from the associated author.
	 */
	function initData() {
		$this->setData('responseDueDate', $this->_reviewAssignment->getDateResponseDue());
		$this->setData('reviewDueDate', $this->_reviewAssignment->getDateDue());
		return parent::initData();
	}

	/**
	 * Fetch the Edit Review Form form
	 * @param $request PKPRequest
	 * @see Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'stageId' => $this->_reviewAssignment->getStageId(),
			'reviewRoundId' => $this->_reviewRound->getId(),
			'submissionId' => $this->_reviewAssignment->getSubmissionId(),
			'reviewAssignmentId' => $this->_reviewAssignment->getId(),
		));
		return parent::fetch($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array(
			'selectedFiles',
			'responseDueDate',
			'reviewDueDate',
		));
	}

	/**
	 * Save review assignment
	 * @param $request PKPRequest
	 */
	function execute() {
		// Get the list of available files for this review.
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.submission.SubmissionFile'); // File constants
		$submissionFiles = $submissionFileDao->getLatestRevisionsByReviewRound($this->_reviewRound, SUBMISSION_FILE_REVIEW_FILE);
		$selectedFiles = (array) $this->getData('selectedFiles');

		// Revoke all, then grant selected.
		$reviewFilesDao = DAORegistry::getDAO('ReviewFilesDAO');
		$reviewFilesDao->revokeByReviewId($this->_reviewAssignment->getId());
		foreach ($submissionFiles as $submissionFile) {
			if (in_array($submissionFile->getFileId(), $selectedFiles)) {
				$reviewFilesDao->grant($this->_reviewAssignment->getId(), $submissionFile->getFileId());
			}
		}

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = $reviewAssignmentDao->getReviewAssignment($this->_reviewRound->getId(), $this->_reviewAssignment->getReviewerId(), $this->_reviewRound->getRound(), $this->_reviewRound->getStageId());
		$reviewAssignment->setDateDue($this->getData('reviewDueDate'));
		$reviewAssignment->setDateResponseDue($this->getData('responseDueDate'));
		$reviewAssignmentDao->updateObject($reviewAssignment);
	}
}

?>
