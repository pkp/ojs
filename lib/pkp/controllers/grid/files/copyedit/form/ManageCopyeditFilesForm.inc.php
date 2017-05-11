<?php

/**
 * @file controllers/grid/files/copyedit/form/ManageCopyeditFilesForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManageCopyeditFilesForm
 * @ingroup controllers_grid_files_copyedit
 *
 * @brief Form to add files to the copyedited files grid
 */

import('lib.pkp.controllers.grid.files.form.ManageSubmissionFilesForm');

class ManageCopyeditFilesForm extends ManageSubmissionFilesForm {

	/**
	 * Constructor.
	 * @param $submissionId int Submission ID.
	 */
	function __construct($submissionId) {
		parent::__construct($submissionId, 'controllers/grid/files/copyedit/manageCopyeditFiles.tpl');
	}

	/**
	 * Save selection of copyedited files
	 * @param $args array
	 * @param $request PKPRequest
	 * @param $stageSubmissionFiles array List of submission files in this stage.
	 */
	function execute($args, $request, $stageSubmissionFiles) {
		parent::execute($args, $request, $stageSubmissionFiles, SUBMISSION_FILE_COPYEDIT);
	}
}

?>
