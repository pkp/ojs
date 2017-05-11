<?php

/**
 * @file classes/tombstone/DataObjectTombstone.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataObjectTombstone
 * @ingroup tombstone
 *
 * @brief Base class for data object tombstones.
 */

class DataObjectTombstone extends DataObject {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * get data object id
	 * @return int
	 */
	function getDataObjectId() {
		return $this->getData('dataObjectId');
	}

	/**
	 * set data object id
	 * @param $dataObjectId int
	 */
	function setDataObjectId($dataObjectId) {
		$this->setData('dataObjectId', $dataObjectId);
	}

	/**
	 * get date deleted
	 * @return date
	 */
	function getDateDeleted() {
		return $this->getData('dateDeleted');
	}

	/**
	 * set date deleted
	 * @param $dateDeleted date
	 */
	function setDateDeleted($dateDeleted) {
		$this->setData('dateDeleted', $dateDeleted);
	}

	/**
	 * Stamp the date of the deletion to the current time.
	 */
	function stampDateDeleted() {
		return $this->setDateDeleted(Core::getCurrentDate());
	}

	/**
	 * Get oai setSpec.
	 * @return string
	 */
	function getSetSpec() {
		return $this->getData('setSpec');
	}

	/**
	 * Set oai setSpec.
	 * @param $setSpec string
	 */
	function setSetSpec($setSpec) {
		$this->setData('setSpec', $setSpec);
	}

	/**
	 * Get oai setName.
	 * @return string
	 */
	function getSetName() {
		return $this->getData('setName');
	}

	/**
	 * Set oai setName.
	 * @param $setName string
	 */
	function setSetName($setName) {
		$this->setData('setName', $setName);
	}

	/**
	 * Get oai identifier.
	 * @return string
	 */
	function getOAIIdentifier() {
		return $this->getData('oaiIdentifier');
	}

	/**
	 * Set oai identifier.
	 * @param $oaiIdentifier string
	 */
	function setOAIIdentifier($oaiIdentifier) {
		$this->setData('oaiIdentifier', $oaiIdentifier);
	}

	/**
	 * Get an specific object id that is part of
	 * the OAI set of this tombstone.
	 * @param $assocType int
	 * @return int The object id.
	 */
	function getOAISetObjectId($assocType) {
		$setObjectsIds = $this->getOAISetObjectsIds();
		if (isset($setObjectsIds[$assocType])) {
			return $setObjectsIds[$assocType];
		} else {
			return null;
		}
	}

	/**
	 * Set an specific object id that is part of
	 * the OAI set of this tombstone.
	 * @param $assocType int
	 * @param $assocId int
	 */
	function setOAISetObjectId($assocType, $assocId) {
		$setObjectsIds = $this->getOAISetObjectsIds();
		$setObjectsIds[$assocType] = $assocId;

		$this->setOAISetObjectsIds($setObjectsIds);
	}

	/**
	 * Get all objects ids that are part of
	 * the OAI set of this tombstone.
	 * @return array assocType => assocId
	 */
	function getOAISetObjectsIds() {
		return $this->getData('OAISetObjectsIds');
	}

	/**
	 * Set all objects ids that are part of
	 * the OAI set of this tombstone.
	 * @param $OAISetObjectsIds array assocType => assocId
	 */
	function setOAISetObjectsIds($OAISetObjectsIds) {
		$this->setData('OAISetObjectsIds', $OAISetObjectsIds);
	}
}

?>
