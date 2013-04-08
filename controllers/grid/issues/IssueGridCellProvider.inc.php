<?php

/**
 * @file controllers/grid/admin/systemIssue/IssueGridCellProvider.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGridCellProvider
 * @ingroup controllers_grid_issues
 *
 * @brief Grid cell provider for the issue management grid
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class IssueGridCellProvider extends GridCellProvider {
	/** @var $dateFormatShort string */
	var $dateFormatShort;

	/**
	 * Constructor
	 */
	function IssueGridCellProvider() {
		parent::GridCellProvider();
		$this->dateFormatShort = Config::getVar('general', 'date_format_short');
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn(&$row, $column) {
		$issue = $row->getData();
		$columnId = $column->getId();
		assert (is_a($issue, 'Issue'));
		assert(!empty($columnId));
		switch ($columnId) {
			case 'identification':
				return array('label' => $issue->getIssueIdentification());
				break;
			case 'published':
				$datePublished = $issue->getDatePublished();
				if ($datePublished) $datePublished = strtotime($datePublished);
				return array('label' => $datePublished?strftime($this->dateFormatShort, $datePublished):'');
				break;
			case 'numArticles':
				return array('label' => $issue->getNumArticles());
				break;
			default: assert(false); break;
		}
	}
}

?>
