<?php

/**
 * @file plugins/generic/objectsForReview/classes/ObjectForReviewAssignmentDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReviewAssignmentDAO
 * @ingroup plugins_generic_objectsForReview
 * @see ObjectForReviewAssignment
 *
 * @brief Operations for retrieving and modifying ObjectForReviewAssignment objects.
 */

/* These constants are used for user-selectable search fields. */
define('OFR_FIELD_TITLE', 		'title');
define('OFR_FIELD_ABSTRACT', 'description');


class ObjectForReviewAssignmentDAO extends DAO {
	/** @var string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function ObjectForReviewAssignmentDAO($parentPluginName) {
		parent::DAO();
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Retrieve assignment by ID.
	 * @param $assignmentId int
	 * @param $objectId int (optional)
	 * @return ObjectForReviewAssignment
	 */
	function getById($assignmentId, $objectId = null) {
		$params = array((int) $assignmentId);
		if ($objectId !== null) $params[] = (int) $objectId;

		$result =& $this->retrieve(
			'SELECT * FROM object_for_review_assignments WHERE assignment_id = ?'
			. ($objectId !== null?' AND object_id = ?':''),
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
	 * Determine if the assignment exists
	 * @param $objectId int
	 * @param $userId int (optional)
	 * @param $submissionId int (optional)
	 * @return boolean
	 */
	function assignmentExists($objectId, $userId = null, $submissionId = null) {
		$params = array((int) $objectId);
		$sql = 'SELECT COUNT(*) FROM object_for_review_assignments WHERE object_id = ?';
		if ($userId) {
			$sql .= ' AND user_id = ?';
			$params[] = (int) $userId;
		}
		if ($submissionId) {
			$sql .= ' AND submission_id = ?';
			$params[] = (int) $submissionId;
		}

		$result =& $this->retrieve($sql, $params);

		$returner = isset($result->fields[0]) && $result->fields[0] > 0 ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ObjectForReviewAssignment
	 */
	function newDataObject() {
		$ofrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$ofrPlugin->import('classes.ObjectForReviewAssignment');
		return new ObjectForReviewAssignment();
	}

	/**
	 * Internal function to return an ObjectForReviewAssignment object from a row.
	 * @param $row array
	 * @return ObjectForReviewAssignment
	 */
	function &_fromRow($row) {
		$assignment = $this->newDataObject();
		$assignment->setId($row['assignment_id']);
		$assignment->setObjectId($row['object_id']);
		$assignment->setUserId($row['user_id']);
		$assignment->setSubmissionId($row['submission_id']);
		$assignment->setStatus($row['status']);
		$assignment->setDateRequested($this->datetimeFromDB($row['date_requested']));
		$assignment->setDateAssigned($this->datetimeFromDB($row['date_assigned']));
		$assignment->setDateMailed($this->datetimeFromDB($row['date_mailed']));
		$assignment->setDateDue($this->datetimeFromDB($row['date_due']));
		$assignment->setDateRemindedBefore($this->datetimeFromDB($row['date_reminded_before']));
		$assignment->setDateRemindedAfter($this->datetimeFromDB($row['date_reminded_after']));
		$assignment->setNotes($row['notes']);

		HookRegistry::call('ObjectForReviewAssignmentDAO::_fromRow', array(&$assignment, &$row));

		return $assignment;
	}

	/**
	 * Insert a new assignment.
	 * @param $assignment ObjectForReviewAssignment
	 * @return int
	 */
	function insertObject(&$assignment) {
		$this->update(
			sprintf(
				'INSERT INTO object_for_review_assignments
				(object_id, user_id, submission_id, status, date_requested, date_assigned, date_mailed, date_due, date_reminded_before, date_reminded_after, notes)
				VALUES
				(?, ?, ?, ?, %s, %s, %s, %s, %s, %s, ?)',
				$this->datetimeToDB($assignment->getDateRequested()),
				$this->datetimeToDB($assignment->getDateAssigned()),
				$this->datetimeToDB($assignment->getDateMailed()),
				$this->datetimeToDB($assignment->getDateDue()),
				$this->datetimeToDB($assignment->getDateRemindedBefore()),
				$this->datetimeToDB($assignment->getDateRemindedAfter())
			),
			array(
				(int) $assignment->getObjectId(),
				$this->nullOrInt($assignment->getUserId()),
				$this->nullOrInt($assignment->getSubmissionId()),
				(int) $assignment->getStatus(),
				$assignment->getNotes()
			)
		);
		$assignment->setId($this->getInsertId());
		return $assignment->getId();
	}

	/**
	 * Update an existing assignment.
	 * @param $assignment ObjectForReviewAssignment
	 * @return boolean
	 */
	function updateObject(&$assignment) {
		$returner = $this->update(
			sprintf(
				'UPDATE	object_for_review_assignments
				SET	object_id = ?,
					user_id = ?,
					submission_id = ?,
					status = ?,
					date_requested = %s,
					date_assigned = %s,
					date_mailed = %s,
					date_due = %s,
					date_reminded_before = %s,
					date_reminded_after = %s,
					notes = ?
				WHERE	assignment_id = ?',
				$this->datetimeToDB($assignment->getDateRequested()),
				$this->datetimeToDB($assignment->getDateAssigned()),
				$this->datetimeToDB($assignment->getDateMailed()),
				$this->datetimeToDB($assignment->getDateDue()),
				$this->datetimeToDB($assignment->getDateRemindedBefore()),
				$this->datetimeToDB($assignment->getDateRemindedAfter())
			),
			array(
				(int) $assignment->getObjectId(),
				$this->nullOrInt($assignment->getUserId()),
				$this->nullOrInt($assignment->getSubmissionId()),
				(int) $assignment->getStatus(),
				$assignment->getNotes(),
				(int) $assignment->getId()
			)
		);
		return $returner;
	}

	/**
	 * Delete an assignment.
	 * @param $assignment ObjectForReviewAssignment
	 * @return boolean
	 */
	function deleteObject($assignment) {
		return $this->deleteById($assignment->getId(), $assignment->getObjectId());
	}

	/**
	 * Delete an assignment by ID.
	 * @param $assignmentId int
	 * @param $objectId int (optional)
	 * @return boolean
	 */
	function deleteById($assignmentId, $objectId = null) {
		$params = array((int) $assignmentId);
		if ($objectId !== null) $params[] = (int) $objectId;

		return $this->update('
			DELETE FROM object_for_review_assignments WHERE assignment_id = ?'
			. ($objectId !== null?' AND object_id = ?':''),
			$params
		);
	}

	/**
	 * Delete all assignments for an object.
	 * @param $objectId int
	 * @return boolean
	 */
	function deleteAllByObjectId($objectId) {
		$params = array((int) $objectId);
		return $this->update('
			DELETE FROM object_for_review_assignments WHERE object_id = ?',
			$params
		);
	}

	/**
	 * Retrieve the assignment matching the object and the user.
	 * @param $objectId int
	 * @param $userId int
	 * @return ObjectForReviewAssignment
	 */
	function &getByObjectAndUserId($objectId, $userId) {
		$params = array((int) $objectId, (int) $userId);
		$sql = 'SELECT * FROM object_for_review_assignments WHERE object_id = ? AND user_id = ?';
		$result =& $this->retrieve($sql, $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve all assignments for an object for review
	 * @param $objectId int
	 * @return array
	 */
	function &getAllByObjectId($objectId) {
		$returner =& $this->_getAllInternally($objectId);
		return $returner;
	}

	/**
	 * Retrieve all assignments for an user
	 * @param $userId int
	 * @return array
	 */
	function &getAllByUserId($userId) {
		$returner =& $this->_getAllInternally(null, $userId);
		return $returner;
	}

	/**
	 * Retrieve all assignments for a submission
	 * @param $submissionId int
	 * @return array
	 */
	function &getAllBySubmissionId($submissionId) {
		$returner =& $this->_getAllInternally(null, null, $submissionId);
		return $returner;
	}

	/**
	 * Retrieve all incomplete assignments matching a particular context ID.
	 * @param $contextId int
	 * @return array
	 */
	function getIncompleteAssignmentsByContextId($contextId) {
		$params = array((int) $contextId);
		$result =& $this->retrieve(
			'SELECT ofra.*
			FROM object_for_review_assignments ofra
            JOIN objects_for_review ofr ON (ofra.object_id = ofr.object_id)
            WHERE ofr.context_id = ? AND ofra.submission_id IS NULL AND (ofra.date_assigned IS NOT NULL OR ofra.date_mailed IS NOT NULL)',
			$params
		);

		$incompleteAssignements = array();
		while (!$result->EOF) {
			$incompleteAssignements[] =& $this->_fromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		return $incompleteAssignements;
	}


	/**
	 * Retrieve all assignments matching a particular context ID.
	 * @param $contextId int
	 * @param $searchType int (optional), which field to search
	 * @param $search string (optional), string to match
	 * @param $searchMatch string (optional), type of match ('is' vs. 'contains')
	 * @param $status int (optional), status to match
	 * @param $userId int (optional), user to match
	 * @param $editorId int (optional), editor to match
	 * @param $filterType int (optional), review object type ID to match
	 * @param $rangeInfo DBResultRange (optional)
	 * @param $sortBy string (optional), sorting criteria
	 * @param $sortDirection int (optional), sorting direction
	 * @return DAOResultFactory containing matching ObjectForReviewAssignments
	 */
 	 function &getAllByContextId($contextId, $searchType = null, $search = null, $searchMatch = null, $status = null, $userId = null, $editorId = null, $filterType = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$ofrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$ofrPlugin->import('classes.ReviewObjectMetadata');
		$ofrPlugin->import('classes.ObjectForReviewAssignment');

		$params = array();

		$sortColumn = '';
		$sortSQL = '';
		switch ($sortBy) {
			case 'title':
				$sortColumn .= ', ofrs.setting_value AS ofr_title';
				$sortSQL .= ' LEFT JOIN object_for_review_settings ofrs ON (ofra.object_id = ofrs.object_id)
                		JOIN review_object_metadata rom ON (rom.metadata_id = ofrs.review_object_metadata_id AND rom.metadata_key = \'' . REVIEW_OBJECT_METADATA_KEY_TITLE .'\')';
              	break;
			case 'type':
				$sortColumn .= ', COALESCE(rtl.setting_value, rtpl.setting_value) AS ofr_type_name';
				$sortSQL .= ' LEFT JOIN review_object_types rt ON (ofr.review_object_type_id = rt.type_id)
                		LEFT JOIN review_object_type_settings rtl ON (ofr.review_object_type_id = rtl.type_id AND rtl.setting_name = \'name\' AND rtl.locale = ?)
				        LEFT JOIN review_object_type_settings rtpl ON (ofr.review_object_type_id = rtpl.type_id AND rtpl.setting_name = \'name\' AND rtpl.locale = ?)';
 				$params[] = AppLocale::getLocale();
 				$params[] = AppLocale::getPrimaryLocale();
			case 'reviewer':
				$sortSQL .= ' LEFT JOIN users u ON (ofra.user_id = u.user_id)';
				break;
 			case 'editor':
				$sortColumn .= ', COALESCE(ue.initials, CONCAT(SUBSTRING(ue.first_name FROM 1 FOR 1), SUBSTRING(ue.last_name FROM 1 FOR 1))) AS ed_initials';
				$sortSQL .= ' LEFT JOIN users ue ON (ofr.editor_id = ue.user_id)';
				break;
		}

		$sql = "SELECT DISTINCT ofra.*$sortColumn
				FROM object_for_review_assignments ofra
                LEFT JOIN objects_for_review ofr ON (ofra.object_id = ofr.object_id)
                $sortSQL";

		switch ($searchType) {
			case OFR_FIELD_TITLE:
				if ($sortBy != 'title') {
					$sql .= ' LEFT JOIN object_for_review_settings ofrs ON (ofra.object_id = ofrs.object_id)
							JOIN review_object_metadata rom ON (rom.metadata_id = ofrs.review_object_metadata_id AND rom.metadata_key = \'' . REVIEW_OBJECT_METADATA_KEY_TITLE .'\')';
				}
				$sql .= ' WHERE LOWER(ofrs.setting_value) ' . ($searchMatch == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
				$params[] = $searchMatch == 'is' ? $search : "%$search%";
				break;
			case OFR_FIELD_ABSTRACT:
				$sql .= ' LEFT JOIN object_for_review_settings ofrsa ON (ofra.object_id = ofrsa.object_id)
					LEFT JOIN review_object_metadata rom ON rom.metadata_id = ofrs.review_object_metadata_id AND rom.metadata_key = \'' . REVIEW_OBJECT_METADATA_KEY_ABSTRACT .'\'
					WHERE LOWER(ofrs.setting_value) ' . ($searchMatch == 'is' ? '=' : 'LIKE') . ' LOWER(?)';
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

		if (!empty($status)) {
			$sql .= ' ofra.status = ? AND';
			$params[] = (int) $status;

		}

		if (!empty($userId)) {
			$sql .= ' ofra.user_id = ? AND';
			$params[] = (int) $userId;
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
	 * Get all objects IDs assigned to an author
	 * @param $userId int
	 * @return array of object IDs
	 */
	function &getObjectIds($userId) {
		$result =& $this->retrieve(
				'SELECT object_id FROM object_for_review_assignments WHERE user_id = ?',
				(int) $userId
		);

		$objectIds = array();
		while (!$result->EOF) {
			$objectIds[] = $result->fields[0];
			$result->MoveNext();
		}
		$result->Close();
		return $objectIds;
	}

	/**
	 * Get all users IDs assigned to an object for review
	 * @param $objectId int
	 * @return array of user IDs
	 */
	function &getUserIds($objectId) {
		$result =& $this->retrieve(
				'SELECT user_id FROM object_for_review_assignments WHERE object_id = ?',
				(int) $objectId
		);

		$userIds = array();
		while (!$result->EOF) {
			$userIds[] = $result->fields[0];
			$result->MoveNext();
		}
		$result->Close();
		return $userIds;
	}

	/**
	 * Transfer all existing assignments to another user.
	 * @param $oldUserId int
	 * @param $newUserId int
	 */
	function transferAssignments($oldUserId, $newUserId) {
		return $this->update(
			'UPDATE	object_for_review_assignments
			SET	user_id = ?
			WHERE	user_id = ?',
			array(
				(int) $newUserId,
				(int) $oldUserId
			)
		);
	}

	/**
	 * Retrieve status counts for a particular context (and optionally user).
	 * @param $contextId int
	 * @param $status int (optional), objects and assignment status to match
	 * @param $userId int (optional), user to match
	 * @return int
	 */
	function getStatusCount($contextId, $status = null, $userId = null) {
		$paramArray = array((int) $contextId);
		$sql = 'SELECT COUNT(*)
				FROM objects_for_review ofr
				LEFT JOIN object_for_review_assignments ofra ON ofr.object_id = ofra.object_id
				WHERE ofr.context_id = ?';

		if ($status) {
			if ($status == OFR_STATUS_AVAILABLE) {
				$sql .= ' AND ofr.available = 1';
			} else {
				$sql .= ' AND ofra.status = ?';
				$paramArray[] = (int) $status;
			}
		}

		if ($userId) {
			$sql .= ' AND ofra.user_id = ?';
			$paramArray[] = (int) $userId;
		}

		$result =& $this->retrieve($sql, $paramArray);
		return isset($result->fields[0]) ? $result->fields[0] : 0;
	}

	/**
	 * Retrieve all status counts for a particular context (and optionally user).
	 * @param $contextId int
	 * @param $userId int (optional), user to match
	 * @return array, status as index
	 */
	function &getStatusCounts($contextId, $userId = null) {
		$ofrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$ofrPlugin->import('classes.ObjectForReviewAssignment');
		$counts = array();
		$counts[OFR_STATUS_AVAILABLE] = $this->getStatusCount($contextId, OFR_STATUS_AVAILABLE, $userId);
		$counts[OFR_STATUS_REQUESTED] = $this->getStatusCount($contextId, OFR_STATUS_REQUESTED, $userId);
		$counts[OFR_STATUS_ASSIGNED] = $this->getStatusCount($contextId, OFR_STATUS_ASSIGNED, $userId);
		$counts[OFR_STATUS_MAILED] = $this->getStatusCount($contextId, OFR_STATUS_MAILED, $userId);
		$counts[OFR_STATUS_SUBMITTED] = $this->getStatusCount($contextId, OFR_STATUS_SUBMITTED, $userId);
		return $counts;
	}

	/**
	 * Change the status of the assignment
	 * @param $objectId int
	 * @param $userId int
	 * @param $status int OFR_STATUS_...
	 */
	function changeStatus($objectId, $userId, $status) {
		$this->update(
			'UPDATE object_for_review_assignments SET status = ? WHERE object_id = ? AND user_id = ?', array((int) $status, (int) $objectId, (int) $userId)
		);
	}

	/**
	 * Get the ID of the last inserted assignment.
	 * @return int
	 */
	function getInsertId() {
		return parent::getInsertId('object_for_review_assignments', 'assignment_id');
	}

	/**
	 * Map a column heading value to a database value for sorting
	 * @param string
	 * @return string
	 */
	static function getSortMapping($heading) {
		switch ($heading) {
			case 'title': return 'ofr_title';
			case 'due': return 'ofra.date_due';
			case 'editor': return 'ed_initials';
			case 'reviewer': return 'u.last_name';
			case 'status': return 'ofra.status';
			case 'type': return 'ofr_type_name';
			case 'submission': return 'ofra.submission_id';
			default: return null;
		}
	}

	//
	// Private helper methods.
	//
	/**
	 * Retrieve all assignments matching the specified input parameters
	 * @param $objectId int (optional)
	 * @param $userId int (optional)
	 * @param $submissionId int (optional)
	 * @return DAOResultFactory
	 */
	function &_getAllInternally($objectId = null, $userId = null, $submissionId = null) {
		$sql = 'SELECT * FROM object_for_review_assignments';

		if ($objectId) {
			$conditions[] = 'object_id = ?';
			$params[] = (int) $objectId;
		}

		if ($userId) {
			$conditions[] = 'user_id = ?';
			$params[] = (int) $userId;
		}

		if ($submissionId) {
			$conditions[] = 'submission_id = ?';
			$params[] = (int) $submissionId;
		}

		if (count($conditions) > 0) {
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}

		$sql .= ' ORDER BY assignment_id';
		$result =& $this->retrieve($sql, $params);

		$assignments = array();
		while (!$result->EOF) {
			$assignments[] =& $this->_fromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		return $assignments;
	}

}

?>
