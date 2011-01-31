<?php

/**
 * @file SubmissionCopyeditHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCopyeditHandler
 * @ingroup pages_copyeditor
 *
 * @brief Handle requests for submission tracking.
 */


import('pages.copyeditor.CopyeditorHandler');

class SubmissionCopyeditHandler extends CopyeditorHandler {
	/**
	 * Constructor
	 **/
	function SubmissionCopyeditHandler() {
		parent::CopyeditorHandler();
	}
	/** submission associated with the request **/
	var $submission;

	function submission($args, &$request) {
		$articleId = $args[0];
		$this->validate($articleId);

		$router =& $request->getRouter();

		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId);

		CopyeditorAction::copyeditUnderway($submission);

		$journal =& $router->getContext($request);
		$useLayoutEditors = $journal->getSetting('useLayoutEditors');
		$metaCitations = $journal->getSetting('metaCitations');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('copyeditor', $submission->getUserBySignoffType('SIGNOFF_COPYEDITING_INITIAL'));
		$templateMgr->assign_by_ref('initialCopyeditFile', $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL'));
		$templateMgr->assign_by_ref('editorAuthorCopyeditFile', $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_AUTHOR'));
		$templateMgr->assign_by_ref('finalCopyeditFile', $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_FINAL'));
		$templateMgr->assign('useLayoutEditors', $useLayoutEditors);
		$templateMgr->assign('metaCitations', $metaCitations);
		$templateMgr->assign('helpTopicId', 'editorial.copyeditorsRole.copyediting');
		$templateMgr->display('copyeditor/submission.tpl');
	}

	function completeCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		$this->validate($articleId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId);

		if (CopyeditorAction::completeCopyedit($submission, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

	function completeFinalCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		$this->validate($articleId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId);;

		if (CopyeditorAction::completeFinalCopyedit($submission, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

	function uploadCopyeditVersion() {
		$articleId = Request::getUserVar('articleId');
		$this->validate($articleId);
		$submission =& $this->submission;

		$copyeditStage = Request::getUserVar('copyeditStage');
		CopyeditorAction::uploadCopyeditVersion($submission, $copyeditStage);

		Request::redirect(null, null, 'submission', $articleId);
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

		$this->validate($articleId);
		$submission =& $this->submission;
		if (!CopyeditorAction::downloadCopyeditorFile($submission, $fileId, $revision)) {
			Request::redirect(null, null, 'submission', $articleId);
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

		$this->validate($articleId);
		$submission =& $this->submission;
		if (!CopyeditorAction::viewFile($articleId, $fileId, $revision)) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

	//
	// Validation
	//

	/**
	 * Validate that the user is the assigned copyeditor for
	 * the article.
	 * Redirects to copyeditor index page if validation fails.
	 */
	function validate($articleId) {
		parent::validate();

		$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$journal =& Request::getJournal();
		$user =& Request::getUser();

		$isValid = true;

		$copyeditorSubmission =& $copyeditorSubmissionDao->getCopyeditorSubmission($articleId, $user->getId());

		if ($copyeditorSubmission == null) {
			$isValid = false;
		} else {
			if ($copyeditorSubmission->getJournalId() != $journal->getId()) {
				$isValid = false;
			} else {
				if ($copyeditorSubmission->getUserIdBySignoffType('SIGNOFF_COPYEDITING_INITIAL') != $user->getId()) {
					$isValid = false;
				}
			}
		}

		if (!$isValid) {
			Request::redirect(null, Request::getRequestedPage());
		}

		$this->submission =& $copyeditorSubmission;
		return true;
	}

	//
	// Proofreading
	//

	/**
	 * Set the author proofreading date completion
	 */
	function authorProofreadingComplete($args) {
		$articleId = Request::getUserVar('articleId');
		$this->validate($articleId);
		$this->setupTemplate(true, $articleId);

		$send = Request::getUserVar('send') ? true : false;

		import('classes.submission.proofreader.ProofreaderAction');

		if (ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_AUTHOR_COMPLETE', $send?'':Request::url(null, 'copyeditor', 'authorProofreadingComplete', 'send'))) {
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
		$this->validate($articleId);

		$templateMgr =& TemplateManager::getManager();
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
		$this->validate($articleId);

		$templateMgr =& TemplateManager::getManager();
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
		$this->validate($articleId);

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $articleId);

		import('classes.file.ArticleFileManager'); // FIXME

		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet(Request::url(null, 'article', 'viewFile', array(
						$articleId, $galleyId, $styleFile->getFileId()
					)));
				}
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');

			} else {
				// View non-HTML file inline
				SubmissionCopyeditHandler::viewFile(array($articleId, $galley->getFileId()));
			}
		}
	}

	/**
	 * Metadata functions.
	 */
	function viewMetadata($args, $request) {
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();
		$this->validate($articleId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId, 'editing');
		CopyeditorAction::viewMetadata($submission, $journal);
	}

	function saveMetadata($args, &$request) {
		$articleId = Request::getUserVar('articleId');
		$this->validate($articleId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId);

		if (CopyeditorAction::saveMetadata($submission, $request)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Remove cover page from article
	 */
	function removeCoverPage($args) {
		$articleId = isset($args[0]) ? (int)$args[0] : 0;
		$formLocale = $args[1];
		$this->validate($articleId);
		$submission =& $this->submission;
		$journal =& Request::getJournal();

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getId(),$submission->getFileName($formLocale));
		$submission->setFileName('', $formLocale);
		$submission->setOriginalFileName('', $formLocale);
		$submission->setWidth('', $formLocale);
		$submission->setHeight('', $formLocale);

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$articleDao->updateArticle($submission);

		Request::redirect(null, null, 'viewMetadata', $articleId);
	}


	//
	// Citation Editing
	//
	/**
	 * Display the citation editing assistant.
	 * @param $args array
	 * @param $request Request
	 */
	function submissionCitations($args, &$request) {
		// Authorize the request.
		$articleId = $args[0];
		$this->validate($articleId);

		// Prepare the view.
		$this->setupTemplate(true, $articleId);

		// Insert the citation editing assistant into the view.
		CopyeditorAction::editCitations($request, $this->submission);

		// Render the view.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->display('copyeditor/submissionCitations.tpl');
	}
}

?>
