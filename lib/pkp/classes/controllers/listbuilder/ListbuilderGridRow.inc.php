<?php

/**
 * @file classes/controllers/listbuilder/ListbuilderGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ListbuilderGridRow
 * @ingroup controllers_listbuilder
 *
 * @brief Handle list builder row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class ListbuilderGridRow extends GridRow {

	/* @var boolean */
	var $_hasDeleteItemLink;

	/**
	 * Constructor
	 * @param $hasDeleteItemLink boolean
	 */
	function __construct($hasDeleteItemLink = true) {
		parent::__construct();

		$this->setHasDeleteItemLink($hasDeleteItemLink);
	}

	/**
	 * Add a delete item link action or not.
	 * @param $hasDeleteItemLink boolean
	 */
	function setHasDeleteItemLink($hasDeleteItemLink) {
		$this->_hasDeleteItemLink = $hasDeleteItemLink;
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = 'controllers/listbuilder/listbuilderGridRow.tpl') {
		parent::initialize($request);

		// Set listbuilder row template
		$this->setTemplate($template);

		if ($this->_hasDeleteItemLink) {
			// Add deletion action (handled in JS-land)
			import('lib.pkp.classes.linkAction.request.NullAction');
			$this->addAction(
				new LinkAction(
					'delete',
					new NullAction(),
					'',
					'remove_item'
				)
			);
		}
	}

	/**
	 * @see GridRow::addAction()
	 */
	function addAction($action, $position = GRID_ACTION_POSITION_ROW_LEFT) {
		return parent::addAction($action, $position);
	}
}

?>
