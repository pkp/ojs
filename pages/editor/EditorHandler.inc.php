<?php

/**
 * EditorHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.editor
 *
 * Handle requests for editor functions. 
 *
 * $Id$
 */

import('sectionEditor.SectionEditorHandler');
import('pages.editor.IssueManagementHandler');

class EditorHandler extends SectionEditorHandler {

	/**
	 * Display editor index page.
	 */
	function index($args) {
		EditorHandler::validate();
		EditorHandler::setupTemplate(false, true);

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$sectionDao = &DAORegistry::getDAO('SectionDAO');

		// sorting list to user specified column
		switch(Request::getUserVar('sort')) {
			case 'submitted':
				$sort = 'date_submitted';
				break;
			default:
				$sort = 'article_id';
		}

		$page = isset($args[0]) ? $args[0] : '';
		$nextOrder = (Request::getUserVar('order') == 'desc') ? 'asc' : 'desc';
		$sections = &$sectionDao->getSectionTitles($journal->getJournalId());

		switch($page) {
			case 'submissionsUnassigned':
				$functionName = 'getEditorSubmissionsUnassigned';
				break;
			case 'submissionsInEditing':
				$functionName = 'getEditorSubmissionsInEditing';
				break;
			case 'submissionsArchives':
				$functionName = 'getEditorSubmissionsArchives';
				break;
			default:
				$page = 'submissionsInReview';
				$functionName = 'getEditorSubmissionsInReview';
		}

		$submissions = &$editorSubmissionDao->$functionName($journal->getJournalId(), Request::getUserVar('section'), $sort, Request::getUserVar('order'));

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('editor', $user->getFullName());
		$templateMgr->assign('sectionOptions', array(0 => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign('submissions', $submissions);
		$templateMgr->assign('section', Request::getUserVar('section'));
		$templateMgr->assign('order',$nextOrder);

		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));

		$templateMgr->display('editor/index.tpl');
	}
	
	function updateSubmissionArchive() {
		EditorHandler::submissionArchive();
	}
	
	function schedulingQueue() {
		EditorHandler::validate();
		EditorHandler::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		// sorting list to user specified column
		switch(Request::getUserVar('sort')) {
			case 'section':
				$sort = 'section_title';
				break;
			case 'submitted':
				$sort = 'date_submitted';
				break;
			case 'title':
				$sort = 'title';
				break;
			default:
				$sort = 'article_id';
		}

		// retrieve order and set the next value
		$nextOrder = (Request::getUserVar('order') == 'desc') ? 'asc' : 'desc';
		$templateMgr->assign('order',$nextOrder);

		// build sections pulldown
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = &$sectionDao->getSectionTitles($journal->getJournalId());
		$templateMgr->assign('sectionOptions', array(0 => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign('section', Request::getUserVar('section'));

		// retrieve the schedule queued submissions
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$schedulingQueueSubmissions = &$editorSubmissionDao->getEditorSubmissions($journal->getJournalId(), SCHEDULED, Request::getUserVar('section'), $sort, Request::getUserVar('order'));
		$templateMgr->assign('schedulingQueueSubmissions', $schedulingQueueSubmissions);		
		
		// build the issues pulldown
		$issueOptions[0] = Locale::Translate('editor.schedulingQueue.unscheduled');
		$issueOptions[-1] = Locale::Translate('editor.schedulingQueue.newIssue');
		$issueOptions += IssueManagementHandler::getIssueOptions($journalId,4);
		$templateMgr->assign('issueOptions', $issueOptions);

		$templateMgr->display('editor/schedulingQueue.tpl');
	}
	
	function updateSchedulingQueue() {
		EditorHandler::validate();

		$scheduledArticles = Request::getUserVar('schedule');
		$removedArticles = Request::getUserVar('remove');

		// remove all selected articles from the scheduling queue
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		if (isset($removedArticles)) {
			foreach ($removedArticles as $articleId) {
				$article = $articleDao->getArticle($articleId);
				$article->setStatus(QUEUED);
				$articleDao->updateArticle($article);
				$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
				$proofAssignment->setDateSchedulingQueue(null);
				$proofAssignmentDao->updateProofAssignment($proofAssignment);

				// used later for scheduledArticles
				$articlesRemovedCheck[$articleId] = $article;
			}
		}

		// add selected articles to their respective issues
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		while (list($articleId,$issueId) = each($scheduledArticles)) {
			if (!isset($articlesRemovedCheck[$articleId]) && $issueId) {
				$article = $articleDao->getArticle($articleId);
	
				if ($issueId != -1) {
					$article->setStatus(PUBLISHED);
					$articleDao->updateArticle($article);

					$publishedArticle = &new PublishedArticle();
					$publishedArticle->setArticleId($articleId);
					$publishedArticle->setIssueId($issueId);
					$publishedArticle->setSectionId($article->getSectionId());
					$publishedArticle->setDatePublished(Core::getCurrentDate());
					$publishedArticle->setSeq(0);
					$publishedArticle->setViews(0);
					$publishedArticle->setAccessStatus(0);
					
					$publishedArticleDao->insertPublishedArticle($publishedArticle);
					$publishedArticleDao->resequencePublishedArticles($article->getSectionId(),$issueId);
				} else {
					$newIssueArticles[] = $article;
				}
			}
		}

		if (isset($newIssueArticles) && !empty($newIssueArticles)) {
			IssueManagementHandler::createIssue($newIssueArticles);
		} else {
			EditorHandler::schedulingQueue();
		}
	}
	
	/**
	 * Assigns the selected editor to the submission.
	 * Any previously assigned editors become unassigned.
	 */
	 
	function assignEditor($args) {
		EditorHandler::validate();
		EditorHandler::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$articleId = isset($args[0]) ? $args[0] : 0;
		
		if (isset($args[1]) && $args[1] != null) {
			// Assign editor to article			
			EditorAction::assignEditor($articleId, $args[1]);
			Request::redirect('editor/submission/'.$articleId);
			
			// FIXME: Prompt for due date.
		} else {
			$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
			$sectionEditors = $editorSubmissionDao->getSectionEditorsNotAssignedToArticle($journal->getJournalId(), $articleId);
		
			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('sectionEditors', $sectionEditors);
			$templateMgr->assign('articleId', $articleId);
	
			$templateMgr->display('editor/selectSectionEditor.tpl');
		}
	}
	
	/**
	 * Validate that user is an editor in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isEditor($journal->getJournalId())) {
			Request::redirect('user');
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('editor', 'editor.journalEditor'), array('editor/index', 'submission.submission'))
				: array(array('user', 'navigation.user'), array('editor', 'editor.journalEditor'))
		);
		
		if ($showSidebar) {
			$templateMgr->assign('sidebarTemplate', 'editor/navsidebar.tpl');

			$journal = &Request::getJournal();
			$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
			$submissionsCount = &$editorSubmissionDao->getEditorSubmissionsCount($journal->getJournalId());
			$templateMgr->assign('submissionsCount', $submissionsCount);

		}
	}

	//
	// Issue
	//
	function backIssues() {
		IssueManagementHandler::backIssues();
	}

	function updateBackIssues($args) {
		IssueManagementHandler::updateBackIssues($args);
	}

	function removeIssue($args) {
		IssueManagementHandler::removeIssue($args);
	}

	function createIssue() {
		IssueManagementHandler::createIssue();
	}	

	function saveIssue() {
		IssueManagementHandler::saveIssue();
	}

	function issueManagement($args) {
		IssueManagementHandler::issueManagement($args);
	}

	function updateIssueToc($args) {
		IssueManagementHandler::updateIssueToc($args);
	}

	function moveSectionToc($args) {
		IssueManagementHandler::moveSectionToc($args);
	}

	function moveArticleToc($args) {
		IssueManagementHandler::moveArticleToc($args);
	}

	function publishIssue($args) {
		IssueManagementHandler::publishIssue($args);
	}

	function editIssue($args) {
		IssueManagementHandler::editIssue($args);
	}

	function download($args) {
		IssueManagementHandler::download($args);
	}

	function removeCoverPage($args) {
		IssueManagementHandler::removeCoverPage($args);
	}

}

?>
