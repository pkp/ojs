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

	/** @var boolean Include a user's working languages in their profile */
	var $profileLocalesEnabled;
	
	/**
	 * Constructor.
	 */
	function UserManagementForm($userId = null) {
		parent::Form('manager/people/userProfileForm.tpl');
		
		$this->userId = isset($userId) ? (int) $userId : null;
		$site = &Request::getSite();
		$this->profileLocalesEnabled = $site->getProfileLocalesEnabled();
		
		// Validation checks for this form
		if ($userId == null) {
			$this->addCheck(new FormValidator(&$this, 'username', 'required', 'user.profile.form.usernameRequired'));
			$this->addCheck(new FormValidatorCustom(&$this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array($this->userId), true));
			$this->addCheck(new FormValidator(&$this, 'password', 'required', 'user.profile.form.passwordRequired'));
			$this->addCheck(new FormValidatorLength(&$this, 'password', 'required', 'user.register.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
			$this->addCheck(new FormValidatorCustom(&$this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
		} else {
			$this->addCheck(new FormValidatorLength(&$this, 'password', 'optional', 'user.register.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
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
		$site = &Request::getSite();
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$templateMgr->assign('userId', $this->userId);
		if (isset($this->userId)) {
			$userDao = &DAORegistry::getDAO('UserDAO');
			$user = &$userDao->getUser($this->userId);
			$templateMgr->assign('username', $user->getUsername());
		}
		$templateMgr->assign('roleOptions',
			array(
				'' => 'manager.people.doNotEnroll',
				'manager' => 'user.role.manager',
				'editor' => 'user.role.editor',
				'sectionEditor' => 'user.role.sectionEditor',
				'layoutEditor' => 'user.role.layoutEditor',
				'reviewer' => 'user.role.reviewer',
				'copyeditor' => 'user.role.copyeditor',
				'proofreader' => 'user.role.proofreader',
				'author' => 'user.role.author',
				'reader' => 'user.role.reader'
			)
		);
		$templateMgr->assign('profileLocalesEnabled', $this->profileLocalesEnabled);
		if ($this->profileLocalesEnabled) {
			$site = &Request::getSite();
			$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		}
		
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
					'firstName' => $user->getFirstName(),
					'middleName' => $user->getMiddleName(),
					'lastName' => $user->getLastName(),
					'affiliation' => $user->getAffiliation(),
					'email' => $user->getEmail(),
					'phone' => $user->getPhone(),
					'fax' => $user->getFax(),
					'mailingAddress' => $user->getMailingAddress(),
					'biography' => $user->getBiography(),
					'userLocales' => $user->getLocales()
				);

			} else {
				$this->userId = null;
			}
		}
		if (!isset($this->userId)) {
			$this->_data = array(
				'enrollAs' => array('')
			);
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('enrollAs', 'password', 'password2', 'firstName', 'middleName', 'lastName', 'affiliation', 'email', 'phone', 'fax', 'mailingAddress', 'biography', 'userLocales', 'sendNotify', 'mustChangePassword'));
		if ($this->userId == null) {
			$this->readUserVars(array('username'));
		}
		
		if ($this->getData('userLocales') == null || !is_array($this->getData('userLocales'))) {
			$this->setData('userLocales', array());
		}
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
		
		$user->setFirstName($this->getData('firstName'));
		$user->setMiddleName($this->getData('middleName'));
		$user->setLastName($this->getData('lastName'));
		$user->setAffiliation($this->getData('affiliation'));
		$user->setEmail($this->getData('email'));
		$user->setPhone($this->getData('phone'));
		$user->setFax($this->getData('fax'));
		$user->setMailingAddress($this->getData('mailingAddress'));
		$user->setBiography($this->getData('biography'));
		$user->setMustChangePassword($this->getData('mustChangePassword') ? 1 : 0);
		
		if ($this->profileLocalesEnabled) {
			$site = &Request::getSite();
			$availableLocales = $site->getSupportedLocales();
			
			$locales = array();
			foreach ($this->getData('userLocales') as $locale) {
				if (Locale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
					array_push($locales, $locale);
				}
			}
			$user->setLocales($locales);
		}
		
		if ($user->getUserId() != null) {
			if ($this->getData('password') !== '') {
				$user->setPassword(Validation::encryptCredentials($this->getData('username'), $this->getData('password')));
			}
			$userDao->updateUser($user);
		
		} else {
			$user->setUsername($this->getData('username'));
			$user->setPassword(Validation::encryptCredentials($this->getData('username'), $this->getData('password')));
			$user->setDateRegistered(Core::getCurrentDate());
			$userDao->insertUser($user);
			$userId = $userDao->getInsertUserId();
			
			if (!empty($this->_data['enrollAs'])) {
				foreach ($this->getData('enrollAs') as $roleName) {
					// Enroll new user into an initial role
					$roleDao = &DAORegistry::getDAO('RoleDAO');
					$roleId = $roleDao->getRoleIdFromPath($roleName);
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
		
			if ($this->getData('sendNotify')) {
				// Send welcome email to user
				$mail = &new MailTemplate('NEW_USER_REGISTRATION');
				$mail->assignParams(array('username' => $this->getData('username'), 'password' => $this->getData('password')));
				$mail->addRecipient($user->getEmail(), $user->getFullName());
				$mail->send();
			}
		}
	}
	
}

?>
