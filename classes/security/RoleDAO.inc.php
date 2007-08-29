<?php

/**
 * @file RoleDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package security
 * @class RoleDAO
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
		$this->userDao = &DAORegistry::getDAO('UserDAO');
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
				(int) $journalId,
				(int) $userId,
				(int) $roleId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnRoleFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
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
		
		HookRegistry::call('RoleDAO::_returnRoleFromRow', array(&$role, &$row));

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
				(int) $role->getJournalId(),
				(int) $role->getUserId(),
				(int) $role->getRoleId()
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
				(int) $role->getJournalId(),
				(int) $role->getUserId(),
				(int) $role->getRoleId()
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
			isset($journalId) ? array((int) $userId, (int) $journalId) : ((int) $userId)
		);
		
		while (!$result->EOF) {
			$roles[] = &$this->_returnRoleFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
		return $roles;
	}
	
	/**
	 * Retrieve a list of users in a specified role.
	 * @param $roleId int optional (can leave as null to get all users in journal)
	 * @param $journalId int optional, include users only in this journal
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains')
	 * @param $dbRangeInfo object DBRangeInfo object describing range of results to return
	 * @return array matching Users
	 */
	function &getUsersByRoleId($roleId = null, $journalId = null, $searchType = null, $search = null, $searchMatch = null, $dbResultRange = null) {
		$users = array();

		$paramArray = array('interests');
		if (isset($roleId)) $paramArray[] = (int) $roleId;
		if (isset($journalId)) $paramArray[] = (int) $journalId;

		// For security / resource usage reasons, a role or journal ID
		// must be specified. Don't allow calls supplying neither.
		if ($journalId === null && $roleId === null) return null;

		$searchSql = '';

		if (isset($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_FIRSTNAME:
				$searchSql = 'AND LOWER(u.first_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_LASTNAME:
				$searchSql = 'AND LOWER(u.last_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_USERNAME:
				$searchSql = 'AND LOWER(u.username) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_EMAIL:
				$searchSql = 'AND LOWER(u.email) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INTERESTS:
				$searchSql = 'AND LOWER(s.setting_value) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND LOWER(u.last_name) LIKE LOWER(?)';
				$paramArray[] = $search . '%';
				break;
		}
		
		$searchSql .= ' ORDER BY u.last_name, u.first_name'; // FIXME Add "sort field" parameter?
		
		$result = &$this->retrieveRange(
			'SELECT DISTINCT u.* FROM users AS u LEFT JOIN user_settings s ON (u.user_id = s.user_id AND s.setting_name = ?), roles AS r WHERE u.user_id = r.user_id ' . (isset($roleId)?'AND r.role_id = ?':'') . (isset($journalId) ? ' AND r.journal_id = ?' : '') . ' ' . $searchSql,
			$paramArray,
			$dbResultRange
		);
		
		$returner = &new DAOResultFactory($result, $this->userDao, '_returnUserFromRowWithData');
		return $returner;
	}
	
	/**
	 * Retrieve a list of all users with some role in the specified journal.
	 * @param $journalId int
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains')
	 * @param $dbRangeInfo object DBRangeInfo object describing range of results to return
	 * @return array matching Users
	 */
	function &getUsersByJournalId($journalId, $searchType = null, $search = null, $searchMatch = null, $dbResultRange = null) {
		$users = array();

		$paramArray = array('interests', (int) $journalId);
		$searchSql = '';

		if (isset($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_FIRSTNAME:
				$searchSql = 'AND LOWER(u.first_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_LASTNAME:
				$searchSql = 'AND LOWER(u.last_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_USERNAME:
				$searchSql = 'AND LOWER(u.username) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_EMAIL:
				$searchSql = 'AND LOWER(u.email) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INTERESTS:
				$searchSql = 'AND LOWER(s.setting_value) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND LOWER(u.last_name) LIKE LOWER(?)';
				$paramArray[] = $search . '%';
				break;
		}
		
		$searchSql .= ' ORDER BY u.last_name, u.first_name'; // FIXME Add "sort field" parameter?
		
		$result = &$this->retrieveRange(

			'SELECT DISTINCT u.* FROM users AS u LEFT JOIN user_settings s ON (u.user_id = s.user_id AND s.setting_name = ?), roles AS r WHERE u.user_id = r.user_id AND r.journal_id = ? ' . $searchSql,
			$paramArray,
			$dbResultRange
		);
		
		$returner = &new DAOResultFactory($result, $this->userDao, '_returnUserFromRowWithData');
		return $returner;
	}
	
	/**
	 * Retrieve the number of users associated with the specified journal.
	 * @param $journalId int
	 * @return int
	 */
	function getJournalUsersCount($journalId) {
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT COUNT(DISTINCT(user_id)) FROM roles WHERE journal_id = ?',
			(int) $journalId
		);

		$returner = $result->fields[0];

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Select all roles for a specified journal.
	 * @param $journalId int optional
	 * @param $roleId int optional
	 */
	function &getRolesByJournalId($journalId = null, $roleId = null) {
		$params = array();
		$conditions = array();
		if (isset($journalId)) {
			$params[] = (int) $journalId;
			$conditions[] = 'journal_id = ?';
		}
		if (isset($roleId)) {
			$params[] = (int) $roleId;
			$conditions[] = 'role_id = ?';
		}
		
		$result = &$this->retrieve(
			'SELECT * FROM roles' . (empty($conditions) ? '' : ' WHERE ' . join(' AND ', $conditions)),
			$params
		);
		
		$returner = &new DAOResultFactory($result, $this, '_returnRoleFromRow');
		return $returner;
	}
	
	/**
	 * Delete all roles for a specified journal.
	 * @param $journalId int
	 */
	function deleteRoleByJournalId($journalId) {
		return $this->update(
			'DELETE FROM roles WHERE journal_id = ?', (int) $journalId
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
			isset($journalId) && isset($roleId) ? array((int) $userId, (int) $journalId, (int) $roleId)
			: (isset($journalId) ? array((int) $userId, (int) $journalId)
			: (isset($roleId) ? array((int) $userId, (int) $roleId) : (int) $userId))
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
			'SELECT COUNT(*) FROM roles WHERE journal_id = ? AND user_id = ? AND role_id = ?', array((int) $journalId, (int) $userId, (int) $roleId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
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
			case ROLE_ID_SUBSCRIPTION_MANAGER:
				return 'user.role.subscriptionManager' . ($plural ? 's' : '');
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
			case ROLE_ID_SUBSCRIPTION_MANAGER:
				return 'subscriptionManager';
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
			case 'subscriptionManager':
				return ROLE_ID_SUBSCRIPTION_MANAGER;
			default:
				return null;
		}
	}
}

?>
