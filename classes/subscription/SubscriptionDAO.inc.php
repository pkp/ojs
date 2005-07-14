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

import('subscription.Subscription');
import('subscription.SubscriptionType');

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
	function getSubscriptionIdByUser($userId, $journalId) {
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
		$subscription->setSubscriptionId($this->getInsertSubscriptionId());
		return $subscription->getSubscriptionId();
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
	 * Delete subscriptions by journal ID.
	 * @param $journalId int
	 */
	function deleteSubscriptionsByJournal($journalId) {
		return $this->update(
			'DELETE FROM subscriptions WHERE journal_id = ?', $journalId
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
	 * @return object DAOResultFactory containing matching Subscriptions
	 */
	function &getSubscriptionsByJournalId($journalId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM subscriptions WHERE journal_id = ?', $journalId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnSubscriptionFromRow');
		return $returner;
	}

	/**
	 * Check whether there is a valid subscription for a given journal.
	 * @param $domain string
	 * @param $IP string
	 * @param $userId int
	 * @param $journalId int
	 * @return boolean
	 */
	function isValidSubscription($domain, $IP, $userId, $journalId) {
		$valid = false;

		if ($domain != null) {
			$valid = $this->isValidSubscriptionByDomain($domain, $journalId);
			if ($valid) { return true; }
		}	

		if ($IP != null) {
			$valid = $this->isValidSubscriptionByIP($IP, $journalId);
			if ($valid) { return true; }
		}

		if ($userId != null) {
			return $this->isValidSubscriptionByUser($userId, $journalId);
		}

		return false;
    }

	/**
	 * Check whether user with ID has a valid subscription for a given journal.
	 * @param $userId int
	 * @param $journalId int
	 * @return boolean
	 */
	function isValidSubscriptionByUser($userId, $journalId) {
		$result = &$this->retrieve(
			'SELECT EXTRACT(DAY FROM date_end),
					EXTRACT(MONTH FROM date_end),
					EXTRACT(YEAR FROM date_end)
			FROM subscriptions, subscription_types
			WHERE subscriptions.user_id = ?
			AND   subscriptions.journal_id = ?
			AND   subscriptions.type_id = subscription_types.type_id
			AND   (subscription_types.format & ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE .' = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE . ')',
			array(
				$userId,
				$journalId
			));

		if ($result->RecordCount() == 0) {
			return false;
		} else {
			$dayEnd = $result->fields[0];
			$monthEnd = $result->fields[1];
			$yearEnd = $result->fields[2];

			// Ensure subscription is still valid
			$curDate = getdate();

			if ( $curDate['year'] < $yearEnd ) {
				return true;
			} elseif (( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] < $monthEnd )) {
				return true;
			} elseif ((( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] == $monthEnd )) && ( $curDate['mday'] <= $dayEnd ) ) {
				return true;
			}

		}

		// By default, not a valid subscription
		return false;
	}

	/**
	 * Check whether there is a valid subscription with given domain for a journal.
	 * @param $domain string
	 * @param $journalId int
	 * @return boolean
	 */
	function isValidSubscriptionByDomain($domain, $journalId) {
		$result = &$this->retrieve(
			'SELECT EXTRACT(DAY FROM date_end),
					EXTRACT(MONTH FROM date_end),
					EXTRACT(YEAR FROM date_end),
					POSITION(UPPER(domain) IN UPPER(?)) 
			FROM subscriptions, subscription_types
			WHERE POSITION(UPPER(domain) IN UPPER(?)) != 0   
			AND   subscriptions.journal_id = ?
			AND   subscriptions.type_id = subscription_types.type_id
			AND   subscription_types.institutional = 1
			AND   (subscription_types.format & ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE .' = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE . ')',
			array(
				$domain,
				$domain,
				$journalId
			));

		if ($result->RecordCount() == 0) {
			return false;
		} else {
			while (!$result->EOF) {
				$dayEnd = $result->fields[0];
				$monthEnd = $result->fields[1];
				$yearEnd = $result->fields[2];
				$posMatch = $result->fields[3];

				// Ensure we have a proper match (i.e. bar.com should not match foobar.com but should match foo.bar.com)
				if ( $posMatch > 1) {
					if ( substr($domain, $posMatch-2, 1) != '.') {
						$result->moveNext();
						continue;
					}
				}

				// Ensure subscription is still valid
				$curDate = getdate();

				if ( $curDate['year'] < $yearEnd ) {
					return true;
				} elseif (( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] < $monthEnd )) {
					return true;
				} elseif ((( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] == $monthEnd )) && ( $curDate['mday'] <= $dayEnd ) ) {
					return true;
				}

				$result->moveNext();
			}
			$result->Close();
		}

		// By default, not a valid subscription
		return false;
	}

	/**
	 * Check whether there is a valid subscription for the given IP for a journal.
	 * @param $IP string
	 * @param $journalId int
	 * @return boolean
	 */
	function isValidSubscriptionByIP($IP, $journalId) {
		$result = &$this->retrieve(
			'SELECT EXTRACT(DAY FROM date_end),
					EXTRACT(MONTH FROM date_end),
					EXTRACT(YEAR FROM date_end),
					ip_range 
			FROM subscriptions, subscription_types
			WHERE ip_range IS NOT NULL   
			AND   subscriptions.journal_id = ?
			AND   subscriptions.type_id = subscription_types.type_id
			AND   subscription_types.institutional = 1
			AND   (subscription_types.format & ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE .' = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE . ')',
			$journalId
			);

		if ($result->RecordCount() == 0) {
			return false;
		} else {
			$matchFound = false;
			$IP = sprintf('%u', ip2long($IP)); 

			while (!$result->EOF) {
				$ipRange = $result->fields[3];

				// Get all IPs and IP ranges
				$ipRanges = explode(SUBSCRIPTION_IP_RANGE_SEPERATOR, $ipRange);

				// Check each IP and IP range
				while (list(, $curIPString) = each($ipRanges)) {
					// Parse and check single IP string
					if (strpos($curIPString, SUBSCRIPTION_IP_RANGE_RANGE) === false) {

						// Check for wildcards in IP
						if (strpos($curIPString, SUBSCRIPTION_IP_RANGE_WILDCARD) === false) {
							$curIPString = sprintf('%u', ip2long(trim($curIPString)));

							if ($curIPString == $IP) {
								$matchFound = true;
								break;
							}
						} else {
							// Turn wildcard IP into IP range
							$ipStart = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '0', trim($curIPString))));
							$ipEnd = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '255', trim($curIPString)))); 

							if ($IP >= $ipStart && $IP <= $ipEnd) {
								$matchFound = true;
								break;
							}
						}
					// Parse and check IP range string
					} else {
						$ipStartAndEnd = explode(SUBSCRIPTION_IP_RANGE_RANGE, $curIPString);

						// Replace wildcards in start and end of range
						$ipStart = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '0', trim($ipStartAndEnd[0]))));
						$ipEnd = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '255', trim($ipStartAndEnd[1]))));

						if ($IP >= $ipStart && $IP <= $ipEnd) {
							$matchFound = true;
							break;
						}
					}

				}

				if ($matchFound == true) {
					break;
				} else {
					$result->moveNext();
				}
			}

			// Found a match. Ensure subscription is still valid
			if ($matchFound == true) {
				$dayEnd = $result->fields[0];
				$monthEnd = $result->fields[1];
				$yearEnd = $result->fields[2];
				$result->Close();

				$curDate = getdate();

				if ( $curDate['year'] < $yearEnd ) {
					return true;
				} elseif (( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] < $monthEnd )) {
					return true;
				} elseif ((( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] == $monthEnd )) && ( $curDate['mday'] <= $dayEnd ) ) {
					return true;
				}
			} else {
				$result->Close();
			}
		}

		// By default, not a valid subscription
		return false;
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
