<?php

/**
 * RegistrationForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user.form
 *
 * Form for user registration.
 *
 * $Id$
 */

class RegistrationForm extends Form {
	
	/**
	 * Constructor.
	 */
	function RegistrationForm() {
		parent::Form('user/register.tpl');
		
		// Validation checks for this form
		$this->addCheck(new FormValidator(&$this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidatorCustom(&$this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'));
		$this->addCheck(new FormValidator(&$this, 'password', 'required', 'user.profile.form.passwordRequired'));
		$this->addCheck(new FormValidatorCustom(&$this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
		$this->addCheck(new FormValidator(&$this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator(&$this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
$this->addCheck(new FormValidatorEmail(&$this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom(&$this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'));
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {		
		$this->_data = array(
			'username' => Request::getUserVar('username'),
			'password' => Request::getUserVar('password'),
			'password2' => Request::getUserVar('password2'),
			'firstName' => Request::getUserVar('firstName'),
			'middleName' => Request::getUserVar('middleName'),
			'lastName' => Request::getUserVar('lastName'),
			'affiliation' => Request::getUserVar('affiliation'),
			'email' => Request::getUserVar('email'),
			'phone' => Request::getUserVar('phone'),
			'fax' => Request::getUserVar('fax'),
			'mailingAddress' => Request::getUserVar('mailingAddress'),
			'biography' => Request::getUserVar('biography')
		);
	}
	
	/**
	 * Register a new user.
	 */
	function execute() {
		$user = &new User();
		
		$user->setUsername($this->_data['username']);
		$user->setPassword(Validation::encryptPassword($this->_data['password']));
		$user->setFirstName($this->_data['firstName']);
		$user->setMiddleName($this->_data['middleName']);
		$user->setLastName($this->_data['lastName']);
		$user->setAffiliation($this->_data['affiliation']);
		$user->setEmail($this->_data['email']);
		$user->setPhone($this->_data['phone']);
		$user->setFax($this->_data['fax']);
		$user->setMailingAddress($this->_data['mailingAddress']);
		$user->setBiography($this->_data['biography']);
		$user->setDateRegistered(Core::getCurrentDate());
		
		$userDao = &DAORegistry::getDAO('UserDAO');
		$userDao->insertUser($user);
		
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		$session->setSessionVar('username', $user->getUsername());
	}
	
}

?>
