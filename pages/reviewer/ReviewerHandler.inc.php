<?php

/**
 * @file pages/reviewer/ReviewerHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for reviewer functions.
 */

import('classes.submission.reviewer.ReviewerAction');
import('classes.handler.Handler');

class ReviewerHandler extends Handler {
	/** user associated with the request **/
	var $user;

	/** submission associated with the request **/
	var $submission;

	/**
	 * Constructor
	 */
	function ReviewerHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorJournal($this));
	}

	/**
	 * Display reviewer index page.
	 */
	function index($args, $request) {
		$this->validate($request);
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$user =& $request->getUser();
		$reviewerSubmissionDao =& DAORegistry::getDAO('ReviewerSubmissionDAO');
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

		if ($sort == 'decision') {
			$submissions = $reviewerSubmissionDao->getReviewerSubmissionsByReviewerId($user->getId(), $journal->getId(), $active, $rangeInfo);

			// Sort all submissions by status, which is too complex to do in the DB
			$submissionsArray = $submissions->toArray();
			$compare = create_function('$s1, $s2', 'return strcmp($s1->getMostRecentDecision(), $s2->getMostRecentDecision());');
			usort ($submissionsArray, $compare);
			if($sortDirection == SORT_DIRECTION_DESC) {
				$submissionsArray = array_reverse($submissionsArray);
			}
			// Convert submission array back to an ItemIterator class
			import('lib.pkp.classes.core.ArrayItemIterator');
			$submissions =& ArrayItemIterator::fromRangeInfo($submissionsArray, $rangeInfo);
		}  else {
			$submissions = $reviewerSubmissionDao->getReviewerSubmissionsByReviewerId($user->getId(), $journal->getId(), $active, $rangeInfo, $sort, $sortDirection);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign_by_ref('submissions', $submissions);

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));
		$templateMgr->assign('helpTopicId', 'editorial.reviewersRole.submissions');
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('reviewer/index.tpl');
	}

	/**
	 * Used by subclasses to validate access keys when they are allowed.
	 * @param $userId int The user this key refers to
	 * @param $reviewId int The ID of the review this key refers to
	 * @param $newKey string The new key name, if one was supplied; otherwise, the existing one (if it exists) is used
	 * @return object Valid user object if the key was valid; otherwise NULL.
	 */
	function &validateAccessKey($request, $userId, $reviewId, $newKey = null) {
		$journal =& $request->getJournal();
		if (!$journal || !$journal->getSetting('reviewerAccessKeysEnabled')) {
			$accessKey = false;
			return $accessKey;
		}

		define('REVIEWER_ACCESS_KEY_SESSION_VAR', 'ReviewerAccessKey');

		import('lib.pkp.classes.security.AccessKeyManager');
		$accessKeyManager = new AccessKeyManager();

		$session =& $request->getSession();
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
		parent::setupTemplate();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_OJS_EDITOR);
		$templateMgr =& TemplateManager::getManager();
		$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'reviewer'), 'user.role.reviewer'))
				: array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'reviewer'), 'user.role.reviewer'));

		if ($articleId && $reviewId) {
			$pageHierarchy[] = array(Request::url(null, 'reviewer', 'submission', $reviewId), "#$articleId", true);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	//
	// Validation
	//

	/**
	 * Validate that the user is an assigned reviewer for
	 * the article.
	 * Redirects to reviewer index page if validation fails.
	 * @param $request PKPRequest
	 * @param $reviewId int optional
	 */
	function validate($request, $reviewId = null) {
		if ($reviewId !== null) {
			$reviewerSubmissionDao =& DAORegistry::getDAO('ReviewerSubmissionDAO');
			$journal =& $request->getJournal();
			$user =& $request->getUser();

			$isValid = true;
			$newKey = $request->getUserVar('key');

			$reviewerSubmission =& $reviewerSubmissionDao->getReviewerSubmission($reviewId);

			if (!$reviewerSubmission || $reviewerSubmission->getJournalId() != $journal->getId()) {
				$isValid = false;
			} elseif ($user && empty($newKey)) {
				if ($reviewerSubmission->getReviewerId() != $user->getId()) {
					$isValid = false;
				}
			} else {
				$user =& SubmissionReviewHandler::validateAccessKey($request, $reviewerSubmission->getReviewerId(), $reviewId, $newKey);
				if (!$user) $isValid = false;
			}

			if (!$isValid) {
				$request->redirect(null, $request->getRequestedPage());
			}

			$this->submission =& $reviewerSubmission;
			$this->user =& $user;
			return true;
		}

		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_REVIEWER)));
		parent::validate();
	}
}

?>
