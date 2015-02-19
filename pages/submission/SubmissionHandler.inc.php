<?php

/**
 * @file pages/submission/SubmissionHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionHandler
 * @ingroup pages_submission
 *
 * @brief Handle requests for the submission wizard.
 */

import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');
import('lib.pkp.pages.submission.PKPSubmissionHandler');

class SubmissionHandler extends PKPSubmissionHandler {
	/**
	 * Constructor
	 */
	function SubmissionHandler() {
		parent::PKPSubmissionHandler();
		$this->addRoleAssignment(array(ROLE_ID_AUTHOR, ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
				array('index', 'wizard', 'step', 'saveStep'));
	}


	//
	// Protected helper methods
	//
	/**
	 * Setup common template variables.
	 * @param $request Request
	 */
	function setupTemplate($request) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_AUTHOR);
		return parent::setupTemplate($request);
	}

	/**
	 * Get the step numbers and their corresponding title locale keys.
	 * @return array
	 */
	protected function _getStepsNumberAndLocaleKeys() {
		return array(
			1 => 'author.submit.start',
			2 => 'author.submit.upload',
			3 => 'author.submit.metadata',
			4 => 'author.submit.confirmation'
		);
	}

	/**
	 * Get the number of submission steps.
	 * @return int
	 */
	protected function _getStepCount() {
		return 4;
	}
}

?>
