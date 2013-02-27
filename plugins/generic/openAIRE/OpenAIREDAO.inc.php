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
	 * Return set of OAI records or identifiers matching specified parameters.
	 * @param $journalId int
	 * @parma $from int timestamp
	 * @parma $until int timestamp
	 * @param $offset int
	 * @param $limit int
	 * @param $total int
	 * @param $funcName string
	 * @return array OAIRecord
	 */
	function &getOpenAIRERecordsOrIdentifiers($journalId, $from, $until, $offset, $limit, &$total, $funcName) {
		$records = array();

		$params = array();
		if (isset($journalId)) {
			array_push($params, (int) $journalId, (int) $journalId);
		}
		$result =& $this->retrieve(
			'SELECT	pa.published_article_id,
					pa.date_published,
					pa.seq,
					pa.views,
					pa.access_status,
					COALESCE(dot.date_deleted, a.last_modified) AS last_modified,
					COALESCE(a.article_id, dot.data_object_id) AS article_id,
					COALESCE(j.journal_id, tsoj.assoc_id) AS journal_id,
					COALESCE(tsos.assoc_id, s.section_id) AS section_id,
					i.issue_id,
					dot.tombstone_id,
					dot.set_spec
			FROM mutex m
			LEFT JOIN published_articles pa ON (m.i=0)
			LEFT JOIN articles a ON (a.article_id = pa.article_id' . (isset($journalId) ? ' AND a.journal_id = ?' : '') .')
			LEFT JOIN issues i ON (i.issue_id = pa.issue_id)
			LEFT JOIN sections s ON (s.section_id = a.section_id)
			LEFT JOIN journals j ON (j.journal_id = a.journal_id)
			LEFT JOIN data_object_tombstones dot ON (m.i = 1)
			LEFT JOIN data_object_tombstone_oai_set_objects tsoj ON ' . (isset($journalId) ? '(tsoj.tombstone_id = dot.tombstone_id AND tsoj.assoc_type = ' . ASSOC_TYPE_JOURNAL . ' AND tsoj.assoc_id = ?)' : 'tsoj.assoc_id = null') .'
			LEFT JOIN data_object_tombstone_oai_set_objects tsos ON (tsos.tombstone_id = dot.tombstone_id AND tsos.assoc_type = ' . ASSOC_TYPE_SECTION . ')
			WHERE ((s.section_id IS NOT NULL AND i.published = 1 AND j.enabled = 1 AND a.status <> ' . STATUS_ARCHIVED . ') OR dot.data_object_id IS NOT NULL)'
				. (isset($from) ? ' AND ((dot.date_deleted IS NOT NULL AND dot.date_deleted >= '. $this->datetimeToDB($from) .') OR (dot.date_deleted IS NULL AND a.last_modified >= ' . $this->datetimeToDB($from) .'))' : '')
				. (isset($until) ? ' AND ((dot.date_deleted IS NOT NULL AND dot.date_deleted <= ' .$this->datetimeToDB($until) .') OR (dot.date_deleted IS NULL AND a.last_modified <= ' . $this->datetimeToDB($until) .'))' : ''),
			$params
		);

		$total = $result->RecordCount();

		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row =& $result->GetRowAssoc(false);
			if ($this->isOpenAIRERecord($row)) {
				$records[] =& $this->$funcName($row);
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $records;
	}

	/**
	 * Check if it's an OpenAIRE record, if it contains projectID.
	 * @param $row array of database fields
	 * @return boolean
	 */
	function isOpenAIRERecord($row) {
		if (!isset($row['tombstone_id'])) {
			$params = array('projectID', (int) $row['article_id']);
			$result =& $this->retrieve(
				'SELECT COUNT(*) FROM article_settings WHERE setting_name = ? AND setting_value IS NOT NULL AND setting_value <> \'\' AND article_id = ?',
				$params
			);
			$returner = (isset($result->fields[0]) && $result->fields[0] == 1) ? true : false;
			$result->Close();
			unset($result);

			return $returner;
		} else {
			$dataObjectTombstoneSettingsDao =& DAORegistry::getDAO('DataObjectTombstoneSettingsDAO');
			return $dataObjectTombstoneSettingsDao->getSetting($row['tombstone_id'], 'openaire');
		}
	}

	/**
	 * Check if it's an OpenAIRE article, if it contains projectID.
	 * @param $articleId int
	 * @return boolean
	 */
	function isOpenAIREArticle($articleId) {
		$params = array('projectID', (int) $articleId);
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM article_settings WHERE setting_name = ? AND setting_value IS NOT NULL AND setting_value <> \'\' AND article_id = ?',
			$params
		);
		$returner = (isset($result->fields[0]) && $result->fields[0] == 1) ? true : false;
		$result->Close();
		unset($result);

		return $returner;
	}


}

?>
