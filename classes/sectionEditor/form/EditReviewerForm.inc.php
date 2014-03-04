<?php

/* copyright etc */

import('lib.pkp.classes.form.Form');

class EditReviewerForm extends Form {
	var $userId;
	var $articleId;

        /**
         * Constructor.
         */
        function EditReviewerForm($userId, $articleId) {
		parent::Form('sectionEditor/editReviewerForm.tpl');
		//$this->addCheck(new FormValidatorPost($this));

		$this->userId = $userId;
		$this->articleId = $articleId;

		// Validation checks for this form
		$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($userId, true), true));
		$this->addCheck(new FormValidatorPost($this));
	}



        /**
         * Initialize form data from current user profile.
         */
        function initData(&$args, &$request) {
                $interestDao =& DAORegistry::getDAO('InterestDAO');
                if (isset($this->userId)) {
                        $userDao =& DAORegistry::getDAO('UserDAO');
                        $user =& $userDao->getUser($this->userId);

                        // Get all available interests to populate the autocomplete with
                        if ($interestDao->getAllUniqueInterests()) {
                                $existingInterests = $interestDao->getAllUniqueInterests();
                        } else $existingInterests = null;

                        // Get the user's current set of interests
                        if ($interestDao->getInterests($user->getId())) {
                                $currentInterests = $interestDao->getInterests($user->getId());
                        } else $currentInterests = null;
		}
                        if ($user != null) {
                                $this->_data = array(
                                        'authId' => $user->getAuthId(),
                                        'username' => $user->getUsername(),
                                        'salutation' => $user->getSalutation(),
                                        'firstName' => $user->getFirstName(),
                                        'middleName' => $user->getMiddleName(),
                                        'lastName' => $user->getLastName(),
                                        'signature' => $user->getSignature(null), // Localized
                                        'initials' => $user->getInitials(),
                                        'gender' => $user->getGender(),
                                        'affiliation' => $user->getAffiliation(null), // Localized
                                        'email' => $user->getEmail(),
                                        'userUrl' => $user->getUrl(),
                                        'phone' => $user->getPhone(),
                                        'fax' => $user->getFax(),
                                        'mailingAddress' => $user->getMailingAddress(),
                                        'country' => $user->getCountry(),
                                        'biography' => $user->getBiography(null), // Localized
                                        'professionalTitle' => $user->getProfessionalTitle(null), // Localized
                                        'existingInterests' => $existingInterests,
                                        'interestsKeywords' => $currentInterests,
                                        'gossip' => $user->getGossip(null), // Localized
                                        'userLocales' => $user->getLocales()
                                );

                        } else {
                                $this->userId = null;
                        }
	}

        /**
         * Display the form.
         */
	function display(&$args, &$request) {
                $userId = isset($args[0]) ? (int) $args[0] : 0;
                $articleId = isset($args[1]) ? (int) $args[1] : 0;	

                $templateMgr =& TemplateManager::getManager();

		// get list of countries to populate drop-down list
                $countryDao =& DAORegistry::getDAO('CountryDAO');
                $countries =& $countryDao->getCountries();
                $templateMgr->assign_by_ref('countries', $countries);

		$templateMgr->assign('userId', $userId);
		$templateMgr->assign('articleId', $articleId);

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
                        'affiliation',
			'affiliationOther',
                        'userUrl',
			'email',
                        'phone',
                        'fax',
                        'interests',
                        'interestsKeywords',
			'gossip',
                        'mailingAddress',
                        'country',
                        'biography',
                        'professionalTitle'
                ));

                $interests = $this->getData('interestsKeywords');
                if ($interests != null && is_array($interests)) {
                        // The interests are coming in encoded -- Decode them for DB storage
                        $this->setData('interestsKeywords', array_map('urldecode', $interests));
                }
	}

	/*
	 * Calculate the proper URL to reach Subi on this server
	 */
	function subi_url()
	{
		$s = $_SERVER;
		$ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
		$sp = strtolower($s['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
		$port = $s['SERVER_PORT'];
		$port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
		$host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : $s['SERVER_NAME'];
		// Hack to get to proper subi on localhost
		if ($host == 'localhost' && $port == '') {
			$port = ':8080';
			$protocol = "http";
		}
		return $protocol . '://' . $host . $port . '/subi';
	}

	/**
	 * Update reviewer profile
	 */
        function execute() {
		$userId = $this->userId;

		if($userId) {
			// update users record
			$userDao =& DAORegistry::getDAO('UserDAO');
                	$user =& $userDao->getUser($userId);

	                // set value of affiliation
        	        // BLH 20131018 I'm not sure how to best deal with the localization here. This is a bit of a kluge since we're only going to use this locally at CDL.
                	$affiliation = $this->getData('affiliation');
                	if ($affiliation['en_US'] == 'Other') {
                        	$affiliationOther = $this->getData('affiliationOther');
                        	$affiliation = array('en_US' => $affiliationOther);
                	}

			$user->setAffiliation($affiliation, null); // Localized
			$user->setUrl($this->getData('userUrl'));
			$emailChanged = false;
			if ($user->getEmail() != $this->getData('email')) {
				$oldEmail = $user->getEmail();
				$user->setEmail($this->getData('email'));
				$emailChanged = true;
			}
			// MCH 20140211: In our OJS, email is the same as username.
			$user->setUsername(strtolower($this->getData('email')));

                	$user->setPhone($this->getData('phone'));
                	$user->setFax($this->getData('fax'));
			$user->setGossip($this->getData('gossip'), null); // Localized
                	$user->setMailingAddress($this->getData('mailingAddress'));
                	$user->setCountry($this->getData('country'));
                	$user->setBiography($this->getData('biography'), null); // Localized
			$user->setProfessionalTitle($this->getData('professionalTitle'), null); // Localized

			$userDao->updateObject($user);

			// update reviewer interests
                	import('lib.pkp.classes.user.InterestManager');
                	$interestManager = new InterestManager();
			$interestManager->insertInterests($userId, $this->getData('interestsKeywords'), $this->getData('interests'));

			// If email has changed, send a password reset link to the new address.
			if ($emailChanged) {
				// To do that, we need Subi to calculate the link.
				$url = $this->subi_url() . '/ojsResetPwd?oldEmail=' . urlEncode($oldEmail) .
				       '&newEmail=' . urlencode($user->getEmail());
				file_get_contents($url);
			}

		}
	}
}
