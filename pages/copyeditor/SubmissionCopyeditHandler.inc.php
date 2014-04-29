<?php

/**
 * @file pages/copyeditor/SubmissionCopyeditHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	 */
	function SubmissionCopyeditHandler() {
		parent::CopyeditorHandler();
	}

	/**
	 * Copyeditor's view of a submission.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submission($args, &$request) {
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);

		$router =& $request->getRouter();

		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId);

		CopyeditorAction::copyeditUnderway($submission, $request);

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

	/**
	 * Complete a copyedit.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function completeCopyedit($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate(true, $articleId);

		if (CopyeditorAction::completeCopyedit($this->submission, $request->getUserVar('send'), $request)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Complete a final copyedit.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function completeFinalCopyedit($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate(true, $articleId);;
		if (CopyeditorAction::completeFinalCopyedit($this->submission, $request->getUserVar('send'), $request)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Upload a copyedit version
	 * @param $args array
	 * @param $request object
	 */
	function uploadCopyeditVersion($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$copyeditStage = $request->getUserVar('copyeditStage');
		CopyeditorAction::uploadCopyeditVersion($this->submission, $copyeditStage, $request);

		$request->redirect(null, null, 'submission', $articleId);
	}

	//
	// Misc
	//

	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function downloadFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = array_shift($args); // Can be null

		$this->validate($request, $articleId);
		if (!CopyeditorAction::downloadCopyeditorFile($this->submission, $fileId, $revision)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function viewFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = array_shift($args); // May be null

		$this->validate($request, $articleId);
		if (!CopyeditorAction::viewFile($articleId, $fileId, $revision)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	//
	// Proofreading
	//

	/**
	 * Set the author proofreading date completion
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function authorProofreadingComplete($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate(true, $articleId);

		$send = $request->getUserVar('send') ? true : false;

		import('classes.submission.proofreader.ProofreaderAction');

		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_AUTHOR_COMPLETE', $request, $send?'':$request->url(null, 'copyeditor', 'authorProofreadingComplete', 'send'))) {
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
				$this->viewFile(array($articleId, $galley->getFileId()), $request);
			}
		}
	}

	/**
	 * Metadata functions.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewMetadata($args, $request) {
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();
		$this->validate($request, $articleId);
		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_AUTHOR);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId, 'editing');
		CopyeditorAction::viewMetadata($submission, $journal);
	}

	/**
	 * Save modified metadata.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveMetadata($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate(true, $articleId);

		if (CopyeditorAction::saveMetadata($this->submission, $request)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Remove cover page from article
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function removeArticleCoverPage($args, &$request) {
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);

		$formLocale = array_shift($args);
		if (!AppLocale::isLocaleValid($formLocale)) {
			$request->redirect(null, null, 'viewMetadata', $articleId);
		}

		import('classes.submission.sectionEditor.SectionEditorAction');
		if (SectionEditorAction::removeArticleCoverPage($this->submission, $formLocale)) {
			$request->redirect(null, null, 'viewMetadata', $articleId);
		}
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
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);

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
