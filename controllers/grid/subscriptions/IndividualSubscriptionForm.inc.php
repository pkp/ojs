<?php

/**
 * @file controllers/grid/subscriptions/IndividualSubscriptionForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscriptionForm
 * @ingroup subscription
 *
 * @brief Form class for individual subscription create/edits.
 */

import('classes.subscription.form.SubscriptionForm');

class IndividualSubscriptionForm extends SubscriptionForm {

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $subscriptionId int leave as default for new subscription
	 */
	function __construct($request, $subscriptionId = null) {
		parent::__construct('payments/individualSubscriptionForm.tpl', $subscriptionId);

		$subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;
		$journal = $request->getJournal();
		$journalId = $journal->getId();

		if (isset($subscriptionId)) {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $subscriptionDao IndividualSubscriptionDAO */
			if ($subscriptionDao->subscriptionExists($subscriptionId)) {
				$this->subscription = $subscriptionDao->getById($subscriptionId);
			}
		}

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
		$subscriptionTypeIterator = $subscriptionTypeDao->getByInstitutional($journalId, false);
		$this->subscriptionTypes = array();
		while ($subscriptionType = $subscriptionTypeIterator->next()) {
			$this->subscriptionTypes[$subscriptionType->getId()] = $subscriptionType->getSummaryString();
		}

		if (count($this->subscriptionTypes) == 0) {
			$this->addError('typeId', __('manager.subscriptions.form.typeRequired'));
			$this->addErrorField('typeId');
		}

		// Ensure subscription type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdValid', function($typeId) use ($journalId) {
			$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
			return ($subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId) == 0);
		}));

		// Ensure that user does not already have a subscription for this journal
		if (!isset($subscriptionId)) {
			$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.subscriptionExists', array(DAORegistry::getDAO('IndividualSubscriptionDAO'), 'subscriptionExistsByUserForJournal'), array($journalId), true));
		} else {
			$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.subscriptionExists', function($userId) use ($journalId, $subscriptionId) {
				$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $subscriptionDao IndividualSubscriptionDAO */
				$checkSubscription = $subscriptionDao->getByUserIdForJournal($userId, $journalId);
				return (!$checkSubscription || $checkSubscription->getId() == $subscriptionId) ? true : false;
			}));
		}
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute(...$functionArgs) {
		$insert = false;
		if (!isset($this->subscription)) {
			import('classes.subscription.IndividualSubscription');
			$this->subscription = new IndividualSubscription();
			$insert = true;
		}

		parent::execute(...$functionArgs);
		$individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $individualSubscriptionDao IndividualSubscriptionDAO */

		if ($insert) {
			$individualSubscriptionDao->insertObject($this->subscription);
		} else {
			$individualSubscriptionDao->updateObject($this->subscription);
		}

		// Send notification email
		if ($this->_data['notifyEmail'] == 1) {
			$mail = $this->_prepareNotificationEmail('SUBSCRIPTION_NOTIFY');
			if (!$mail->send()) {
				import('classes.notification.NotificationManager');
				$notificationMgr = new NotificationManager();
				$request = Application::get()->getRequest();
				$notificationMgr->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('email.compose.error')));
			}
		}
	}
}


