<?php

/**
 * CreateReviewerForm.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package sectionEditor.form
 *
 * Form for section editors to create reviewers.
 *
 * $Id$
 */

import('form.Form');

class CreateReviewerForm extends Form {
	/** @var int The article this form is for */
	var $articleId;

	/** @var boolean Include a user's working languages in their profile */
	var $profileLocalesEnabled;
	
	/**
	 * Constructor.
	 */
	function CreateReviewerForm($articleId) {
		parent::Form('sectionEditor/createReviewerForm.tpl');
		
		$site = &Request::getSite();
		$this->profileLocalesEnabled = $site->getProfileLocalesEnabled();
		$this->articleId = $articleId;
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(null, true), true));
		$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));
		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array(null, true), true));

		// Provide a default for sendNotify: If we're using one-click
		// reviewer access or email-based reviews, it's not necessary;
		// otherwise, it should default to on.
		$journal =& Request::getJournal();
		$reviewerAccessKeysEnabled = $journal->getSetting('reviewerAccessKeysEnabled');
		$isEmailBasedReview = $journal->getSetting('mailSubmissionsToReviewers')==1?true:false;
		$this->setData('sendNotify', ($reviewerAccessKeysEnabled || $isEmailBasedReview)?false:true);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$site = &Request::getSite();
		$templateMgr->assign('articleId', $this->articleId);
		$templateMgr->assign('profileLocalesEnabled', $this->profileLocalesEnabled);
		if ($this->profileLocalesEnabled) {
			$site = &Request::getSite();
			$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		}

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('firstName', 'middleName', 'lastName', 'initials', 'affiliation', 'email', 'userUrl', 'phone', 'fax', 'mailingAddress', 'country', 'biography', 'interests', 'userLocales', 'sendNotify', 'username'));

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
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &new User();
		
		$user->setFirstName($this->getData('firstName'));
		$user->setMiddleName($this->getData('middleName'));
		$user->setLastName($this->getData('lastName'));
		$user->setInitials($this->getData('initials'));
		$user->setAffiliation($this->getData('affiliation'));
		$user->setEmail($this->getData('email'));
		$user->setUrl($this->getData('userUrl'));
		$user->setPhone($this->getData('phone'));
		$user->setFax($this->getData('fax'));
		$user->setMailingAddress($this->getData('mailingAddress'));
		$user->setCountry($this->getData('country'));
		$user->setBiography($this->getData('biography'));
		$user->setInterests($this->getData('interests'));
		$user->setMustChangePassword($this->getData('mustChangePassword') ? 1 : 0);

		$authDao = &DAORegistry::getDAO('AuthSourceDAO');
		$auth =& $authDao->getDefaultPlugin();
		$user->setAuthId($auth?$auth->getAuthId():0);
		
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
		
		$user->setUsername($this->getData('username'));
		$password = Validation::generatePassword();
		$sendNotify = $this->getData('sendNotify');
		
		if (isset($auth)) {
			$user->setPassword($password);
			// FIXME Check result and handle failures
			$auth->doCreateUser($user);
			$user->setAuthId($auth->authId);
			$user->setPassword(Validation::encryptCredentials($user->getUserId(), Validation::generatePassword())); // Used for PW reset hash only
		} else {
			$user->setPassword(Validation::encryptCredentials($this->getData('username'), $password));
		}

		$user->setDateRegistered(Core::getCurrentDate());
		$userId = $userDao->insertUser($user);
			
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$journal = &Request::getJournal();
		$role = &new Role();
		$role->setJournalId($journal->getJournalId());
		$role->setUserId($userId);
		$role->setRoleId(ROLE_ID_REVIEWER);
		$roleDao->insertRole($role);
		
		if ($sendNotify) {
			// Send welcome email to user
			import('mail.MailTemplate');
			$mail = &new MailTemplate('USER_REGISTER');
			$mail->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
			$mail->assignParams(array('username' => $this->getData('username'), 'password' => $password));
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
		}
	}
	
}

?>
