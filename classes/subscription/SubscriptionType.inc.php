<?php

/**
 * SubscriptionType.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package subscription 
 *
 * Subscription type class.
 * Basic class describing a subscription type.
 *
 */

/**
 * Subscription type formats
 */
define('SUBSCRIPTION_TYPE_FORMAT_ONLINE',		0x01); 
define('SUBSCRIPTION_TYPE_FORMAT_PRINT',		0x10);
define('SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE',	0x11);

/**
 * Subscription type currencies 
 */
define('SUBSCRIPTION_TYPE_CURRENCY_US',			0x01);
define('SUBSCRIPTION_TYPE_CURRENCY_CANADA',		0x02);
define('SUBSCRIPTION_TYPE_CURRENCY_EUROPE',		0x03);


class SubscriptionType extends DataObject {

	function SubscriptionType() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get the ID of the subscription type.
	 * @return int
	 */
	function getTypeId() {
		return $this->getData('typeId');
	}
	
	/**
	 * Set the ID of the subscription type.
	 * @param $typeId int
	 */
	function setTypeId($typeId) {
		return $this->setData('typeId', $typeId);
	}

	/**
	 * Get the journal ID of the subscription type.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}
	
	/**
	 * Set the journal ID of the subscription type.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}
	
	/**
	 * Get subscription type name.
	 * @return string
	 */
	function getTypeName() {
		return $this->getData('typeName');
	}
	
	/**
	 * Set subscription type name.
	 * @param $typeName string
	 */
	function setTypeName($typeName) {
		return $this->setData('typeName', $typeName);
	}

	/**
	 * Get subscription type description.
	 * @return string
	 */
	function getDescription() {
		return $this->getData('description');
	}
	
	/**
	 * Set subscription type description.
	 * @param $description string
	 */
	function setDescription($description) {
		return $this->setData('description', $description);
	}

	/**
	 * Get subscription type cost.
	 * @return float 
	 */
	function getCost() {
		return $this->getData('cost');
	}
	
	/**
	 * Set subscription type cost.
	 * @param $cost float
	 */
	function setCost($cost) {
		return $this->setData('cost', $cost);
	}

	/**
	 * Get subscription type currency.
	 * @return int
	 */
	function getCurrency() {
		return $this->getData('currency');
	}
	
	/**
	 * Set subscription type currency.
	 * @param $currency int
	 */
	function setCurrency($currency) {
		return $this->setData('currency', $currency);
	}

	/**
	 * Get subscription type currency locale key.
	 * @return int
	 */
	function getCurrencyString() {
		switch ($this->getData('currency')) {
			case SUBSCRIPTION_TYPE_CURRENCY_US:
				return 'manager.subscriptionTypes.currency.us';
			case SUBSCRIPTION_TYPE_CURRENCY_CANADA:
				return 'manager.subscriptionTypes.currency.canada';
			case SUBSCRIPTION_TYPE_CURRENCY_EUROPE:
				return 'manager.subscriptionTypes.currency.europe';
			default:
				return 'manager.subscriptionTypes.currency';
		}
	}

	/**
	 * Get subscription type currency locale key.
	 * @return int
	 */
	function getCurrencyLongString() {
		switch ($this->getData('currency')) {
			case SUBSCRIPTION_TYPE_CURRENCY_US:
				return 'manager.subscriptionTypes.currency.usLong';
			case SUBSCRIPTION_TYPE_CURRENCY_CANADA:
				return 'manager.subscriptionTypes.currency.canadaLong';
			case SUBSCRIPTION_TYPE_CURRENCY_EUROPE:
				return 'manager.subscriptionTypes.currency.europeLong';
			default:
				return 'manager.subscriptionTypes.currency';
		}
	}
	
	/**
	 * Get subscription type format.
	 * @return int
	 */
	function getFormat() {
		return $this->getData('format');
	}
	
	/**
	 * Set subscription type format.
	 * @param $format int
	 */
	function setFormat($format) {
		return $this->setData('format', $format);
	}

	/**
	 * Get subscription type format locale key.
	 * @return int
	 */
	function getFormatString() {
		switch ($this->getData('format')) {
			case SUBSCRIPTION_TYPE_FORMAT_ONLINE:
				return 'manager.subscriptionTypes.format.online';
			case SUBSCRIPTION_TYPE_FORMAT_PRINT:
				return 'manager.subscriptionTypes.format.print';
			case SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE:
				return 'manager.subscriptionTypes.format.printOnline';
			default:
				return 'manager.subscriptionTypes.format';
		}
	}

	/**
	 * Check if this subscription type is for an institution.
	 * @return boolean
	 */
	function getInstitutional() {
		return $this->getData('institutional');
	}
	
	/**
	 * Set whether or not this subscription type is for an institution.
	 * @param $institutional boolean
	 */
	function setInstitutional($institutional) {
		return $this->setData('institutional', $institutional);
	}

	/**
	 * Check if this subscription type requires a membership.
	 * @return boolean
	 */
	function getMembership() {
		return $this->getData('membership');
	}
	
	/**
	 * Set whether or not this subscription type requires a membership.
	 * @param $membership boolean
	 */
	function setMembership($membership) {
		return $this->setData('membership', $membership);
	}

	/**
	 * Get subscription type display sequence.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}
	
	/**
	 * Set subscription type display sequence.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

}

?>
