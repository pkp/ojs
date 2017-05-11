<?php

/**
 * @file controllers/grid/admin/context/ContextGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextGridRow
 * @ingroup controllers_grid_admin_context
 *
 * @brief Context grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

class ContextGridRow extends GridRow {
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
		parent::initialize($request, $template);

		// Is this a new row or an existing row?
		$element = $this->getData();
		assert(is_a($element, 'Context'));

		$rowId = $this->getId();

		$router = $request->getRouter();
		$this->addAction(
			new LinkAction(
				'edit',
				new AjaxModal(
					$router->url($request, null, null, 'editContext', null, array('rowId' => $rowId)),
					__('grid.action.edit'),
					'modal_edit',
					true
					),
				__('grid.action.edit'),
				'edit'
			)
		);
		$this->addAction(
			new LinkAction(
				'delete',
				new RemoteActionConfirmationModal(
					$request->getSession(),
					__('admin.contexts.confirmDelete'),
					null,
					$router->url($request, null, null, 'deleteContext', null, array('rowId' => $rowId))
					),
				__('grid.action.remove'),
				'delete'
			)
		);

		import('lib.pkp.classes.linkAction.request.RedirectAction');
		$dispatcher = $router->getDispatcher();
		$this->addAction(
			new LinkAction(
				'wizard',
				new RedirectAction(
					$dispatcher->url($request, ROUTE_PAGE, $element->getPath(), 'admin', 'contexts', null, array('openWizard' => 1))),
				__('grid.action.wizard'),
				'wrench'
			)
		);
	}
}

?>
