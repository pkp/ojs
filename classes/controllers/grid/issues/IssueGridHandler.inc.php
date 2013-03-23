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

import('lib.pkp.classes.controllers.grid.GridHandler');

class IssueGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function IssueGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
				array(ROLE_ID_EDITOR),
				array('fetchGrid', 'fetchRow'));
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request, $args) {
		parent::initialize($request, $args);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);

		//
		// Grid columns.
		//
		import('controllers.grid.issues.IssueGridCellProvider');
		$issueGridCellProvider = new IssueGridCellProvider();

		// Issue identification
		$this->addColumn(
			new GridColumn(
				'identification',
				'issue.issue',
				null,
				'controllers/grid/gridCell.tpl',
				$issueGridCellProvider
			)
		);

		// Published state
		$this->addColumn(
			new GridColumn(
				'published',
				'editor.issues.published',
				null,
				'controllers/grid/gridCell.tpl',
				$issueGridCellProvider
			)
		);

		// Number of articles
		$this->addColumn(
			new GridColumn(
				'numArticles',
				'editor.issues.numArticles',
				null,
				'controllers/grid/gridCell.tpl',
				$issueGridCellProvider
			)
		);
	}
}

?>
