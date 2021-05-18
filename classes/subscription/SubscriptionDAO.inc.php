<?php

/**
 * @file classes/subscription/SubscriptionDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionDAO
 * @ingroup subscription
 *
 * @see Subscription
 *
 * @brief Abstract class for retrieving and modifying subscriptions.
 */

namespace APP\subscription;

use PKP\identity\Identity;
use PKP\user\UserDAO;
use PKP\db\DAORegistry;
use PKP\plugins\HookRegistry;

use APP\subscription\Subscription;
use APP\subscription\SubscriptionType;

abstract class SubscriptionDAO extends \PKP\db\DAO
{
    public const SUBSCRIPTION_MEMBERSHIP = 0x02;
    public const SUBSCRIPTION_REFERENCE_NUMBER = 0x03;
    public const SUBSCRIPTION_NOTES = 0x04;

    /**
     * Retrieve subscription by subscription ID.
     *
     * @param $subscriptionId int
     *
     * @return Subscription
     */
    abstract public function getById($subscriptionId);

    /**
     * Retrieve subscription journal ID by subscription ID.
     *
     * @param $subscriptionId int
     *
     * @return int|false
     */
    public function getSubscriptionJournalId($subscriptionId)
    {
        $result = $this->retrieve(
            'SELECT journal_id FROM subscriptions WHERE subscription_id = ?',
            [(int) $subscriptionId]
        );
        $row = $result->current();
        return $row ? $row->journal_id : false;
    }

    /**
     * Retrieve subscription status options as associative array.
     *
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            Subscription::SUBSCRIPTION_STATUS_ACTIVE => 'subscriptions.status.active',
            Subscription::SUBSCRIPTION_STATUS_NEEDS_INFORMATION => 'subscriptions.status.needsInformation',
            Subscription::SUBSCRIPTION_STATUS_NEEDS_APPROVAL => 'subscriptions.status.needsApproval',
            Subscription::SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT => 'subscriptions.status.awaitingManualPayment',
            Subscription::SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT => 'subscriptions.status.awaitingOnlinePayment',
            Subscription::SUBSCRIPTION_STATUS_OTHER => 'subscriptions.status.other'
        ];
    }

    /**
     * Return number of subscriptions with given status.
     *
     * @param status int
     *
     * @return int
     */
    abstract public function getStatusCount($status);

    /**
     * Check if subscription exists for a given subscriptionId.
     *
     * @param $subscriptionId int
     *
     * @return boolean
     */
    abstract public function subscriptionExists($subscriptionId);

    /**
     * Check if subscription exists given a user.
     *
     * @param $subscriptionId int
     * @param $userId int
     *
     * @return boolean
     */
    abstract public function subscriptionExistsByUser($subscriptionId, $userId);

    /**
     * Check if subscription exists given a user and journal.
     *
     * @param $userId int
     *
     * @return boolean
     */
    abstract public function subscriptionExistsByUserForJournal($userId, $journalId);

    /**
     * Insert a subscription.
     *
     * @param $subscription Subscription
     */
    abstract public function insertObject($subscription);

    /**
     * Function to get the ID of the last inserted subscription.
     *
     * @return int
     */
    public function getInsertId()
    {
        return $this->_getInsertId('subscriptions', 'subscription_id');
    }

    /**
     * Update existing subscription.
     *
     * @param $subscription Subscription
     *
     * @return boolean
     */
    abstract public function updateObject($subscription);

    /**
     * Delete subscription by subscription ID.
     *
     * @param $subscriptionId int Subscription ID
     * @param $journalId int Journal ID
     */
    abstract public function deleteById($subscriptionId, $journalId);

    /**
     * Delete subscriptions by journal ID.
     *
     * @param $journalId int
     *
     * @return boolean
     */
    abstract public function deleteByJournalId($journalId);

    /**
     * Delete subscriptions by user ID.
     *
     * @param $userId int
     *
     * @return boolean
     */
    abstract public function deleteByUserId($userId);

    /**
     * Delete all subscriptions by subscription type ID.
     *
     * @param $subscriptionTypeId int
     *
     * @return boolean
     */
    abstract public function deleteByTypeId($subscriptionTypeId);

    /**
     * Retrieve all subscriptions.
     *
     * @param null|mixed $rangeInfo
     *
     * @return object DAOResultFactory containing Subscriptions
     */
    abstract public function getAll($rangeInfo = null);

    /**
     * Retrieve subscriptions matching a particular journal ID.
     *
     * @param $journalId int
     * @param $status int
     * @param $searchField int
     * @param $searchMatch string "is" or "contains" or "startsWith"
     * @param $search String to look in $searchField for
     * @param $dateField int
     * @param $dateFrom String date to search from
     * @param $dateTo String date to search to
     * @param null|mixed $rangeInfo
     *
     * @return object DAOResultFactory containing matching Subscriptions
     */
    abstract public function getByJournalId($journalId, $status = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null);

    /**
     * Retrieve subscriptions matching a particular end date and journal ID.
     *
     * @param $dateEnd string (YYYY-MM-DD)
     * @param $journalId int
     * @param null|mixed $rangeInfo
     *
     * @return object DAOResultFactory containing matching Subscriptions
     */
    abstract public function getByDateEnd($dateEnd, $journalId, $rangeInfo = null);

    /**
     * Function to renew a subscription by dateEnd + duration of subscription type
     * if the subscription is expired, renew to current date + duration
     *
     * @param $subscription Subscription
     *
     * @return boolean
     */
    abstract public function renewSubscription($subscription);

    /**
     * Internal function to generate subscription based search query.
     *
     * @param null|mixed $status
     * @param null|mixed $searchField
     * @param null|mixed $searchMatch
     * @param null|mixed $search
     * @param null|mixed $dateField
     * @param null|mixed $dateFrom
     * @param null|mixed $dateTo
     * @param null|mixed $params
     *
     * @return string
     */
    protected function _generateSearchSQL($status = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, &$params = null)
    {
        $searchSql = '';

        if (!empty($search)) {
            switch ($searchField) {
            case Identity::IDENTITY_SETTING_GIVENNAME:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(COALESCE(ugl.setting_value,ugpl.setting_value)) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(COALESCE(ugl.setting_value,ugpl.setting_value)) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(COALESCE(ugl,ugpl)) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $params[] = $search;
                break;
            case Identity::IDENTITY_SETTING_FAMILYNAME:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(COALESCE(ufl.setting_value,ufpl.setting_value)) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(COALESCE(ufl.setting_value,ufpl.setting_value)) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(COALESCE(ufl.setting_value,ufpl.setting_value)) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $params[] = $search;
                break;
            case UserDAO::USER_FIELD_USERNAME:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(u.username) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(u.username) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(u.username) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $params[] = $search;
                break;
            case UserDAO::USER_FIELD_EMAIL:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(u.email) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(u.email) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(u.email) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $params[] = $search;
                break;
            case self::SUBSCRIPTION_MEMBERSHIP:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(s.membership) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(s.membership) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(s.membership) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $params[] = $search;
                break;
            case self::SUBSCRIPTION_REFERENCE_NUMBER:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(s.reference_number) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(s.reference_number) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(s.reference_number) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $params[] = $search;
                break;
            case self::SUBSCRIPTION_NOTES:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(s.notes) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(s.notes) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(s.notes) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $params[] = $search;
                break;
        }
        }

        if (!empty($dateFrom) || !empty($dateTo)) {
            switch ($dateField) {
            case Subscription::SUBSCRIPTION_DATE_START:
                if (!empty($dateFrom)) {
                    $searchSql .= ' AND s.date_start >= ' . $this->datetimeToDB($dateFrom);
                }
                if (!empty($dateTo)) {
                    $searchSql .= ' AND s.date_start <= ' . $this->datetimeToDB($dateTo);
                }
                break;
            case Subscription::SUBSCRIPTION_DATE_END:
                if (!empty($dateFrom)) {
                    $searchSql .= ' AND s.date_end >= ' . $this->datetimeToDB($dateFrom);
                }
                if (!empty($dateTo)) {
                    $searchSql .= ' AND s.date_end <= ' . $this->datetimeToDB($dateTo);
                }
                break;
        }
        }

        if (!empty($status)) {
            $searchSql .= ' AND s.status = ' . (int) $status;
        }

        return $searchSql;
    }

    /**
     * Generator function to create object.
     *
     * @return Subscription
     */
    abstract public function newDataObject();

    /**
     * Internal function to return a Subscription object from a row.
     *
     * @param $row array
     *
     * @return Subscription
     */
    public function _fromRow($row)
    {
        $subscription = $this->newDataObject();
        $subscription->setId($row['subscription_id']);
        $subscription->setJournalId($row['journal_id']);
        $subscription->setUserId($row['user_id']);
        $subscription->setTypeId($row['type_id']);
        $subscription->setDateStart($this->dateFromDB($row['date_start']));
        $subscription->setDateEnd($this->dateFromDB($row['date_end']));
        $subscription->setStatus($row['status']);
        $subscription->setMembership($row['membership']);
        $subscription->setReferenceNumber($row['reference_number']);
        $subscription->setNotes($row['notes']);

        HookRegistry::call('SubscriptionDAO::_fromRow', [&$subscription, &$row]);

        return $subscription;
    }

    /**
     * Internal function to insert a new Subscription.
     *
     * @param $subscription Subscription
     *
     * @return int Subscription ID
     */
    public function _insertObject($subscription)
    {
        $dateStart = $subscription->getDateStart();
        $dateEnd = $subscription->getDateEnd();
        $this->update(
            sprintf(
                'INSERT INTO subscriptions
				(journal_id, user_id, type_id, date_start, date_end, status, membership, reference_number, notes)
				VALUES
				(?, ?, ?, %s, %s, ?, ?, ?, ?)',
                $dateStart !== null ? $this->dateToDB($dateStart) : 'null',
                $dateEnd !== null ? $this->datetimeToDB($dateEnd) : 'null'
            ),
            [
                (int) $subscription->getJournalId(),
                (int) $subscription->getUserId(),
                (int) $subscription->getTypeId(),
                (int) $subscription->getStatus(),
                $subscription->getMembership(),
                $subscription->getReferenceNumber(),
                $subscription->getNotes()
            ]
        );

        $subscriptionId = $this->getInsertId();
        $subscription->setId($subscriptionId);

        return $subscriptionId;
    }

    /**
     * Internal function to update a Subscription.
     *
     * @param $subscription Subscription
     */
    public function _updateObject($subscription)
    {
        $dateStart = $subscription->getDateStart();
        $dateEnd = $subscription->getDateEnd();
        $this->update(
            sprintf(
                'UPDATE subscriptions
				SET
					journal_id = ?,
					user_id = ?,
					type_id = ?,
					date_start = %s,
					date_end = %s,
					status = ?,
					membership = ?,
					reference_number = ?,
					notes = ?
				WHERE subscription_id = ?',
                $dateStart !== null ? $this->dateToDB($dateStart) : 'null',
                $dateEnd !== null ? $this->datetimeToDB($dateEnd) : 'null'
            ),
            [
                (int) $subscription->getJournalId(),
                (int) $subscription->getUserId(),
                (int) $subscription->getTypeId(),
                (int) $subscription->getStatus(),
                $subscription->getMembership(),
                $subscription->getReferenceNumber(),
                $subscription->getNotes(),
                (int) $subscription->getId()
            ]
        );
    }

    /**
     * Internal function to renew a subscription by dateEnd + duration of subscription type
     * if the subscription is expired, renew to current date + duration
     *
     * @param $subscription Subscription
     *
     * @return boolean
     */
    public function _renewSubscription($subscription)
    {
        if ($subscription->isNonExpiring()) {
            return;
        }

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
        $subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId());

        $duration = $subscriptionType->getDuration();
        $dateEnd = strtotime($subscription->getDateEnd());

        // if the subscription is expired, extend it to today + duration of subscription
        $time = time();
        if ($dateEnd < $time) {
            $dateEnd = $time;
        }

        $subscription->setDateEnd(mktime(23, 59, 59, date('m', $dateEnd) + $duration, date('d', $dateEnd), date('Y', $dateEnd)));
        $this->updateObject($subscription);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\SubscriptionDAO', '\SubscriptionDAO');
    foreach ([
        'SUBSCRIPTION_MEMBERSHIP',
        'SUBSCRIPTION_REFERENCE_NUMBER',
        'SUBSCRIPTION_NOTES',
    ] as $constantName) {
        define($constantName, constant('\SubscriptionDAO::' . $constantName));
    }
}
