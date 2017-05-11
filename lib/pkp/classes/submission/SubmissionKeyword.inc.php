<?php

/**
 * @file classes/submission/SubmissionKeyword.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionKeyword
 * @ingroup submission
 * @see SubmissionKeywordEntryDAO
 *
 * @brief Basic class describing a submission keyword
 */

import('lib.pkp.classes.controlledVocab.ControlledVocabEntry');

class SubmissionKeyword extends ControlledVocabEntry {
	//
	// Get/set methods
	//

	/**
	 * Get the keyword
	 * @return string
	 */
	function getKeyword() {
		return $this->getData('submissionKeyword');
	}

	/**
	 * Set the keyword text
	 * @param keyword string
	 * @param locale string
	 */
	function setKeyword($keyword, $locale) {
		$this->setData('submissionKeyword', $keyword, $locale);
	}

	function getLocaleMetadataFieldNames() {
		return array('submissionKeyword');
	}
}
?>
