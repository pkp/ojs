<?php

/**
 * @file UserDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user
 * @class UserDAO
 *
 * Class for User DAO.
 * Operations for retrieving and modifying User objects.
 *
 * $Id$
 */

import('user.User');

/* These constants are used user-selectable search fields. */
define('USER_FIELD_USERID', 'user_id');
define('USER_FIELD_FIRSTNAME', 'first_name');
define('USER_FIELD_LASTNAME', 'last_name');
define('USER_FIELD_USERNAME', 'username');
define('USER_FIELD_EMAIL', 'email');
define('USER_FIELD_URL', 'url');
define('USER_FIELD_INTERESTS', 'interests');
define('USER_FIELD_INITIAL', 'initial');
define('USER_FIELD_NONE', null);

class UserDAO extends DAO {

	/**
	 * Constructor.
	 */
	function UserDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a user by ID.
	 * @param $userId int
	 * @param $allowDisabled boolean
	 * @return User
	 */
	function &getUser($userId, $allowDisabled = true) {
		$result = &$this->retrieve(
			'SELECT * FROM users WHERE user_id = ?' . ($allowDisabled?'':' AND disabled = 0'), $userId
		);

		$user = null;
		if ($result->RecordCount() != 0) {
			$user = &$this->_returnUserFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $user;
	}
	
	/**
	 * Retrieve a user by username.
	 * @param $username string
	 * @param $allowDisabled boolean
	 * @return User
	 */
	function &getUserByUsername($username, $allowDisabled = true) {
		$result = &$this->retrieve(
			'SELECT * FROM users WHERE username = ?' . ($allowDisabled?'':' AND disabled = 0'), $username
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnUserFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}
	
	/**
	 * Retrieve a user by email address.
	 * @param $email string
	 * @param $allowDisabled boolean
	 * @return User
	 */
	function &getUserByEmail($email, $allowDisabled = true) {
		$result = &$this->retrieve(
			'SELECT * FROM users WHERE email = ?' . ($allowDisabled?'':' AND disabled = 0'), $email
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnUserFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}
	
	/**
	 * Retrieve a user by username and (encrypted) password.
	 * @param $username string
	 * @param $password string encrypted password
	 * @param $allowDisabled boolean
	 * @return User
	 */
	function &getUserByCredentials($username, $password, $allowDisabled = true) {
		$result = &$this->retrieve(
			'SELECT * FROM users WHERE username = ? AND password = ?' . ($allowDisabled?'':' AND disabled = 0'), array($username, $password)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnUserFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}
	
	/**
	 * Internal function to return a User object from a row.
	 * @param $row array
	 * @return User
	 */
	function &_returnUserFromRow(&$row) {
		$user = &new User();
		$user->setUserId($row['user_id']);
		$user->setUsername($row['username']);
		$user->setSignature($row['signature']);
		$user->setPassword($row['password']);
		$user->setFirstName($row['first_name']);
		$user->setMiddleName($row['middle_name']);
		$user->setInitials($row['initials']);
		$user->setLastName($row['last_name']);
		$user->setAffiliation($row['affiliation']);
		$user->setEmail($row['email']);
		$user->setUrl($row['url']);
		$user->setPhone($row['phone']);
		$user->setFax($row['fax']);
		$user->setMailingAddress($row['mailing_address']);
		$user->setCountry($row['country']);
		$user->setBiography($row['biography']);
		$user->setInterests($row['interests']);
		$user->setLocales(isset($row['locales']) && !empty($row['locales']) ? explode(':', $row['locales']) : array());
		$user->setDateLastEmail($this->datetimeFromDB($row['date_last_email']));
		$user->setDateRegistered($this->datetimeFromDB($row['date_registered']));
		$user->setDateValidated($this->datetimeFromDB($row['date_validated']));
		$user->setDateLastLogin($this->datetimeFromDB($row['date_last_login']));
		$user->setMustChangePassword($row['must_change_password']);
		$user->setDisabled($row['disabled']);
		$user->setDisabledReason($row['disabled_reason']);
		$user->setAuthId($row['auth_id']);
		
		HookRegistry::call('UserDAO::_returnUserFromRow', array(&$user, &$row));

		return $user;
	}
	
	/**
	 * Insert a new user.
	 * @param $user User
	 */
	function insertUser(&$user) {
		if ($user->getDateRegistered() == null) {
			$user->setDateRegistered(Core::getCurrentDate());
		}
		if ($user->getDateLastLogin() == null) {
			$user->setDateLastLogin(Core::getCurrentDate());
		}
		$this->update(
			sprintf('INSERT INTO users
				(username, signature, password, first_name, middle_name, initials, last_name, affiliation, email, url, phone, fax, mailing_address, country, biography, interests, locales, date_last_email, date_registered, date_validated, date_last_login, must_change_password, disabled, disabled_reason, auth_id)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, %s, %s, %s, %s, ?, ?, ?, ?)',
				$this->datetimeToDB($user->getDateLastEmail()), $this->datetimeToDB($user->getDateRegistered()), $this->datetimeToDB($user->getDateValidated()), $this->datetimeToDB($user->getDateLastLogin())),
			array(
				$user->getUsername(),
				$user->getSignature(),
				$user->getPassword(),
				$user->getFirstName(),
				$user->getMiddleName(),
				$user->getInitials(),
				$user->getLastName(),
				$user->getAffiliation(),
				$user->getEmail(),
				$user->getUrl(),
				$user->getPhone(),
				$user->getFax(),
				$user->getMailingAddress(),
				$user->getCountry(),
				$user->getBiography(),
				$user->getInterests(),
				join(':', $user->getLocales()),
				$user->getMustChangePassword(),
				$user->getDisabled() ? 1 : 0,
				$user->getDisabledReason(),
				$user->getAuthId()
			)
		);
		
		$user->setUserId($this->getInsertUserId());
		return $user->getUserId();
	}
	
	/**
	 * Update an existing user.
	 * @param $user User
	 */
	function updateUser(&$user) {
		if ($user->getDateLastLogin() == null) {
			$user->setDateLastLogin(Core::getCurrentDate());
		}
		return $this->update(
			sprintf('UPDATE users
				SET
					username = ?,
					signature = ?,
					password = ?,
					first_name = ?,
					middle_name = ?,
					initials = ?,
					last_name = ?,
					affiliation = ?,
					email = ?,
					url = ?,
					phone = ?,
					fax = ?,
					mailing_address = ?,
					country = ?,
					biography = ?,
					interests = ?,
					locales = ?,
					date_last_email = %s,
					date_validated = %s,
					date_last_login = %s,
					must_change_password = ?,
					disabled = ?,
					disabled_reason = ?,
					auth_id = ?
				WHERE user_id = ?',
				$this->datetimeToDB($user->getDateLastEmail()), $this->datetimeToDB($user->getDateValidated()), $this->datetimeToDB($user->getDateLastLogin())),
			array(
				$user->getUsername(),
				$user->getSignature(),
				$user->getPassword(),
				$user->getFirstName(),
				$user->getMiddleName(),
				$user->getInitials(),
				$user->getLastName(),
				$user->getAffiliation(),
				$user->getEmail(),
				$user->getUrl(),
				$user->getPhone(),
				$user->getFax(),
				$user->getMailingAddress(),
				$user->getCountry(),
				$user->getBiography(),
				$user->getInterests(),
				join(':', $user->getLocales()),
				$user->getMustChangePassword(),
				$user->getDisabled()?1:0,
				$user->getDisabledReason(),
				$user->getAuthId(),
				$user->getUserId()
			)
		);
	}
	
	/**
	 * Delete a user.
	 * @param $user User
	 */
	function deleteUser(&$user) {
		return $this->deleteUserById($user->getUserId());
	}
	
	/**
	 * Delete a user by ID.
	 * @param $userId int
	 */
	function deleteUserById($userId) {
		return $this->update(
			'DELETE FROM users WHERE user_id = ?', $userId
		);
	}
	
	/**
	 * Retrieve a user's name.
	 * @param int $userId
	 * @param $allowDisabled boolean
	 * @return string
	 */
	function getUserFullName($userId, $allowDisabled = true) {
		$result = &$this->retrieve(
			'SELECT first_name, middle_name, last_name FROM users WHERE user_id = ?' . ($allowDisabled?'':' AND disabled = 0'),
			$userId
		);
		
		if($result->RecordCount() == 0) {
			$returner = false;
		} else {
			$returner = $result->fields[0] . ' ' . (empty($result->fields[1]) ? '' : $result->fields[1] . ' ') . $result->fields[2];
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Retrieve a user's email address.
	 * @param int $userId
	 * @param $allowDisabled boolean
	 * @return string
	 */
	function getUserEmail($userId, $allowDisabled = true) {
		$result = &$this->retrieve(
			'SELECT email FROM users WHERE user_id = ?' . ($allowDisabled?'':' AND disabled = 0'),
			$userId
		);
		
		if($result->RecordCount() == 0) {
			$returner = false;
		} else {
			$returner = $result->fields[0];
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve an array of users.
	 * @param $sort string the field to sort on
	 * @param $order string the sort order (+|-)
	 * @param $allowDisabled boolean
	 * @param $dbResultRange object The desired range of results to return
	 * @return array of Users 
 	 */
	function &getUsers($sort='lastName', $order='+', $allowDisabled = true, $dbResultRange = null) {
		switch ($sort) {
			case 'username':
				break;
			case 'firstName':
				$sort = 'first_name';
				break;
			case 'lastName':
			default:
				$sort = 'last_name';
		}

		if ($order == '-') {
			$order = 'DESC';
		} else {
			$order = 'ASC';
		}
	
		$result = &$this->retrieveRange(
			'SELECT * FROM users' . ($allowDisabled?'':' AND disabled = 0') . ' ORDER BY ' . $sort . ' '. $order,
			false,
			$dbResultRange
		); 

		$returner = &new DAOResultFactory($result, $this, '_returnUserFromRow');
		return $returner;
	}

	/**
	 * Retrieve an array of users matching a particular field value.
	 * @param $field string the field to match on
	 * @param $match string "is" for exact match, otherwise assume "like" match
	 * @param $value mixed the value to match
	 * @param $allowDisabled boolean
	 * @param $dbResultRange object The desired range of results to return
	 * @return array matching Users
	 */

	function &getUsersByField($field = USER_FIELD_NONE, $match = null, $value = null, $allowDisabled = true, $dbResultRange = null) {
		$sql = 'SELECT * FROM users';
		switch ($field) {
			case USER_FIELD_USERID:
				$sql .= ' WHERE user_id = ?';
				$var = $value;
				break;
			case USER_FIELD_USERNAME:
				$sql .= ' WHERE LOWER(username) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case USER_FIELD_INITIAL:
				$sql .= ' WHERE LOWER(last_name) LIKE LOWER(?)';
				$var = "$value%";
				break;
			case USER_FIELD_INTERESTS:
				$sql .= ' WHERE LOWER(interests) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case USER_FIELD_EMAIL:
				$sql .= ' WHERE LOWER(email) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case USER_FIELD_URL:
				$sql .= ' WHERE LOWER(url) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case USER_FIELD_FIRSTNAME:
				$sql .= ' WHERE LOWER(first_name) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case USER_FIELD_LASTNAME:
				$sql .= ' WHERE LOWER(last_name) ' . ($match == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
		}
		
		$orderSql = ' ORDER BY last_name, first_name'; // FIXME Add "sort field" parameter?
		
		if ($field != USER_FIELD_NONE) $result = &$this->retrieveRange($sql . ($allowDisabled?'':' AND disabled = 0') . $orderSql, $var, $dbResultRange);
		else $result = &$this->retrieveRange($sql . ($allowDisabled?'':' WHERE disabled = 0') . $orderSql, false, $dbResultRange);
		
		$returner = &new DAOResultFactory($result, $this, '_returnUserFromRow');
		return $returner;
	}

	/**
	 * Check if a user exists with the specified user ID.
	 * @param $userId int
	 * @param $allowDisabled boolean
	 * @return boolean
	 */
	function userExistsById($userId, $allowDisabled = true) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM users WHERE user_id = ?' . ($allowDisabled?'':' AND disabled = 0'), $userId
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

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
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM users WHERE username = ?' . (isset($userId) ? ' AND user_id != ?' : '') . ($allowDisabled?'':' AND disabled = 0'),
			isset($userId) ? array($username, $userId) : $username
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

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
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM users WHERE email = ?' . (isset($userId) ? ' AND user_id != ?' : '') . ($allowDisabled?'':' AND disabled = 0'),
			isset($userId) ? array($email, $userId) : $email
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Get the ID of the last inserted user.
	 * @return int
	 */
	function getInsertUserId() {
		return $this->getInsertId('users', 'user_id');
	}
	
}

?>
