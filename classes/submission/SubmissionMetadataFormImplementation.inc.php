<?php

/**
 * @file classes/submission/SubmissionMetadataFormImplementation.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
	 * Save changes to submission.
	 * @param $submission Submission
	 * @param $request PKPRequest
	 * @return Submission
	 */
	function execute($submission, $request) {
		parent::execute($submission, $request);

		// Log the modification event.
		import('classes.log.ArticleLog');
		import('classes.log.SubmissionEventLogEntry');
		ArticleLog::logEvent($request, $submission, SUBMISSION_LOG_METADATA_UPDATE, 'submission.event.general.metadataUpdated');
	}
}

?>
