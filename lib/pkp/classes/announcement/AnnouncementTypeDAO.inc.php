<?php

/**
 * @file classes/announcement/AnnouncementTypeDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeDAO
 * @ingroup announcement
 * @see AnnouncementType
 *
 * @brief Operations for retrieving and modifying AnnouncementType objects.
 */


import('lib.pkp.classes.announcement.AnnouncementType');

class AnnouncementTypeDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Generate a new data object.
	 * @return DataObject
	 */
	function newDataObject() {
		return new AnnouncementType();
	}

	/**
	 * Retrieve an announcement type by announcement type ID.
	 * @param $typeId int Announcement type ID
	 * @param $assocType int Optional assoc type
	 * @param $assocId int Optional assoc ID
	 * @return AnnouncementType
	 */
	function getById($typeId, $assocType = null, $assocId = null) {
		$params = array((int) $typeId);
		if ($assocType !== null) $params[] = (int) $assocType;
		if ($assocId !== null) $params[] = (int) $assocId;
		$result = $this->retrieve(
			'SELECT * FROM announcement_types WHERE type_id = ?' .
			($assocType !== null?' AND assoc_type = ?':'') .
			($assocId !== null?' AND assoc_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve announcement type Assoc ID by announcement type ID.
	 * @param $typeId int
	 * @return int
	 */
	function getAnnouncementTypeAssocId($typeId) {
		$result = $this->retrieve(
			'SELECT assoc_id FROM announcement_types WHERE type_id = ?',
			(int) $typeId
		);

		return isset($result->fields[0]) ? $result->fields[0] : 0;
	}

	/**
	 * Retrieve announcement type name by ID.
	 * @param $typeId int
	 * @return string
	 */
	function getAnnouncementTypeName($typeId) {
		$result = $this->retrieve(
			'SELECT COALESCE(l.setting_value, p.setting_value) FROM announcement_type_settings p LEFT JOIN announcement_type_settings l ON (l.type_id = ? AND l.setting_name = ? AND l.locale = ?) WHERE p.type_id = ? AND p.setting_name = ? AND p.locale = ?',
			array(
				(int) $typeId, 'name', AppLocale::getLocale(),
				(int) $typeId, 'name', AppLocale::getPrimaryLocale()
			)
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		return $returner;
	}


	/**
	 * Check if a announcement type exists with the given type id for a assoc type/id pair.
	 * @param $typeId int
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 * @return boolean
	 */
	function announcementTypeExistsByTypeId($typeId, $assocType, $assocId) {
		$result = $this->retrieve(
			'SELECT COUNT(*)
			FROM	announcement_types
			WHERE	type_id = ? AND
				assoc_type = ? AND
				assoc_id = ?',
			array(
				(int) $typeId,
				(int) $assocType,
				(int) $assocId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Get the locale field names.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name');
	}

	/**
	 * Return announcement type ID based on a type name for an assoc type/id pair.
	 * @param $typeName string
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 * @return int
	 */
	function getByTypeName($typeName, $assocType, $assocId) {
		$result = $this->retrieve(
			'SELECT ats.type_id
				FROM announcement_type_settings AS ats
				LEFT JOIN announcement_types at ON ats.type_id = at.type_id
				WHERE ats.setting_name = "name"
				AND ats.setting_value = ?
				AND at.assoc_type = ?
				AND at.assoc_id = ?',
			array(
				$typeName,
				(int) $assocType,
				(int) $assocId
			)
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		return $returner;
	}

	/**
	 * Internal function to return an AnnouncementType object from a row.
	 * @param $row array
	 * @return AnnouncementType
	 */
	function _fromRow($row) {
		$announcementType = $this->newDataObject();
		$announcementType->setId($row['type_id']);
		$announcementType->setAssocType($row['assoc_type']);
		$announcementType->setAssocId($row['assoc_id']);
		$this->getDataObjectSettings('announcement_type_settings', 'type_id', $row['type_id'], $announcementType);

		return $announcementType;
	}

	/**
	 * Update the localized settings for this object
	 * @param $announcementType object
	 */
	function updateLocaleFields($announcementType) {
		$this->updateDataObjectSettings('announcement_type_settings', $announcementType, array(
			'type_id' => (int) $announcementType->getId()
		));
	}

	/**
	 * Insert a new AnnouncementType.
	 * @param $announcementType AnnouncementType
	 * @return int
	 */
	function insertObject($announcementType) {
		$this->update(
			sprintf('INSERT INTO announcement_types
				(assoc_type, assoc_id)
				VALUES
				(?, ?)'),
			array(
				(int) $announcementType->getAssocType(),
				(int) $announcementType->getAssocId()
			)
		);
		$announcementType->setId($this->getInsertId());
		$this->updateLocaleFields($announcementType);
		return $announcementType->getId();
	}

	/**
	 * Update an existing announcement type.
	 * @param $announcementType AnnouncementType
	 * @return boolean
	 */
	function updateObject($announcementType) {
		$returner = $this->update(
			'UPDATE	announcement_types
			SET	assoc_type = ?,
				assoc_id = ?
			WHERE	type_id = ?',
			array(
				(int) $announcementType->getAssocType(),
				(int) $announcementType->getAssocId(),
				(int) $announcementType->getId()
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
	function deleteObject($announcementType) {
		return $this->deleteById($announcementType->getId());
	}

	/**
	 * Delete an announcement type by announcement type ID. Note that all announcements with
	 * this type ID are also deleted.
	 * @param $typeId int
	 */
	function deleteById($typeId) {
		$this->update('DELETE FROM announcement_type_settings WHERE type_id = ?', (int) $typeId);
		$this->update('DELETE FROM announcement_types WHERE type_id = ?', (int) $typeId);

		$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
		$announcementDao->deleteByTypeId($typeId);
	}

	/**
	 * Delete announcement types by association.
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 */
	function deleteByAssoc($assocType, $assocId) {
		$types = $this->getByAssoc($assocType, $assocId);
		while ($type = $types->next()) {
			$this->deleteObject($type);
		}
	}

	/**
	 * Retrieve an array of announcement types matching a particular Assoc ID.
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 * @param $rangeInfo DBResultRange (optional)
	 * @return object DAOResultFactory containing matching AnnouncementTypes
	 */
	function getByAssoc($assocType, $assocId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM announcement_types WHERE assoc_type = ? AND assoc_id = ? ORDER BY type_id',
			array((int) $assocType, (int) $assocId),
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Get the ID of the last inserted announcement type.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('announcement_types', 'type_id');
	}
}

?>
