<?php

/**
 * @file plugins/generic/openAIRE/OpenAIREDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
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

		$params = array('projectID');
		if (isset($journalId)) {
			array_push($params, (int) $journalId);
		}
		$result =& $this->retrieve(
			'SELECT	pa.*,
				a.last_modified,
				a.article_id,
				j.journal_id,
				s.section_id,
				i.issue_id
			FROM	published_articles pa,
				issues i,
				journals j,
				articles a,
				article_settings api,
				sections s
			WHERE	pa.article_id = a.article_id
				AND api.article_id = a.article_id AND api.setting_name = ? AND api.setting_value IS NOT NULL AND api.setting_value <> \'\'
				AND s.section_id = a.section_id
				AND j.journal_id = a.journal_id
				AND pa.issue_id = i.issue_id
				AND i.published = 1'
				. (isset($journalId) ? ' AND a.journal_id = ?' : '')
				. (isset($from) ? ' AND a.last_modified >= ' . $this->datetimeToDB($from) : '')
				. (isset($until) ? ' AND a.last_modified <= ' . $this->datetimeToDB($until) : '')
				. ' ORDER BY journal_id',
			$params
		);

		$total = $result->RecordCount();

		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row =& $result->GetRowAssoc(false);
			$records[] =& $this->_returnRecordFromRow($row);
			$result->moveNext();
		}

		$result->Close();
		unset($result);

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

		$params = array('projectID');
		if (isset($journalId)) {
			array_push($params, (int) $journalId);
		}
		$result =& $this->retrieve(
			'SELECT	pa.article_id,
				a.last_modified,
				j.journal_id,
				s.section_id
			FROM	published_articles pa,
				issues i,
				journals j,
				articles a,
				article_settings api,
				sections s
			WHERE	pa.article_id = a.article_id
				AND api.article_id = a.article_id AND api.setting_name = ? AND api.setting_value IS NOT NULL AND api.setting_value <> \'\'
				AND s.section_id = a.section_id
				AND j.journal_id = a.journal_id
				AND pa.issue_id = i.issue_id AND i.published = 1'
				. (isset($journalId) ? ' AND a.journal_id = ?' : '')
				. (isset($from) ? ' AND a.last_modified >= ' . $this->datetimeToDB($from) : '')
				. (isset($until) ? ' AND a.last_modified <= ' . $this->datetimeToDB($until) : '')
				. ' ORDER BY journal_id',
			$params
		);

		$total = $result->RecordCount();

		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row =& $result->GetRowAssoc(false);
			$records[] =& $this->_returnIdentifierFromRow($row);
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $records;
	}

	/**
	 * Check if it's an OpenAIRE article, if it contains projectID.
	 * @param $articleId int
	 * @return boolean
	 */
	function isOpenAIREArticle($articleId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM article_settings WHERE setting_name = "projectID" AND setting_value IS NOT NULL AND setting_value <> \'\' AND article_id = ?',
			array($articleId)
		);
		$returner = (isset($result->fields[0]) && $result->fields[0] == 1) ? true : false;

		$result->Close();
		unset($result);

		return $returner;		
	}
		
}

?>
