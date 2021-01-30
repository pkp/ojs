<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep2Form.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
	function __construct($context, $submission) {
		parent::__construct($context, $submission);
	}
}


