<?php

/**
 * @file controllers/grid/StaticPageGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StaticPageGridCellProvider
 * @ingroup controllers_grid_staticPages
 *
 * @brief Class for a cell provider to display information about static pages
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');
import('lib.pkp.classes.linkAction.request.RedirectAction');

class StaticPageGridCellProvider extends GridCellProvider {
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
	 * Get cell actions associated with this row/column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array an array of LinkAction instances
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		$staticPage = $row->getData();

		switch ($column->getId()) {
			case 'path':
				$dispatcher = $request->getDispatcher();
				return array(new LinkAction(
					'details',
					new RedirectAction(
						$dispatcher->url($request, ROUTE_PAGE, null) . '/' . $staticPage->getPath(),
						'staticPage'
					),
					$staticPage->getPath()
				));
			default:
				return parent::getCellActions($request, $row, $column, $position);
		}
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$staticPage = $row->getData();

		switch ($column->getId()) {
			case 'path':
				// The action has the label
				return array('label' => '');
			case 'title':
				return array('label' => $staticPage->getLocalizedTitle());
		}
	}
}

?>
