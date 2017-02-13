<?php

/**
 * @file classes/subscription/SubscriptionDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionDAO
 * @ingroup subscription
 * @see Subscription
 *
 * @brief Abstract class for retrieving and modifying subscriptions.
 */

import('classes.subscription.Subscription');
import('classes.subscription.SubscriptionType');

define('SUBSCRIPTION_USER',			0x01);
define('SUBSCRIPTION_MEMBERSHIP',		0x02);
define('SUBSCRIPTION_REFERENCE_NUMBER',		0x03);
define('SUBSCRIPTION_NOTES',			0x04);

class SubscriptionDAO extends DAO {

	/**
	 * Retrieve subscription by subscription ID.
	 * @param $subscriptionId int
	 * @return Subscription
	 */
	function getSubscription($subscriptionId) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Retrieve subscription journal ID by subscription ID.
	 * @param $subscriptionId int
	 * @return int
	 */
	function getSubscriptionJournalId($subscriptionId) {
		$result = $this->retrieve(
			'SELECT journal_id FROM subscriptions WHERE subscription_id = ?', $subscriptionId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve subscription status options as associative array.
	 * @return array
	 */
	function getStatusOptions() {
		$statusOptions = array(
			SUBSCRIPTION_STATUS_ACTIVE => 'subscriptions.status.active',
			SUBSCRIPTION_STATUS_NEEDS_INFORMATION => 'subscriptions.status.needsInformation',
			SUBSCRIPTION_STATUS_NEEDS_APPROVAL => 'subscriptions.status.needsApproval',
			SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT => 'subscriptions.status.awaitingManualPayment',
			SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT => 'subscriptions.status.awaitingOnlinePayment',
			SUBSCRIPTION_STATUS_OTHER => 'subscriptions.status.other'
		);

		return $statusOptions;
	}

	/**
	 * Return number of subscriptions with given status.
	 * @param status int
	 * @return int
	 */
	function getStatusCount($status) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Check if subscription exists for a given subscriptionId.
	 * @param $subscriptionId int
	 * @return boolean
	 */
	function subscriptionExists($subscriptionId) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Check if subscription exists given a user.
	 * @param $subscriptionId int
	 * @param $userId int
	 * @return boolean
	 */
	function subscriptionExistsByUser($subscriptionId, $userId) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Check if subscription exists given a user and journal.
	 * @param $subscriptionId int
	 * @param $userId int
	 * @return boolean
	 */
	function subscriptionExistsByUserForJournal($userId, $journalId) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Insert a new subscription.
	 * @param $subscription Subscription
	 * @return int
	 */
	function insertSubscription($subscription) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Function to get the ID of the last inserted subscription.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('subscriptions', 'subscription_id');
	}

	/**
	 * Update existing subscription.
	 * @param $subscription Subscription
	 * @return boolean
	 */
	function updateSubscription($subscription) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Delete subscription by subscription ID.
	 * @param $subscriptionId int
	 */
	function deleteSubscriptionById($subscriptionId) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Delete subscriptions by journal ID.
	 * @param $journalId int
	 * @return boolean
	 */
	function deleteSubscriptionsByJournal($journalId) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Delete subscriptions by user ID.
	 * @param $userId int
	 * @return boolean
	 */
	function deleteSubscriptionsByUserId($userId) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Delete all subscriptions by subscription type ID.
	 * @param $subscriptionTypeId int
	 * @return boolean
	 */
	function deleteSubscriptionsByTypeId($subscriptionTypeId) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Retrieve all subscriptions.
	 * @return object DAOResultFactory containing Subscriptions
	 */
	function getSubscriptions($rangeInfo = null) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Retrieve subscriptions matching a particular journal ID.
	 * @param $journalId int
	 * @param $status int
	 * @param $searchField int
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @return object DAOResultFactory containing matching Subscriptions
	 */
	function getSubscriptionsByJournalId($journalId, $status = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Retrieve subscriptions matching a particular end date and journal ID.
	 * @param $dateEnd date (YYYY-MM-DD)
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching Subscriptions
	 */
	function getSubscriptionsByDateEnd($dateEnd, $journalId, $rangeInfo = null) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Function to renew a subscription by dateEnd + duration of subscription type
	 * if the subscription is expired, renew to current date + duration
	 * @param $subscription Subscription
	 * @return boolean
	 */
	function renewSubscription($subscription) {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Internal function to generate user based search query.
	 * @return string
	 */
	function _generateUserNameSearchSQL($search, $searchMatch, $prefix, &$params) {
		$first_last = $this->concat($prefix.'first_name', '\' \'', $prefix.'last_name');
		$first_middle_last = $this->concat($prefix.'first_name', '\' \'', $prefix.'middle_name', '\' \'', $prefix.'last_name');
		$last_comma_first = $this->concat($prefix.'last_name', '\', \'', $prefix.'first_name');
		$last_comma_first_middle = $this->concat($prefix.'last_name', '\', \'', $prefix.'first_name', '\' \'', $prefix.'middle_name');
		if ($searchMatch === 'is') {
			$searchSql = " AND (LOWER({$prefix}last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
		} elseif ($searchMatch === 'contains') {
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
			$search = '%' . $search . '%';
		} else { // $searchMatch === 'startsWith'
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
			$search = $search . '%';
		}
		$params[] = $params[] = $params[] = $params[] = $params[] = $search;
		return $searchSql;
	}

	/**
	 * Internal function to generate subscription based search query.
	 * @return string
	 */
	function _generateSearchSQL($status = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, &$params) {

		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case SUBSCRIPTION_USER:
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 'u.', $params);
				break;
			case SUBSCRIPTION_MEMBERSHIP:
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
			case SUBSCRIPTION_REFERENCE_NUMBER:
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
			case SUBSCRIPTION_NOTES:
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

		if (!empty($dateFrom) || !empty($dateTo)) switch($dateField) {
			case SUBSCRIPTION_DATE_START:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND s.date_start >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND s.date_start <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBSCRIPTION_DATE_END:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND s.date_end >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND s.date_end <= ' . $this->datetimeToDB($dateTo);
				}
				break;
		}

		if (!empty($status)) {
			$searchSql .= ' AND s.status = ' . (int) $status;
		}

		return $searchSql;
	}

	/**
	 * Generator function to create object.
	 * @return Subscription
	 */
	function newDataObject() {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Internal function to return a Subscription object from a row.
	 * @param $row array
	 * @return Subscription
	 */
	function _fromRow($row) {
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

		HookRegistry::call('SubscriptionDAO::_fromRow', array(&$subscription, &$row));

		return $subscription;
	}

	/**
	 * Internal function to insert a new Subscription.
	 * @param $subscription Subscription
	 * @return int Subscription ID
	 */
	function _insertSubscription($subscription) {
		$this->update(
			sprintf('INSERT INTO subscriptions
				(journal_id, user_id, type_id, date_start, date_end, status, membership, reference_number, notes)
				VALUES
				(?, ?, ?, %s, %s, ?, ?, ?, ?)',
				$this->dateToDB($subscription->getDateStart()), $this->datetimeToDB($subscription->getDateEnd())),
			array(
				$subscription->getJournalId(),
				$subscription->getUserId(),
				$subscription->getTypeId(),
				$subscription->getStatus(),
				$subscription->getMembership(),
				$subscription->getReferenceNumber(),
				$subscription->getNotes()
			)
		);

		$subscriptionId = $this->getInsertId();
		$subscription->setId($subscriptionId);

		return $subscriptionId;
	}

	/**
	 * Internal function to update a Subscription.
	 * @param $subscription Subscription
	 * @return boolean
	 */
	function _updateSubscription($subscription) {
		$returner = $this->update(
			sprintf('UPDATE subscriptions
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
				$this->dateToDB($subscription->getDateStart()), $this->datetimeToDB($subscription->getDateEnd())),
			array(
				$subscription->getJournalId(),
				$subscription->getUserId(),
				$subscription->getTypeId(),
				$subscription->getStatus(),
				$subscription->getMembership(),
				$subscription->getReferenceNumber(),
				$subscription->getNotes(),
				$subscription->getId()
			)
		);

		return $returner;
	}

	/**
	 * Internal function to renew a subscription by dateEnd + duration of subscription type
	 * if the subscription is expired, renew to current date + duration
	 * @param $subscription Subscription
	 * @return boolean
	 */
	function _renewSubscription($subscription) {
		if ($subscription->isNonExpiring()) return;

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType = $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

		$duration = $subscriptionType->getDuration();
		$dateEnd = strtotime($subscription->getDateEnd());

		// if the subscription is expired, extend it to today + duration of subscription
		$time = time();
		if ($dateEnd < $time ) $dateEnd = $time;

		$subscription->setDateEnd(mktime(23, 59, 59, date("m", $dateEnd)+$duration, date("d", $dateEnd), date("Y", $dateEnd)));
		$this->updateSubscription($subscription);
	}
}

?>
