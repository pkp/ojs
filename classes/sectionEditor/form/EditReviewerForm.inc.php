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

		// Validation checks
		// None of the fields on this form need validation, but here's where they would go.
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
		parent::display();
	}

        /**
         * Assign form data to user-submitted data.
         */
        function readInputData() {
                $this->readUserVars(array(
                        'affiliation',
                        'userUrl',
                        'phone',
                        'fax',
                        'interests',
                        'interestsKeywords',
			'gossip',
                        'mailingAddress',
                        'country',
                        'biography'
                ));

                $interests = $this->getData('interestsKeywords');
                if ($interests != null && is_array($interests)) {
                        // The interests are coming in encoded -- Decode them for DB storage
                        $this->setData('interestsKeywords', array_map('urldecode', $interests));
                }
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

                	$user->setAffiliation($this->getData('affiliation'), null); // Localized
                	$user->setUrl($this->getData('userUrl'));
                	$user->setPhone($this->getData('phone'));
                	$user->setFax($this->getData('fax'));
			$user->setGossip($this->getData('gossip'), null); // Localized
                	$user->setMailingAddress($this->getData('mailingAddress'));
                	$user->setCountry($this->getData('country'));
                	$user->setBiography($this->getData('biography'), null); // Localized

			$userDao->updateObject($user);

			// update reviewer interests
                	import('lib.pkp.classes.user.InterestManager');
                	$interestManager = new InterestManager();
                	$interestManager->insertInterests($userId, $this->getData('interestsKeywords'), $this->getData('interests'));
		}
	}
}
