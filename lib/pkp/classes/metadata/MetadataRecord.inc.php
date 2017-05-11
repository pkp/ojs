<?php

/**
 * @file classes/metadata/MetadataRecord.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataRecord
 * @ingroup metadata
 * @see MetadataProperty
 * @see MetadataDescription
 *
 * @brief Class modeling a meta-data record (DCMI abstract model: an instance
 *  of a description set, RDF: a graph of several subject nodes with associated
 *  object nodes).
 */


class MetadataRecord {
	/** @var array the MetadataDescriptions in this record */
	var $_descriptions = array();

	//
	// Get/set methods
	//
	/**
	 * Add a meta-data description.
	 * @param $metadataDescription MetadataDescription
	 * @param $replace whether to replace a description if a description for
	 *  the same application entity instance already exists.
	 * @return boolean true if a valid description was added, otherwise false
	 */
	function addDescription($metadataDescription, $replace = true) {
		assert(is_a($metadataDescription, 'MetadataDescription'));

		// Check that the description complies with the meta-data schema
		$descriptionMetadataSchema = $metadataDescription->getMetadataSchema();
		$recordMetadataSchema = $this->getMetadataSchema();
		if ($descriptionMetadataSchema->getName() != $recordMetadataSchema->getName()) return false;

		// Check whether we already have a description for the same
		// application entity instance.
		$applicationEntityId = $this->getApplicationEntityIdFromMetadataDescription($metadataDescription);
		if (isset($this->_descriptions[$applicationEntityId]) && !$replace) return false;

		// Add the description
		$this->_descriptions[$applicationEntityId] =& $metadataDescription;
	}

	/**
	 * Remove description.
	 * @param $applicationEntityId string consisting of 'assocType:assocId'
	 * @return boolean true if the description was found and removed, otherwise false
	 * @see MetadataRecord::getApplicationEntityIdFromMetadataDescription()
	 */
	function removeDescription($applicationEntityId) {
		// Remove the description if it exists
		if (isset($applicationEntityId) && isset($this->_descriptions[$applicationEntityId])) {
			unset($this->_descriptions[$applicationEntityId]);
			return true;
		}

		return false;
	}

	/**
	 * Get all descriptions
	 * @return array statements
	 */
	function &getDescriptions() {
		return $this->_descriptions;
	}

	/**
	 * Get a specific description
	 * @param $applicationEntityId string consisting of 'assocType:assocId'
	 * @return boolean true if the description was found and removed, otherwise false
	 * @see MetadataRecord::getApplicationEntityIdFromMetadataDescription()
	 */
	function &getDescription($applicationEntityId) {
		assert(isset($applicationEntityId));

		// Retrieve the description
		if (isset($this->_descriptions[$applicationEntityId])) {
			return $this->_descriptions[$applicationEntityId];
		} else {
			$nullValue = null;
			return $nullValue;
		}
	}

	/**
	 * Replace all descriptions at once. If one of the descriptions
	 * is invalid then the meta-data record will be empty after this
	 * operation.
	 * @param $descriptions array descriptions
	 * @return boolean true if all descriptions could be added, false otherwise
	 */
	function setDescriptions(&$descriptions) {
		// Delete existing statements
		$this->_descriptions = array();

		// Add descriptions one by one to validate them.
		foreach($descriptions as $description) {
			if (!($this->addDescription($description, false))) {
				$this->_descriptions = array();
			}
		}
		return true;
	}
}
?>
