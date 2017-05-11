<?php
/**
 * @file controllers/grid/plugins/PluginGalleryGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginGalleryGridCellProvider
 * @ingroup controllers_grid_plugins
 *
 * @brief Provide information about plugins to the plugin gallery grid handler
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');
import('lib.pkp.classes.linkAction.request.AjaxModal');

class PluginGalleryGridCellProvider extends GridCellProvider {
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
		assert(is_a($element, 'GalleryPlugin') && !empty($columnId));
		switch ($columnId) {
			case 'name':
				// The name is returned as an action.
				return array('label' => '');
				break;
			case 'summary':
				$label = $element->getLocalizedSummary();
				return array('label' => $label);
				break;
			case 'status':
				switch ($element->getCurrentStatus()) {
					case PLUGIN_GALLERY_STATE_NEWER:
						$statusKey = 'manager.plugins.installedVersionNewer.short';
						break;
					case PLUGIN_GALLERY_STATE_UPGRADABLE:
						$statusKey = 'manager.plugins.installedVersionOlder.short';
						break;
					case PLUGIN_GALLERY_STATE_CURRENT:
						$statusKey = 'manager.plugins.installedVersionNewest.short';
						break;
					case PLUGIN_GALLERY_STATE_AVAILABLE:
						$statusKey = null;
						break;
					case PLUGIN_GALLERY_STATE_INCOMPATIBLE:
						$statusKey = 'manager.plugins.noCompatibleVersion.short';
						break;
					default: return assert(false);
				}
				return array('label' => __($statusKey));
			default:
				break;
		}
	}

	/**
	 * Get cell actions associated with this row/column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array an array of LinkAction instances
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		$element = $row->getData();
		switch ($column->getId()) {
			case 'name':
				$router = $request->getRouter();
				return array(new LinkAction(
					'moreInformation',
					new AjaxModal(
						$router->url($request, null, null, 'viewPlugin', null, array('rowId' => $row->getId()+1)),
						$element->getLocalizedName(),
						'modal_information',
						true
					),
					$element->getLocalizedName(),
					'details'
				));
		}
		return parent::getCellActions($request, $row, $column, $position);
	}
}

?>
