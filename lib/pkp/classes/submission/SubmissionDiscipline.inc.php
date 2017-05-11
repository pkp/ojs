<?php

/**
 * @file classes/submission/SubmissionDiscipline.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionDiscipline
 * @ingroup submission
 * @see SubmissionDisciplineEntryDAO
 *
 * @brief Basic class describing a submission discipline
 */


import('lib.pkp.classes.controlledVocab.ControlledVocabEntry');

class SubmissionDiscipline extends ControlledVocabEntry {
	//
	// Get/set methods
	//

	/**
	 * Get the discipline
	 * @return string
	 */
	function getDiscipline() {
		return $this->getData('submissionDiscipline');
	}

	/**
	 * Set the discipline text
	 * @param discipline string
	 * @param locale string
	 */
	function setDiscipline($discipline, $locale) {
		$this->setData('submissionDiscipline', $discipline, $locale);
	}

	function getLocaleMetadataFieldNames() {
		return array('submissionDiscipline');
	}
}
?>
