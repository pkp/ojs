<?php

/**
 * @file pages/author/AuthorHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for journal author functions. 
 */

import('classes.submission.author.AuthorAction');
import('classes.handler.Handler');

class AuthorHandler extends Handler {
	/** @var $submission AuthorSubmission */
	var $submission;

	/**
	 * Constructor
	 */
	function AuthorHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorJournal($this));		
	}

	/**
	 * Display journal author index page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request);
		
		$journal =& $request->getJournal();

		$user =& $request->getUser();
		$rangeInfo =& $this->getRangeInfo('submissions');
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');

		$page = array_shift($args);
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
		$sortDirection = (isset($sortDirection) && ($sortDirection == SORT_DIRECTION_ASC || $sortDirection == SORT_DIRECTION_DESC)) ? $sortDirection : SORT_DIRECTION_ASC;

		if ($sort == 'status') {
			// FIXME Does not pass $rangeInfo else we only get partial results
			$unsortedSubmissions = $authorSubmissionDao->getAuthorSubmissions($user->getId(), $journal->getId(), $active, null, $sort, $sortDirection);

			// Sort all submissions by status, which is too complex to do in the DB
			$submissionsArray = $unsortedSubmissions->toArray();
			$compare = create_function('$s1, $s2', 'return strcmp($s1->getSubmissionStatus(), $s2->getSubmissionStatus());');
			usort ($submissionsArray, $compare);
			if($sortDirection == SORT_DIRECTION_DESC) {
				$submissionsArray = array_reverse($submissionsArray);
			}
			// Convert submission array back to an ItemIterator class
			import('lib.pkp.classes.core.ArrayItemIterator');
			$submissions =& ArrayItemIterator::fromRangeInfo($submissionsArray, $rangeInfo);
		} else {
			$submissions = $authorSubmissionDao->getAuthorSubmissions($user->getId(), $journal->getId(), $active, $rangeInfo, $sort, $sortDirection);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		if (!$active) {
			// Make view counts available if enabled.
			$templateMgr->assign('statViews', $journal->getSetting('statViews'));
		}
		$templateMgr->assign_by_ref('submissions', $submissions);

		// assign payment 
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);

		if ( $paymentManager->isConfigured() ) {		
			$templateMgr->assign('submissionEnabled', $paymentManager->submissionEnabled());
			$templateMgr->assign('fastTrackEnabled', $paymentManager->fastTrackEnabled());
			$templateMgr->assign('publicationEnabled', $paymentManager->publicationEnabled());
			
			$completedPaymentDAO =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
			$templateMgr->assign_by_ref('completedPaymentDAO', $completedPaymentDAO);
		}

		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.submissions');
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('author/index.tpl');
	}

	/**
	 * Validate that user has author permissions in the selected journal
	 * and, optionally, for the specified article.
	 * Redirects to user index page if not properly authenticated.
	 * @param $request PKPRequest
	 * @param $articleId int optional
	 * @param $reason string optional
	 */
	function validate(&$request, $articleId = null, $reason = null) {
		$this->addCheck(new HandlerValidatorRoles($this, true, $reason, null, array(ROLE_ID_AUTHOR)));		

		if ($articleId !== null) {
			$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$journal =& $request->getJournal();
			$user =& $request->getUser();

			$isValid = true;

			$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($articleId);

			if ($authorSubmission == null) {
				$isValid = false;
			} else if ($authorSubmission->getJournalId() != $journal->getId()) {
				$isValid = false;
			} else {
				if (!$user || ($authorSubmission->getUserId() != $user->getId())) {
					$isValid = false;
				}
			}

			if (!$isValid) {
				$request->redirect(null, $request->getRequestedPage());
			}

			$this->submission =& $authorSubmission;
		}

		return parent::validate();
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($request, $subclass = false, $articleId = 0, $parentPage = null) {
		parent::setupTemplate();
		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_AUTHOR, LOCALE_COMPONENT_PKP_SUBMISSION);
		$templateMgr =& TemplateManager::getManager();

		$pageHierarchy = $subclass ? array(array($request->url(null, 'user'), 'navigation.user'), array($request->url(null, 'author'), 'user.role.author'), array($request->url(null, 'author'), 'article.submissions'))
			: array(array($request->url(null, 'user'), 'navigation.user'), array($request->url(null, 'author'), 'user.role.author'));

		import('classes.submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'author');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	/**
	 * Display submission management instructions.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function instructions($args, &$request) {
		import('classes.submission.proofreader.ProofreaderAction');
		if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('copy', 'proof'))) {
			$request->redirect(null, null, 'index');
		}
	}
}

?>
