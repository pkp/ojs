<?php

/**
 * GroupDAO.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package group
 *
 * Class for Group DAO.
 * Operations for retrieving and modifying Group objects.
 *
 * $Id$
 */

import ('group.Group');

class GroupDAO extends DAO {

	/**
	 * Constructor.
	 */
	function GroupDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a group by ID.
	 * @param $groupId int
	 * @return Group
	 */
	function &getGroup($groupId) {
		$result = &$this->retrieve(
			'SELECT * FROM groups WHERE group_id = ?', $groupId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnGroupFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Get all groups for a journal.
	 * @param $journalId int
	 * @param $rangeInfo object RangeInfo object (optional)
	 * @return array
	 */
	function &getGroups($journalId, $rangeInfo = null) {
		$result = &$this->retrieve(
			'SELECT * FROM groups WHERE journal_id = ? ORDER BY seq',
			$journalId, $rangeInfo
		);

		$returner =& new DAOResultFactory($result, $this, '_returnGroupFromRow');
		return $returner;
	}

	/**
	 * Internal function to return a Group object from a row.
	 * @param $row array
	 * @return Group
	 */
	function &_returnGroupFromRow(&$row) {
		$group = &new Group();
		$group->setGroupId($row['group_id']);
		$group->setTitle($row['title']);
		$group->setTitleAlt1($row['title_alt1']);
		$group->setTitleAlt2($row['title_alt2']);
		$group->setAboutDisplayed($row['about_displayed']);
		$group->setSequence($row['seq']);
		$group->setJournalId($row['journal_id']);
		
		HookRegistry::call('GroupDAO::_returnGroupFromRow', array(&$group, &$row));

		return $group;
	}

	/**
	 * Insert a new board group.
	 * @param $group Group
	 */	
	function insertGroup(&$group) {
		$this->update(
			'INSERT INTO groups
				(title, title_alt1, title_alt2, seq, journal_id, about_displayed)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$group->getTitle(),
				$group->getTitleAlt1(),
				$group->getTitleAlt2(),
				$group->getSequence() == null ? 0 : $group->getSequence(),
				$group->getJournalId(),
				$group->getAboutDisplayed()
			)
		);
		
		$group->setGroupId($this->getInsertGroupId());
		return $group->getGroupId();
	}
	
	/**
	 * Update an existing board group.
	 * @param $group Group
	 */
	function updateGroup(&$group) {
		return $this->update(
			'UPDATE groups
				SET
					title = ?,
					title_alt1 = ?,
					title_alt2 = ?,
					seq = ?,
					journal_id = ?,
					about_displayed = ?
				WHERE group_id = ?',
			array(
				$group->getTitle(),
				$group->getTitleAlt1(),
				$group->getTitleAlt2(),
				$group->getSequence(),
				$group->getJournalId(),
				$group->getAboutDisplayed(),
				$group->getGroupId()
			)
		);
	}
	
	/**
	 * Delete a board group, including membership info
	 * @param $journal Group
	 */
	function deleteGroup(&$group) {
		return $this->deleteGroupById($group->getGroupId());
	}
	
	/**
	 * Delete a board group, including membership info
	 * @param $groupId int
	 */
	function deleteGroupById($groupId) {
		$groupMembershipDao = &DAORegistry::getDAO('GroupMembershipDAO');
		$groupMembershipDao->deleteMembershipByGroupId($groupId);

		return $this->update(
			'DELETE FROM groups WHERE group_id = ?', $groupId
		);
	}
	
	/**
	 * Delete board groups by journal ID, including membership info
	 * @param $journalId int
	 */
	function deleteGroupsByJournalId($journalId) {
		$groups =& $this->getGroups($journalId);
		while ($group =& $groups->next()) {
			$this->deleteGroup($group);
		}
	}
	
	/**
	 * Sequentially renumber board groups in their sequence order, optionally by journal.
	 * @param $journalId int
	 */
	function resequenceGroups($journalId = null) {
		$result = &$this->retrieve(
			'SELECT group_id FROM groups ' .
			($journalId !== null?'WHERE journal_id = ?':'') .
			'ORDER BY seq',
			$journalId
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($groupId) = $result->fields;
			$this->update(
				'UPDATE groups SET seq = ? WHERE group_id = ?',
				array(
					$i,
					$groupId
				)
			);
			
			$result->moveNext();
		}

		$result->close();
		unset($result);
	}
	
	/**
	 * Get the ID of the last inserted board group.
	 * @return int
	 */
	function getInsertGroupId() {
		return $this->getInsertId('groups', 'group_id');
	}
	
}

?>
