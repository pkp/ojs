<?php

/**
 * @file controllers/grid/settings/user/form/UserDisableForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserDisableForm
 * @ingroup controllers_grid_settings_user_form
 *
 * @brief Form for enabling/disabling a user
 */

import('lib.pkp.classes.form.Form');

class UserDisableForm extends Form {

	/* @var the user id of user to enable/disable */
	var $_userId;

	/* @var whether to enable or disable the user */
	var $_enable;

	/**
	 * Constructor.
	 */
	function __construct($userId, $enable = false) {
		parent::__construct('controllers/grid/settings/user/form/userDisableForm.tpl');

		$this->_userId = (int) $userId;
		$this->_enable = (bool) $enable;

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData($args, $request) {
		if ($this->_userId) {
			$userDao = DAORegistry::getDAO('UserDAO');
			$user = $userDao->getById($this->_userId);

			if ($user) {
				$this->_data = array(
					'disableReason' => $user->getDisabledReason()
				);
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'disableReason',
			)
		);

	}

	/**
	 * Display the form.
	 */
	function display($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'userId' => $this->_userId,
			'enable' => $this->_enable,
		));
		return $this->fetch($request);
	}

	/**
	 * Enable/Disable the user
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function execute($args, $request) {
		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->getById($this->_userId);

		if ($user) {
			$user->setDisabled($this->_enable ? false : true);
			$user->setDisabledReason($this->getData('disableReason'));
			$userDao->updateObject($user);
		}

		return $user;
	}
}

?>
