<?php

/**
 * AnnouncementTypeDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package announcement
 *
 * Class for AnnouncementType DAO.
 * Operations for retrieving and modifying AnnouncementType objects.
 *
 * $Id$
 */

import('announcement.AnnouncementType');

class AnnouncementTypeDAO extends DAO {

	/**
	 * Constructor.
	 */
	function AnnouncementTypeDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve an announcement type by announcement type ID.
	 * @param $typeId int
	 * @return AnnouncementType
	 */
	function &getAnnouncementType($typeId) {
		$result = &$this->retrieve(
			'SELECT * FROM announcement_types WHERE type_id = ?', $typeId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnAnnouncementTypeFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve announcement type journal ID by announcement type ID.
	 * @param $typeId int
	 * @return int
	 */
	function getAnnouncementTypeJournalId($typeId) {
		$result = &$this->retrieve(
			'SELECT journal_id FROM announcement_types WHERE type_id = ?', $typeId
		);
		
		return isset($result->fields[0]) ? $result->fields[0] : 0;	
	}

	/**
	 * Retrieve announcement type name by ID.
	 * @param $typeId int
	 * @return string
	 */
	function getAnnouncementTypeName($typeId) {
		$result = &$this->retrieve(
			'SELECT type_name FROM announcement_types WHERE type_id = ?', $typeId
		);
		
		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}


	/**
	 * Check if a announcement type exists with the given type id for a journal.
	 * @param $typeId int
	 * @param $journalId int
	 * @return boolean
	 */
	function announcementTypeExistsByTypeId($typeId, $journalId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM announcement_types
				WHERE type_id = ?
				AND   journal_id = ?',
			array(
				$typeId,
				$journalId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a announcement type exists with the given type name for a journal.
	 * @param $typeName string
	 * @param $journalId int
	 * @return boolean
	 */
	function announcementTypeExistsByTypeName($typeName, $journalId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM announcement_types
				WHERE type_name = ?
				AND   journal_id = ?',
			array(
				$typeName,
				$journalId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Return announcement type ID based on a type name for a journal.
	 * @param $typeName string
	 * @param $journalId int
	 * @return int
	 */
	function getAnnouncementTypeByTypeName($typeName, $journalId) {
		$result = &$this->retrieve(
			'SELECT type_id
				FROM announcement_types
				WHERE type_name = ?
				AND   journal_id = ?',
			array(
				$typeName,
				$journalId
			)
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return an AnnouncementType object from a row.
	 * @param $row array
	 * @return AnnouncementType
	 */
	function &_returnAnnouncementTypeFromRow(&$row) {
		$announcementType = &new AnnouncementType();
		$announcementType->setTypeId($row['type_id']);
		$announcementType->setJournalId($row['journal_id']);
		$announcementType->setTypeName($row['type_name']);
		
		return $announcementType;
	}

	/**
	 * Insert a new AnnouncementType.
	 * @param $announcementType AnnouncementType
	 * @return int 
	 */
	function insertAnnouncementType(&$announcementType) {
		$ret = $this->update(
			sprintf('INSERT INTO announcement_types
				(journal_id, type_name)
				VALUES
				(?, ?)'),
			array(
				$announcementType->getJournalId(),
				$announcementType->getTypeName()
			)
		);
		$announcementType->setTypeId($this->getInsertTypeId());
		return $announcementType->getTypeId();
	}

	/**
	 * Update an existing announcement type.
	 * @param $announcement AnnouncementType
	 * @return boolean
	 */
	function updateAnnouncementType(&$announcementType) {
		return $this->update(
			sprintf('UPDATE announcement_types
				SET
					journal_id = ?,
					type_name = ?
				WHERE type_id = ?'),
			array(
				$announcementType->getJournalId(),
				$announcementType->getTypeName(),
				$announcementType->getTypeId()
			)
		);
	}

	/**
	 * Delete an announcement type. Note that all announcements with this type are also
	 * deleted.
	 * @param $announcementType AnnouncementType 
	 * @return boolean
	 */
	function deleteAnnouncementType($announcementType) {
		return $this->deleteAnnouncementTypeById($announcementType->getTypeId());
	}

	/**
	 * Delete an announcement type by announcement type ID. Note that all announcements with
	 * this type ID are also deleted.
	 * @param $typeId int
	 * @return boolean
	 */
	function deleteAnnouncementTypeById($typeId) {
		$ret = $this->update(
			'DELETE FROM announcement_types WHERE type_id = ?', $typeId
		);

		// Delete all announcements with this announcement type
		if ($ret) {
			$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
			return $announcementDao->deleteAnnouncementByTypeId($typeId);
		} else {
			return $ret;
		}
	}

	/**
	 * Delete announcement types by journal ID.
	 * @param $journalId int
	 */
	function deleteAnnouncementTypesByJournal($journalId) {
		return $this->update(
			'DELETE FROM announcement_types WHERE journal_id = ?', $journalId
		);
	}

	/**
	 * Retrieve an array of announcement types matching a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching AnnouncementTypes
	 */
	function &getAnnouncementTypesByJournalId($journalId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM announcement_types WHERE journal_id = ? ORDER BY type_id', $journalId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnAnnouncementTypeFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted announcement type.
	 * @return int
	 */
	function getInsertTypeId() {
		return $this->getInsertId('announcement_types', 'type_id');
	}

}

?>
