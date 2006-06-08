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
 * $Id$
 */

/**
 * Subscription type formats
 */
define('SUBSCRIPTION_TYPE_FORMAT_ONLINE',		0x01); 
define('SUBSCRIPTION_TYPE_FORMAT_PRINT',		0x10);
define('SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE',	0x11);


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
	function getCurrencyId() {
		return $this->getData('currencyId');
	}
	
	/**
	 * Set subscription type currency.
	 * @param $currencyId int
	 */
	function setCurrencyId($currencyId) {
		return $this->setData('currencyId', $currencyId);
	}

	/**
	 * Get subscription type currency string.
	 * @return int
	 */
	function getCurrencyString() {
		$currencyDao = &DAORegistry::getDAO('CurrencyDAO');
		$currency = $currencyDao->getCurrency($this->getData('currencyId'));

		if ($currency != null) {
			return $currency->getName();
		} else {
			return 'manager.subscriptionTypes.currency';
		}
	}

	/**
	 * Get subscription type currency abbreviated string.
	 * @return int
	 */
	function getCurrencyStringShort() {
		$currencyDao = &DAORegistry::getDAO('CurrencyDAO');
		$currency = $currencyDao->getCurrency($this->getData('currencyId'));

		if ($currency != null) {
			return $currency->getCodeAlpha();
		} else {
			return 'manager.subscriptionTypes.currency';
		}
	}

	/**
	 * Get subscription type duration.
	 * @return int
	 */
	function getDuration() {
		return $this->getData('duration');
	}
	
	/**
	 * Set subscription type duration.
	 * @param $duration int
	 */
	function setDuration($duration) {
		return $this->setData('duration', $duration);
	}

	/**
	 * Get subscription type duration in years and months.
	 * @return string
	 */
	function getDurationYearsMonths() {
		$years = (int)floor($this->getData('duration')/12);
		$months = (int)fmod($this->getData('duration'), 12);
		$yearsMonths = '';

		if ($years == 1) {
			$yearsMonths = '1 ' . Locale::Translate('manager.subscriptionTypes.year');
		} elseif ($years > 1) {
			$yearsMonths = $years . ' ' . Locale::Translate('manager.subscriptionTypes.years');
		}

		if ($months == 1) {
			$yearsMonths .= $yearsMonths == ''  ? '1 ' : ' 1 ';
			$yearsMonths .= Locale::Translate('manager.subscriptionTypes.month'); 
		} elseif ($months > 1){
			$yearsMonths .= $yearsMonths == ''  ? $months . ' ' : ' ' . $months . ' ';
			$yearsMonths .= Locale::Translate('manager.subscriptionTypes.months');
		}

		return $yearsMonths;
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
	 * Check if this subscription type should be publicly visible.
	 * @return boolean
	 */
	function getPublic() {
		return $this->getData('public');
	}
	
	/**
	 * Set whether or not this subscription should be publicly visible.
	 * @param $public boolean
	 */
	function setPublic($public) {
		return $this->setData('public', $public);
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

	/**
	 * Get subscription type summary in the form: TypeName - Duration - Cost (CurrencyShort).
	 * @return string
	 */
	function getSummaryString() {
		return $this->getTypeName() . ' - ' . $this->getDurationYearsMonths() . ' - ' . sprintf('%.2f', $this->getCost()) . ' ' . $this->getCurrencyStringShort();
	}
}

?>
