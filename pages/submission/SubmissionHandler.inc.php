<?php

/**
 * @file pages/submission/SubmissionHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(ROLE_ID_AUTHOR, ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
				array('index', 'wizard', 'step', 'saveStep', 'fetchChoices'));
	}


	//
	// Public methods
	//
	/**
	 * @copydoc PKPSubmissionHandler::step()
	 */
	function step($args, $request) {
		$step = isset($args[0]) ? (int) $args[0] : 1;
		if ($step == $this->getStepCount()) {
			$templateMgr = TemplateManager::getManager($request);
			$context = $request->getContext();
			$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

			// PPS: Check if author can publish
			import('classes.core.Services');

			// Author can publish, see if other criteria exists and create an array of errors
			if (Services::get('publication')->canAuthorPublish($submission->getId())){

				$primaryLocale = $context->getPrimaryLocale();
				$allowedLocales = $context->getSupportedLocales();
				$errors = Services::get('publication')->validatePublish($submission->getLatestPublication(), $submission, $allowedLocales, $primaryLocale);

				if (!empty($errors)){
					$msg .= '<ul class="plain">';
					foreach ($errors as $error) {
						$msg .= '<li>' . $error . '</li>';
					}
					$msg .= '</ul>';
					$templateMgr->assign('errors', $msg);
				}
			}
			// Author can not publish
			else {
				$templateMgr->assign('authorCanNotPublish', true);
			}
		}
		return parent::step($args, $request);
	}

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
				$languageDao = DAORegistry::getDAO('LanguageDAO');
				$languages = $languageDao->getLanguages($locale);
				$matches = array();
				foreach ($languages as $language) {
					if (stristr($language->getName(), $term)) $matches[$language->getCode()] = $language->getName();
				}
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


