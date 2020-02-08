<?php

/**
 * @file pages/submission/SubmissionHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(ROLE_ID_AUTHOR, ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
				array('index', 'wizard', 'step', 'saveStep', 'fetchChoices'));
	}


	//
	// Public methods
	//
	/**
	 * Retrieves a JSON list of available choices for a tagit metadata input field.
	 * @param $args array
	 * @param $request Request
	 */
	function fetchChoices($args, $request) {
		$term = $request->getUserVar('term');
		$locale = $request->getUserVar('locale');
		if (!$locale) {
			$locale = AppLocale::getLocale();
		}
		switch ($request->getUserVar('list')) {
			case 'languages':
				$isoCodes = new \Sokil\IsoCodes\IsoCodesFactory(\Sokil\IsoCodes\IsoCodesFactory::OPTIMISATION_IO);
				$matches = array();
				foreach ($isoCodes->getLanguages() as $language) {
					if (!$language->getAlpha2() || $language->getType() != 'L' || $language->getScope() != 'I') continue;
					if (stristr($language->getLocalName(), $term)) $matches[$language->getAlpha3()] = $language->getLocalName();
				};
				header('Content-Type: text/json');
				echo json_encode($matches);
		}
		assert(false);
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
	function getStepsNumberAndLocaleKeys() {
		return array(
			1 => 'author.submit.start',
			2 => 'author.submit.upload',
			3 => 'author.submit.metadata',
			4 => 'author.submit.confirmation',
			5 => 'author.submit.nextSteps',
		);
	}

	/**
	 * Get the number of submission steps.
	 * @return int
	 */
	function getStepCount() {
		return 5;
	}
}


