<?php

/**
 * @file classes/subscription/IndividualSubscriptionDAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscriptionDAO
 *
 * @ingroup subscription
 *
 * @see IndividualSubscription
 *
 * @brief Operations for retrieving and modifying IndividualSubscription objects.
 */

namespace APP\subscription;

use APP\core\Application;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use PKP\core\Core;
use PKP\db\DAOResultFactory;
use PKP\db\DBResultRange;
use PKP\facades\Locale;
use PKP\identity\Identity;
use PKP\plugins\Hook;

class IndividualSubscriptionDAO extends SubscriptionDAO
{
    /**
     * Retrieve an individual subscription by subscription ID.
     *
     * @param int $subscriptionId Subscription ID
     * @param int $journalId Optional journal ID
     *
     * @return IndividualSubscription
     */
    public function getById($subscriptionId, $journalId = null)
    {
        $params = [(int) $subscriptionId];
        if ($journalId) {
            $params[] = (int) $journalId;
        }
        $result = $this->retrieve(
            'SELECT	s.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	st.institutional = 0
				AND s.subscription_id = ?
				' . ($journalId ? ' AND s.journal_id = ?' : ''),
            $params
        );
        $row = (array) $result->current();
        return $row ? $this->_fromRow($row) : null;
    }

    /**
     * Retrieve individual subscription by user ID for journal.
     *
     * @param int $userId
     * @param int $journalId
     *
     * @return IndividualSubscription
     */
    public function getByUserIdForJournal($userId, $journalId)
    {
        $result = $this->retrieveRange(
            'SELECT s.*
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = ?
			AND s.journal_id = ?',
            [(int) $userId, (int) $journalId]
        );
        $row = (array) $result->current();
        return $row ? $this->_fromRow($row) : null;
    }

    /**
     * Retrieve individual subscriptions by user ID.
     *
     * @param int $userId
     * @param ?DBResultRange $rangeInfo
     *
     * @return DAOResultFactory<IndividualSubscription> Object containing IndividualSubscriptions
     */
    public function getByUserId($userId, $rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT s.*
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = ?',
            [(int) $userId],
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Return number of individual subscriptions with given status for journal.
     *
     * @param int $journalId
     * @param int $status
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
            'SELECT	COUNT(*) AS row_count
			FROM	subscriptions s,
				subscription_types st
			WHERE	s.type_id = st.type_id AND
				st.institutional = 0 AND
				s.journal_id = ?
			' . ($status !== null ? ' AND s.status = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? $row->row_count : 0;
    }

    /**
     * Get the number of individual subscriptions for a particular journal.
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
     * Check if an individual subscription exists for a given subscriptionId.
     *
     * @param int $subscriptionId
     *
     * @return bool
     */
    public function subscriptionExists($subscriptionId)
    {
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.subscription_id = ?',
            [(int) $subscriptionId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Check if an individual subscription exists for a given user.
     *
     * @param int $subscriptionId
     * @param int $userId
     *
     * @return bool
     */
    public function subscriptionExistsByUser($subscriptionId, $userId)
    {
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.subscription_id = ?
			AND s.user_id = ?',
            [(int) $subscriptionId, (int) $userId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Check if an individual subscription exists for a given user and journal.
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
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = ?
			AND s.journal_id = ?',
            [(int) $userId, (int) $journalId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Generator function to create object.
     *
     * @return IndividualSubscription
     */
    public function newDataObject()
    {
        return new IndividualSubscription();
    }

    /**
     * Internal function to return an IndividualSubscription object from a row.
     *
     * @param array $row
     *
     * @return IndividualSubscription
     *
     * @hook IndividualSubscriptionDAO::_fromRow [[&$individualSubscription, &$row]]
     */
    public function _fromRow($row)
    {
        $individualSubscription = parent::_fromRow($row);
        Hook::call('IndividualSubscriptionDAO::_fromRow', [&$individualSubscription, &$row]);

        return $individualSubscription;
    }

    /**
     * Insert a new individual subscription.
     *
     * @param IndividualSubscription $individualSubscription
     *
     * @return int
     */
    public function insertObject($individualSubscription)
    {
        return $this->_insertObject($individualSubscription);
    }

    /**
     * Update an existing individual subscription.
     *
     * @param IndividualSubscription $individualSubscription
     */
    public function updateObject($individualSubscription)
    {
        $this->_updateObject($individualSubscription);
    }

    /**
     * Delete an individual subscription by subscription ID.
     */
    public function deleteById(int $subscriptionId, ?int $journalId = null): int
    {
        return DB::table('subscriptions')
            ->where('subscription_id', '=', $subscriptionId)
            ->when($journalId !== null, fn ($q) => $q->where('journal_id', '=', $journalId))
            ->delete();
    }

    /**
     * Delete individual subscriptions by journal ID.
     *
     * @param int $journalId
     *
     * @return bool
     */
    public function deleteByJournalId($journalId)
    {
        $result = $this->retrieve('SELECT subscription_id FROM subscriptions WHERE journal_id = ?', [(int) $journalId]);

        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }
        return true;
    }

    /**
     * Delete individual subscriptions by user ID.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function deleteByUserId($userId)
    {
        $result = $this->retrieve('SELECT subscription_id FROM subscriptions WHERE user_id = ?', [(int) $userId]);

        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }
        return true;
    }

    /**
     * Delete individual subscription by user ID and journal ID.
     *
     * @param int $userId
     * @param int $journalId
     *
     * @return bool
     */
    public function deleteByUserIdForJournal($userId, $journalId)
    {
        $result = $this->retrieve('SELECT subscription_id FROM subscriptions WHERE user_id = ? AND journal_id = ?', [(int) $userId, (int) $journalId]);

        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }
        return true;
    }

    /**
     * Delete all individual subscriptions by subscription type ID.
     *
     * @param int $subscriptionTypeId
     *
     * @return bool
     */
    public function deleteByTypeId($subscriptionTypeId)
    {
        $result = $this->retrieve('SELECT subscription_id FROM subscriptions WHERE type_id = ?', [(int) $subscriptionTypeId]);

        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }

        return true;
    }

    /**
     * Retrieve all individual subscriptions.
     *
     * @param ?DBResultRange $rangeInfo
     *
     * @return DAOResultFactory<IndividualSubscription> Object containing IndividualSubscriptions
     */
    public function getAll($rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT s.*
			FROM
			subscriptions s,
			subscription_types st,
			users u
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = u.user_id
			ORDER BY u.user_id, s.subscription_id',
            [],
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Retrieve individual subscriptions matching a particular journal ID.
     *
     * @param int $journalId
     * @param int $status
     * @param int $searchField
     * @param string $searchMatch "is" or "contains" or "startsWith"
     * @param string $search to look in $searchField for
     * @param int $dateField
     * @param string $dateFrom date to search from
     * @param string $dateTo date to search to
     * @param ?DBResultRange $rangeInfo
     *
     * @return DAOResultFactory<IndividualSubscription> Object containing matching IndividualSubscriptions
     */
    public function getByJournalId($journalId, $status = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null)
    {
        $locale = Locale::getLocale();
        // the users register for the site, thus
        // the site primary locale should be the default locale
        $site = Application::get()->getRequest()->getSite();
        $primaryLocale = $site->getPrimaryLocale();
        $q = DB::table('subscriptions', 's')
            ->join('subscription_types AS st', 's.type_id', '=', 'st.type_id')
            ->join('users AS u', 's.user_id', '=', 'u.user_id')
            ->leftJoin(
                'user_settings AS ugl',
                fn (JoinClause $j) =>
                $j->on('u.user_id', '=', 'ugl.user_id')
                    ->where('ugl.setting_name', '=', Identity::IDENTITY_SETTING_GIVENNAME)
                    ->where('ugl.locale', '=', $locale)
            )
            ->leftJoin(
                'user_settings AS ugpl',
                fn (JoinClause $j) =>
                $j->on('u.user_id', '=', 'ugpl.user_id')
                    ->where('ugpl.setting_name', '=', Identity::IDENTITY_SETTING_GIVENNAME)
                    ->where('ugpl.locale', '=', $primaryLocale)
            )
            ->leftJoin(
                'user_settings AS ufl',
                fn (JoinClause $j) =>
                $j->on('u.user_id', '=', 'ufl.user_id')
                    ->where('ufl.setting_name', '=', Identity::IDENTITY_SETTING_FAMILYNAME)
                    ->where('ufl.locale', '=', $locale)
            )
            ->leftJoin(
                'user_settings AS ufpl',
                fn (JoinClause $j) =>
                $j->on('u.user_id', '=', 'ufpl.user_id')
                    ->where('ufpl.setting_name', '=', Identity::IDENTITY_SETTING_FAMILYNAME)
                    ->where('ufpl.locale', '=', $primaryLocale)
            )
            ->where('st.institutional', '=', 0)
            ->where('s.journal_id', '=', $journalId)
            ->orderBy('u.user_id')
            ->orderBy('s.subscription_id')
            ->select(
                's.*',
                DB::raw('COALESCE(ugl.setting_value, ugpl.setting_value) AS user_given'),
                DB::raw("CASE WHEN ugl.setting_value <> '' THEN ufl.setting_value ELSE ufpl.setting_value END AS user_family")
            );
        $this->applySearchFilters($q, $status, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, $params);

        $result = $this->retrieveRange($q, [], $rangeInfo);
        return new DAOResultFactory($result, $this, '_fromRow', [], $q, [], $rangeInfo); // Counted in subscription grid paging
    }

    /**
     * Check whether user with ID has a valid individual subscription for a given journal.
     *
     * @param int $userId
     * @param int $journalId
     * @param int $check Check using either start date, end date, or both (default)
     * @param string $checkDate (YYYY-MM-DD) Use this date instead of current date
     *
     * @return bool
     */
    public function isValidIndividualSubscription($userId, $journalId, $check = Subscription::SUBSCRIPTION_DATE_BOTH, $checkDate = null)
    {
        if (empty($userId) || empty($journalId)) {
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
                $dateSql = sprintf('%s >= s.date_start AND %s <= s.date_end', $checkDate, $checkDate);
        }

        $result = $this->retrieve(
            '
			SELECT	s.subscription_id AS subscription_id
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	s.user_id = ?
				AND s.journal_id = ?
				AND s.status = ' . Subscription::SUBSCRIPTION_STATUS_ACTIVE . '
				AND st.institutional = 0
				AND (st.duration IS NULL OR (' . $dateSql . '))
				AND (st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
				OR st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')',
            [(int) $userId, (int) $journalId]
        );
        $row = $result->current();
        return $row ? (bool) $row->subscription_id : false;
    }

    /**
     * Retrieve active individual subscriptions matching a particular end date and journal ID.
     *
     * @param string $dateEnd (YYYY-MM-DD)
     * @param int $journalId
     * @param ?DBResultRange $rangeInfo
     *
     * @return DAOResultFactory<IndividualSubscription> Object containing matching IndividualSubscriptions
     */
    public function getByDateEnd($dateEnd, $journalId, $rangeInfo = null)
    {
        $dateEnd = explode('-', $dateEnd);

        $result = $this->retrieveRange(
            'SELECT	s.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN users u ON (u.user_id = s.user_id)
			WHERE	s.status = ' . Subscription::SUBSCRIPTION_STATUS_ACTIVE . '
				AND st.institutional = 0
				AND EXTRACT(YEAR FROM s.date_end) = ?
				AND EXTRACT(MONTH FROM s.date_end) = ?
				AND EXTRACT(DAY FROM s.date_end) = ?
				AND s.journal_id = ?
			ORDER BY u.user_id, s.subscription_id',
            [$dateEnd[0], $dateEnd[1], $dateEnd[2], (int) $journalId],
            $rangeInfo
        );
        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Renew an individual subscription by dateEnd + duration of subscription type
     * if the individual subscription is expired, renew to current date + duration
     *
     * @param IndividualSubscription $individualSubscription
     */
    public function renewSubscription($individualSubscription)
    {
        return $this->_renewSubscription($individualSubscription);
    }
}
