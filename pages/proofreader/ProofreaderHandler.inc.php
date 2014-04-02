<?php

/**
 * @file pages/proofreader/ProofreaderHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProofreaderHandler
 * @ingroup pages_proofreader
 *
 * @brief Handle requests for proofreader functions.
 */

import('classes.submission.proofreader.ProofreaderAction');
import('classes.handler.Handler');

class ProofreaderHandler extends Handler {
	/** submission associated with the request **/
	var $submission;

	/**
	 * Constructor
	 */
	function ProofreaderHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorJournal($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_PROOFREADER)));
	}

	/**
	 * Display proofreader index page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		$this->validate($request);
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$user =& $request->getUser();
		$proofreaderSubmissionDao =& DAORegistry::getDAO('ProofreaderSubmissionDAO');

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

		$submissions = $proofreaderSubmissionDao->getSubmissions($user->getId(), $journal->getId(), $searchField, $searchMatch, $search, $dateSearchField, $fromDate, $toDate, $active, $rangeInfo, $sort, $sortDirection);

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
		$templateMgr->assign('helpTopicId', 'editorial.proofreadersRole.submissions');
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('proofreader/index.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null, $showSidebar = true) {
		parent::setupTemplate();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_OJS_EDITOR);
		$templateMgr =& TemplateManager::getManager();
		$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'proofreader'), 'user.role.proofreader'))
				: array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'proofreader'), 'user.role.proofreader'));

		import('classes.submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'proofreader');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	/**
	 * Display submission management instructions.
	 * @param $args array
	 * @param $requet PKPRequest
	 */
	function instructions($args, &$request) {
		$this->setupTemplate();
		if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('proof'))) {
			$request->redirect(null, $request->getRequestedPage());
		}
	}

	/**
	 * Validate that the user is the assigned proofreader for the submission,
	 * if a submission ID is specified.
	 * Redirects to proofreader index page if validation fails.
	 * @param $articleId int optional
	 */
	function validate(&$request, $articleId = null) {
		parent::validate();

		if ($articleId !== null) {
			$isValid = false;

			$journal =& $request->getJournal();
			$user =& $request->getUser();

			$proofreaderDao =& DAORegistry::getDAO('ProofreaderSubmissionDAO');
			$signoffDao =& DAORegistry::getDAO('SignoffDAO');
			$submission =& $proofreaderDao->getSubmission($articleId, $journal->getId());

			if (isset($submission)) {
				$proofSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $articleId);
				if ($proofSignoff->getUserId() == $user->getId()) {
					$isValid = true;
				}
			}

			if (!$isValid) {
				$request->redirect(null, $request->getRequestedPage());
			}

			$this->submission =& $submission;
		}

		return true;
	}
}

?>
