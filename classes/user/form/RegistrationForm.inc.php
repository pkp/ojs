<?php

/**
 * @file RegistrationForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user.form
 * @class RegistrationForm
 *
 * Form for user registration.
 *
 * $Id$
 */

import('form.Form');

class RegistrationForm extends Form {

	/** @var boolean user is already registered with another journal */
	var $existingUser;

	/** @var AuthPlugin default authentication source, if specified */
	var $defaultAuth;

	/** @var boolean whether or not captcha is enabled for this form */
	var $captchaEnabled;

	/**
	 * Constructor.
	 */
	function RegistrationForm() {
		parent::Form('user/register.tpl');

		$this->existingUser = Request::getUserVar('existingUser') ? 1 : 0;

		import('captcha.CaptchaManager');
		$captchaManager =& new CaptchaManager();
		$this->captchaEnabled = ($captchaManager->isEnabled() && Config::getVar('captcha', 'captcha_on_register'))?true:false;

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.passwordRequired'));

		if ($this->existingUser) {
			// Existing user -- check login
			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.login.loginError', create_function('$username,$form', 'return Validation::checkCredentials($form->getData(\'username\'), $form->getData(\'password\'));'), array(&$this)));
		} else {
			// New user -- check required profile fields
			$site = &Request::getSite();

			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(), true));
			$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));
			$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.register.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
			$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
			$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
			$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
			$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array(), true));
			if ($this->captchaEnabled) {
				$this->addCheck(new FormValidatorCaptcha($this, 'captcha', 'captchaId', 'common.captchaField.badCaptcha'));
			}

			$authDao = &DAORegistry::getDAO('AuthSourceDAO');
			$this->defaultAuth = &$authDao->getDefaultPlugin();
			if (isset($this->defaultAuth)) {
				$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', create_function('$username,$form,$auth', 'return (!$auth->userExists($username) || $auth->authenticate($username, $form->getData(\'password\')));'), array(&$this, $this->defaultAuth)));
			}
		}
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$site = &Request::getSite();
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$journal = &Request::getJournal();

		if ($this->captchaEnabled) {
			import('captcha.CaptchaManager');
			$captchaManager =& new CaptchaManager();
			$captcha =& $captchaManager->createCaptcha();
			if ($captcha) {
				$templateMgr->assign('captchaEnabled', $this->captchaEnabled);
				$this->setData('captchaId', $captcha->getCaptchaId());
			}
		}

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		$disciplineDao =& DAORegistry::getDAO('DisciplineDAO');
		$disciplines =& $disciplineDao->getDisciplines();
		$templateMgr->assign_by_ref('disciplines', $disciplines);

		$templateMgr->assign('privacyStatement', $journal->getLocalizedSetting('privacyStatement'));
		$templateMgr->assign('allowRegReader', $journal->getSetting('allowRegReader')==1?1:0);
		$templateMgr->assign('enableSubscriptions', $journal->getSetting('enableSubscriptions')==1?1:0);
		$templateMgr->assign('enableOpenAccessNotification', $journal->getSetting('enableOpenAccessNotification')==1?1:0);
		$templateMgr->assign('allowRegAuthor', $journal->getSetting('allowRegAuthor')==1?1:0);
		$templateMgr->assign('allowRegReviewer', $journal->getSetting('allowRegReviewer')==1?1:0);
		$templateMgr->assign('source', Request::getUserVar('source'));

		$site = &Request::getSite();
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());

		$templateMgr->assign('helpTopicId', 'user.registerAndProfile');		
		parent::display();
	}

	function getLocaleFieldNames() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getLocaleFieldNames();
	}

	/**
	 * Initialize default data.
	 */
	function initData() {
		$this->setData('registerAsReader', 1);
		$this->setData('existingUser', $this->existingUser);
		$this->setData('userLocales', array());
		$this->setData('sendPassword', 1);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$userVars = array(
			'username', 'password', 'password2',
			'salutation', 'firstName', 'middleName', 'lastName',
			'gender', 'initials', 'country', 'discipline',
			'affiliation', 'email', 'userUrl', 'phone', 'fax', 'signature',
			'mailingAddress', 'biography', 'interests', 'userLocales',
			'registerAsReader', 'openAccessNotification', 'registerAsAuthor',
			'registerAsReviewer', 'existingUser', 'sendPassword'
		);
		if ($this->captchaEnabled) {
			$userVars[] = 'captchaId';
			$userVars[] = 'captcha';
		}

		$this->readUserVars($userVars);

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
		$requireValidation = Config::getVar('email', 'require_validation');
		if ($this->existingUser) {
			// Existing user in the system
			$userDao = &DAORegistry::getDAO('UserDAO');
			$user = &$userDao->getUserByUsername($this->getData('username'));
			if ($user == null) {
				return false;
			}

			$userId = $user->getUserId();

		} else {
			// New user
			$user = &new User();

			$user->setUsername($this->getData('username'));
			$user->setSalutation($this->getData('salutation'));
			$user->setFirstName($this->getData('firstName'));
			$user->setMiddleName($this->getData('middleName'));
			$user->setInitials($this->getData('initials'));
			$user->setLastName($this->getData('lastName'));
			$user->setGender($this->getData('gender'));
			$user->setAffiliation($this->getData('affiliation'));
			$user->setSignature($this->getData('signature'), null); // Localized
			$user->setEmail($this->getData('email'));
			$user->setUrl($this->getData('userUrl'));
			$user->setPhone($this->getData('phone'));
			$user->setFax($this->getData('fax'));
			$user->setMailingAddress($this->getData('mailingAddress'));
			$user->setBiography($this->getData('biography'), null); // Localized
			$user->setInterests($this->getData('interests'), null); // Localized
			$user->setDateRegistered(Core::getCurrentDate());
			$user->setCountry($this->getData('country'));

			$site = &Request::getSite();
			$availableLocales = $site->getSupportedLocales();

			$locales = array();
			foreach ($this->getData('userLocales') as $locale) {
				if (Locale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
					array_push($locales, $locale);
				}
			}
			$user->setLocales($locales);

			if (isset($this->defaultAuth)) {
				$user->setPassword($this->getData('password'));
				// FIXME Check result and handle failures
				$this->defaultAuth->doCreateUser($user);
				$user->setAuthId($this->defaultAuth->authId);
			}
			$user->setPassword(Validation::encryptCredentials($this->getData('username'), $this->getData('password')));

			if ($requireValidation) {
				// The account should be created in a disabled
				// state.
				$user->setDisabled(true);
				$user->setDisabledReason(Locale::translate('user.login.accountNotValidated'));
			}

			$userDao = &DAORegistry::getDAO('UserDAO');
			$userDao->insertUser($user);
			$userId = $user->getUserId();
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
			unset($allowedRoles['reviewer']);
		}

		foreach ($allowedRoles as $k => $v) {
			$roleId = $roleDao->getRoleIdFromPath($k);
			if ($this->getData($v) && !$roleDao->roleExists($journal->getJournalId(), $userId, $roleId)) {
				$role = &new Role();
				$role->setJournalId($journal->getJournalId());
				$role->setUserId($userId);
				$role->setRoleId($roleId);
				$roleDao->insertRole($role);

			}
		}

		if (!$this->existingUser) {
			import('mail.MailTemplate');
			if ($requireValidation) {
				// Create an access key
				import('security.AccessKeyManager');
				$accessKeyManager =& new AccessKeyManager();
				$accessKey = $accessKeyManager->createKey('RegisterContext', $user->getUserId(), null, Config::getVar('email', 'validation_timeout'));

				// Send email validation request to user
				$mail =& new MailTemplate('USER_VALIDATE');
				$mail->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
				$mail->assignParams(array(
					'userFullName' => $user->getFullName(),
					'activateUrl' => Request::url($journal->getPath(), 'user', 'activateUser', array($this->getData('username'), $accessKey))
				));
				$mail->addRecipient($user->getEmail(), $user->getFullName());
				$mail->send();
				unset($mail);
			}
			if ($this->getData('sendPassword')) {
				// Send welcome email to user
				$mail = &new MailTemplate('USER_REGISTER');
				$mail->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
				$mail->assignParams(array(
					'username' => $this->getData('username'),
					'password' => $this->getData('password'),
					'userFullName' => $user->getFullName()
				));
				$mail->addRecipient($user->getEmail(), $user->getFullName());
				$mail->send();
				unset($mail);
			}
		}

		// By default, self-registering readers will receive
		// journal updates. (The double set is here to prevent a
		// duplicate insert error msg if there was a notification entry
		// left over from a previous role.)
		if (isset($allowedRoles['reader']) && $this->getData($allowedRoles['reader'])) {
			$notificationStatusDao = &DAORegistry::getDAO('NotificationStatusDAO');
			$notificationStatusDao->setJournalNotifications($journal->getJournalId(), $userId, false);
			$notificationStatusDao->setJournalNotifications($journal->getJournalId(), $userId, true);
		}

		if (isset($allowedRoles['reader']) && $this->getData('openAccessNotification')) {
			$userSettingsDao = &DAORegistry::getDAO('UserSettingsDAO');
			$userSettingsDao->updateSetting($userId, 'openAccessNotification', true, 'bool', $journal->getJournalId());
		}
	}

}

?>
