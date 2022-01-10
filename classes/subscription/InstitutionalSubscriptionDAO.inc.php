<?php

/**
 * @file classes/subscription/InstitutionalSubscriptionDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InstitutionalSubscriptionDAO
 * @ingroup subscription
 *
 * @see InstitutionalSubscription
 *
 * @brief Operations for retrieving and modifying InstitutionalSubscription objects.
 */

namespace APP\subscription;

use PKP\core\Core;
use PKP\db\DAOResultFactory;
use PKP\plugins\HookRegistry;

class InstitutionalSubscriptionDAO extends SubscriptionDAO
{
    public const SUBSCRIPTION_INSTITUTION_NAME = 0x20;
    public const SUBSCRIPTION_DOMAIN = 0x21;
    public const SUBSCRIPTION_IP_RANGE = 0x22;

    /**
     * Retrieve an institutional subscription by subscription ID.
     *
     * @param int $subscriptionId Subscription ID
     * @param int $journalId Journal ID
     *
     * @return InstitutionalSubscription
     */
    public function getById($subscriptionId, $journalId = null)
    {
        $params = [(int) $subscriptionId];
        if ($journalId) {
            $params[] = (int) $journalId;
        }
        $result = $this->retrieve(
            'SELECT s.*, iss.*
            FROM subscriptions s
            JOIN subscription_types st ON s.type_id = st.type_id
            JOIN institutional_subscriptions iss ON s.subscription_id = iss.subscription_id
            WHERE
                st.institutional = 1
                AND s.subscription_id = ?
                ' . ($journalId ? ' AND s.journal_id = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Retrieve institutional subscriptions by user ID.
     *
     * @param int $userId
     * @param null|mixed $rangeInfo
     *
     * @return object DAOResultFactory containing matching InstitutionalSubscriptions
     */
    public function getByUserId($userId, $rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT s.*, iss.*
            FROM subscriptions s
            JOIN subscription_types st ON st.type_id = s.type_id
            JOIN institutional_subscriptions iss ON s.subscription_id = iss.subscription_id
            WHERE
                st.institutional = 1
                AND s.user_id = ?',
            [(int) $userId],
            $rangeInfo
        );
        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Retrieve institutional subscriptions by user ID and journal ID.
     *
     * @param int $userId
     * @param int $journalId
     * @param RangeInfo $rangeInfo
     *
     * @return object DAOResultFactory containing matching InstitutionalSubscriptions
     */
    public function getByUserIdForJournal($userId, $journalId, $rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT s.*, iss.*
            FROM subscriptions s
            JOIN subscription_types st ON s.type_id = st.type_id
            JOIN institutional_subscriptions iss ON s.subscription_id = iss.subscription_id
            WHERE
                st.institutional = 1
                AND s.user_id = ?
                AND s.journal_id = ?',
            [(int) $userId, (int) $journalId],
            $rangeInfo
        );
        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Retrieve institutional subscriptions by institution name.
     *
     * @param string $institutionName Institution name
     * @param int $journalId Journal ID
     * @param bool $exactMatch True iff the match on institution name should be exact
     * @param RangeInfo $rangeInfo
     *
     * @return object DAOResultFactory containing matching InstitutionalSubscriptions
     */
    public function getByInstitutionName($institutionName, $journalId, $exactMatch = true, $rangeInfo = null)
    {
        $matchType = $exactMatch ? '=' : 'LIKE';
        $result = $this->retrieveRange(
            "SELECT s.*, iss.*
            FROM subscriptions s
            JOIN subscription_types st ON s.type_id = st.type_id
            JOIN institutional_subscriptions iss ON s.subscription_id = iss.subscription_id
            WHERE
                st.institutional = 1
                AND LOWER(iss.institution_name) $matchType LOWER(?)
                AND s.journal_id = ?",
            [$institutionName, (int) $journalId],
            $rangeInfo
        );
        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Return number of institutional subscriptions with given status for journal.
     *
     * @param int $journalId
     * @param null|mixed $status
     *
     * @return int
     */
    public function getStatusCount($journalId, $status = null)
    {
        $params = [(int) $journalId];
        if ($status !== null) {
            $params[] = (int) $status;
        }
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count
            FROM subscriptions s
            JOIN subscription_types st ON s.type_id = st.type_id
            WHERE
                st.institutional = 1
                AND s.journal_id = ?
                ' . ($status !== null ? ' AND s.status = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? $row->row_count : 0;
    }

    /**
     * Get the number of institutional subscriptions for a particular journal.
     *
     * @param int $journalId
     *
     * @return int
     */
    public function getSubscribedUserCount($journalId)
    {
        return $this->getStatusCount($journalId);
    }

    /**
     * Check if an institutional subscription exists for a given subscriptionId.
     *
     * @param int $subscriptionId Subscription ID
     * @param int $journalId Optional journal ID
     *
     * @return bool
     */
    public function subscriptionExists($subscriptionId, $journalId = null)
    {
        $params = [(int) $subscriptionId];
        if ($journalId) {
            $params[] = (int) $journalId;
        }
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count
            FROM subscriptions s
            JOIN subscription_types st ON s.type_id = st.type_id
            WHERE
                st.institutional = 1
                AND s.subscription_id = ?
                ' . ($journalId ? ' AND s.journal_id = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Check if an institutional subscription exists for a given user.
     *
     * @param int $subscriptionId Subscription ID
     * @param int $userId User ID
     *
     * @return bool
     */
    public function subscriptionExistsByUser($subscriptionId, $userId)
    {
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count
            FROM subscriptions s
            JOIN subscription_types st ON s.type_id = st.type_id
            WHERE
                st.institutional = 1
                AND s.subscription_id = ?
                AND s.user_id = ?',
            [(int) $subscriptionId, (int) $userId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Check if an institutional subscription exists for a given user and journal.
     *
     * @param int $userId
     * @param int $journalId
     *
     * @return bool
     */
    public function subscriptionExistsByUserForJournal($userId, $journalId)
    {
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count
            FROM subscriptions s
            JOIN subscription_types st ON s.type_id = st.type_id
            WHERE
                st.institutional = 1
                AND s.user_id = ?
                AND s.journal_id = ?',
            [(int) $userId, (int) $journalId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Check if an institutional subscription exists for a given institution name and journal.
     *
     * @param string $institutionName
     * @param int $journalId
     * @param bool $exactMatch
     *
     * @return bool
     */
    public function subscriptionExistsByInstitutionName($institutionName, $journalId, $exactMatch = true)
    {
        $matchType = $exactMatch ? '=' : 'LIKE';
        $result = $this->retrieve(
            "SELECT COUNT(*) AS row_count
            FROM subscriptions s
            JOIN subscription_types st ON s.type_id = st.type_id
            JOIN institutional_subscriptions iss ON s.subscription_id = iss.subscription_id
            WHERE
                st.institutional = 1
                AND LOWER(iss.institution_name) $matchType LOWER(?)
                AND s.journal_id = ?",
            [$institutionName, (int) $journalId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Insert a new institutional subscription.
     *
     * @param InstitutionalSubscription $institutionalSubscription
     *
     * @return int
     */
    public function insertObject($institutionalSubscription)
    {
        $subscriptionId = null;
        if ($this->_insertObject($institutionalSubscription)) {
            $subscriptionId = $institutionalSubscription->getId();

            $this->update(
                'INSERT INTO institutional_subscriptions
                (subscription_id, institution_name, mailing_address, domain)
                VALUES
                (?, ?, ?, ?)',
                [
                    (int) $subscriptionId,
                    $institutionalSubscription->getInstitutionName(),
                    $institutionalSubscription->getInstitutionMailingAddress(),
                    $institutionalSubscription->getDomain()
                ]
            );

            $this->_insertSubscriptionIPRanges($subscriptionId, $institutionalSubscription->getIPRanges());
        }

        return $subscriptionId;
    }

    /**
     * Update an existing institutional subscription.
     *
     * @param InstitutionalSubscription $institutionalSubscription
     *
     * @return bool
     */
    public function updateObject($institutionalSubscription)
    {
        $this->_updateObject($institutionalSubscription);

        $this->update(
            'UPDATE institutional_subscriptions
            SET
                institution_name = ?,
                mailing_address = ?,
                domain = ?
            WHERE subscription_id = ?',
            [
                $institutionalSubscription->getInstitutionName(),
                $institutionalSubscription->getInstitutionMailingAddress(),
                $institutionalSubscription->getDomain(),
                (int) $institutionalSubscription->getId()
            ]
        );

        $this->_deleteSubscriptionIPRanges($institutionalSubscription->getId());
        $this->_insertSubscriptionIPRanges($institutionalSubscription->getId(), $institutionalSubscription->getIPRanges());
    }

    /**
     * Delete an institutional subscription by subscription ID.
     *
     * @param int $subscriptionId
     * @param null|mixed $journalId
     */
    public function deleteById($subscriptionId, $journalId = null)
    {
        if (!$this->subscriptionExists($subscriptionId, $journalId)) {
            return;
        }

        $this->update('DELETE FROM subscriptions WHERE subscription_id = ?', [(int) $subscriptionId]);
        $this->update('DELETE FROM institutional_subscriptions WHERE subscription_id = ?', [(int) $subscriptionId]);
        $this->_deleteSubscriptionIPRanges($subscriptionId);
    }

    /**
     * Delete institutional subscriptions by journal ID.
     *
     * @param int $journalId
     */
    public function deleteByJournalId($journalId)
    {
        $result = $this->retrieve('SELECT s.subscription_id AS subscription_id FROM subscriptions s WHERE s.journal_id = ?', [(int) $journalId]);
        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }
    }

    /**
     * Delete institutional subscriptions by user ID.
     *
     * @param int $userId
     */
    public function deleteByUserId($userId)
    {
        $result = $this->retrieve('SELECT s.subscription_id AS subscription_id FROM subscriptions s WHERE s.user_id = ?', [(int) $userId]);
        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }
    }

    /**
     * Delete institutional subscriptions by user ID and journal ID.
     *
     * @param int $userId User ID
     * @param int $journalId Journal ID
     */
    public function deleteByUserIdForJournal($userId, $journalId)
    {
        $result = $this->retrieve('SELECT s.subscription_id AS subscription_id FROM subscriptions s WHERE s.user_id = ? AND s.journal_id = ?', [(int) $userId, (int) $journalId]);
        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }
    }

    /**
     * Delete all institutional subscriptions by subscription type ID.
     *
     * @param int $subscriptionTypeId Subscription type ID
     */
    public function deleteByTypeId($subscriptionTypeId)
    {
        $result = $this->retrieve('SELECT s.subscription_id AS subscription_id FROM subscriptions s WHERE s.type_id = ?', [(int) $subscriptionTypeId]);
        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }
    }

    /**
     * Retrieve all institutional subscriptions.
     *
     * @param null|mixed $rangeInfo
     *
     * @return object DAOResultFactory containing InstitutionalSubscriptions
     */
    public function getAll($rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT s.*, iss.*
            FROM subscriptions s
            JOIN subscription_types st ON s.type_id = st.type_id
            JOIN institutional_subscriptions iss ON s.subscription_id = iss.subscription_id
            WHERE st.institutional = 1
            ORDER BY iss.institution_name ASC, s.subscription_id',
            [],
            $rangeInfo
        );
        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Retrieve institutional subscriptions matching a particular journal ID.
     *
     * @param int $journalId
     * @param int $status
     * @param int $searchField
     * @param string $searchMatch "is" or "contains" or "startsWith"
     * @param string $search to look in $searchField for
     * @param int $dateField
     * @param string $dateFrom date to search from
     * @param string $dateTo date to search to
     * @param null|mixed $rangeInfo
     *
     * @return object DAOResultFactory containing matching Subscriptions
     */
    public function getByJournalId($journalId, $status = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null)
    {
        $params = [(int) $journalId];
        $ipRangeSql = '';
        $searchSql = $this->_generateSearchSQL($status, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, $params);

        if (!empty($search)) {
            switch ($searchField) {
            case self::SUBSCRIPTION_INSTITUTION_NAME:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(iss.institution_name) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(iss.institution_name) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(iss.institution_name) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $params[] = $search;
                break;
            case self::SUBSCRIPTION_DOMAIN:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(iss.domain) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(iss.domain) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(iss.domain) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $params[] = $search;
                break;
            case self::SUBSCRIPTION_IP_RANGE:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(isip.ip_string) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(isip.ip_string) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(isip.ip_string) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $params[] = $search;
                $ipRangeSql = 'JOIN institutional_subscription_ip isip ON s.subscription_id = isip.subscription_id';
                break;
            }
        }


        $result = $this->retrieveRange(
            $sql = "SELECT DISTINCT s.*, iss.institution_name, iss.mailing_address, iss.domain
            FROM subscriptions s
            JOIN subscription_types st ON s.type_id = st.type_id
            JOIN users u ON s.user_id = u.user_id
            JOIN institutional_subscriptions iss ON s.subscription_id = iss.subscription_id
            $ipRangeSql
            WHERE
                st.institutional = 1
                AND s.journal_id = ?
                $searchSql
            ORDER BY iss.institution_name ASC, s.subscription_id",
            $params,
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow', [], $sql, $params, $rangeInfo); // Counted in subscription grid paging
    }

    /**
     * Check whether there is a valid institutional subscription for a given journal.
     *
     * @param string $domain
     * @param string $IP
     * @param int $journalId
     * @param int $check Test using either start date, end date, or both (default)
     * @param date $checkDate (YYYY-MM-DD) Use this date instead of current date
     *
     * @return int|false Found subscription ID, or false for none.
     */
    public function isValidInstitutionalSubscription($domain, $IP, $journalId, $check = Subscription::SUBSCRIPTION_DATE_BOTH, $checkDate = null)
    {
        if (empty($journalId) || (empty($domain) && empty($IP))) {
            return false;
        }

        $today = $this->dateToDB(Core::getCurrentDate());

        if ($checkDate == null) {
            $checkDate = $today;
        } else {
            $checkDate = $this->dateToDB($checkDate);
        }

        switch ($check) {
            case Subscription::SUBSCRIPTION_DATE_START:
                $dateSql = sprintf('%s >= s.date_start AND %s >= s.date_start', $checkDate, $today);
                break;
            case Subscription::SUBSCRIPTION_DATE_END:
                $dateSql = sprintf('%s <= s.date_end AND %s >= s.date_start', $checkDate, $today);
                break;
            default:
                $dateSql = sprintf('%s BETWEEN s.date_start AND s.date_end', $checkDate);
        }

        // Check if domain match
        if (!empty($domain)) {
            $result = $this->retrieve(
                'SELECT iss.subscription_id
                FROM institutional_subscriptions iss
                JOIN subscriptions s ON iss.subscription_id = s.subscription_id
                JOIN subscription_types st ON s.type_id = st.type_id
                WHERE
                    POSITION(UPPER(LPAD(iss.domain, LENGTH(iss.domain) + 1, \'.\')) IN UPPER(LPAD(?, LENGTH(?) + 1, \'.\'))) <> 0
                    AND iss.domain <> \'\'
                    AND s.journal_id = ?
                    AND s.status = ' . Subscription::SUBSCRIPTION_STATUS_ACTIVE . '
                    AND st.institutional = 1
                    AND (
                        st.duration IS NULL
                        OR (' . $dateSql . ')
                    )
                    AND (
                        st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
                        OR st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . '
                    )',
                [$domain, $domain, (int) $journalId]
            );
            $row = $result->current();
            if ($row) {
                return $row->subscription_id;
            }
        }

        // Check for IP match
        if (!empty($IP)) {
            $IP = sprintf('%u', ip2long($IP));

            $result = $this->retrieve(
                'SELECT isip.subscription_id
                FROM institutional_subscription_ip isip
                JOIN subscriptions s ON isip.subscription_id = s.subscription_id
                JOIN subscription_types st ON s.type_id = st.type_id
                WHERE
                    s.journal_id = ?
                    AND ? BETWEEN isip.ip_start AND COALESCE(isip.ip_end, isip.ip_start)
                    AND s.status = ' . Subscription::SUBSCRIPTION_STATUS_ACTIVE . '
                    AND st.institutional = 1
                    AND (
                        st.duration IS NULL
                        OR (' . $dateSql . ')
                    )
                    AND (
                        st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
                        OR st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . '
                    )',
                [(int) $journalId, $IP]
            );
            $row = $result->current();
            if ($row) {
                return $row->subscription_id;
            }
        }

        return false;
    }

    /**
     * Retrieve active institutional subscriptions matching a particular end date and journal ID.
     *
     * @param string $dateEnd (YYYY-MM-DD)
     * @param int $journalId
     * @param null|mixed $rangeInfo
     *
     * @return object DAOResultFactory containing matching InstitutionalSubscriptions
     */
    public function getByDateEnd($dateEnd, $journalId, $rangeInfo = null)
    {
        $dateEnd = explode('-', $dateEnd);
        $result = $this->retrieveRange(
            'SELECT s.*, iss.*
            FROM subscriptions s
            JOIN subscription_types st ON s.type_id = st.type_id
            JOIN institutional_subscriptions iss ON s.subscription_id = iss.subscription_id
            WHERE
                s.status = ' . Subscription::SUBSCRIPTION_STATUS_ACTIVE . '
                AND st.institutional = 1
                AND EXTRACT(YEAR FROM s.date_end) = ?
                AND EXTRACT(MONTH FROM s.date_end) = ?
                AND EXTRACT(DAY FROM s.date_end) = ?
                AND s.journal_id = ?
            ORDER BY iss.institution_name ASC, s.subscription_id',
            [
                ...$dateEnd,
                (int) $journalId
            ],
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Renew an institutional subscription by dateEnd + duration of subscription type
     * if the institutional subscription is expired, renew to current date + duration
     *
     * @param InstitutionalSubscription $institutionalSubscription
     *
     * @return bool
     */
    public function renewSubscription($institutionalSubscription)
    {
        return $this->_renewSubscription($institutionalSubscription);
    }

    /**
     * Generator function to create object.
     *
     * @return InstitutionalSubscription
     */
    public function newDataObject()
    {
        return new InstitutionalSubscription();
    }

    /**
     * Internal function to return an InstitutionalSubscription object from a row.
     *
     * @param array $row
     *
     * @return InstitutionalSubscription
     */
    public function _fromRow($row)
    {
        $institutionalSubscription = parent::_fromRow($row);

        $institutionalSubscription->setInstitutionName($row['institution_name']);
        $institutionalSubscription->setInstitutionMailingAddress($row['mailing_address']);
        $institutionalSubscription->setDomain($row['domain']);

        $ipResult = $this->retrieve(
            'SELECT ip_string
            FROM institutional_subscription_ip
            WHERE subscription_id = ?
            ORDER BY institutional_subscription_ip_id ASC',
            [(int) $institutionalSubscription->getId()]
        );

        $ipRanges = [];
        foreach ($ipResult as $ipRow) {
            $ipRanges[] = $ipRow->ip_string;
        }
        $institutionalSubscription->setIPRanges($ipRanges);

        HookRegistry::call('InstitutionalSubscriptionDAO::_fromRow', [&$institutionalSubscription, &$row]);

        return $institutionalSubscription;
    }

    /**
     * Internal function to insert institutional subscription IP ranges.
     *
     * @param int $subscriptionId
     * @param array $ipRanges
     *
     * @return bool
     */
    public function _insertSubscriptionIPRanges($subscriptionId, $ipRanges)
    {
        if (empty($ipRanges)) {
            return true;
        }

        if (empty($subscriptionId)) {
            return false;
        }

        $returner = true;

        foreach ($ipRanges as $curIPString) {
            $ipStart = null;
            $ipEnd = null;

            // Parse and check single IP string
            if (strpos($curIPString, InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_RANGE) === false) {

                // Check for wildcards in IP
                if (strpos($curIPString, InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_WILDCARD) === false) {

                    // Get non-CIDR IP
                    if (strpos($curIPString, '/') === false) {
                        $ipStart = sprintf('%u', ip2long(trim($curIPString)));

                    // Convert CIDR IP to IP range
                    } else {
                        [$cidrIPString, $cidrBits] = explode('/', trim($curIPString));

                        if ($cidrBits == 0) {
                            $cidrMask = 0;
                        } else {
                            $cidrMask = (0xffffffff << (32 - $cidrBits));
                        }

                        $ipStart = sprintf('%u', ip2long($cidrIPString) & $cidrMask);

                        if ($cidrBits != 32) {
                            $ipEnd = sprintf('%u', ip2long($cidrIPString) | (~$cidrMask & 0xffffffff));
                        }
                    }

                    // Convert wildcard IP to IP range
                } else {
                    $ipStart = sprintf('%u', ip2long(str_replace(InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_WILDCARD, '0', trim($curIPString))));
                    $ipEnd = sprintf('%u', ip2long(str_replace(InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_WILDCARD, '255', trim($curIPString))));
                }

                // Convert wildcard IP range to IP range
            } else {
                [$ipStart, $ipEnd] = explode(InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_RANGE, $curIPString);

                // Replace wildcards in start and end of range
                $ipStart = sprintf('%u', ip2long(str_replace(InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_WILDCARD, '0', trim($ipStart))));
                $ipEnd = sprintf('%u', ip2long(str_replace(InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_WILDCARD, '255', trim($ipEnd))));
            }

            // Insert IP or IP range
            if ($ipStart != null && $returner) {
                $returner = (bool) $this->update(
                    'INSERT INTO institutional_subscription_ip
                    (subscription_id, ip_string, ip_start, ip_end)
                    VALUES
                    (?, ?, ?, ?)',
                    [
                        (int) $subscriptionId,
                        $curIPString,
                        $ipStart,
                        $ipEnd
                    ]
                );
            } else {
                $returner = false;
                break;
            }
        }

        return $returner;
    }

    /**
     * Internal function to delete subscription ip ranges by subscription ID.
     *
     * @param int $subscriptionId
     *
     * @return bool
     */
    public function _deleteSubscriptionIPRanges($subscriptionId)
    {
        return $this->update(
            'DELETE FROM institutional_subscription_ip WHERE subscription_id = ?',
            [(int) $subscriptionId]
        );
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\InstitutionalSubscriptionDAO', '\InstitutionalSubscriptionDAO');
    foreach ([
        'SUBSCRIPTION_INSTITUTION_NAME',
        'SUBSCRIPTION_DOMAIN',
        'SUBSCRIPTION_IP_RANGE',
    ] as $constantName) {
        define($constantName, constant('\InstitutionalSubscriptionDAO::' . $constantName));
    }
}
