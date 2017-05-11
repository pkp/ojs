<?php

/**
 * @file controllers/modals/submissionMetadata/form/PKPSubmissionMetadataViewForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPSubmissionMetadataViewForm
 * @ingroup controllers_modals_submissionMetadata_form_SubmissionMetadataViewForm
 *
 * @brief Displays a submission's metadata view.
 */

import('lib.pkp.classes.form.Form');

// Use this class to handle the submission metadata.
import('classes.submission.SubmissionMetadataFormImplementation');

class PKPSubmissionMetadataViewForm extends Form {

	/** The submission used to show metadata information **/
	var $_submission;

	/** The current stage id **/
	var $_stageId;

	/**
	 * Parameters to configure the form template.
	 */
	var $_formParams;

	/** @var SubmissionMetadataFormImplementation */
	var $_metadataFormImplem;

	/**
	 * Constructor.
	 * @param $submissionId integer
	 * @param $stageId integer
	 * @param $formParams array
	 */
	function __construct($submissionId, $stageId = null, $formParams = null, $templateName = 'controllers/modals/submissionMetadata/form/submissionMetadataViewForm.tpl') {
		parent::__construct($templateName);

		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById((int) $submissionId);
		if ($submission) {
			$this->_submission = $submission;
		}

		$this->_stageId = $stageId;

		$this->_formParams = $formParams;

		$this->_metadataFormImplem = new SubmissionMetadataFormImplementation($this);

		$this->setDefaultFormLocale($submission->getLocale());

		// Validation checks for this form
		$this->_metadataFormImplem->addChecks($submission);
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the Submission
	 * @return Submission
	 */
	function getSubmission() {
		return $this->_submission;
	}

	/**
	 * Get the Stage Id
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get the extra form parameters.
	 */
	function getFormParams() {
		return $this->_formParams;
	}


	//
	// Overridden template methods
	//
	/**
	 * Get the names of fields for which data should be localized
	 * @return array
	 */
	function getLocaleFieldNames() {
		$this->_metadataFormImplem->getLocaleFieldNames();
	}

	/**
	 * Initialize form data with the author name and the submission id.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initData($args, $request) {
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_COMMON,
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_APP_SUBMISSION
		);

		$this->_metadataFormImplem->initData($this->getSubmission());
		parent::initData();
	}

	/**
	 * Fetch the HTML contents of the form.
	 * @param $request PKPRequest
	 * return string
	 */
	function fetch($request) {
		$submission = $this->getSubmission();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'submissionId' =>$submission->getId(),
			'stageId' => $this->getStageId(),
			'formParams' => $this->getFormParams(),
		));

		// Tell the form what fields are enabled (and which of those are required)
		import('lib.pkp.controllers.grid.settings.metadata.MetadataGridHandler');
		$context = $request->getContext();
		foreach (array_keys(MetadataGridHandler::getNames()) as $field) {
			$templateMgr->assign(array(
				$field . 'Enabled' => $context->getSetting($field . 'EnabledWorkflow'),
				$field . 'Required' => $context->getSetting($field . 'Required')
			));
		}
		// Provide available submission languages. (Convert the array
		// of locale symbolic names xx_XX into an associative array
		// of symbolic names => readable names.)
		$supportedSubmissionLocales = $context->getSetting('supportedSubmissionLocales');
		if (empty($supportedSubmissionLocales)) $supportedSubmissionLocales = array($context->getPrimaryLocale());
		$templateMgr->assign(
			'supportedSubmissionLocaleNames',
			array_flip(array_intersect(
				array_flip(AppLocale::getAllLocales()),
				$supportedSubmissionLocales
			))
		);
		return parent::fetch($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->_metadataFormImplem->readInputData();
	}

	/**
	 * Save changes to submission.
	 * @param $request PKPRequest
	 */
	function execute($request) {
		$submission = $this->getSubmission();
		parent::execute($submission);
		// Execute submission metadata related operations.
		$this->_metadataFormImplem->execute($submission, $request);
	}

}

?>
