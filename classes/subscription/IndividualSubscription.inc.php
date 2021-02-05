<?php

/**
 * @file classes/subscription/IndividualSubscription.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscription
 * @ingroup subscription 
 * @see IndividualSubscriptionDAO
 *
 * @brief Basic class describing an individual (non-institutional) subscription.
 */

import('classes.subscription.Subscription');

class IndividualSubscription extends Subscription {

	/**
	 * Check whether subscription is valid
	 */
	function isValid($check = SUBSCRIPTION_DATE_BOTH, $checkDate = null) {
		$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $subscriptionDao IndividualSubscriptionDAO */
		return $subscriptionDao->isValidIndividualSubscription($this->getData('userId'), $this->getData('journalId'), $check, $checkDate);	
	}
}


