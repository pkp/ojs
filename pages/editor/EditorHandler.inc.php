<?php

/**
 * EditorHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.editor
 *
 * Handle requests for editor functions. 
 *
 * $Id$
 */

import('sectionEditor.SectionEditorHandler');

define('EDITOR_SECTION_HOME', 0);
define('EDITOR_SECTION_SUBMISSIONS', 1);
define('EDITOR_SECTION_ISSUES', 2);

import ('submission.editor.EditorAction');

class EditorHandler extends SectionEditorHandler {

	/**
	 * Displays the editor role selection page.
	 */
	 
	function index($args) {
		EditorHandler::validate();
		EditorHandler::setupTemplate(EDITOR_SECTION_HOME, false);
		
		$templateMgr = &TemplateManager::getManager();
		$journal = &Request::getJournal();
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$submissionsCount = &$editorSubmissionDao->getEditorSubmissionsCount($journal->getJournalId());
		$templateMgr->assign('submissionsCount', $submissionsCount);
		$templateMgr->assign('helpTopicId', 'editorial.editorsRole');
		$templateMgr->display('editor/index.tpl');
	}
	
	/**
	 * Display editor submission queue pages.
	 */
	function submissions($args) {
		EditorHandler::validate();
		EditorHandler::setupTemplate(EDITOR_SECTION_SUBMISSIONS, true);

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$sectionDao = &DAORegistry::getDAO('SectionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		$sections = &$sectionDao->getSectionTitles($journal->getJournalId());

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$dateSearchField = Request::getUserVar('dateSearchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$rangeInfo = Handler::getRangeInfo('submissions');

		switch($page) {
			case 'submissionsUnassigned':
				$functionName = 'getEditorSubmissionsUnassigned';
				$helpTopicId = 'editorial.editorsRole.submissions.unassigned';
				break;
			case 'submissionsInEditing':
				$functionName = 'getEditorSubmissionsInEditing';
				$helpTopicId = 'editorial.editorsRole.submissions.inEditing';
				break;
			case 'submissionsArchives':
				$functionName = 'getEditorSubmissionsArchives';
				$helpTopicId = 'editorial.editorsRole.submissions.archives';
				break;
			default:
				$page = 'submissionsInReview';
				$functionName = 'getEditorSubmissionsInReview';
				$helpTopicId = 'editorial.editorsRole.submissions.inReview';
		}

		$submissions = &$editorSubmissionDao->$functionName(
			$journal->getJournalId(),
			Request::getUserVar('section'),
			$searchField,
			$searchMatch,
			$search,
			$dateSearchField,
			$fromDate,
			$toDate,
			$rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('editor', $user->getFullName());
		$templateMgr->assign('sectionOptions', array(0 => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('section', Request::getUserVar('section'));

		// Set search parameters
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search',
			'dateFromMonth', 'dateFromDay', 'dateFromYear',
			'dateToMonth', 'dateToDay', 'dateToYear',
			'dateSearchField'
		);
		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, Request::getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('fieldOptions', Array(
			SUBMISSION_FIELD_TITLE => 'article.title',
			SUBMISSION_FIELD_AUTHOR => 'user.role.author',
			SUBMISSION_FIELD_EDITOR => 'user.role.editor'
		));
		$templateMgr->assign('dateFieldOptions', Array(
			SUBMISSION_FIELD_DATE_SUBMITTED => 'submissions.submitted',
			SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE => 'submissions.copyeditComplete',
			SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE => 'submissions.layoutComplete',
			SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE => 'submissions.proofreadingComplete'
		));

		import('issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));

		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->display('editor/submissions.tpl');
	}
	
	function updateSubmissionArchive() {
		EditorHandler::submissionArchive();
	}
	
	function schedulingQueue() {
		EditorHandler::validate();
		EditorHandler::setupTemplate(EDITOR_SECTION_ISSUES, true);

		$rangeInfo = Handler::getRangeInfo('articles');

		$templateMgr = &TemplateManager::getManager();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		// build sections pulldown
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = &$sectionDao->getSectionTitles($journal->getJournalId());
		$templateMgr->assign('sectionOptions', array(0 => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign('section', Request::getUserVar('section'));

		// retrieve the schedule queued submissions
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$schedulingQueueSubmissions = &$editorSubmissionDao->getEditorSubmissions($journal->getJournalId(), STATUS_SCHEDULED, Request::getUserVar('section'), $rangeInfo);
		$templateMgr->assign('schedulingQueueSubmissions', $schedulingQueueSubmissions);		

		// build the issues pulldown
		$issueOptions[0] = Locale::Translate('editor.schedulingQueue.unscheduled');
		$issueOptions[-1] = Locale::Translate('editor.schedulingQueue.newIssue');
		$issueOptions += IssueManagementHandler::getIssueOptions();
		$templateMgr->assign('issueOptions', $issueOptions);
		$templateMgr->assign('helpTopicId', 'publishing.scheduleSubmissions');
		$templateMgr->display('editor/schedulingQueue.tpl');
	}
	
	function updateSchedulingQueue() {
		EditorHandler::validate();
		$journal = &Request::getJournal();

		$scheduledArticles = Request::getUserVar('schedule');
		$removedArticles = Request::getUserVar('remove');

		// remove all selected articles from the scheduling queue
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		if (isset($removedArticles)) {
			foreach ($removedArticles as $articleId) {
				$article = $articleDao->getArticle($articleId);
				$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
				if ($article && $proofAssignment && $article->getJournalId() == $journal->getJournalId()) {
					$article->setStatus(STATUS_QUEUED);
					$article->stampStatusModified();
					$articleDao->updateArticle($article);
					$proofAssignment->setDateSchedulingQueue(null);
					$proofAssignmentDao->updateProofAssignment($proofAssignment);

					// used later for scheduledArticles
					$articlesRemovedCheck[$articleId] = $article;
				}
			}
		}

		// add selected articles to their respective issues
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		if (isset($scheduledArticles)) {
			while (list($articleId,$issueId) = each($scheduledArticles)) {
				if (!isset($articlesRemovedCheck[$articleId]) && $issueId) {
					$article = $articleDao->getArticle($articleId);
					
					if (!isset($article) || $journal->getJournalId() != $article->getJournalId()) {
						// Invalid request. Do nothing.
					} else if ($issueId == -1) {
						$newIssueArticles[] = $articleId;
					
					} else if ($issueDao->issueIdExists($issueId, $journal->getJournalId())) {
						$article->setStatus(STATUS_PUBLISHED);
						$article->stampStatusModified();
						$articleDao->updateArticle($article);
	
						$publishedArticle = &new PublishedArticle();
						$publishedArticle->setArticleId($articleId);
						$publishedArticle->setIssueId($issueId);
						$publishedArticle->setDatePublished(Core::getCurrentDate());
						$publishedArticle->setSeq(0);
						$publishedArticle->setViews(0);
						$publishedArticle->setAccessStatus(0);
						
						$publishedArticleDao->insertPublishedArticle($publishedArticle);
						$publishedArticleDao->resequencePublishedArticles($article->getSectionId(),$issueId);
					}
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
		
		$journal = &Request::getJournal();
		$articleId = Request::getUserVar('articleId');
		$editorId = Request::getUserVar('editorId');
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		if (isset($editorId) && $editorId != null && $roleDao->roleExists($journal->getJournalId(), $editorId, ROLE_ID_SECTION_EDITOR)) {
			// A valid section editor has already been chosen;
			// either prompt with a modifiable email or, if this
			// has been done, send the email and store the editor
			// selection.

			EditorHandler::setupTemplate(EDITOR_SECTION_SUBMISSIONS, true, $articleId, 'summary');

			// FIXME: Prompt for due date.
			if (EditorAction::assignEditor($articleId, $editorId, Request::getUserVar('send'))) {
				Request::redirect('editor/submission/'.$articleId);
			}
		} else {
			// Allow the user to choose a section editor.
			EditorHandler::setupTemplate(EDITOR_SECTION_SUBMISSIONS, true, $articleId, 'summary');

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = Request::getUserVar('search');
			$searchInitial = Request::getUserVar('searchInitial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');
				
			} else if (isset($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$rangeInfo = &Handler::getRangeInfo('sectionEditors');
			$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
			$sectionEditors = &$editorSubmissionDao->getSectionEditorsNotAssignedToArticle($journal->getJournalId(), $articleId, $searchType, $search, $searchMatch, $rangeInfo);
	
			$templateMgr = &TemplateManager::getManager();
	
			$templateMgr->assign_by_ref('sectionEditors', $sectionEditors);
			$templateMgr->assign('articleId', $articleId);
	
			$sectionDao = &DAORegistry::getDAO('SectionDAO');
			$sectionEditorSections = &$sectionDao->getEditorSections($journal->getJournalId());

			$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
			$editorStatistics = $editAssignmentDao->getEditorStatistics($journal->getJournalId());

			$templateMgr->assign('editorSections', $sectionEditorSections);
			$templateMgr->assign('editorStatistics', $editorStatistics);
			
			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $searchInitial);

			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole.submissionSummary.submissionManagement');	
			$templateMgr->display('editor/selectSectionEditor.tpl');
		}
	}
	
	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		EditorHandler::validate($articleId);
		parent::setupTemplate(true);

		$journal = &Request::getJournal();

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($articleId);

		$status = $article->getStatus();

		if ($article->getJournalId() == $journal->getJournalId() && ($status == STATUS_DECLINED || $status == STATUS_ARCHIVED)) {
			// Delete article files
			import('file.ArticleFileManager');
			$articleFileManager = new ArticleFileManager($articleId);
			$articleFileManager->deleteArticleTree();

			// Delete article database entries
			$articleDao->deleteArticleById($articleId);
		}
		
		Request::redirect('editor/submissions/submissionsArchives');
	}

	/**
	 * Validate that user is an editor in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isEditor($journal->getJournalId())) {
			Validation::redirectLogin();
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $level int set to 0 if caller is at the same level as this handler in the hierarchy; otherwise the number of levels below this handler
	 */
	function setupTemplate($level = EDITOR_SECTION_HOME, $showSidebar = true, $articleId = 0, $parentPage = null) {
		$templateMgr = &TemplateManager::getManager();

		if ($level==EDITOR_SECTION_HOME) $pageHierarchy = array(array('user', 'navigation.user'));
		else if ($level==EDITOR_SECTION_SUBMISSIONS) $pageHierarchy = array(array('user', 'navigation.user'), array('editor', 'user.role.editor'), array('editor/submissions', 'article.submissions'));
		else if ($level==EDITOR_SECTION_ISSUES) $pageHierarchy = array(array('user', 'navigation.user'), array('editor', 'user.role.editor'), array('editor/issueToc', 'issue.issues'));
	
		import('submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'editor');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
		
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
	function futureIssues() {
		IssueManagementHandler::futureIssues();
	}
	
	function backIssues() {
		IssueManagementHandler::backIssues();
	}

	function removeIssue($args) {
		IssueManagementHandler::removeIssue($args);
		Request::redirect(sprintf('%s/issueToc', Request::getRequestedPage()));
	}

	function createIssue() {
		IssueManagementHandler::createIssue();
	}	

	function saveIssue() {
		IssueManagementHandler::saveIssue();
	}

	function issueData($args) {
		IssueManagementHandler::issueData($args);
	}	

	function editIssue($args) {
		IssueManagementHandler::editIssue($args);
	}	
	
	function removeCoverPage($args) {
		IssueManagementHandler::removeCoverPage($args);
	}	
		
	function issueToc($args) {
		IssueManagementHandler::issueToc($args);
	}

	function updateIssueToc($args) {
		IssueManagementHandler::updateIssueToc($args);
	}

	/* ------------------------------------
	   --- #1635# Removed functionality ---
	   ------------------------------------

	function moveSectionToc($args) {
		IssueManagementHandler::moveSectionToc($args);
	}
	*/

	function moveArticleToc($args) {
		IssueManagementHandler::moveArticleToc($args);
	}	
	
	function publishIssue($args) {
		IssueManagementHandler::publishIssue($args);
	}

	function notifyUsers($args) {
		IssueManagementHandler::notifyUsers($args);
	}

}

import('pages.editor.IssueManagementHandler'); // FIXME WTF?

?>
