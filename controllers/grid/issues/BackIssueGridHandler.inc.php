<?php

/**
 * @file controllers/grid/issues/IssueGridHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
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
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('saveSequence')
		);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Basic grid configuration.
		$this->setTitle('editor.issues.backIssues');
	}

	/**
	 * Private function to add central columns to the grid.
	 * @param $issueGridCellProvider IssueGridCellProvider
	 */
	protected function _addCenterColumns($issueGridCellProvider) {
		// Published state
		$this->addColumn(
			new GridColumn(
				'published',
				'editor.issues.published',
				null,
				null,
				$issueGridCellProvider
			)
		);
	}

	/**
	 * @copydoc GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence) {
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->moveCustomIssueOrder($gridDataElement->getJournalId(), $gridDataElement->getId(), $newSequence);
	}

	/**
	 * @copydoc GridHandler::getDataElementSequence()
	 */
	function getDataElementSequence($gridDataElement) {
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$customOrder = $issueDao->getCustomIssueOrder($gridDataElement->getJournalId(), $gridDataElement->getId());
		if ($customOrder !== null) return $customOrder;

		if ($gridDataElement->getCurrent()) return 0;
		return $gridDataElement->getDatePublished();
	}

	/**
	 * @copydoc GridHandler::addFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
		return array(new OrderGridItemsFeature());
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$journal = $request->getJournal();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		return $issueDao->getPublishedIssues($journal->getId());
	}

	/**
	 * Get the js handler for this component.
	 * @return string
	 */
	public function getJSHandler() {
		return '$.pkp.controllers.grid.issues.BackIssueGridHandler';
	}
}


