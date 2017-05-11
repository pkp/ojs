<?php

/**
 * @file classes/metadata/MetadataDescriptionDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataDescriptionDAO
 * @ingroup metadata
 * @see MetadataDescription
 *
 * @brief Operations for retrieving and modifying MetadataDescription objects.
 */

import('lib.pkp.classes.metadata.MetadataDescription');


class MetadataDescriptionDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Insert a new meta-data description.
	 *
	 * @param $metadataDescription MetadataDescription
	 * @return integer the new metadata description id
	 */
	function insertObject(&$metadataDescription) {
		$metadataSchema =& $metadataDescription->getMetadataSchema();
		$this->update(
			sprintf('INSERT INTO metadata_descriptions
				(assoc_type, assoc_id, schema_namespace, schema_name, display_name, seq)
				VALUES (?, ?, ?, ?, ?, ?)'),
			array(
				(integer)$metadataDescription->getAssocType(),
				(integer)$metadataDescription->getAssocId(),
				$metadataSchema->getNamespace(),
				$metadataSchema->getName(),
				$metadataDescription->getDisplayName(),
				(integer)$metadataDescription->getSequence()
			)
		);
		$metadataDescription->setId($this->getInsertId());
		$this->_updateObjectMetadata($metadataDescription);
		return $metadataDescription->getId();
	}

	/**
	 * Retrieve a meta-data description by id.
	 * @param $metadataDescriptionId integer
	 * @return MetadataDescription
	 */
	function &getObjectById($metadataDescriptionId) {
		$result = $this->retrieve(
			'SELECT * FROM metadata_descriptions WHERE metadata_description_id = ?', $metadataDescriptionId
		);

		$metadataDescription = null;
		if ($result->RecordCount() != 0) {
			$metadataDescription = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $metadataDescription;
	}

	/**
	 * Retrieve an array of meta-data descriptions matching a particular association id.
	 * @param $assocType int
	 * @param $assocId int
	 * @param $dbResultRange DBResultRange the desired range
	 * @return DAOResultFactory containing matching source descriptions (MetadataDescription objects)
	 */
	function getObjectsByAssocId($assocType, $assocId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT *
			FROM metadata_descriptions
			WHERE assoc_type = ? AND assoc_id = ?
			ORDER BY metadata_description_id DESC',
			array((int)$assocType, (int)$assocId),
			$rangeInfo
		);

	 	return new DAOResultFactory($result, $this, '_fromRow', array('id'));
	}

	/**
	 * Update an existing meta-data description.
	 * @param $metadataDescription MetadataDescription
	 */
	function updateObject(&$metadataDescription) {
		$metadataSchema =& $metadataDescription->getMetadataSchema();
		$this->update(
			'UPDATE	metadata_descriptions
			SET	assoc_type = ?,
				assoc_id = ?,
				schema_name = ?,
				schema_namespace = ?,
				display_name = ?,
				seq = ?
			WHERE metadata_description_id = ?',
			array(
				(integer)$metadataDescription->getAssocType(),
				(integer)$metadataDescription->getAssocId(),
				$metadataSchema->getName(),
				$metadataSchema->getNamespace(),
				$metadataDescription->getDisplayName(),
				$metadataDescription->getSequence(),
				$metadataDescription->getId()
			)
		);
		$this->_updateObjectMetadata($metadataDescription);
	}

	/**
	 * Delete a meta-data description.
	 * @param $metadataDescription MetadataDescription
	 * @return boolean
	 */
	function deleteObject(&$metadataDescription) {
		return $this->deleteObjectById($metadataDescription->getId());
	}

	/**
	 * Delete a meta-data description by id.
	 * @param $metadataDescriptionId int
	 * @return boolean
	 */
	function deleteObjectById($metadataDescriptionId) {
		$params = array((int)$metadataDescriptionId);
		$this->update('DELETE FROM metadata_descriptions WHERE metadata_description_id = ?', $params);
		return $this->update('DELETE FROM metadata_description_settings WHERE metadata_description_id = ?', $params);
	}

	/**
	 * Delete all meta-data descriptions matching a particular association id.
	 * @param $assocType int
	 * @param $assocId int
	 * @return boolean
	 */
	function deleteObjectsByAssocId($assocType, $assocId) {
		$metadataDescriptions = $this->getObjectsByAssocId($assocType, $assocId);
		while ($metadataDescription = $metadataDescriptions->next()) {
			$this->deleteObjectById($metadataDescription->getId());
		}
		return true;
	}

	//
	// Protected helper methods
	//
	/**
	 * Get the ID of the last inserted Source Description.
	 * @return int
	 */
	function getInsertId() {
		return parent::_getInsertId('metadata_descriptions', 'metadata_description_id');
	}


	//
	// Private helper methods
	//
	/**
	 * Identify the meta-data schema class that corresponds
	 * to the schema identifiers passed into this method.
	 *
	 * FIXME: This information should come from a central
	 * meta-data schema registry.
	 *
	 * @param $metadataSchemaId string
	 * @return string
	 */
	function &_resolveSchemaIdentifierToMetadataSchemaName($metadataSchemaId) {
		static $metadataSchemaMapping = array(
			'nlm30:nlm-3.0-element-citation' => 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema',
			'nlm30:nlm-3.0-name' => 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema',
			'openurl10:openurl-1.0-journal' => 'lib.pkp.plugins.metadata.nlm30.schema.Openurl10JournalSchema',
			'openurl10:openurl-1.0-book' => 'lib.pkp.plugins.metadata.nlm30.schema.Openurl10BookSchema',
			'openurl10:openurl-1.0-dissertation' => 'lib.pkp.plugins.metadata.nlm30.schema.Openurl10DissertationSchema'
		);

		// Map the metadata schema identifier to a metadata schema class name.
		assert(isset($metadataSchemaMapping[$metadataSchemaId]));
		$metadataSchemaName = $metadataSchemaMapping[$metadataSchemaId];
		return $metadataSchemaName;
	}

	/**
	 * Construct a new meta-data description object based on the
	 * schema identifiers passed into this method.
	 * @param $metadataSchemaNamespace string
	 * @param $metadataSchemaName string
	 * @param $assocType integer
	 * @return MetadataDescription
	 */
	function &_newDataObject($metadataSchemaNamespace, $metadataSchemaName, $assocType) {
		$metadataSchemaName =& $this->_resolveSchemaIdentifierToMetadataSchemaName($metadataSchemaNamespace.':'.$metadataSchemaName);
		$metadataDescription = new MetadataDescription($metadataSchemaName, $assocType);
		return $metadataDescription;
	}

	/**
	 * Internal function to return a meta-data description
	 * object from a row.
	 * @param $row array
	 * @return MetadataDescription
	 */
	function _fromRow($row) {
		$metadataDescription = $this->_newDataObject($row['schema_namespace'], $row['schema_name'], (int)$row['assoc_type']);
		$metadataDescription->setId((int)$row['metadata_description_id']);
		$metadataDescription->setAssocId((int)$row['assoc_id']);
		$metadataDescription->setDisplayName($row['display_name']);
		$metadataDescription->setSequence((int)$row['seq']);

		$this->getDataObjectSettings('metadata_description_settings', 'metadata_description_id', $row['metadata_description_id'], $metadataDescription);

		return $metadataDescription;
	}

	/**
	 * Update the meta-data
	 * @param $metadataDescription MetadataDescription
	 */
	function _updateObjectMetadata(&$metadataDescription) {
		// Inject a dummy meta-data adapter so that we can
		// use the meta-data persistence infrastructure of the
		// DAO and DataObject classes to persist our meta-data
		// description.
		import('lib.pkp.classes.metadata.MetadataDescriptionDummyAdapter');
		$metadataAdapter = new MetadataDescriptionDummyAdapter($metadataDescription);
		$metadataDescription->addSupportedMetadataAdapter($metadataAdapter);

		$this->updateDataObjectSettings('metadata_description_settings', $metadataDescription,
				array('metadata_description_id' => $metadataDescription->getId()));
	}
}

?>
