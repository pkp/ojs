<?php

/**
 * UserDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user
 *
 * Class for User DAO.
 * Operations for retrieving and modifying User objects.
 *
 * $Id$
 */

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
	 * @return User
	 */
	function &getUser($userId) {
		$result = &$this->retrieve(
			'SELECT * FROM users WHERE user_id = ?', $userId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnUserFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve a user by username.
	 * @param $username string
	 * @return User
	 */
	function &getUserByUsername($username) {
		$result = &$this->retrieve(
			'SELECT * FROM users WHERE username = ?', $username
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnUserFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve a user by email address.
	 * @param $email string
	 * @return User
	 */
	function &getUserByEmail($email) {
		$result = &$this->retrieve(
			'SELECT * FROM users WHERE email = ?', $email
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnUserFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve a user by username and (encrypted) password.
	 * @param $username string
	 * @param $password string encrypted password
	 * @return User
	 */
	function &getUserByCredentials($username, $password) {
		$result = &$this->retrieve(
			'SELECT * FROM users WHERE username = ? AND password = ?', array($username, $password)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnUserFromRow($result->GetRowAssoc(false));
		}
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
		$user->setPassword($row['password']);
		$user->setFirstName($row['first_name']);
		$user->setMiddleName($row['middle_name']);
		$user->setInitials($row['initials']);
		$user->setLastName($row['last_name']);
		$user->setAffiliation($row['affiliation']);
		$user->setEmail($row['email']);
		$user->setPhone($row['phone']);
		$user->setFax($row['fax']);
		$user->setMailingAddress($row['mailing_address']);
		$user->setBiography($row['biography']);
		$user->setInterests($row['interests']);
		$user->setLocales(isset($row['locales']) && !empty($row['locales']) ? explode(':', $row['locales']) : array());
		$user->setDateRegistered($row['date_registered']);
		$user->setDateLastLogin($row['date_last_login']);
		$user->setMustChangePassword($row['must_change_password']);
		
		return $user;
	}
	
	/**
	 * Insert a new user.
	 * @param $user User
	 */
	function insertUser(&$user) {
		$ret = $this->update(
			'INSERT INTO users
				(username, password, first_name, middle_name, initials, last_name, affiliation, email, phone, fax, mailing_address, biography, interests, locales, date_registered, date_last_login, must_change_password)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$user->getUsername(),
				$user->getPassword(),
				$user->getFirstName(),
				$user->getMiddleName(),
				$user->getInitials(),
				$user->getLastName(),
				$user->getAffiliation(),
				$user->getEmail(),
				$user->getPhone(),
				$user->getFax(),
				$user->getMailingAddress(),
				$user->getBiography(),
				$user->getInterests(),
				join(':', $user->getLocales()),
				$user->getDateRegistered() == null ? Core::getCurrentDate() : $user->getDateRegistered(),
				$user->getDateLastLogin() == null ? Core::getCurrentDate() : $user->getDateLastLogin(),
				$user->getMustChangePassword()
			)
		);
		if ($ret) {
			$user->setUserId($this->getInsertUserId());
		}
		return $ret;
	}
	
	/**
	 * Update an existing user.
	 * @param $user User
	 */
	function updateUser(&$user) {
		return $this->update(
			'UPDATE users
				SET
					username = ?,
					password = ?,
					first_name = ?,
					middle_name = ?,
					initials = ?,
					last_name = ?,
					affiliation = ?,
					email = ?,
					phone = ?,
					fax = ?,
					mailing_address = ?,
					biography = ?,
					interests = ?,
					locales = ?,
					date_last_login = ?,
					must_change_password = ?
				WHERE user_id = ?',
			array(
				$user->getUsername(),
				$user->getPassword(),
				$user->getFirstName(),
				$user->getMiddleName(),
				$user->getInitials(),
				$user->getLastName(),
				$user->getAffiliation(),
				$user->getEmail(),
				$user->getPhone(),
				$user->getFax(),
				$user->getMailingAddress(),
				$user->getBiography(),
				$user->getInterests(),
				join(':', $user->getLocales()),
				$user->getDateLastLogin() == null ? Core::getCurrentDate() : $user->getDateLastLogin(),
				$user->getMustChangePassword(),
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
	 * @return string
	 */
	function getUserFullName($userId) {
		$result = $this->retrieve(
			'SELECT first_name, middle_name, last_name FROM users WHERE user_id = ?',
			$userId
		);
		
		if($result->RecordCount() == 0) {
			return false;
		} else {
			return $result->fields[0] . ' ' . (empty($result->fields[1]) ? '' : $result->fields[1] . ' ') . $result->fields[2];
		}
	}
	
	/**
	 * Retrieve a user's email address.
	 * @param int $userId
	 * @return string
	 */
	function getUserEmail($userId) {
		$result = $this->retrieve(
			'SELECT email FROM users WHERE user_id = ?',
			$userId
		);
		
		if($result->RecordCount() == 0) {
			return false;
		} else {
			return $result->fields[0];
		}
	}
	
	/**
	 * Retrieve an array of users matching a particular field value.
	 * @param $field string the field to match on
	 * @param $match string "is" for exact match, otherwise assume "like" match
	 * @param $value mixed the value to match
	 * @return array matching Users
	 */
	function &getUsersByField($field, $match, $value) {
		$sql = 'SELECT * FROM users WHERE ';
		switch ($field) {
			case 'userId':
				$sql .= 'username = ?';
				$var = $value;
				break;
			case 'username':
			default:
				$sql .= $match == 'is' ? 'username = ?' : 'LOWER(username) LIKE LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case 'firstName':
			default:
				$sql .= $match == 'is' ? 'first_name = ?' : 'LOWER(first_name) LIKE LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
			case 'lastName':
			default:
				$sql .= $match == 'is' ? 'last_name = ?' : 'LOWER(last_name) LIKE LOWER(?)';
				$var = $match == 'is' ? $value : "%$value%";
				break;
		}
		$result = &$this->retrieve(
			$sql, $var
		);
		
		$users = array();
		
		while (!$result->EOF) {
			$users[] = &$this->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $users;
	}
	
	/**
	 * Check if a user exists with the specified username.
	 * @param $username string
	 * @param $userId int optional, ignore matches with this user ID
	 * @return boolean
	 */
	function userExistsByUsername($username, $userId = null) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM users WHERE username = ?' . (isset($userId) ? ' AND user_id != ?' : ''),
			isset($userId) ? array($username, $userId) : $username
		);
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}
	
	/**
	 * Check if a user exists with the specified email address.
	 * @param $email string
	 * @param $userId int optional, ignore matches with this user ID
	 * @return boolean
	 */
	function userExistsByEmail($email, $userId = null) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM users WHERE email = ?' . (isset($userId) ? ' AND user_id != ?' : ''),
			isset($userId) ? array($email, $userId) : $email
		);
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
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
