<?php
/**
 * @file controllers/grid/files/query/QueryNoteFilesCategoryGridDataProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueryNoteFilesGridCategoryDataProvider
 * @ingroup controllers_grid_files_query
 *
 * @brief Provide access to query file data for category grids.
 */

import('lib.pkp.controllers.grid.files.SubmissionFilesCategoryGridDataProvider');

class QueryNoteFilesCategoryGridDataProvider extends SubmissionFilesCategoryGridDataProvider {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct(SUBMISSION_FILE_QUERY);
	}


	//
	// Overriden public methods from SubmissionFilesCategoryGridDataProvider
	//
	/**
	 * @copydoc SubmissionFilesCategoryGridDataProvider::initGridDataProvider()
	 */
	function initGridDataProvider($fileStage, $initParams = null) {
		$request = Application::getRequest();
		import('lib.pkp.controllers.grid.files.query.QueryNoteFilesGridDataProvider');
		return new QueryNoteFilesGridDataProvider($request->getUserVar('noteId'));
	}
}

?>
