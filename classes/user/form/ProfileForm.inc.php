<?php

/**
 * ProfileForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user.form
 *
 * Form to edit user profile.
 *
 * $Id$
 */

class ProfileForm extends Form {
	
	/**
	 * Constructor.
	 */
	function ProfileForm() {
		parent::Form('user/profile.tpl');
		
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		$user = &$session->getUser();
		
		// Validation checks for this form
		$this->addCheck(new FormValidatorCustom(&$this, 'password', 'optional', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
		$this->addCheck(new FormValidator(&$this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator(&$this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
$this->addCheck(new FormValidatorEmail(&$this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom(&$this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($user->getUserId()), true));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		$user = &$session->getUser();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('username', $user->getUsername());
		
		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		$user = &$session->getUser();
		
		$this->_data = array(
			'firstName' => $user->getFirstName(),
			'middleName' => $user->getMiddleName(),
			'lastName' => $user->getLastName(),
			'affiliation' => $user->getAffiliation(),
			'email' => $user->getEmail(),
			'phone' => $user->getPhone(),
			'fax' => $user->getFax(),
			'mailingAddress' => $user->getMailingAddress(),
			'biography' => $user->getBiography()
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {		
		$this->_data = array(
			'firstName' => Request::getUserVar('firstName'),
			'password' => Request::getUserVar('password'),
			'password2' => Request::getUserVar('password2'),
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
	 * Save profile settings.
	 */
	function execute() {
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		$user = &$session->getUser();
		
		if ($this->_data['password'] !== '') {
			$user->setPassword(Validation::encryptPassword($this->_data['password']));
		}
		
		$user->setFirstName($this->_data['firstName']);
		$user->setMiddleName($this->_data['middleName']);
		$user->setLastName($this->_data['lastName']);
		$user->setAffiliation($this->_data['affiliation']);
		$user->setEmail($this->_data['email']);
		$user->setPhone($this->_data['phone']);
		$user->setFax($this->_data['fax']);
		$user->setMailingAddress($this->_data['mailingAddress']);
		$user->setBiography($this->_data['biography']);
		
		$userDao = &DAORegistry::getDAO('UserDAO');
		$userDao->updateUser($user);
	}
	
}

?>
