<?php

/**
 * @file controllers/grid/queries/QueryNotesGridRow.inc.php
 *
 * Copyright (c) 2016-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueryNotesGridRow
 * @ingroup controllers_grid_queries
 *
 * @brief Base class for query grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class QueryNotesGridRow extends GridRow {
	/** @var array **/
	var $_actionArgs;

	/** @var Query */
	var $_query;

	/** @var QueryNotesGridHandler */
	var $_queryNotesGrid;

	/**
	 * Constructor
	 * @param $actionArgs array Action arguments
	 * @param $query Query
	 * @param $queryNotesGrid The notes grid containing this row
	 */
	function __construct($actionArgs, $query, $queryNotesGrid) {
		$this->_actionArgs = $actionArgs;
		$this->_query = $query;
		$this->_queryNotesGrid = $queryNotesGrid;

		parent::__construct();
	}

	//
	// Overridden methods from GridRow
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		// Do the default initialization
		parent::initialize($request, $template);

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		$headNote = $this->getQuery()->getHeadNote();
		if (!empty($rowId) && is_numeric($rowId) && (!$headNote || $headNote->getId() != $rowId)) {
			// Only add row actions if this is an existing row
			$router = $request->getRouter();
			$actionArgs = array_merge(
				$this->_actionArgs,
				array('noteId' => $rowId)
			);

			// Add row-level actions
			if ($this->_queryNotesGrid->getCanManage($this->getData())) {
				import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
				$this->addAction(
					new LinkAction(
						'deleteNote',
						new RemoteActionConfirmationModal(
							$request->getSession(),
							__('common.confirmDelete'),
							__('grid.action.delete'),
							$router->url($request, null, null, 'deleteNote', null, $actionArgs), 'modal_delete'),
						__('grid.action.delete'),
						'delete')
				);
			}
		}
	}

	/**
	 * Get the query
	 * @return Query
	 */
	function getQuery() {
		return $this->_query;
	}

	/**
	 * Get the base arguments that will identify the data in the grid.
	 * @return array
	 */
	function getRequestArgs() {
		return $this->_actionArgs;
	}
}

?>
