<?php

/**
 * @file controllers/grid/files/submission/SubmissionWizardFilesGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionWizardFilesGridHandler
 * @ingroup controllers_grid_files_submission
 *
 * @brief Handle submission file grid requests at the author submission wizard.
 * The submission author and all context/editor roles have access to this grid.
 */

import('lib.pkp.controllers.grid.files.fileList.FileListGridHandler');

class SubmissionWizardFilesGridHandler extends FileListGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		// import app-specific grid data provider for access policies.
		import('lib.pkp.controllers.grid.files.SubmissionFilesGridDataProvider');
		parent::__construct(
			new SubmissionFilesGridDataProvider(SUBMISSION_FILE_SUBMISSION),
			WORKFLOW_STAGE_ID_SUBMISSION,
			FILE_GRID_ADD|FILE_GRID_DELETE|FILE_GRID_VIEW_NOTES|FILE_GRID_EDIT
		);
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR),
			array('fetchGrid', 'fetchRow')
		);

		// Set grid title.
		$this->setTitle('submission.submit.submissionFiles');
	}

	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request) {
		// Determine whether to force the user group ID submitted
		// on step 1 of the submission wizard during upload
		$submission = $this->getSubmission();
		$authors = $submission->getAuthors();
		if (count($authors)==1) {
			// Force the author's user group. (#8302)
			$author = array_shift($authors);
			$dataProvider = $this->getDataProvider();
			$dataProvider->setUploaderGroupIds(array($author->getUserGroupId()));
		}

		return parent::initialize($request);
	}
}

?>
