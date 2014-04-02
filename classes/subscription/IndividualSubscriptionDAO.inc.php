<?php

/**
 * @file classes/subscription/IndividualSubscriptionDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscriptionDAO
 * @ingroup subscription
 * @see IndividualSubscription
 *
 * @brief Operations for retrieving and modifying IndividualSubscription objects.
 */

import('classes.subscription.SubscriptionDAO');
import('classes.subscription.IndividualSubscription');

class IndividualSubscriptionDAO extends SubscriptionDAO {
	/**
	 * Retrieve an individual subscription by subscription ID.
	 * @param $subscriptionId int
	 * @return IndividualSubscription
	 */
	function &getSubscription($subscriptionId) {
		$result =& $this->retrieve(
			'SELECT s.*
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.subscription_id = ?',
			$subscriptionId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnSubscriptionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve individual subscription by user ID for journal.
	 * @param $userId int
	 * @param $journalId int
	 * @return IndividualSubscriptions
	 */
	function &getSubscriptionByUserForJournal($userId, $journalId) {
		$result =& $this->retrieveRange(
			'SELECT s.*
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = ?
			AND s.journal_id = ?',
			array(
				$userId,
				$journalId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnSubscriptionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve individual subscriptions by user ID.
	 * @param $userId int
	 * @return object DAOResultFactory containing IndividualSubscriptions
	 */
	function &getSubscriptionsByUser($userId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT s.*
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = ?',
			$userId,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSubscriptionFromRow');

		return $returner;
	}

	/**
	 * Retrieve individual subscription ID by user ID.
	 * @param $userId int
	 * @param $journalId int
	 * @return int
	 */
	function getSubscriptionIdByUser($userId, $journalId) {
		$result =& $this->retrieve(
			'SELECT s.subscription_id
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = ?
			AND s.journal_id = ?',
			array(
				$userId,
				$journalId
			)
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;	

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Return number of individual subscriptions with given status for journal.
	 * @param status int 
	 * @return int
	 */
	function getStatusCount($journalId, $status = null) {
		$params = array((int) $journalId);
		if ($status !== null) $params[] = (int) $status;

		$result =& $this->retrieve(
			'SELECT	COUNT(*)
			FROM	subscriptions s,
				subscription_types st
			WHERE	s.type_id = st.type_id AND
				st.institutional = 0 AND
				s.journal_id = ?
			' . ($status !== null?' AND s.status = ?':''),
			$params
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the number of individual subscriptions for a particular journal.
	 * @param $journalId int
	 * @return int
	 */
	function getSubscribedUserCount($journalId) {
		return $this->getStatusCount($journalId);
	}

	/**
	 * Check if an individual subscription exists for a given subscriptionId.
	 * @param $subscriptionId int
	 * @return boolean
	 */
	function subscriptionExists($subscriptionId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*)
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.subscription_id = ?',
			$subscriptionId
		);

		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if an individual subscription exists for a given user.
	 * @param $subscriptionId int
	 * @param $userId int
	 * @return boolean
	 */
	function subscriptionExistsByUser($subscriptionId, $userId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*)
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.subscription_id = ?
			AND s.user_id = ?',
			array(
				$subscriptionId,
				$userId
			)
		);

		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if an individual subscription exists for a given user and journal.
	 * @param $userId int
	 * @param $journalId int
	 * @return boolean
	 */
	function subscriptionExistsByUserForJournal($userId, $journalId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*)
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = ?
			AND s.journal_id = ?',
			array(
				$userId,
				$journalId
			)
		);

		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Generator function to create object.
	 * @return IndividualSubscription
	 */
	function createObject() {
		return new IndividualSubscription();
	}

	/**
	 * Internal function to return an IndividualSubscription object from a row.
	 * @param $row array
	 * @return IndividualSubscription
	 */
	function &_returnSubscriptionFromRow(&$row) {
		$individualSubscription = parent::_returnSubscriptionFromRow($row);
		HookRegistry::call('IndividualSubscriptionDAO::_returnSubscriptionFromRow', array(&$individualSubscription, &$row));

		return $individualSubscription;
	}

	/**
	 * Insert a new individual subscription.
	 * @param $individualSubscription IndividualSubscription
	 * @return int 
	 */
	function insertSubscription(&$individualSubscription) {
		return $this->_insertSubscription($individualSubscription);
	}

	/**
	 * Update an existing individual subscription.
	 * @param $individualSubscription IndividualSubscription
	 * @return boolean
	 */
	function updateSubscription(&$individualSubscription) {
		return $this->_updateSubscription($individualSubscription);
	}

	/**
	 * Delete an individual subscription by subscription ID.
	 * @param $subscriptionId int
	 * @return boolean
	 */
	function deleteSubscriptionById($subscriptionId) {
		if ($this->subscriptionExists($subscriptionId)) {
			return $this->update(
				'DELETE
				FROM
				subscriptions
				WHERE subscription_id = ?',
				$subscriptionId
			);
		} else {
			return false;
		}
	}

	/**
	 * Delete individual subscriptions by journal ID.
	 * @param $journalId int
	 * @return boolean
	 */
	function deleteSubscriptionsByJournal($journalId) {
		$result =& $this->retrieve(
			'SELECT s.subscription_id
			FROM
			subscriptions s
			WHERE s.journal_id = ?',
			$journalId
		);

		$returner = true;
		if ($result->RecordCount() != 0) {
			while (!$result->EOF) {
				$subscriptionId = $result->fields[0];
				$returner = $this->deleteSubscriptionById($subscriptionId);
				if (!$returner) { 
					break;
				}
				$result->moveNext();
			}
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Delete individual subscriptions by user ID.
	 * @param $userId int
	 * @return boolean
	 */
	function deleteSubscriptionsByUserId($userId) {
		$result =& $this->retrieve(
			'SELECT s.subscription_id
			FROM
			subscriptions s
			WHERE s.user_id = ?',
			$userId
		);

		$returner = true;
		if ($result->RecordCount() != 0) {
			while (!$result->EOF) {
				$subscriptionId = $result->fields[0];
				$returner = $this->deleteSubscriptionById($subscriptionId);
				if (!$returner) { 
					break;
				}
				$result->moveNext();
			}
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Delete individual subscription by user ID and journal ID.
	 * @param $userId int
	 * @param $journalId int
	 * @return boolean
	 */
	function deleteSubscriptionsByUserIdForJournal($userId, $journalId) {
		$result =& $this->retrieve(
			'SELECT s.subscription_id
			FROM
			subscriptions s
			WHERE s.user_id = ?
			AND s.journal_id = ?',
			array (
				$userId,
				$journalId
			)
		);

		$returner = true;
		if ($result->RecordCount() != 0) {
			while (!$result->EOF) {
				$subscriptionId = $result->fields[0];
				$returner = $this->deleteSubscriptionById($subscriptionId);
				if (!$returner) { 
					break;
				}
				$result->moveNext();
			}
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Delete all individual subscriptions by subscription type ID.
	 * @param $subscriptionTypeId int
	 * @return boolean
	 */
	function deleteSubscriptionsByTypeId($subscriptionTypeId) {
		$result =& $this->retrieve(
			'SELECT s.subscription_id
			FROM
			subscriptions s
			WHERE s.type_id = ?',
			$subscriptionTypeId
		);

		$returner = true;
		if ($result->RecordCount() != 0) {
			while (!$result->EOF) {
				$subscriptionId = $result->fields[0];
				$returner = $this->deleteSubscriptionById($subscriptionId);
				if (!$returner) { 
					break;
				}
				$result->moveNext();
			}
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all individual subscriptions.
	 * @return object DAOResultFactory containing IndividualSubscriptions
	 */
	function &getSubscriptions($rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT s.*
			FROM
			subscriptions s,
			subscription_types st,
			users u
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = u.user_id
			ORDER BY
			u.last_name ASC,
			s.subscription_id',
			false,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSubscriptionFromRow');

		return $returner;
	}

	/**
	 * Retrieve all individual subscribed users.
	 * @return object DAOResultFactory containing IndividualSubscriptions
	 */
	function &getSubscribedUsers($journalId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT	u.*
			FROM	subscriptions s,
				subscription_types st,
				users u
			WHERE	s.type_id = st.type_id AND
				st.institutional = 0 AND
				s.user_id = u.user_id AND
				s.journal_id = ?
			ORDER BY u.last_name ASC, s.subscription_id',
			array((int) $journalId),
			$rangeInfo
		);

		$userDao =& DAORegistry::getDAO('UserDAO');
		$returner = new DAOResultFactory($result, $userDao, '_returnUserFromRow');

		return $returner;
	}

	/**
	 * Retrieve individual subscriptions matching a particular journal ID.
	 * @param $journalId int
	 * @param $status int
	 * @param $searchField int
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int 
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @return object DAOResultFactory containing matching IndividualSubscriptions
	 */
	function &getSubscriptionsByJournalId($journalId, $status = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {

		$params = array($journalId);
		$searchSql = parent::_generateSearchSQL($status, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, $params);

		$sql = 'SELECT s.*
				FROM
				subscriptions s,
				subscription_types st,
				users u
				WHERE s.type_id = st.type_id
				AND st.institutional = 0
				AND s.user_id = u.user_id
				AND s.journal_id = ?';
 
		$result =& $this->retrieveRange(
			$sql . ' ' . $searchSql . ' ORDER BY u.last_name ASC, s.subscription_id',
			count($params)===1?array_shift($params):$params,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSubscriptionFromRow');

		return $returner;
	}

	/**
	 * Check whether user with ID has a valid individual subscription for a given journal.
	 * @param $userId int
	 * @param $journalId int
	 * @param $check int Check using either start date, end date, or both (default)
	 * @param $checkDate date (YYYY-MM-DD) Use this date instead of current date
	 * @return int 
	 */
	function isValidIndividualSubscription($userId, $journalId, $check = SUBSCRIPTION_DATE_BOTH, $checkDate = null) {
		if (empty($userId) || empty($journalId)) {
			return false;
		}
		$returner = false;

		$today = $this->dateToDB(Core::getCurrentDate()); 

		if ($checkDate == null) {
			$checkDate = $today;
		} else {
			$checkDate = $this->dateToDB($checkDate);
		}

		switch($check) {
			case SUBSCRIPTION_DATE_START:
				$dateSql = sprintf('%s >= s.date_start AND %s >= s.date_start', $checkDate, $today);
				break;
			case SUBSCRIPTION_DATE_END:
				$dateSql = sprintf('%s <= s.date_end AND %s >= s.date_start', $checkDate, $today);
				break;
			default:
				$dateSql = sprintf('%s >= s.date_start AND %s <= s.date_end', $checkDate, $checkDate);
		}

		$nonExpiringSql = "AND ((st.non_expiring = 1) OR (st.non_expiring = 0 AND ($dateSql)))";

		$result =& $this->retrieve('
			SELECT s.subscription_id
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.user_id = ?
			AND s.journal_id = ? 
			AND s.status = ' . SUBSCRIPTION_STATUS_ACTIVE . '
			AND s.type_id = st.type_id
			AND st.institutional = 0 '
			. $nonExpiringSql .
			' AND (st.format = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE . ' 
				OR st.format = ' . SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')',
			array(
				$userId,
				$journalId
			)
		);

		if ($result->RecordCount() != 0) {
			$returner = $result->fields[0];
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve active individual subscriptions matching a particular end date and journal ID.
	 * @param $dateEnd date
	 * @param $journalId int
	 * @param $reminderType int SUBSCRIPTION_REMINDER_FIELD_..._EXPIRY
	 * @return object DAOResultFactory containing matching IndividualSubscriptions
	 */
	function &getSubscriptionsToRemind($dateEnd, $journalId, $reminderType, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			sprintf(
				'SELECT	s.*
				FROM	subscriptions s,
					subscription_types st,
					users u
				WHERE	s.type_id = st.type_id
					AND s.status = ?
					AND st.institutional = 0
					AND u.user_id = s.user_id
					AND s.date_end <= %s
					AND s.' . ($reminderType==SUBSCRIPTION_REMINDER_FIELD_BEFORE_EXPIRY?'date_reminded_before':'date_reminded_after') . ' IS NULL
					AND s.journal_id = ?
				ORDER BY u.last_name ASC, s.subscription_id',
				$this->datetimeToDB($dateEnd)
			), array(
				SUBSCRIPTION_STATUS_ACTIVE,
				(int) $journalId
			),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSubscriptionFromRow');

		return $returner;
	}

	/**
	 * Renew an individual subscription by dateEnd + duration of subscription type
	 * if the individual subscription is expired, renew to current date + duration  
	 * @param $individualSubscription IndividualSubscription
	 * @return boolean
	 */	
	function renewSubscription(&$individualSubscription) {
		return $this->_renewSubscription($individualSubscription);
	}
}

?>
