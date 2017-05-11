<?php
/**
 * @file controllers/grid/files/attachment/ReviewerReviewAttachmentGridDataProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerReviewAttachmentGridDataProvider
 * @ingroup controllers_grid_files_attachment
 *
 * @brief Provide the reviewers access to their own review attachments data for grids.
 */


import('lib.pkp.controllers.grid.files.SubmissionFilesGridDataProvider');

class ReviewerReviewAttachmentGridDataProvider extends SubmissionFilesGridDataProvider {
	/** @var integer */
	var $_reviewId;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct(SUBMISSION_FILE_REVIEW_ATTACHMENT);
	}


	//
	// Implement template methods from GridDataProvider
	//
	/**
	 * @copydoc GridDataProvider::getAuthorizationPolicy()
	 */
	function getAuthorizationPolicy($request, $args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.internal.ReviewAssignmentRequiredPolicy');

		// Need to use the reviewId because this grid can either be
		// viewed by the reviewer (in which case, we could do a
		// $request->getUser()->getId() or by the editor when reading
		// the review. The following covers both cases...
		$assocType = (int) $request->getUserVar('assocType');
		$assocId = (int) $request->getUserVar('assocId');
		if ($assocType && $assocId) {
			// Viewing from a Reviewer perspective.
			assert($assocType == ASSOC_TYPE_REVIEW_ASSIGNMENT);

			$this->setUploaderRoles($roleAssignments);
			import('lib.pkp.classes.security.authorization.ReviewStageAccessPolicy');

			$authorizationPolicy = new ReviewStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $request->getUserVar('stageId'));
			$paramName = 'assocId';
		} else {
			// Viewing from a context role perspective.
			$authorizationPolicy = parent::getAuthorizationPolicy($request, $args, $roleAssignments);
			$paramName = 'reviewId';
		}

		$authorizationPolicy->addPolicy(new ReviewAssignmentRequiredPolicy($request, $args, $paramName));

		return $authorizationPolicy;
	}

	/**
	 * @copydoc GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		return array_merge(
			parent::getRequestArgs(),
			array(
				'assocType' => ASSOC_TYPE_REVIEW_ASSIGNMENT,
				'assocId' => $this->_getReviewId()
			)
		);
	}

	/**
	 * @copydoc GridDataProvider::loadData()
	 */
	function loadData($filter = array()) {
		// Get all review files assigned to this submission.
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$submissionFiles = $submissionFileDao->getAllRevisionsByAssocId(
			ASSOC_TYPE_REVIEW_ASSIGNMENT, $this->_getReviewId(), $this->getFileStage()
		);
		return $this->prepareSubmissionFileData($submissionFiles, false, $filter);
	}

	//
	// Overridden public methods from FilesGridDataProvider
	//
	/**
	 * @copydoc FilesGridDataProvider::getAddFileAction()
	 */
	function getAddFileAction($request) {
		import('lib.pkp.controllers.api.file.linkAction.AddFileLinkAction');
		$submission = $this->getSubmission();

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = $reviewAssignmentDao->getById($this->_getReviewId());

		return new AddFileLinkAction(
			$request, $submission->getId(), $this->getStageId(),
			$this->getUploaderRoles(), null, $this->getFileStage(),
			ASSOC_TYPE_REVIEW_ASSIGNMENT, $this->_getReviewId(),
			$reviewAssignment->getReviewRoundId()
		);
	}
	//
	// Private helper methods
	//
	/**
	 * Get the review id.
	 * @return integer
	 */
	function _getReviewId() {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		return $reviewAssignment->getId();
	}
}

?>
