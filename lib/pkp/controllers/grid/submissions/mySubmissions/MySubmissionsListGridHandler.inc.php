<?php

/**
 * @file controllers/grid/submissions/mySubmissions/MySubmissionsListGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MySubmissionsListGridHandler
 * @ingroup controllers_grid_submissions_mySubmissions
 *
 * @brief Handle author's submissions list grid requests (submissions the user has made).
 */

// Import grid base classes.
import('lib.pkp.controllers.grid.submissions.SubmissionsListGridHandler');

class MySubmissionsListGridHandler extends SubmissionsListGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR),
			array('fetchGrid', 'fetchRows', 'fetchRow', 'deleteSubmission')
		);
	}

	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);
		
		$this->setTitle('submission.mySubmissions');
	}

	
	//
	// Implement methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		list($search, $column, $stageId, $sectionId) = $this->getFilterValues($filter);

		$submissionDao = Application::getSubmissionDAO();
		return $submissionDao->getUnpublishedByUserId(
			$request->getUser()->getId(),
			$request->getContext()->getId(),
			$search,
			$stageId,
			$sectionId,
			$this->getGridRangeInfo($request, $this->getId())
		);
	}

	
	//
	// Extend methods from SubmissionListGridHandler
	//
	/**
	 * @copyDoc SubmissionListGridHandler::getFilterColumns()
	 */
	function getFilterColumns() {
		$columns = parent::getFilterColumns();
		unset($columns['author']);

		return $columns;
	}
}

?>
