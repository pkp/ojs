<?php

/**
 * @file classes/gift/Gift.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Gift
 * @ingroup gift
 * @see GiftDAO
 *
 * @brief Class for an OJS Gift.
 */

import('lib.pkp.classes.gift.PKPGift');

define('GIFT_TYPE_SUBSCRIPTION', 0x01);

class Gift extends PKPGift {
	/**
	 * Constructor.
	 */
	function Gift() {
		parent::PKPGift();
	}

	/**
	 * Get the name of the gift based on gift type.
	 * @return string
	 */
	function getGiftName() {
		switch ($this->getGiftType()){
			case GIFT_TYPE_SUBSCRIPTION:
				$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
				$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($this->getGiftAssocId());
				if ($subscriptionType) {
					return Locale::translate('payment.type.gift') . ' ' . Locale::translate('payment.type.gift.subscription') . ': ' . $subscriptionType->getSubscriptionTypeName() . ' - ' . $subscriptionType->getDurationYearsMonths();
				} else {
					return Locale::translate('payment.type.gift') . ' ' . Locale::translate('payment.type.gift.subscription');
				}
				break;
			default:
				return Locale::translate('payment.type.gift');
		}
	}
}

?>