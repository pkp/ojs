<?php

/**
 * @file plugins/generic/objectsForReview/classes/ObjectForReviewDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReviewDAO
 * @ingroup plugins_generic_objectsForReview
 * @see ObjectForReview
 *
 * @brief Operations for retrieving and modifying ObjectForReview objects.
 */


class ObjectForReviewDAO extends DAO {
	/** @var string Name of the parent plugin */
	var $parentPluginName;

	/** @var object Object for review person DAO */
	var $objectForReviewPersonDao;

	/**
	 * Constructor.
	 */
	function ObjectForReviewDAO($parentPluginName) {
		parent::DAO();
		$this->parentPluginName = $parentPluginName;
		$this->objectForReviewPersonDao =& DAORegistry::getDAO('ObjectForReviewPersonDAO');
	}

	/**
	 * Retrieve object for review by object ID.
	 * @param $objectId int
	 * @param $contextId int
	 * @return ObjectForReview
	 */
	function &getById($objectId, $contextId = null) {
		$params = array((int) $objectId);
		if ($contextId) $params[] = (int) $contextId;

		$result =& $this->retrieve(
			'SELECT * FROM objects_for_review WHERE object_id = ?'. ($contextId ? ' AND context_id = ?' : ''),
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
	 * @return ObjectForReview
	 */
	function newDataObject() {
		$ofrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$ofrPlugin->import('classes.ObjectForReview');
		return new ObjectForReview();
	}

	/**
	 * Internal function to return an ObjectForReview object from a row.
	 * @param $row array
	 * @return ObjectForReview
	 */
	function &_fromRow(&$row) {
		$object = $this->newDataObject();
		$object->setId($row['object_id']);
		$object->setReviewObjectTypeId($row['review_object_type_id']);
		$object->setContextId($row['context_id']);
		$object->setAvailable($row['available']);
		$object->setDateCreated($row['date_created']);
		$object->setEditorId($row['editor_id']);
		$object->setNotes($row['notes']);

		HookRegistry::call('ObjectForReviewDAO::_fromRow', array(&$object, &$row));

		return $object;
	}

	/**
	 * Insert a new ObjectForReview.
	 * @param $objectForReview ObjectForReview
	 * @return int
	 */
	function insertObject(&$objectForReview) {
		$ret = $this->update(
			sprintf('
				INSERT INTO objects_for_review
					(review_object_type_id,
					context_id,
					available,
					date_created,
					editor_id,
					notes)
				VALUES
					(?, ?, ?, %s, ?, ?)',
				$this->datetimeToDB($objectForReview->getDateCreated())
			),
			array(
				(int) $objectForReview->getReviewObjectTypeId(),
				(int) $objectForReview->getContextId(),
				(int) $objectForReview->getAvailable(),
				(int) $objectForReview->getEditorId(),
				$objectForReview->getNotes()
			)
		);
		$objectForReview->setId($this->getInsertId());

		return $objectForReview->getId();
	}

	/**
	 * Update an existing object for review.
	 * @param $objectForReview ObjectForReview
	 * @return boolean
	 */
	function updateObject(&$objectForReview) {
		$this->update(
			sprintf('UPDATE objects_for_review
				SET
					review_object_type_id = ?,
					context_id = ?,
					available = ?,
					date_created = %s,
					editor_id = ?,
					notes = ?
				WHERE object_id = ?',
				$this->datetimeToDB($objectForReview->getDateCreated())
			),
			array(
				(int) $objectForReview->getReviewObjectTypeId(),
				(int) $objectForReview->getContextId(),
				(int) $objectForReview->getAvailable(),
				$this->nullOrInt($objectForReview->getEditorId()),
				$objectForReview->getNotes(),
				(int) $objectForReview->getId()
			)
		);
	}

	/**
	 * Delete an object for review.
	 * @param $objectForReview ObjectForReview
	 */
	function deleteObject(&$objectForReview) {
		// Delete object
		$this->update('DELETE FROM objects_for_review WHERE object_id = ? AND context_id = ?',
			array((int) $objectForReview->getId(), (int) $objectForReview->getContextId())
		);
		if ($this->getAffectedRows()) {
			// Delete cover image files (for all locales) from the filesystem
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$coverPageSetting = $objectForReview->getCoverPage();
			$publicFileManager->removeJournalFile($objectForReview->getContextId(), $coverPageSetting['fileName']);

			// Delete settings
			$ofrSettingsDao =& DAORegistry::getDAO('ObjectForReviewSettingsDAO');
			$ofrSettingsDao->deleteSettings($objectForReview->getId());
			// Delete persons
			$this->objectForReviewPersonDao->deleteByObjectForReview($objectForReview->getId());
			// Delete assignments
			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			$ofrAssignmentDao->deleteAllByObjectId($objectForReview->getId());
		}
	}

	/**
	 * Delete objects for review by context ID.
	 * @param $contextId int
	 */
	function deleteByContextId($contextId) {
		$objectsForReview = $this->getAllByContextId($contextId);
		while ($objectForReview =& $objectsForReview->next()) {
			$this->deleteObject($objectForReview);
			unset($objectForReview);
		}
	}

	/**
	 * Delete objects for review by review object type ID
	 * to be called only when deleting a review object type.
	 * @param $reviewObjectTypeId int
	 */
	function deleteByReviewObjectTypeId($reviewObjectTypeId) {
		$objectsForReview =& $this->getArrayByReviewObjectTypeId($reviewObjectTypeId);
		foreach ($objectsForReview as $objectId => $objectForReview) {
			$this->deleteObject($objectForReview);
		}
	}

	/**
	 * Retrieve all objects for review in an array for a review object type.
	 * @param $reviewObjectTypeId int
	 * @return array ObjectForReviews ordered by ID
	 */
	function &getArrayByReviewObjectTypeId($reviewObjectTypeId) {
		$result =& $this->retrieve(
			'SELECT * FROM objects_for_review WHERE review_object_type_id = ? ORDER BY object_id',
			(int) $reviewObjectTypeId
		);

		$allObjectsForReview = array();
		while (!$result->EOF) {
			$objectForReview =& $this->_fromRow($result->GetRowAssoc(false));
			$allObjectsForReview[$objectForReview->getId()] =& $objectForReview;
			$result->MoveNext();
			unset($objectForReview);
		}
		$result->Close();
		return $allObjectsForReview;
	}

	/**
	 * Retrieve all objects for reivew matching a particular context ID.
	 * @param $contextId int
	 * @param $searchType int (optional), which field to search
	 * @param $search string (optional), string to match
	 * @param $searchMatch string (optional), type of match ('is' vs. 'contains')
	 * @param $available int (optional), status to match
	 * @param $editorId int, (optional) editor to match
	 * @param $filterType int (optional), review object type ID to match
	 * @param $rangeInfo DBResultRange (optional)
	 * @param $sortBy string (optional), sorting criteria
	 * @param $sortDirection int (optional), sorting direction
	 * @return DAOResultFactory containing matching ObjectForReviewAssignments
	 */
	function &getAllByContextId($contextId, $searchType = null, $search = null, $searchMatch = null, $available = null, $editorId = null, $filterType = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$ofrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$ofrPlugin->import('classes.ReviewObjectMetadata');

		$params = array();

		$sortColumn = '';
		$sortSQL = '';
		switch ($sortBy) {
			case 'title':
				$sortColumn .= ', ofrs.setting_value AS ofr_title';
				$sortSQL .= ' LEFT JOIN object_for_review_settings ofrs ON (ofr.object_id = ofrs.object_id)
					JOIN review_object_metadata rom ON (rom.metadata_id = ofrs.review_object_metadata_id AND rom.metadata_key = \'' . REVIEW_OBJECT_METADATA_KEY_TITLE .'\')';
				break;
			case 'type':
				$sortColumn .= ', COALESCE(rtl.setting_value, rtpl.setting_value) AS ofr_type_name';
				$sortSQL .= ' LEFT JOIN review_object_types rt ON (ofr.review_object_type_id = rt.type_id)
					LEFT JOIN review_object_type_settings rtl ON (ofr.review_object_type_id = rtl.type_id AND rtl.setting_name = \'name\' AND rtl.locale = ?)
					LEFT JOIN review_object_type_settings rtpl ON (ofr.review_object_type_id = rtpl.type_id AND rtpl.setting_name = \'name\' AND rtpl.locale = ?)';
				$params[] = AppLocale::getLocale();
				$params[] = AppLocale::getPrimaryLocale();
			case 'editor':
				$sortColumn .= ', COALESCE(ue.initials, CONCAT(SUBSTRING(ue.first_name FROM 1 FOR 1), SUBSTRING(ue.last_name FROM 1 FOR 1))) AS ed_initials';
				$sortSQL .= 'LEFT JOIN users ue ON (ofr.editor_id = ue.user_id)';
				break;
		}

		$sql = "SELECT DISTINCT ofr.*$sortColumn
			FROM objects_for_review ofr
			$sortSQL";

		switch ($searchType) {
			case OFR_FIELD_TITLE:
				if ($sortBy != 'title') {
					$sql .= ' LEFT JOIN object_for_review_settings ofrs ON (ofr.object_id = ofrs.object_id)
						JOIN review_object_metadata rom ON (rom.metadata_id = ofrs.review_object_metadata_id AND rom.metadata_key = \'' . REVIEW_OBJECT_METADATA_KEY_TITLE .'\')';
				}
				$sql .= ' WHERE LOWER(ofrs.setting_value) ' . ($searchMatch == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$params[] = $searchMatch == 'is' ? $search : "%$search%";
				break;
			case OFR_FIELD_ABSTRACT:
				$sql .= ' LEFT JOIN object_for_review_settings ofrsa ON (ofr.object_id = ofrsa.object_id)
					LEFT JOIN review_object_metadata roma ON (roma.metadata_id = ofrsa.review_object_metadata_id)
					WHERE roma.metadata_key = \'' . REVIEW_OBJECT_METADATA_KEY_ABSTRACT .'\' AND LOWER(ofrsa.setting_value) ' . ($searchMatch == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$params[] = $searchMatch == 'is' ? $search : "%$search%";
				break;
			default:
				$searchType = null;
		}

		if (empty($searchType)) {
			$sql .= ' WHERE';
		} else {
			$sql .= ' AND';
		}

		if (!empty($available)) {
			$sql .= ' ofr.available = 1 AND';
		}

		if (!empty($editorId)) {
			$sql .= ' ofr.editor_id = ? AND';
			$params[] = (int) $editorId;
		}

		if (!empty($filterType)) {
			$sql .= ' ofr.review_object_type_id = ? AND';
			$params[] = (int) $filterType;
		}

		$sql .= " ofr.context_id = ?";
		$params[] = (int) $contextId;

		$sql .= ($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : '');

		$result =& $this->retrieveRange($sql, $params, $rangeInfo);
		$returner = new DAOResultFactory($result, $this, '_fromRow');
		return $returner;
	}

	/**
	 * Check if the review object type exists with the specified ID.
	 * @param $objectId int
	 * @param $contextId int (optional)
	 * @return boolean
	 */
	function objectForReviewExists($objectId, $contextId = null) {
		$params = array((int) $objectId);
		if ($contextId) $params[] = (int) $contextId;

		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM objects_for_review WHERE object_id = ?' . ($contextId ? ' AND context_id = ?' : ''),
			$params
		);

		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Get the ID of the last inserted object for review.
	 * @return int
	 */
	function getInsertId() {
		return parent::getInsertId('objects_for_review', 'object_id');
	}

	/**
	 * Map a column heading value to a database value for sorting
	 * @param string
	 * @return string
	 */
	static function getSortMapping($heading) {
		switch ($heading) {
			case 'title': return 'ofr_title';
			case 'created': return 'ofr.date_created';
			case 'editor': return 'ed_initials';
			case 'status': return 'ofr.available';
			case 'type': return 'ofr_type_name';
			default: return null;
		}
	}

}

?>
