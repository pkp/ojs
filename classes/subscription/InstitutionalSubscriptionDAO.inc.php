<?php

/**
 * @file classes/subscription/InstitutionalSubscriptionDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
define('SUBSCRIPTION_DOMAIN',		0x21);
define('SUBSCRIPTION_IP_RANGE',		0x22);


class InstitutionalSubscriptionDAO extends SubscriptionDAO {
	/**
	 * Retrieve an institutional subscription by subscription ID.
	 * @param $subscriptionId int Subscription ID
	 * @param $journalId int Journal ID
	 * @return InstitutionalSubscription
	 */
	function getById($subscriptionId, $journalId = null) {
		$params = array((int) $subscriptionId);
		if ($journalId) $params[] = (int) $journalId;
		$result = $this->retrieve(
			'SELECT	s.*, iss.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
			WHERE	st.institutional = 1
				AND s.subscription_id = ?
				' . ($journalId?' AND s.journal_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve institutional subscriptions by user ID.
	 * @param $userId int
	 * @return object DAOResultFactory containing matching InstitutionalSubscriptions
	 */
	function getByUserId($userId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT	s.*, iss.*
			FROM	subscriptions s
				JOIN subscription_types st ON (st.type_id = s.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
			WHERE	st.institutional = 1
				AND s.user_id = ?',
			(int) $userId,
			$rangeInfo
		);
		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve institutional subscriptions by user ID and journal ID.
	 * @param $userId int
	 * @param $journalId int
	 * @param $rangeInfo RangeInfo
	 * @return object DAOResultFactory containing matching InstitutionalSubscriptions
	 */
	function getByUserIdForJournal($userId, $journalId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT	s.*, iss.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
			WHERE	st.institutional = 1
				AND s.user_id = ?
				AND s.journal_id = ?',
			array(
				(int) $userId,
				(int) $journalId
			),
			$rangeInfo
		);
		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve institutional subscriptions by institution name.
	 * @param $institutionName string Institution name
	 * @param $journalId int Journal ID
	 * @param $exactMatch boolean True iff the match on institution name should be exact
	 * @param $rangeInfo RangeInfo
	 * @return object DAOResultFactory containing matching InstitutionalSubscriptions
	 */
	function getByInstitutionName($institutionName, $journalId, $exactMatch = true, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT	s.*, iss.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
			WHERE AND st.institutional = 1' .
			$exactMatch ? ' AND LOWER(iss.institution_name) = LOWER(?)'
			: ' AND LOWER(iss.institution_name) LIKE LOWER(?)'
			. ' AND s.journal_id = ?',
			array(
				$institutionName,
				(int) $journalId
			),
			$rangeInfo
		);
		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Return number of institutional subscriptions with given status for journal.
	 * @param status int
	 * @return int
	 */
	function getStatusCount($journalId, $status = null) {
		$params = array((int) $journalId);
		if ($status !== null) $params[] = (int) $status;
		$result = $this->retrieve(
			'SELECT	COUNT(*)
			FROM	subscriptions s,
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	st.institutional = 1 AND
				s.journal_id = ?
				' . ($status !== null?' AND s.status = ?':''),
			$params
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;
		$result->Close();
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
	 * @param $subscriptionId int Subscription ID
	 * @param $journalId int Optional journal ID
	 * @return boolean
	 */
	function subscriptionExists($subscriptionId, $journalId = null) {
		$params = array((int) $subscriptionId);
		if ($journalId) $params[] = (int) $journalId;
		$result = $this->retrieve(
			'SELECT	COUNT(*)
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	st.institutional = 1
				AND s.subscription_id = ?
				' . ($journalId?' AND s.journal_id = ?':''),
			$params
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Check if an institutional subscription exists for a given user.
	 * @param $subscriptionId int Subscription ID
	 * @param $userId int User ID
	 * @return boolean
	 */
	function subscriptionExistsByUser($subscriptionId, $userId) {
		$result = $this->retrieve(
			'SELECT COUNT(*)
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	st.institutional = 1
				AND s.subscription_id = ?
				AND s.user_id = ?',
			array(
				(int) $subscriptionId,
				(int) $userId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Check if an institutional subscription exists for a given user and journal.
	 * @param $userId int
	 * @param $journalId int
	 * @return boolean
	 */
	function subscriptionExistsByUserForJournal($userId, $journalId) {
		$result = $this->retrieve(
			'SELECT	COUNT(*)
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	st.institutional = 1
				AND s.user_id = ?
				AND s.journal_id = ?',
			array(
				(int) $userId,
				(int) $journalId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;
		$result->Close();
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
		$result = $this->retrieve(
			'SELECT	COUNT(*)
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
			WHERE st.institutional = 1' .
				$exactMatch ? ' AND LOWER(iss.institution_name) = LOWER(?)'
				: ' AND LOWER(iss.institution_name) LIKE LOWER(?)'
				. ' AND s.journal_id = ?',
			array(
				$institutionName,
				(int) $journalId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Insert a new institutional subscription.
	 * @param $institutionalSubscription InstitutionalSubscription
	 * @return int
	 */
	function insertObject($institutionalSubscription) {
		$subscriptionId = null;
		if ($this->_insertObject($institutionalSubscription)) {
			$subscriptionId = $institutionalSubscription->getId();

			$this->update(
				'INSERT INTO institutional_subscriptions
				(subscription_id, institution_name, mailing_address, domain)
				VALUES
				(?, ?, ?, ?)',
				array(
					(int) $subscriptionId,
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
	function updateObject($institutionalSubscription) {
		$this->_updateObject($institutionalSubscription);

		$this->update(
			'UPDATE	institutional_subscriptions
			SET	institution_name = ?,
				mailing_address = ?,
				domain = ?
			WHERE	subscription_id = ?',
			array(
				$institutionalSubscription->getInstitutionName(),
				$institutionalSubscription->getInstitutionMailingAddress(),
				$institutionalSubscription->getDomain(),
				(int) $institutionalSubscription->getId()
			)
		);

		$this->_deleteSubscriptionIPRanges($institutionalSubscription->getId());
		$this->_insertSubscriptionIPRanges($institutionalSubscription->getId(), $institutionalSubscription->getIPRanges());
	}

	/**
	 * Delete an institutional subscription by subscription ID.
	 * @param $subscriptionId int
	 */
	function deleteById($subscriptionId, $journalId = null) {
		if (!$this->subscriptionExists($subscriptionId, $journalId)) return;

		$this->update('DELETE FROM subscriptions WHERE subscription_id = ?', (int) $subscriptionId);
		$this->update('DELETE FROM institutional_subscriptions WHERE subscription_id = ?', (int) $subscriptionId);
		$this->_deleteSubscriptionIPRanges($subscriptionId);
	}

	/**
	 * Delete institutional subscriptions by journal ID.
	 * @param $journalId int
	 */
	function deleteByJournalId($journalId) {
		$result = $this->retrieve(
			'SELECT	s.subscription_id
			FROM	subscriptions s
			WHERE	s.journal_id = ?',
			(int) $journalId
		);

		if ($result->RecordCount() != 0) {
			while (!$result->EOF) {
				$subscriptionId = $result->fields[0];
				$this->deleteById($subscriptionId);
				$result->MoveNext();
			}
		}

		$result->Close();
	}

	/**
	 * Delete institutional subscriptions by user ID.
	 * @param $userId int
	 */
	function deleteByUserId($userId) {
		$result = $this->retrieve('SELECT s.subscription_id FROM subscriptions s WHERE s.user_id = ?', (int) $userId);
		if ($result->RecordCount() != 0) {
			while (!$result->EOF) {
				$subscriptionId = $result->fields[0];
				$this->deleteById($subscriptionId);
				$result->MoveNext();
			}
		}

		$result->Close();
	}

	/**
	 * Delete institutional subscriptions by user ID and journal ID.
	 * @param $userId int User ID
	 * @param $journalId int Journal ID
	 */
	function deleteByUserIdForJournal($userId, $journalId) {
		$result = $this->retrieve(
			'SELECT s.subscription_id FROM subscriptions s WHERE s.user_id = ? AND s.journal_id = ?',
			array((int) $userId, (int) $journalId)
		);

		if ($result->RecordCount() != 0) {
			while (!$result->EOF) {
				$subscriptionId = $result->fields[0];
				$this->deleteById($subscriptionId);
				$result->MoveNext();
			}
		}

		$result->Close();
	}

	/**
	 * Delete all institutional subscriptions by subscription type ID.
	 * @param $subscriptionTypeId int Subscription type ID
	 */
	function deleteByTypeId($subscriptionTypeId) {
		$result = $this->retrieve('SELECT s.subscription_id FROM subscriptions s WHERE s.type_id = ?', (int) $subscriptionTypeId);

		if ($result->RecordCount() != 0) {
			while (!$result->EOF) {
				$subscriptionId = $result->fields[0];
				$this->deleteById($subscriptionId);
				$result->MoveNext();
			}
		}

		$result->Close();
	}

	/**
	 * Retrieve all institutional subscriptions.
	 * @return object DAOResultFactory containing InstitutionalSubscriptions
	 */
	function getAll($rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT	s.*, iss.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
			WHERE	st.institutional = 1
			ORDER BY iss.institution_name ASC, s.subscription_id',
			false,
			$rangeInfo
		);
		return new DAOResultFactory($result, $this, '_fromRow');
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
	function getByJournalId($journalId, $status = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {

		$params = array((int) $journalId);
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


		$result = $this->retrieveRange(
			'SELECT DISTINCT s.*, iss.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN users u ON (s.user_id = u.user_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
				' . $ipRangeSql1 . '
			WHERE	st.institutional = 1
				' . $ipRangeSql2 . '
				AND s.journal_id = ?
			' . $searchSql . ' ORDER BY iss.institution_name ASC, s.subscription_id',
			$params,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
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
		if (empty($journalId) || (empty($domain) && empty($IP))) return false;
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

		// Check if domain match
		if (!empty($domain)) {
			$result = $this->retrieve('
				SELECT	iss.subscription_id
				FROM	institutional_subscriptions iss
					JOIN subscriptions s ON (iss.subscription_id = s.subscription_id)
					JOIN subscription_types st ON (s.type_id = st.type_id)
				WHERE	POSITION(UPPER(LPAD(iss.domain, LENGTH(iss.domain)+1, \'.\')) IN UPPER(LPAD(?, LENGTH(?)+1, \'.\'))) != 0
					AND iss.domain != \'\'
					AND s.journal_id = ?
					AND s.status = ' . SUBSCRIPTION_STATUS_ACTIVE . '
					AND st.institutional = 1
					AND ((st.non_expiring = 1) OR (st.non_expiring = 0 AND (' . $dateSql . ')))
					AND (st.format = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
					OR st.format = ' . SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')',
				array(
					$domain,
					$domain,
					(int) $journalId
				)
			);

			if ($result->RecordCount() != 0) {
				$returner = $result->fields[0];
			}

			$result->Close();

			if ($returner) {
				return $returner;
			}
		}

		// Check for IP match
		if (!empty($IP)) {
			$IP = sprintf('%u', ip2long($IP));

			$result = $this->retrieve(
				'SELECT	isip.subscription_id
				FROM	institutional_subscription_ip isip
					JOIN subscriptions s ON (isip.subscription_id = s.subscription_id)
					JOIN subscription_types st ON (s.type_id = st.type_id)
				WHERE	((isip.ip_end IS NOT NULL
					AND ? >= isip.ip_start AND ? <= isip.ip_end
					AND s.journal_id = ?
					AND s.status = ' . SUBSCRIPTION_STATUS_ACTIVE . '
					AND st.institutional = 1
					AND ((st.non_expiring = 1) OR (st.non_expiring = 0 AND (' . $dateSql . ')))
					AND (st.format = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
						OR st.format = ' . SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . '))
					OR  (isip.ip_end IS NULL
					AND ? = isip.ip_start
					AND s.journal_id = ?
					AND s.status = ' . SUBSCRIPTION_STATUS_ACTIVE . '
					AND st.institutional = 1
					AND ((st.non_expiring = 1) OR (st.non_expiring = 0 AND (' . $dateSql . ')))
					AND (st.format = ' . SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
					OR st.format = ' . SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')))',
				array (
					$IP,
					$IP,
					(int) $journalId,
					$IP,
					(int) $journalId
				)
			);

			if ($result->RecordCount() != 0) {
				$returner = $result->fields[0];
			}

			$result->Close();
		}

		return $returner;
	}

	/**
	 * Retrieve active institutional subscriptions matching a particular end date and journal ID.
	 * @param $dateEnd date (YYYY-MM-DD)
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching InstitutionalSubscriptions
	 */
	function getByDateEnd($dateEnd, $journalId, $rangeInfo = null) {
		$dateEnd = explode('-', $dateEnd);

		$result = $this->retrieveRange(
			'SELECT	s.*, iss.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
			WHERE	s.status = ' . SUBSCRIPTION_STATUS_ACTIVE . '
				AND st.institutional = 1
				AND EXTRACT(YEAR FROM s.date_end) = ?
				AND EXTRACT(MONTH FROM s.date_end) = ?
				AND EXTRACT(DAY FROM s.date_end) = ?
				AND s.journal_id = ?
			ORDER BY iss.institution_name ASC, s.subscription_id',
			array(
				$dateEnd[0],
				$dateEnd[1],
				$dateEnd[2],
				(int) $journalId
			), $rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Renew an institutional subscription by dateEnd + duration of subscription type
	 * if the institutional subscription is expired, renew to current date + duration
	 * @param $institutionalSubscription InstitutionalSubscription
	 * @return boolean
	 */
	function renewSubscription($institutionalSubscription) {
		return $this->_renewSubscription($institutionalSubscription);
	}

	/**
	 * Generator function to create object.
	 * @return InstitutionalSubscription
	 */
	function newDataObject() {
		return new InstitutionalSubscription();
	}

	/**
	 * Internal function to return an InstitutionalSubscription object from a row.
	 * @param $row array
	 * @return InstitutionalSubscription
	 */
	function _fromRow($row) {
		$institutionalSubscription = parent::_fromRow($row);

		$institutionalSubscription->setInstitutionName($row['institution_name']);
		$institutionalSubscription->setInstitutionMailingAddress($row['mailing_address']);
		$institutionalSubscription->setDomain($row['domain']);

		$ipResult = $this->retrieve(
			'SELECT ip_string
			FROM	institutional_subscription_ip
			WHERE	subscription_id = ?
			ORDER BY institutional_subscription_ip_id ASC',
			(int) $institutionalSubscription->getId()
		);

		$ipRanges = array();
		while (!$ipResult->EOF) {
			$ipRow = $ipResult->GetRowAssoc(false);
			$ipRanges[] = $ipRow['ip_string'];
			$ipResult->MoveNext();
		}

		$institutionalSubscription->setIPRanges($ipRanges);
		$ipResult->Close();

		HookRegistry::call('InstitutionalSubscriptionDAO::_fromRow', array(&$institutionalSubscription, &$row));

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
						(int) $subscriptionId,
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
			'DELETE FROM institutional_subscription_ip WHERE subscription_id = ?', (int) $subscriptionId
		);
	}
}


