<?php

/**
 * @file controllers/grid/settings/user/form/UserRoleForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserRoleForm
 * @ingroup controllers_grid_settings_user_form
 *
 * @brief Form for managing roles for a newly created user.
 */

import('lib.pkp.controllers.grid.settings.user.form.UserForm');

class UserRoleForm extends UserForm {

	/* @var string User full name */
	var $_userFullName;

	/**
	 * Constructor.
	 * @param int $userId
	 * @param string $userFullName
	 */
	function __construct($userId, $userFullName) {
		parent::__construct('controllers/grid/settings/user/form/userRoleForm.tpl', $userId);

		$this->_userFullName = $userFullName;
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Display the form.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function display($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'userId' => $this->userId,
			'userFullName' => $this->_userFullName,
		));
		return $this->fetch($request);
	}

	/**
	 * Update user's roles.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function execute($args, $request) {
		parent::execute($request);

		// Role management handled by parent form, just return user.
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getById($this->userId);
	}
}

?>
