<?php

/**
 * @file SubscriptionDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package subscription
 * @class SubscriptionDAO
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

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnSubscriptionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);
		return $returner;
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
		
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;	

		$result->Close();
		unset($result);

		return $returner;
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
		
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;	

		$result->Close();
		unset($result);

		return $returner;
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
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
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
		$subscription->setDateStart($this->dateFromDB($row['date_start']));
		$subscription->setDateEnd($this->dateFromDB($row['date_end']));
		$subscription->setMembership($row['membership']);
		$subscription->setDomain($row['domain']);
		$subscription->setIPRange($row['ip_range']);
		
		HookRegistry::call('SubscriptionDAO::_returnSubscriptionFromRow', array(&$subscription, &$row));

		return $subscription;
	}

	/**
	 * Insert a new Subscription.
	 * @param $subscription Subscription
	 * @return boolean 
	 */
	function insertSubscription(&$subscription) {
		$ret = $this->update(
			sprintf('INSERT INTO subscriptions
				(journal_id, user_id, type_id, date_start, date_end, membership, domain, ip_range)
				VALUES
				(?, ?, ?, %s, %s, ?, ?, ?)',
				$this->dateToDB($subscription->getDateStart()), $this->dateToDB($subscription->getDateEnd())),
			array(
				$subscription->getJournalId(),
				$subscription->getUserId(),
				$subscription->getTypeId(),
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
			sprintf('UPDATE subscriptions
				SET
					journal_id = ?,
					user_id = ?,
					type_id = ?,
					date_start = %s,
					date_end = %s,
					membership = ?,
					domain = ?,
					ip_range = ?
				WHERE subscription_id = ?',
				$this->dateToDB($subscription->getDateStart()), $this->dateToDB($subscription->getDateEnd())),
			array(
				$subscription->getJournalId(),
				$subscription->getUserId(),
				$subscription->getTypeId(),
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
	 * Delete subscriptions by user ID.
	 * @param $userId int
	 */
	function deleteSubscriptionsByUserId($userId) {
		return $this->update(
			'DELETE FROM subscriptions WHERE user_id = ?', $userId
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
	 * Retrieve an array of subscriptions matching a particular end date and journal ID.
	 * @param $dateEnd date (YYYY-MM-DD)
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching Subscriptions
	 */
	function &getSubscriptionsByDateEnd($dateEnd, $journalId, $rangeInfo = null) {
		$dateEnd = explode('-', $dateEnd);

		$result = &$this->retrieveRange(
			'SELECT * FROM subscriptions
				WHERE EXTRACT(YEAR FROM date_end) = ?
				AND   EXTRACT(MONTH FROM date_end) = ?
				AND   EXTRACT(DAY FROM date_end) = ?
				AND   journal_id = ?',
			array(
				$dateEnd[0],
				$dateEnd[1],
				$dateEnd[2],
				$journalId
			), $rangeInfo
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
			AND   (subscription_types.format = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE .' OR subscription_types.format = ' . SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')',
			array(
				$userId,
				$journalId
			));

		$returner = false;

		if ($result->RecordCount() != 0) {
			$dayEnd = $result->fields[0];
			$monthEnd = $result->fields[1];
			$yearEnd = $result->fields[2];

			// Ensure subscription is still valid
			$curDate = getdate();

			if ( $curDate['year'] < $yearEnd ) {
				$returner = true;
			} elseif (( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] < $monthEnd )) {
				$returner = true;
			} elseif ((( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] == $monthEnd )) && ( $curDate['mday'] <= $dayEnd ) ) {
				$returner = true;
			}
		}

		$result->Close();
		unset($result);

		return $returner;
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
			AND   domain != \'\'
			AND   subscriptions.journal_id = ?
			AND   subscriptions.type_id = subscription_types.type_id
			AND   subscription_types.institutional = 1
			AND   (subscription_types.format = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE .' OR subscription_types.format = ' . SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')',
			array(
				$domain,
				$domain,
				$journalId
			));

		$returner = false;

		if ($result->RecordCount() != 0) {
			while (!$returner && !$result->EOF) {
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
					$returner = true;
				} elseif (( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] < $monthEnd )) {
					$returner = true;
				} elseif ((( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] == $monthEnd )) && ( $curDate['mday'] <= $dayEnd ) ) {
					$returner = true;
				}

				$result->moveNext();
			}
		}

		$result->Close();
		unset($result);

		// By default, not a valid subscription
		return $returner;
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
			AND   (subscription_types.format = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE .' OR subscription_types.format = ' . SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')',
			$journalId
			);

		$returner = false;

		if ($result->RecordCount() != 0) {
			$matchFound = false;

			while (!$returner && !$result->EOF) {
				$ipRange = $result->fields[3];

				// Get all IPs and IP ranges
				$ipRanges = explode(SUBSCRIPTION_IP_RANGE_SEPERATOR, $ipRange);

				// Check each IP and IP range
				while (list(, $curIPString) = each($ipRanges)) {
					// Parse and check single IP string
					if (strpos($curIPString, SUBSCRIPTION_IP_RANGE_RANGE) === false) {

						// Check for wildcards in IP
						if (strpos($curIPString, SUBSCRIPTION_IP_RANGE_WILDCARD) === false) {

							// Check non-CIDR IP
							if (strpos($curIPString, '/') === false) {
								if (ip2long(trim($curIPString)) == ip2long($IP)) {
									$matchFound = true;
									break;
								}
							// Check CIDR IP
							} else {
								list($curIPString, $cidrMask) = explode('/', trim($curIPString));
								$cidrMask = 0xffffffff << (32 - $cidrMask);

								if ((ip2long($IP) & $cidrMask) == (ip2long($curIPString) & $cidrMask)) {
									$matchFound = true;
									break;
								}
							}

						} else {
							// Turn wildcard IP into IP range
							$ipStart = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '0', trim($curIPString))));
							$ipEnd = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '255', trim($curIPString)))); 
							$IP = sprintf('%u', ip2long($IP)); 

							if ($IP >= $ipStart && $IP <= $ipEnd) {
								$matchFound = true;
								break;
							}
						}
					// Parse and check IP range string
					} else {
						list($ipStart, $ipEnd) = explode(SUBSCRIPTION_IP_RANGE_RANGE, $curIPString);

						// Replace wildcards in start and end of range
						$ipStart = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '0', trim($ipStart))));
						$ipEnd = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '255', trim($ipEnd))));
						$IP = sprintf('%u', ip2long($IP)); 

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

				$curDate = getdate();

				if ( $curDate['year'] < $yearEnd ) {
					$returner = true;
				} elseif (( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] < $monthEnd )) {
					$returner = true;
				} elseif ((( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] == $monthEnd )) && ( $curDate['mday'] <= $dayEnd ) ) {
					$returner = true;
				}
			}
		}

		$result->Close();
		unset($result);

		return $returner;
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
