<?php

/**
 * @file controllers/tab/issueEntry/form/IssueEntryPublicationMetadataForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueEntryPublicationMetadataForm
 * @ingroup controllers_tab_issueEntry_form_IssueEntryPublicationMetadataForm
 *
 * @brief Displays a submission's publication metadata entry form.
 */

import('lib.pkp.classes.form.Form');

class IssueEntryPublicationMetadataForm extends Form {

	/** @var Submission The submission used to show metadata information */
	var $_submission;

	/** @var PublishedSubmission The published submission associated with this submission */
	var $_publishedSubmission;

	/** @var int The current stage id */
	var $_stageId;

	/** @var int The current user ID */
	var $_userId;

	/**
	 * Parameters to configure the form template.
	 */
	var $_formParams;

	/**
	 * Constructor.
	 * @param $submissionId integer
	 * @param $userId integer
	 * @param $stageId integer
	 * @param $formParams array
	 */
	function __construct($submissionId, $userId, $stageId = null, $formParams = null) {
		parent::__construct('controllers/tab/issueEntry/form/publicationMetadataFormFields.tpl');
		$submissionDao = Application::getSubmissionDAO();

		$submissionVersion = null;
		if (isset($formParams) && array_key_exists("submissionVersion", $formParams)) {
			$submissionVersion = $formParams["submissionVersion"];
		}
		$this->_submission = $submissionDao->getById($submissionId, null, false, $submissionVersion);

		$this->_stageId = $stageId;
		$this->_formParams = $formParams;
		$this->_userId = $userId;
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));

		$this->addCheck(new FormValidatorURL($this, 'licenseURL', 'optional', 'form.url.invalid'));
	}

	/**
	 * Fetch the HTML contents of the form.
	 * @param $request PKPRequest
	 * return string
	 */
	function fetch($request) {

		$context = $request->getContext();

		$templateMgr = TemplateManager::getManager($request);

		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');

		$submission = $this->getSubmission();
		$publishedSubmission = $this->getPublishedSubmission();
		if ($publishedSubmission) {
			if ($submission->getCurrentSubmissionVersion() != $submission->getSubmissionVersion()) {
				if (!isset($this->_formParams)) {
					$this->_formParams = array();
				}

				$this->_formParams["readOnly"] = true;
				$this->_formParams["hideSubmit"] = true;
			}
			$templateMgr->assign('publishedSubmission', $publishedSubmission);
		}

		$templateMgr->assign(array(
			'submissionId' => $this->getSubmission()->getId(),
			'stageId' => $this->getStageId(),
			'submissionVersion' => $this->getSubmission()->getSubmissionVersion(),
			'formParams' => $this->getFormParams(),
			'context' => $context,
		));
		$templateMgr->assign('submission', $this->getSubmission());

		return parent::fetch($request);
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_COMMON,
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_APP_SUBMISSION,
			LOCALE_COMPONENT_APP_EDITOR
		);

		$submission = $this->getSubmission();
		$publishedSubmissionDao = DAORegistry::getDAO('PublishedSubmissionDAO');
		$this->_publishedSubmission = $publishedSubmissionDao->getBySubmissionId($submission->getId(), null, false, $submission->getSubmissionVersion());

		$copyrightHolder = $submission->getCopyrightHolder(null);
		$copyrightYear = $submission->getCopyrightYear();
		$licenseURL = $submission->getLicenseURL();

		$this->_data = array(
			'copyrightHolder' => $submission->getDefaultCopyrightHolder(null), // Localized
			'copyrightYear' => $submission->getDefaultCopyrightYear(),
			'licenseURL' => $submission->getDefaultLicenseURL(),
			'arePermissionsAttached' => !empty($copyrightHolder) || !empty($copyrightYear) || !empty($licenseURL),
		);
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
	 * Get the PublishedSubmission
	 * @return PublishedSubmission
	 */
	function getPublishedSubmission() {
		return $this->_publishedSubmission;
	}

	/**
	 * Get the stage id
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

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array(
			'datePublished', 'accessStatus', 'pages',
			'copyrightYear', 'copyrightHolder',
			'licenseURL', 'attachPermissions',
		));
	}

	/**
	 * Save the metadata and store the catalog data for this published
	 * monograph.
	 */
	function execute() {
		parent::execute();

		$request = Application::get()->getRequest();
		$submission = $this->getSubmission();
		$context = $request->getContext();

		$waivePublicationFee = $request->getUserVar('waivePublicationFee') ? true : false;
		if ($waivePublicationFee) {

			$markAsPaid = $request->getUserVar('markAsPaid');
			$paymentManager = Application::getPaymentManager($context);

			$user = $request->getUser();

			// Get a list of author user IDs
			$authorUserIds = array();
			$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
			$submitterAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($submission->getId(), ROLE_ID_AUTHOR);
			$submitterAssignment = $submitterAssignments->next();
			assert(isset($submitterAssignment)); // At least one author should be assigned

			$queuedPayment = $paymentManager->createQueuedPayment(
				$request,
				PAYMENT_TYPE_PUBLICATION,
				$markAsPaid ? $submitterAssignment->getUserId() : $user->getId(),
				$submission->getId(),
				$markAsPaid ? $context->getData('publicationFee') : 0,
				$markAsPaid ? $context->getData('currency') : ''
			);

			$paymentManager->queuePayment($queuedPayment);

			// Since this is a waiver, fulfill the payment immediately
			$paymentManager->fulfillQueuedPayment($request, $queuedPayment, $markAsPaid?'ManualPayment':'Waiver');
		} else {
			// Get the issue for publication.
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issueId = $this->getData('issueId');
			$issue = $issueDao->getById($issueId, $context->getId());

			$sectionDao = DAORegistry::getDAO('SectionDAO');
			$publishedSubmissionDao = DAORegistry::getDAO('PublishedSubmissionDAO');
			$publishedSubmission = $publishedSubmissionDao->getBySubmissionId($submission->getId(), null, false, $submission->getSubmissionVersion()); /* @var $publishedSubmission PublishedSubmission */

			if ($publishedSubmission) {
				if (!$issue || !$issue->getPublished()) {
					$fromIssue = $issueDao->getById($publishedSubmission->getIssueId(), $context->getId());
					if ($fromIssue->getPublished()) {
						// Insert article tombstone
						import('classes.article.ArticleTombstoneManager');
						$articleTombstoneManager = new ArticleTombstoneManager();
						$articleTombstoneManager->insertArticleTombstone($submission, $context);
					}
				}
			}

			$articleSearchIndex = Application::getSubmissionSearchIndex();
			$submissionFilesChanged = false;
			$submissionMetadataChanged = false;

			// define the access status for the article if none is set.
			$accessStatus = $this->getData('accessStatus') != '' ? $this->getData('accessStatus') : ARTICLE_ACCESS_ISSUE_DEFAULT;

			$submissionDao = DAORegistry::getDAO('SubmissionDAO');
			if (!is_null($this->getData('pages'))) {
				$submission->setPages($this->getData('pages'));
			}

		$request = Application::get()->getRequest();
		$submission = $this->getSubmission();
		$context = $request->getContext();

	$sectionDao = DAORegistry::getDAO('SectionDAO');
	$publishedSubmissionDao = DAORegistry::getDAO('PublishedSubmissionDAO');
	$publishedSubmission = $publishedSubmissionDao->getBySubmissionId($submission->getId(), null, false, $submission->getSubmissionVersion()); /* @var $publishedSubmission PublishedSubmission */

	$publishedSubmission = $publishedSubmissionDao->newDataObject();
	$publishedSubmission->setId($submission->getId());
	$publishedSubmission->setDatePublished(Core::getCurrentDate());
	$publishedSubmission->setSequence(REALLY_BIG_NUMBER);
	$publishedSubmission->setAccessStatus($accessStatus);
	$publishedSubmission->setSubmissionVersion($submission->getSubmissionVersion());
	$publishedSubmission->setIsCurrentSubmissionVersion(true);
	$prevPublishedSubmission = $publishedSubmissionDao->getBySubmissionId($submission->getId(), null, false, $submission->getSubmissionVersion() - 1);
	if ($prevPublishedSubmission) {
		$prevPublishedSubmission->setIsCurrentSubmissionVersion(false);

		$publishedSubmissionDao->updatePublishedSubmission($prevPublishedSubmission);
	}

	$publishedSubmissionDao->insertObject($publishedSubmission);

	// Index the published submission metadata and files for the first time.
	$submissionMetadataChanged = true;
	$submissionFilesChanged = true;

	if ($this->getData('attachPermissions')) {
		$submission->setCopyrightYear($this->getData('copyrightYear'));
		$submission->setCopyrightHolder($this->getData('copyrightHolder'), null); // Localized
		$submission->setLicenseURL($this->getData('licenseURL'));
	} else {
		$submission->setCopyrightYear(null);
		$submission->setCopyrightHolder(null, null);
		$submission->setLicenseURL(null);
	}

	$submission->stampStatusModified();
	$submission->setStatus(STATUS_PUBLISHED);
	$submissionDao->updateObject($submission);

	//after the submission is updated, update the search index
	if ($publishedSubmission) {
		if ($submissionFilesChanged) {
			$articleSearchIndex->submissionFilesChanged($publishedSubmission);
		}

		if ($submissionMetadataChanged) {
			$articleSearchIndex->submissionMetadataChanged($publishedSubmission);
		}
	}
	$articleSearchIndex->submissionChangesFinished();
}


