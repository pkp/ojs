<?php

/**
 * SubscriptionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package subscription
 *
 * Class for Subscription DAO.
 * Operations for retrieving and modifying Subscription objects.
 *
 * $Id$
 */

class SubscriptionDAO extends DAO {

	/**
	 * Constructor.
	 */
	function SubscriptionDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a subscription by subscription ID.
	 * @param $subscriptionId int
	 * @return Subscription
	 */
	function &getSubscription($subscriptionId) {
		$result = &$this->retrieve(
			'SELECT * FROM subscriptions WHERE subscription_id = ?', $subscriptionId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnSubscriptionFromRow($result->GetRowAssoc(false));
		}
	}

	/**
	 * Retrieve subscription journal ID by subscription ID.
	 * @param $subscriptionId int
	 * @return int
	 */
	function getSubscriptionJournalId($subscriptionId) {
		$result = &$this->retrieve(
			'SELECT journal_id FROM subscriptions WHERE subscription_id = ?', $subscriptionId
		);
		
		return isset($result->fields[0]) ? $result->fields[0] : 0;	
	}

	/**
	 * Retrieve subscription ID by user ID.
	 * @param $userId int
	 * @param $journalId int
	 * @return int
	 */
	function getSubscriptionByUser($userId, $journalId) {
		$result = &$this->retrieve(
			'SELECT subscription_id
				FROM subscriptions
				WHERE user_id = ?
				AND journal_id = ?',
			array(
				$userId,
				$journalId
			)
		);
		
		return isset($result->fields[0]) ? $result->fields[0] : 0;	
	}

	/**
	 * Check if a subscription exists for a given user and journal.
	 * @param $userId int
	 * @param $journalId int
	 * @return boolean
	 */
	function subscriptionExistsByUser($userId, $journalId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM subscriptions
				WHERE user_id = ?
				AND   journal_id = ?',
			array(
				$userId,
				$journalId
			)
		);
		return isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;
	}

	/**
	 * Internal function to return a Subscription object from a row.
	 * @param $row array
	 * @return Subscription
	 */
	function &_returnSubscriptionFromRow(&$row) {
		$subscription = &new Subscription();
		$subscription->setSubscriptionId($row['subscription_id']);
		$subscription->setJournalId($row['journal_id']);
		$subscription->setUserId($row['user_id']);
		$subscription->setTypeId($row['type_id']);
		$subscription->setDateStart($row['date_start']);
		$subscription->setDateEnd($row['date_end']);
		$subscription->setMembership($row['membership']);
		$subscription->setDomain($row['domain']);
		$subscription->setIPRange($row['ip_range']);
		
		return $subscription;
	}

	/**
	 * Insert a new Subscription.
	 * @param $subscription Subscription
	 * @return boolean 
	 */
	function insertSubscription(&$subscription) {
		$ret = $this->update(
			'INSERT INTO subscriptions
				(journal_id, user_id, type_id, date_start, date_end, membership, domain, ip_range)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$subscription->getJournalId(),
				$subscription->getUserId(),
				$subscription->getTypeId(),
				$subscription->getDateStart(),
				$subscription->getDateEnd(),
				$subscription->getMembership(),
				$subscription->getDomain(),
				$subscription->getIPRange()
			)
		);
		if ($ret) {
			$subscription->setSubscriptionId($this->getInsertSubscriptionId());
		}
		return $ret;
	}

	/**
	 * Update an existing subscription.
	 * @param $subscription Subscription
	 * @return boolean
	 */
	function updateSubscription(&$subscription) {
		return $this->update(
			'UPDATE subscriptions
				SET
					journal_id = ?,
					user_id = ?,
					type_id = ?,
					date_start = ?,
					date_end = ?,
					membership = ?,
					domain = ?,
					ip_range = ?
				WHERE subscription_id = ?',
			array(
				$subscription->getJournalId(),
				$subscription->getUserId(),
				$subscription->getTypeId(),
				$subscription->getDateStart(),
				$subscription->getDateEnd(),
				$subscription->getMembership(),
				$subscription->getDomain(),
				$subscription->getIPRange(),
				$subscription->getSubscriptionId()
			)
		);
	}

	/**
	 * Delete a subscription type.
	 * @param $subscriptionType SubscriptionType
	 * @return boolean 
	 */
	function deleteSubscriptionType(&$subscriptionType) {
		return $this->deleteSubscriptionTypeById($subscriptionType->getTypeId());
	}

	/**
	 * Delete a subscription by subscription ID.
	 * @param $subscriptionId int
	 * @return boolean
	 */
	function deleteSubscriptionById($subscriptionId) {
		return $this->update(
			'DELETE FROM subscriptions WHERE subscription_id = ?', $subscriptionId
			);
	}

	/**
	 * Delete all subscriptions by subscription type ID.
	 * @param $subscriptionTypeId int
	 * @return boolean
	 */
	function deleteSubscriptionByTypeId($subscriptionTypeId) {
		return $this->update(
			'DELETE FROM subscriptions WHERE type_id = ?', $subscriptionTypeId
			);
	}

	/**
	 * Retrieve an array of subscriptions matching a particular journal ID.
	 * @param $journalId int
	 * @return array matching Subscriptions
	 */
	function &getSubscriptionsByJournalId($journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM subscriptions WHERE journal_id = ?', $journalId	
		);
	
		$subscriptions = array();
		
		while (!$result->EOF) {
			$subscriptions[] = &$this->_returnSubscriptionFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $subscriptions;
	}

	/**
	 * Get the ID of the last inserted subscription.
	 * @return int
	 */
	function getInsertSubscriptionId() {
		return $this->getInsertId('subscriptions', 'subscription_id');
	}
	
}

?>
