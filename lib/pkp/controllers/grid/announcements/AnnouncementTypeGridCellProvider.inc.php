<?php

/**
 * @file controllers/grid/announcements/AnnouncementTypeGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeGridCellProvider
 * @ingroup controllers_grid_announcements
 *
 * @brief Cell provider for title column of an announcement type grid.
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class AnnouncementTypeGridCellProvider extends GridCellProvider {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * @copydoc GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		switch ($column->getId()) {
			case 'name':
				$announcementType = $row->getData();
				$router = $request->getRouter();
				$actionArgs = array('announcementTypeId' => $row->getId());

				import('lib.pkp.classes.linkAction.request.AjaxModal');
				return array(new LinkAction(
					'edit',
					new AjaxModal(
						$router->url($request, null, null, 'editAnnouncementType', null, $actionArgs),
						__('grid.action.edit'),
						null,
						true),
					$announcementType->getLocalizedTypeName()
				));
		}
		return parent::getCellActions($request, $row, $column, $position);
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$announcementType = $row->getData();
		$columnId = $column->getId();
		assert(is_a($announcementType, 'AnnouncementType') && !empty($columnId));

		switch ($columnId) {
			case 'title':
				return array('label' => $announcementType->getLocalizedName());
				break;
			default:
				break;
		}

		return parent::getTemplateVarsFromRowColumn($row, $column);
	}
}

?>
