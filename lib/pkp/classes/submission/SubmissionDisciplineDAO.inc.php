<?php

/**
 * @file classes/submission/SubmissionDisciplineDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionDisciplineDAO
 * @ingroup submission
 * @see Submission
 *
 * @brief Operations for retrieving and modifying a submission's assigned
 * disciplines
 */

import('lib.pkp.classes.controlledVocab.ControlledVocabDAO');

define('CONTROLLED_VOCAB_SUBMISSION_DISCIPLINE', 'submissionDiscipline');

class SubmissionDisciplineDAO extends ControlledVocabDAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Build/fetch a submission discipline controlled vocabulary.
	 * @pararm $submissionId int
	 * @return ControlledVocabulary
	 */
	function build($submissionId) {
		return parent::build(CONTROLLED_VOCAB_SUBMISSION_DISCIPLINE, ASSOC_TYPE_SUBMISSION, $submissionId);
	}

	/**
	 * Get the list of localized additional fields to store.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('submissionDiscipline');
	}

	/**
	 * Get disciplines for a submission.
	 * @param $submissionId int
	 * @param $locales array
	 * @return array
	 */
	function getDisciplines($submissionId, $locales) {

		$returner = array();

		foreach ($locales as $locale) {

			$returner[$locale] = array();
			$disciplines = $this->build($submissionId);
			$submissionDisciplineEntryDao = DAORegistry::getDAO('SubmissionDisciplineEntryDAO');
			$submissionDisciplines = $submissionDisciplineEntryDao->getByControlledVocabId($disciplines->getId());

			while ($discipline = $submissionDisciplines->next()) {
				$discipline = $discipline->getDiscipline();
				if (array_key_exists($locale, $discipline)) { // quiets PHP when there are no disciplines for a given locale
					$returner[$locale][] = $discipline[$locale];
				}
			}
		}
		return $returner;
	}

	/**
	 * Get an array of all of the submission's disciplines
	 * @return array
	 */
	function getAllUniqueDisciplines() {
		$disciplines = array();

		$result = $this->retrieve(
			'SELECT DISTINCT setting_value FROM controlled_vocab_entry_settings WHERE setting_name = ?', CONTROLLED_VOCAB_SUBMISSION_DISCIPLINE
		);

		while (!$result->EOF) {
			$disciplines[] = $result->fields[0];
			$result->MoveNext();
		}

		$result->Close();
		return $disciplines;
	}

	/**
	 * Get an array of submissionIds that have a given discipline
	 * @param $content string
	 * @return array
	 */
	function getSubmissionIdsByDiscipline($discipline) {
		$result = $this->retrieve(
			'SELECT assoc_id
			 FROM controlled_vocabs cv
			 LEFT JOIN controlled_vocab_entries cve ON cv.controlled_vocab_id = cve.controlled_vocab_id
			 INNER JOIN controlled_vocab_entry_settings cves ON cve.controlled_vocab_entry_id = cves.controlled_vocab_entry_id
			 WHERE cves.setting_name = ? AND cves.setting_value = ?',
			array(CONTROLLED_VOCAB_SUBMISSION_DISCIPLINE, $discipline)
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
	 * Add an array of disciplines
	 * @param $disciplines array
	 * @param $submissionId int
	 * @param $deleteFirst boolean
	 * @return int
	 */
	function insertDisciplines($disciplines, $submissionId, $deleteFirst = true) {
		$disciplineDao = DAORegistry::getDAO('SubmissionDisciplineDAO');
		$submissionDisciplineEntryDao = DAORegistry::getDAO('SubmissionDisciplineEntryDAO');
		$currentDisciplines = $this->build($submissionId);

		if ($deleteFirst) {
			$existingEntries = $disciplineDao->enumerate($currentDisciplines->getId(), CONTROLLED_VOCAB_SUBMISSION_DISCIPLINE);

			foreach ($existingEntries as $id => $entry) {
				$entry = trim($entry);
				$submissionDisciplineEntryDao->deleteObjectById($id);
			}
		}
		if (is_array($disciplines)) { // localized, array of arrays

			foreach ($disciplines as $locale => $list) {
				if (is_array($list)) {
					$list = array_unique($list); // Remove any duplicate keywords
					$i = 1;
					foreach ($list as $discipline) {
						$disciplineEntry = $submissionDisciplineEntryDao->newDataObject();
						$disciplineEntry->setControlledVocabId($currentDisciplines->getID());
						$disciplineEntry->setDiscipline(urldecode($discipline), $locale);
						$disciplineEntry->setSequence($i);
						$i++;
						$submissionDisciplineEntryDao->insertObject($disciplineEntry);
					}
				}
			}
		}
	}
}

?>
