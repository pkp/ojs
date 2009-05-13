<?php

/**
 * @file AuthorHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for journal author functions. 
 */

// $Id$


import('submission.author.AuthorAction');
import('handler.Handler');

class AuthorHandler extends Handler {
	/**
	 * Constructor
	 **/
	function AuthorHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorJournal($this));		
	}

	/**
	 * Display journal author index page.
	 */
	function index($args) {
		$this->validate();
		$this->setupTemplate();
		
		$journal =& Request::getJournal();

		$user =& Request::getUser();
		$rangeInfo =& Handler::getRangeInfo('submissions');
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}

		$submissions = $authorSubmissionDao->getAuthorSubmissions($user->getId(), $journal->getJournalId(), $active, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		if (!$active) {
			// Make view counts available if enabled.
			$templateMgr->assign('statViews', $journal->getSetting('statViews'));
		}
		$templateMgr->assign_by_ref('submissions', $submissions);

		// assign payment 
		import('payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();

		if ( $paymentManager->isConfigured() ) {		
			$templateMgr->assign('submissionEnabled', $paymentManager->submissionEnabled());
			$templateMgr->assign('fastTrackEnabled', $paymentManager->fastTrackEnabled());
			$templateMgr->assign('publicationEnabled', $paymentManager->publicationEnabled());
			
			$completedPaymentDAO =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
			$templateMgr->assign_by_ref('completedPaymentDAO', $completedPaymentDAO);
		} 				

		import('issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.submissions');
		$templateMgr->display('author/index.tpl');
	}

	/**
	 * Validate that user has author permissions in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate($reason = null) {
		$this->addCheck(new HandlerValidatorRoles($this, true, $reason, null, array(ROLE_ID_AUTHOR)));		
		return parent::validate();
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null) {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_OJS_AUTHOR, LOCALE_COMPONENT_PKP_SUBMISSION));
		$templateMgr =& TemplateManager::getManager();

		$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'author'), 'user.role.author'), array(Request::url(null, 'author'), 'article.submissions'))
			: array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'author'), 'user.role.author'));

		import('submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'author');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	/**
	 * Display submission management instructions.
	 * @param $args (type)
	 */
	function instructions($args) {
		import('submission.proofreader.ProofreaderAction');
		if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('copy', 'proof'))) {
			Request::redirect(null, null, 'index');
		}
	}
}

?>
