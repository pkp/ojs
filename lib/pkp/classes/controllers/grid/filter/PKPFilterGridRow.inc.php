<?php

/**
 * @file classes/controllers/grid/filter/PKPFilterGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPFilterGridRow
 * @ingroup classes_controllers_grid_filter
 *
 * @brief The filter grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class PKPFilterGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function __construct() {
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
		if (!empty($rowId) && is_numeric($rowId)) {
			// Only add row actions if this is an existing row
			$router = $request->getRouter();
			$actionArgs = array(
				'filterId' => $rowId
			);

			// Add row actions

			// Only add an edit action if the filter actually has
			// settings to be configured.
			$filter =& $this->getData();
			assert(is_a($filter, 'Filter'));
			import('lib.pkp.classes.linkAction.request.AjaxModal');
			if ($filter->hasSettings()) {
				$this->addAction(
					new LinkAction(
						'editFilter',
						new AjaxModal(
							$router->url($request, null, null, 'editFilter', null, $actionArgs),
							__('grid.action.edit'),
							'edit'
						),
						__('grid.action.edit'),
						'edit'
					)
				);
			}
			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			$this->addAction(
				new LinkAction(
					'deleteFilter',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('manager.setup.filter.grid.confirmDelete', array('filterName' => $filter->getDisplayName())),
						__('common.delete'),
						$router->url($request, null, null, 'deleteFilter', null, $actionArgs),
						'modal_delete'
					),
					__('common.delete'),
					'delete'
				)
			);
		}
	}
}

?>
