<?php

/**
 * NotificationStatusDAO.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
 *
 * Operations for retrieving and modifying users' journal notification status.
 *
 * $Id$
 */

class NotificationStatusDAO extends DAO {
	function &getJournalNotifications($userId) {
		$returner = array();
		
		$result = &$this->retrieve(
			'SELECT j.journal_id AS journal_id, n.journal_id AS notification FROM journals j LEFT JOIN notification_status n ON j.journal_id = n.journal_id AND n.user_id = ? ORDER BY j.seq',
			$userId
		);
		
		while (!$result->EOF) {
			$row = &$result->GetRowAssoc(false);
			$returner[$row['journal_id']] = $row['notification'] != null;
			$result->moveNext();
		}
		$result->Close();
	
		return $returner;
	}
	
	/**
	 * Changes whether or not a user will receive email notifications about a given journal.
	 * @param $journalId int
	 * @param $userId int
	 * @param $notificationStatus bool
	 */
	function setJournalNotifications($journalId, $userId, $notificationStatus) {
		return $this->update(
			($notificationStatus?'INSERT INTO notification_status (user_id, journal_id) VALUES (?, ?)':
			'DELETE FROM notification_status WHERE user_id = ? AND journal_id = ?'),
			array($userId, $journalId)
		);
	}

	/**
	 * Retrieve a list of users who wish to receive updates about the specified journal.
	 * @param $journalId int
	 * @return array matching Users
	 */
	function &getNotifiableUsersByJournalId($journalId) {
		$users = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT u.* FROM users u, notification_status n WHERE u.user_id = n.user_id AND n.journal_id = ?',
			$journalId
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $users;
	}
	

}

?>
