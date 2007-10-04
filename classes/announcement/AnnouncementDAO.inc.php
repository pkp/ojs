<?php

/**
 * @file AnnouncementDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package announcement
 * @class AnnouncementDAO
 *
 * Class for Announcement DAO.
 * Operations for retrieving and modifying Announcement objects.
 *
 * $Id$
 */

import('announcement.Announcement');

class AnnouncementDAO extends DAO {
	/**
	 * Retrieve an announcement by announcement ID.
	 * @param $announcementId int
	 * @return Announcement
	 */
	function &getAnnouncement($announcementId) {
		$result = &$this->retrieve(
			'SELECT * FROM announcements WHERE announcement_id = ?', $announcementId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnAnnouncementFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve announcement journal ID by announcement ID.
	 * @param $announcementId int
	 * @return int
	 */
	function getAnnouncementJournalId($announcementId) {
		$result = &$this->retrieve(
			'SELECT journal_id FROM announcements WHERE announcement_id = ?', $announcementId
		);

		return isset($result->fields[0]) ? $result->fields[0] : 0;	
	}

	/**
	 * Get the list of localized field names for this table
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'descriptionShort', 'description');
	}

	/**
	 * Internal function to return an Announcement object from a row.
	 * @param $row array
	 * @return Announcement
	 */
	function &_returnAnnouncementFromRow(&$row) {
		$announcement = &new Announcement();
		$announcement->setAnnouncementId($row['announcement_id']);
		$announcement->setJournalId($row['journal_id']);
		$announcement->setTypeId($row['type_id']);
		$announcement->setDateExpire($this->dateFromDB($row['date_expire']));
		$announcement->setDatePosted($this->dateFromDB($row['date_posted']));

		$this->getDataObjectSettings('announcement_settings', 'announcement_id', $row['announcement_id'], $announcement);

		return $announcement;
	}

	/**
	 * Update the settings for this object
	 * @param $announcement object
	 */
	function updateLocaleFields(&$announcement) {
		$this->updateDataObjectSettings('announcement_settings', $announcement, array(
			'announcement_id' => $announcement->getAnnouncementId()
		));
	}

	/**
	 * Insert a new Announcement.
	 * @param $announcement Announcement
	 * @return int 
	 */
	function insertAnnouncement(&$announcement) {
		$this->update(
			sprintf('INSERT INTO announcements
				(journal_id, type_id, date_expire, date_posted)
				VALUES
				(?, ?, %s, %s)',
				$this->dateToDB($announcement->getDateExpire()), $this->dateToDB($announcement->getDatePosted())),
			array(
				$announcement->getJournalId(),
				$announcement->getTypeId()
			)
		);
		$announcement->setAnnouncementId($this->getInsertAnnouncementId());
		$this->updateLocaleFields($announcement);
		return $announcement->getAnnouncementId();
	}

	/**
	 * Update an existing announcement.
	 * @param $announcement Announcement
	 * @return boolean
	 */
	function updateAnnouncement(&$announcement) {
		$returner = $this->update(
			sprintf('UPDATE announcements
				SET
					journal_id = ?,
					type_id = ?,
					date_expire = %s
				WHERE announcement_id = ?',
				$this->dateToDB($announcement->getDateExpire())),
			array(
				$announcement->getJournalId(),
				$announcement->getTypeId(),
				$announcement->getAnnouncementId()
			)
		);
		$this->updateLocaleFields($announcement);
		return $returner;
	}

	/**
	 * Delete an announcement.
	 * @param $announcement Announcement 
	 * @return boolean
	 */
	function deleteAnnouncement($announcement) {
		return $this->deleteAnnouncementById($announcement->getAnnouncementId());
	}

	/**
	 * Delete an announcement by announcement ID.
	 * @param $announcementId int
	 * @return boolean
	 */
	function deleteAnnouncementById($announcementId) {
		$this->update('DELETE FROM announcement_settings WHERE announcement_id = ?', $announcementId);
		return $this->update('DELETE FROM announcements WHERE announcement_id = ?', $announcementId);
	}

	/**
	 * Delete announcements by announcement type ID.
	 * @param $typeId int
	 * @return boolean
	 */
	function deleteAnnouncementByTypeId($typeId) {
		$announcements =& $this->getAnnouncementsByTypeId($typeId);
		while (($announcement =& $announcements->next())) {
			$this->deleteAnnouncement($announcement->getAnnouncementId());
			unset($announcement);
		}
	}

	/**
	 * Delete announcements by journal ID.
	 * @param $journalId int
	 */
	function deleteAnnouncementsByJournal($journalId) {
		$announcements =& $this->getAnnouncementsByJournalId($journalId);
		while (($announcement =& $announcements->next())) {
			$this->deleteAnnouncementById($announcement->getAnnouncementId());
			unset($announcement);
		}
	}

	/**
	 * Retrieve an array of announcements matching a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching Announcements
	 */
	function &getAnnouncementsByJournalId($journalId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM announcements WHERE journal_id = ? ORDER BY announcement_id DESC', $journalId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}

	/**
	 * Retrieve an array of announcements matching a particular type ID.
	 * @param $typeId int
	 * @return object DAOResultFactory containing matching Announcements
	 */
	function &getAnnouncementsByTypeId($typeId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM announcements WHERE type_id = ? ORDER BY announcement_id DESC', $typeId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}

	/**
	 * Retrieve an array of numAnnouncements announcements matching a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching Announcements
	 */
	function &getNumAnnouncementsByJournalId($journalId, $numAnnouncements, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM announcements WHERE journal_id = ? ORDER BY announcement_id DESC LIMIT ?', array($journalId, $numAnnouncements), $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}

	/**
	 * Retrieve an array of announcements with no/valid expiry date matching a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching Announcements
	 */
	function &getAnnouncementsNotExpiredByJournalId($journalId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM announcements WHERE journal_id = ? AND (date_expire IS NULL OR date_expire > CURRENT_DATE) ORDER BY announcement_id DESC', $journalId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}

	/**
	 * Retrieve an array of numAnnouncements announcements with no/valid expiry date matching a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching Announcements
	 */
	function &getNumAnnouncementsNotExpiredByJournalId($journalId, $numAnnouncements, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM announcements WHERE journal_id = ? AND (date_expire IS NULL OR date_expire > CURRENT_DATE) ORDER BY announcement_id DESC LIMIT ?', array($journalId, $numAnnouncements), $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted announcement.
	 * @return int
	 */
	function getInsertAnnouncementId() {
		return $this->getInsertId('announcements', 'announcement_id');
	}
}

?>
