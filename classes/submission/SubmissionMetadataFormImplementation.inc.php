<?php

/**
 * @file classes/submission/SubmissionMetadataFormImplementation.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionMetadataFormImplementation
 * @ingroup submission
 *
 * @brief This can be used by other forms that want to
 * implement submission metadata data and form operations.
 */

import('lib.pkp.classes.submission.PKPSubmissionMetadataFormImplementation');

class SubmissionMetadataFormImplementation extends PKPSubmissionMetadataFormImplementation {
	/**
	 * Constructor.
	 * @param $parentForm Form A form that can use this form.
	 */
	function SubmissionMetadataFormImplementation($parentForm = null) {
		parent::PKPSubmissionMetadataFormImplementation($parentForm);
	}

	/**
	 * @copydoc PKPSubmissionMetadataFormImplementation::addChecks
	 */
	function addChecks($submission) {
		parent::addChecks($submission);

		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($submission->getSectionId());
		if (!$section->getAbstractsNotRequired()) {
			$this->_parentForm->addCheck(new FormValidatorLocale($this->_parentForm, 'abstract', 'required', 'submission.submit.form.abstractRequired'));
		}
	}
}

?>
