<?php

/**
 * @file classes/submission/editor/EditorAction.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditorAction
 * @ingroup submission
 *
 * @brief EditorAction class.
 */

import('classes.submission.sectionEditor.SectionEditorAction');

class EditorAction extends SectionEditorAction {
	/**
	 * Actions.
	 */

	/**
	 * Rush a new submission into the end of the editing queue.
	 * @param $article object
	 */
	function expediteSubmission($article, $request) {
		$user = $request->getUser();

		import('classes.submission.editor.EditorAction');
		import('classes.submission.sectionEditor.SectionEditorAction');
		import('classes.submission.proofreader.ProofreaderAction');

		$sectionEditorSubmissionDao = DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($article->getId());

		$submissionFile = $sectionEditorSubmission->getSubmissionFile();

		// Add a log entry before doing anything.
		import('lib.pkp.classes.log.SubmissionLog');
		import('classes.log.SubmissionEventLogEntry');
		SubmissionLog::logEvent($request, $article, SUBMISSION_LOG_EDITOR_EXPEDITE, 'log.editor.submissionExpedited', array('editorName' => $user->getFullName()));

		// 1. Ensure that an editor is assigned.
		$editAssignments =& $sectionEditorSubmission->getEditAssignments();
		if (empty($editAssignments)) {
			// No editors are currently assigned; assign self.
			EditorAction::assignEditor($article->getId(), $user->getId(), true, false, $request);
		}

		// 2. Accept the submission and send to copyediting.
		$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($article->getId());
		if (!$sectionEditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL', true)) {
			SectionEditorAction::recordDecision($sectionEditorSubmission, SUBMISSION_EDITOR_DECISION_ACCEPT, $request);
			$reviewFile = $sectionEditorSubmission->getReviewFile();
			SectionEditorAction::setCopyeditFile($sectionEditorSubmission, $reviewFile->getFileId(), $reviewFile->getRevision(), $request);
		}

		// 3. Add a galley.
		$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($article->getId());
		$galleys =& $sectionEditorSubmission->getGalleys();
		$articleSearchIndex = null;
		if (empty($galleys)) {
			// No galley present -- use copyediting file.
			import('classes.file.ArticleFileManager');
			$copyeditFile =& $sectionEditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL');
			$fileType = $copyeditFile->getFileType();
			$articleFileManager = new ArticleFileManager($article->getId());
			$fileId = $articleFileManager->copyPublicFile($copyeditFile->getFilePath(), $fileType);

			if (strstr($fileType, 'html')) {
				$galley = new ArticleHTMLGalley();
			} else {
				$galley = new ArticleGalley();
			}
			$galley->setArticleId($article->getId());
			$galley->setFileId($fileId);
			$galley->setLocale(AppLocale::getLocale());

			if ($galley->isHTMLGalley()) {
				$galley->setLabel('HTML');
			} else {
				if (strstr($fileType, 'pdf')) {
					$galley->setLabel('PDF');
				} else if (strstr($fileType, 'postscript')) {
					$galley->setLabel('Postscript');
				} else if (strstr($fileType, 'xml')) {
					$galley->setLabel('XML');
				} else {
					$galley->setLabel(__('common.untitled'));
				}
			}

			$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
			$galleyDao->insertGalley($galley);

			// Update file search index
			import('classes.search.ArticleSearchIndex');
			$articleSearchIndex = new ArticleSearchIndex();
			$articleSearchIndex->articleFileChanged($article->getId(), ARTICLE_SEARCH_GALLEY_FILE, $fileId);
		}

		$sectionEditorSubmission->setStatus(STATUS_QUEUED);
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		if ($articleSearchIndex) $articleSearchIndex->articleChangesFinished();
	}
}

?>
