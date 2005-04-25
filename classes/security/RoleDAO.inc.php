<?php

/**
 * RoleDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package security
 *
 * Class for Role DAO.
 * Operations for retrieving and modifying Role objects.
 *
 * $Id$
 */

import('security.Role');

class RoleDAO extends DAO {

	/**
	 * Constructor.
	 */
	function RoleDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a role.
	 * @param $journalId int
	 * @param $userId int
	 * @param $roleId int
	 * @return Role
	 */
	function &getRole($journalId, $userId, $roleId) {
		$result = &$this->retrieve(
			'SELECT * FROM roles WHERE journal_id = ? AND user_id = ? AND role_id = ?',
			array(
				$journalId,
				$userId,
				$roleId
			)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnRoleFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return a Role object from a row.
	 * @param $row array
	 * @return Role
	 */
	function &_returnRoleFromRow(&$row) {
		$role = &new Role();
		$role->setJournalId($row['journal_id']);
		$role->setUserId($row['user_id']);
		$role->setRoleId($row['role_id']);
		
		return $role;
	}
	
	/**
	 * Insert a new role.
	 * @param $role Role
	 */
	function insertRole(&$role) {
		return $this->update(
			'INSERT INTO roles
				(journal_id, user_id, role_id)
				VALUES
				(?, ?, ?)',
			array(
				$role->getJournalId(),
				$role->getUserId(),
				$role->getRoleId()
			)
		);
	}
	
	/**
	 * Delete a role.
	 * @param $role Role
	 */
	function deleteRole(&$role) {
		return $this->update(
			'DELETE FROM roles WHERE journal_id = ? AND user_id = ? AND role_id = ?',
			array(
				$role->getJournalId(),
				$role->getUserId(),
				$role->getRoleId()
			)
		);
	}
	
	/**
	 * Retrieve a list of all roles for a specified user.
	 * @param $userId int
	 * @param $journalId int optional, include roles only in this journal
	 * @return array matching Roles
	 */
	function &getRolesByUserId($userId, $journalId = null) {
		$roles = array();
				
		$result = &$this->retrieve(
			'SELECT * FROM roles WHERE user_id = ?' . (isset($journalId) ? ' AND journal_id = ?' : ''),
			isset($journalId) ? array($userId, $journalId) : $userId
		);
		
		while (!$result->EOF) {
			$roles[] = &$this->_returnRoleFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $roles;
	}
	
	/**
	 * Retrieve a list of users in a specified role.
	 * @param $roleId int
	 * @param $journalId int optional, include users only in this journal
	 * @return array matching Users
	 */
	function &getUsersByRoleId($roleId, $journalId = null, $searchType = null, $search = null, $searchMatch = null) {
		$users = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');

		$paramArray = isset($journalId) ? array($roleId, $journalId) : array($roleId);
		$searchSql = '';

		if (isset($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_FIRSTNAME:
				$searchSql = 'AND ' . ($searchMatch=='is'?'u.first_name=?':'LOWER(u.first_name) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_LASTNAME:
				$searchSql = 'AND ' . ($searchMatch=='is'?'u.last_name=?':'LOWER(u.last_name) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_USERNAME:
				$searchSql = 'AND ' . ($searchMatch=='is'?'u.username=?':'LOWER(u.username) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_EMAIL:
				$searchSql = 'AND ' . ($searchMatch=='is'?'u.email=?':'LOWER(u.email) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INTERESTS:
				$searchSql = 'AND ' . ($searchMatch=='is'?'u.interests=?':'LOWER(u.interests) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(u.last_name) LIKE LOWER(?) OR LOWER(u.username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}
		
		$result = &$this->retrieve(

			'SELECT u.* FROM users AS u, roles AS r WHERE u.user_id = r.user_id AND r.role_id = ?' . (isset($journalId) ? ' AND r.journal_id = ?' : '') . ' ' . $searchSql,
			$paramArray
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $users;
	}
	
	/**
	 * Retrieve a list of all users with some role in the specified journal.
	 * @param $journalId int
	 * @return array matching Users
	 */
	function &getUsersByJournalId($journalId, $searchType = null, $search = null, $searchMatch = null) {
		$users = array();

		$userDao = &DAORegistry::getDAO('UserDAO');

		$paramArray = array($journalId);
		$searchSql = '';

		if (isset($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_FIRSTNAME:
				$searchSql = 'AND ' . ($searchMatch=='is'?'u.first_name=?':'LOWER(u.first_name) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_LASTNAME:
				$searchSql = 'AND ' . ($searchMatch=='is'?'u.last_name=?':'LOWER(u.last_name) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_USERNAME:
				$searchSql = 'AND ' . ($searchMatch=='is'?'u.username=?':'LOWER(u.username) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_EMAIL:
				$searchSql = 'AND ' . ($searchMatch=='is'?'u.email=?':'LOWER(u.email) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INTERESTS:
				$searchSql = 'AND ' . ($searchMatch=='is'?'u.interests=?':'LOWER(u.interests) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(u.last_name) LIKE LOWER(?) OR LOWER(u.username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}
		
		$result = &$this->retrieve(

			'SELECT DISTINCT u.* FROM users AS u, roles AS r WHERE u.user_id = r.user_id AND r.journal_id = ? ' . $searchSql,
			$paramArray
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();

		return $users;
	}
	
	/**
	 * Delete all roles for a specified journal.
	 * @param $journalId int
	 */
	function deleteRoleByJournalId($journalId) {
		return $this->update(
			'DELETE FROM roles WHERE journal_id = ?', $journalId
		);
	}
	
	/**
	 * Delete all roles for a specified journal.
	 * @param $userId int
	 * @param $journalId int optional, include roles only in this journal
	 * @param $roleId int optional, include only this role
	 */
	function deleteRoleByUserId($userId, $journalId  = null, $roleId = null) {
		return $this->update(
			'DELETE FROM roles WHERE user_id = ?' . (isset($journalId) ? ' AND journal_id = ?' : '') . (isset($roleId) ? ' AND role_id = ?' : ''),
			isset($journalId) && isset($roleId) ? array($userId, $journalId, $roleId)
			: (isset($journalId) ? array($userId, $journalId)
			: (isset($roleId) ? array($userId, $roleId) : $userId))
		);
	}
	
	/**
	 * Check if a role exists.
	 * @param $journalId int
	 * @param $userId int
	 * @param $roleId int
	 * @return boolean
	 */
	function roleExists($journalId, $userId, $roleId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM roles WHERE journal_id = ? AND user_id = ? AND role_id = ?', array($journalId, $userId, $roleId)
		);
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}
	
	/**
	 * Get the i18n key name associated with the specified role.
	 * @param $roleId int
	 * @param $plural boolean get the plural form of the name
	 * @return string
	 */
	function getRoleName($roleId, $plural = false) {
		switch ($roleId) {
			case ROLE_ID_SITE_ADMIN:
				return 'user.role.siteAdmin' . ($plural ? 's' : '');
			case ROLE_ID_JOURNAL_MANAGER:
				return 'user.role.manager' . ($plural ? 's' : '');
			case ROLE_ID_EDITOR:
				return 'user.role.editor' . ($plural ? 's' : '');
			case ROLE_ID_SECTION_EDITOR:
				return 'user.role.sectionEditor' . ($plural ? 's' : '');
			case ROLE_ID_LAYOUT_EDITOR:
				return 'user.role.layoutEditor' . ($plural ? 's' : '');
			case ROLE_ID_REVIEWER:
				return 'user.role.reviewer' . ($plural ? 's' : '');
			case ROLE_ID_COPYEDITOR:
				return 'user.role.copyeditor' . ($plural ? 's' : '');
			case ROLE_ID_PROOFREADER:
				return 'user.role.proofreader' . ($plural ? 's' : '');
			case ROLE_ID_AUTHOR:
				return 'user.role.author' . ($plural ? 's' : '');
			case ROLE_ID_READER:
				return 'user.role.reader' . ($plural ? 's' : '');
			default:
				return '';
		}
	}
	
	/**
	 * Get the URL path associated with the specified role's operations.
	 * @param $roleId int
	 * @return string
	 */
	function getRolePath($roleId) {
		switch ($roleId) {
			case ROLE_ID_SITE_ADMIN:
				return 'admin';
			case ROLE_ID_JOURNAL_MANAGER:
				return 'manager';
			case ROLE_ID_EDITOR:
				return 'editor';
			case ROLE_ID_SECTION_EDITOR:
				return 'sectionEditor';
			case ROLE_ID_LAYOUT_EDITOR:
				return 'layoutEditor';
			case ROLE_ID_REVIEWER:
				return 'reviewer';
			case ROLE_ID_COPYEDITOR:
				return 'copyeditor';
			case ROLE_ID_PROOFREADER:
				return 'proofreader';
			case ROLE_ID_AUTHOR:
				return 'author';
			case ROLE_ID_READER:
				return 'reader';
			default:
				return '';
		}
	}
	
	/**
	 * Get a role's ID based on its path.
	 * @param $rolePath string
	 * @return int
	 */
	function getRoleIdFromPath($rolePath) {
		switch ($rolePath) {
			case 'admin':
				return ROLE_ID_SITE_ADMIN;
			case 'manager':
				return ROLE_ID_JOURNAL_MANAGER;
			case 'editor':
				return ROLE_ID_EDITOR;
			case 'sectionEditor':
				return ROLE_ID_SECTION_EDITOR;
			case 'layoutEditor':
				return ROLE_ID_LAYOUT_EDITOR;
			case 'reviewer':
				return ROLE_ID_REVIEWER;
			case 'copyeditor':
				return ROLE_ID_COPYEDITOR;
			case 'proofreader':
				return ROLE_ID_PROOFREADER;
			case 'author':
				return ROLE_ID_AUTHOR;
			case 'reader':
				return ROLE_ID_READER;
			default:
				return null;
		}
	}

}

?>
