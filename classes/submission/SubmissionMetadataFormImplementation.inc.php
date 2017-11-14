<?php

/**
 * @file classes/submission/SubmissionMetadataFormImplementation.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
	function __construct($parentForm = null) {
		parent::__construct($parentForm);
	}

	/**
	 * @copydoc PKPSubmissionMetadataFormImplementation::_getAbstractsRequired
	 */
	function _getAbstractsRequired($submission) {
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($submission->getSectionId());
		return !$section->getAbstractsNotRequired();
	}

	/**
	 *
	 * @copydoc PKPSubmissionMetadataFormImplementation::addChecks()
	 */
	function addChecks($submission) {
		parent::addChecks($submission);
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($submission->getSectionId());
		$wordNo = $section->getAbstractWordCount();
		if (isset($wordNo) && $wordNo > 0) {
			$this->_parentForm->addCheck(new FormValidatorCustom($this->_parentForm, 'abstract', 'required', 'submission.submit.form.wordCountAlert', create_function('$abstract, $wordNo', 'foreach ($abstract as $localizedAbstract) {return count(explode(" ",trim(strip_tags($localizedAbstract)))) <= $wordNo; }'), array($wordNo)));
		}

	}

	/**
	 *
	 * @copydoc Form::initData()
	 */
	function initData($submission) {
		parent::initData($submission);
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($submission->getSectionId());
		$wordNo = $section->getAbstractWordCount();
		$this->_parentForm->setData('wordNo', $wordNo);
	}
}

?>
