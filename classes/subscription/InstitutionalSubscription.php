<?php

/**
 * @defgroup subscription Subscription
 * Implement subscriptions, subscription management, and subscription checking.
 */

/**
 * @file classes/subscription/InstitutionalSubscription.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InstitutionalSubscription
 *
 * @ingroup subscription
 *
 * @see InstitutionalSubscriptionDAO
 *
 * @brief Basic class describing an institutional subscription.
 */

namespace APP\subscription;

use PKP\db\DAORegistry;

class InstitutionalSubscription extends Subscription
{
    //
    // Get/set methods
    //

    /**
     * Get the institution ID of the subscription.
     */
    public function getInstitutionId(): int
    {
        return $this->getData('institutionId');
    }

    /**
     * Set the institution ID of the subscription.
     */
    public function setInstitutionId(int $institutionId): void
    {
        $this->setData('institutionId', $institutionId);
    }

    /**
     * Get the mailing address of the institutionalSubscription.
     */
    public function getInstitutionMailingAddress(): string
    {
        return $this->getData('mailingAddress');
    }

    /**
     * Set the mailing address of the institutionalSubscription.
     */
    public function setInstitutionMailingAddress(string $mailingAddress): void
    {
        $this->setData('mailingAddress', $mailingAddress);
    }

    /**
     * Get institutionalSubscription domain string.
     */
    public function getDomain(): string
    {
        return $this->getData('domain');
    }

    /**
     * Set institutionalSubscription domain string.
     */
    public function setDomain(string $domain): void
    {
        $this->setData('domain', $domain);
    }

    /**
     * Check whether subscription is valid
     *
     * @param ?int $check SUBSCRIPTION_DATE_... Test using either start date, end date, or both (default)
     * @param ?string $checkDate (YYYY-MM-DD) Use this date instead of current date
     *
     * @return int|false Found subscription ID, or false for none.
     */
    public function isValid(string $domain, string $IP, ?int $check = self::SUBSCRIPTION_DATE_BOTH, ?string $checkDate = null)
    {
        $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $subscriptionDao */
        return $subscriptionDao->isValidInstitutionalSubscription($domain, $IP, $this->getData('journalId'), $check, $checkDate);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\InstitutionalSubscription', '\InstitutionalSubscription');
}
