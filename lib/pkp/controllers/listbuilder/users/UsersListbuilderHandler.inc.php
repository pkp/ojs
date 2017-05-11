<?php

/**
 * @file controllers/listbuilder/users/UsersListbuilderHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsersListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Abstract listbuilder for implementing a listbuilder for a set of users.
 */

import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');

abstract class UsersListbuilderHandler extends ListbuilderHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Implement protected template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		// Load submission-specific translations
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

		import('lib.pkp.classes.linkAction.request.NullAction');
		$this->addAction(
			new LinkAction(
				'addItem',
				new NullAction(),
				__('grid.action.addUser'),
				'add_user'
			)
		);

		// Basic configuration.
		$this->setTitle('editor.submission.stageParticipants');
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_SELECT);
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
		$this->setSaveFieldName('users');

		// Name column
		$nameColumn = new ListbuilderGridColumn($this, 'name', 'common.name');
		import('lib.pkp.controllers.listbuilder.users.UserListbuilderGridCellProvider');
		$cellProvider = new UserListbuilderGridCellProvider();
		$nameColumn->setCellProvider($cellProvider);
		$this->addColumn($nameColumn);
	}

	//
	// Implement methods from ListbuilderHandler
	//
	/**
	 * @copydoc GridHandler::getRowDataElement
	 */
	protected function getRowDataElement($request, &$rowId) {
		// fallback on the parent if a rowId is found
		if ( !empty($rowId) ) {
			return parent::getRowDataElement($request, $rowId);
		}

		// Otherwise return from the newRowId
		// FIXME: Validate user ID?
		$newRowId = $this->getNewRowId($request);
		$userId = (int) $newRowId['name'];
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getById($userId);
	}
}

?>
