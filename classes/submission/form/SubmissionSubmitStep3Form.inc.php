<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep3Form.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubmitStep3Form
 * @ingroup submission_form
 *
 * @brief Form for Step 3 of author submission.
 */

import('lib.pkp.classes.submission.form.PKPSubmissionSubmitStep3Form');
import('classes.submission.SubmissionMetadataFormImplementation');

class SubmissionSubmitStep3Form extends PKPSubmissionSubmitStep3Form {
	/**
	 * Constructor.
	 */
	function __construct($context, $submission) {
		parent::__construct(
			$context,
			$submission,
			new SubmissionMetadataFormImplementation($this)
		);
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		// get word count of the section
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($this->submission->getSectionId());
		$wordCount = $section->getAbstractWordCount();
		$templateMgr->assign('wordCount', $wordCount);
		return parent::fetch($request);
	}
}

?>
