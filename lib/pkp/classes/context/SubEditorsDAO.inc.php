<?php

/**
 * @file classes/context/SubEditorsDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubEditorsDAO
 * @ingroup context
 *
 * @brief Base class associating sections/series to sub editors.
 */

class SubEditorsDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Insert a new section editor.
	 * @param $contextId int
	 * @param $sectionId int
	 * @param $userId int
	 */
	function insertEditor($contextId, $sectionId, $userId) {
		return $this->update(
			'INSERT INTO section_editors
				(context_id, section_id, user_id)
				VALUES
				(?, ?, ?)',
			array(
				(int) $contextId,
				(int) $sectionId,
				(int) $userId,
			)
		);
	}

	/**
	 * Delete a section editor.
	 * @param $contextId int
	 * @param $sectionId int
	 * @param $userId int
	 */
	function deleteEditor($contextId, $sectionId, $userId) {
		$this->update(
			'DELETE FROM section_editors WHERE context_id = ? AND section_id = ? AND user_id = ?',
			array(
				(int) $contextId,
				(int) $sectionId,
				(int) $userId,
			)
		);
	}

	/**
	 * Retrieve a list of all section editors assigned to the specified section.
	 * @param $sectionId int
	 * @param $contextId int
	 * @return array matching Users
	 */
	function getBySectionId($sectionId, $contextId) {
		$result = $this->retrieve(
			'SELECT	u.user_id
			FROM	section_editors e
				JOIN users u ON (e.user_id = u.user_id)
			WHERE	e.context_id = ? AND
				e.section_id = ?
			ORDER BY u.last_name, u.first_name',
			array((int) $contextId, (int) $sectionId)
		);

		$users = array();
		$userDao = DAORegistry::getDAO('UserDAO');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$user = $userDao->getById($row['user_id']);
			$users[$user->getId()] = $user;
			$result->MoveNext();
		}

		$result->Close();
		return $users;
	}

	/**
	 * Retrieve a list of all section editors not assigned to the specified section.
	 * @param $contextId int
	 * @param $sectionId int
	 * @return array matching Users
	 */
	function getEditorsNotInSection($contextId, $sectionId) {
		$result = $this->retrieve(
			'SELECT	u.user_id
			FROM	users u
				JOIN user_user_groups uug ON (u.user_id = uug.user_id)
				JOIN user_groups ug ON (uug.user_group_id = ug.user_group_id AND ug.role_id = ? AND ug.context_id = ?)
				LEFT JOIN section_editors e ON (e.user_id = u.user_id AND e.context_id = ug.context_id AND e.section_id = ?)
			WHERE	e.section_id IS NULL
			ORDER BY u.last_name, u.first_name',
			array(ROLE_ID_SUB_EDITOR, (int) $contextId, (int) $sectionId)
		);

		$userDao = DAORegistry::getDAO('UserDAO');
		$users = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$user = $userDao->getById($row['user_id']);
			$users[$user->getId()] = $user;
			$result->MoveNext();
		}

		$result->Close();
		return $users;
	}

	/**
	 * Delete all section editors for a specified section in a context.
	 * @param $sectionId int
	 * @param $contextId int
	 */
	function deleteBySectionId($sectionId, $contextId = null) {
		$params = array((int) $sectionId);
		if ($contextId) $params[] = (int) $contextId;
		$this->update(
			'DELETE FROM section_editors WHERE section_id = ?' .
			($contextId?' AND context_id = ?':''),
			$params
		);
	}

	/**
	 * Delete all section assignments for the specified user.
	 * @param $userId int
	 * @param $contextId int optional, include assignments only in this context
	 * @param $sectionId int optional, include only this section
	 */
	function deleteByUserId($userId, $contextId  = null, $sectionId = null) {
		$params = array((int) $userId);
		if ($contextId) $params[] = (int) $contextId;
		if ($sectionId) $params[] = (int) $sectionId;

		$this->update(
			'DELETE FROM section_editors WHERE user_id = ?' .
			($contextId?' AND context_id = ?':'') .
			($sectionId?' AND section_id = ?':''),
			$params
		);
	}

	/**
	 * Check if a user is assigned to a specified section.
	 * @param $contextId int
	 * @param $sectionId int
	 * @param $userId int
	 * @return boolean
	 */
	function editorExists($contextId, $sectionId, $userId) {
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM section_editors WHERE context_id = ? AND section_id = ? AND user_id = ?',
			array((int) $contextId, (int) $sectionId, (int) $userId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		return $returner;
	}
}

?>
