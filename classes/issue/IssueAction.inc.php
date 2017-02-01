<?php

/**
 * @file classes/issue/IssueAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
	 * Smarty usage: {print_issue_id articleId="$articleId"}
	 *
	 * Custom Smarty function for printing the issue id
	 * @return string
	 */
	function smartyPrintIssueId($params, &$smarty) {
		if (isset($params) && !empty($params)) {
			if (isset($params['articleId'])) {
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$issue = $issueDao->getIssueByArticleId($params['articleId']);
				if ($issue != null) {
					return $issue->getIssueIdentification();
				}
			}
		}
	}

	/**
	 * Checks if subscription is required for viewing the issue
	 * @param $issue Issue
	 * @param $journal Journal
	 * @return bool
	 */
	function subscriptionRequired(&$issue, $journal = null) {
		// Check the issue.
		if (!$issue) return false;

		// Get the journal.
		if (is_null($journal)) {
			$journal = Request::getJournal();
		}
		if (!$journal || $journal->getId() !== $issue->getJournalId()) {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getById($issue->getJournalId());
		}
		if (!$journal) return false;

		// Check subscription state.
		$result = $journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION &&
			$issue->getAccessStatus() != ISSUE_ACCESS_OPEN &&
			(is_null($issue->getOpenAccessDate()) ||
			strtotime($issue->getOpenAccessDate()) > time());
		HookRegistry::call('IssueAction::subscriptionRequired', array(&$journal, &$issue, &$result));
		return $result;
	}

	/**
	 * Checks if this user is granted reader access to pre-publication articles
	 * based on their roles in the journal (i.e. Manager, Editor, etc).
	 * @param $journal object
	 * @param $article object
	 * @return bool
	 */
	function allowedPrePublicationAccess($journal, $article) {
		if ($this->_roleAllowedPrePublicationAccess($journal)) return true;

		$user = Request::getUser();
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
	function allowedIssuePrePublicationAccess($journal) {
		return $this->_roleAllowedPrePublicationAccess($journal);
	}

	/**
	 * Checks if user has subscription
	 * @return bool
	 */
	function subscribedUser($journal, $issueId = null, $articleId = null) {
		$user = Request::getUser();
		$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId($articleId, null, true);
		$result = false;
		if (isset($user) && isset($journal)) {
			if ($publishedArticle && $this->allowedPrePublicationAccess($journal, $publishedArticle)) {
				 $result = true;
			} else {
				$result = $subscriptionDao->isValidIndividualSubscription($user->getId(), $journal->getId());
			}

			// If no valid subscription, check if there is an expired subscription
			// that was valid during publication date of requested content
			if (!$result && $journal->getSetting('subscriptionExpiryPartial')) {
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
		HookRegistry::call('IssueAction::subscribedUser', array(&$journal, &$result));
		return $result;
	}

	/**
	 * Checks if remote client domain or ip is allowed
	 * @return bool
	 */
	function subscribedDomain($journal, $issueId = null, $articleId = null) {
		$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$result = false;
		if (isset($journal)) {
			$result = $subscriptionDao->isValidInstitutionalSubscription(Request::getRemoteDomain(), Request::getRemoteAddr(), $journal->getId());

			// If no valid subscription, check if there is an expired subscription
			// that was valid during publication date of requested content
			if (!$result && $journal->getSetting('subscriptionExpiryPartial')) {
				if (isset($articleId)) {
					$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
					$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId, null, true);
					if (isset($publishedArticle)) {
						import('classes.subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidInstitutionalSubscription(Request::getRemoteDomain(), Request::getRemoteAddr(), $journal->getId(), SUBSCRIPTION_DATE_END, $publishedArticle->getDatePublished());
					}
				} else if (isset($issueId)) {
					$issueDao = DAORegistry::getDAO('IssueDAO');
					$issue = $issueDao->getById($issueId);
					if (isset($issue) && $issue->getPublished()) {
						import('classes.subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidInstitutionalSubscription(Request::getRemoteDomain(), Request::getRemoteAddr(), $journal->getId(), SUBSCRIPTION_DATE_END, $issue->getDatePublished());
					}
				}
			}
		}
		HookRegistry::call('IssueAction::subscribedDomain', array(&$journal, &$result));
		return $result;
	}

	/**
	 * Checks if this user is granted access to pre-publication galleys based on role
	 * based on their roles in the journal (i.e. Manager, Editor, etc).
	 * @param $journal object
	 * @param $issue object
	 * @return bool
	 */
	function _roleAllowedPrePublicationAccess($journal) {
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$user = Request::getUser();
		if ($user && $journal) {
			$journalId = $journal->getId();
			$userId = $user->getId();
			$subscriptionAssumedRoles = array(
				ROLE_ID_MANAGER,
				ROLE_ID_SECTION_EDITOR,
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

?>
