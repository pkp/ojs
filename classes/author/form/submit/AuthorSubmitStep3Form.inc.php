<?php

/**
 * AuthorSubmitStep3Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Form for Step 3 of author submit.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep3Form extends AuthorSubmitForm {
	
	function AuthorSubmitStep3Form() {
		parent::AuthorSubmitForm(
			3,
			array(
				'authorGuidelines' => 'string',
				'submissionChecklist' => 'object',
				'bibFormat' => 'string',
				'copyrightNotice' => 'string',
				'metaDiscipline' => 'bool',
				'metaDisciplineExamples' => 'string',
				'metaSubjectClass' => 'bool',
				'metaSubjectClassTitle' => 'string',
				'metaSubjectClassUrl' => 'string',
				'metaSubject' => 'bool',
				'metaSubjectExamples' => 'string',
				'metaCoverage' => 'bool',
				'metaCoverageGeoExamples' => 'string',
				'metaCoverageChronExamples' => 'string',
				'metaCoverageResearchSampleExamples' => 'string',
				'metaType' => 'bool',
				'metaTypeExamples' => 'string'
			)
		);
	}
	
}

?>
