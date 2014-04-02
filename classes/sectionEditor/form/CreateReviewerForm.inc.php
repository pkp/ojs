<?php

/**
 * @defgroup sectionEditor_form
 */

/**
 * @file classes/sectionEditor/form/CreateReviewerForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CreateReviewerForm
 * @ingroup sectionEditor_form
 *
 * @brief Form for section editors to create reviewers.
 */

import('lib.pkp.classes.form.Form');

class CreateReviewerForm extends Form {
	/** @var int The article this form is for */
	var $articleId;

	/**
	 * Constructor.
	 */
	function CreateReviewerForm($articleId) {
		parent::Form('sectionEditor/createReviewerForm.tpl');
		$this->addCheck(new FormValidatorPost($this));

		$site =& Request::getSite();
		$this->articleId = $articleId;

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(null, true), true));
		$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));
		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
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

	function getLocaleFieldNames() {
		return array('biography', 'gossip');
	}

	/**
	 * Display the form.
	 */
	function display(&$args, &$request) {
		$templateMgr =& TemplateManager::getManager();
		$site =& Request::getSite();
		$templateMgr->assign('articleId', $this->articleId);

		$site =& Request::getSite();
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		$userDao =& DAORegistry::getDAO('UserDAO');
		$templateMgr->assign('genderOptions', $userDao->getGenderOptions());

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'salutation',
			'firstName',
			'middleName',
			'lastName',
			'gender',
			'initials',
			'affiliation',
			'email',
			'orcid',
			'userUrl',
			'phone',
			'fax',
			'mailingAddress',
			'country',
			'biography',
			'interestsTextOnly',
			'keywords',
			'gossip',
			'userLocales',
			'sendNotify',
			'username'
		));

		if ($this->getData('userLocales') == null || !is_array($this->getData('userLocales'))) {
			$this->setData('userLocales', array());
		}

		if ($this->getData('username') != null) {
			// Usernames must be lowercase
			$this->setData('username', strtolower($this->getData('username')));
		}

		$keywords = $this->getData('keywords');
		if ($keywords != null && is_array($keywords['interests'])) {
			// The interests are coming in encoded -- Decode them for DB storage
			$this->setData('interestsKeywords', array_map('urldecode', $keywords['interests']));
		}
	}

	/**
	 * Register a new user.
	 * @return userId int
	 */
	function execute() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user = new User();

		$user->setSalutation($this->getData('salutation'));
		$user->setFirstName($this->getData('firstName'));
		$user->setMiddleName($this->getData('middleName'));
		$user->setLastName($this->getData('lastName'));
		$user->setGender($this->getData('gender'));
		$user->setInitials($this->getData('initials'));
		$user->setAffiliation($this->getData('affiliation'), null); // Localized
		$user->setEmail($this->getData('email'));
		$user->setData('orcid', $this->getData('orcid'));
		$user->setUrl($this->getData('userUrl'));
		$user->setPhone($this->getData('phone'));
		$user->setFax($this->getData('fax'));
		$user->setMailingAddress($this->getData('mailingAddress'));
		$user->setCountry($this->getData('country'));
		$user->setBiography($this->getData('biography'), null); // Localized
		$user->setGossip($this->getData('gossip'), null); // Localized
		$user->setMustChangePassword($this->getData('mustChangePassword') ? 1 : 0);

		$authDao =& DAORegistry::getDAO('AuthSourceDAO');
		$auth =& $authDao->getDefaultPlugin();
		$user->setAuthId($auth?$auth->getAuthId():0);

		$site =& Request::getSite();
		$availableLocales = $site->getSupportedLocales();

		$locales = array();
		foreach ($this->getData('userLocales') as $locale) {
			if (AppLocale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
				array_push($locales, $locale);
			}
		}
		$user->setLocales($locales);

		$user->setUsername($this->getData('username'));
		$password = Validation::generatePassword();
		$sendNotify = $this->getData('sendNotify');

		if (isset($auth)) {
			$user->setPassword($password);
			// FIXME Check result and handle failures
			$auth->doCreateUser($user);
			$user->setAuthId($auth->authId);
			$user->setPassword(Validation::encryptCredentials($user->getId(), Validation::generatePassword())); // Used for PW reset hash only
		} else {
			$user->setPassword(Validation::encryptCredentials($this->getData('username'), $password));
		}

		$user->setDateRegistered(Core::getCurrentDate());
		$userId = $userDao->insertUser($user);

		// Insert the user interests
		$interests = $this->getData('interestsKeywords') ? $this->getData('interestsKeywords') : $this->getData('interestsTextOnly');
		import('lib.pkp.classes.user.InterestManager');
		$interestManager = new InterestManager();
		$interestManager->setInterestsForUser($user, $interests);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$journal =& Request::getJournal();
		$role = new Role();
		$role->setJournalId($journal->getId());
		$role->setUserId($userId);
		$role->setRoleId(ROLE_ID_REVIEWER);
		$roleDao->insertRole($role);

		if ($sendNotify) {
			// Send welcome email to user
			import('classes.mail.MailTemplate');
			$mail = new MailTemplate('REVIEWER_REGISTER');
			$mail->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
			$mail->assignParams(array('username' => $this->getData('username'), 'password' => $password, 'userFullName' => $user->getFullName()));
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
		}

		return $userId;
	}
}

?>
