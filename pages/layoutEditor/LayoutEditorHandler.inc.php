<?php

/**
 * @file pages/layoutEditor/LayoutEditorHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LayoutEditorHandler
 * @ingroup pages_layoutEditor
 *
 * @brief Handle requests for layout editor functions.
 */

import('classes.submission.layoutEditor.LayoutEditorAction');
import('classes.submission.proofreader.ProofreaderAction');
import('classes.handler.Handler');

class LayoutEditorHandler extends Handler {
	/** submission associated with the request **/
	var $submission;

	/**
	 * Constructor
	 */
	function LayoutEditorHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorJournal($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_LAYOUT_EDITOR)));
	}

	/**
	 * Display layout editor index page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		$this->validate($request);
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'editorial.layoutEditorsRole');
		$templateMgr->display('layoutEditor/index.tpl');
	}

	/**
	 * Display layout editor submissions page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissions($args, &$request) {
		$this->validate($request);
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$user =& $request->getUser();
		$layoutEditorSubmissionDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}

		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'title';
		$sortDirection = $request->getUserVar('sortDirection');

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
		$submissions = $layoutEditorSubmissionDao->getSubmissions($user->getId(), $journal->getId(), $searchField, $searchMatch, $search, $dateSearchField, $fromDate, $toDate, $active, $rangeInfo, $sort, $sortDirection);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign_by_ref('submissions', $submissions);

		// Set search parameters
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search',
			'dateFromMonth', 'dateFromDay', 'dateFromYear',
			'dateToMonth', 'dateToDay', 'dateToYear',
			'dateSearchField'
		);
		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, $request->getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('fieldOptions', array(
			SUBMISSION_FIELD_TITLE => 'article.title',
			SUBMISSION_FIELD_ID => 'article.submissionId',
			SUBMISSION_FIELD_AUTHOR => 'user.role.author',
			SUBMISSION_FIELD_EDITOR => 'user.role.editor'
		));
		$templateMgr->assign('dateFieldOptions', array(
			SUBMISSION_FIELD_DATE_SUBMITTED => 'submissions.submitted',
			SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE => 'submissions.copyeditComplete',
			SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE => 'submissions.layoutComplete',
			SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE => 'submissions.proofreadingComplete'
		));

		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));
		$templateMgr->assign('helpTopicId', 'editorial.layoutEditorsRole.submissions');
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('layoutEditor/submissions.tpl');
	}

	/**
	 * Display Future Isshes page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function futureIssues($args, &$request) {
		$this->validate($request);
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$rangeInfo = $this->getRangeInfo('issues');
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $issueDao->getUnpublishedIssues($journal->getId(), $rangeInfo));
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->display('layoutEditor/futureIssues.tpl');
	}

	/**
	 * Displays the listings of back (published) issues
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function backIssues($args, &$request) {
		$this->validate($request);
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$rangeInfo = $this->getRangeInfo('issues');
		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'title';
		$sortDirection = $request->getUserVar('sortDirection');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $issueDao->getPublishedIssues($journal->getId(), $rangeInfo));

		$allIssuesIterator = $issueDao->getPublishedIssues($journal->getId());
		$issueMap = array();
		while ($issue =& $allIssuesIterator->next()) {
			$issueMap[$issue->getId()] = $issue->getIssueIdentification();
			unset($issue);
		}
		$templateMgr->assign('allIssues', $issueMap);

		$currentIssue =& $issueDao->getCurrentIssue($journal->getId());
		$currentIssueId = $currentIssue?$currentIssue->getId():null;
		$templateMgr->assign('currentIssueId', $currentIssueId);

		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->assign('usesCustomOrdering', $issueDao->customIssueOrderingExists($journal->getId()));
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('layoutEditor/backIssues.tpl');
	}

	/**
	 * Sets proofreader completion date
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function completeProofreader($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');

		$this->validate($request, $articleId);
		$this->setupTemplate($request, true);

		// set the date notified for this signoff so proofreading can no longer be initiated.
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$signoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $articleId);
		$signoff->setDateNotified(Core::getCurrentDate());
		$signoffDao->updateObject($signoff);

		$signoff = $signoffDao->build('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
		$signoff->setDateCompleted(Core::getCurrentDate());
		$signoffDao->updateObject($signoff);

		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_COMPLETE', $request, $request->getUserVar('send')?'':$request->url(null, 'layoutEditor', 'completeProofreader'))) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 * @param $articleId int optional
	 * @param $parentPage string optional
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null) {
		parent::setupTemplate();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_OJS_EDITOR);
		$templateMgr =& TemplateManager::getManager();
		$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'layoutEditor'), 'user.role.layoutEditor'))
				: array(array(Request::url(null, 'user'), 'navigation.user'));

		import('classes.submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'layoutEditor');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	/**
	 * Display submission management instructions.
	 * @param $args (type)
	 * @param $request PKPRequest
	 */
	function instructions($args, &$request) {
		$this->setupTemplate();
		import('classes.submission.proofreader.ProofreaderAction');
		if (!isset($args[0]) || !LayoutEditorAction::instructions($args[0], array('layout', 'proof', 'referenceLinking'))) {
			$request->redirect(null, $request->getRequestedPage());
		}
	}


	//
	// Validation
	//


	/**
	 * Validate that the user is the assigned layout editor for the submission.
	 * Redirects to layoutEditor index page if validation fails.
	 * @param $request PKPRequest
	 * @param $articleId int optional the submission being edited
	 * @param $checkEdit boolean check if editor has editing permissions
	 */
	function validate($request, $articleId = null, $checkEdit = false) {
		parent::validate();

		if ($articleId !== null) {
			$isValid = false;

			$journal =& $request->getJournal();
			$user =& $request->getUser();

			$layoutDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');
			$signoffDao =& DAORegistry::getDAO('SignoffDAO');
			$submission =& $layoutDao->getSubmission($articleId, $journal->getId());

			if (isset($submission)) {
				$layoutSignoff = $signoffDao->getBySymbolic('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
				if (!isset($layoutSignoff)) $isValid = false;
				elseif ($layoutSignoff->getUserId() == $user->getId()) {
					if ($checkEdit) {
						$isValid = $this->_layoutEditingEnabled($submission);
					} else {
						$isValid = true;
					}
				}
			}

			if (!$isValid) {
				$request->redirect(null, $request->getRequestedPage());
			}

			$this->submission =& $submission;
			return true;
		}
	}

	/**
	 * Check if a layout editor is allowed to make changes to the submission.
	 * This is allowed if there is an outstanding galley creation or layout editor
	 * proofreading request.
	 * @param $submission LayoutEditorSubmission
	 * @return boolean true if layout editor can modify the submission
	 */
	function _layoutEditingEnabled(&$submission) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$layoutEditorProofreadSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $submission->getId());
		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $submission->getId());

		return(($layoutSignoff->getDateNotified() != null
			&& $layoutSignoff->getDateCompleted() == null)
		|| ($layoutEditorProofreadSignoff->getDateNotified() != null
			&& $layoutEditorProofreadSignoff->getDateCompleted() == null));
	}
}

?>
