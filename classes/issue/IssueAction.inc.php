<?php

/**
 * @file classes/issue/IssueAction.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueAction
 * @ingroup issue
 * @see Issue
 *
 * @brief IssueAction class.
 */

class IssueAction {

	/**
	 * Constructor.
	 */
	function __construct() {
	}

	/**
	 * Actions.
	 */

	/**
	 * Checks if subscription is required for viewing the issue
	 * @param $issue Issue
	 * @param $journal Journal
	 * @return bool
	 */
	function subscriptionRequired($issue, $journal) {
		assert(is_a($issue, 'Issue'));
		assert(is_a($journal, 'Journal'));
		assert($journal->getId() == $issue->getJournalId());

		// Check subscription state.
		$result = $journal->getData('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION &&
			$issue->getAccessStatus() != ISSUE_ACCESS_OPEN && (
				is_null($issue->getOpenAccessDate()) ||
				strtotime($issue->getOpenAccessDate()) > time()
			);
		HookRegistry::call('IssueAction::subscriptionRequired', array(&$journal, &$issue, &$result));
		return $result;
	}

	/**
	 * Checks if this user is granted reader access to pre-publication articles
	 * based on their roles in the journal (i.e. Manager, Editor, etc).
	 * @param $journal Journal
	 * @param $article Article
	 * @param $user User
	 * @return bool
	 */
	function allowedPrePublicationAccess($journal, $article, $user) {
		if ($this->_roleAllowedPrePublicationAccess($journal, $user)) return true;

		if ($user && $journal) {
			$journalId = $journal->getId();
			$userId = $user->getId();

			$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
			$stageAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($article->getId(), ROLE_ID_AUTHOR, null, $userId);
			$stageAssignment = $stageAssignments->next();
			if ($stageAssignment) return true;
		}
		return false;
	}

	/**
	 * Checks if this user is granted access to pre-publication issue galleys
	 * based on their roles in the journal (i.e. Manager, Editor, etc).
	 * @param $journal object
	 * @param $issue object
	 * @return bool
	 */
	function allowedIssuePrePublicationAccess($journal, $user) {
		return $this->_roleAllowedPrePublicationAccess($journal, $user);
	}

	/**
	 * Checks if user has subscription
	 * @param $user User
	 * @param $journal Journal
	 * @param $issueId int Issue ID (optional)
	 * @param $articleId int Article ID (optional)
	 * @return bool
	 */
	function subscribedUser($user, $journal, $issueId = null, $articleId = null) {
		$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getByArticleId($articleId, null, true);
		$result = false;
		if (isset($user) && isset($journal)) {
			if ($publishedArticle && $this->allowedPrePublicationAccess($journal, $publishedArticle, $user)) {
				 $result = true;
			} else {
				$result = $subscriptionDao->isValidIndividualSubscription($user->getId(), $journal->getId());
			}

			// If no valid subscription, check if there is an expired subscription
			// that was valid during publication date of requested content
			if (!$result && $journal->getData('subscriptionExpiryPartial')) {
				if (isset($articleId)) {
					if (isset($publishedArticle)) {
						import('classes.subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidIndividualSubscription($user->getId(), $journal->getId(), SUBSCRIPTION_DATE_END, $publishedArticle->getDatePublished());
					}
				} else if (isset($issueId)) {
					$issueDao = DAORegistry::getDAO('IssueDAO');
					$issue = $issueDao->getById($issueId);
					if (isset($issue) && $issue->getPublished()) {
						import('classes.subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidIndividualSubscription($user->getId(), $journal->getId(), SUBSCRIPTION_DATE_END, $issue->getDatePublished());
					}
				}
			}
		}
		HookRegistry::call('IssueAction::subscribedUser', array(&$user, &$journal, &$issueId, &$articleId, &$result));
		return $result;
	}

	/**
	 * Checks if remote client domain or ip is allowed
	 * @param $request PKPRequest
	 * @param $journal Journal
	 * @param $issueId int Issue ID (optional)
	 * @param $articleId int Article ID (optional)
	 * @return bool
	 */
	function subscribedDomain($request, $journal, $issueId = null, $articleId = null) {
		$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$result = false;
		if (isset($journal)) {
			$result = $subscriptionDao->isValidInstitutionalSubscription($request->getRemoteDomain(), $request->getRemoteAddr(), $journal->getId());

			// If no valid subscription, check if there is an expired subscription
			// that was valid during publication date of requested content
			if (!$result && $journal->getData('subscriptionExpiryPartial')) {
				if (isset($articleId)) {
					$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
					$publishedArticle = $publishedArticleDao->getByArticleId($articleId, null, true);
					if (isset($publishedArticle)) {
						import('classes.subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidInstitutionalSubscription($request->getRemoteDomain(), $request->getRemoteAddr(), $journal->getId(), SUBSCRIPTION_DATE_END, $publishedArticle->getDatePublished());
					}
				} else if (isset($issueId)) {
					$issueDao = DAORegistry::getDAO('IssueDAO');
					$issue = $issueDao->getById($issueId);
					if (isset($issue) && $issue->getPublished()) {
						import('classes.subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidInstitutionalSubscription($request->getRemoteDomain(), $request->getRemoteAddr(), $journal->getId(), SUBSCRIPTION_DATE_END, $issue->getDatePublished());
					}
				}
			}
		}
		HookRegistry::call('IssueAction::subscribedDomain', array(&$request, &$journal, &$issueId, &$articleId, &$result));
		return $result;
	}

	/**
	 * Checks if this user is granted access to pre-publication galleys based on role
	 * based on their roles in the journal (i.e. Manager, Editor, etc).
	 * @param $journal Journal
	 * @param $user User
	 * @return bool
	 */
	function _roleAllowedPrePublicationAccess($journal, $user) {
		$roleDao = DAORegistry::getDAO('RoleDAO');
		if ($user && $journal) {
			$journalId = $journal->getId();
			$userId = $user->getId();
			$subscriptionAssumedRoles = array(
				ROLE_ID_MANAGER,
				ROLE_ID_SUB_EDITOR,
				ROLE_ID_ASSISTANT,
				ROLE_ID_SUBSCRIPTION_MANAGER
			);

			$roles = $roleDao->getByUserId($userId, $journalId);
			foreach ($roles as $role) {
				if (in_array($role->getRoleId(), $subscriptionAssumedRoles)) return true;
			}
		}
		return false;
	}
}


