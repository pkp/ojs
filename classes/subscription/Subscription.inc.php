<?php

/**
 * @file Subscription.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package subscription 
 * @class Subscription
 *
 * Subscription class.
 * Basic class describing a subscription.
 *
 * $Id$
 */

define('SUBSCRIPTION_IP_RANGE_SEPERATOR', ';');
define('SUBSCRIPTION_IP_RANGE_RANGE', '-');
define('SUBSCRIPTION_IP_RANGE_WILDCARD', '*');
define('SUBSCRIPTION_YEAR_OFFSET_PAST', '-10');
define('SUBSCRIPTION_YEAR_OFFSET_FUTURE', '+10');


class Subscription extends DataObject {

	function Subscription() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get the ID of the subscription.
	 * @return int
	 */
	function getSubscriptionId() {
		return $this->getData('subscriptionId');
	}
	
	/**
	 * Set the ID of the subscription.
	 * @param $subscriptionId int
	 */
	function setSubscriptionId($subscriptionId) {
		return $this->setData('subscriptionId', $subscriptionId);
	}

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
		$userDao = &DAORegistry::getDAO('UserDAO');
		return $userDao->getUserFullName($this->getData('userId'));
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
	function getTypeName() {
		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');
		return $subscriptionTypeDao->getSubscriptionTypeName($this->getData('typeId'));
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
	 * Get subscription domain string.
	 * @return string
	 */
	function getDomain() {
		return $this->getData('domain');
	}
	
	/**
	 * Set subscription domain string.
	 * @param $domain string
	 */
	function setDomain($domain) {
		return $this->setData('domain', $domain);
	}

	/**
	 * Get subscription ip range string.
	 * @return string
	 */
	function getIPRange() {
		return $this->getData('ipRange');
	}
	
	/**
	 * Set subscription ip range string.
	 * @param $ipRange string
	 */
	function setIPRange($ipRange) {
		return $this->setData('ipRange', $ipRange);
	}

	/**
	 * Get subscription ip ranges.
	 * @return array 
	 */
	function getIPRanges() {
		return explode(SUBSCRIPTION_IP_RANGE_SEPERATOR, $this->getData('ipRange'));
	}

	/**
	 * Set subscription ip ranges.
	 * @param ipRanges array 
	 */
	function setIPRanges($ipRanges) {
		return $this->setData(implode(SUBSCRIPTION_IP_RANGE_SEPERATOR, $ipRanges));
	}

}

?>
