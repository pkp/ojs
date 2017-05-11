<?php

/**
 * @file controllers/grid/submissions/unassignedSubmissions/UnassignedSubmissionsListGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UnassignedSubmissionsListGridHandler
 * @ingroup controllers_grid_submissions_unassignedSubmissions
 *
 * @brief Handle unassigned submissions list grid requests.
 */

// Import grid base classes.
import('lib.pkp.controllers.grid.submissions.SubmissionsListGridHandler');

// Filter editor
define('FILTER_EDITOR_ALL', 0);
define('FILTER_EDITOR_ME', 1);

class UnassignedSubmissionsListGridHandler extends SubmissionsListGridHandler {
	
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR),
			array('fetchGrid', 'fetchRow', 'fetchRows', 'deleteSubmission')
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

		// Set title.
		$this->setTitle('common.queue.long.submissionsUnassigned');

		// Add editor specific locale component.
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
	}


	//
	// Implement methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$submissionDao = Application::getSubmissionDAO(); /* @var $submissionDao SubmissionDAO */

		// Determine whether this is a Sub Editor or Manager.
		// Managers can access all submissions, Sub Editors
		// only assigned submissions.
		$user = $request->getUser();
		$userId = $user->getId();

		$context = $request->getContext();

		list($search, $column, $stageId, $sectionId) = $this->getFilterValues($filter);
		$title = $author = null;
		if ($column == 'title') {
			$title = $search;
		} else {
			$author = $search;
		}

		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());
		return $submissionDao->getBySubEditorId(
			$context->getId(),
			null,
			false, // do not include STATUS_DECLINED submissions
			false,  // include only unpublished submissions
			$title,
			$author,
			$stageId,
			$sectionId,
			$rangeInfo
		);
	}
}

?>
