<?php

/**
 * @defgroup sectionEditor_form
 */

/**
 * @file classes/sectionEditor/form/CreateReviewerForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CreateReviewerForm
 * @ingroup sectionEditor_form
 *
 * @brief Form for section editors to create reviewers.
 *
 * 
 */

// $Id$


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
		// 20111005 BLH Had to modify checks to reflect changed functionality (now using email address as username). Same as ojs/classes/manager/form/UserManagementForm.inc.php!
		$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(null, true), true));
		//$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));
		$this->addCheck(new FormValidatorEmail($this, 'username', 'required', 'user.profile.form.emailRequired')); //using email as username
		//$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array(null, true), true));
		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
		
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

		$interestDao =& DAORegistry::getDAO('InterestDAO');
		// Get all available interests to populate the autocomplete with
		if ($interestDao->getAllUniqueInterests()) {
			$existingInterests = $interestDao->getAllUniqueInterests();
		} else $existingInterests = null;
		$templateMgr->assign('existingInterests', $existingInterests);

                // Set list of institutions for eSchol
                $institutionList = array(
                        'UC Berkeley' => 'UC Berkeley',
                        'UC Davis' => 'UC Davis',
                        'UC Irvine' => 'UC Irvine',
                        'UC Los Angeles' => 'UC Los Angeles',
                        'UC Merced' => 'UC Merced',
                        'UC Riverside' => 'UC Riverside',
                        'UC San Diego' => 'UC San Diego',
                        'UC San Francisco' => 'UC San Francisco',
                        'UC Santa Barbara' => 'UC Santa Barbara',
                        'UC Santa Cruz' => 'UC Santa Cruz',
                        'UC Office of the President' => 'UC Office of the President',
                        'Lawrence Berkeley National Lab' => 'Lawrence Berkeley National Lab'
                );
                $templateMgr->assign('institutionList', $institutionList);

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
			'affiliationOther',
			'email',
			'userUrl',
			'phone',
			'fax',
			'mailingAddress',
			'country',
			'biography',
			'interests',
			'interestsKeywords',
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

		$interests = $this->getData('interestsKeywords');
		if ($interests != null && is_array($interests)) {
			// The interests are coming in encoded -- Decode them for DB storage
			$this->setData('interestsKeywords', array_map('urldecode', $interests));
		}
	}

	/**
	 * Register a new user.
	 * @return userId int
	 */
	function execute() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user = new User();

                // set value of affiliation
                // BLH 20131018 I'm not sure how to best deal with the localization here. This is a bit of a kluge since we're only going to use this locally at CDL.
                $affiliation = $this->getData('affiliation');
                if ($affiliation['en_US'] == 'Other') {
                        $affiliationOther = $this->getData('affiliationOther');
                        $affiliation = array('en_US' => $affiliationOther);
                }

		$user->setSalutation($this->getData('salutation'));
		$user->setFirstName($this->getData('firstName'));
		$user->setMiddleName($this->getData('middleName'));
		$user->setLastName($this->getData('lastName'));
		$user->setGender($this->getData('gender'));
		$user->setInitials($this->getData('initials'));
                $user->setAffiliation($affiliation, null); // Localized
		$user->setEmail($this->getData('email'));
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
			if (Locale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
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
		import('lib.pkp.classes.user.InterestManager');
		$interestManager = new InterestManager();
		$interestManager->insertInterests($userId, $this->getData('interestsKeywords'), $this->getData('interests'));

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
