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
	 * Save the metadata.
	 */
	function execute() {
		parent::execute();
		HookRegistry::call('issueentrysubmissionreviewform::execute', array($this));

		$submission = $this->getSubmission();
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


