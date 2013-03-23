<?php

/**
 * @file controllers/grid/issues/IssueGridHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGridHandler
 * @ingroup controllers_grid_issues
 *
 * @brief Handle issues grid requests.
 */

import('classes.controllers.grid.issues.IssueGridHandler');

class BackIssueGridHandler extends IssueGridHandler {
	/**
	 * Constructor
	 */
	function BackIssueGridHandler() {
		parent::IssueGridHandler();
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request, $args) {
		parent::initialize($request, $args);

		// Basic grid configuration.
		$this->setTitle('editor.issues.backIssues');
	}

	/**
	 * @see GridHandler::loadData
	 */
	function loadData(&$request, $filter) {
		$journal = $request->getJournal();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		return $issueDao->getPublishedIssues($journal->getId());
	}
}

?>
