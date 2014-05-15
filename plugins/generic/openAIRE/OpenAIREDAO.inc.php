<?php

/**
 * @file plugins/generic/openAIRE/OpenAIREDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	 * @param $setIds array Objects ids that specify an OAI set, in this case only journal ID.
	 * @param $from int timestamp
	 * @param $until int timestamp
	 * @param $offset int
	 * @param $limit int
	 * @param $total int
	 * @param $funcName string
	 * @return array OAIRecord
	 */
	function &getOpenAIRERecordsOrIdentifiers($setIds, $from, $until, $offset, $limit, &$total, $funcName) {
		$records = array();

		$result =& $this->_getRecordsRecordSet($setIds, $from, $until, null);

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
