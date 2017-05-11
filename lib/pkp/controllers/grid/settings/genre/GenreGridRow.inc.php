<?php

/**
 * @file controllers/grid/settings/genre/GenreGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GenreGridRow
 * @ingroup controllers_grid_settings_genre
 *
 * @brief Handle Genre grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class GenreGridRow extends GridRow {
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

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		if (!empty($rowId) && is_numeric($rowId)) {
			$router = $request->getRouter();
			$actionArgs = array(
				'gridId' => $this->getGridId(),
				'genreId' => $rowId
			);

			import('lib.pkp.classes.linkAction.request.AjaxModal');
			$this->addAction(
				new LinkAction(
					'editGenre',
					new AjaxModal(
						$router->url($request, null, null, 'editGenre', null, $actionArgs),
						__('grid.action.edit'),
						'modal_edit',
						true),
					__('grid.action.edit'),
					'edit')
			);

			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			$this->addAction(
				new LinkAction(
					'deleteGenre',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('common.confirmDelete'),
						__('grid.action.delete'),
						$router->url($request, null, null, 'deleteGenre', null, $actionArgs), 'modal_delete'),
					__('grid.action.delete'),
					'delete')
			);
		}
	}
}

?>
