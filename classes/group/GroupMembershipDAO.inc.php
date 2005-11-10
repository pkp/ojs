<?php

/**
 * GroupMembershipDAO.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package group
 *
 * Class for GroupMembership DAO.
 * Operations for retrieving and modifying group membership info.
 *
 * $Id$
 */

import ('group.GroupMembership');

class GroupMembershipDAO extends DAO {
	var $userDao;

	/**
	 * Constructor.
	 */
	function GroupMembershipDAO() {
		parent::DAO();
		$this->userDao =& DAORegistry::getDAO('UserDAO');
	}
	
	/**
	 * Retrieve a membership by ID.
	 * @param $groupId int
	 * @param $userId int
	 * @return GroupMembership
	 */
	function &getMembership($groupId, $userId) {
		$result = &$this->retrieve(
			'SELECT * FROM group_memberships WHERE group_id = ? AND user_id = ?',
			array($groupId, $userId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnMembershipFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Retrieve memberships by group ID.
	 * @param $groupId int
	 * @return ItemIterator
	 */
	function &getMemberships($groupId, $rangeInfo = null) {
		$result = &$this->retrieve(
			'SELECT * FROM group_memberships m, users u WHERE group_id = ? AND u.user_id = m.user_id ORDER BY m.seq',
			$groupId,
			$rangeInfo
		);

		$returner =& new DAOResultFactory($result, $this, '_returnMembershipFromRow');
		return $returner;
	}

	/**
	 * Internal function to return a GroupMembership object from a row.
	 * @param $row array
	 * @return GroupMembership
	 */
	function &_returnMembershipFromRow(&$row) {
		// Keep a cache of users.
		static $users;
		if (!isset($users)) {
			$users = array();
		}
		$userId = $row['user_id'];
		if (!isset($users[$userId])) {
			$users[$userId] =& $this->userDao->getUser($userId);
		}

		$membership = &new GroupMembership();
		$membership->setGroupId($row['group_id']);
		$membership->setUserId($row['user_id']);
		$membership->setUser($users[$userId]);
		$membership->setSequence($row['seq']);
		$membership->setAboutDisplayed($row['about_displayed']);
		
		HookRegistry::call('GroupMembershipDAO::_returnMemberFromRow', array(&$membership, &$row));

		return $membership;
	}

	/**
	 * Insert a new group membership.
	 * @param $membership GroupMembership
	 */	
	function insertMembership(&$membership) {
		$this->update(
			'INSERT INTO group_memberships
				(group_id, user_id, seq, about_displayed)
				VALUES
				(?, ?, ?, ?)',
			array(
				$membership->getGroupId(),
				$membership->getUserId(),
				$membership->getSequence() == null ? 0 : $membership->getSequence(),
				$membership->getAboutDisplayed()
			)
		);
	}
	
	/**
	 * Update an existing group membership.
	 * @param $membership GroupMembership
	 */
	function updateMembership(&$membership) {
		return $this->update(
			'UPDATE group_memberships
				SET
					seq = ?,
					about_displayed = ?
				WHERE
					group_id = ? AND
					user_id = ?',
			array(
				$membership->getSequence(),
				$membership->getAboutDisplayed(),
				$membership->getGroupId(),
				$membership->getUserId()
			)
		);
	}
	
	/**
	 * Delete a membership
	 * @param $journal GroupMembership
	 */
	function deleteMembership(&$membership) {
		return $this->deleteMembershipById($membership->getGroupId(), $membership->getUserId());
	}
	
	/**
	 * Delete a membership, including membership info
	 * @param $groupId int
	 * @param $userId int
	 */
	function deleteMembershipById($groupId, $userId) {
		return $this->update(
			'DELETE FROM group_memberships WHERE group_id = ? AND user_id = ?',
			array($groupId, $userId)
		);
	}
	
	/**
	 * Delete group membership by journal ID, including membership info
	 * @param $journalId int
	 */
	function deleteMembershipByGroupId($groupId) {
		return $this->update(
			'DELETE FROM group_memberships WHERE group_id = ?',
			$groupId
		);
	}
	
	/**
	 * Sequentially renumber group members in their sequence order.
	 * @param $groupId int
	 */
	function resequenceMemberships($groupId) {
		$result = &$this->retrieve(
			'SELECT user_id, group_id FROM group_memberships WHERE group_id = ? ORDER BY seq',
			$groupId
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($userId, $groupId) = $result->fields;
			$this->update(
				'UPDATE group_memberships SET seq = ? WHERE user_id = ? AND group_id = ?',
				array(
					$i,
					$userId,
					$groupId
				)
			);
			
			$result->moveNext();
		}

		$result->close();
		unset($result);
	}
}

?>
