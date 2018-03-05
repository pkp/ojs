<?php

/**
 * @file classes/subscription/Subscription.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Subscription
 * @ingroup subscription 
 * @see SubscriptionDAO
 *
 * @brief Basic class describing a subscription.
 */

define('SUBSCRIPTION_STATUS_ACTIVE', 			0x01);
define('SUBSCRIPTION_STATUS_NEEDS_INFORMATION', 	0x02);
define('SUBSCRIPTION_STATUS_NEEDS_APPROVAL', 		0x03);
define('SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT', 	0x04);
define('SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT',	0x05);
define('SUBSCRIPTION_STATUS_OTHER', 			0x10);

define('SUBSCRIPTION_DATE_START',	0x01);
define('SUBSCRIPTION_DATE_END',		0x02);
define('SUBSCRIPTION_DATE_BOTH',	0x03);

define('SUBSCRIPTION_YEAR_OFFSET_PAST',		'-10');
define('SUBSCRIPTION_YEAR_OFFSET_FUTURE',	'+10');


class Subscription extends DataObject {

	//
	// Get/set methods
	//

	/**
	 * Get the journal ID of the subscription.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set the journal ID of the subscription.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get the user ID of the subscription.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * Set the user ID of the subscription.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}

	/**
	 * Get the user's full name of the subscription.
	 * @return string 
	 */
	function getUserFullName() {
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getUserFullName($this->getData('userId'));
	}

	/**
	 * Get the user's email of the subscription.
	 * @return string 
	 */
	function getUserEmail() {
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getUserEmail($this->getData('userId'));
	}

	/**
	 * Get the subscription type ID of the subscription.
	 * @return int
	 */
	function getTypeId() {
		return $this->getData('typeId');
	}

	/**
	 * Set the subscription type ID of the subscription.
	 * @param $typeId int
	 */
	function setTypeId($typeId) {
		return $this->setData('typeId', $typeId);
	}

	/**
	 * Get the subscription type name of the subscription.
	 * @return string
	 */
	function getSubscriptionTypeName() {
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		return $subscriptionTypeDao->getSubscriptionTypeName($this->getData('typeId'));
	}

	/**
	 * Get the subscription type name of the subscription.
	 * @return string
	 */
	function getSubscriptionTypeSummaryString() {
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($this->getData('typeId'));
		return $subscriptionType->getSummaryString();
	}

	/**
	 * Get the subscription type institutional flag for the subscription.
	 * @return string
	 */
	function getSubscriptionTypeInstitutional() {
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		return $subscriptionTypeDao->getSubscriptionTypeInstitutional($this->getData('typeId'));
	}

	/**
	 * Check whether the subscription type is non-expiring for the subscription.
	 * @return boolean
	 */
	function isNonExpiring() {
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType = $subscriptionTypeDao->getById($this->getTypeId());
		return $subscriptionType->getNonExpiring();
	}

	/**
	 * Get subscription start date.
	 * @return date (YYYY-MM-DD)
	 */
	function getDateStart() {
		return $this->getData('dateStart');
	}

	/**
	 * Set subscription start date.
	 * @param $dateStart date (YYYY-MM-DD)
	 */
	function setDateStart($dateStart) {
		return $this->setData('dateStart', $dateStart);
	}

	/**
	 * Get subscription end date.
	 * @return date (YYYY-MM-DD) 
	 */
	function getDateEnd() {
		return $this->getData('dateEnd');
	}

	/**
	 * Set subscription end date.
	 * @param $dateEnd date (YYYY-MM-DD)
	 */
	function setDateEnd($dateEnd) {
		return $this->setData('dateEnd', $dateEnd);
	}

	/**
	 * Get subscription status.
	 * @return int
	 */
	function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Set subscription status.
	 * @param $status int
	 */
	function setStatus($status) {
		return $this->setData('status', $status);
	}

	/**
	 * Get subscription status string.
	 * @return int
	 */
	function getStatusString() {
		switch ($this->getData('status')) {
			case SUBSCRIPTION_STATUS_ACTIVE:
				return __('subscriptions.status.active');
			case SUBSCRIPTION_STATUS_NEEDS_INFORMATION:
				return __('subscriptions.status.needsInformation');
			case SUBSCRIPTION_STATUS_NEEDS_APPROVAL:
				return __('subscriptions.status.needsApproval');
			case SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT:
				return __('subscriptions.status.awaitingManualPayment');
			case SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT:
				return __('subscriptions.status.awaitingOnlinePayment');
			case SUBSCRIPTION_STATUS_OTHER:
				return __('subscriptions.status.other');
			default:
				return __('subscriptions.status');
		}
	}

	/**
	 * Get subscription membership.
	 * @return string
	 */
	function getMembership() {
		return $this->getData('membership');
	}

	/**
	 * Set subscription membership.
	 * @param $membership string
	 */
	function setMembership($membership) {
		return $this->setData('membership', $membership);
	}

	/**
	 * Get subscription reference number.
	 * @return string
	 */
	function getReferenceNumber() {
		return $this->getData('referenceNumber');
	}

	/**
	 * Set subscription reference number.
	 * @param $referenceNumber string
	 */
	function setReferenceNumber($referenceNumber) {
		return $this->setData('referenceNumber', $referenceNumber);
	}

	/**
	 * Get subscription notes.
	 * @return string
	 */
	function getNotes() {
		return $this->getData('notes');
	}

	/**
	 * Set subscription notes.
	 * @param $notes string
	 */
	function setNotes($notes) {
		return $this->setData('notes', $notes);
	}

	/**
	 * Check whether subscription is expired
	 */
	function isExpired() {
		if (strtotime($this->getData('dateEnd')) < time()) {
			return true;
		} else {
			return false;
		}
	}
}

?>
