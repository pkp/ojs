<?php

/**
 * @file classes/submission/SubmissionSubjectDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubjectDAO
 * @ingroup submission
 * @see Submission
 *
 * @brief Operations for retrieving and modifying a submission's assigned subjects
 */

import('lib.pkp.classes.controlledVocab.ControlledVocabDAO');

define('CONTROLLED_VOCAB_SUBMISSION_SUBJECT', 'submissionSubject');

class SubmissionSubjectDAO extends ControlledVocabDAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Build/fetch and return a controlled vocabulary for subjects.
	 * @param $submissionId int
	 * @return ControlledVocab
	 */
	function build($submissionId) {
		// may return an array of ControlledVocabs
		return parent::build(CONTROLLED_VOCAB_SUBMISSION_SUBJECT, ASSOC_TYPE_SUBMISSION, $submissionId);
	}

	/**
	 * Get the list of localized additional fields to store.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('submissionSubject');
	}

	/**
	 * Get Subjects for a submission.
	 * @param $submissionId int
	 * @param $locales array
	 * @return array
	 */
	function getSubjects($submissionId, $locales) {
		$returner = array();
		$submissionSubjectEntryDao = DAORegistry::getDAO('SubmissionSubjectEntryDAO');
		foreach ($locales as $locale) {
			$returner[$locale] = array();
			$subjects = $this->build($submissionId);
			$submissionSubjects = $submissionSubjectEntryDao->getByControlledVocabId($subjects->getId());

			while ($subject = $submissionSubjects->next()) {
				$subject = $subject->getSubject();
				if (array_key_exists($locale, $subject)) { // quiets PHP when there are no Subjects for a given locale
					$returner[$locale][] = $subject[$locale];
				}
			}
		}
		return $returner;
	}

	/**
	 * Get an array of all of the submission's Subjects
	 * @return array
	 */
	function getAllUniqueSubjects() {
		$subjects = array();

		$result = $this->retrieve(
			'SELECT DISTINCT setting_value FROM controlled_vocab_entry_settings WHERE setting_name = ?', CONTROLLED_VOCAB_SUBMISSION_SUBJECT
		);

		while (!$result->EOF) {
			$subjects[] = $result->fields[0];
			$result->MoveNext();
		}

		$result->Close();
		return $subjects;
	}

	/**
	 * Get an array of submissionIds that have a given subject
	 * @param $subject string
	 * @return array
	 */
	function getSubmissionIdsBySubject($subject) {
		$result = $this->retrieve(
			'SELECT assoc_id
			 FROM controlled_vocabs cv
			 LEFT JOIN controlled_vocab_entries cve ON cv.controlled_vocab_id = cve.controlled_vocab_id
			 INNER JOIN controlled_vocab_entry_settings cves ON cve.controlled_vocab_entry_id = cves.controlled_vocab_entry_id
			 WHERE cves.setting_name = ? AND cves.setting_value = ?',
			array(CONTROLLED_VOCAB_SUBMISSION_SUBJECT, $subject)
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
	 * Add an array of subjects
	 * @param $subjects array
	 * @param $submissionId int
	 * @param $deleteFirst boolean
	 * @return int
	 */
	function insertSubjects($subjects, $submissionId, $deleteFirst = true) {
		$subjectDao = DAORegistry::getDAO('SubmissionSubjectDAO');
		$submissionSubjectEntryDao = DAORegistry::getDAO('SubmissionSubjectEntryDAO');
		$currentSubjects = $this->build($submissionId);

		if ($deleteFirst) {
			$existingEntries = $subjectDao->enumerate($currentSubjects->getId(), CONTROLLED_VOCAB_SUBMISSION_SUBJECT);

			foreach ($existingEntries as $id => $entry) {
				$entry = trim($entry);
				$submissionSubjectEntryDao->deleteObjectById($id);
			}
		}
		if (is_array($subjects)) { // localized, array of arrays

			foreach ($subjects as $locale => $list) {
				if (is_array($list)) {
					$list = array_unique($list); // Remove any duplicate Subjects
					$i = 1;
					foreach ($list as $subject) {
						$subjectEntry = $submissionSubjectEntryDao->newDataObject();
						$subjectEntry->setControlledVocabId($currentSubjects->getID());
						$subjectEntry->setSubject(urldecode($subject), $locale);
						$subjectEntry->setSequence($i);
						$i++;
						$submissionSubjectEntryDao->insertObject($subjectEntry);
					}
				}
			}
		}
	}
}

?>
