<?php

/**
 * @file classes/user/form/LoginChangePasswordForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LoginChangePasswordForm
 * @ingroup user_form
 *
 * @brief Form to change a user's password in order to login.
 */

// $Id$


import('form.Form');

class LoginChangePasswordForm extends Form {

	/**
	 * Constructor.
	 */
	function LoginChangePasswordForm() {
		parent::Form('user/loginChangePassword.tpl');
		$site = &Request::getSite();

		// Validation checks for this form
		$this->addCheck(new FormValidatorCustom($this, 'oldPassword', 'required', 'user.profile.form.oldPasswordInvalid', create_function('$password,$form', 'return Validation::checkCredentials($form->getData(\'username\'),$password);'), array(&$this)));
		$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.register.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
		$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.newPasswordRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$site = &Request::getSite();
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('username', 'oldPassword', 'password', 'password2'));
	}

	/**
	 * Save new password.
	 * @return boolean success
	 */
	function execute() {
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &$userDao->getUserByUsername($this->getData('username'), false);
		if ($user != null) {
			if ($user->getAuthId()) {
				$authDao = &DAORegistry::getDAO('AuthSourceDAO');
				$auth = &$authDao->getPlugin($user->getAuthId());
			}

			if (isset($auth)) {
				$auth->doSetUserPassword($user->getUsername(), $this->getData('password'));
				$user->setPassword(Validation::encryptCredentials($user->getUserId(), Validation::generatePassword())); // Used for PW reset hash only
			} else {
				$user->setPassword(Validation::encryptCredentials($user->getUsername(), $this->getData('password')));
			}

			$user->setMustChangePassword(0);
			$userDao->updateUser($user);
			return true;

		} else {
			return false;
		}
	}

}

?>
