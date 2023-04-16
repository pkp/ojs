<?php

/**
 * @file classes/subscription/IndividualSubscription.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscription
 *
 * @ingroup subscription
 *
 * @see IndividualSubscriptionDAO
 *
 * @brief Basic class describing an individual (non-institutional) subscription.
 */

namespace APP\subscription;

use PKP\db\DAORegistry;

class IndividualSubscription extends Subscription
{
    /**
     * Check whether subscription is valid
     *
     * @param null|mixed $checkDate
     */
    public function isValid($check = self::SUBSCRIPTION_DATE_BOTH, $checkDate = null)
    {
        $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
        return $subscriptionDao->isValidIndividualSubscription($this->getData('userId'), $this->getData('journalId'), $check, $checkDate);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\IndividualSubscription', '\IndividualSubscription');
}
