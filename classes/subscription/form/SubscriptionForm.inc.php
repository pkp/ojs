<?php

/**
 * @file classes/subscription/form/SubscriptionForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionForm
 * @ingroup subscription
 *
 * @brief Base form class for subscription create/edits.
 */

import('lib.pkp.classes.form.Form');

class SubscriptionForm extends Form {

	/** @var Subscription the subscription being created/edited */
	var $subscription;

	/** @var int the user associated with the subscription */
	var $userId;

	/** @var array of subscription types */
	var $subscriptionTypes;

	/** @var array valid subscription status values */
	var $validStatus;

	/** @var array valid user country values */
	var $validCountries;

	/**
	 * Constructor
	 * @param $template string? Template to use for form presentation
	 * @param $subscriptionId int The subscription ID for this subscription; null for new subscription
	 */
	function __construct($template, $subscriptionId = null) {
		parent::__construct($template);

		$subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;

		$this->subscription = null;
		$this->subscriptionTypes = null;

		import('classes.subscription.SubscriptionDAO');
		$this->validStatus = SubscriptionDAO::getStatusOptions();

		$countryDao = DAORegistry::getDAO('CountryDAO');
		$this->validCountries =& $countryDao->getCountries();

		// User is provided and valid
		$this->addCheck(new FormValidator($this, 'userId', 'required', 'manager.subscriptions.form.userIdRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.userIdValid', function($userId) {
			$userDao = DAORegistry::getDAO('UserDAO');
			return $userDao->userExistsById($userId);
		}));

		// Subscription status is provided and valid
		$this->addCheck(new FormValidator($this, 'status', 'required', 'manager.subscriptions.form.statusRequired'));
		$this->addCheck(new FormValidatorInSet($this, 'status', 'required', 'manager.subscriptions.form.statusValid', array_keys($this->validStatus)));
		// Subscription type is provided
		$this->addCheck(new FormValidator($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdRequired'));
		// Notify email flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'notifyEmail', 'optional', 'manager.subscriptions.form.notifyEmailValid', array('1')));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Display the form.
	 * @param $request PKPRequest
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'subscriptionId' => $this->subscription?$this->subscription->getId():null,
			'yearOffsetPast' => SUBSCRIPTION_YEAR_OFFSET_PAST,
			'yearOffsetFuture' => SUBSCRIPTION_YEAR_OFFSET_FUTURE,
			'validStatus' => $this->validStatus,
			'subscriptionTypes' => $this->subscriptionTypes,
		));
		return parent::fetch($request);
	}

	/**
	 * Initialize form data from current subscription.
	 */
	function initData() {
		if (isset($this->subscription)) {
			$subscription = $this->subscription;
			$this->_data = array(
				'status' => $subscription->getStatus(),
				'userId' => $subscription->getUserId(),
				'typeId' => $subscription->getTypeId(),
				'dateStart' => $subscription->getDateStart(),
				'dateEnd' => $subscription->getDateEnd(),
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
		$this->readUserVars(array('status', 'userId', 'typeId', 'membership', 'referenceNumber', 'notes', 'notifyEmail', 'dateStart', 'dateEnd'));

		// If subscription type requires it, membership is provided
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$needMembership = $subscriptionTypeDao->getSubscriptionTypeMembership($this->getData('typeId'));

		if ($needMembership) {
			$this->addCheck(new FormValidator($this, 'membership', 'required', 'manager.subscriptions.form.membershipRequired'));
		}

		// If subscription type requires it, start and end dates are provided
		$subscriptionType = $subscriptionTypeDao->getById($this->getData('typeId'));
		$nonExpiring = $subscriptionType->getNonExpiring();

		if (!$nonExpiring) {
			// Start date is provided and is valid
			$this->addCheck(new FormValidator($this, 'dateStart', 'required', 'manager.subscriptions.form.dateStartRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'dateStart', 'required', 'manager.subscriptions.form.dateStartValid', function($dateStart) {
				$dateStartYear = strftime('%Y', strtotime($dateStart));
				$minYear = date('Y') + SUBSCRIPTION_YEAR_OFFSET_PAST;
				$maxYear = date('Y') + SUBSCRIPTION_YEAR_OFFSET_FUTURE;
				return ($dateStartYear >= $minYear && $dateStartYear <= $maxYear);
			}));
			$this->addCheck(new FormValidatorCustom($this, 'dateStart', 'required', 'manager.subscriptions.form.dateStartValid', function($dateStart) {
				$dateStartMonth = strftime('%m', strtotime($dateStart));
				return ($dateStartMonth >= 1 && $dateStartMonth <= 12);
			}));
			$this->addCheck(new FormValidatorCustom($this, 'dateStart', 'required', 'manager.subscriptions.form.dateStartValid', function($dateStart) {
				$dateStartDay = strftime('%d', strtotime($dateStart));
				return ($dateStartDay >= 1 && $dateStartDay <= 31);
			}));

			// End date is provided and is valid
			$this->addCheck(new FormValidator($this, 'dateEnd', 'required', 'manager.subscriptions.form.dateEndRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'dateEnd', 'required', 'manager.subscriptions.form.dateEndValid', function($dateEnd) {
				$dateEndYear = strftime('%Y', strtotime($dateEnd)); $minYear = date('Y') + SUBSCRIPTION_YEAR_OFFSET_PAST;
				$maxYear = date('Y') + SUBSCRIPTION_YEAR_OFFSET_FUTURE;
				return ($dateEndYear >= $minYear && $dateEndYear <= $maxYear);
			}));
			$this->addCheck(new FormValidatorCustom($this, 'dateEnd', 'required', 'manager.subscriptions.form.dateEndValid', function($dateEnd) {
				$dateEndMonth = strftime('%m', strtotime($dateEnd));
				return ($dateEndMonth >= 1 && $dateEndMonth <= 12);
			}));
			$this->addCheck(new FormValidatorCustom($this, 'dateEnd', 'required', 'manager.subscriptions.form.dateEndValid', function($dateEnd) {
				$dateEndDay = strftime('%d', strtotime($dateEnd));
				return ($dateEndDay >= 1 && $dateEndDay <= 31);
			}));
		} else {
			// Is non-expiring; ensure that start/end dates weren't entered.
			$this->addCheck(new FormValidatorCustom($this, 'dateStart', 'optional', 'manager.subscriptions.form.dateStartEmpty', function($dateStart) {
				return empty($dateStart);
			}));
			$this->addCheck(new FormValidatorCustom($this, 'dateEnd', 'optional', 'manager.subscriptions.form.dateEndEmpty', function($dateEnd) {
				return empty($dateEnd);
			}));
		}

		// If notify email is requested, ensure subscription contact name and email exist.
		if ($this->_data['notifyEmail'] == 1) {
			$this->addCheck(new FormValidatorCustom($this, 'notifyEmail', 'required', 'manager.subscriptions.form.subscriptionContactRequired', function() {
				$request = Application::get()->getRequest();
				$journal = $request->getJournal();
				$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
				$subscriptionName = $journalSettingsDao->getSetting($journal->getId(), 'subscriptionName');
				$subscriptionEmail = $journalSettingsDao->getSetting($journal->getId(), 'subscriptionEmail');
				return $subscriptionName != '' && $subscriptionEmail != '';
			}));
		}
	}

	/**
	 * Save subscription.
	 */
	function execute() {
		$request = Application::get()->getRequest();
		$journal = $request->getJournal();
		$subscription =& $this->subscription;

		$subscription->setJournalId($journal->getId());
		$subscription->setStatus($this->getData('status'));
		$subscription->setUserId($this->getData('userId'));
		$subscription->setTypeId($this->getData('typeId'));
		$subscription->setMembership($this->getData('membership') ? $this->getData('membership') : null);
		$subscription->setReferenceNumber($this->getData('referenceNumber') ? $this->getData('referenceNumber') : null);
		$subscription->setNotes($this->getData('notes') ? $this->getData('notes') : null);

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId());
		if (!$subscriptionType->getNonExpiring()) {
			$subscription->setDateStart($this->getData('dateStart'));
			$subscription->setDateEnd($this->getData('dateEnd'));
		}
	}

	/**
	 * Internal function to prepare notification email
	 * @param $emailTemplateKey string
	 */
	function _prepareNotificationEmail($mailTemplateKey) {
		$userDao = DAORegistry::getDAO('UserDAO');
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');

		$request = Application::get()->getRequest();
		$journal = $request->getJournal();
		$journalName = $journal->getLocalizedTitle();
		$journalId = $journal->getId();
		$user = $userDao->getById($this->subscription->getUserId());
		$subscriptionType = $subscriptionTypeDao->getById($this->subscription->getTypeId());

		$subscriptionName = $journalSettingsDao->getSetting($journalId, 'subscriptionName');
		$subscriptionEmail = $journalSettingsDao->getSetting($journalId, 'subscriptionEmail');
		$subscriptionPhone = $journalSettingsDao->getSetting($journalId, 'subscriptionPhone');
		$subscriptionMailingAddress = $journalSettingsDao->getSetting($journalId, 'subscriptionMailingAddress');
		$subscriptionContactSignature = $subscriptionName;

		if ($subscriptionMailingAddress != '') {
			$subscriptionContactSignature .= "\n" . $subscriptionMailingAddress;
		}
		if ($subscriptionPhone != '') {
			$subscriptionContactSignature .= "\n" . __('user.phone') . ': ' . $subscriptionPhone;
		}

		$subscriptionContactSignature .= "\n" . __('user.email') . ': ' . $subscriptionEmail;

		$paramArray = array(
			'subscriberName' => $user->getFullName(),
			'journalName' => $journalName,
			'subscriptionType' => $subscriptionType->getSummaryString(),
			'username' => $user->getUsername(),
			'subscriptionContactSignature' => $subscriptionContactSignature
		);

		import('lib.pkp.classes.mail.MailTemplate');
		$mail = new MailTemplate($mailTemplateKey);
		$mail->setReplyTo($subscriptionEmail, $subscriptionName);
		$mail->addRecipient($user->getEmail(), $user->getFullName());
		$mail->setSubject($mail->getSubject($journal->getPrimaryLocale()));
		$mail->setBody($mail->getBody($journal->getPrimaryLocale()));
		$mail->assignParams($paramArray);

		return $mail;
	}
}


