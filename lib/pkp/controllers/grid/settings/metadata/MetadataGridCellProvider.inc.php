<?php

/**
 * @file controllers/grid/settings/metadata/MetadataGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataGridCellProvider
 * @ingroup controllers_grid_settings_metadata
 *
 * @brief Subclass for a metadata grid column's cell provider
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class MetadataGridCellProvider extends GridCellProvider {

	/** @var Context */
	var $_context;

	/**
	 * Constructor
	 * @param $context Context
	 */
	function __construct($context) {
		$this->_context = $context;
		parent::__construct();
	}

	/**
	 * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$element = $row->getData();
		$columnId = $column->getId();
		switch ($columnId) {
			case 'name':
				return array('label' => $element['name']);
			case 'submission':
				$settingName = $row->getId() . 'EnabledSubmission'; // e.g. typeEnabledSubmission
				$settingEnabled = $this->_context->getSetting($settingName);
				return array('name' => $settingName, 'selected' => $settingEnabled?true:false);
			case 'workflow':
				$settingName = $row->getId() . 'EnabledWorkflow'; // e.g. typeEnabledWorkflow
				$settingEnabled = $this->_context->getSetting($settingName);
				return array('name' => $settingName, 'selected' => $settingEnabled?true:false);
		}
		assert(false);
	}
}

?>
