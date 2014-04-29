<?php

/**
 * @file pages/proofreader/SubmissionProofreadHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionProofreadHandler
 * @ingroup pages_proofreader
 *
 * @brief Handle requests for proofreader submission functions.
 */

import('pages.proofreader.ProofreaderHandler');

class SubmissionProofreadHandler extends ProofreaderHandler {
	/**
	 * Constructor
	 */
	function SubmissionProofreadHandler() {
		parent::ProofreaderHandler();
	}

	/**
	 * Submission - Proofreading view
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submission($args, &$request) {
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();

		$this->validate($request, $articleId);
		$this->setupTemplate(true, $articleId);

		$useProofreaders = $journal->getSetting('useProofreaders');

		$authorDao =& DAORegistry::getDAO('AuthorDAO');
		$authors = $authorDao->getAuthorsBySubmissionId($articleId);

		ProofreaderAction::proofreadingUnderway($this->submission, 'SIGNOFF_PROOFREADING_PROOFREADER');
		$useLayoutEditors = $journal->getSetting('useLayoutEditors');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('useProofreaders', $useProofreaders);
		$templateMgr->assign_by_ref('authors', $authors);
		$templateMgr->assign_by_ref('submission', $this->submission);
		$templateMgr->assign('useLayoutEditors', $useLayoutEditors);
		$templateMgr->assign('helpTopicId', 'editorial.proofreadersRole.proofreading');

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($this->submission->getId());
		if ($publishedArticle) {
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue =& $issueDao->getIssueById($publishedArticle->getIssueId());
			$templateMgr->assign_by_ref('publishedArticle', $publishedArticle);
			$templateMgr->assign_by_ref('issue', $issue);
		}

		$templateMgr->display('proofreader/submission.tpl');
	}

	/**
	 * Sets proofreader completion date
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function completeProofreader($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');

		$this->validate($request, $articleId);
		$this->setupTemplate(true);

		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_COMPLETE', $request, $request->getUserVar('send')?'':$request->url(null, 'proofreader', 'completeProofreader'))) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * View submission metadata.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewMetadata($args, &$request) {
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();
		$this->validate($request, $articleId);
		$this->setupTemplate(true, $articleId, 'summary');

		ProofreaderAction::viewMetadata($this->submission, $journal);
	}

	//
	// Misc
	//

	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 * @param $request PKPRequest
	 */
	function downloadFile($args, $request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = array_shift($args); // May be null

		$this->validate($request, $articleId);
		if (!ProofreaderAction::downloadProofreaderFile($this->submission, $fileId, $revision)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Proof / "preview" a galley.
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofGalley($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('submission/layout/proofGalley.tpl');
	}

	/**
	 * Proof galley (shows frame header).
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofGalleyTop($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('backHandler', 'submission');
		$templateMgr->display('submission/layout/proofGalleyTop.tpl');
	}

	/**
	 * Proof galley (outputs file contents).
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofGalleyFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $articleId);

		import('classes.file.ArticleFileManager'); // FIXME

		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet($request->url(null, 'article', 'viewFile', array(
						$articleId, $galleyId, $styleFile->getFileId()
					)));
				}
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');

			} else {
				// View non-HTML file inline
				$this->viewFile(array($articleId, $galley->getFileId()));
			}
		}
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $fileId, [$revision])
	 * @param $request PKPRequest
	 */
	function viewFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = array_shift($args); // Can be null

		$this->validate($request, $articleId);
		if (!ProofreaderAction::viewFile($articleId, $fileId, $revision)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}
}

?>
