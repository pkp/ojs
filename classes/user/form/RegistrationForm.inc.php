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

	/** @var boolean user is already registered with another journal */
	var $existingUser;

	/** @var boolean Include a user's working languages in their profile */
	var $profileLocalesEnabled;
	
	/**
	 * Constructor.
	 */
	function RegistrationForm() {
		parent::Form('user/register.tpl');
		
		$this->existingUser = Request::getUserVar('existingUser') ? 1 : 0;
		
		// Validation checks for this form
		$this->addCheck(new FormValidator(&$this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidator(&$this, 'password', 'required', 'user.profile.form.passwordRequired'));

		if ($this->existingUser) {
			// Existing user -- check login
			$this->addCheck(new FormValidatorCustom(&$this, 'username', 'required', 'user.login.loginError', create_function('$username,$form', '$userDao = &DAORegistry::getDao(\'UserDAO\'); $user = &$userDao->getUserByCredentials($username, Validation::encryptCredentials($form->getData(\'username\'), $form->getData(\'password\'))); return isset($user);'), array(&$this)));

		} else {
			// New user -- check required profile fields
			$site = &Request::getSite();
			$this->profileLocalesEnabled = $site->getProfileLocalesEnabled();
			
			$this->addCheck(new FormValidatorCustom(&$this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(), true));
			$this->addCheck(new FormValidatorAlphaNum(&$this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));
			$this->addCheck(new FormValidatorLength(&$this, 'password', 'required', 'user.register.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
			$this->addCheck(new FormValidatorCustom(&$this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
			$this->addCheck(new FormValidator(&$this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
			$this->addCheck(new FormValidator(&$this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
	$this->addCheck(new FormValidatorEmail(&$this, 'email', 'required', 'user.profile.form.emailRequired'));
			$this->addCheck(new FormValidatorCustom(&$this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array(), true));
		}
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$site = &Request::getSite();
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$journal = &Request::getJournal();
		$templateMgr->assign('privacyStatement', $journal->getSetting('privacyStatement'));
		$templateMgr->assign('allowRegReader', $journal->getSetting('allowRegReader')==1?1:0);
		$templateMgr->assign('allowRegAuthor', $journal->getSetting('allowRegAuthor')==1?1:0);
		$templateMgr->assign('allowRegReviewer', $journal->getSetting('allowRegReviewer')==1?1:0);
		$templateMgr->assign('profileLocalesEnabled', $this->profileLocalesEnabled);
		if ($this->profileLocalesEnabled) {
			$site = &Request::getSite();
			$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		}
		$templateMgr->assign('helpTopicId', 'user.registerAndProfile');		
		parent::display();
	}
	
	/**
	 * Initialize default data.
	 */
	function initData() {
		$this->setData('registerAsReader', 1);
		$this->setData('existingUser', $this->existingUser);
		$this->setData('userLocales', array());
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'username', 'password', 'password2',
				'firstName', 'middleName', 'lastName', 'initials',
				'affiliation', 'email', 'phone', 'fax',
				'mailingAddress', 'biography', 'interests', 'userLocales',
				'registerAsReader', 'registerAsAuthor', 'registerAsReviewer',
				'existingUser'
			)
		);
		
		if ($this->getData('userLocales') == null || !is_array($this->getData('userLocales'))) {
			$this->setData('userLocales', array());
		}
		
		if ($this->getData('username') != null) {
			// Usernames must be lowercase
			$this->setData('username', strtolower($this->getData('username')));
		}
	}
	
	/**
	 * Register a new user.
	 */
	function execute() {
		if ($this->existingUser) {
			// Existing user in the system
			$userDao = &DAORegistry::getDAO('UserDAO');
			$user = &$userDao->getUserByCredentials($this->getData('username'), Validation::encryptCredentials($this->getData('username'), $this->getData('password')));
			if ($user == null) {
				return false;
			}
			
			$userId = $user->getUserId();
			
		} else {
			// New user
			$user = &new User();
			
			$user->setUsername($this->getData('username'));
			$user->setPassword(Validation::encryptCredentials($this->getData('username'), $this->getData('password')));
			$user->setFirstName($this->getData('firstName'));
			$user->setMiddleName($this->getData('middleName'));
			$user->setInitials($this->getData('initials'));
			$user->setLastName($this->getData('lastName'));
			$user->setAffiliation($this->getData('affiliation'));
			$user->setEmail($this->getData('email'));
			$user->setPhone($this->getData('phone'));
			$user->setFax($this->getData('fax'));
			$user->setMailingAddress($this->getData('mailingAddress'));
			$user->setBiography($this->getData('biography'));
			$user->setInterests($this->getData('interests'));
			$user->setDateRegistered(Core::getCurrentDate());
		
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
			
			$userDao = &DAORegistry::getDAO('UserDAO');
			$userDao->insertUser($user);
			$userId = $userDao->getInsertUserId();
			if (!$userId) {
				return false;
			}
			
			$sessionManager = &SessionManager::getManager();
			$session = &$sessionManager->getUserSession();
			$session->setSessionVar('username', $user->getUsername());
		}

		$journal = &Request::getJournal();
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		
		// Roles users are allowed to register themselves in
		$allowedRoles = array('reader' => 'registerAsReader', 'author' => 'registerAsAuthor', 'reviewer' => 'registerAsReviewer');

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		if (!$journalSettingsDao->getSetting($journal->getJournalId(), 'allowRegReader')) {
			unset($allowedRoles['reader']);
		}
		if (!$journalSettingsDao->getSetting($journal->getJournalId(), 'allowRegAuthor')) {
			unset($allowedRoles['author']);
		}
		if (!$journalSettingsDao->getSetting($journal->getJournalId(), 'allowRegReviewer')) {
			unset($allowedRoles['reader']);
		}
		
		foreach ($allowedRoles as $k => $v) {
			$roleId = $roleDao->getRoleIdFromPath($k);
			if ($this->getData($v) && !$roleDao->roleExists($journal->getJournalId(), $userId, $roleId)) {
				$role = new Role();
				$role->setJournalId($journal->getJournalId());
				$role->setUserId($userId);
				$role->setRoleId($roleId);
				$roleDao->insertRole($role);
			}
		}
		
		if (!$this->existingUser) {
			// Send welcome email to user
			$mail = &new MailTemplate('USER_REGISTER');
			$mail->assignParams(array('username' => $this->getData('username'), 'password' => $this->getData('password')));
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
		}
	}
	
}

?>
