<?php

/**
 * @file classes/submission/SubmissionKeywordDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionKeywordDAO
 * @ingroup submission
 * @see Submission
 *
 * @brief Operations for retrieving and modifying a submission's assigned keywords
 */

import('lib.pkp.classes.controlledVocab.ControlledVocabDAO');

define('CONTROLLED_VOCAB_SUBMISSION_KEYWORD', 'submissionKeyword');

class SubmissionKeywordDAO extends ControlledVocabDAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Build/fetch and return a controlled vocabulary for keywords.
	 * @param $submissionId int
	 * @return ControlledVocab
	 */
	function build($submissionId) {
		// may return an array of ControlledVocabs
		return parent::build(CONTROLLED_VOCAB_SUBMISSION_KEYWORD, ASSOC_TYPE_SUBMISSION, $submissionId);
	}

	/**
	 * Get the list of localized additional fields to store.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('submissionKeyword');
	}

	/**
	 * Get keywords for a submission.
	 * @param $submissionId int
	 * @param $locales array
	 * @return array
	 */
	function getKeywords($submissionId, $locales) {

		$returner = array();
		foreach ($locales as $locale) {
			$returner[$locale] = array();
			$keywords = $this->build($submissionId);
			$submissionKeywordEntryDao = DAORegistry::getDAO('SubmissionKeywordEntryDAO');
			$submissionKeywords = $submissionKeywordEntryDao->getByControlledVocabId($keywords->getId());

			while ($keyword = $submissionKeywords->next()) {
				$keyword = $keyword->getKeyword();
				if (array_key_exists($locale, $keyword)) { // quiets PHP when there are no keywords for a given locale
					$returner[$locale][] = $keyword[$locale];
				}
			}
		}
		return $returner;
	}

	/**
	 * Get an array of all of the submission's keywords
	 * @return array
	 */
	function getAllUniqueKeywords() {
		$keywords = array();

		$result = $this->retrieve(
			'SELECT DISTINCT setting_value FROM controlled_vocab_entry_settings WHERE setting_name = ?', CONTROLLED_VOCAB_SUBMISSION_KEYWORD
		);

		while (!$result->EOF) {
			$keywords[] = $result->fields[0];
			$result->MoveNext();
		}

		$result->Close();
		return $keywords;
	}

	/**
	 * Get an array of submissionIds that have a given keyword
	 * @param $content string
	 * @return array
	 */
	function getSubmissionIdsByKeyword($keyword) {
		$result = $this->retrieve(
			'SELECT assoc_id
			 FROM controlled_vocabs cv
			 LEFT JOIN controlled_vocab_entries cve ON cv.controlled_vocab_id = cve.controlled_vocab_id
			 INNER JOIN controlled_vocab_entry_settings cves ON cve.controlled_vocab_entry_id = cves.controlled_vocab_entry_id
			 WHERE cves.setting_name = ? AND cves.setting_value = ?',
			array(CONTROLLED_VOCAB_SUBMISSION_KEYWORD, $keyword)
		);

		$returner = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[] = $row['assoc_id'];
			$result->MoveNext();
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Add an array of keywords
	 * @param $keywords array
	 * @param $submissionId int
	 * @param $deleteFirst boolean
	 * @return int
	 */
	function insertKeywords($keywords, $submissionId, $deleteFirst = true) {
		$keywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$submissionKeywordEntryDao = DAORegistry::getDAO('SubmissionKeywordEntryDAO');
		$currentKeywords = $this->build($submissionId);

		if ($deleteFirst) {
			$existingEntries = $keywordDao->enumerate($currentKeywords->getId(), CONTROLLED_VOCAB_SUBMISSION_KEYWORD);

			foreach ($existingEntries as $id => $entry) {
				$entry = trim($entry);
				$submissionKeywordEntryDao->deleteObjectById($id);
			}
		}
		if (is_array($keywords)) { // localized, array of arrays

			foreach ($keywords as $locale => $list) {
				if (is_array($list)) {
					$list = array_unique($list); // Remove any duplicate keywords
					$i = 1;
					foreach ($list as $keyword) {
						$keywordEntry = $submissionKeywordEntryDao->newDataObject();
						$keywordEntry->setControlledVocabId($currentKeywords->getID());
						$keywordEntry->setKeyword(urldecode($keyword), $locale);
						$keywordEntry->setSequence($i);
						$i++;
						$submissionKeywordEntryDao->insertObject($keywordEntry);
					}
				}
			}
		}
	}
}

?>
