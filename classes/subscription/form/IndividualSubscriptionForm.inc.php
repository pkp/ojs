<?php

/**
 * @defgroup subscription_form
 */
 
/**
 * @file classes/subscription/form/IndividualSubscriptionForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscriptionForm
 * @ingroup subscription_form
 *
 * @brief Form class for individual subscription create/edits.
 */

import('classes.subscription.form.SubscriptionForm');

class IndividualSubscriptionForm extends SubscriptionForm {

	/**
	 * Constructor
	 * @param subscriptionId int leave as default for new subscription
	 */
	function IndividualSubscriptionForm($subscriptionId = null, $userId = null) {
		parent::Form('subscription/individualSubscriptionForm.tpl');
		parent::SubscriptionForm($subscriptionId, $userId);

		$subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;
		$userId = isset($userId) ? (int) $userId : null;

		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		if (isset($subscriptionId)) {
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO'); 
			if ($subscriptionDao->subscriptionExists($subscriptionId)) {
				$this->subscription =& $subscriptionDao->getSubscription($subscriptionId);
			}
		}

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypes =& $subscriptionTypeDao->getSubscriptionTypesByInstitutional($journalId, false);
		$this->subscriptionTypes =& $subscriptionTypes->toArray();

		$subscriptionTypeCount = count($this->subscriptionTypes);
		if ($subscriptionTypeCount == 0) {
			$this->addError('typeId', __('manager.subscriptions.form.typeRequired'));
			$this->addErrorField('typeId');
		}

		// Ensure subscription type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdValid', create_function('$typeId, $journalId', '$subscriptionTypeDao =& DAORegistry::getDAO(\'SubscriptionTypeDAO\'); return ($subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId) == 0);'), array($journal->getId())));

		// Ensure that user does not already have a subscription for this journal
		if (!isset($subscriptionId)) {
			$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.subscriptionExists', array(DAORegistry::getDAO('IndividualSubscriptionDAO'), 'subscriptionExistsByUserForJournal'), array($journalId), true));
		} else {
			$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.subscriptionExists', create_function('$userId, $journalId, $subscriptionId', '$subscriptionDao =& DAORegistry::getDAO(\'IndividualSubscriptionDAO\'); $checkId = $subscriptionDao->getSubscriptionIdByUser($userId, $journalId); return ($checkId == 0 || $checkId == $subscriptionId) ? true : false;'), array($journalId, $subscriptionId)));
		}
	}

	/**
	 * Save individual subscription. 
	 */
	function execute() {
		$insert = false;
		if (!isset($this->subscription)) {
			import('classes.subscription.IndividualSubscription');
			$this->subscription = new IndividualSubscription();
			$insert = true;
		}

		parent::execute();
		$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');

		if ($insert) {
			$individualSubscriptionDao->insertSubscription($this->subscription);
		} else {
			$individualSubscriptionDao->updateSubscription($this->subscription);
		} 

		// Send notification email
		if ($this->_data['notifyEmail'] == 1) {
			$mail =& $this->_prepareNotificationEmail('SUBSCRIPTION_NOTIFY');
			$mail->send();
		} 
	}
}

?>
