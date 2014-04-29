<?php

/**
 * @defgroup subscription
 */
 
/**
 * @file classes/subscription/IndividualSubscription.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscription
 * @ingroup subscription 
 * @see IndividualSubscriptionDAO
 *
 * @brief Basic class describing an individual (non-institutional) subscription.
 */

import('classes.subscription.Subscription');

class IndividualSubscription extends Subscription {

	function IndividualSubscription() {
		parent::Subscription();
	}

	/**
	 * Check whether subscription is valid
	 */
	function isValid($check = SUBSCRIPTION_DATE_BOTH, $checkDate = null) {
		$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		return $subscriptionDao->isValidIndividualSubscription($this->getData('userId'), $this->getData('journalId'), $check, $checkDate);	
	}
}

?>
