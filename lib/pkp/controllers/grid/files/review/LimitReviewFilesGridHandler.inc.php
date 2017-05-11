<?php
/**
 * @file controllers/grid/files/review/LimitReviewFilesGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LimitReviewFilesGridHandler
 * @ingroup controllers_grid_files_review
 *
 * @brief Display a selectable list of review files for the round to editors.
 *   Items in this list can be selected or deselected to give a specific subset
 *   to a particular reviewer.
 */

import('lib.pkp.controllers.grid.files.fileList.SelectableFileListGridHandler');

class LimitReviewFilesGridHandler extends SelectableFileListGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		import('lib.pkp.controllers.grid.files.review.ReviewGridDataProvider');
		// Pass in null stageId to be set in initialize from request var.
		parent::__construct(
			new ReviewGridDataProvider(SUBMISSION_FILE_REVIEW_FILE),
			null,
			FILE_GRID_VIEW_NOTES
		);

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT),
			array('fetchGrid', 'fetchRow')
		);

		// Set the grid information.
		$this->setTitle('editor.submissionReview.restrictFiles');
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		if ($reviewAssignmentId = $request->getUserVar('reviewAssignmentId')) {
			// If a review assignment ID is specified, preload the
			// checkboxes with the currently selected files. To do
			// this, we'll need the review assignment in the context.
			// Add the required policies:

			// 1) Review stage access policy (fetches submission in context)
			import('lib.pkp.classes.security.authorization.ReviewStageAccessPolicy');
			$this->addPolicy(new ReviewStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $request->getUserVar('stageId')));

			// 2) Review assignment
			import('lib.pkp.classes.security.authorization.internal.ReviewAssignmentRequiredPolicy');
			$this->addPolicy(new ReviewAssignmentRequiredPolicy($request, $args, 'reviewAssignmentId', array('fetchGrid', 'fetchRow')));
		}
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::isDataElementSelected()
	 */
	function isDataElementSelected($gridDataElement) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		if ($reviewAssignment) {
			$submissionFile = $gridDataElement['submissionFile'];
			// A review assignment was specified in the request; preset the
			// checkboxes to the currently available set of files.
			$reviewFilesDao = DAORegistry::getDAO('ReviewFilesDAO');
			return $reviewFilesDao->check($reviewAssignment->getId(), $submissionFile->getFileId());
		} else {
			// No review assignment specified; default to all files available.
			return true;
		}
	}
}

?>
