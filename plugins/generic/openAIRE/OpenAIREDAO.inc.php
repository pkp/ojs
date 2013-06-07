<?php

/**
 * @file plugins/generic/openAIRE/OpenAIREDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OpenAIREDAO
 * @ingroup plugins_generic_openAIRE
 *
 * @brief DAO operations for OpenAIRE.
 */

import('classes.oai.ojs.OAIDAO');


class OpenAIREDAO extends OAIDAO {

 	/**
	 * Constructor.
	 */
	function OpenAIREDAO() {
		parent::OAIDAO();
	}

	/**
	 * Set parent OAI object.
	 * @param JournalOAI
	 */
	function setOAI(&$oai) {
		$this->oai = $oai;
	}

	//
	// Records
	//

	/**
	 * Return set of OAI records matching specified parameters.
	 * @param $journalId int
	 * @parma $from int timestamp
	 * @parma $until int timestamp
	 * @param $offset int
	 * @param $limit int
	 * @param $total int
	 * @return array OAIRecord
	 */
	function &getOpenAIRERecords($journalId, $from, $until, $offset, $limit, &$total) {
		$records = array();

		$params = array();
		if (isset($journalId)) {
			array_push($params, (int) $journalId, (int) $journalId);
		}
		$result = $this->retrieve(
			'SELECT	pa.published_submission_id,
					pa.date_published,
					pa.seq,
					pa.access_status,
					COALESCE(st.date_deleted, a.last_modified) AS last_modified,
					COALESCE(a.submission_id, st.submission_id) AS submission_id,
					COALESCE(j.journal_id, st.journal_id) AS journal_id,
					COALESCE(st.section_id, s.section_id) AS section_id,
					i.issue_id,
					st.tombstone_id,
					st.set_spec
			FROM mutex m
			LEFT JOIN published_submissions pa ON (m.i=0)
			LEFT JOIN submissions a ON (a.submission_id = pa.submission_id' . (isset($journalId) ? ' AND a.journal_id = ?' : '') .')
			LEFT JOIN issues i ON (i.issue_id = pa.issue_id)
			LEFT JOIN sections s ON (s.section_id = a.section_id)
			LEFT JOIN journals j ON (j.journal_id = a.journal_id)
			LEFT JOIN submission_tombstones st ON (m.i = 1' . (isset($journalId) ? ' AND st.journal_id = ?' : '') .')
			WHERE ((s.section_id IS NOT NULL AND i.published = 1 AND j.enabled = 1 AND a.status <> ' . STATUS_ARCHIVED . ') OR st.submission_id IS NOT NULL)'
				. (isset($from) ? ' AND ((st.date_deleted IS NOT NULL AND st.date_deleted >= '. $this->datetimeToDB($from) .') OR (st.date_deleted IS NULL AND a.last_modified >= ' . $this->datetimeToDB($from) .'))' : '')
				. (isset($until) ? ' AND ((st.date_deleted IS NOT NULL AND st.date_deleted <= ' .$this->datetimeToDB($until) .') OR (st.date_deleted IS NULL AND a.last_modified <= ' . $this->datetimeToDB($until) .'))' : ''),
			$params
		);

		$total = $result->RecordCount();

		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row = $result->GetRowAssoc(false);
			if ($this->isOpenAIRERecord($row)) {
				$records[] = $this->_returnRecordFromRow($row);
			}
			$result->MoveNext();
		}

		$result->Close();
		return $records;
	}

	/**
	 * Return set of OAI identifiers matching specified parameters.
	 * @param $journalId int
	 * @parma $from int timestamp
	 * @parma $until int timestamp
	 * @param $offset int
	 * @param $limit int
	 * @param $total int
	 * @return array OAIIdentifier
	 */
	function &getOpenAIREIdentifiers($journalId, $from, $until, $offset, $limit, &$total) {
		$records = array();

		$params = array();
		if (isset($journalId)) {
			array_push($params, (int) $journalId, (int) $journalId);
		}
		$result = $this->retrieve(
			'SELECT	pa.published_submission_id,
					pa.date_published,
					pa.seq,
					pa.access_status,
					COALESCE(st.date_deleted, a.last_modified) AS last_modified,
					COALESCE(a.submission_id, st.submission_id) AS submission_id,
					COALESCE(j.journal_id, st.journal_id) AS journal_id,
					COALESCE(st.section_id, s.section_id) AS section_id,
					i.issue_id,
					st.tombstone_id,
					st.set_spec
			FROM mutex m
			LEFT JOIN published_submissions pa ON (m.i=0)
			LEFT JOIN submissions a ON (a.submission_id = pa.submission_id' . (isset($journalId) ? ' AND a.journal_id = ?' : '') .')
			LEFT JOIN issues i ON (i.issue_id = pa.issue_id)
			LEFT JOIN sections s ON (s.section_id = a.section_id)
			LEFT JOIN journals j ON (j.journal_id = a.journal_id)
			LEFT JOIN submission_tombstones st ON (m.i = 1' . (isset($journalId) ? ' AND st.journal_id = ?' : '') .')
			WHERE ((s.section_id IS NOT NULL AND i.published = 1 AND j.enabled = 1 AND a.status <> ' . STATUS_ARCHIVED . ') OR st.submission_id IS NOT NULL)'
				. (isset($from) ? ' AND ((st.date_deleted IS NOT NULL AND st.date_deleted >= '. $this->datetimeToDB($from) .') OR (st.date_deleted IS NULL AND a.last_modified >= ' . $this->datetimeToDB($from) .'))' : '')
				. (isset($until) ? ' AND ((st.date_deleted IS NOT NULL AND st.date_deleted <= ' .$this->datetimeToDB($until) .') OR (st.date_deleted IS NULL AND a.last_modified <= ' . $this->datetimeToDB($until) .'))' : ''),
			$params
		);

		$total = $result->RecordCount();

		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row = $result->GetRowAssoc(false);
			if ($this->isOpenAIRERecord($row)) {
				$records[] = $this->_returnIdentifierFromRow($row);
			}
			$result->MoveNext();
		}

		$result->Close();
		return $records;
	}

	/**
	 * Check if it's an OpenAIRE record, if it contains projectID.
	 * @param $row array of database fields
	 * @return boolean
	 */
	function isOpenAIRERecord($row) {
		if (!isset($row['tombstone_id'])) {
			$params = array('projectID', (int) $row['submission_id']);
			$result = $this->retrieve(
				'SELECT COUNT(*) FROM submission_settings WHERE setting_name = ? AND setting_value IS NOT NULL AND setting_value <> \'\' AND submission_id = ?',
				$params
			);
			$returner = (isset($result->fields[0]) && $result->fields[0] == 1) ? true : false;
			$result->Close();
			return $returner;
		} else {
			$submissionTombstoneSettingsDao = DAORegistry::getDAO('SubmissionTombstoneSettingsDAO');
			return $submissionTombstoneSettingsDao->getSetting($row['tombstone_id'], 'openaire');
		}
	}

	/**
	 * Check if it's an OpenAIRE article, if it contains projectID.
	 * @param $articleId int
	 * @return boolean
	 */
	function isOpenAIREArticle($articleId) {
		$params = array('projectID', (int) $articleId);
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM submission_settings WHERE setting_name = ? AND setting_value IS NOT NULL AND setting_value <> \'\' AND submission_id = ?',
			$params
		);
		$returner = (isset($result->fields[0]) && $result->fields[0] == 1) ? true : false;
		$result->Close();
		return $returner;
	}
}

?>
