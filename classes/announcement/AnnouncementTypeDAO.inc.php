<?php

/**
 * @file classes/announcement/AnnouncementTypeDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeDAO
 * @ingroup announcement
 * @see AnnouncementType
 *
 * @brief Operations for retrieving and modifying AnnouncementType objects.
 */

// $Id$


import('announcement.AnnouncementType');

class AnnouncementTypeDAO extends DAO {
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
			'SELECT COALESCE(l.setting_value, p.setting_value) FROM announcement_type_settings l LEFT JOIN announcement_type_settings p ON (p.type_id = ? AND p.setting_name = ? AND p.locale = ?) WHERE l.type_id = ? AND l.setting_name = ? AND l.locale = ?', 
			array(
				$typeId, 'name', Locale::getLocale(),
				$typeId, 'name', Locale::getPrimaryLocale()
			)
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

	function getLocaleFieldNames() {
		return array('name');
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
		$this->getDataObjectSettings('announcement_type_settings', 'type_id', $row['type_id'], $announcementType);

		return $announcementType;
	}

	/**
	 * Update the localized settings for this object
	 * @param $announcementType object
	 */
	function updateLocaleFields(&$announcementType) {
		$this->updateDataObjectSettings('announcement_type_settings', $announcementType, array(
			'type_id' => $announcementType->getTypeId()
		));
	}

	/**
	 * Insert a new AnnouncementType.
	 * @param $announcementType AnnouncementType
	 * @return int 
	 */
	function insertAnnouncementType(&$announcementType) {
		$this->update(
			sprintf('INSERT INTO announcement_types
				(journal_id)
				VALUES
				(?)'),
			array(
				$announcementType->getJournalId()
			)
		);
		$announcementType->setTypeId($this->getInsertTypeId());
		$this->updateLocaleFields($announcementType);
		return $announcementType->getTypeId();
	}

	/**
	 * Update an existing announcement type.
	 * @param $announcement AnnouncementType
	 * @return boolean
	 */
	function updateAnnouncementType(&$announcementType) {
		$returner = $this->update(
			sprintf('UPDATE announcement_types
				SET
					journal_id = ?
				WHERE type_id = ?'),
			array(
				$announcementType->getJournalId(),
				$announcementType->getTypeId()
			)
		);
		$this->updateLocaleFields($announcementType);
		return $returner;
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
		$this->update('DELETE FROM announcement_type_settings WHERE type_id = ?', $typeId);
		$ret = $this->update('DELETE FROM announcement_types WHERE type_id = ?', $typeId);

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
		$types =& $this->getAnnouncementTypesByJournalId($journalId);
		while (($type =& $types->next())) {
			$this->deleteAnnouncementType($type);
			unset($type);
		}
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
