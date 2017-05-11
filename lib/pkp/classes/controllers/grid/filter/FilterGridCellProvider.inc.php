<?php

/**
 * @file classes/controllers/grid/filter/FilterGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterGridCellProvider
 * @ingroup classes_controllers_grid_filter
 *
 * @brief Base class for a cell provider that can retrieve labels from DataObjects
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class FilterGridCellProvider extends GridCellProvider {
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
	 * This implementation assumes an element that is a
	 * Filter. It will display the filter name and information
	 * about filter parameters (if any).
	 * @see GridCellProvider::getTemplateVarsFromRowColumn()
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$filter =& $row->getData();
		assert(is_a($filter, 'Filter'));
		switch($column->getId()) {
			case 'settings':
				$label = '';
				foreach($filter->getSettings() as $filterSetting) {
					$settingData = $filter->getData($filterSetting->getName());
					if (is_a($filterSetting, 'BooleanFilterSetting')) {
						if ($settingData) {
							if (!empty($label)) $label .= ' | ';
							$label .= __($filterSetting->getDisplayName());
						}
					} else {
						if (!empty($settingData)) {
							if (!empty($label)) $label .= ' | ';
							$label .= __($filterSetting->getDisplayName()).': '.$settingData;
						}
					}
				}
				break;

			default:
				$label = $filter->getData($column->getId());
		}
		return array('label' => $label);
	}
}

?>
