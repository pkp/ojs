<?php

/**
 * SectionEditorsDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
 *
 * Class for DAO relating sections to editors.
 *
 * $Id$
 */

class SectionEditorsDAO extends DAO {

	/**
	 * Constructor.
	 */
	function SectionEditorsDAO() {
		parent::DAO();
	}
	
	/**
	 * Insert a new section editor.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $userId int
	 */
	function insertEditor($journalId, $sectionId, $userId) {
		return $this->update(
			'INSERT INTO section_editors
				(journal_id, section_id, user_id)
				VALUES
				(?, ?, ?)',
			array(
				$journalId,
				$sectionId,
				$userId
			)
		);
	}
	
	/**
	 * Delete a section editor.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $userId int
	 */
	function deleteEditor($journalId, $sectionId, $userId) {
		return $this->update(
			'DELETE FROM section_editors WHERE journal_id = ? AND section_id = ? AND user_id = ?',
			array(
				$journalId,
				$sectionId,
				$userId
			)
		);
	}
	
	/**
	 * Retrieve a list of sections assigned to the specified user.
	 * @param $journalId int
	 * @param $userId int
	 * @return array matching Sections
	 */
	function &getSectionsByUserId($journalId, $userId) {
		$sections = array();
		
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
				
		$result = &$this->retrieve(
			'SELECT s.* FROM sections AS s, section_editors AS e WHERE s.section_id = e.section_id AND s.journal_id = ? AND e.user_id = ?',
			array($journalId, $userId)
		);
		
		while (!$result->EOF) {
			$sections[] = &$sectionDao->_returnSectionFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $sections;
	}
	
	/**
	 * Retrieve a list of all section editors assigned to the specified section.
	 * @param $journalId int
	 * @param $sectionId int
	 * @return array matching Users
	 */
	function &getEditorsBySectionId($journalId, $sectionId) {
		$users = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT u.* FROM users AS u, section_editors AS e WHERE u.user_id = e.user_id AND e.journal_id = ? AND e.section_id = ? ORDER BY last_name, first_name',
			array($journalId, $sectionId)
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $users;
	}
	
	/**
	 * Retrieve a list of all section editors not assigned to the specified section.
	 * @param $journalId int
	 * @param $sectionId int
	 * @return array matching Users
	 */
	function &getEditorsNotInSection($journalId, $sectionId) {
		$users = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT u.* FROM users AS u, roles r LEFT JOIN section_editors AS e on e.user_id = u.user_id AND e.journal_id = r.journal_id AND e.section_id = ? WHERE u.user_id = r.user_id AND r.journal_id = ? AND r.role_id = ? AND e.section_id IS NULL ORDER BY last_name, first_name',
			array($sectionId, $journalId, RoleDAO::getRoleIdFromPath('sectionEditor'))
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $users;
	}
	
	/**
	 * Delete all section editors for a specified section in a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 */
	function deleteEditorsBySectionId($journalId, $sectionId) {
		return $this->update(
			'DELETE FROM section_editors WHERE journal_id = ? AND section_id = ?',
			array($journalId, $sectionId)
		);
	}
	
	/**
	 * Delete all section editors for a specified journal.
	 * @param $journalId int
	 */
	function deleteEditorsByJournalId($journalId) {
		return $this->update(
			'DELETE FROM section_editors WHERE journal_id = ?', $journalId
		);
	}
	
	/**
	 * Delete all section assignments for the specified user.
	 * @param $userId int
	 * @param $journalId int optional, include assignments only in this journal
	 * @param $sectionId int optional, include only this section
	 */
	function deleteEditorsByUserId($userId, $journalId  = null, $sectionId = null) {
		return $this->update(
			'DELETE FROM section_editors WHERE user_id = ?' . (isset($journalId) ? ' AND journal_id = ?' : '') . (isset($sectionId) ? ' AND section_id = ?' : ''),
			isset($journalId) && isset($sectionId) ? array($userId, $journalId, $sectionId)
			: (isset($journalId) ? array($userId, $journalId)
			: (isset($sectionId) ? array($userId, $sectionId) : $userId))
		);
	}
	
	/**
	 * Check if a user is assigned to a specified section.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $userId int
	 * @return boolean
	 */
	function editorExists($journalId, $sectionId, $userId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM section_editors WHERE journal_id = ? AND section_id = ? AND user_id = ?', array($journalId, $sectionId, $userId)
		);
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}
	
}

?>
