<?php
/**
 * @file controllers/grid/files/review/ManageReviewFilesGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManageReviewFilesGridHandler
 * @ingroup controllers_grid_files_review
 *
 * @brief Handle the editor review file selection grid (selects which files to send to review or to next review round)
 */

// import grid base classes
import('lib.pkp.controllers.grid.files.SelectableSubmissionFileListCategoryGridHandler');

class ManageReviewFilesGridHandler extends SelectableSubmissionFileListCategoryGridHandler {

	/** @var array */
	var $_selectionArgs;


	/**
	 * Constructor
	 */
	function __construct() {
		import('lib.pkp.controllers.grid.files.review.ReviewCategoryGridDataProvider');
		// Pass in null stageId to be set in initialize from request var.
		parent::__construct(
			new ReviewCategoryGridDataProvider(SUBMISSION_FILE_REVIEW_FILE),
			null,
			FILE_GRID_ADD|FILE_GRID_VIEW_NOTES
		);

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT),
			array('fetchGrid', 'fetchCategory', 'fetchRow', 'updateReviewFiles')
		);

		// Set the grid title.
		$this->setTitle('reviewer.submission.reviewFiles');
	}


	//
	// Public handler methods
	//
	/**
	 * Save 'manage review files' form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateReviewFiles($args, $request) {
		$submission = $this->getSubmission();

		import('lib.pkp.controllers.grid.files.review.form.ManageReviewFilesForm');
		$manageReviewFilesForm = new ManageReviewFilesForm($submission->getId(), $this->getRequestArg('stageId'), $this->getRequestArg('reviewRoundId'));
		$manageReviewFilesForm->readInputData();

		if ($manageReviewFilesForm->validate()) {
			$dataProvider = $this->getDataProvider();
			$manageReviewFilesForm->execute(
				$args, $request,
				$this->getGridCategoryDataElements($request, $this->getStageId())
			);

			$this->setupTemplate($request);
			$user = $request->getUser();
			NotificationManager::createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.updatedReviewFiles')));

			// Let the calling grid reload itself
			return DAO::getDataChangedEvent();
		} else {
			return new JSONMessage(false);
		}
	}


	//
	// Extended methods from CategoryGridHandler.
	//
	/**
	 * @copydoc CategoryGridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		return array_merge(array('stageId' => $stageId), parent::getRequestArgs());
	}
}

?>
