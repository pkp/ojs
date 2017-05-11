<?php

/**
 * @file controllers/grid/users/reviewerSelect/ReviewerSelectGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataObjectGridCellProvider
 * @ingroup controllers_grid_users_reviewerSelect
 *
 * @brief Base class for a cell provider that retrieves statistics and other data for selectinga reviewer
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class ReviewerSelectGridCellProvider extends DataObjectGridCellProvider {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Template methods from GridCellProvider
	//
	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$element = $row->getData();
		assert(is_a($element, 'User'));
		switch ($column->getId()) {
			case 'select': // Displays the radio option
				return array('rowId' => $row->getId());

			case 'name': // Reviewer's name
				return array('label' => $element->getFullName());

			case 'done': // # of reviews completed
				return array('label' => $element->getData('completeCount'));

			case 'avg': // Average period of time in days to complete a review
				return array('label' => round($element->getData('averageTime')));

			case 'last': // Days since most recently completed review
				$lastAssigned = $element->getData('lastAssigned');
				if (!$lastAssigned) return array('label' => '--');
				$formattedDate = strftime('%b %e', strtotime($lastAssigned));
				return array('label' => $formattedDate);

			case 'active': // How many reviews are currently being considered or underway
				return array('label' => $element->getData('incompleteCount'));

			case 'interests': // Reviewing interests
				return array('label' => $element->getInterestString());
		}
		assert(false);
	}
}

?>
