<?php

/**
 * @defgroup subscription Subscription
 * Implement subscriptions, subscription management, and subscription checking.
 */

/**
 * @file classes/subscription/InstitutionalSubscription.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InstitutionalSubscription
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
    public const SUBSCRIPTION_IP_RANGE_RANGE = '-';
    public const SUBSCRIPTION_IP_RANGE_WILDCARD = '*';

    //
    // Get/set methods
    //

    /**
     * Get the institution name of the institutionalSubscription.
     *
     * @return string
     */
    public function getInstitutionName()
    {
        return $this->getData('institutionName');
    }

    /**
     * Set the institution name of the institutionalSubscription.
     *
     * @param string $institutionName
     */
    public function setInstitutionName($institutionName)
    {
        return $this->setData('institutionName', $institutionName);
    }

    /**
     * Get the mailing address of the institutionalSubscription.
     *
     * @return string
     */
    public function getInstitutionMailingAddress()
    {
        return $this->getData('mailingAddress');
    }

    /**
     * Set the mailing address of the institutionalSubscription.
     *
     * @param string $mailingAddress
     */
    public function setInstitutionMailingAddress($mailingAddress)
    {
        return $this->setData('mailingAddress', $mailingAddress);
    }

    /**
     * Get institutionalSubscription domain string.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->getData('domain');
    }

    /**
     * Set institutionalSubscription domain string.
     *
     * @param string $domain
     */
    public function setDomain($domain)
    {
        return $this->setData('domain', $domain);
    }

    /**
     * Get institutionalSubscription ip ranges.
     *
     * @return array
     */
    public function getIPRanges()
    {
        return $this->getData('ipRanges');
    }

    /**
     * Get institutionalSubscription ip ranges string.
     *
     * @return string
     */
    public function getIPRangesString()
    {
        $ipRanges = $this->getData('ipRanges');
        $numRanges = count($ipRanges);
        $ipRangesString = '';

        for ($i = 0; $i < $numRanges; $i++) {
            $ipRangesString .= $ipRanges[$i];
            if ($i + 1 < $numRanges) {
                $ipRangesString .= '\n';
            }
        }

        return $ipRangesString;
    }

    /**
     * Set institutionalSubscription ip ranges.
     *
     * @param array $ipRanges
     */
    public function setIPRanges($ipRanges)
    {
        return $this->setData('ipRanges', $ipRanges);
    }

    /**
     * Check whether subscription is valid
     *
     * @param string $domain
     * @param string $IP
     * @param int $check SUBSCRIPTION_DATE_... Test using either start date, end date, or both (default)
     * @param date $checkDate (YYYY-MM-DD) Use this date instead of current date
     *
     * @return int|false Found subscription ID, or false for none.
     */
    public function isValid($domain, $IP, $check = self::SUBSCRIPTION_DATE_BOTH, $checkDate = null)
    {
        $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $subscriptionDao */
        return $subscriptionDao->isValidInstitutionalSubscription($domain, $IP, $this->getData('journalId'), $check, $checkDate);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\InstitutionalSubscription', '\InstitutionalSubscription');
    foreach ([
        'SUBSCRIPTION_IP_RANGE_RANGE',
        'SUBSCRIPTION_IP_RANGE_WILDCARD',
    ] as $constantName) {
        define($constantName, constant('\InstitutionalSubscription::' . $constantName));
    }
}
