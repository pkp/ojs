<?php

/**
 * @file plugins/generic/objectsForReview/classes/ReviewObjectTypeDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
	/** @var $parentPluginName string Name of parent plugin */
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
	 * @param $journalId int (optional)
	 * @return ReviewObjectType
	 */
	function &getById($typeId, $journalId = null) {
		$params = array((int) $typeId);
		if ($journalId) $params[] = (int) $journalId;

		$result =& $this->retrieve(
			'SELECT * FROM review_object_types WHERE type_id = ?' . ($journalId ? ' AND journal_id = ?' : ''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Retrieve a review object type by key.
	 * @param $typeKey string
	 * @param $journalId int (optional)
	 * @return ReviewObjectType
	 */
	function &getByKey($typeKey, $journalId = null) {
		$params = array((int) $typeKey);
		if ($journalId) $params[] = (int) $journalId;

		$result =& $this->retrieve(
			'SELECT * FROM review_object_types WHERE type_key = ?' . ($journalId ? ' AND journal_id = ?' : ''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
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
		$reviewObjectType->setJournalId($row['journal_id']);
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
	 * @param $reviewObjectType object
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
				(journal_id, is_active, type_key)
				VALUES
				(?, ?, ?)',
			array(
				(int) $reviewObjectType->getJournalId(),
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
					journal_id = ?,
					is_active = ?,
					type_key = ?
				WHERE type_id = ?',
			array(
				(int) $reviewObjectType->getJournalId(),
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
	 * @param $journalId int (optional)
	 */
	function deleteById($typeId, $journalId = null) {
		$params = array((int) $typeId);
		if (isset($journalId)) $params[] = (int) $journalId;

		$this->update('DELETE FROM review_object_types WHERE type_id = ?' . (isset($journalId) ? ' AND journal_id = ?' : ''),
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
		return false;
	}

	/**
	 * Delete all review object types by journal ID.
	 * @param $journalId int
	 * @param $active boolean (otional)
	 */
	function deleteByJournalId($journalId, $active = null) {
		$reviewObjectTypes = $this->getByJournalId($journalId, $active);
		while (!$reviewObjectTypes->eof()) {
			$reviewObjectType =& $reviewObjectTypes->next();
			$this->deleteById($reviewObjectType->getId());
		}
	}

	/**
	 * Get all review object types by jorunal ID.
	 * @param $journalId int
	 * @param $active boolean (optional)
	 * @param $rangeInfo DBResultRange (optional)
	 * @return DAOResultFactory containing matching ReviewForms
	 */
	function &getByJournalId($journalId, $active = null, $rangeInfo = null) {
		$params = array((int) $journalId);
		if ($active) {
			$params[] = $active === true ? 1 : 0;
		}

		$result =& $this->retrieveRange(
			'SELECT	* FROM review_object_types WHERE journal_id = ?' . ($active?' AND is_active = ?':''),
			$params, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_fromRow');
		return $returner;
	}

	/**
	 * Retrieve review object types IDs for a journal, sorted alphabetically.
	 * @param $journalId int
	 * @param $active boolean (optional)
	 * @return array
	 */
	function &getTypeIdsAlphabetizedByJournal($journalId, $active = null) {
		$params = array(
			'name', AppLocale::getLocale(),
			'name', AppLocale::getPrimaryLocale(),
			(int) $journalId
		);
		if ($active) {
			$params[] = $active === true ? 1 : 0;
		}

		$result =& $this->retrieve(
					'SELECT	t.type_id, t.is_active, t.type_key,
				COALESCE(nl.setting_value, npl.setting_value) AS type_name
			FROM	review_object_types t
				LEFT JOIN review_object_type_settings nl ON (nl.type_id = t.type_id AND nl.setting_name = ? AND nl.locale = ?)
				LEFT JOIN review_object_type_settings npl ON (npl.type_id = t.type_id AND npl.setting_name = ? AND npl.locale = ?)
			WHERE t.journal_id = ?' .
			($active?' AND t.is_active = ?':'') . ' ORDER BY type_name',
			$params
		);

		$types = array();
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$types[] = array('typeId' => $row['type_id'], 'typeKey' => $row['type_key'], 'typeName' => $row['type_name'], 'typeActive' => $row['is_active']);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
		return $types;
	}

	/**
	 * Check if review object type exists with the specified ID.
	 * @param $typeId int
	 * @param $journalId int (optional)
	 * @return boolean
	 */
	function reviewObjectTypeExists($typeId, $journalId = null) {
		$params = array((int) $typeId);
		if (isset($journalId)) $params[] = (int) $journalId;

		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM review_object_types WHERE type_id = ?' . ($journalId ? ' AND journal_id = ?' : ''),
			$params
		);

		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Get all installed default types i.e. their keys.
	 * @param $journalId int
	 * @return array
	 */
	function getTypeKeys($journalId) {
		$params = array((int) $journalId);
		$result =& $this->retrieve(
			'SELECT type_key FROM review_object_types WHERE journal_id = ?',
			$params
		);

		$typeKeys = array();
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$typeKeys[] = $row['type_key'];
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
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
