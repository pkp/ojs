<?php

/**
 * @file controllers/grid/LocaleGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LocaleGridRow
 * @ingroup controllers_grid_translator
 *
 * @brief Handle locale grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RedirectAction');

class LocaleGridRow extends GridRow {
	/** @var string JQuery selector for containing tab element */
	var $tabsSelector;

	/**
	 * Constructor
	 * @param $tabsSelector string Selector for containing tab element
	 */
	function __construct($tabsSelector) {
		parent::__construct();
		$this->tabsSelector = $tabsSelector;
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);
		$router = $request->getRouter();

		$actionArgs = array(
			'locale' => $this->getId(),
			'tabsSelector' => $this->tabsSelector,
		);

		// Create the "edit" action
		import('lib.pkp.classes.linkAction.request.AddTabAction');
		$this->addAction(
			new LinkAction(
				'edit',
				new AddTabAction(
					$this->tabsSelector,
					$router->url($request, null, null, 'edit', null, $actionArgs),
					__('plugins.generic.translator.locale', array('locale' => $this->getId()))
				),
				__('grid.action.edit'),
				'edit'
			)
		);

		// Create the "export" action
		$this->addAction(
			new LinkAction(
				'export',
				new RedirectAction(
					$router->url($request, null, null, 'export', null, array('locale' => $this->getId()))
				),
				__('common.export'),
				'zip'
			)
		);
	}
}

?>
