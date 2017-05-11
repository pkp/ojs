<?php

/**
 * @file classes/security/RoleDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoleDAO
 * @ingroup security
 *
 * @brief Operations for retrieving and modifying Role objects.
 */

import('lib.pkp.classes.security.Role');
import('lib.pkp.classes.security.UserGroupAssignment');

class RoleDAO extends DAO {
	/** @var The User DAO to return User objects when necessary **/
	var $userDao;

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->userDao = DAORegistry::getDAO('UserDAO');
	}

	/**
	 * Create new data object
	 * @return Role
	 */
	function newDataObject() {
		return new Role();
	}

	/**
	 * Retrieve a list of users in a specified role.
	 * @param $roleId int optional (can leave as null to get all users in context)
	 * @param $contextId int optional, include users only in this context
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains' vs. 'startsWith')
	 * @param $dbResultRange object DBRangeInfo object describing range of results to return
	 * @return array matching Users
	 */
	function getUsersByRoleId($roleId = null, $contextId = null, $searchType = null, $search = null, $searchMatch = null, $dbResultRange = null) {
		$paramArray = array(ASSOC_TYPE_USER, 'interest');
		if (isset($roleId)) $paramArray[] = (int) $roleId;
		if (isset($contextId)) $paramArray[] = (int) $contextId;
		// For security / resource usage reasons, a role or context ID
		// must be specified. Don't allow calls supplying neither.
		if ($contextId === null && $roleId === null) return null;

		$searchSql = '';

		$searchTypeMap = array(
			USER_FIELD_FIRSTNAME => 'u.first_name',
			USER_FIELD_LASTNAME => 'u.last_name',
			USER_FIELD_USERNAME => 'u.username',
			USER_FIELD_EMAIL => 'u.email',
			USER_FIELD_INTERESTS => 'cves.setting_value'
		);

		if (!empty($search) && isset($searchTypeMap[$searchType])) {
			$fieldName = $searchTypeMap[$searchType];
			switch ($searchMatch) {
				case 'is':
					$searchSql = "AND LOWER($fieldName) = LOWER(?)";
					$paramArray[] = $search;
					break;
				case 'contains':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = '%' . $search . '%';
					break;
				case 'startsWith':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = $search . '%';
					break;
			}
		} elseif (!empty($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND LOWER(u.last_name) LIKE LOWER(?)';
				$paramArray[] = $search . '%';
				break;
		}

		$searchSql .= ' ORDER BY u.last_name, u.first_name'; // FIXME Add "sort field" parameter?

		$result = $this->retrieveRange(
			'SELECT DISTINCT u.* FROM users AS u LEFT JOIN controlled_vocabs cv ON (cv.assoc_type = ? AND cv.assoc_id = u.user_id AND cv.symbolic = ?)
			LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id)
			LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id),
			user_groups AS ug, user_user_groups AS uug
			WHERE ug.user_group_id = uug.user_group_id AND u.user_id = uug.user_id' . (isset($roleId) ? ' AND ug.role_id = ?' : '') . (isset($contextId) ? ' AND ug.context_id = ?' : '') . ' ' . $searchSql,
			$paramArray,
			$dbResultRange
		);

		return new DAOResultFactory($result, $this->userDao, '_returnUserFromRowWithData');
	}

	/**
	 * Validation check to see if a user belongs to any group that has a given role
	 * @param $contextId int
	 * @param $userId int
	 * @param $roleId int ROLE_ID_...
	 * @return bool True iff at least one such role exists
	 */
	function userHasRole($contextId, $userId, $roleId) {
		$result = $this->retrieve(
			'SELECT count(*) FROM user_groups ug JOIN user_user_groups uug ON ug.user_group_id = uug.user_group_id
			WHERE ug.context_id = ? AND uug.user_id = ? AND ug.role_id = ?',
			array((int) $contextId, (int) $userId, (int) $roleId)
		);

		// > 0 because user could belong to more than one user group with this role
		$returner = isset($result->fields[0]) && $result->fields[0] > 0 ? true : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Return an array of row objects corresponding to the roles a given use has
	 * @param $userId
	 * @param $contextId
	 * @return array of Roles
	 */
	function getByUserId($userId, $contextId = null) {
		$params = array((int) $userId);
		if ($contextId) $params[] = (int) $contextId;
		$result = $this->retrieve(
			'SELECT	DISTINCT ug.role_id
			FROM	user_groups ug
				JOIN user_user_groups uug ON ug.user_group_id = uug.user_group_id
			WHERE	uug.user_id = ?' . ($contextId?' AND ug.context_id = ?':''),
			$params
		);

		$roles = array();
		while ( !$result->EOF ) {
			$role = $this->newDataObject();
			$role->setRoleId($result->fields[0]);
			$roles[] = $role;
			$result->MoveNext();
		}
		$result->Close();
		return $roles;
	}

	/**
	 * Return an array of objects corresponding to the roles a given user has,
	 * grouped by context id.
	 * @param $userId int
	 * @return array
	 */
	function getByUserIdGroupedByContext($userId) {
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$userGroupsFactory = $userGroupDao->getByUserId($userId);

		$roles = array();
		while ($userGroup = $userGroupsFactory->next()) {
			$role = $roleDao->newDataObject();
			$role->setRoleId($userGroup->getRoleId());
			$roles[$userGroup->getContextId()][$userGroup->getRoleId()] = $role;
		}

		return $roles;
	}

	/**
	 * Map a column heading value to a database value for sorting
	 * @param string
	 * @return string
	 */
	function getSortMapping($heading) {
		switch ($heading) {
			case 'username': return 'u.username';
			case 'name': return 'u.last_name';
			case 'email': return 'u.email';
			case 'id': return 'u.user_id';
			default: return null;
		}
	}

	/**
	 * Get role forbidden stages.
	 * @param $roleId int Specific role ID to fetch stages for, if any
	 * @return array With $roleId, array(WORKFLOW_STAGE_ID_...); without,
	 *  array(ROLE_ID_... => array(WORKFLOW_STAGE_ID_...))
	 */
	function getForbiddenStages($roleId = null) {
		$forbiddenStages = array(
			ROLE_ID_REVIEWER => array(
				// Reviewer user groups should only have review stage assignments.
				WORKFLOW_STAGE_ID_SUBMISSION, WORKFLOW_STAGE_ID_EDITING, WORKFLOW_STAGE_ID_PRODUCTION,
			),
			ROLE_ID_READER => array(
				// Reader user groups should have no stage assignments.
				WORKFLOW_STAGE_ID_SUBMISSION, WORKFLOW_STAGE_ID_INTERNAL_REVIEW, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW, WORKFLOW_STAGE_ID_EDITING, WORKFLOW_STAGE_ID_PRODUCTION,
			),
		);

		if ($roleId) {
			if (isset($forbiddenStages[$roleId])) {
				return $forbiddenStages[$roleId];
			} else {
				return array();
			}
		} else {
			return $forbiddenStages;
		}
	}


	//
	// Static methods.
	//
	/**
	 * Get a mapping of role keys and i18n key names.
	 * @param boolean $contextOnly If false, also returns site-level roles (Site admin)
	 * @param array|null $roleIds Only return role names of these IDs
	 * @return array
	 */
	static function getRoleNames($contextOnly = false, $roleIds = null) {
		$siteRoleNames = array(ROLE_ID_SITE_ADMIN => 'user.role.siteAdmin');
		$appRoleNames = array(
			ROLE_ID_MANAGER => 'user.role.manager',
			ROLE_ID_SUB_EDITOR => 'user.role.subEditor',
			ROLE_ID_ASSISTANT => 'user.role.assistant',
			ROLE_ID_AUTHOR => 'user.role.author',
			ROLE_ID_REVIEWER => 'user.role.reviewer',
			ROLE_ID_READER => 'user.role.reader',
		);
		$roleNames = $contextOnly ? $appRoleNames : $siteRoleNames + $appRoleNames;

		if(!empty($roleIds)) {
			$returner = array();
			foreach($roleIds as $roleId) {
				if(isset($roleNames[$roleId])) $returner[$roleId] = $roleNames[$roleId];
			}
			return $returner;
		}

		return $roleNames;
	}
}

?>
