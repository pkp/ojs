<?php

/**
 * @file classes/user/PKPUserDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPUserDAO
 * @ingroup user
 * @see User
 *
 * @brief Operations for retrieving and modifying User objects.
 */

import('lib.pkp.classes.user.PKPUser');

/* These constants are used user-selectable search fields. */
define('USER_FIELD_USERID', 'user_id');
define('USER_FIELD_FIRSTNAME', 'first_name');
define('USER_FIELD_LASTNAME', 'last_name');
define('USER_FIELD_USERNAME', 'username');
define('USER_FIELD_EMAIL', 'email');
define('USER_FIELD_URL', 'url');
define('USER_FIELD_INTERESTS', 'interests');
define('USER_FIELD_INITIAL', 'initial');
define('USER_FIELD_AFFILIATION', 'affiliation');
define('USER_FIELD_NONE', null);

class PKPUserDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Construct a new User object.
	 * @return PKPUser
	 */
	function newDataObject() {
		return new PKPUser();
	}

	/**
	 * Retrieve a user by ID.
	 * @param $userId int
	 * @param $allowDisabled boolean
	 * @return PKPUser
	 */
	function getById($userId, $allowDisabled = true) {
		$result = $this->retrieve(
			'SELECT * FROM users WHERE user_id = ?' . ($allowDisabled?'':' AND disabled = 0'),
			array((int) $userId)
		);

		$user = null;
		if ($result->RecordCount() != 0) {
			$user =& $this->_returnUserFromRowWithData($result->GetRowAssoc(false));
		}
		$result->Close();
		return $user;
	}

	/**
	 * Retrieve a user by username.
	 * @param $username string
	 * @param $allowDisabled boolean
	 * @return PKPUser
	 */
	function &getByUsername($username, $allowDisabled = true) {
		$result = $this->retrieve(
			'SELECT * FROM users WHERE username = ?' . ($allowDisabled?'':' AND disabled = 0'),
			array($username)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnUserFromRowWithData($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Get the user by the TDL ID (implicit authentication).
	 * @param $authstr string
	 * @param $allowDisabled boolean
	 * @return object User
	 */
	function &getUserByAuthStr($authstr, $allowDisabled = true) {
		$result = $this->retrieve(
			'SELECT * FROM users WHERE auth_str = ?' . ($allowDisabled?'':' AND disabled = 0'),
			array($authstr)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnUserFromRowWithData($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a user by email address.
	 * @param $email string
	 * @param $allowDisabled boolean
	 * @return PKPUser
	 */
	function &getUserByEmail($email, $allowDisabled = true) {
		$result = $this->retrieve(
			'SELECT * FROM users WHERE email = ?' . ($allowDisabled?'':' AND disabled = 0'),
			array($email)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnUserFromRowWithData($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a user by username and (encrypted) password.
	 * @param $username string
	 * @param $password string encrypted password
	 * @param $allowDisabled boolean
	 * @return PKPUser
	 */
	function &getUserByCredentials($username, $password, $allowDisabled = true) {
		$result = $this->retrieve(
			'SELECT * FROM users WHERE username = ? AND password = ?' . ($allowDisabled?'':' AND disabled = 0'),
			array($username, $password)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnUserFromRowWithData($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a list of all reviewers assigned to a submission.
	 * @param $contextId int
	 * @param $submissionId int
	 * @param $round int
	 * @return DAOResultFactory containing matching Users
	 */
	function getReviewersForSubmission($contextId, $submissionId, $round) {
		$result = $this->retrieve(
			'SELECT	u.*
			FROM	users u
			LEFT JOIN user_user_groups uug ON (uug.user_id = u.user_id)
			LEFT JOIN user_groups ug ON (ug.user_group_id = uug.user_group_id)
			LEFT JOIN review_assignments r ON (r.reviewer_id = u.user_id)
			WHERE	ug.context_id = ? AND
			ug.role_id = ? AND
			r.submission_id = ? AND
			r.round = ?
			ORDER BY last_name, first_name',
			array(
				(int) $contextId,
				ROLE_ID_REVIEWER,
				(int) $submissionId,
				(int) $round
			)
		);

		return new DAOResultFactory($result, $this, '_returnUserFromRowWithData');
	}

	/**
	 * Retrieve a list of all reviewers not assigned to the specified submission.
	 * @param $contextId int
	 * @param $submissionId int
	 * @param $reviewRound ReviewRound
	 * @param $name string
	 * @return array matching Users
	 */
	function getReviewersNotAssignedToSubmission($contextId, $submissionId, &$reviewRound, $name = '') {
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');

		$params = array((int) $contextId, ROLE_ID_REVIEWER, (int) $reviewRound->getStageId(), (int) $submissionId, (int) $reviewRound->getId());
		if (!empty($name)) $params[] = $params[] = $params[] = $params[] = "%$name%";

		$result = $this->retrieve(
			'SELECT	DISTINCT u.*
			FROM	users u
			JOIN user_user_groups uug ON (uug.user_id = u.user_id)
			JOIN user_groups ug ON (ug.user_group_id = uug.user_group_id AND ug.context_id = ? AND ug.role_id = ?)
			JOIN user_group_stage ugs ON (ugs.user_group_id = ug.user_group_id AND ugs.stage_id = ?)
			WHERE 0=(SELECT COUNT(r.reviewer_id)
			FROM review_assignments r
			WHERE r.submission_id = ? AND r.reviewer_id = u.user_id AND r.review_round_id = ?)' .
			(!empty($name)?' AND (first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR email LIKE ?)':'') .
			' ORDER BY last_name, first_name',
			$params
		);

		return new DAOResultFactory($result, $this, '_returnUserFromRowWithData');
	}

	/**
	 * Retrieve a list of all reviewers in a context.
	 * @param $contextId int
	 * @return array matching Users
	 */
	function getAllReviewers($contextId) {
		$result = $this->retrieve(
			'SELECT	u.*
			FROM	users u
			LEFT JOIN user_user_groups uug ON (uug.user_id = u.user_id)
			LEFT JOIN user_groups ug ON (ug.user_group_id = uug.user_group_id)
			WHERE	ug.context_id = ? AND
			ug.role_id = ?
			ORDER BY last_name, first_name',
			array((int) $contextId, ROLE_ID_REVIEWER)
		);

		return new DAOResultFactory($result, $this, '_returnUserFromRowWithData');
	}

	/**
	 * Given the ranges selected by the editor, produce a filtered list of reviewers
	 * @param $contextId int
	 * @param $stageId int WORKFLOW_STAGE_ID_...
	 * @param $name string|null Partial string match with reviewer name
	 * @param $doneMin int|null # of reviews completed int
	 * @param $doneMax int|null
	 * @param $avgMin int|null Average period of time in days to complete a review int
	 * @param $avgMax int|null
	 * @param $lastMin int|null Days since most recently completed review int
	 * @param $lastMax int|null
	 * @param $activeMin int|null How many reviews are currently being considered or underway int
	 * @param $activeMax int|null
	 * @param $interests array
	 * @param $submissionId int This parameter is in conjunction with the next two parameters i.e. reviewRoundId and reviewRound
	 * @param $reviewRoundId int Exclude users assigned to this round of the given submission
	 * @param $reviewRound int Filter users assigned to previous rounds of the given submission
	 * @param $rangeInfo|null object The desired range of results to return
	 * @return DAOResultFactory Iterator for matching users
	 */
	function getFilteredReviewers($contextId, $stageId, $name = null, $doneMin = null, $doneMax = null, $avgMin = null, $avgMax = null, $lastMin = null, $lastMax = null, $activeMin = null, $activeMax = null, $interests = array(), $submissionId = null, $reviewRoundId = null, $reviewRound = null, $rangeInfo = null) {
		// Timestamp math appears not to work in seconds in MySQL. Issue #1167.
		switch (Config::getVar('database', 'driver')) {
			case 'mysql':
			case 'mysqli':
				$dateDiffClause = 'DATEDIFF(rac.date_completed, rac.date_notified)';
				break;
			default:
				$dateDiffClause = 'DATE_PART(\'day\', rac.date_completed - rac.date_notified)';
		}
		$result = $this->retrieveRange(
			'SELECT	u.*,
				COUNT(DISTINCT rac.review_id) AS complete_count,
				AVG(' . $dateDiffClause . ') AS average_time,
				MAX(raf.date_assigned) AS last_assigned,
				COUNT(DISTINCT rai.review_id) AS incomplete_count
			FROM	users u
				JOIN user_user_groups uug ON (uug.user_id = u.user_id)
				JOIN user_groups ug ON (ug.user_group_id = uug.user_group_id AND ug.role_id = ? AND ug.context_id = ?)
				JOIN user_group_stage ugs ON (ugs.user_group_id = ug.user_group_id AND ugs.stage_id = ?)
				LEFT JOIN review_assignments ras ON (ras.submission_id = ? AND ras.stage_id = ? AND ras.review_round_id = ? AND ras.reviewer_id=u.user_id)
				LEFT JOIN review_assignments rac ON (rac.reviewer_id = u.user_id AND rac.date_notified IS NOT NULL AND rac.date_completed IS NOT NULL)
				LEFT JOIN review_assignments raf ON (raf.reviewer_id = u.user_id)
				LEFT JOIN review_assignments ran ON (ran.reviewer_id = u.user_id AND ran.review_id > raf.review_id)
				LEFT JOIN review_assignments rai ON (rai.reviewer_id = u.user_id AND rai.date_notified IS NOT NULL AND rai.date_completed IS NULL AND rai.cancelled = 0 AND rai.declined = 0 AND rai.replaced = 0)
			WHERE	ras.review_id IS NULL
				AND ran.review_id IS NULL' .
				($reviewRound !== null ? ' AND rac.submission_id = ? AND rac.stage_id = ? AND rac.round < ?':'') .
				str_repeat(' AND u.user_id IN (SELECT ui.user_id FROM user_interests ui JOIN controlled_vocab_entry_settings cves ON (ui.controlled_vocab_entry_id = cves.controlled_vocab_entry_id) WHERE cves.setting_name = \'interest\' AND LOWER(cves.setting_value) = ?)', count($interests)) .
				($name !== null?' AND (u.first_name LIKE ? OR u.middle_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)':'') . '
			GROUP BY u.user_id
			HAVING 1=1' .
				($doneMin !== null?' AND COUNT(DISTINCT rac.review_id) >= ?':'') .
				($doneMax !== null?' AND COUNT(DISTINCT rac.review_id) <= ?':'') .
				($avgMin !== null?" AND AVG($dateDiffClause) >= ?":'') .
				($avgMax !== null?" AND AVG($dateDiffClause) <= ?":'') .
				($activeMin !== null?' AND COUNT(DISTINCT rai.review_id) >= ?':'') .
				($activeMax !== null?' AND COUNT(DISTINCT rai.review_id) <= ?':'') .
				($lastMin !== null?' AND MAX(raf.date_assigned) <= ' . ($this->datetimeToDB(time() - ((int) $lastMin * 86400))):'') .
				($lastMax !== null?' AND MAX(raf.date_assigned) >= ' . ($this->datetimeToDB(time() - ((int) $lastMax * 86400))):'') .
			' ORDER BY last_name, first_name',
			array_merge(
				array(
					ROLE_ID_REVIEWER,
					(int) $contextId,
					(int) $stageId,
					(int) $submissionId, // null gets cast to 0 which doesn't exist
					(int) $stageId,
					(int) $reviewRoundId,
				),
				$reviewRound !== null?array((int) $submissionId, (int) $stageId, (int) $reviewRound):array(),
				array_map(array('PKPString', 'strtolower'), $interests),
				$name !== null?array('%'.(string) $name.'%'):array(),
				$name !== null?array('%'.(string) $name.'%'):array(),
				$name !== null?array('%'.(string) $name.'%'):array(),
				$name !== null?array('%'.(string) $name.'%'):array(),
				$name !== null?array('%'.(string) $name.'%'):array(),
				$doneMin !== null?array((int) $doneMin):array(),
				$doneMax !== null?array((int) $doneMax):array(),
				$avgMin !== null?array((int) $avgMin):array(),
				$avgMax !== null?array((int) $avgMax):array(),
				$activeMin !== null?array((int) $activeMin):array(),
				$activeMax !== null?array((int) $activeMax):array()
			),
			$rangeInfo
		);
		return new DAOResultFactory($result, $this, '_returnUserFromRowWithReviewerStats');
	}

	/**
	 * Return a user object from a DB row, including dependent data and reviewer stats.
	 * @param $row array
	 * @return User
	 */
	function _returnUserFromRowWithReviewerStats($row) {
		$user = $this->_returnUserFromRowWithData($row, false);
		$user->setData('lastAssigned', $row['last_assigned']);
		$user->setData('incompleteCount', $row['incomplete_count']);
		$user->setData('completeCount', $row['complete_count']);
		$user->setData('averageTime', $row['average_time']);
		HookRegistry::call('UserDAO::_returnUserFromRowWithReviewerStats', array(&$user, &$row));

		return $user;
	}

	/**
	 * Create and return a complete PKPUser object from a given row.
	 * @param $row array
	 * @param $callHook boolean
	 * @return PKPUser
	 */
	function &_returnUserFromRowWithData($row, $callHook = true) {
		$user =& $this->_returnUserFromRow($row, false);
		$this->getDataObjectSettings('user_settings', 'user_id', $row['user_id'], $user);

		if (isset($row['review_id'])) $user->review_id = $row['review_id'];
		HookRegistry::call('UserDAO::_returnUserFromRowWithData', array(&$user, &$row));

		return $user;
	}

	/**
	 * Internal function to return a User object from a row.
	 * @param $row array
	 * @param $callHook boolean
	 * @return PKPUser
	 */
	function &_returnUserFromRow($row, $callHook = true) {
		$user = $this->newDataObject();
		$user->setId($row['user_id']);
		$user->setUsername($row['username']);
		$user->setPassword($row['password']);
		$user->setSalutation($row['salutation']);
		$user->setFirstName($row['first_name']);
		$user->setMiddleName($row['middle_name']);
		$user->setInitials($row['initials']);
		$user->setLastName($row['last_name']);
		$user->setSuffix($row['suffix']);
		$user->setGender($row['gender']);
		$user->setEmail($row['email']);
		$user->setUrl($row['url']);
		$user->setPhone($row['phone']);
		$user->setMailingAddress($row['mailing_address']);
		$user->setBillingAddress($row['billing_address']);
		$user->setCountry($row['country']);
		$user->setLocales(isset($row['locales']) && !empty($row['locales']) ? explode(':', $row['locales']) : array());
		$user->setDateLastEmail($this->datetimeFromDB($row['date_last_email']));
		$user->setDateRegistered($this->datetimeFromDB($row['date_registered']));
		$user->setDateValidated($this->datetimeFromDB($row['date_validated']));
		$user->setDateLastLogin($this->datetimeFromDB($row['date_last_login']));
		$user->setMustChangePassword($row['must_change_password']);
		$user->setDisabled($row['disabled']);
		$user->setDisabledReason($row['disabled_reason']);
		$user->setAuthId($row['auth_id']);
		$user->setAuthStr($row['auth_str']);
		$user->setInlineHelp($row['inline_help']);

		if ($callHook) HookRegistry::call('UserDAO::_returnUserFromRow', array(&$user, &$row));

		return $user;
	}

	/**
	 * Insert a new user.
	 * @param $user User
	 */
	function insertObject($user) {
		if ($user->getDateRegistered() == null) {
			$user->setDateRegistered(Core::getCurrentDate());
		}
		if ($user->getDateLastLogin() == null) {
			$user->setDateLastLogin(Core::getCurrentDate());
		}
		$this->update(
			sprintf('INSERT INTO users
				(username, password, salutation, first_name, middle_name, initials, last_name, suffix, gender, email, url, phone, mailing_address, billing_address, country, locales, date_last_email, date_registered, date_validated, date_last_login, must_change_password, disabled, disabled_reason, auth_id, auth_str, inline_help)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, %s, %s, %s, %s, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($user->getDateLastEmail()), $this->datetimeToDB($user->getDateRegistered()), $this->datetimeToDB($user->getDateValidated()), $this->datetimeToDB($user->getDateLastLogin())),
			array(
				$user->getUsername(),
				$user->getPassword(),
				$user->getSalutation(),
				$user->getFirstName(),
				$user->getMiddleName(),
				$user->getInitials(),
				$user->getLastName(),
				$user->getSuffix(),
				$user->getGender(),
				$user->getEmail(),
				$user->getUrl(),
				$user->getPhone(),
				$user->getMailingAddress(),
				$user->getBillingAddress(),
				$user->getCountry(),
				join(':', $user->getLocales()),
				$user->getMustChangePassword() ? 1 : 0,
				$user->getDisabled() ? 1 : 0,
				$user->getDisabledReason(),
				$user->getAuthId()=='' ? null : (int) $user->getAuthId(),
				$user->getAuthStr(),
				(int) $user->getInlineHelp(),
			)
		);

		$user->setId($this->getInsertId());
		$this->updateLocaleFields($user);
		return $user->getId();
	}

	/**
	 * @copydoc DAO::getLocaleFieldNames
	 */
	function getLocaleFieldNames() {
		return array('biography', 'signature', 'gossip', 'affiliation');
	}

	/**
	 * @copydoc DAO::getAdditionalFieldNames()
	 */
	function getAdditionalFieldNames() {
		return array_merge(parent::getAdditionalFieldNames(), array(
			'orcid',
		));
	}

	/**
	 * @copydoc DAO::updateLocaleFields
	 */
	function updateLocaleFields($user) {
		$this->updateDataObjectSettings('user_settings', $user, array(
			'user_id' => (int) $user->getId()
		));
	}

	/**
	 * Update an existing user.
	 * @param $user PKPUser
	 */
	function updateObject($user) {
		if ($user->getDateLastLogin() == null) {
			$user->setDateLastLogin(Core::getCurrentDate());
		}

		$this->updateLocaleFields($user);

		return $this->update(
			sprintf('UPDATE	users
				SET	username = ?,
					password = ?,
					salutation = ?,
					first_name = ?,
					middle_name = ?,
					initials = ?,
					last_name = ?,
					suffix = ?,
					gender = ?,
					email = ?,
					url = ?,
					phone = ?,
					mailing_address = ?,
					billing_address = ?,
					country = ?,
					locales = ?,
					date_last_email = %s,
					date_validated = %s,
					date_last_login = %s,
					must_change_password = ?,
					disabled = ?,
					disabled_reason = ?,
					auth_id = ?,
					auth_str = ?,
					inline_help = ?
				WHERE	user_id = ?',
				$this->datetimeToDB($user->getDateLastEmail()), $this->datetimeToDB($user->getDateValidated()), $this->datetimeToDB($user->getDateLastLogin())),
			array(
				$user->getUsername(),
				$user->getPassword(),
				$user->getSalutation(),
				$user->getFirstName(),
				$user->getMiddleName(),
				$user->getInitials(),
				$user->getLastName(),
				$user->getSuffix(),
				$user->getGender(),
				$user->getEmail(),
				$user->getUrl(),
				$user->getPhone(),
				$user->getMailingAddress(),
				$user->getBillingAddress(),
				$user->getCountry(),
				join(':', $user->getLocales()),
				$user->getMustChangePassword() ? 1 : 0,
				$user->getDisabled() ? 1 : 0,
				$user->getDisabledReason(),
				$user->getAuthId()=='' ? null : (int) $user->getAuthId(),
				$user->getAuthStr(),
				(int) $user->getInlineHelp(),
				(int) $user->getId(),
			)
		);
	}

	/**
	 * Delete a user.
	 * @param $user User
	 */
	function deleteObject($user) {
		return $this->deleteUserById($user->getId());
	}

	/**
	 * Delete a user by ID.
	 * @param $userId int
	 */
	function deleteUserById($userId) {
		$this->update('DELETE FROM user_settings WHERE user_id = ?', array((int) $userId));
		return $this->update('DELETE FROM users WHERE user_id = ?', array((int) $userId));
	}

	/**
	 * Retrieve a user's name.
	 * @param $userId int
	 * @param $allowDisabled boolean
	 * @return string
	 */
	function getUserFullName($userId, $allowDisabled = true) {
		$result = $this->retrieve(
			'SELECT first_name, middle_name, last_name, suffix FROM users WHERE user_id = ?' . ($allowDisabled?'':' AND disabled = 0'),
			array((int) $userId)
		);

		if($result->RecordCount() == 0) {
			$returner = false;
		} else {
			$returner = $result->fields[0] . ' ' . (empty($result->fields[1]) ? '' : $result->fields[1] . ' ') . $result->fields[2] . (empty($result->fields[3]) ? '' : ', ' . $result->fields[3]);
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a user's email address.
	 * @param $userId int
	 * @param $allowDisabled boolean
	 * @return string
	 */
	function getUserEmail($userId, $allowDisabled = true) {
		$result = $this->retrieve(
			'SELECT email FROM users WHERE user_id = ?' . ($allowDisabled?'':' AND disabled = 0'),
			array((int) $userId)
		);

		if($result->RecordCount() == 0) {
			$returner = false;
		} else {
			$returner = $result->fields[0];
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve an array of users matching a particular field value.
	 * @param $field string the field to match on
	 * @param $match string "is" for exact match, otherwise assume "like" match
	 * @param $value mixed the value to match
	 * @param $allowDisabled boolean
	 * @param $dbResultRange object The desired range of results to return
	 * @return DAOResultFactory
	 */
	function getUsersByField($field = USER_FIELD_NONE, $match = null, $value = null, $allowDisabled = true, $dbResultRange = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$sql = 'SELECT DISTINCT u.* FROM users u';
		switch ($field) {
			case USER_FIELD_USERID:
				$sql .= ' WHERE u.user_id = ?';
				$var = (int) $value;
				break;
			case USER_FIELD_USERNAME:
				$sql .= ' WHERE LOWER(u.username) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case USER_FIELD_INITIAL:
				$sql .= ' WHERE LOWER(u.last_name) LIKE LOWER(?)';
				$var = "$value%";
				break;
			case USER_FIELD_INTERESTS:
				$interestDao = DAORegistry::getDAO('InterestDAO');  // Loaded to ensure interest constant is in namespace
				$sql .=', controlled_vocabs cv, controlled_vocab_entries cve, controlled_vocab_entry_settings cves, user_interests ui
					WHERE cv.symbolic = \'' . CONTROLLED_VOCAB_INTEREST .  '\' AND cve.controlled_vocab_id = cv.controlled_vocab_id
					AND cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id AND LOWER(cves.setting_value) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)
					AND ui.user_id = u.user_id AND cve.controlled_vocab_entry_id = ui.controlled_vocab_entry_id';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case USER_FIELD_EMAIL:
				$sql .= ' WHERE LOWER(u.email) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case USER_FIELD_URL:
				$sql .= ' WHERE LOWER(u.url) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case USER_FIELD_FIRSTNAME:
				$sql .= ' WHERE LOWER(u.first_name) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case USER_FIELD_LASTNAME:
				$sql .= ' WHERE LOWER(u.last_name) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
		}

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$orderSql = ($sortBy?(' ORDER BY ' . $roleDao->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : '');
		if ($field != USER_FIELD_NONE) $result = $this->retrieveRange($sql . ($allowDisabled?'':' AND u.disabled = 0') . $orderSql, $var, $dbResultRange);
		else $result = $this->retrieveRange($sql . ($allowDisabled?'':' WHERE u.disabled = 0') . $orderSql, false, $dbResultRange);

		return new DAOResultFactory($result, $this, '_returnUserFromRowWithData');
	}

	/**
	 * Retrieve an array of users with no role defined.
	 * @param $allowDisabled boolean
	 * @param $dbResultRange object The desired range of results to return
	 * @return DAOResultFactory
	 */
	function getUsersWithNoRole($allowDisabled = true, $dbResultRange = null) {
		$sql = 'SELECT u.* FROM users u LEFT JOIN roles r ON u.user_id=r.user_id WHERE r.role_id IS NULL';

		$orderSql = ' ORDER BY u.last_name, u.first_name'; // FIXME Add "sort field" parameter?

		$result = $this->retrieveRange($sql . ($allowDisabled?'':' AND u.disabled = 0') . $orderSql, false, $dbResultRange);

		return new DAOResultFactory($result, $this, '_returnUserFromRowWithData');
	}

	/**
	 * Check if a user exists with the specified user ID.
	 * @param $userId int
	 * @param $allowDisabled boolean
	 * @return boolean
	 */
	function userExistsById($userId, $allowDisabled = true) {
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM users WHERE user_id = ?' . ($allowDisabled?'':' AND disabled = 0'),
			array((int) $userId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Check if a user exists with the specified username.
	 * @param $username string
	 * @param $userId int optional, ignore matches with this user ID
	 * @param $allowDisabled boolean
	 * @return boolean
	 */
	function userExistsByUsername($username, $userId = null, $allowDisabled = true) {
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM users WHERE username = ?' . (isset($userId) ? ' AND user_id != ?' : '') . ($allowDisabled?'':' AND disabled = 0'),
			isset($userId) ? array($username, (int) $userId) : array($username)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Check if a user exists with the specified email address.
	 * @param $email string
	 * @param $userId int optional, ignore matches with this user ID
	 * @param $allowDisabled boolean
	 * @return boolean
	 */
	function userExistsByEmail($email, $userId = null, $allowDisabled = true) {
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM users WHERE email = ?' . (isset($userId) ? ' AND user_id != ?' : '') . ($allowDisabled?'':' AND disabled = 0'),
			isset($userId) ? array($email, (int) $userId) : array($email)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Get the ID of the last inserted user.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('users', 'user_id');
	}

	/**
	 * Return a list of gender names for use in the user profile.
	 * @return array
	 */
	function getGenderOptions() {
		return array(
			'' => '',
			'M' => 'user.masculine',
			'F' => 'user.feminine',
			'O' => 'user.other',
		);
	}
}

?>
