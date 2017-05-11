<?php

/**
 * @file controllers/grid/queries/QueriesAccessHelper.inc.php
 *
 * Copyright (c) 2016-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueriesAccessHelper
 * @ingroup controllers_grid_query
 *
 * @brief Implements access rules for queries.
 * Permissions are intended as follows (per UI/UX group, 2015-12-01):
 *
 *	     ROLE
 *  TASK       MANAGER   SUB EDITOR  ASSISTANT  AUTHOR
 *  Create Q   Yes       Yes	     Yes        Yes
 *  Edit Q     All       All         If Creator If Creator
 *  List/View  All       All	     Assigned   Assigned
 *  Open/close All       All	     Assigned   No
 *  Delete Q   All       All	     No         No
 */

class QueriesAccessHelper {
	/** @var array */
	var $_authorizedContext;

	/** @var User */
	var $_user;

	/**
	 * Constructor
	 * @param $authorizedContext array
	 * @param $user User
	 */
	function __construct($authorizedContext, $user) {
		$this->_authorizedContext = $authorizedContext;
		$this->_user = $user;
	}

	/**
	 * Retrieve authorized context objects from the authorized context.
	 * @param $assocType integer any of the ASSOC_TYPE_* constants
	 * @return mixed
	 */
	function getAuthorizedContextObject($assocType) {
		return isset($this->_authorizedContext[$assocType])?$this->_authorizedContext[$assocType]:null;
	}

	/**
	 * Determine whether the current user can open/close a query.
	 * @param $queryId int Query ID
	 * @return boolean True iff the user is allowed to open/close the query.
	 */
	function getCanOpenClose($queryId) {
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		// Managers and sub editors are always allowed
		if (count(array_intersect($userRoles, array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR)))) return true;

		// Assigned assistants are allowed
		if (in_array(ROLE_ID_ASSISTANT, (array) $userRoles) && $this->isAssigned($this->_user->getId(), $queryId)) return true;

		// Otherwise, not allowed.
		return false;
	}

	/**
	 * Determine whether the user can re-order the queries.
	 * @return boolean
	 */
	function getCanOrder() {
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		// Managers and editors can re-order.
		if (count(array_intersect($userRoles, array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR)))) return true;

		return false;
	}

	/**
	 * Determine whether the user can create queries.
	 * @return boolean
	 */
	function getCanCreate() {
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		// Managers, editors, assistants, and authors can create.
		if (count(array_intersect($userRoles, array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR)))) return true;

		return false;
	}

	/**
	 * Determine whether the current user can edit a query.
	 * @param $queryId int Query ID
	 * @return boolean True iff the user is allowed to edit the query.
	 */
	function getCanEdit($queryId) {
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		// Managers and sub editors are always allowed
		if (count(array_intersect($userRoles, array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR)))) return true;

		// Assistants and authors are allowed, if they created the query
		if (count(array_intersect($userRoles, array(ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR)))) {
			$queryDao = DAORegistry::getDAO('QueryDAO');
			$query = $queryDao->getById($queryId);
			if ($query && $query->getHeadNote()->getUserId() == $this->_user->getId()) return true;
		}

		// Otherwise, not allowed.
		return false;
	}

	/**
	 * Determine whether the current user can delete a query.
	 * @param $queryId int Query ID
	 * @return boolean True iff the user is allowed to delete the query.
	 */
	function getCanDelete($queryId) {
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		// Managers and sub editors are always allowed
		if (count(array_intersect($userRoles, array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR)))) return true;

		// Users can always delete their own placeholder queries.
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$query = $queryDao->getById($queryId);
		if ($query) {
			$headNote = $query->getHeadNote();
			if ($headNote->getUserId() == $this->_user->getId() && $headNote->getTitle()=='') return true;
		}

		// Otherwise, not allowed.
		return false;
	}


	/**
	 * Determine whether the current user can list all queries on the submission
	 * @return boolean
	 */
	function getCanListAll() {
		return (count(array_intersect(
			$this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES),
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR)
		))>0);
	}

	/**
	 * Determine whether the current user is assigned to the current query.
	 * @param $userId int User ID
	 * @param $queryId int Query ID
	 * @return boolean
	 */
	protected function isAssigned($userId, $queryId) {
		$queryDao = DAORegistry::getDAO('QueryDAO');
		return (boolean) $queryDao->getParticipantIds($queryId, $userId);
	}
}

?>
