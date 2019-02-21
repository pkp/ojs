<?php

/**
 * @file controllers/grid/issues/IssueGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGridCellProvider
 * @ingroup controllers_grid_issues
 *
 * @brief Grid cell provider for the issue management grid
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class IssueGridCellProvider extends GridCellProvider {
	/** @var string */
	var $dateFormatShort;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->dateFormatShort = Config::getVar('general', 'date_format_short');
	}

	/**
	 * Get cell actions associated with this row/column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array an array of LinkAction instances
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		if ($column->getId() == 'identification') {
			$issue = $row->getData();
			assert(is_a($issue, 'Issue'));
			$router = $request->getRouter();
			import('lib.pkp.classes.linkAction.request.AjaxModal');
			return array(
				new LinkAction(
					'edit',
					new AjaxModal(
						$router->url($request, null, null, 'editIssue', null, array('issueId' => $issue->getId())),
						__('editor.issues.editIssue', array('issueIdentification' => $issue->getIssueIdentification())),
						'modal_edit',
						true
					),
					htmlspecialchars($issue->getIssueIdentification())
				)
			);
		}
		return array();
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$issue = $row->getData();
		$columnId = $column->getId();
		assert (is_a($issue, 'Issue'));
		assert(!empty($columnId));
		switch ($columnId) {
			case 'identification':
				return array('label' => ''); // Title returned as action
			case 'published':
				$datePublished = $issue->getDatePublished();
				if ($datePublished) $datePublished = strtotime($datePublished);
				return array('label' => $datePublished?strftime($this->dateFormatShort, $datePublished):'');
			case 'numArticles':
				return array('label' => $issue->getNumArticles());
			default: assert(false); break;
		}
	}
}


