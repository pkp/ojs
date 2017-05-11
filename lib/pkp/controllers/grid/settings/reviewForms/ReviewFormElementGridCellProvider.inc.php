<?php
/**
 * @file controllers/grid/settings/reviewForms/ReviewFormElementGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormElementGridCellProvider
 * @ingroup controllers_grid_settings_reviewForms
 *
 * @brief Subclass for review form element column's cell provider
 */
import('lib.pkp.classes.controllers.grid.GridCellProvider');

class ReviewFormElementGridCellProvider extends GridCellProvider {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$element = $row->getData();
		$columnId = $column->getId();
		assert(is_a($element, 'ReviewFormElement') && !empty($columnId));
		switch ($columnId) {
			case 'question':
				$label = $element->getLocalizedQuestion();
				return array('label' => $label);
				break;
			default:
				assert(false);
				break;
		}
	}
}
?>
