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
		
		$this->userId = isset($userId) ? (int) $userId : null;
		
		// Validation checks for this form
		$this->addCheck(new FormValidator(&$this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidatorCustom(&$this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array($this->userId), true));
		if ($userId == null) {
			$this->addCheck(new FormValidator(&$this, 'password', 'required', 'user.profile.form.passwordRequired'));
			$this->addCheck(new FormValidatorCustom(&$this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
		} else {
			$this->addCheck(new FormValidatorCustom(&$this, 'password', 'optional', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
		}
		$this->addCheck(new FormValidator(&$this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator(&$this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorEmail(&$this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom(&$this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($this->userId), true));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('userId', $this->userId);
		$templateMgr->assign('roleOptions',
			array(
				'' => 'manager.people.doNotEnroll',
				'manager' => 'user.role.manager',
				'editor' => 'user.role.editor',
				'sectionEditor' => 'user.role.sectionEditor',
				'layoutEditor' => 'user.role.layoutEditor',
				'copyeditor' => 'user.role.copyeditor',
				'proofreader' => 'user.role.proofreader',
				'author' => 'user.role.author',
				'reader' => 'user.role.reader'
			)
		);
		
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

			} else {
				$this->userId = null;
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {		
		$this->_data = array(
			'enrollAs' => Request::getUserVar('enrollAs'),
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
			if ($this->_data['password'] !== '') {
				$user->setPassword(Validation::encryptCredentials($this->_data['username'], $this->_data['password']));
			}
			$userDao->updateUser($user);
		
		} else {
			$user->setPassword(Validation::encryptCredentials($this->_data['username'], $this->_data['password']));
			$userDao->insertUser($user);
			$userId = $userDao->getInsertUserId();
			
			if (!empty($this->_data['enrollAs'])) {
				// Enroll new user into an initial role
				$roleDao = &DAORegistry::getDAO('RoleDAO');
				$roleId = $roleDao->getRoleIdFromPath($this->_data['enrollAs']);
				if ($roleId != null) {
					$journal = &Request::getJournal();
					$role = &new Role();
					$role->setJournalId($journal->getJournalId());
					$role->setUserId($userId);
					$role->setRoleId($roleId);
					$roleDao->insertRole($role);
				}
			}
		}
	}
	
}

?>
