<?php

/**
 * @file controllers/grid/plugins/PluginGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginGridCellProvider
 * @ingroup controllers_grid_plugins
 *
 * @brief Cell provider for columns in a plugin grid.
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class PluginGridCellProvider extends GridCellProvider {

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
		$plugin =& $row->getData();
		$columnId = $column->getId();
		assert(is_a($plugin, 'Plugin') && !empty($columnId));

		switch ($columnId) {
			case 'name':
				return array('label' => $plugin->getDisplayName());
				break;
			case 'category':
				return array('label' => $plugin->getCategory());
				break;
			case 'description':
				return array('label' => $plugin->getDescription());
				break;
			case 'enabled':
				$isEnabled = $plugin->getEnabled();
				return array(
					'selected' => $isEnabled,
					'disabled' => $isEnabled?!$plugin->getCanDisable():!$plugin->getCanEnable(),
				);
			default:
				break;
		}

		return parent::getTemplateVarsFromRowColumn($row, $column);
	}

	/**
	 * @copydoc GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		switch ($column->getId()) {
			case 'enabled':
				$plugin = $row->getData(); /* @var $plugin Plugin */
				$requestArgs = array_merge(
					array('plugin' => $plugin->getName()),
					$row->getRequestArgs()
				);
				switch (true) {
					case $plugin->getEnabled() && $plugin->getCanDisable():
						// Create an action to disable the plugin
						import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
						return array(new LinkAction(
							'disable',
							new RemoteActionConfirmationModal(
								$request->getSession(),
								__('grid.plugin.disable'),
								__('common.disable'),
								$request->url(null, null, 'disable', null, $requestArgs)
							),
							__('manager.plugins.disable'),
							null
						));
						break;
					case !$plugin->getEnabled() && $plugin->getCanEnable():
						// Create an action to enable the plugin
						import('lib.pkp.classes.linkAction.request.AjaxAction');
						return array(new LinkAction(
							'enable',
							new AjaxAction(
								$request->url(null, null, 'enable', null, $requestArgs)
							),
							__('manager.plugins.enable'),
							null
						));
					break;
				}
		}
		return parent::getCellActions($request, $row, $column, $position);
	}
}

?>
