<?php

/**
 * ChangePasswordForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user.form
 *
 * Form to change a user's password.
 *
 * $Id$
 */

class ChangePasswordForm extends Form {
	
	/**
	 * Constructor.
	 */
	function ChangePasswordForm() {
		parent::Form('user/changePassword.tpl');
		$user = &Request::getUser();
		$site = &Request::getSite();
		
		// Validation checks for this form
		$this->addCheck(new FormValidatorCustom(&$this, 'oldPassword', 'required', 'user.profile.form.oldPasswordInvalid', create_function('$password,$username', 'return Validation::checkCredentials($username,$password);'), array($user->getUsername())));
		$this->addCheck(new FormValidatorLength(&$this, 'password', 'required', 'user.register.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
		$this->addCheck(new FormValidator(&$this, 'password', 'required', 'user.profile.form.newPasswordRequired'));
		$this->addCheck(new FormValidatorCustom(&$this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$user = &Request::getUser();
		$templateMgr = &TemplateManager::getManager();
		$site = &Request::getSite();
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$templateMgr->assign('username', $user->getUsername());
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('oldPassword', 'password', 'password2'));
	}
	
	/**
	 * Save new password.
	 */
	function execute() {
		$user = &Request::getUser();
		$user->setPassword(Validation::encryptCredentials($user->getUsername(), $this->getData('password')));
		$userDao = &DAORegistry::getDAO('UserDAO');
		$userDao->updateUser($user);
	}
	
}

?>
