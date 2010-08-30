<?php
/**
 * @file pidResourceDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class pidResourceDAO
 * @ingroup plugins_generic_pid
 *
 * @brief enables PID functionality.
 */

class pidResourceDAO extends DAO {

	/**
	 * Get PID for a resource.
	 * @param $assoc_id int
	 * @param $assoc_type int
	 * @return int
	 */
	function getResourcePid($assoc_id, $assoc_type) {
		$result = &$this->retrieve(
		'SELECT resource_pid FROM pid_resources WHERE assoc_id = ? AND assoc_type = ?', array($assoc_id, $assoc_type)
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Set resource Persistent URL.
	 * @param $resourcePid str
	 * @param $resourcePurl str
	 *
	 */
	function setResourcePurl($resourcePid, $resourcePurl) {
		$result = &$this->update(
		'UPDATE pid_resources SET resource_purl = ? WHERE resource_pid = ?', array($resourcePurl, $resourcePid)
		);
	}

	/**
	 * Get resource Persistent URL.
	 * @param $resourcePid str
	 * @return str
	 */
	function getResourcePurl($resourcePid) {
		$result = &$this->retrieve(
		'SELECT resource_purl FROM pid_resources WHERE resource_pid = ?', $resourcePid
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Set Resource PID.
	 * @param $resourcePid str
	 * @param $assoc_id int
	 * @param $assoc_type int
	 *
	 * @return int
	 */
	function setResourcePid($resourcePid, $assoc_id, $assoc_type) {
		$this->update(
		"INSERT INTO pid_resources (resource_pid, assoc_id, assoc_type) VALUES (?, ?, ?)", array($resourcePid, $assoc_id, $assoc_type)
		);
	}
	
	/** 
	 * Retreive Article IDs without PID for a Journal 
	 * 
	 * @param $journalId
	 * @param $assoc_type
	 *
	 * @return array 
	 */
	function getJournalArticleIDsWithoutPid($journalId, $assoc_type){
		$result = $this->retrieve(
			"SELECT a.article_id FROM articles AS a
			INNER JOIN published_articles AS pa ON a.article_id = pa.article_id
			LEFT JOIN pid_resources AS pr ON a.article_id = pr.assoc_id AND pr.assoc_type = ? 
			WHERE a.journal_id = ? AND pr.assoc_id IS NULL", array($assoc_type, $journalId)
		);
		
		$returner = array();
		if ($result->RecordCount() != 0) {
			while (!$result->EOF) {
				$row =& $result->GetRowAssoc(false);
				$returner[] = $row['article_id'];
				$result->MoveNext();
			}
		}
		$result->Close();
		unset($result);

		return $returner;
	}
}