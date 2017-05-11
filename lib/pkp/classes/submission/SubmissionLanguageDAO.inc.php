<?php

/**
 * @file classes/submission/SubmissionLanguageDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionLanguageDAO
 * @ingroup submission
 * @see Submission
 *
 * @brief Operations for retrieving and modifying a submission's assigned languages
 */

import('lib.pkp.classes.controlledVocab.ControlledVocabDAO');

define('CONTROLLED_VOCAB_SUBMISSION_LANGUAGE', 'submissionLanguage');

class SubmissionLanguageDAO extends ControlledVocabDAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Build/fetch and return a controlled vocabulary for languages.
	 * @param $submissionId int
	 * @return ControlledVocab
	 */
	function build($submissionId) {
		// may return an array of ControlledVocabs
		return parent::build(CONTROLLED_VOCAB_SUBMISSION_LANGUAGE, ASSOC_TYPE_SUBMISSION, $submissionId);
	}

	/**
	 * Get the list of localized additional fields to store.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('submissionLanguage');
	}

	/**
	 * Get Languages for a submission.
	 * @param $submissionId int
	 * @param $locales array
	 * @return array
	 */
	function getLanguages($submissionId, $locales) {

		$returner = array();
		foreach ($locales as $locale) {
			$returner[$locale] = array();
			$languages = $this->build($submissionId);
			$submissionLanguageEntryDao = DAORegistry::getDAO('SubmissionLanguageEntryDAO');
			$submissionLanguages = $submissionLanguageEntryDao->getByControlledVocabId($languages->getId());

			while ($language = $submissionLanguages->next()) {
				$language = $language->getLanguage();
				if (array_key_exists($locale, $language)) { // quiets PHP when there are no Languages for a given locale
					$returner[$locale][] = $language[$locale];
				}
			}
		}
		return $returner;
	}

	/**
	 * Get an array of all of the submission's Languages
	 * @return array
	 */
	function getAllUniqueLanguages() {
		$languages = array();

		$result = $this->retrieve(
			'SELECT DISTINCT setting_value FROM controlled_vocab_entry_settings WHERE setting_name = ?', CONTROLLED_VOCAB_SUBMISSION_LANGUAGE
		);

		while (!$result->EOF) {
			$languages[] = $result->fields[0];
			$result->MoveNext();
		}

		$result->Close();
		return $languages;
	}

	/**
	 * Get an array of submissionIds that have a given language
	 * @param $language string
	 * @return array
	 */
	function getSubmissionIdsByLanguage($language) {
		$result = $this->retrieve(
			'SELECT assoc_id
			 FROM controlled_vocabs cv
			 LEFT JOIN controlled_vocab_entries cve ON cv.controlled_vocab_id = cve.controlled_vocab_id
			 INNER JOIN controlled_vocab_entry_settings cves ON cve.controlled_vocab_entry_id = cves.controlled_vocab_entry_id
			 WHERE cves.setting_name = ? AND cves.setting_value = ?',
			array(CONTROLLED_VOCAB_SUBMISSION_LANGUAGE, $language)
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
	 * Add an array of languages
	 * @param $languages array
	 * @param $submissionId int
	 * @param $deleteFirst boolean
	 * @return int
	 */
	function insertLanguages($languages, $submissionId, $deleteFirst = true) {
		$languageDao = DAORegistry::getDAO('SubmissionLanguageDAO');
		$submissionLanguageEntryDao = DAORegistry::getDAO('SubmissionLanguageEntryDAO');
		$currentLanguages = $this->build($submissionId);

		if ($deleteFirst) {
			$existingEntries = $languageDao->enumerate($currentLanguages->getId(), CONTROLLED_VOCAB_SUBMISSION_LANGUAGE);

			foreach ($existingEntries as $id => $entry) {
				$entry = trim($entry);
				$submissionLanguageEntryDao->deleteObjectById($id);
			}
		}
		if (is_array($languages)) { // localized, array of arrays

			foreach ($languages as $locale => $list) {
				if (is_array($list)) {
					$list = array_unique($list); // Remove any duplicate Languages
					$i = 1;
					foreach ($list as $language) {
						$languageEntry = $submissionLanguageEntryDao->newDataObject();
						$languageEntry->setControlledVocabId($currentLanguages->getID());
						$languageEntry->setLanguage(urldecode($language), $locale);
						$languageEntry->setSequence($i);
						$i++;
						$submissionLanguageEntryDao->insertObject($languageEntry);
					}
				}
			}
		}
	}
}

?>
