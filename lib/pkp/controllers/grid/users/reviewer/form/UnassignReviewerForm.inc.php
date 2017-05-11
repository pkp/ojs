<?php
/**
 * @file controllers/grid/users/reviewer/form/UnassignReviewerForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UnassignReviewerForm
 * @ingroup controllers_grid_users_reviewer_form
 *
 * @brief Allow the editor to remove a review assignment
 */

import('lib.pkp.classes.form.Form');

class UnassignReviewerForm extends Form {
	/** The review assignment to delete */
	var $_reviewAssignment;

	/** The submission associated with the review assignment **/
	var $_submission;

	/** The review round associated with the review assignment **/
	var $_reviewRound;

	/**
	 * Constructor
	 * @param mixed $reviewAssignment ReviewAssignment
	 * @param mixed $reviewRound ReviewRound
	 * @param mixed $submission Submission
	 */
	function __construct($reviewAssignment, $reviewRound, $submission) {
		$this->setReviewAssignment($reviewAssignment);
		$this->setReviewRound($reviewRound);
		$this->setSubmission($submission);

		parent::__construct('controllers/grid/users/reviewer/form/unassignReviewerForm.tpl');
	}

	//
	// Overridden template methods
	//
	/**
	 * Initialize form data
	 *
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initData($args, $request) {
		$context = $request->getContext();
		$submission = $this->getSubmission();
		$reviewAssignment = $this->getReviewAssignment();
		$reviewRound = $this->getReviewRound();
		$reviewerId = $reviewAssignment->getReviewerId();

		$this->setData('submissionId', $submission->getId());
		$this->setData('stageId', $reviewRound->getStageId());
		$this->setData('reviewRoundId', $reviewRound->getId());
		$this->setData('reviewAssignmentId', $reviewAssignment->getId());
		$this->setData('reviewerId', $reviewerId);

		import('lib.pkp.classes.mail.SubmissionMailTemplate');
		$template = new SubmissionMailTemplate($submission, 'REVIEW_CANCEL');
		if ($template) {
			$userDao = DAORegistry::getDAO('UserDAO');
			$reviewer = $userDao->getById($reviewerId);
			$user = $request->getUser();

			$template->assignParams(array(
				'reviewerName' => $reviewer->getFullName(),
				'editorialContactSignature' => $user->getContactSignature(),
				'signatureFullName' => $user->getFullname(),
			));
			$template->replaceParams();

			$this->setData('personalMessage', $template->getBody());
		}
	}

	/**
	 * Deletes the review assignment and notifies the reviewer via email
	 *
	 * @param mixed $args
	 * @param $request PKPRequest
	 * @return bool whether or not the review assignment was deleted successfully
	 */
	function execute($args, $request) {
		$submission = $this->getSubmission();
		$reviewAssignment = $this->getReviewAssignment();

		// Notify the reviewer via email.
		import('lib.pkp.classes.mail.SubmissionMailTemplate');
		$mail = new SubmissionMailTemplate($submission, 'REVIEW_CANCEL', null, null, false);

		if ($mail->isEnabled() && !$this->getData('skipEmail')) {
			$userDao = DAORegistry::getDAO('UserDAO');
			$reviewerId = (int) $this->getData('reviewerId');
			$reviewer = $userDao->getById($reviewerId);
			$mail->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
			$mail->setBody($this->getData('personalMessage'));
			$mail->assignParams();
			$mail->send($request);
		}

		// Delete the review assignment.
		// NB: EditorAction::clearReview() will check that this review
		// id is actually attached to the submission so no need for further
		// validation here.
		import('lib.pkp.classes.submission.action.EditorAction');
		$editorAction = new EditorAction();
		return $editorAction->clearReview($request, $submission->getId(), $reviewAssignment->getId());
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array(
			'personalMessage',
			'reviewAssignmentId',
			'reviewRoundId',
			'reviewerId',
			'skipEmail',
			'stageId',
			'submissionId',
		));
	}

	//
	// Getters and Setters
	//
	/**
	 * Set the ReviewAssignment
	 *
	 * @param mixed $reviewAssignment ReviewAssignment
	 */
	function setReviewAssignment($reviewAssignment) {
		$this->_reviewAssignment = $reviewAssignment;
	}

	/**
	 * Get the ReviewAssignment
	 *
	 * @return ReviewAssignment
	 */
	function getReviewAssignment() {
		return $this->_reviewAssignment;
	}

	/**
	 * Set the ReviewRound
	 *
	 * @param mixed $reviewRound ReviewRound
	 */
	function setReviewRound($reviewRound) {
		$this->_reviewRound = $reviewRound;
	}

	/**
	 * Get the ReviewRound
	 *
	 * @return ReviewRound
	 */
	function getReviewRound() {
		return $this->_reviewRound;
	}

	/**
	 * Set the submission
	 * @param $submission Submission
	 */
	function setSubmission($submission) {
		$this->_submission = $submission;
	}

	/**
	 * Get the submission
	 * @return Submission
	 */
	function getSubmission() {
		return $this->_submission;
	}
}
