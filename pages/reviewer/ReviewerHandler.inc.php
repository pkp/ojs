<?php

/**
 * @file ReviewerHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.reviewer
 * @class ReviewerHandler
 *
 * Handle requests for reviewer functions. 
 *
 * $Id$
 */

import('submission.reviewer.ReviewerAction');

class ReviewerHandler extends Handler {

	/**
	 * Display reviewer index page.
	 */
	function index($args) {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate();

		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$rangeInfo = Handler::getRangeInfo('submissions');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}

		$submissions = $reviewerSubmissionDao->getReviewerSubmissionsByReviewerId($user->getUserId(), $journal->getJournalId(), $active, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign_by_ref('submissions', $submissions);

		import('submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

		import('issue.IssueAction');
		$issueAction = &new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));
		$templateMgr->assign('helpTopicId', 'editorial.reviewersRole.submissions');
		$templateMgr->display('reviewer/index.tpl');
	}
	
	/**
	 * Validate that user is a reviewer in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 * Note that subclasses using access keys should not call this method.
	 */
	function validate() {
		parent::validate();
		$journal = &Request::getJournal();

		if (!isset($journal) || !Validation::isReviewer($journal->getJournalId())) {
			Validation::redirectLogin();
		}
	}
	
	/**
	 * Used by subclasses to validate access keys when they are allowed.
	 * @param $userId int The user this key refers to
	 * @param $reviewId int The ID of the review this key refers to
	 * @param $newKey string The new key name, if one was supplied; otherwise, the existing one (if it exists) is used
	 * @return object Valid user object if the key was valid; otherwise NULL.
	 */
	function &validateAccessKey($userId, $reviewId, $newKey = null) {
		$journal =& Request::getJournal();
		if (!$journal || !$journal->getSetting('reviewerAccessKeysEnabled')) {
			$accessKey = false;
			return $accessKey;
		}

		define('REVIEWER_ACCESS_KEY_SESSION_VAR', 'ReviewerAccessKey');

		import('security.AccessKeyManager');
		$accessKeyManager =& new AccessKeyManager();

		$session =& Request::getSession();
		// Check to see if a new access key is being used.
		if (!empty($newKey)) {
			if (Validation::isLoggedIn()) {
				Validation::logout();
			}
			$keyHash = $accessKeyManager->generateKeyHash($newKey);
			$session->setSessionVar(REVIEWER_ACCESS_KEY_SESSION_VAR, $keyHash);
		} else {
			$keyHash = $session->getSessionVar(REVIEWER_ACCESS_KEY_SESSION_VAR);
		}

		// Now that we've gotten the key hash (if one exists), validate it.
		$accessKey =& $accessKeyManager->validateKey(
			'ReviewerContext',
			$userId,
			$keyHash,
			$reviewId
		);

		if ($accessKey) {
			$userDao =& DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getUser($accessKey->getUserId(), false);
			return $user;
		}

		// No valid access key -- return NULL.
		return $accessKey;
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $reviewId = 0) {
		$templateMgr = &TemplateManager::getManager();
		$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'reviewer'), 'user.role.reviewer'))
				: array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'reviewer'), 'user.role.reviewer'));

		if ($articleId && $reviewId) {
			$pageHierarchy[] = array(Request::url(null, 'reviewer', 'submission', $reviewId), "#$articleId", true);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);

		$templateMgr->assign('sidebarTemplate', 'reviewer/navsidebar.tpl');

		$journal = &Request::getJournal();
		$user = &Request::getUser();
		if ($user) {
			$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
			$submissionsCount = $reviewerSubmissionDao->getSubmissionsCount($user->getUserId(), $journal->getJournalId());
			$templateMgr->assign('submissionsCount', $submissionsCount);
		}
	}
	
	//
	// Submission Tracking
	//
	
	function submission($args) {
		import('pages.reviewer.SubmissionReviewHandler');
		SubmissionReviewHandler::submission($args);
	}

	function confirmReview($args) {
		import('pages.reviewer.SubmissionReviewHandler');
		SubmissionReviewHandler::confirmReview($args);
	}
	
	function recordRecommendation() {
		import('pages.reviewer.SubmissionReviewHandler');
		SubmissionReviewHandler::recordRecommendation();
	}
	
	function viewMetadata($args) {
		import('pages.reviewer.SubmissionReviewHandler');
		SubmissionReviewHandler::viewMetadata($args);
	}
	
	function uploadReviewerVersion() {
		import('pages.reviewer.SubmissionReviewHandler');
		SubmissionReviewHandler::uploadReviewerVersion();
	}

	function deleteReviewerVersion($args) {
		import('pages.reviewer.SubmissionReviewHandler');
		SubmissionReviewHandler::deleteReviewerVersion($args);
	}
	
	//
	// Misc.
	//

	function downloadFile($args) {
		import('pages.reviewer.SubmissionReviewHandler');
		SubmissionReviewHandler::downloadFile($args);
	}
	
	//
	// Submission Comments
	//
	
	function viewPeerReviewComments($args) {
		import('pages.reviewer.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewPeerReviewComments($args);
	}
	
	function postPeerReviewComment() {
		import('pages.reviewer.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postPeerReviewComment();
	}

	function editComment($args) {
		import('pages.reviewer.SubmissionCommentsHandler');
		SubmissionCommentsHandler::editComment($args);
	}
	
	function saveComment() {
		import('pages.reviewer.SubmissionCommentsHandler');
		SubmissionCommentsHandler::saveComment();
	}
	
	function deleteComment($args) {
		import('pages.reviewer.SubmissionCommentsHandler');
		SubmissionCommentsHandler::deleteComment($args);
	}
}

?>
