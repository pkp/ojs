<?php

/**
 * AuthorSubmitStep2Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Form for Step 2 of author submit.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep2Form extends AuthorSubmitForm {
	
	function AuthorSubmitStep2Form() {
		parent::AuthorSubmitForm(
			2,
			array(
				'focusScopeDesc' => 'string',
				'numReviewersPerSubmission' => 'int',
				'numWeeksPerReview' => 'int',
				'reviewPolicy' => 'string',
				'mailSubmissionsToReviewers' => 'int',
				'reviewGuidelines' => 'string',
				'authorSelectsEditor' => 'int',
				'privacyStatement' => 'string',
				'openAccessPolicy' => 'string'
			)
		);
	}
	
}

?>
