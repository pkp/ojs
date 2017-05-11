<?php

/**
 * @file controllers/grid/files/review/WorkflowReviewRevisionsGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowReviewRevisionsGridHandler
 * @ingroup controllers_grid_files_review
 *
 * @brief Display in workflow pages the file revisions that authors have uploaded.
 */

import('lib.pkp.controllers.grid.files.fileList.FileListGridHandler');

class WorkflowReviewRevisionsGridHandler extends FileListGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		import('lib.pkp.controllers.grid.files.review.ReviewGridDataProvider');
		parent::__construct(
			new ReviewGridDataProvider(SUBMISSION_FILE_REVIEW_REVISION),
			null,
			FILE_GRID_ADD|FILE_GRID_EDIT|FILE_GRID_VIEW_NOTES|FILE_GRID_DELETE
		);

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT),
			array('fetchGrid', 'fetchRow', 'addFile')
		);

		$this->setTitle('editor.submission.revisions');
	}
}

?>
