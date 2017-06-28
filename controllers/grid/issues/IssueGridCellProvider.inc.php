<?php

/**
 * @file controllers/grid/issues/IssueGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
	function __construct($request) {
		parent::__construct($request);
		$this->dateFormatShort = Config::getVar('general', 'date_format_short');
	}

	/**
	 * Get cell actions associated with this row/column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @param $position string
	 * @return array an array of LinkAction instances
	 */
	function getCellActions($row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		if ($column->getId() == 'identification') {
			$issue = $row->getData();
			assert(is_a($issue, 'Issue'));
			$router = $this->_request->getRouter();
			import('lib.pkp.classes.linkAction.request.AjaxModal');
			return array(
				new LinkAction(
					'edit',
					new AjaxModal(
						$router->url($this->_request, null, null, 'editIssue', null, array('issueId' => $issue->getId())),
						__('editor.issues.editIssue', array('issueIdentification' => $issue->getIssueIdentification())),
						'modal_edit',
						true
					),
					$issue->getIssueIdentification()
				)
			);
		}
		return array();
	}

	/**
	 * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
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

?>
