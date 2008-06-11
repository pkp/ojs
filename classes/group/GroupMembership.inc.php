<?php

/**
 * @file GroupMembership.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package board
 * @class GroupMembership
 *
 * GroupMembership class.
 * Describes memberships for journal board positions.
 *
 * $Id$
 */

class GroupMembership extends DataObject {

	/**
	 * Constructor.
	 */
	function GroupMembership() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of board position.
	 * @return int
	 */
	function getGroupId() {
		return $this->getData('groupId');
	}

	/**
	 * Set ID of board position.
	 * @param $groupId int
	 */
	function setGroupId($groupId) {
		return $this->setData('groupId', $groupId);
	}

	/**
	 * Get user ID of membership.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * Set user ID of membership.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}

	/**
	 * Get user for this membership.
	 * @return int
	 */
	function &getUser() {
		return $this->getData('user');
	}

	/**
	 * Set user for this membership.
	 * @param $userId int
	 */
	function setUser(&$user) {
		return $this->setData('user', $user);
	}

	/**
	 * Get sequence of membership.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of membership.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	/**
	 * Get flag indicating whether or not the membership is displayed in "About"
	 * @return boolean
	 */
	function getAboutDisplayed() {
		return $this->getData('aboutDisplayed');
	}

	/**
	 * Set flag indicating whether or not the membership is displayed in "About"
	 * @param $aboutDisplayed boolean
	 */
	function setAboutDisplayed($aboutDisplayed) {
		return $this->setData('aboutDisplayed',$aboutDisplayed);
	}
}

?>
