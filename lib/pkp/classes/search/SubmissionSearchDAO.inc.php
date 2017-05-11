<?php

/**
 * @file classes/search/SubmissionSearchDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSearchDAO
 * @ingroup search
 * @see SubmissionSearch
 *
 * @brief DAO class for submission search index.
 */

class SubmissionSearchDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Add a word to the keyword list (if it doesn't already exist).
	 * @param $keyword string
	 * @return int the keyword ID
	 */
	function insertKeyword($keyword) {
		static $submissionSearchKeywordIds = array();
		if (isset($submissionSearchKeywordIds[$keyword])) return $submissionSearchKeywordIds[$keyword];
		$result = $this->retrieve(
			'SELECT keyword_id FROM submission_search_keyword_list WHERE keyword_text = ?',
			$keyword
		);
		if($result->RecordCount() == 0) {
			$result->Close();
			if ($this->update(
				'INSERT INTO submission_search_keyword_list (keyword_text) VALUES (?)',
				$keyword,
				true,
				false
			)) {
				$keywordId = $this->_getInsertId('submission_search_keyword_list', 'keyword_id');
			} else {
				$keywordId = null; // Bug #2324
			}
		} else {
			$keywordId = $result->fields[0];
			$result->Close();
		}

		$submissionSearchKeywordIds[$keyword] = $keywordId;

		return $keywordId;
	}

	/**
	 * Delete all keywords for a submission.
	 * @param $submissionId int
	 * @param $type int optional
	 * @param $assocId int optional
	 */
	function deleteSubmissionKeywords($submissionId, $type = null, $assocId = null) {
		$sql = 'SELECT object_id FROM submission_search_objects WHERE submission_id = ?';
		$params = array((int) $submissionId);

		if (isset($type)) {
			$sql .= ' AND type = ?';
			$params[] = (int) $type;
		}

		if (isset($assocId)) {
			$sql .= ' AND assoc_id = ?';
			$params[] = (int) $assocId;
		}

		$result = $this->retrieve($sql, $params);
		while (!$result->EOF) {
			$objectId = $result->fields[0];
			$this->update('DELETE FROM submission_search_object_keywords WHERE object_id = ?', $objectId);
			$this->update('DELETE FROM submission_search_objects WHERE object_id = ?', $objectId);
			$result->MoveNext();
		}
		$result->Close();
	}

	/**
	 * Add a submission object to the index (if already exists, indexed keywords are cleared).
	 * @param $submissionId int
	 * @param $type int
	 * @param $assocId int
	 * @return int the object ID
	 */
	function insertObject($submissionId, $type, $assocId, $keepExisting = false) {
		$result = $this->retrieve(
			'SELECT object_id FROM submission_search_objects WHERE submission_id = ? AND type = ? AND assoc_id = ?',
			array((int) $submissionId, (int) $type, (int) $assocId)
		);
		if ($result->RecordCount() == 0) {
			$this->update(
				'INSERT INTO submission_search_objects (submission_id, type, assoc_id) VALUES (?, ?, ?)',
				array((int) $submissionId, (int) $type, (int) $assocId)
			);
			$objectId = $this->_getInsertId('submission_search_objects', 'object_id');

		} else {
			$objectId = $result->fields[0];
			$this->update(
				'DELETE FROM submission_search_object_keywords WHERE object_id = ?',
				(int) $objectId
			);
		}
		$result->Close();
		return $objectId;
	}

	/**
	 * Index an occurrence of a keyword in an object.
	 * @param $objectId int
	 * @param $keyword string
	 * @param $position int
	 * @return $keywordId
	 */
	function insertObjectKeyword($objectId, $keyword, $position) {
		$keywordId = $this->insertKeyword($keyword);
		if ($keywordId === null) return null; // Bug #2324
		$this->update(
			'INSERT INTO submission_search_object_keywords (object_id, keyword_id, pos) VALUES (?, ?, ?)',
			array((int) $objectId, (int) $keywordId, (int) $position)
		);
		return $keywordId;
	}

	/**
	 * Clear the search index.
	 */
	function clearIndex() {
		$this->update('DELETE FROM submission_search_object_keywords');
		$this->update('DELETE FROM submission_search_objects');
		$this->update('DELETE FROM submission_search_keyword_list');
		$this->setCacheDir(Config::getVar('files', 'files_dir') . '/_db');
		$dataSource = $this->getDataSource();
		$dataSource->CacheFlush();
	}
}

?>
