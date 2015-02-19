<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep2Form.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubmitStep2Form
 * @ingroup submission_form
 *
 * @brief Form for Step 2 of author manuscript submission.
 */

import('lib.pkp.classes.submission.form.PKPSubmissionSubmitStep2Form');

class SubmissionSubmitStep2Form extends PKPSubmissionSubmitStep2Form {
	/**
	 * Constructor.
	 */
	function SubmissionSubmitStep2Form($context, $submission) {
		parent::PKPSubmissionSubmitStep2Form($context, $submission);
	}
}

?>
