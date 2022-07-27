<?php

/**
 * @file classes/subscription/Subscription.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Subscription
 * @ingroup subscription
 *
 * @see SubscriptionDAO
 *
 * @brief Basic class describing a subscription.
 */

namespace APP\subscription;

use APP\facades\Repo;
use PKP\db\DAORegistry;

class Subscription extends \PKP\core\DataObject
{
    public const SUBSCRIPTION_STATUS_ACTIVE = 1;
    public const SUBSCRIPTION_STATUS_NEEDS_INFORMATION = 2;
    public const SUBSCRIPTION_STATUS_NEEDS_APPROVAL = 3;
    public const SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT = 4;
    public const SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT = 5;
    public const SUBSCRIPTION_STATUS_OTHER = 16;

    public const SUBSCRIPTION_DATE_START = 1;
    public const SUBSCRIPTION_DATE_END = 2;
    public const SUBSCRIPTION_DATE_BOTH = 3;

    public const SUBSCRIPTION_YEAR_OFFSET_PAST = '-10';
    public const SUBSCRIPTION_YEAR_OFFSET_FUTURE = '+10';

    //
    // Get/set methods
    //

    /**
     * Get the journal ID of the subscription.
     *
     * @return int
     */
    public function getJournalId()
    {
        return $this->getData('journalId');
    }

    /**
     * Set the journal ID of the subscription.
     *
     * @param int $journalId
     */
    public function setJournalId($journalId)
    {
        $this->setData('journalId', $journalId);
    }

    /**
     * Get the user ID of the subscription.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->getData('userId');
    }

    /**
     * Set the user ID of the subscription.
     *
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->setData('userId', $userId);
    }

    /**
     * Get the user's full name of the subscription.
     *
     * @return string
     */
    public function getUserFullName()
    {
        return Repo::user()->get($this->getData('userId'), true)->getFullName();
    }

    /**
     * Get the user's email of the subscription.
     *
     * @return string
     */
    public function getUserEmail()
    {
        return Repo::user()->get($this->getData('userId'), true)->getEmail();
    }

    /**
     * Get the subscription type ID of the subscription.
     *
     * @return int
     */
    public function getTypeId()
    {
        return $this->getData('typeId');
    }

    /**
     * Set the subscription type ID of the subscription.
     *
     * @param int $typeId
     */
    public function setTypeId($typeId)
    {
        $this->setData('typeId', $typeId);
    }

    /**
     * Get the subscription type name of the subscription.
     *
     * @return string
     */
    public function getSubscriptionTypeName()
    {
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        return $subscriptionTypeDao->getSubscriptionTypeName($this->getData('typeId'));
    }

    /**
     * Get the subscription type name of the subscription.
     *
     * @return string
     */
    public function getSubscriptionTypeSummaryString()
    {
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionType = $subscriptionTypeDao->getById($this->getData('typeId'));
        return $subscriptionType->getSummaryString();
    }

    /**
     * Get the subscription type institutional flag for the subscription.
     *
     * @return bool
     */
    public function getSubscriptionTypeInstitutional()
    {
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        return $subscriptionTypeDao->getSubscriptionTypeInstitutional($this->getData('typeId'));
    }

    /**
     * Check whether the subscription type is non-expiring for the subscription.
     *
     * @return bool
     */
    public function isNonExpiring()
    {
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionType = $subscriptionTypeDao->getById($this->getTypeId());
        return $subscriptionType->getNonExpiring();
    }

    /**
     * Get subscription start date.
     *
     * @return date (YYYY-MM-DD)
     */
    public function getDateStart()
    {
        return $this->getData('dateStart');
    }

    /**
     * Set subscription start date.
     *
     * @param date $dateStart (YYYY-MM-DD)
     */
    public function setDateStart($dateStart)
    {
        $this->setData('dateStart', $dateStart);
    }

    /**
     * Get subscription end date.
     *
     * @return date (YYYY-MM-DD)
     */
    public function getDateEnd()
    {
        return $this->getData('dateEnd');
    }

    /**
     * Set subscription end date.
     *
     * @param date $dateEnd (YYYY-MM-DD)
     */
    public function setDateEnd($dateEnd)
    {
        $this->setData('dateEnd', $dateEnd);
    }

    /**
     * Get subscription status.
     *
     * @return int SUBSCRIPTION_STATUS_...
     */
    public function getStatus()
    {
        return $this->getData('status');
    }

    /**
     * Set subscription status.
     *
     * @param int $status SUBSCRIPTION_STATUS_...
     */
    public function setStatus($status)
    {
        $this->setData('status', $status);
    }

    /**
     * Get subscription status string.
     *
     * @return string
     */
    public function getStatusString()
    {
        switch ($this->getData('status')) {
            case self::SUBSCRIPTION_STATUS_ACTIVE:
                return __('subscriptions.status.active');
            case self::SUBSCRIPTION_STATUS_NEEDS_INFORMATION:
                return __('subscriptions.status.needsInformation');
            case self::SUBSCRIPTION_STATUS_NEEDS_APPROVAL:
                return __('subscriptions.status.needsApproval');
            case self::SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT:
                return __('subscriptions.status.awaitingManualPayment');
            case self::SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT:
                return __('subscriptions.status.awaitingOnlinePayment');
            case self::SUBSCRIPTION_STATUS_OTHER:
                return __('subscriptions.status.other');
            default:
                return __('subscriptions.status');
        }
    }

    /**
     * Get subscription membership.
     *
     * @return string
     */
    public function getMembership()
    {
        return $this->getData('membership');
    }

    /**
     * Set subscription membership.
     *
     * @param string $membership
     */
    public function setMembership($membership)
    {
        $this->setData('membership', $membership);
    }

    /**
     * Get subscription reference number.
     *
     * @return string
     */
    public function getReferenceNumber()
    {
        return $this->getData('referenceNumber');
    }

    /**
     * Set subscription reference number.
     *
     * @param string $referenceNumber
     */
    public function setReferenceNumber($referenceNumber)
    {
        $this->setData('referenceNumber', $referenceNumber);
    }

    /**
     * Get subscription notes.
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->getData('notes');
    }

    /**
     * Set subscription notes.
     *
     * @param string $notes
     */
    public function setNotes($notes)
    {
        $this->setData('notes', $notes);
    }

    /**
     * Check whether subscription is expired
     *
     * @return bool
     */
    public function isExpired()
    {
        if (strtotime($this->getData('dateEnd')) < time()) {
            return true;
        } else {
            return false;
        }
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\Subscription', '\Subscription');
    foreach ([
        'SUBSCRIPTION_STATUS_ACTIVE',
        'SUBSCRIPTION_STATUS_NEEDS_INFORMATION',
        'SUBSCRIPTION_STATUS_NEEDS_APPROVAL',
        'SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT',
        'SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT',
        'SUBSCRIPTION_STATUS_OTHER',
        'SUBSCRIPTION_DATE_START',
        'SUBSCRIPTION_DATE_END',
        'SUBSCRIPTION_DATE_BOTH',
        'SUBSCRIPTION_YEAR_OFFSET_PAST',
        'SUBSCRIPTION_YEAR_OFFSET_FUTURE',
    ] as $constantName) {
        define($constantName, constant('\Subscription::' . $constantName));
    }
}
