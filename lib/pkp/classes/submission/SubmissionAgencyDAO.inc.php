<?php

/**
 * @file classes/submission/SubmissionAgencyDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionAgencyDAO
 * @ingroup submission
 * @see Submission
 *
 * @brief Operations for retrieving and modifying a submission's assigned agencies
 */

import('lib.pkp.classes.controlledVocab.ControlledVocabDAO');

define('CONTROLLED_VOCAB_SUBMISSION_AGENCY', 'submissionAgency');

class SubmissionAgencyDAO extends ControlledVocabDAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Build/fetch and return a controlled vocabulary for agencies.
	 * @param $submissionId int
	 * @return ControlledVocab
	 */
	function build($submissionId) {
		return parent::build(CONTROLLED_VOCAB_SUBMISSION_AGENCY, ASSOC_TYPE_SUBMISSION, $submissionId);
	}

	/**
	 * Get the list of localized additional fields to store.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('submissionAgency');
	}

	/**
	 * Get agencies for a specified submission ID.
	 * @param $submissionId int
	 * @param $locales array
	 * @return array
	 */
	function getAgencies($submissionId, $locales) {

		$returner = array();
		foreach ($locales as $locale) {
			$returner[$locale] = array();
			$agencies = $this->build($submissionId);
			$submissionAgencyEntryDao = DAORegistry::getDAO('SubmissionAgencyEntryDAO');
			$submissionAgencies = $submissionAgencyEntryDao->getByControlledVocabId($agencies->getId());

			while ($agency = $submissionAgencies->next()) {
				$agency = $agency->getAgency();
				if (array_key_exists($locale, $agency)) { // quiets PHP when there are no agencies for a given locale
					$returner[$locale][] = $agency[$locale];
				}
			}
		}
		return $returner;
	}

	/**
	 * Get an array of all of the submission's agencies
	 * @return array
	 */
	function getAllUniqueAgencies() {
		$agencies = array();

		$result = $this->retrieve(
			'SELECT DISTINCT setting_value FROM controlled_vocab_entry_settings WHERE setting_name = ?', CONTROLLED_VOCAB_SUBMISSION_AGENCY
		);

		while (!$result->EOF) {
			$agencies[] = $result->fields[0];
			$result->MoveNext();
		}

		$result->Close();
		return $agencies;
	}

	/**
	 * Get an array of submissionIds that have a given agency
	 * @param $agency string
	 * @return array
	 */
	function getSubmissionIdsByAgency($agency) {
		$result = $this->retrieve(
			'SELECT assoc_id
			 FROM controlled_vocabs cv
			 LEFT JOIN controlled_vocab_entries cve ON cv.controlled_vocab_id = cve.controlled_vocab_id
			 INNER JOIN controlled_vocab_entry_settings cves ON cve.controlled_vocab_entry_id = cves.controlled_vocab_entry_id
			 WHERE cves.setting_name = ? AND cves.setting_value = ?',
			array(CONTROLLED_VOCAB_SUBMISSION_AGENCY, $agency)
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
	 * Add an array of agencies
	 * @param $agencies array List of agencies.
	 * @param $submissionId int Submission ID.
	 * @param $deleteFirst boolean True iff existing agencies should be removed first.
	 * @return int
	 */
	function insertAgencies($agencies, $submissionId, $deleteFirst = true) {
		$agencyDao = DAORegistry::getDAO('SubmissionAgencyDAO');
		$submissionAgencyEntryDao = DAORegistry::getDAO('SubmissionAgencyEntryDAO');
		$currentAgencies = $this->build($submissionId);

		if ($deleteFirst) {
			$existingEntries = $agencyDao->enumerate($currentAgencies->getId(), CONTROLLED_VOCAB_SUBMISSION_AGENCY);

			foreach ($existingEntries as $id => $entry) {
				$entry = trim($entry);
				$submissionAgencyEntryDao->deleteObjectById($id);
			}
		}
		if (is_array($agencies)) { // localized, array of arrays

			foreach ($agencies as $locale => $list) {
				if (is_array($list)) {
					$list = array_unique($list); // Remove any duplicate keywords
					$i = 1;
					foreach ($list as $agency) {
						$agencyEntry = $submissionAgencyEntryDao->newDataObject();
						$agencyEntry->setControlledVocabId($currentAgencies->getID());
						$agencyEntry->setAgency(urldecode($agency), $locale);
						$agencyEntry->setSequence($i);
						$i++;
						$submissionAgencyEntryDao->insertObject($agencyEntry);
					}
				}
			}
		}
	}
}

?>
