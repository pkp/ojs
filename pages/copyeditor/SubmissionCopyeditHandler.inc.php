<?php

/**
 * @file SubmissionCopyeditHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.copyeditor
 * @class SubmissionCopyeditHandler
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */

class SubmissionCopyeditHandler extends CopyeditorHandler {

	function submission($args) {
		$articleId = $args[0];
		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);		

		CopyeditorAction::copyeditUnderway($submission);

		$useLayoutEditors = $journal->getSetting('useLayoutEditors');

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('copyeditor', $submission->getCopyeditor());
		$templateMgr->assign_by_ref('initialCopyeditFile', $submission->getInitialCopyeditFile());
		$templateMgr->assign_by_ref('editorAuthorCopyeditFile', $submission->getEditorAuthorCopyeditFile());
		$templateMgr->assign_by_ref('finalCopyeditFile', $submission->getFinalCopyeditFile());
		$templateMgr->assign_by_ref('proofAssignment', $submission->getProofAssignment());
		$templateMgr->assign('useLayoutEditors', $useLayoutEditors);
		$templateMgr->assign('helpTopicId', 'editorial.copyeditorsRole.copyediting');
		$templateMgr->display('copyeditor/submission.tpl');
	}

	function completeCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		parent::setupTemplate($articleId);

		if (CopyeditorAction::completeCopyedit($submission, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

	function completeFinalCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);

		if (CopyeditorAction::completeFinalCopyedit($submission, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

	function uploadCopyeditVersion() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);

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

		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
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

		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
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

		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$isValid = true;

		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId, $user->getUserId());

		if ($copyeditorSubmission == null) {
			$isValid = false;
		} else if ($copyeditorSubmission->getJournalId() != $journal->getJournalId()) {
			$isValid = false;
		} else {
			if ($copyeditorSubmission->getCopyeditorId() != $user->getUserId()) {
				$isValid = false;
			}
		}

		if (!$isValid) {
			Request::redirect(null, Request::getRequestedPage());
		}

		return array($journal, $copyeditorSubmission);
	}

	//
	// Proofreading
	//

	/**
	 * Set the author proofreading date completion
	 */
	function authorProofreadingComplete($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);

		$send = Request::getUserVar('send') ? true : false;

		import('submission.proofreader.ProofreaderAction');

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
		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);

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
		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);

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
		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);

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
				SubmissionCopyeditHandler::viewFile(array($articleId, $galley->getFileId()));
			}
		}
	}

	/**
	 * Metadata functions.
	 */
	function viewMetadata($args) {
		$articleId = $args[0];
		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'editing');

		CopyeditorAction::viewMetadata($submission, ROLE_ID_COPYEDITOR);
	}

	function saveMetadata() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);

		if (CopyeditorAction::saveMetadata($submission)) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

}
?>
