<?php

/**
 * @file SubscriptionType.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package subscription 
 * @class SubscriptionType
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
	 * Get the localized subscription type name
	 * @return string
	 */
	function getSubscriptionTypeName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get subscription type name.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set subscription type name.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

	/**
	 * Get the localized subscription type description
	 * @return string
	 */
	function getSubscriptionTypeDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get subscription type description.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set subscription type description.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
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
	 * Get subscription type currency code.
	 * @return string
	 */
	function getCurrencyCodeAlpha() {
		return $this->getData('currencyCodeAlpha');
	}

	/**
	 * Set subscription type currency code.
	 * @param $currencyCodeAlpha string
	 */
	function setCurrencyCodeAlpha($currencyCodeAlpha) {
		return $this->setData('currencyCodeAlpha', $currencyCodeAlpha);
	}

	/**
	 * Get subscription type currency string.
	 * @return int
	 */
	function getCurrencyString() {
		$currencyDao = &DAORegistry::getDAO('CurrencyDAO');
		$currency =& $currencyDao->getCurrencyByAlphaCode($this->getData('currencyCodeAlpha'));

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
		$currency =& $currencyDao->getCurrencyByAlphaCode($this->getData('currencyCodeAlpha'));

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
	function getNoPublicDisplay() {
		return $this->getData('no_public_display');
	}

	/**
	 * Set whether or not this subscription should be publicly visible.
	 * @param $noPublicDisplay boolean
	 */
	function setNoPublicDisplay($noPublicDisplay) {
		return $this->setData('no_public_display', $noPublicDisplay);
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
		return $this->getSubscriptionTypeName() . ' - ' . $this->getDurationYearsMonths() . ' - ' . sprintf('%.2f', $this->getCost()) . ' ' . $this->getCurrencyStringShort();
	}
}

?>
