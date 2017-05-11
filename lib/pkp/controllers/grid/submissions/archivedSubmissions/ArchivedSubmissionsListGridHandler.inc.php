<?php

/**
 * @file controllers/grid/submissions/archivedSubmissions/ArchivedSubmissionsListGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArchivedSubmissionsListGridHandler
 * @ingroup controllers_grid_submissions_archivedSubmissions
 *
 * @brief Handle archived submissions list grid requests.
 */

// Import grid base classes.
import('lib.pkp.controllers.grid.submissions.SubmissionsListGridHandler');

// Filter editor
define('FILTER_EDITOR_ALL', 0);
define('FILTER_EDITOR_ME', 1);

class ArchivedSubmissionsListGridHandler extends SubmissionsListGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_REVIEWER, ROLE_ID_ASSISTANT, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR),
			array('fetchGrid', 'fetchRows', 'fetchRow')
		);
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR),
			array('deleteSubmission')
		);
	}


	//
	// Implement template methods from GridHandler
	//
	function getIsSubComponent() {
		return false;
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request, $filter) {
		$context = $request->getContext();
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		$submissionDao = Application::getSubmissionDAO();
		$user = $request->getUser();
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());

		list($search, $column, $stageId, $sectionId) = $this->getFilterValues($filter);
		$title = $author = null;
		if ($column == 'title') {
			$title = $search;
		} elseif ($column == 'author') {
			$author = $search;
		}

		if ($userRoles == array(ROLE_ID_REVIEWER)) {
			// Just a reviewer, get the rejected reviews submissions only.
			return $submissionDao->getReviewerArchived($user->getId(), $context->getId(), $title, $author, $stageId, $sectionId, $rangeInfo);
		}

		$canSeeAllSubmissions = in_array(ROLE_ID_MANAGER, $userRoles);

		return $submissionDao->getByStatus(
			array(STATUS_DECLINED, STATUS_PUBLISHED),
			$canSeeAllSubmissions?null:$user->getId(),
			$context->getId(),
			$title,
			$author,
			$stageId,
			$sectionId,
			$rangeInfo
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
		$this->setTitle('common.queue.long.submissionsArchived');

		// Add editor specific locale component.
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
	}


	//
	// Extend methods from SubmissionsListGridHandler
	//
	/**
	 * @copydoc SubmissionsListGridHandler::getItemsNumber()
	 */
	protected function getItemsNumber() {
		return 20;
	}
}

?>
