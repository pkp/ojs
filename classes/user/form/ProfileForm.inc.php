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
		$this->addCheck(new FormValidator(&$this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator(&$this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
$this->addCheck(new FormValidatorEmail(&$this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom(&$this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($user->getUserId()), true));
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

		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$journal = Request::getJournal();
		if (isset($journal)) $journals = array($journal);
		else $journals = &$journalDao->getJournals();

		$journals = &$journalDao->getJournals();
		$journalsToDisplay = array();
		
		// Get the reader role for this journal if it exists
		foreach ($journals as $thisJournal) {
			$role = &$roleDao->getRole($thisJournal->getJournalId(), $user->getUserId(), ROLE_ID_READER);
			if (!empty($role)) {
				$thisJournal->receivesUpdates = $role->getReceivesUpdates();
				$journalsToDisplay[] = $thisJournal;
			}
		}
		
		$templateMgr->assign('readerJournals', $journalsToDisplay);
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

		// Update each relevant role's email notification flag
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		if (isset($journal)) $journals = array($journal);
		else $journals = &$journalDao->getJournals();

		$journals = &$journalDao->getJournals();
		
		// Get the reader role for this journal if it exists
		foreach ($journals as $thisJournal) {
			$role = &$roleDao->getRole($thisJournal->getJournalId(), $user->getUserId(), ROLE_ID_READER);
			if (!empty($role)) {
				$readerNotify = Request::getUserVar('readerNotify');
				$currentlyReceives = $role->getReceivesUpdates();
				$shouldReceive = !empty($readerNotify) && in_array($thisJournal->getJournalId(), $readerNotify);
				if ($currentlyReceives != $shouldReceive) {
					$role->setReceivesUpdates($shouldReceive);
					$roleDao->updateRole($role);
				}
			}
		}
		
	}
	
}

?>
