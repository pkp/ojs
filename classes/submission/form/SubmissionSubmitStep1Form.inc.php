<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep1Form.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubmitStep1Form
 * @ingroup submission_form
 *
 * @brief Form for Step 1 of author submission.
 */

import('lib.pkp.classes.submission.form.PKPSubmissionSubmitStep1Form');

class SubmissionSubmitStep1Form extends PKPSubmissionSubmitStep1Form {
	/**
	 * Constructor.
	 */
	function __construct($context, $submission = null) {
		parent::__construct($context, $submission);
		$this->addCheck(new FormValidatorCustom($this, 'sectionId', 'required', 'author.submit.form.sectionRequired', array(DAORegistry::getDAO('SectionDAO'), 'sectionExists'), array($context->getId())));
	}

	/**
	 * @copydoc SubmissionSubmitForm::fetch
	 */
	function fetch($request, $template = null, $display = false) {
		$roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */
		$user = $request->getUser();
		$canSubmitAll = $roleDao->userHasRole($this->context->getId(), $user->getId(), ROLE_ID_MANAGER) ||
			$roleDao->userHasRole($this->context->getId(), $user->getId(), ROLE_ID_SUB_EDITOR);

		// Get section options for this context
		$sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
		$sections = array();
		$sectionsIterator = $sectionDao->getByContextId($this->context->getId(), null, !$canSubmitAll);
		while ($section = $sectionsIterator->next()) {
			if (!$section->getIsInactive()) {
				$sections[$section->getId()] = $section->getLocalizedTitle();
			}
		} 
		$sectionOptions = array('0' => '') + $sections;

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('sectionOptions', $sectionOptions);
		$templateMgr->assign('sectionId', $request->getUserVar('sectionId'));

		// Get section policies for this context
		$sectionPolicies = array();
		foreach ($sectionOptions as $sectionId => $sectionTitle) {
			$section = $sectionDao->getById($sectionId);

			$sectionPolicy = $section ? $section->getLocalizedPolicy() : null;
			if ($this->doesSectionPolicyContainAnyText($sectionPolicy))
				$sectionPolicies[$sectionId] = $sectionPolicy;
		}

		$templateMgr->assign('sectionPolicies', $sectionPolicies);

		return parent::fetch($request, $template, $display);
	}

	/**
	 * Checks whether a section policy contains any text (plain / readable).
	 */
	private function doesSectionPolicyContainAnyText($sectionPolicy) {
		$sectionPolicyPlainText = trim(PKPString::html2text($sectionPolicy));
		return strlen($sectionPolicyPlainText) > 0;
	}

	/**
	 * @copydoc PKPSubmissionSubmitStep1Form::initData
	 */
	function initData($data = array()) {
		if (isset($this->submission)) {
			parent::initData(array(
				'sectionId' => $this->submission->getCurrentPublication()->getData('sectionId'),
			));
		} else {
			parent::initData();
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'sectionId',
		));
		parent::readInputData();
	}

	/**
	 * Perform additional validation checks
	 * @copydoc Form::validate
	 */
	function validate($callHooks = true) {
		if (!parent::validate($callHooks)) return false;

		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
		$section = $sectionDao->getById($this->getData('sectionId'), $context->getId());

		// Validate that the section ID is attached to this journal.
		if (!$section) return false;

		// Ensure that submissions are enabled and the assigned section is activated
		if ($context->getData('disableSubmissions') || $section->getIsInactive()) {
			return false;
		}

		return true;
	}

	/**
	 * Set the publication data from the form.
	 * @param Publication $publication
	 * @param Submission $submission
	 */
	function setPublicationData($publication, $submission) {
		$publication->setData('sectionId', $this->getData('sectionId'));
		parent::setPublicationData($publication, $submission);
	}
}


