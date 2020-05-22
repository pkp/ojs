<?php
/**
 * @file controllers/grid/settings/sections/SectionGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionGridCellProvider
 * @ingroup controllers_grid_settings_sections
 *
 * @brief Subclass for review form column's cell provider
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class SectionGridCellProvider extends GridCellProvider {

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
		assert(is_a($element, 'Section') && !empty($columnId));
		switch ($columnId) {
			case 'archived':
				return array('selected' => $element['archived']);
		}
		return parent::getTemplateVarsFromRowColumn($row, $column);
	}

	/**
	 * @see GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		switch ($column->getId()) {
			case 'archived':
				$element = $row->getData(); /* @var $element DataObject */

				$router = $request->getRouter();
				import('lib.pkp.classes.linkAction.LinkAction');

				if ($element['archived']) return array(new LinkAction(
					'deactivateSection',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('manager.sections.confirmDearchive'),
						null,
						$router->url(
							$request,
							null,
							'grid.settings.sections.SectionGridHandler',
							'dearchiveSection',
							null,
							array('sectionKey' => $row->getId())
						)
					)
				));
				else return array(new LinkAction(
					'activateSection',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('manager.sections.confirmArchive'),
						null,
						$router->url(
							$request,
							null,
							'grid.settings.sections.SectionGridHandler',
							'archiveSection',
							null,
							array('sectionKey' => $row->getId())
						)
					)
				));
		}
		return parent::getCellActions($request, $row, $column, $position);
	}
}