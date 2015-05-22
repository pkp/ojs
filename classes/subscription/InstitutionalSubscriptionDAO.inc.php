<?php

/**
 * @file classes/subscription/InstitutionalSubscriptionDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InstitutionalSubscriptionDAO
 * @ingroup subscription
 * @see InstitutionalSubscription
 *
 * @brief Operations for retrieving and modifying InstitutionalSubscription objects.
 */

import('classes.subscription.SubscriptionDAO');
import('classes.subscription.InstitutionalSubscription');

define('SUBSCRIPTION_INSTITUTION_NAME',	0x20);
define('SUBSCRIPTION_DOMAIN',			0x21);
define('SUBSCRIPTION_IP_RANGE',			0x22);

class InstitutionalSubscriptionDAO extends SubscriptionDAO {
	/**
	 * Retrieve an institutional subscription by subscription ID.
	 * @param $subscriptionId int
	 * @return InstitutionalSubscription
	 */
	function &getSubscription($subscriptionId) {
		$result =& $this->retrieve(
			'SELECT s.*, iss.*
			FROM
			subscriptions s,
			subscription_types st,
			institutional_subscriptions iss
			WHERE s.type_id = st.type_id
			AND st.institutional = 1
			AND s.subscription_id = iss.subscription_id
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
	 * Retrieve institutional subscriptions by user ID.
	 * @param $userId int
	 * @return object DAOResultFactory containing matching InstitutionalSubscriptions
	 */
	function &getSubscriptionsByUser($userId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT s.*, iss.*
			FROM
			subscriptions s,
			subscription_types st,
			institutional_subscriptions iss
			WHERE s.type_id = st.type_id
			AND st.institutional = 1
			AND s.subscription_id = iss.subscription_id
			AND s.user_id = ?',
			$userId,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSubscriptionFromRow');

		return $returner;
	}

	/**
	 * Retrieve institutional subscriptions by user ID and journal ID.
	 * @param $userId int
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching InstitutionalSubscriptions
	 */
	function &getSubscriptionsByUserForJournal($userId, $journalId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT s.*, iss.*
			FROM
			subscriptions s,
			subscription_types st,
			institutional_subscriptions iss
			WHERE s.type_id = st.type_id
			AND st.institutional = 1
			AND s.subscription_id = iss.subscription_id
			AND s.user_id = ?
			AND s.journal_id = ?',
			array(
				$userId,
				$journalId
			),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSubscriptionFromRow');

		return $returner;
	}

	/**
	 * Retrieve institutional subscriptions by institution name.
	 * @param $institutionName string
	 * @param $journalId int
	 * @param $exactMatch boolean
	 * @return object DAOResultFactory containing matching InstitutionalSubscriptions
	 */
	function &getSubscriptionsByInstitutionName($institutionName, $journalId, $exactMatch = true, $rangeInfo = null) {
		$sql = 'SELECT s.*, iss.*
				FROM
				subscriptions s,
				subscription_types st,
				institutional_subscriptions iss
				WHERE s.type_id = st.type_id
				AND st.institutional = 1
				AND s.subscription_id = iss.subscription_id' .
				$exactMatch ? ' AND LOWER(iss.institution_name) = LOWER(?)'
				: ' AND LOWER(iss.institution_name) LIKE LOWER(?)'
				. ' AND s.journal_id = ?';

		$result =& $this->retrieveRange(
			$sql,
			array(
				$institutionName,
				$journalId
			),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSubscriptionFromRow');

		return $returner;
	}

	/**
	 * Return number of institutional subscriptions with given status for journal.
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
				st.institutional = 1 AND
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
	 * Get the number of institutional subscriptions for a particular journal.
	 * @param $journalId int
	 * @return int
	 */
	function getSubscribedUserCount($journalId) {
		return $this->getStatusCount($journalId);
	}

	/**
	 * Check if an institutional subscription exists for a given subscriptionId.
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
			AND st.institutional = 1
			AND s.subscription_id = ?',
			$subscriptionId
		);

		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if an institutional subscription exists for a given user.
	 * @param $subscriptionId int
	 * @param $userId int
	 * @return boolean
	 */
	function subscriptionExistsByUser($subscriptionId, $userId){ 
		$result =& $this->retrieve(
			'SELECT COUNT(*)
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 1
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
	 * Check if an institutional subscription exists for a given user and journal.
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
			AND st.institutional = 1
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
	 * Check if an institutional subscription exists for a given institution name and journal.
	 * @param $institutionName string
	 * @param $journalId int
	 * @param $exactMatch boolean
	 * @return boolean
	 */
	function subscriptionExistsByInstitutionName($institutionName, $journalId, $exactMatch = true) {
		$sql = 'SELECT COUNT(*)
				FROM
				subscriptions s,
				subscription_types st,
				institutional_subscriptions iss
				WHERE s.type_id = st.type_id
				AND st.institutional = 1' .
				$exactMatch ? ' AND LOWER(iss.institution_name) = LOWER(?)'
				: ' AND LOWER(iss.institution_name) LIKE LOWER(?)'
				. ' AND s.journal_id = ?';

		$result =& $this->retrieve(
			$sql,
			array(
				$institutionName,
				$journalId
			)
		);

		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Insert a new institutional subscription.
	 * @param $institutionalSubscription InstitutionalSubscription
	 * @return int 
	 */
	function insertSubscription(&$institutionalSubscription) {
		$subscriptionId = null;
		if ($this->_insertSubscription($institutionalSubscription)) {
			$subscriptionId = $institutionalSubscription->getId();

			$returner = $this->update(
				'INSERT INTO institutional_subscriptions
				(subscription_id, institution_name, mailing_address, domain)
				VALUES
				(?, ?, ?, ?)',
				array(
					$subscriptionId,
					$institutionalSubscription->getInstitutionName(),
					$institutionalSubscription->getInstitutionMailingAddress(),
					$institutionalSubscription->getDomain()
				)
			);

			$this->_insertSubscriptionIPRanges($subscriptionId, $institutionalSubscription->getIPRanges());
		}

		return $subscriptionId;
	}

	/**
	 * Update an existing institutional subscription.
	 * @param $institutionalSubscription InstitutionalSubscription
	 * @return boolean
	 */
	function updateSubscription(&$institutionalSubscription) {
		$returner = false;
		if ($this->_updateSubscription($institutionalSubscription)) {

			$returner = $this->update(
				'UPDATE institutional_subscriptions
				SET
					institution_name = ?,
					mailing_address = ?,
					domain = ?
				WHERE subscription_id = ?',
				array(
					$institutionalSubscription->getInstitutionName(),
					$institutionalSubscription->getInstitutionMailingAddress(),
					$institutionalSubscription->getDomain(),
					$institutionalSubscription->getId()
				)
			);

			$this->_deleteSubscriptionIPRanges($institutionalSubscription->getId());
			$this->_insertSubscriptionIPRanges($institutionalSubscription->getId(), $institutionalSubscription->getIPRanges());
		}

		return $returner;
	}

	/**
	 * Delete an institutional subscription by subscription ID.
	 * @param $subscriptionId int
	 * @return boolean
	 */
	function deleteSubscriptionById($subscriptionId) {
		$returner = false;
		if ($this->subscriptionExists($subscriptionId)) {
			$returner = $this->update(
				'DELETE
				FROM
				subscriptions
				WHERE subscription_id = ?',
				$subscriptionId
			);

			$returner = $this->update(
				'DELETE
				FROM
				institutional_subscriptions
				WHERE subscription_id = ?',
				$subscriptionId
			);

			$this->_deleteSubscriptionIPRanges($subscriptionId);
		} 
		return $returner;
	}

	/**
	 * Delete institutional subscriptions by journal ID.
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
	 * Delete institutional subscriptions by user ID.
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
	 * Delete institutional subscriptions by user ID and journal ID.
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
	 * Delete all institutional subscriptions by subscription type ID.
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
	 * Retrieve all institutional subscriptions.
	 * @return object DAOResultFactory containing InstitutionalSubscriptions
	 */
	function &getSubscriptions($rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT s.*, iss.*
			FROM
			subscriptions s,
			subscription_types st,
			institutional_subscriptions iss
			WHERE s.type_id = st.type_id
			AND st.institutional = 1
			AND s.subscription_id = iss.subscription_id
			ORDER BY
			iss.institution_name ASC,
			s.subscription_id',
			false,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSubscriptionFromRow');

		return $returner;
	}

	/**
	 * Retrieve all institutional subscription contacts.
	 * @return object DAOResultFactory containing Users
	 */
	function &getSubscribedUsers($journalId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT	u.*
			FROM	subscriptions s,
				subscription_types st,
				users u
			WHERE	s.type_id = st.type_id AND
				st.institutional = 1 AND
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
	 * Retrieve institutional subscriptions matching a particular journal ID.
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
	function &getSubscriptionsByJournalId($journalId, $status = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {

		$params = array($journalId);
		$ipRangeSql1 = $ipRangeSql2 = '';
		$searchSql = $this->_generateSearchSQL($status, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, $params);

		if (!empty($search)) switch ($searchField) {
			case SUBSCRIPTION_INSTITUTION_NAME:
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
			case SUBSCRIPTION_DOMAIN:
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
			case SUBSCRIPTION_IP_RANGE:
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
				$ipRangeSql1 = ', institutional_subscription_ip isip' ;
				$ipRangeSql2 = ' AND s.subscription_id = isip.subscription_id';
				break;
		}

		$sql = 'SELECT DISTINCT
				s.*, iss.*
				FROM
				subscriptions s,
				subscription_types st,
				users u,
				institutional_subscriptions iss'
				. $ipRangeSql1 .
				' WHERE s.type_id = st.type_id
				AND s.user_id = u.user_id
				AND st.institutional = 1
				AND s.subscription_id = iss.subscription_id'
				. $ipRangeSql2 .
				' AND s.journal_id = ?';

		$result =& $this->retrieveRange(
			$sql . ' ' . $searchSql . ' ORDER BY iss.institution_name ASC, s.subscription_id',
			count($params)===1?array_shift($params):$params,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSubscriptionFromRow');

		return $returner;
	}

	/**
	 * Check whether there is a valid institutional subscription for a given journal.
	 * @param $domain string
	 * @param $IP string
	 * @param $journalId int
	 * @param $check int Test using either start date, end date, or both (default)
	 * @param $checkDate date (YYYY-MM-DD) Use this date instead of current date
	 * @return int 
	 */
	function isValidInstitutionalSubscription($domain, $IP, $journalId, $check = SUBSCRIPTION_DATE_BOTH, $checkDate = null) {
		if (empty($journalId) || (empty($domain) && empty($IP))) {
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

		// Check if domain match
		if (!empty($domain)) {
			$result =& $this->retrieve('
				SELECT iss.subscription_id 
				FROM
				institutional_subscriptions iss,
				subscriptions s,
				subscription_types st
				WHERE POSITION(UPPER(LPAD(iss.domain, LENGTH(iss.domain)+1, \'.\')) IN UPPER(LPAD(?, LENGTH(?)+1, \'.\'))) != 0
				AND iss.domain != \'\'
				AND iss.subscription_id = s.subscription_id
				AND s.journal_id = ?
				AND s.status = ' . SUBSCRIPTION_STATUS_ACTIVE . '
				AND s.type_id = st.type_id
				AND st.institutional = 1 '
				. $nonExpiringSql .
				' AND (st.format = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
					OR st.format = ' . SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')',
				array(
					$domain,
					$domain,
					$journalId
				)
			);

			if ($result->RecordCount() != 0) {
				$returner = $result->fields[0];
			}

			$result->Close();
			unset($result);

			if ($returner) {
				return $returner;
			}
		}

		// Check for IP match
		if (!empty($IP)) {
			$IP = sprintf('%u', ip2long($IP));

			$result =& $this->retrieve('
				SELECT isip.subscription_id
				FROM
				institutional_subscription_ip isip,
				subscriptions s,
				subscription_types st
				WHERE ((isip.ip_end IS NOT NULL
				AND ? >= isip.ip_start AND ? <= isip.ip_end
				AND isip.subscription_id = s.subscription_id   
				AND s.journal_id = ?
				AND s.status = ' . SUBSCRIPTION_STATUS_ACTIVE . '
				AND s.type_id = st.type_id
				AND st.institutional = 1 '
				. $nonExpiringSql .
				' AND (st.format = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
					OR st.format = ' . SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . '))
				OR  (isip.ip_end IS NULL
				AND ? = isip.ip_start
				AND isip.subscription_id = s.subscription_id   
				AND s.journal_id = ?
				AND s.status = ' . SUBSCRIPTION_STATUS_ACTIVE . '
				AND s.type_id = st.type_id
				AND st.institutional = 1 '
				. $nonExpiringSql .
				' AND (st.format = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
					OR st.format = ' . SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')))',
				array (
					$IP,
					$IP,
					$journalId,
					$IP,
					$journalId
				)
			);

			if ($result->RecordCount() != 0) {
				$returner = $result->fields[0];
			}

			$result->Close();
			unset($result);
		}

		return $returner;
	}

	/**
	 * Retrieve active institutional subscriptions matching a particular end date and journal ID.
	 * @param $dateEnd date
	 * @param $journalId int
	 * @param $reminderType int SUBSCRIPTION_REMINDER_FIELD_..._EXPIRY
	 * @return object DAOResultFactory containing matching InstitutionalSubscriptions
	 */
	function &getSubscriptionsToRemind($dateEnd, $journalId, $reminderType, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			sprintf(
				'SELECT	s.*, iss.*
				FROM	subscriptions s,
					subscription_types st,
					institutional_subscriptions iss
				WHERE	s.type_id = st.type_id
					AND s.status = ?
					AND st.institutional = 1
					AND s.subscription_id = iss.subscription_id
					AND s.date_end <= %s
					AND s.' . ($reminderType==SUBSCRIPTION_REMINDER_FIELD_BEFORE_EXPIRY?'date_reminded_before':'date_reminded_after') . ' IS NULL
					AND s.journal_id = ?
				ORDER BY iss.institution_name ASC, s.subscription_id',
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
	 * Renew an institutional subscription by dateEnd + duration of subscription type
	 * if the institutional subscription is expired, renew to current date + duration  
	 * @param $institutionalSubscription InstitutionalSubscription
	 * @return boolean
	 */	
	function renewSubscription(&$institutionalSubscription) {
		return $this->_renewSubscription($institutionalSubscription);
	}

	/**
	 * Generator function to create object.
	 * @return InstitutionalSubscription
	 */
	function createObject() {
		return new InstitutionalSubscription();
	}

	/**
	 * Internal function to return an InstitutionalSubscription object from a row.
	 * @param $row array
	 * @return InstitutionalSubscription
	 */
	function &_returnSubscriptionFromRow(&$row) {
		$institutionalSubscription = parent::_returnSubscriptionFromRow($row);

		$institutionalSubscription->setInstitutionName($row['institution_name']);
		$institutionalSubscription->setInstitutionMailingAddress($row['mailing_address']);
		$institutionalSubscription->setDomain($row['domain']);

		$ipResult =& $this->retrieve(
			'SELECT ip_string
			FROM
			institutional_subscription_ip
			WHERE subscription_id = ?
			ORDER BY institutional_subscription_ip_id ASC',
			$institutionalSubscription->getId()
		);

		$ipRanges = array();
		while (!$ipResult->EOF) {
			$ipRow =& $ipResult->GetRowAssoc(false);
			$ipRanges[] = $ipRow['ip_string'];
			$ipResult->moveNext();
		}

		$institutionalSubscription->setIPRanges($ipRanges);
		$ipResult->Close();
		unset($ipResult);

		HookRegistry::call('InstitutionalSubscriptionDAO::_returnSubscriptionFromRow', array(&$institutionalSubscription, &$row));

		return $institutionalSubscription;
	}

	/**
	 * Internal function to insert institutional subscription IP ranges.
	 * @param $subscriptionId int
	 * @param $ipRanges array
	 * @return boolean 
	 */
	function _insertSubscriptionIPRanges($subscriptionId, $ipRanges) {
		if (empty($ipRanges)) {
			return true;
		}

		if (empty($subscriptionId)) {
			return false;
		}

		$returner = true;
		
		while (list(, $curIPString) = each($ipRanges)) {
			$ipStart = null;
			$ipEnd = null;

			// Parse and check single IP string
			if (strpos($curIPString, SUBSCRIPTION_IP_RANGE_RANGE) === false) {

				// Check for wildcards in IP
				if (strpos($curIPString, SUBSCRIPTION_IP_RANGE_WILDCARD) === false) {

					// Get non-CIDR IP
					if (strpos($curIPString, '/') === false) {
						$ipStart = sprintf("%u", ip2long(trim($curIPString)));

					// Convert CIDR IP to IP range
					} else {
						list($cidrIPString, $cidrBits) = explode('/', trim($curIPString));

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
					$ipStart = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '0', trim($curIPString))));
					$ipEnd = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '255', trim($curIPString)))); 
				}

			// Convert wildcard IP range to IP range
			} else {
				list($ipStart, $ipEnd) = explode(SUBSCRIPTION_IP_RANGE_RANGE, $curIPString);

				// Replace wildcards in start and end of range
				$ipStart = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '0', trim($ipStart))));
				$ipEnd = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '255', trim($ipEnd))));
			}

			// Insert IP or IP range
			if (($ipStart != null) && ($returner)) {
				$returner = $this->update(
						'INSERT INTO institutional_subscription_ip
						(subscription_id, ip_string, ip_start, ip_end)
						VALUES
						(?, ?, ?, ?)',
					array(
						$subscriptionId,
						$curIPString,
						$ipStart,
						$ipEnd
					) 
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
	 * @param $subscriptionId int
	 * @return boolean
	 */
	function _deleteSubscriptionIPRanges($subscriptionId) {
		return $this->update(
			'DELETE FROM institutional_subscription_ip WHERE subscription_id = ?', $subscriptionId
		);
	}
}

?>
