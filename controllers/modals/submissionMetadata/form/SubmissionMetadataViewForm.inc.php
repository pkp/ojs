<?php

/**
 * @file controllers/modals/submissionMetadata/form/SubmissionMetadataViewForm.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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

		// consider public identifiers
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		$templateMgr->assign('article', $submission);

		return parent::fetch($request);
	}

	/**
	 * Initialize form data for a new form.
	 */
	function initData($args, $request) {
		parent::initData($args, $request);
		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->init($this, $this->getSubmission());
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();
		$this->readUserVars(array('sectionId'));
		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->readInputData($this);
	}

	/**
	 * Check to ensure that the form is correctly validated.
	 */
	function validate($request) {
		// Verify additional fields from public identifer plug-ins.
		$journal = $request->getJournal();
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->validate($journal->getId(), $this, $this->getSubmission());

		return parent::validate();
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

		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->execute($this, $submission);
	}
}

?>
