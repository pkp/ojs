<?php

/**
 * @file pages/editor/EditorHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditorHandler
 * @ingroup pages_editor
 *
 * @brief Handle requests for editor functions.
 */

import('pages.sectionEditor.SectionEditorHandler');

define('EDITOR_SECTION_HOME', 0);
define('EDITOR_SECTION_SUBMISSIONS', 1);
define('EDITOR_SECTION_ISSUES', 2);

// Filter editor
define('FILTER_EDITOR_ALL', 0);
define('FILTER_EDITOR_ME', 1);

import ('classes.submission.editor.EditorAction');

class EditorHandler extends SectionEditorHandler {
	/**
	 * Constructor
	 **/
	function EditorHandler() {
		parent::SectionEditorHandler();

		$this->addCheck(new HandlerValidatorJournal($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_EDITOR)));
	}

	/**
	 * Displays the editor role selection page.
	 */

	function index($args, $request) {
		$this->validate();
		$this->setupTemplate(EDITOR_SECTION_HOME);

		$templateMgr =& TemplateManager::getManager();
		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$user =& $request->getUser();

		$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		$sections =& $sectionDao->getSectionTitles($journal->getId());
		$templateMgr->assign('sectionOptions', array(0 => AppLocale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign('fieldOptions', $this->_getSearchFieldOptions());
		$templateMgr->assign('dateFieldOptions', $this->_getDateFieldOptions());

		// Bring in the print_issue_id function (FIXME?)
		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));

		// If a search was performed, get the necessary info.
		if (array_shift($args) == 'search') {
			$rangeInfo = $this->getRangeInfo('submissions');

			// Get the user's search conditions, if any
			$searchField = $request->getUserVar('searchField');
			$dateSearchField = $request->getUserVar('dateSearchField');
			$searchMatch = $request->getUserVar('searchMatch');
			$search = $request->getUserVar('search');

			$sort = $request->getUserVar('sort');
			$sort = isset($sort) ? $sort : 'id';
			$sortDirection = $request->getUserVar('sortDirection');
			$sortDirection = (isset($sortDirection) && ($sortDirection == SORT_DIRECTION_ASC || $sortDirection == SORT_DIRECTION_DESC)) ? $sortDirection : SORT_DIRECTION_ASC;

			$fromDate = $request->getUserDateVar('dateFrom', 1, 1);
			if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
			$toDate = $request->getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
			if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

			if ($sort == 'status') {
				$rawSubmissions =& $editorSubmissionDao->_getUnfilteredEditorSubmissions(
					$journal->getId(),
					$request->getUserVar('section'),
					0,
					$searchField,
					$searchMatch,
					$search,
					$dateSearchField,
					$fromDate,
					$toDate,
					null,
					null,
					$sort,
					$sortDirection
				);
				$submissions = new DAOResultFactory($rawSubmissions, $editorSubmissionDao, '_returnEditorSubmissionFromRow');

				// Sort all submissions by status, which is too complex to do in the DB
				$submissionsArray = $submissions->toArray();
				$compare = create_function('$s1, $s2', 'return strcmp($s1->getSubmissionStatus(), $s2->getSubmissionStatus());');
				usort ($submissionsArray, $compare);
				if($sortDirection == SORT_DIRECTION_DESC) {
					$submissionsArray = array_reverse($submissionsArray);
				}
				// Convert submission array back to an ItemIterator class
				import('lib.pkp.classes.core.ArrayItemIterator');
				$submissions =& ArrayItemIterator::fromRangeInfo($submissionsArray, $rangeInfo);
			} else {
				$rawSubmissions =& $editorSubmissionDao->_getUnfilteredEditorSubmissions(
					$journal->getId(),
					$request->getUserVar('section'),
					0,
					$searchField,
					$searchMatch,
					$search,
					$dateSearchField,
					$fromDate,
					$toDate,
					null,
					$rangeInfo,
					$sort,
					$sortDirection
				);
				$submissions = new DAOResultFactory($rawSubmissions, $editorSubmissionDao, '_returnEditorSubmissionFromRow');
			}


			// If only result is returned from a search, fast-forward to it
			if ($search && $submissions && $submissions->getCount() == 1) {
				$submission =& $submissions->next();
				$request->redirect(null, null, 'submission', array($submission->getId()));
			}

			$templateMgr->assign_by_ref('submissions', $submissions);
			$templateMgr->assign('section', $request->getUserVar('section'));

			// Set search parameters
			foreach ($this->_getSearchFormDuplicateParameters() as $param)
				$templateMgr->assign($param, $request->getUserVar($param));

			$templateMgr->assign('dateFrom', $fromDate);
			$templateMgr->assign('dateTo', $toDate);
			$templateMgr->assign('displayResults', true);
			$templateMgr->assign('sort', $sort);
			$templateMgr->assign('sortDirection', $sortDirection);
		}

		$submissionsCount =& $editorSubmissionDao->getEditorSubmissionsCount($journal->getId());
		$templateMgr->assign('submissionsCount', $submissionsCount);
		$templateMgr->assign('helpTopicId', 'editorial.editorsRole');
		$templateMgr->display('editor/index.tpl');
	}

	/**
	 * Display editor submission queue pages.
	 */
	function submissions($args, $request) {
		$this->validate();
		$this->setupTemplate(EDITOR_SECTION_SUBMISSIONS);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$user =& $request->getUser();

		$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		$sections =& $sectionDao->getSectionTitles($journalId);

		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'id';
		$sortDirection = $request->getUserVar('sortDirection');
		$sortDirection = (isset($sortDirection) && ($sortDirection == SORT_DIRECTION_ASC || $sortDirection == SORT_DIRECTION_DESC)) ? $sortDirection : SORT_DIRECTION_ASC;

		$filterEditorOptions = array(
			FILTER_EDITOR_ALL => AppLocale::Translate('editor.allEditors'),
			FILTER_EDITOR_ME => AppLocale::Translate('editor.me')
		);

		$filterSectionOptions = array(
			FILTER_SECTION_ALL => AppLocale::Translate('editor.allSections')
		) + $sections;

		// Get the user's search conditions, if any
		$searchField = $request->getUserVar('searchField');
		$dateSearchField = $request->getUserVar('dateSearchField');
		$searchMatch = $request->getUserVar('searchMatch');
		$search = $request->getUserVar('search');

		$fromDate = $request->getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = $request->getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$rangeInfo = $this->getRangeInfo('submissions');

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

		$filterEditor = $request->getUserVar('filterEditor');
		if ($filterEditor != '' && array_key_exists($filterEditor, $filterEditorOptions)) {
			$user->updateSetting('filterEditor', $filterEditor, 'int', $journalId);
		} else {
			$filterEditor = $user->getSetting('filterEditor', $journalId);
			if ($filterEditor == null) {
				$filterEditor = FILTER_EDITOR_ALL;
				$user->updateSetting('filterEditor', $filterEditor, 'int', $journalId);
			}
		}

		if ($filterEditor == FILTER_EDITOR_ME) {
			$editorId = $user->getId();
		} else {
			$editorId = FILTER_EDITOR_ALL;
		}

		$filterSection = $request->getUserVar('filterSection');
		if ($filterSection != '' && array_key_exists($filterSection, $filterSectionOptions)) {
			$user->updateSetting('filterSection', $filterSection, 'int', $journalId);
		} else {
			$filterSection = $user->getSetting('filterSection', $journalId);
			if ($filterSection == null) {
				$filterSection = FILTER_SECTION_ALL;
				$user->updateSetting('filterSection', $filterSection, 'int', $journalId);
			}
		}

		$submissions =& $editorSubmissionDao->$functionName(
			$journalId,
			$filterSection,
			$editorId,
			$searchField,
			$searchMatch,
			$search,
			$dateSearchField,
			$fromDate,
			$toDate,
			$rangeInfo,
			$sort,
			$sortDirection
		);

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('editor', $user->getFullName());
		$templateMgr->assign('editorOptions', $filterEditorOptions);
		$templateMgr->assign('sectionOptions', $filterSectionOptions);

		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('filterEditor', $filterEditor);
		$templateMgr->assign('filterSection', $filterSection);

		// Set search parameters
		foreach ($this->_getSearchFormDuplicateParameters() as $param)
			$templateMgr->assign($param, $request->getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('fieldOptions', $this->_getSearchFieldOptions());
		$templateMgr->assign('dateFieldOptions', $this->_getDateFieldOptions());

		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));

		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('editor/submissions.tpl');
	}

	/**
	 * Get the list of parameter names that should be duplicated when
	 * displaying the search form (i.e. made available to the template
	 * based on supplied user data).
	 * @return array
	 */
	function _getSearchFormDuplicateParameters() {
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
	function _getSearchFieldOptions() {
		return array(
			SUBMISSION_FIELD_TITLE => 'article.title',
			SUBMISSION_FIELD_ID => 'article.submissionId',
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
	function _getDateFieldOptions() {
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
	function setEditorFlags($args, $request) {
		$this->validate();

		$journal =& $request->getJournal();
		$articleId = (int) $request->getUserVar('articleId');

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article =& $articleDao->getArticle($articleId);

		if ($article && $article->getJournalId() === $journal->getId()) {
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByArticleId($articleId);

			while($editAssignment =& $editAssignments->next()) {
				if ($editAssignment->getIsEditor()) continue;

				$canReview = $request->getUserVar('canReview-' . $editAssignment->getEditId()) ? 1 : 0;
				$canEdit = $request->getUserVar('canEdit-' . $editAssignment->getEditId()) ? 1 : 0;

				$editAssignment->setCanReview($canReview);
				$editAssignment->setCanEdit($canEdit);

				$editAssignmentDao->updateEditAssignment($editAssignment);
			}
		}

		$request->redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Delete the specified edit assignment.
	 */
	function deleteEditAssignment($args, $request) {
		$this->validate();

		$journal =& $request->getJournal();
		$editId = (int) array_shift($args);

		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignment =& $editAssignmentDao->getEditAssignment($editId);

		if ($editAssignment) {
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle($editAssignment->getArticleId());

			if ($article && $article->getJournalId() === $journal->getId()) {
				$editAssignmentDao->deleteEditAssignmentById($editAssignment->getEditId());
				$request->redirect(null, null, 'submission', $article->getId());
			}
		}

		$request->redirect(null, null, 'submissions');
	}

	/**
	 * Assigns the selected editor to the submission.
	 */
	function assignEditor($args, $request) {
		$this->validate();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER); // manager.people.noneEnrolled

		$journal =& $request->getJournal();
		$articleId = $request->getUserVar('articleId');
		$editorId = $request->getUserVar('editorId');
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$isSectionEditor = $roleDao->userHasRole($journal->getId(), $editorId, ROLE_ID_SECTION_EDITOR);
		$isEditor = $roleDao->userHasRole($journal->getId(), $editorId, ROLE_ID_EDITOR);

		if (isset($editorId) && $editorId != null && ($isEditor || $isSectionEditor)) {
			// A valid section editor has already been chosen;
			// either prompt with a modifiable email or, if this
			// has been done, send the email and store the editor
			// selection.

			$this->setupTemplate(EDITOR_SECTION_SUBMISSIONS, $articleId, 'summary');

			// FIXME: Prompt for due date.
			if (EditorAction::assignEditor($articleId, $editorId, $isEditor, $request->getUserVar('send'), $request)) {
				Request::redirect(null, null, 'submission', $articleId);
			}
		} else {
			// Allow the user to choose a section editor or editor.
			$this->setupTemplate(EDITOR_SECTION_SUBMISSIONS, $articleId, 'summary');

			$searchType = null;
			$searchMatch = null;
			$search = $request->getUserVar('search');
			$searchInitial = $request->getUserVar('searchInitial');
			if (!empty($search)) {
				$searchType = $request->getUserVar('searchField');
				$searchMatch = $request->getUserVar('searchMatch');

			} elseif (!empty($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$rangeInfo =& $this->getRangeInfo('editors');
			$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');

			if (isset($args[0]) && $args[0] === 'editor') {
				$roleName = 'user.role.editor';
				$rolePath = 'editor';
				$editors =& $editorSubmissionDao->getUsersNotAssignedToArticle($journal->getId(), $articleId, RoleDAO::getRoleIdFromPath('editor'), $searchType, $search, $searchMatch, $rangeInfo);
			} else {
				$roleName = 'user.role.sectionEditor';
				$rolePath = 'sectionEditor';
				$editors =& $editorSubmissionDao->getUsersNotAssignedToArticle($journal->getId(), $articleId, RoleDAO::getRoleIdFromPath('sectionEditor'), $searchType, $search, $searchMatch, $rangeInfo);
			}

			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign_by_ref('editors', $editors);
			$templateMgr->assign('roleName', $roleName);
			$templateMgr->assign('rolePath', $rolePath);
			$templateMgr->assign('articleId', $articleId);

			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$sectionEditorSections =& $sectionDao->getEditorSections($journal->getId());

			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editorStatistics = $editAssignmentDao->getEditorStatistics($journal->getId());

			$templateMgr->assign_by_ref('editorSections', $sectionEditorSections);
			$templateMgr->assign('editorStatistics', $editorStatistics);

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $search);
			$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

			$templateMgr->assign('fieldOptions', array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole.submissionSummary.submissionManagement');
			$templateMgr->display('editor/selectSectionEditor.tpl');
		}
	}

	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($articleId);
		parent::setupTemplate(true);

		$journal =& $request->getJournal();

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article =& $articleDao->getArticle($articleId);

		$status = $article->getStatus();

		if ($article->getJournalId() == $journal->getId() && ($status == STATUS_DECLINED || $status == STATUS_ARCHIVED)) {
			// Delete article files
			import('classes.file.ArticleFileManager');
			$articleFileManager = new ArticleFileManager($articleId);
			$articleFileManager->deleteArticleTree();

			// Delete article database entries
			$articleDao->deleteArticleById($articleId);
		}

		$request->redirect(null, null, 'submissions', 'submissionsArchives');
	}

	/**
	 * Setup common template variables.
	 * @param $level int set to 0 if caller is at the same level as this handler in the hierarchy; otherwise the number of levels below this handler
	 */
	function setupTemplate($level = EDITOR_SECTION_HOME, $articleId = 0, $parentPage = null) {
		parent::setupTemplate();

		// Layout Editors have access to some Issue Mgmt functions. Make sure we give them
		// the appropriate breadcrumbs and sidebar.
		$isLayoutEditor = Request::getRequestedPage() == 'layoutEditor';

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();

		if ($level==EDITOR_SECTION_HOME) $pageHierarchy = array(array(Request::url(null, 'user'), 'navigation.user'));
		else if ($level==EDITOR_SECTION_SUBMISSIONS) $pageHierarchy = array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'editor'), 'user.role.editor'), array(Request::url(null, 'editor', 'submissions'), 'article.submissions'));
		else if ($level==EDITOR_SECTION_ISSUES) $pageHierarchy = array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $isLayoutEditor?'layoutEditor':'editor'), $isLayoutEditor?'user.role.layoutEditor':'user.role.editor'), array(Request::url(null, $isLayoutEditor?'layoutEditor':'editor', 'futureIssues'), 'issue.issues'));

		import('classes.submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'editor');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
