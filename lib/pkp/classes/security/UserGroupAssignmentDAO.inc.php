<?php

/**
 * @file classes/security/UserGroupAssignmentDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserGroupAssignmentDAO
 * @ingroup security
 * @see UserGroupAssigment
 *
 * @brief Operations for retrieving and modifying user group assignments
 * FIXME: Some of the context-specific features of this class will have
 * to be changed for zero- or double-context applications when user groups
 * are ported over to them.
 */

import('lib.pkp.classes.security.UserGroupAssignment');

class UserGroupAssignmentDAO extends DAO {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Create a new UserGroupAssignment object
	 * (allows extensibility)
	 */
	function newDataObject() {
		return new UserGroupAssignment();
	}

	/**
	 * Internal function to return a UserGroupAssignment object from a row.
	 * @param $row array
	 * @return Role
	 */
	function _fromRow($row) {
		$userGroupAssignment = $this->newDataObject();
		$userGroupAssignment->setUserGroupId($row['user_group_id']);
		$userGroupAssignment->setUserId($row['user_id']);

		return $userGroupAssignment;
	}

	/**
	 * Delete all user group assignments for a given userId
	 * @param int $userId
	 * @param $userGroupId int optional
	 */
	function deleteByUserId($userId, $userGroupId = null) {
		$params = array((int) $userId);
		if ($userGroupId) $params[] = (int) $userGroupId;

		$this->update(
			'DELETE FROM user_user_groups
			WHERE	user_id = ?
			' . ($userGroupId?' AND user_group_id = ?':''),
			$params
		);
	}

	/**
	 * Remove all user group assignments for a given group
	 * @param int $userGroupId
	 */
	function deleteAssignmentsByUserGroupId($userGroupId) {
		return $this->update('DELETE FROM user_user_groups
							WHERE user_group_id = ?',
						(int) $userGroupId);
	}

	/**
	 * Remove all user group assignments in a given context
	 * @param int $contextId
	 * @param int $userId
	 */
	function deleteAssignmentsByContextId($contextId, $userId = null) {
		$params = array((int) $contextId);
		if ($userId) $params[] = (int) $userId;
		$result = $this->retrieve(
			'SELECT	uug.user_group_id, uug.user_id
			FROM	user_groups ug
				JOIN user_user_groups uug ON ug.user_group_id = uug.user_group_id
			WHERE	ug.context_id = ?
				' . ($userId?' AND uug.user_id = ?':''),
			$params
		);

		$assignments = new DAOResultFactory($result, $this, '_fromRow');
		while ($assignment = $assignments->next()) {
			$this->deleteByUserId($assignment->getUserId(), $assignment->getUserGroupId());
		}
	}


	/**
	 * Retrieve user group assignments for a user
	 * @param $userId int
	 * @param $contextId int
	 * @param $roleId int
	 * @return Iterator UserGroup
	 */
	function getByUserId($userId, $contextId = null, $roleId = null) {
		$params = array((int) $userId);
		if ($contextId) $params[] = (int) $contextId;
		if ($roleId) $params[] = (int) $roleId;

		$result = $this->retrieve(
			'SELECT uug.user_group_id, uug.user_id
			FROM	user_groups ug
				JOIN user_user_groups uug ON ug.user_group_id = uug.user_group_id
				WHERE uug.user_id = ?' .
				($contextId?' AND ug.context_id = ?':'') .
				($roleId?' AND ug.role_id = ?':''),
			$params
		);
		return new DAOResultFactory($result, $this, '_fromRow');
	}


	/**
	 * Insert an assignment
	 * @param $userId
	 * @param $groupId
	 */
	function insertObject($userGroupAssignment) {
		$this->replace(
			'user_user_groups',
			array(
				'user_id' => (int) $userGroupAssignment->getUserId(),
				'user_group_id' => (int) $userGroupAssignment->getUserGroupId(),
			),
			array('user_id', 'user_group_id')
		);
	}

	/**
	 * Remove an assignment
	 * @param $userGroupAssignment
	 */
	function deleteAssignment(&$userGroupAssignment) {
		$this->update(
			'DELETE FROM user_user_groups WHERE user_id = ? AND user_group_id = ?',
			array((int) $userGroupAssignment->getUserId(), (int) $userGroupAssignment->getUserGroupId())
		);
	}
}

?>
