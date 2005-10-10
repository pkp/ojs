<?php

/**
 * ProfileForm.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user.form
 *
 * Form to edit user profile.
 *
 * $Id$
 */

import('form.Form');

class ProfileForm extends Form {

	/** @var boolean Include a user's working languages in their profile */
	var $profileLocalesEnabled;
	
	/**
	 * Constructor.
	 */
	function ProfileForm() {
		parent::Form('user/profile.tpl');
		
		$user = &Request::getUser();
		
		$site = &Request::getSite();
		$this->profileLocalesEnabled = $site->getProfileLocalesEnabled();
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($user->getUserId(), true), true));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$user = &Request::getUser();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('username', $user->getUsername());
		$templateMgr->assign('profileLocalesEnabled', $this->profileLocalesEnabled);
		if ($this->profileLocalesEnabled) {
			$site = &Request::getSite();
			$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		}

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$notificationStatusDao = &DAORegistry::getDAO('NotificationStatusDAO');

		$journals = &$journalDao->getJournals();
		$journals = &$journals->toArray();
		$journalNotifications = &$notificationStatusDao->getJournalNotifications($user->getUserId());
		
		$templateMgr->assign_by_ref('journals', $journals);
		$templateMgr->assign_by_ref('journalNotifications', $journalNotifications);
		$templateMgr->assign('helpTopicId', 'user.registerAndProfile');		
		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$user = &Request::getUser();
		
		$this->_data = array(
			'firstName' => $user->getFirstName(),
			'middleName' => $user->getMiddleName(),
			'initials' => $user->getInitials(),
			'lastName' => $user->getLastName(),
			'affiliation' => $user->getAffiliation(),
			'email' => $user->getEmail(),
			'phone' => $user->getPhone(),
			'fax' => $user->getFax(),
			'mailingAddress' => $user->getMailingAddress(),
			'biography' => $user->getBiography(),
			'interests' => $user->getInterests(),
			'userLocales' => $user->getLocales()
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'firstName',
			'middleName',
			'lastName',
			'initials',
			'affiliation',
			'email',
			'phone',
			'fax',
			'mailingAddress',
			'biography',
			'interests',
			'userLocales'
		));
		
		if ($this->getData('userLocales') == null || !is_array($this->getData('userLocales'))) {
			$this->setData('userLocales', array());
		}
	}
	
	/**
	 * Save profile settings.
	 */
	function execute() {
		$user = &Request::getUser();

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
		$userDao->updateUser($user);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$notificationStatusDao = &DAORegistry::getDAO('NotificationStatusDAO');

		$journals = &$journalDao->getJournals();
		$journals = &$journals->toArray();
		$journalNotifications = $notificationStatusDao->getJournalNotifications($user->getUserId());

		$readerNotify = Request::getUserVar('journalNotify');

		foreach ($journals as $thisJournal) {
			$thisJournalId = $thisJournal->getJournalId();
			$currentlyReceives = !empty($journalNotifications[$thisJournalId]);
			$shouldReceive = !empty($readerNotify) && in_array($thisJournal->getJournalId(), $readerNotify);
			if ($currentlyReceives != $shouldReceive) {
				$notificationStatusDao->setJournalNotifications($thisJournalId, $user->getUserId(), $shouldReceive);
			}
		}
		
		if ($user->getAuthId()) {
			$authDao = &DAORegistry::getDAO('AuthSourceDAO');
			$auth = &$authDao->getPlugin($user->getAuthId());
		}
		
		if (isset($auth)) {
			$auth->doSetUserInfo($user);
		}
	}
	
}

?>
