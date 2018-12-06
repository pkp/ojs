<?php

/**
 * @file controllers/modals/submissionMetadata/form/IssueEntrySubmissionReviewForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CatalogEntrySubmissionReviewForm
 * @ingroup controllers_modals_submissionMetadata_form_IssueEntrySubmissionReviewForm
 *
 * @brief Displays a submission's metadata view.
 */

import('lib.pkp.classes.form.Form');

// Use this class to handle the submission metadata.
import('controllers.modals.submissionMetadata.form.SubmissionMetadataViewForm');

class IssueEntrySubmissionReviewForm extends SubmissionMetadataViewForm {

	/**
	 * Constructor.
	 * @param $submissionId integer
	 * @param $stageId integer
	 * @param $formParams array
	 */
	function __construct($submissionId, $stageId = null, $formParams = null) {
		parent::__construct($submissionId, $stageId, $formParams, 'controllers/modals/submissionMetadata/form/issueEntrySubmissionReviewForm.tpl');
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_APP_AUTHOR);
	}

	/**
	 * Fetch the HTML contents of the form.
	 * @param $request PKPRequest
	 * return string
	 */
	function fetch($request) {
		$context = $request->getContext();

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$user = $request->getUser();
		$canSubmitAll = $roleDao->userHasRole($context->getId(), $user->getId(), ROLE_ID_MANAGER) ||
			$roleDao->userHasRole($context->getId(), $user->getId(), ROLE_ID_SUB_EDITOR);

		// Get section options for this context
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$sectionOptions = array('0' => '') + $sectionDao->getTitlesByContextId($context->getId(), !$canSubmitAll);

		// Get section policies for this context
		$sectionPolicies = array();
		foreach ($sectionOptions as $sectionId => $sectionTitle) {
			$section = $sectionDao->getById($sectionId);

			$sectionPolicy = $section ? $section->getLocalizedPolicy() : null;
			$sectionPolicyPlainText = trim(PKPString::html2text($sectionPolicy));
			if (strlen($sectionPolicyPlainText) > 0)
				$sectionPolicies[$sectionId] = $sectionPolicy;
		}

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('sectionPolicies', $sectionPolicies);

		return parent::fetch($request);
	}

	/**
	 * Save the metadata.
	 */
	function execute() {
		parent::execute();

		$submission = $this->getSubmission();
		HookRegistry::call('issueentrysubmissionreviewform::execute', array($this, $submission));

		$submissionDao = Application::getSubmissionDAO();
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getByArticleId($submission->getId(), null, false);
		$isExistingEntry = $publishedArticle?true:false;

		if ($isExistingEntry) {
			// Update the search index for this published article.
			import('classes.search.ArticleSearchIndex');
			ArticleSearchIndex::articleMetadataChanged($submission);
		}

		$submissionDao->updateObject($submission);
	}
}


