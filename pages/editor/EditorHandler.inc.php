<?php

/**
 * @file EditorHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.editor
 * @class EditorHandler
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
		EditorHandler::setupTemplate(EDITOR_SECTION_HOME);

		$templateMgr = &TemplateManager::getManager();
		$journal = &Request::getJournal();

		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$sectionDao = &DAORegistry::getDAO('SectionDAO');

		$sections = &$sectionDao->getSectionTitles($journal->getJournalId());
		$templateMgr->assign('sectionOptions', array(0 => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign('fieldOptions', EditorHandler::getSearchFieldOptions());
		$templateMgr->assign('dateFieldOptions', EditorHandler::getDateFieldOptions());

		// Bring in the print_issue_id function (FIXME?)
		import('issue.IssueAction');
		$issueAction = &new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));

		// If a search was performed, get the necessary info.
		if (array_shift($args) == 'search') {
			$rangeInfo = Handler::getRangeInfo('submissions');

			// Get the user's search conditions, if any
			$searchField = Request::getUserVar('searchField');
			$dateSearchField = Request::getUserVar('dateSearchField');
			$searchMatch = Request::getUserVar('searchMatch');
			$search = Request::getUserVar('search');

			$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
			if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
			$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
			if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);
			$rawSubmissions = &$editorSubmissionDao->getUnfilteredEditorSubmissions(
				$journal->getJournalId(),
				Request::getUserVar('section'),
				$searchField,
				$searchMatch,
				$search,
				$dateSearchField,
				$fromDate,
				$toDate,
				null,
				$rangeInfo
			);
			
			// EditorSubmissionDAO does not return edit assignments, so we need this.
			$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$submissions = &new DAOResultFactory($rawSubmissions, $sectionEditorSubmissionDao, '_returnSectionEditorSubmissionFromRow');


			$templateMgr->assign_by_ref('submissions', $submissions);
			$templateMgr->assign('section', Request::getUserVar('section'));

			// Set search parameters
			foreach (EditorHandler::getSearchFormDuplicateParameters() as $param)
				$templateMgr->assign($param, Request::getUserVar($param));

			$templateMgr->assign('dateFrom', $fromDate);
			$templateMgr->assign('dateTo', $toDate);
			$templateMgr->assign('displayResults', true);
		}

		$rangeInfo = Handler::getRangeInfo('submissions');


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
		EditorHandler::setupTemplate(EDITOR_SECTION_SUBMISSIONS);

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
		foreach (EditorHandler::getSearchFormDuplicateParameters() as $param)
			$templateMgr->assign($param, Request::getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('fieldOptions', EditorHandler::getSearchFieldOptions());
		$templateMgr->assign('dateFieldOptions', EditorHandler::getDateFieldOptions());

		import('issue.IssueAction');
		$issueAction = &new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));

		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->display('editor/submissions.tpl');
	}

	function updateSubmissionArchive() {
		EditorHandler::submissionArchive();
	}

	/**
	 * Get the list of parameter names that should be duplicated when
	 * displaying the search form (i.e. made available to the template
	 * based on supplied user data).
	 * @return array
	 */
	function getSearchFormDuplicateParameters() {
		return array(
			'searchField', 'searchMatch', 'search',
			'dateFromMonth', 'dateFromDay', 'dateFromYear',
			'dateToMonth', 'dateToDay', 'dateToYear',
			'dateSearchField'
		);
	}

	/**
	 * Get the list of fields that can be searched by contents.
	 * @return array
	 */
	function getSearchFieldOptions() {
		return array(
			SUBMISSION_FIELD_TITLE => 'article.title',
			SUBMISSION_FIELD_AUTHOR => 'user.role.author',
			SUBMISSION_FIELD_EDITOR => 'user.role.editor',
			SUBMISSION_FIELD_REVIEWER => 'user.role.reviewer',
			SUBMISSION_FIELD_COPYEDITOR => 'user.role.copyeditor',
			SUBMISSION_FIELD_LAYOUTEDITOR => 'user.role.layoutEditor',
			SUBMISSION_FIELD_PROOFREADER => 'user.role.proofreader'
		);
	}

	/**
	 * Get the list of date fields that can be searched.
	 * @return array
	 */
	function getDateFieldOptions() {
		return array(
			SUBMISSION_FIELD_DATE_SUBMITTED => 'submissions.submitted',
			SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE => 'submissions.copyeditComplete',
			SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE => 'submissions.layoutComplete',
			SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE => 'submissions.proofreadingComplete'
		);
	}

	/**
	 * Set the canEdit / canReview flags for this submission's edit assignments.
	 */
	function setEditorFlags($args) {
		EditorHandler::validate();

		$journal = &Request::getJournal();
		$articleId = (int) Request::getUserVar('articleId');

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article =& $articleDao->getArticle($articleId);

		if ($article && $article->getJournalId() === $journal->getJournalId()) {
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByArticleId($articleId);

			while($editAssignment =& $editAssignments->next()) {
				if ($editAssignment->getIsEditor()) continue;

				$canReview = Request::getUserVar('canReview-' . $editAssignment->getEditId()) ? 1 : 0;
				$canEdit = Request::getUserVar('canEdit-' . $editAssignment->getEditId()) ? 1 : 0;

				$editAssignment->setCanReview($canReview);
				$editAssignment->setCanEdit($canEdit);

				$editAssignmentDao->updateEditAssignment($editAssignment);
			}
		}

		Request::redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Delete the specified edit assignment.
	 */
	function deleteEditAssignment($args) {
		EditorHandler::validate();

		$journal = &Request::getJournal();
		$editId = (int) (isset($args[0])?$args[0]:0);

		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignment =& $editAssignmentDao->getEditAssignment($editId);

		if ($editAssignment) {
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle($editAssignment->getArticleId());

			if ($article && $article->getJournalId() === $journal->getJournalId()) {
				$editAssignmentDao->deleteEditAssignmentById($editAssignment->getEditId());
				Request::redirect(null, null, 'submission', $article->getArticleId());
			}
		}

		Request::redirect(null, null, 'submissions');
	}

	/**
	 * Assigns the selected editor to the submission.
	 */
	function assignEditor($args) {
		EditorHandler::validate();

		$journal = &Request::getJournal();
		$articleId = Request::getUserVar('articleId');
		$editorId = Request::getUserVar('editorId');
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		$isSectionEditor = $roleDao->roleExists($journal->getJournalId(), $editorId, ROLE_ID_SECTION_EDITOR);
		$isEditor = $roleDao->roleExists($journal->getJournalId(), $editorId, ROLE_ID_EDITOR);

		if (isset($editorId) && $editorId != null && ($isEditor || $isSectionEditor)) {
			// A valid section editor has already been chosen;
			// either prompt with a modifiable email or, if this
			// has been done, send the email and store the editor
			// selection.

			EditorHandler::setupTemplate(EDITOR_SECTION_SUBMISSIONS, $articleId, 'summary');

			// FIXME: Prompt for due date.
			if (EditorAction::assignEditor($articleId, $editorId, $isEditor, Request::getUserVar('send'))) {
				Request::redirect(null, null, 'submission', $articleId);
			}
		} else {
			// Allow the user to choose a section editor or editor.
			EditorHandler::setupTemplate(EDITOR_SECTION_SUBMISSIONS, $articleId, 'summary');

			$searchType = null;
			$searchMatch = null;
			$search = Request::getUserVar('search');
			$searchInitial = Request::getUserVar('searchInitial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');

			} else if (isset($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$rangeInfo = &Handler::getRangeInfo('editors');
			$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');

			if (isset($args[0]) && $args[0] === 'editor') {
				$roleName = 'user.role.editor';
				$editors = &$editorSubmissionDao->getUsersNotAssignedToArticle($journal->getJournalId(), $articleId, RoleDAO::getRoleIdFromPath('editor'), $searchType, $search, $searchMatch, $rangeInfo);
			} else {
				$roleName = 'user.role.sectionEditor';
				$editors = &$editorSubmissionDao->getUsersNotAssignedToArticle($journal->getJournalId(), $articleId, RoleDAO::getRoleIdFromPath('sectionEditor'), $searchType, $search, $searchMatch, $rangeInfo);
			}

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign_by_ref('editors', $editors);
			$templateMgr->assign('roleName', $roleName);
			$templateMgr->assign('articleId', $articleId);

			$sectionDao = &DAORegistry::getDAO('SectionDAO');
			$sectionEditorSections = &$sectionDao->getEditorSections($journal->getJournalId());

			$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
			$editorStatistics = $editAssignmentDao->getEditorStatistics($journal->getJournalId());

			$templateMgr->assign_by_ref('editorSections', $sectionEditorSections);
			$templateMgr->assign('editorStatistics', $editorStatistics);

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $search);
			$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
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
			$articleFileManager = &new ArticleFileManager($articleId);
			$articleFileManager->deleteArticleTree();

			// Delete article database entries
			$articleDao->deleteArticleById($articleId);
		}

		Request::redirect(null, null, 'submissions', 'submissionsArchives');
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
	function setupTemplate($level = EDITOR_SECTION_HOME, $articleId = 0, $parentPage = null) {
		// Layout Editors have access to some Issue Mgmt functions. Make sure we give them
		// the appropriate breadcrumbs and sidebar.
		$isLayoutEditor = Request::getRequestedPage() == 'layoutEditor';

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();

		if ($level==EDITOR_SECTION_HOME) $pageHierarchy = array(array(Request::url(null, 'user'), 'navigation.user'));
		else if ($level==EDITOR_SECTION_SUBMISSIONS) $pageHierarchy = array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'editor'), 'user.role.editor'), array(Request::url(null, 'editor', 'submissions'), 'article.submissions'));
		else if ($level==EDITOR_SECTION_ISSUES) $pageHierarchy = array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $isLayoutEditor?'layoutEditor':'editor'), $isLayoutEditor?'user.role.layoutEditor':'user.role.editor'), array(Request::url(null, $isLayoutEditor?'layoutEditor':'editor', 'futureIssues'), 'issue.issues'));

		import('submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'editor');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	//
	// Issue
	//
	function futureIssues() {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::futureIssues();
	}

	function backIssues() {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::backIssues();
	}

	function removeIssue($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::removeIssue($args);
		Request::redirect(null, null, 'issueToc');
	}

	function createIssue() {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::createIssue();
	}	

	function saveIssue() {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::saveIssue();
	}

	function issueData($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::issueData($args);
	}	

	function editIssue($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::editIssue($args);
	}	

	function removeCoverPage($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::removeCoverPage($args);
	}

	function removeStyleFile($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::removeStyleFile($args);
	}	

	function issueToc($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::issueToc($args);
	}

	function updateIssueToc($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::updateIssueToc($args);
	}

	function setCurrentIssue($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::setCurrentIssue($args);
	}

	function moveIssue($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::moveIssue($args);
	}

	function resetIssueOrder($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::resetIssueOrder($args);
	}

	function moveSectionToc($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::moveSectionToc($args);
	}

	function resetSectionOrder($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::resetSectionOrder($args);
	}


	function moveArticleToc($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::moveArticleToc($args);
	}	

	function publishIssue($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::publishIssue($args);
	}

	function notifyUsers($args) {
		import('pages.editor.IssueManagementHandler');
		IssueManagementHandler::notifyUsers($args);
	}

}

?>
