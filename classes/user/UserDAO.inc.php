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
		$user->setLastName($row['last_name']);
		$user->setAffiliation($row['affiliation']);
		$user->setEmail($row['email']);
		$user->setPhone($row['phone']);
		$user->setFax($row['fax']);
		$user->setMailingAddress($row['mailing_address']);
		$user->setBiography($row['biography']);
		$user->setDateRegistered($row['date_registered']);
		
		return $user;
	}
	
	/**
	 * Insert a new user.
	 * @param $user User
	 */
	function insertUser(&$user) {
		return $this->update(
			'INSERT INTO users
				(username, password, first_name, middle_name, last_name, affiliation, email, phone, fax, mailing_address, biography, date_registered)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$user->getUsername(),
				$user->getPassword(),
				$user->getFirstName(),
				$user->getMiddleName(),
				$user->getLastName(),
				$user->getAffiliation(),
				$user->getEmail(),
				$user->getPhone(),
				$user->getFax(),
				$user->getMailingAddress(),
				$user->getBiography(),
				$user->getDateRegistered()
			)
		);
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
					last_name = ?,
					affiliation = ?,
					email = ?,
					phone = ?,
					fax = ?,
					mailing_address = ?,
					biography = ?,
					date_registered = ?
				WHERE user_id = ?',
			array(
				$user->getUsername(),
				$user->getPassword(),
				$user->getFirstName(),
				$user->getMiddleName(),
				$user->getLastName(),
				$user->getAffiliation(),
				$user->getEmail(),
				$user->getPhone(),
				$user->getFax(),
				$user->getMailingAddress(),
				$user->getBiography(),
				$user->getDateRegistered(),
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
	 * Retrieve an array of users matching a particular field value.
	 * @param $field string the field to match on
	 * @param $match string "is" for exact match, otherwise assume "like" match
	 * @param $value mixed the value to match
	 * @return array matching Users
	 */
	function &getUsersByField($field, $match, $value) {
		$sql = 'SELECT * FROM users WHERE ';
		switch($field) {
			case 'username':
			default:
				$sql .= $match == 'is' ? 'username = ?' : 'LOWER(username) LIKE LOWER(?)';
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
	function userExistsByEmail($email) {
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
