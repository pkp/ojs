<?php

/**
 * SubmissionProofreadHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.proofreader
 *
 * Handle requests for proofreader submission functions. 
 *
 * $Id$
 */

class SubmissionProofreadHandler extends ProofreaderHandler {

	/**
	 * Submission - Proofreading view
	 */
	function submission($args) {
		$articleId = isset($args[0]) ? (int)$args[0] : 0;

		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);

		$useProofreaders = $journal->getSetting('useProofreaders');

		$authorDao = &DAORegistry::getDAO('AuthorDAO');
		$authors = $authorDao->getAuthorsByArticle($articleId);

		ProofreaderAction::proofreaderProofreadingUnderway($submission);
		$useLayoutEditors = $journal->getSetting('useLayoutEditors');

		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('useProofreaders', $useProofreaders);
		$templateMgr->assign_by_ref('authors', $authors);
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('proofAssignment', $submission->getProofAssignment());
		$templateMgr->assign('useLayoutEditors', $useLayoutEditors);
		$templateMgr->assign('helpTopicId', 'editorial.proofreadersRole.proofreading');		

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($submission->getArticleId());
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
	 */
	function completeProofreader($args) {
		$articleId = Request::getUserVar('articleId');

		SubmissionProofreadHandler::validate($articleId);
		parent::setupTemplate(true);

		if (ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_COMPLETE', Request::getUserVar('send')?'':Request::url(null, 'proofreader', 'completeProofreader'))) {
			Request::redirect(null, null, 'submission', $articleId);
		}		
	}

	function viewMetadata($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'summary');
		
		ProofreaderAction::viewMetadata($submission, ROLE_ID_PROOFREADER);
	}
	
	/**
	 * Validate that the user is the assigned proofreader for the submission.
	 * Redirects to proofreader index page if validation fails.
	 */
	function validate($articleId) {
		parent::validate();
		
		$isValid = false;
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$proofreaderDao = &DAORegistry::getDAO('ProofreaderSubmissionDAO');
		$submission = &$proofreaderDao->getSubmission($articleId, $journal->getJournalId());

		if (isset($submission)) {
			$proofAssignment = &$submission->getProofAssignment();
			if ($proofAssignment->getProofreaderId() == $user->getUserId()) {
				$isValid = true;
			}			
		}
		
		if (!$isValid) {
			Request::redirect(null, Request::getRequestedPage());
		}

		return array($journal, $submission);
	}
	
	//
	// Misc
	//
	
	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function downloadFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		if (!ProofreaderAction::downloadProofreaderFile($submission, $fileId, $revision)) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Proof / "preview" a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('submission/layout/proofGalley.tpl');
	}
	
	/**
	 * Proof galley (shows frame header).
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalleyTop($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('backHandler', 'submission');
		$templateMgr->display('submission/layout/proofGalleyTop.tpl');
	}
	
	/**
	 * Proof galley (outputs file contents).
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalleyFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $articleId);
		
		import('file.ArticleFileManager'); // FIXME
		
		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet(Request::url(null, 'article', 'viewFile', array(
						$articleId, $galleyId, $styleFile->getFileId()
					)));
				}
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');
				
			} else {
				// View non-HTML file inline
				SubmissionProofreadHandler::viewFile(array($articleId, $galley->getFileId()));
			}
		}
	}
	
	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function viewFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		if (!ProofreaderAction::viewFile($articleId, $fileId, $revision)) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

}

?>
