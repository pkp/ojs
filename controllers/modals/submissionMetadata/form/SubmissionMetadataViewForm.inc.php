<?php

/**
 * @file controllers/modals/submissionMetadata/form/SubmissionMetadataViewForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	function __construct($submissionId, $stageId = null, $formParams = null, $templateName = 'controllers/modals/submissionMetadata/form/submissionMetadataViewForm.tpl') {
		parent::__construct($submissionId, $stageId, $formParams, $templateName);
	}

	/**
	 * Fetch the HTML contents of the form.
	 * @param $request PKPRequest
	 * return string
	 */
	function fetch($request) {
		$submission = $this->getSubmission();
		$templateMgr = TemplateManager::getManager($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);

		// Get section for this journal
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$sectionOptions = $sectionDao->getTitles($submission->getContextId());
		$templateMgr->assign('sectionOptions', $sectionOptions);
		$templateMgr->assign('sectionId', $submission->getSectionId());
		// get word count of the section
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($submission->getSectionId());
		$wordCount = $section->getAbstractWordCount();
		$templateMgr->assign('wordCount', $wordCount);

		// Cover image delete link action
		$locale = AppLocale::getLocale();
		$coverImage = $submission->getCoverImage($locale);
		if ($coverImage) {
			import('lib.pkp.classes.linkAction.LinkAction');
			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			$router = $request->getRouter();
			$deleteCoverImageLinkAction = new LinkAction(
				'deleteCoverImage',
				new RemoteActionConfirmationModal(
					$request->getSession(),
					__('common.confirmDelete'), null,
					$router->url(
						$request, null, null, 'deleteCoverImage', null, array(
							'coverImage' => $coverImage,
							'submissionId' => $submission->getId(),
							// This action can be performed during any stage,
							// but we have to provide a stage id to make calls
							// to IssueEntryTabHandler
							'stageId' => WORKFLOW_STAGE_ID_PRODUCTION,
						)
					),
					'modal_delete'
				),
				__('common.delete'),
				null
			);
			$templateMgr->assign('deleteCoverImageLinkAction', $deleteCoverImageLinkAction);
		}

		return parent::fetch($request);
	}

	/**
	 * Initialize form data
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initData($args, $request) {
		parent::initData($args, $request);
		$submission = $this->getSubmission();
		$locale = AppLocale::getLocale();
		$this->setData('coverImage', $submission->getCoverImage($locale));
		$this->setData('coverImageAltText', $submission->getCoverImageAltText($locale));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();
		$this->readUserVars(array('sectionId','temporaryFileId', 'coverImageAltText'));
	}

	/**
	 * Save changes to submission.
	 * @param $request PKPRequest
	 */
	function execute($request) {
		parent::execute($request);
		$submission = $this->getSubmission();
		$submissionDao = Application::getSubmissionDAO();

		// if section changed consider reordering
		$reorder = false;
		$oldSectionId = $submission->getSectionId();
		if ($oldSectionId != $this->getData('sectionId')) {
			$reorder = true;
			$submission->setSectionId($this->getData('sectionId'));
		}

		$locale = AppLocale::getLocale();
		// Copy an uploaded cover file for the article, if there is one.
		if ($temporaryFileId = $this->getData('temporaryFileId')) {
			$user = $request->getUser();
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());

			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$newFileName = 'article_' . $submission->getId() . '_cover_' . $locale . $publicFileManager->getImageExtension($temporaryFile->getFileType());
			$journal = $request->getJournal();
			$publicFileManager->copyJournalFile($journal->getId(), $temporaryFile->getFilePath(), $newFileName);
			$submission->setCoverImage($newFileName, $locale);
		}

		$submission->setCoverImageAltText($this->getData('coverImageAltText'), $locale);

		$submissionDao->updateObject($submission);

		if ($reorder) {
			// see if it is a published article
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticle = $publishedArticleDao->getByArticleId($submission->getId(), null, false); /* @var $publishedArticle PublishedArticle */
			if ($publishedArticle) {
				// Resequence the articles.
				$publishedArticle->setSequence(REALLY_BIG_NUMBER);
				$publishedArticleDao->updatePublishedArticle($publishedArticle);
				$publishedArticleDao->resequencePublishedArticles($submission->getSectionId(), $publishedArticle->getIssueId());
				// The reordering for the old section is not necessary, but for the correctness sake
				$publishedArticleDao->resequencePublishedArticles($oldSectionId, $publishedArticle->getIssueId());
			}
		}

		if ($submission->getDatePublished()) {
			import('classes.search.ArticleSearchIndex');
			ArticleSearchIndex::articleMetadataChanged($submission);
		}
	}
}

?>
