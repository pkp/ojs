<?php

/**
 * @file controllers/grid/NavigationMenusGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NavigationMenusGridRow
 * @ingroup controllers_grid_NavigationMenus
 *
 * @brief Handle navigationMenus grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class NavigationMenusGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);

		$navigationMenuId = $this->getId();
		if (!empty($navigationMenuId)) {
			$router = $request->getRouter();

			// Create the "edit static page" action
			import('lib.pkp.classes.linkAction.request.AjaxModal');
			$this->addAction(
				new LinkAction(
					'editNavigationMenu',
					new AjaxModal(
						$router->url($request, null, null, 'editNavigationMenu', null, array('navigationMenuId' => $navigationMenuId)),
						__('grid.action.edit'),
						'modal_edit',
						true),
					__('grid.action.edit'),
					'edit'
				)
			);

			// Create the "delete navigationMenu" action
			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			$this->addAction(
				new LinkAction(
					'delete',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('common.confirmDelete'),
						__('grid.action.delete'),
						$router->url($request, null, null, 'delete', null, array('navigationMenuId' => $navigationMenuId)), 'modal_delete'
					),
					__('grid.action.delete'),
					'delete'
				)
			);
		}
	}
}

?>
