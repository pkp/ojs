<?php

/**
 * @defgroup subscription_form
 */

/**
 * @file classes/subscription/form/SubscriptionForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionForm
 * @ingroup subscription_form
 *
 * @brief Base form class for subscription create/edits.
 */

import('lib.pkp.classes.form.Form');

class SubscriptionForm extends Form {

	/** @var subscription the subscription being created/edited */
	var $subscription;

	/** @var userId int the user associated with the subscription */
	var $userId;

	/** @var subscriptionTypes Array of subscription types */
	var $subscriptionTypes;

	/** @var validStatus array valid subscription status values */
	var $validStatus;

	/** @var validCountries array valid user country values */
	var $validCountries;

	/**
	 * Constructor
	 * @param subscriptionId int leave as default for new subscription
	 */
	function SubscriptionForm($subscriptionId = null, $userId = null) {
		$subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;
		$this->userId = isset($userId) ? (int) $userId : null;

		$this->subscription = null;
		$this->subscriptionTypes = null;

		$subscriptionDao =& DAORegistry::getDAO('SubscriptionDAO');
		$this->validStatus =& $subscriptionDao->getStatusOptions();

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$this->validCountries =& $countryDao->getCountries();

		// User is provided and valid
		$this->addCheck(new FormValidator($this, 'userId', 'required', 'manager.subscriptions.form.userIdRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.userIdValid', create_function('$userId', '$userDao =& DAORegistry::getDAO(\'UserDAO\'); return $userDao->userExistsById($userId);')));

		// User name, country, and url valid
		$this->addCheck(new FormValidator($this, 'userFirstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'userLastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));

		$this->addCheck(new FormValidatorInSet($this, 'userCountry', 'optional', 'manager.subscriptions.form.countryValid', array_keys($this->validCountries)));

		// Subscription status is provided and valid
		$this->addCheck(new FormValidator($this, 'status', 'required', 'manager.subscriptions.form.statusRequired'));
		$this->addCheck(new FormValidatorInSet($this, 'status', 'required', 'manager.subscriptions.form.statusValid', array_keys($this->validStatus)));

		// Subscription type is provided
		$this->addCheck(new FormValidator($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdRequired'));
		// Notify email flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'notifyEmail', 'optional', 'manager.subscriptions.form.notifyEmailValid', array('1')));

		// Form was POSTed
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$journal =& Request::getJournal();

		if (isset($this->subscription)) {
			$subscriptionId = $this->subscription->getId();
			$templateMgr->assign('dateRemindedBefore', $this->subscription->getDateRemindedBefore());
			$templateMgr->assign('dateRemindedAfter', $this->subscription->getDateRemindedAfter());
		} else {
			$subscriptionId = null;
		}

		$templateMgr->assign('subscriptionId', $subscriptionId);
		$templateMgr->assign('yearOffsetPast', SUBSCRIPTION_YEAR_OFFSET_PAST);
		$templateMgr->assign('yearOffsetFuture', SUBSCRIPTION_YEAR_OFFSET_FUTURE);

		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $userDao->getUser(isset($this->userId)?$this->userId:$this->getData('userId'));

		$templateMgr->assign('userId', $user->getId());
		$templateMgr->assign('username', $user->getUsername());
		$templateMgr->assign('userSalutation', $user->getSalutation());
		$templateMgr->assign('userFirstName', $user->getFirstName());
		$templateMgr->assign('userMiddleName', $user->getMiddleName());
		$templateMgr->assign('userLastName', $user->getLastName());
		$templateMgr->assign('userInitials', $user->getInitials());
		$templateMgr->assign('userGender', $user->getGender());
		$templateMgr->assign('userAffiliation', $user->getAffiliation(null)); // Localized
		$templateMgr->assign('orcid', $user->getData('orcid'));
		$templateMgr->assign('userUrl', $user->getUrl());
		$templateMgr->assign('userFullName', $user->getFullName());
		$templateMgr->assign('userEmail', $user->getEmail());
		$templateMgr->assign('userPhone', $user->getPhone());
		$templateMgr->assign('userFax', $user->getFax());
		$templateMgr->assign('userMailingAddress', $user->getMailingAddress());
		$templateMgr->assign('userCountry', $user->getCountry());
		$templateMgr->assign('genderOptions', $userDao->getGenderOptions());

		$templateMgr->assign_by_ref('validStatus', $this->validStatus);
		$templateMgr->assign_by_ref('subscriptionTypes', $this->subscriptionTypes);
		$templateMgr->assign_by_ref('validCountries', $this->validCountries);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');

		parent::display();
	}

	/**
	 * Initialize form data from current subscription.
	 */
	function initData() {
		if (isset($this->subscription)) {
			$subscription =& $this->subscription;

			$userDao =& DAORegistry::getDAO('UserDAO');
			if (isset($this->userId)) {
				$user =& $userDao->getUser($this->userId);
			} else {
				$user =& $userDao->getUser($subscription->getUserId());
			}

			$this->_data = array(
				'status' => $subscription->getStatus(),
				'userId' => $user->getId(),
				'typeId' => $subscription->getTypeId(),
				'dateStart' => $subscription->getDateStart(),
				'dateEnd' => $subscription->getDateEnd(),
				'username', $user->getUsername(),
				'userSalutation', $user->getSalutation(),
				'userFirstName', $user->getFirstName(),
				'userMiddleName', $user->getMiddleName(),
				'userLastName', $user->getLastName(),
				'userInitials', $user->getInitials(),
				'userGender', $user->getGender(),
				'userAffiliation', $user->getAffiliation(null),
				'orcid', $user->getData('orcid'),
				'userUrl', $user->getUrl(),
				'userEmail' => $user->getEmail(),
				'userPhone' => $user->getPhone(),
				'userFax' => $user->getFax(),
				'userMailingAddress' => $user->getMailingAddress(),
				'userCountry' => $user->getCountry(),
				'membership' => $subscription->getMembership(),
				'referenceNumber' => $subscription->getReferenceNumber(),
				'notes' => $subscription->getNotes()
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('status', 'userId', 'typeId', 'dateStartYear', 'dateStartMonth', 'dateStartDay', 'dateEndYear', 'dateEndMonth', 'dateEndDay', 'userSalutation', 'userFirstName', 'userMiddleName', 'userLastName', 'userInitials', 'userGender', 'userAffiliation', 'orcid', 'userUrl', 'userEmail', 'userPhone', 'userFax', 'userMailingAddress', 'userCountry', 'membership', 'referenceNumber', 'notes', 'notifyEmail'));
		$this->_data['dateStart'] = Request::getUserDateVar('dateStart');
		$this->_data['dateEnd'] = Request::getUserDateVar('dateEnd');

		// Ensure user email is provided and does not already exist
		$this->addCheck(new FormValidatorEmail($this, 'userEmail', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'userEmail', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($this->getData('userId'), true), true));

		// If subscription type requires it, membership is provided
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$needMembership = $subscriptionTypeDao->getSubscriptionTypeMembership($this->getData('typeId'));

		if ($needMembership) {
			$this->addCheck(new FormValidator($this, 'membership', 'required', 'manager.subscriptions.form.membershipRequired'));
		}

		// If subscription type requires it, start and end dates are provided
		$nonExpiring = $subscriptionTypeDao->getSubscriptionTypeNonExpiring($this->getData('typeId'));

		if (!$nonExpiring) {
			// Start date is provided and is valid
			$this->addCheck(new FormValidator($this, 'dateStartYear', 'required', 'manager.subscriptions.form.dateStartRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'dateStartYear', 'required', 'manager.subscriptions.form.dateStartValid', create_function('$dateStartYear', '$minYear = date(\'Y\') + SUBSCRIPTION_YEAR_OFFSET_PAST; $maxYear = date(\'Y\') + SUBSCRIPTION_YEAR_OFFSET_FUTURE; return ($dateStartYear >= $minYear && $dateStartYear <= $maxYear) ? true : false;')));

			$this->addCheck(new FormValidator($this, 'dateStartMonth', 'required', 'manager.subscriptions.form.dateStartRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'dateStartMonth', 'required', 'manager.subscriptions.form.dateStartValid', create_function('$dateStartMonth', 'return ($dateStartMonth >= 1 && $dateStartMonth <= 12) ? true : false;')));

			$this->addCheck(new FormValidator($this, 'dateStartDay', 'required', 'manager.subscriptions.form.dateStartRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'dateStartDay', 'required', 'manager.subscriptions.form.dateStartValid', create_function('$dateStartDay', 'return ($dateStartDay >= 1 && $dateStartDay <= 31) ? true : false;')));

			// End date is provided and is valid
			$this->addCheck(new FormValidator($this, 'dateEndYear', 'required', 'manager.subscriptions.form.dateEndRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'dateEndYear', 'required', 'manager.subscriptions.form.dateEndValid', create_function('$dateEndYear', '$minYear = date(\'Y\') + SUBSCRIPTION_YEAR_OFFSET_PAST; $maxYear = date(\'Y\') + SUBSCRIPTION_YEAR_OFFSET_FUTURE; return ($dateEndYear >= $minYear && $dateEndYear <= $maxYear) ? true : false;')));

			$this->addCheck(new FormValidator($this, 'dateEndMonth', 'required', 'manager.subscriptions.form.dateEndRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateEndMonth', 'required', 'manager.subscriptions.form.dateEndValid', create_function('$dateEndMonth', 'return ($dateEndMonth >= 1 && $dateEndMonth <= 12) ? true : false;')));

			$this->addCheck(new FormValidator($this, 'dateEndDay', 'required', 'manager.subscriptions.form.dateEndRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'dateEndDay', 'required', 'manager.subscriptions.form.dateEndValid', create_function('$dateEndDay', 'return ($dateEndDay >= 1 && $dateEndDay <= 31) ? true : false;')));
		}

		// If notify email is requested, ensure subscription contact name and email exist.
		if ($this->_data['notifyEmail'] == 1) {
			$this->addCheck(new FormValidatorCustom($this, 'notifyEmail', 'required', 'manager.subscriptions.form.subscriptionContactRequired', create_function('', '$journal =& Request::getJournal(); $journalSettingsDao =& DAORegistry::getDAO(\'JournalSettingsDAO\'); $subscriptionName = $journalSettingsDao->getSetting($journal->getId(), \'subscriptionName\'); $subscriptionEmail = $journalSettingsDao->getSetting($journal->getId(), \'subscriptionEmail\'); return $subscriptionName != \'\' && $subscriptionEmail != \'\' ? true : false;'), array()));
		}
	}

	/**
	 * Save subscription.
	 */
	function execute() {
		$journal =& Request::getJournal();
		$subscription =& $this->subscription;

		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $userDao->getUser($this->getData('userId'));

		$subscription->setJournalId($journal->getId());
		$subscription->setStatus($this->getData('status'));
		$subscription->setUserId($this->getData('userId'));
		$subscription->setTypeId($this->getData('typeId'));
		$subscription->setMembership($this->getData('membership') ? $this->getData('membership') : null);
		$subscription->setReferenceNumber($this->getData('referenceNumber') ? $this->getData('referenceNumber') : null);
		$subscription->setNotes($this->getData('notes') ? $this->getData('notes') : null);

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$nonExpiring = $subscriptionTypeDao->getSubscriptionTypeNonExpiring($this->getData('typeId'));
		$subscription->setDateStart($nonExpiring ? null : $this->getData('dateStart'));
		$subscription->setDateEnd($nonExpiring ? null : $this->getData('dateEnd'));

		$user->setSalutation($this->getData('userSalutation'));
		$user->setFirstName($this->getData('userFirstName'));
		$user->setMiddleName($this->getData('userMiddleName'));
		$user->setLastName($this->getData('userLastName'));
		$user->setInitials($this->getData('userInitials'));
		$user->setGender($this->getData('userGender'));
		$user->setAffiliation($this->getData('userAffiliation'), null); // Localized
		$user->setData('orcid', $this->getData('orcid'));
		$user->setUrl($this->getData('userUrl'));
		$user->setEmail($this->getData('userEmail'));
		$user->setPhone($this->getData('userPhone'));
		$user->setFax($this->getData('userFax'));
		$user->setMailingAddress($this->getData('userMailingAddress'));
		$user->setCountry($this->getData('userCountry'));

		$userDao->updateObject($user);
	}

	/**
	 * Internal function to prepare notification email
	 */
	function &_prepareNotificationEmail($mailTemplateKey) {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');

		$journal =& Request::getJournal();
		$journalName = $journal->getLocalizedTitle();
		$journalId = $journal->getId();
		$user =& $userDao->getUser($this->subscription->getUserId());
		$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($this->subscription->getTypeId());

		$subscriptionName = $journalSettingsDao->getSetting($journalId, 'subscriptionName');
		$subscriptionEmail = $journalSettingsDao->getSetting($journalId, 'subscriptionEmail');
		$subscriptionPhone = $journalSettingsDao->getSetting($journalId, 'subscriptionPhone');
		$subscriptionFax = $journalSettingsDao->getSetting($journalId, 'subscriptionFax');
		$subscriptionMailingAddress = $journalSettingsDao->getSetting($journalId, 'subscriptionMailingAddress');
		$subscriptionContactSignature = $subscriptionName;

		if ($subscriptionMailingAddress != '') {
			$subscriptionContactSignature .= "\n" . $subscriptionMailingAddress;
		}
		if ($subscriptionPhone != '') {
			$subscriptionContactSignature .= "\n" . __('user.phone') . ': ' . $subscriptionPhone;
		}
		if ($subscriptionFax != '') {
			$subscriptionContactSignature .= "\n" . __('user.fax') . ': ' . $subscriptionFax;
		}

		$subscriptionContactSignature .= "\n" . __('user.email') . ': ' . $subscriptionEmail;

		$paramArray = array(
			'subscriberName' => $user->getFullName(),
			'journalName' => $journalName,
			'subscriptionType' => $subscriptionType->getSummaryString(),
			'username' => $user->getUsername(),
			'subscriptionContactSignature' => $subscriptionContactSignature
		);

		import('classes.mail.MailTemplate');
		$mail = new MailTemplate($mailTemplateKey);
		$mail->setReplyTo($subscriptionEmail, $subscriptionName);
		$mail->addRecipient($user->getEmail(), $user->getFullName());
		$mail->setSubject($mail->getSubject($journal->getPrimaryLocale()));
		$mail->setBody($mail->getBody($journal->getPrimaryLocale()));
		$mail->assignParams($paramArray);

		return $mail;
	}

}

?>
