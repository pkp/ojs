<?php

/**
 * UserManagementForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for journal managers to edit user profiles.
 *
 * $Id$
 */

class UserManagementForm extends Form {

	/** The ID of the user being edited */
	var $userId;
	
	/**
	 * Constructor.
	 */
	function UserManagementForm($userId = null) {
		parent::Form('manager/people/userProfileForm.tpl');
		
		$this->userId = $userId;
		
		// Validation checks for this form
		$this->addCheck(new FormValidator(&$this, 'username', 'required', 'user.profile.form.usernameRequired'));
		if($userId == null) {
			$this->addCheck(new FormValidator(&$this, 'password', 'required', 'user.profile.form.passwordRequired'));
		}
		$this->addCheck(new FormValidator(&$this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator(&$this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
$this->addCheck(new FormValidatorEmail(&$this, 'email', 'required', 'user.profile.form.emailRequired'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('userId', $this->userId);
		
		parent::display();
	}
	
	/**
	 * Initialize form data from current user profile.
	 */
	function initData() {
		if (isset($this->userId)) {
			$userDao = &DAORegistry::getDAO('UserDAO');
			$user = &$userDao->getUser($this->userId);
			
			if ($user != null) {
				$this->_data = array(
					'username' => $user->getUsername(),
					'firstName' => $user->getUsername(),
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
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {		
		$this->_data = array(
			'username' => Request::getUserVar('username'),
			'password' => Request::getUserVar('password'),
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
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		if (isset($this->userId)) {
			$user = &$userDao->getUser($this->userId);
		}
		
		if (!isset($user)) {
			$user = &new User();
		}
		
		$user->setUsername($this->_data['username']);
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
		
		if ($user->getUserId() != null) {
			if($this->_data['password'] != '') {
				$user->setPassword(Validation::encryptPassword($this->_data['password']));
			}
			$userDao->updateUser($user);
		
		} else {
			$user->setPassword(Validation::encryptPassword($this->_data['password']));
			$userDao->insertUser($user);
		}
	}
	
}

?>
