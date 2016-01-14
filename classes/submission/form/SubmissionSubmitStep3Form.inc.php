<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep3Form.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	function SubmissionSubmitStep3Form($context, $submission) {
		parent::PKPSubmissionSubmitStep3Form(
			$context,
			$submission,
			new SubmissionMetadataFormImplementation($this)
		);
	}
}

?>
