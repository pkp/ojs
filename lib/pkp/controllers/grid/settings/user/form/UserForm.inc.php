<?php

/**
 * @file controllers/grid/settings/user/form/UserForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserForm
 * @ingroup controllers_grid_settings_user_form
 *
 * @brief Base class for user forms.
 */

import('lib.pkp.classes.form.Form');

class UserForm extends Form {

	/** @var Id of the user being edited */
	var $userId;

	/**
	 * Constructor.
	 * @param $request PKPRequest
	 * @param $userId int optional
	 * @param $author Author optional
	 */
	function __construct($template, $userId = null) {
		parent::__construct($template);

		$this->userId = isset($userId) ? (int) $userId : null;

		if (!is_null($userId)) {
			$this->addCheck(new FormValidatorListbuilder($this, 'roles', 'manager.users.roleRequired'));
		}
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('roles'));
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute($request) {
		ListbuilderHandler::unpack($request, $this->getData('roles'), array($this, 'deleteEntry'), array($this, 'insertEntry'), array($this, 'updateEntry'));
	}

	/**
	 * @copydoc Listbuilder::insertentry()
	 */
	function insertEntry($request, $newRowId) {
		$context = $request->getContext();
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

		$userGroupId = (int) $newRowId['name'];
		$userId = (int) $this->userId;

		// Ensure that:
		// $userGroupId is not empty
		// $userGroupId is valid for this context.
		// user group assignment does not already exist
		if (
			empty($userGroupId) ||
			!$userGroupDao->contextHasGroup($context->getId(), $userGroupId) ||
			$userGroupDao->userInGroup($userId, $userGroupId)
		) {
			return false;
		} else {
			// Add the assignment
			$userGroupDao->assignUserToGroup($userId, $userGroupId);
		}

		return true;
	}

	/**
	 * @copydoc Listbuilder::deleteEntry()
	 */
	function deleteEntry($request, $rowId) {
		$userGroupId = (int) $rowId;
		$userId = (int) $this->userId;

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$context = $request->getContext();

		$userGroupDao->removeUserFromGroup(
			$userId,
			(int) $userGroupId,
			$context->getId()
		);

		return true;
	}

}

?>
