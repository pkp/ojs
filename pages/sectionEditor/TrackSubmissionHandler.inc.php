<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.reviewer
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */

class TrackSubmissionHandler extends SectionEditorHandler {

	/**
	 * Show assignments list.
	 */
	function assignments($args = array()) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$templateMgr = &TemplateManager::getManager();
			
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$assignedArticles = &$sectionEditorSubmissionDao->getSectionEditorSubmissions($user->getUserId(), $journal->getJournalId());
		$templateMgr->assign('assignedArticles', $assignedArticles);
		
		if (isset($args[0]) && $args[0] == 'completed') {
			$templateMgr->assign('showCompleted', true);
		}
		$templateMgr->display('sectionEditor/assignments.tpl');
	}
	
	function submission($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		
		$articleId = $args[0];
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getJournalSections($journal->getJournalId());

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$numReviewers = $journalSettingsDao->getSetting($journal->getJournalId(), 'numReviewersPerSubmission');
		
		if (count($submission->getReviewAssignments()) < $numReviewers) {
			$numSelectReviewers = $numReviewers - count($submission->getReviewAssignments());
		} else {
			$numSelectReviewers = 1;
		}
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('reviewAssignments', $submission->getReviewAssignments());
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign('postReviewFile', $submission->getPostReviewFile());
		$templateMgr->assign('numSelectReviewers', $numSelectReviewers);
		$templateMgr->assign('sections', $sections);
		
		$templateMgr->display('sectionEditor/submission.tpl');
	}
	
	function submissionEditing($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		
		$articleId = $args[0];
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getJournalSections($journal->getJournalId());
		
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$useCopyeditors = $journalSettingsDao->getSetting($journal->getJournalId(), 'useCopyeditors');
		$useProofreaders = $journalSettingsDao->getSetting($journal->getJournalId(), 'useProofreaders');

		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('copyeditor', $submission->getCopyeditor());
		$templateMgr->assign('sections', $sections);
		$templateMgr->assign('useCopyeditors', $useCopyeditors);
		$templateMgr->assign('useProofreaders', $useProofreaders);
		
		$templateMgr->display('sectionEditor/submissionEditing.tpl');
	}
	
	function changeSection() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$sectionId = Request::getUserVar('sectionId');

		SectionEditorAction::changeSection($articleId, $sectionId);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	function recordRecommendation() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$recommendation = Request::getUserVar('recommendation');

		SectionEditorAction::recordRecommendation($articleId, $recommendation);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	function selectReviewer($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		
		if (isset($args[1]) && $args[1] != null) {
			SectionEditorAction::AddReviewer($args[0], $args[1]);
		
			Request::redirect('sectionEditor/submission/'.$args[0]);
			
		} else {
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			// Actually, we probably want to get reviewers that
			// have not already been assigned this particular
			// article. We'll work on that later ...
			$reviewers = $roleDao->getUsersByRoleId(ROLE_ID_REVIEWER, $journal->getJournalId());
		
			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('reviewers', $reviewers);
			$templateMgr->assign('articleId', $args[0]);
	
			$templateMgr->display('sectionEditor/selectReviewer.tpl');
		}
	}
	
	function clearReviewer($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$reviewId = $args[1];
		
		SectionEditorAction::clearReviewer($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	function notifyReviewer() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');
		
		SectionEditorAction::notifyReviewer($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	function remindReviewer($args = null) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');
		
		if (isset($args[0]) && $args[0] == 'send') {
			SectionEditorAction::remindReviewer($articleId, $reviewId, true);
		} else {
			SectionEditorAction::remindReviewer($articleId, $reviewId);
		}
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	function replaceReviewer($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		
		$articleId = $args[0];
		$reviewId = $args[1];
		
		if (isset($args[2]) && $args[2] != '') {
			$reviewerId = $args[2];
			SectionEditorAction::clearReviewer($articleId, $reviewId);
			SectionEditorAction::addReviewer($articleId, $reviewerId);
		
			Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
		} else {
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			// Actually, we probably want to get reviewers that
			// have not already been assigned this particular
			// article. We'll work on that later ...
			$reviewers = $roleDao->getUsersByRoleId(ROLE_ID_REVIEWER, $journal->getJournalId());
		
			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('reviewers', $reviewers);
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewId', $reviewId);
	
			$templateMgr->display('sectionEditor/replaceReviewer.tpl');
		}
	}
	
	function rateReviewer() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');
		$timeliness = Request::getUserVar('timeliness');
		$quality = Request::getUserVar('quality');
		
		SectionEditorAction::rateReviewer($articleId, $reviewId, $timeliness, $quality);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	function makeReviewFileViewable() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');
		$viewable = Request::getUserVar('viewable');
		
		SectionEditorAction::makeReviewFileViewable($articleId, $reviewId, $viewable);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	function setDueDate($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$reviewId = $args[1];
		
		$dueDate = Request::getUserVar('dueDate');
		$numWeeks = Request::getUserVar('numWeeks');
		
		if ($dueDate != null || $numWeeks != null) {
			SectionEditorAction::setDueDate($articleId, $reviewId, $dueDate, $numWeeks);
		
			Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
		} else {
			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($args[0]);
			foreach ($submission->getReviewAssignments() as $reviewAssignment) {
				if ($reviewAssignment->getReviewId() == $reviewId) {
					$existingDueDate = $reviewAssignment->getDateDue();
				}
			}

			$templateMgr = &TemplateManager::getManager();
		
			if (isset($existingDueDate) && $existingDueDate) {
				$templateMgr->assign('dueDate', $existingDueDate);
			}
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewId', $reviewId);
			$templateMgr->assign('todaysDate', date('Y-m-d'));
	
			$templateMgr->display('sectionEditor/setDueDate.tpl');
		}
	}
	
	function viewMetadata($args) {
		parent::validate();
		parent::setupTemplate(true);
	
		$articleId = $args[0];
	
		SectionEditorAction::viewMetadata($articleId, ROLE_ID_SECTION_EDITOR);
	}
	
	function saveMetadata() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		SectionEditorAction::saveMetadata($articleId);
	}
	
	function selectCopyeditor($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		
		if (isset($args[1]) && $args[1] != null) {
			SectionEditorAction::AddCopyeditor($args[0], $args[1]);
		
			Request::redirect('sectionEditor/submissionEditing/'.$args[0]);
			
		} else {
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			// Actually, we probably want to get reviewers that
			// have not already been assigned this particular
			// article. We'll work on that later ...
			$copyeditors = $roleDao->getUsersByRoleId(ROLE_ID_COPYEDITOR, $journal->getJournalId());
		
			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('copyeditors', $copyeditors);
			$templateMgr->assign('articleId', $args[0]);
	
			$templateMgr->display('sectionEditor/selectCopyeditor.tpl');
		}
	}
	
	function notifyCopyeditor() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		SectionEditorAction::notifyCopyeditor($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}

	function thankCopyeditor() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		SectionEditorAction::thankCopyeditor($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function notifyAuthorCopyedit() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		SectionEditorAction::notifyAuthorCopyedit($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function thankAuthorCopyedit() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		SectionEditorAction::thankAuthorCopyedit($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function initiateFinalCopyedit() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		SectionEditorAction::initiateFinalCopyedit($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}

	function thankFinalCopyedit() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		SectionEditorAction::thankFinalCopyedit($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function uploadPostReviewArticle() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		SectionEditorAction::uploadPostReviewArticle($articleId);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));	
	}
	
	function addSuppFile($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		import("submission.form.SuppFileForm");
		
		$submitForm = &new SuppFileForm($articleId);
		
		$submitForm->initData();
		$submitForm->display();
	}
	
	/**
	 * Save a supplementary file.
	 * @param $args array optional, if set the first parameter is the supplementary file to update
	 */
	function saveSuppFile($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;
		
		import("author.form.submit.AuthorSubmitSuppFileForm");
		
		$submitForm = &new AuthorSubmitSuppFileForm($articleId, $suppFileId);
		$submitForm->readInputData();
		
		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
		
		} else {
			$submitForm->display();
		}
	}
}
?>
