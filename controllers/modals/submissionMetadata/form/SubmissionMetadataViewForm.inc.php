<?php

/**
 * @file controllers/modals/submissionMetadata/form/SubmissionMetadataViewForm.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionMetadataViewForm
 * @ingroup controllers_modals_submissionMetadata_form_SubmissionMetadataViewForm
 *
 * @brief Displays a submission's metadata view.
 */

import('lib.pkp.controllers.modals.submissionMetadata.form.PKPSubmissionMetadataViewForm');

class SubmissionMetadataViewForm extends PKPSubmissionMetadataViewForm {

	/**
	 * Constructor.
	 * @param $submissionId integer
	 * @param $stageId integer
	 * @param $formParams array
	 */
	function SubmissionMetadataViewForm($submissionId, $stageId = null, $formParams = null, $templateName = 'controllers/modals/submissionMetadata/form/submissionMetadataViewForm.tpl') {
		parent::PKPSubmissionMetadataViewForm($submissionId, $stageId, $formParams, $templateName);
	}

	/**
	 * Fetch the HTML contents of the form.
	 * @param $request PKPRequest
	 * return string
	 */
	function fetch($request) {
		$submission = $this->getSubmission();
		$templateMgr = TemplateManager::getManager($request);

		// Get section for this journal
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$seriesOptions = array('0' => __('submission.submit.selectSection')) + $sectionDao->getSectionTitles($submission->getContextId());
		$templateMgr->assign('sectionOptions', $seriesOptions);
		$templateMgr->assign('sectionId', $submission->getSectionId());

		return parent::fetch($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();
		$this->readUserVars(array('sectionId'));
	}

	/**
	 * Save changes to submission.
	 * @param $request PKPRequest
	 */
	function execute($request) {
		parent::execute($request);
		$submission = $this->getSubmission();
		$submissionDao = Application::getSubmissionDAO();

		$submission->setSectionId($this->getData('sectionId'));
		$submissionDao->updateObject($submission);

		if ($submission->getDatePublished()) {
			import('classes.search.ArticleSearchIndex');
			ArticleSearchIndex::articleMetadataChanged($submission);
		}
	}
}

?>
