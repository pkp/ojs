<?php

/**
 * @file classes/tombstone/DataObjectTombstoneDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataObjectTombstoneDAO
 * @ingroup tombstone
 * @see DataObjectTombstone
 *
 * @brief Base class for retrieving and modifying DataObjectTombstone objects.
 */

import('lib.pkp.classes.tombstone.DataObjectTombstone');

class DataObjectTombstoneDAO extends DAO {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Return an instance of the DataObjectTombstone class.
	 * @return DataObjectTombstone
	 */
	function newDataObject() {
		return new DataObjectTombstone();
	}

	/**
	 * Retrieve DataObjectTombstone by id.
	 * @param $tombstoneId int
	 * @param $assocType int
	 * @param $assocId int
	 * @return DataObjectTombstone object
	 */
	function getById($tombstoneId, $assocType = null, $assocId = null) {
		$params = array((int) $tombstoneId);
		if ($assocId !== null && $assocType !== null) {
			$params[] = (int) $assocType;
			$params[] = (int) $assocId;
		}
		$result = $this->retrieve(
			'SELECT DISTINCT * ' . $this->_getSelectTombstoneSql($assocType, $assocId), $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve DataObjectTombstone by data object id.
	 * @param $dataObjectId int
	 * @return DataObjectTombstone object
	 */
	function &getByDataObjectId($dataObjectId) {
		$result = $this->retrieve(
			'SELECT * FROM data_object_tombstones WHERE data_object_id = ?', (int) $dataObjectId
		);

		$dataObjectTombstone = $this->_fromRow($result->GetRowAssoc(false));

		$result->Close();
		return $dataObjectTombstone;
	}

	/**
	 * Creates and returns a data object tombstone object from a row.
	 * @param $row array
	 * @return DataObjectTombstone object
	 */
	function _fromRow($row) {
		$dataObjectTombstone = $this->newDataObject();
		$dataObjectTombstone->setId($row['tombstone_id']);
		$dataObjectTombstone->setDataObjectId($row['data_object_id']);
		$dataObjectTombstone->setDateDeleted($this->datetimeFromDB($row['date_deleted']));
		$dataObjectTombstone->setSetSpec($row['set_spec']);
		$dataObjectTombstone->setSetName($row['set_name']);
		$dataObjectTombstone->setOAIIdentifier($row['oai_identifier']);

		$OAISetObjectsIds = $this->getOAISetObjectsIds($dataObjectTombstone->getId());
		$dataObjectTombstone->setOAISetObjectsIds($OAISetObjectsIds);

		return $dataObjectTombstone;
	}

	/**
	 * Delete DataObjectTombstone by tombstone id.
	 * @param $tombstoneId int
	 * @param $assocType int
	 * @param $assocId int
	 * @return boolean
	 */
	function deleteById($tombstoneId, $assocType = null, $assocId = null) {
		$tombstone = $this->getById($tombstoneId, $assocType, $assocId);
		if (!$tombstone) return false; // Did not exist

		assert(is_a($tombstone, 'DataObjectTombstone'));

		$this->update(
			'DELETE FROM data_object_tombstones WHERE tombstone_id = ?',
			(int) $tombstoneId
		);
		if ($this->getAffectedRows()) {
			$dataObjectTombstoneSettingsDao = DAORegistry::getDAO('DataObjectTombstoneSettingsDAO');
			$settingsDeleted = $dataObjectTombstoneSettingsDao->deleteSettings($tombstoneId);
			$setObjectsDeleted = $this->deleteOAISetObjects($tombstoneId);
			if ($settingsDeleted && $setObjectsDeleted) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Delete DataObjectTombstone by data object id.
	 * @param $dataObjectId int
	 * @return boolean
	 */
	function deleteByDataObjectId($dataObjectId) {
		$dataObjectTombstone =& $this->getByDataObjectId($dataObjectId);
		return $this->deleteById($dataObjectTombstone->getId());
	}

	/**
	 * Inserts a new data object tombstone into data_object_tombstone table.
	 * @param $dataObjectTombstone DataObjectTombstone
	 * @return int Data object tombstone id.
	 */
	function insertObject(&$dataObjectTombstone) {
		$this->update(
			sprintf('INSERT INTO data_object_tombstones
				(data_object_id, date_deleted, set_spec, set_name, oai_identifier)
				VALUES
				(?, %s, ?, ?, ?)',
				$this->datetimeToDB(date('Y-m-d H:i:s'))
			),
			array(
				(int) $dataObjectTombstone->getDataObjectId(),
				$dataObjectTombstone->getSetSpec(),
				$dataObjectTombstone->getSetName(),
				$dataObjectTombstone->getOAIIdentifier()
			)
		);

		$dataObjectTombstone->setId($this->getInsertId());
		$this->insertOAISetObjects($dataObjectTombstone);

		return $dataObjectTombstone->getId();
	}

	/**
	 * Update a data object tombstone in the data_object_tombstones table.
	 * @param $dataObjectTombstone DataObjectTombstone
	 * @return int dataObjectTombstone id
	 */
	function updateObject(&$dataObjectTombstone) {
		$returner = $this->update(
			sprintf('UPDATE	data_object_tombstones SET
					data_object_id = ?,
					date_deleted = %s,
					set_spec = ?,
					set_name = ?,
					oai_identifier = ?
					WHERE	tombstone_id = ?',
				$this->datetimeToDB(date('Y-m-d H:i:s'))
			),
			array(
				(int) $publicationFormatTombstone->getDataObjectId(),
				$publicationFormatTombstone->getSetSpec(),
				$publicationFormatTombstone->getSetName(),
				$publicationFormatTombstone->getOAIIdentifier(),
				(int) $publicationFormatTombstone->getId()
			)
		);

		$this->updateOAISetObjects($dataObjectTombstone);

		return $returner;
	}

	/**
	 * Get the ID of the last inserted data object tombstone.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('data_object_tombstones', 'tombstone_id');
	}

	/**
	 * Retrieve all sets for data object tombstones that are inside of
	 * the passed set object id.
	 * @param $assocType int The assoc type of the parent set object.
	 * @param $assocId int The id of the parent set object.
	 * @return array('setSpec' => setName)
	 */
	function &getSets($assocType, $assocId) {
		$result = $this->retrieve(
			'SELECT DISTINCT dot.set_spec, dot.set_name FROM data_object_tombstones dot
			LEFT JOIN data_object_tombstone_oai_set_objects oso ON (dot.tombstone_id = oso.tombstone_id)
			WHERE oso.assoc_type = ? AND oso.assoc_id = ?',
			array((int) $assocType, (int) $assocId)
		);

		$returner = array();
		while (!$result->EOF) {
			$returner[$result->fields[0]] = $result->fields[1];
			$result->MoveNext();
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Get all objects ids that are part of the passed
	 * tombstone OAI set.
	 * @param $tombstoneId int
	 * @return array assocType => assocId
	 */
	function getOAISetObjectsIds($tombstoneId) {
		$result = $this->retrieve(
			'SELECT * FROM data_object_tombstone_oai_set_objects WHERE tombstone_id = ?',
			(int) $tombstoneId
		);

		$oaiSetObjectsIds = array();
		while ($row = $result->FetchRow()) {
			$oaiSetObjectsIds[$row['assoc_type']] = $row['assoc_id'];
		}

		$result->Close();
		return $oaiSetObjectsIds;
	}

	/**
	 * Delete OAI set objects data from data_object_tombstone_oai_set_objects table.
	 * @param $tombstoneId int The related tombstone id.
	 */
	function deleteOAISetObjects($tombstoneId) {
		$this->update(
			'DELETE FROM data_object_tombstone_oai_set_objects WHERE tombstone_id = ?',
			(int) $tombstoneId
		);

		return $this->getAffectedRows();
	}

	/**
	 * Insert OAI set objects data into data_object_tombstone_oai_set_objects table.
	 * @param $dataObjectTombstone DataObjectTombstone
	 */
	function insertOAISetObjects($dataObjectTombstone) {
		foreach ($dataObjectTombstone->getOAISetObjectsIds() as $assocType => $assocId) {
			$this->update('INSERT INTO data_object_tombstone_oai_set_objects
					(tombstone_id, assoc_type, assoc_id)
					VALUES
					(?, ?, ?)',
				array(
					(int) $dataObjectTombstone->getId(),
					(int) $assocType,
					(int) $assocId
				)
			);
		}
	}

	/**
	 * Update OAI set objects data into data_object_tombstone_oai_set_objects table.
	 * @param $dataObjectTombstone DataObjectTombstone
	 * @return boolean
	 */
	function updateOAISetObjects($dataObjectTombstone) {
		foreach ($dataObjectTombstone->getOAISetObjectsIds() as $assocType => $assocId) {
			$this->update('UPDATE data_object_tombstone_oai_set_objects SET
					assoc_type = ?,
					assoc_id = ?
					WHERE	tombstone_id = ?',
				array(
					(int) $assocType,
					(int) $assocId,
					(int) $dataObjectTombstone->getId()
				)
			);
		}
	}


	//
	// Private helper methods.
	//

	/**
	 * Get the sql to select a tombstone from table, optionally
	 * using an OAI set object id.
	 * @return string
	 */
	function _getSelectTombstoneSql($assocType, $assocId) {
		return 'FROM data_object_tombstones dot
			LEFT JOIN data_object_tombstone_oai_set_objects oso ON (dot.tombstone_id = oso.tombstone_id)
			WHERE dot.tombstone_id = ?' .
			(isset($assocId) && isset($assocType) ? 'AND oso.assoc_type = ? AND oso.assoc_id = ?' : '');
	}
}

?>
