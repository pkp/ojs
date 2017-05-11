<?php

/**
 * @file controllers/grid/admin/systemInfo/InfoGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InfoGridCellProvider
 * @ingroup controllers_grid_admin_systemInfo
 *
 * @brief Subclass for the admin sysInfo grid's cell provider
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class InfoGridCellProvider extends GridCellProvider {

	/* boolean */
	var $_translate;

	/**
	 * Constructor
	 */
	function __construct($translate = false) {
		parent::__construct();
		$this->_translate = $translate;
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
		assert(!empty($columnId));
		switch ($columnId) {
			case 'name':
				if ($this->_translate)
					return array('label' => __($row->getId()));
				else
					return array('label' => $row->getId());
				break;
			case 'value':
				if ($element === true) return array('label' => __('common.on'));
				if ($element === false) return array('label' => __('common.off'));
				return array('label' => sprintf('%s', $element));
				break;
			case 'version':
				return array('label' => $element->getVersionString(false));
				break;
			case 'versionMajor':
				return array('label' => $element->getMajor());
				break;
			case 'versionMinor':
				return array('label' => $element->getMinor());
				break;
			case 'versionRevision':
				return array('label' => $element->getRevision());
				break;
			case 'versionBuild':
				return array('label' => $element->getBuild());
				break;
			case 'dateInstalled':
				$dateFormatShort = Config::getVar('general', 'date_format_short');
				return array('label' => strftime($dateFormatShort, strtotime($element->getDateInstalled())));
				break;
			default:
				break;
		}
	}
}

?>
