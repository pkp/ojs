<?php

/**
 * @file controllers/grid/queries/QueryTitleGridColumn.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueryTitleGridColumn
 * @ingroup controllers_grid_queriess
 *
 * @brief Implements a query tile column.
 */

import('lib.pkp.classes.controllers.grid.GridColumn');

class QueryTitleGridColumn extends GridColumn {

	/** @var array Action args for link actions */
	var $_actionArgs;

	/**
	 * Constructor
	 * @param $actionArgs array Action args for link actions
	 */
	function __construct($actionArgs) {
		$this->_actionArgs = $actionArgs;

		import('lib.pkp.classes.controllers.grid.ColumnBasedGridCellProvider');
		$cellProvider = new ColumnBasedGridCellProvider();

		parent::__construct('name', 'common.name', null, null, $cellProvider,
			array('width' => 60, 'alignment' => COLUMN_ALIGNMENT_LEFT));
	}


	//
	// Public methods
	//
	/**
	 * Method expected by ColumnBasedGridCellProvider
	 * to render a cell in this column.
	 *
	 * @copydoc ColumnBasedGridCellProvider::getTemplateVarsFromRowColumn()
	 */
	function getTemplateVarsFromRow($row) {
		// We do not need any template variables because
		// the only content of this column's cell will be
		// an action. See QueryTitleGridColumn::getCellActions().
		return array('label' => '');
	}


	//
	// Override methods from GridColumn
	//
	/**
	 * @copydoc GridColumn::getCellActions()
	 */
	function getCellActions($request, $row, $position = GRID_ACTION_POSITION_DEFAULT) {
		// Retrieve the submission file.
		$query = $row->getData();
		$headNote = $query->getHeadNote();

		// Create the cell action to download a file.
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$router = $request->getRouter();
		$actionArgs = array_merge(
			$this->_actionArgs,
			array('queryId' => $query->getId())
		);

		return array_merge(
			parent::getCellActions($request, $row, $position),
			array(
				new LinkAction(
					'readQuery',
					new AjaxModal(
						$router->url($request, null, null, 'readQuery', null, $actionArgs),
						$headNote?$headNote->getTitle():'&mdash;',
						'modal_edit'
					),
					($headNote && $headNote->getTitle()!='')?$headNote->getTitle():'&mdash;',
					null
				)
			)
		);
	}
}

?>
