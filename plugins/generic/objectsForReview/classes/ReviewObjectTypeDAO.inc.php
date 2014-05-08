<?php

/**
 * @file plugins/generic/objectsForReview/classes/ReviewObjectTypeDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewObjectTypeDAO
 * @ingroup plugins_generic_booksForReview
 * @see ReviewObjectType
 *
 * @brief Operations for retrieving and modifying ReviewObjectType objects.
 *
 */

class ReviewObjectTypeDAO extends DAO {
	/** @var string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor.
	 */
	function ReviewObjectTypeDAO($parentPluginName) {
		parent::DAO();
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Retrieve a review object type by ID.
	 * @param $typeId int
	 * @param $contextId int (optional)
	 * @return ReviewObjectType
	 */
	function &getById($typeId, $contextId = null) {
		$params = array((int) $typeId);
		if ($contextId) $params[] = (int) $contextId;

		$result =& $this->retrieve(
			'SELECT * FROM review_object_types WHERE type_id = ?' . ($contextId ? ' AND context_id = ?' : ''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a review object type by key.
	 * @param $typeKey string
	 * @param $contextId int (optional)
	 * @return ReviewObjectType
	 */
	function &getByKey($typeKey, $contextId = null) {
		$params = array((int) $typeKey);
		if ($contextId) $params[] = (int) $contextId;

		$result =& $this->retrieve(
			'SELECT * FROM review_object_types WHERE type_key = ?' . ($contextId ? ' AND context_id = ?' : ''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ReviewObjectType
	 */
	function newDataObject() {
		$ofrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$ofrPlugin->import('classes.ReviewObjectType');
		return new ReviewObjectType();
	}

	/**
	 * Internal function to return a ReviewObjectType object from a row.
	 * @param $row array
	 * @return ReviewObjectType
	 */
	function &_fromRow(&$row) {
		$reviewObjectType = $this->newDataObject();
		$reviewObjectType->setId($row['type_id']);
		$reviewObjectType->setContextId($row['context_id']);
		$reviewObjectType->setActive($row['is_active']);
		$reviewObjectType->setKey($row['type_key']);

		$this->getDataObjectSettings('review_object_type_settings', 'type_id', $row['type_id'], $reviewObjectType);

		HookRegistry::call('ReviewObjectTypeDAO::_fromRow', array(&$reviewObjectType, &$row));

		return $reviewObjectType;
	}

	/**
	 * Get the list of fields for which data can be localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name', 'description');
	}

	/**
	 * Update the localized fields for this table
	 * @param $reviewObjectType ReviewObjectType
	 */
	function updateLocaleFields(&$reviewObjectType) {
		$this->updateDataObjectSettings('review_object_type_settings', $reviewObjectType, array(
			'type_id' => $reviewObjectType->getId()
		));
	}

	/**
	 * Insert a new review object type.
	 * @param $reviewObjectType ReviewObjectType
	 */
	function insertObject(&$reviewObjectType) {
		$this->update(
			'INSERT INTO review_object_types
				(context_id, is_active, type_key)
				VALUES
				(?, ?, ?)',
			array(
				(int) $reviewObjectType->getContextId(),
				$reviewObjectType->getActive() ? 1 : 0,
				$reviewObjectType->getKey()
			)
		);
		$reviewObjectType->setId($this->getInsertId());
		$this->updateLocaleFields($reviewObjectType);
		return $reviewObjectType->getId();
	}

	/**
	 * Update an existing review object type.
	 * @param $reviewObjectType ReviewObjectType
	 */
	function updateObject(&$reviewObjectType) {
		$returner = $this->update(
			'UPDATE review_object_types
				SET
					context_id = ?,
					is_active = ?,
					type_key = ?
				WHERE type_id = ?',
			array(
				(int) $reviewObjectType->getContextId(),
				$reviewObjectType->getActive() ? 1 : 0,
				$reviewObjectType->getKey(),
				(int) $reviewObjectType->getId()
			)
		);
		$this->updateLocaleFields($reviewObjectType);
		return $returner;
	}

	/**
	 * Delete a review object type.
	 * @param $reviewObjectType ReviewObjectType
	 */
	function deleteObject(&$reviewObjectType) {
		return $this->deleteById($reviewObjectType->getId());
	}

	/**
	 * Delete a review object type by ID.
	 * @param $typeId int
	 * @param $contextId int (optional)
	 */
	function deleteById($typeId, $contextId = null) {
		$params = array((int) $typeId);
		if ($contextId) $params[] = (int) $contextId;

		$this->update('DELETE FROM review_object_types WHERE type_id = ?' . ($contextId ? ' AND context_id = ?' : ''),
			$params
		);
		if ($this->getAffectedRows()) {
			// Delete settings
			$this->update('DELETE FROM review_object_type_settings WHERE type_id = ?',
				(int) $typeId
			);
			// Delete metadata
			$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
			$reviewObjectMetadataDao->deleteByReviewObjectTypeId($typeId);
			// Delete objects for review of this type
			$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
			$ofrDao->deleteByReviewObjectTypeId($typeId);
		}
	}

	/**
	 * Delete all review object types by context ID.
	 * @param $contextId int
	 */
	function deleteByContextId($contextId) {
		$reviewObjectTypes = $this->getByContextId($contextId);
		while ($reviewObjectType =& $reviewObjectTypes->next()) {
			$this->deleteById($reviewObjectType->getId());
			unset($reviewObjectType);
		}
	}

	/**
	 * Get all review object types by jorunal ID.
	 * @param $contextId int
	 * @param $rangeInfo DBResultRange (optional)
	 * @return DAOResultFactory containing matching ReviewForms
	 */
	function &getByContextId($contextId, $rangeInfo = null) {
		$params = array((int) $contextId);

		$result =& $this->retrieveRange(
			'SELECT	* FROM review_object_types WHERE context_id = ?',
			$params, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_fromRow');
		return $returner;
	}

	/**
	 * Retrieve review object types IDs for a context, sorted alphabetically.
	 * @param $contextId int
	 * @return array
	 */
	function &getTypeIdsAlphabetizedByContext($contextId) {
		$params = array(
			'name', AppLocale::getLocale(),
			'name', AppLocale::getPrimaryLocale(),
			(int) $contextId
		);

		$result =& $this->retrieve(
					'SELECT	t.type_id, t.is_active, t.type_key,
				COALESCE(nl.setting_value, npl.setting_value) AS type_name
			FROM	review_object_types t
				LEFT JOIN review_object_type_settings nl ON (nl.type_id = t.type_id AND nl.setting_name = ? AND nl.locale = ?)
				LEFT JOIN review_object_type_settings npl ON (npl.type_id = t.type_id AND npl.setting_name = ? AND npl.locale = ?)
			WHERE t.context_id = ? ORDER BY type_name',
			$params
		);

		$types = array();
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$types[] = array('typeId' => $row['type_id'], 'typeKey' => $row['type_key'], 'typeName' => $row['type_name'], 'typeActive' => $row['is_active']);
			$result->MoveNext();
		}
		$result->Close();
		return $types;
	}

	/**
	 * Check if review object type exists with the specified ID.
	 * @param $typeId int
	 * @param $contextId int (optional)
	 * @return boolean
	 */
	function reviewObjectTypeExists($typeId, $contextId = null) {
		$params = array((int) $typeId);
		if ($contextId) $params[] = (int) $contextId;

		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM review_object_types WHERE type_id = ?' . ($contextId ? ' AND context_id = ?' : ''),
			$params
		);

		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Get all installed default types i.e. their keys.
	 * @param $contextId int
	 * @return array
	 */
	function getTypeKeys($contextId) {
		$params = array((int) $contextId);
		$result =& $this->retrieve(
			'SELECT type_key FROM review_object_types WHERE context_id = ?',
			$params
		);

		$typeKeys = array();
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$typeKeys[] = $row['type_key'];
			$result->MoveNext();
		}
		$result->Close();
		return $typeKeys;
	}

	/**
	 * Get the ID of the last inserted review object type.
	 * @return int
	 */
	function getInsertId() {
		return parent::getInsertId('review_object_types', 'type_id');
	}

}

?>
