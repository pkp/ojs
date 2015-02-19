<?php

/**
 * @file classes/issue/IssueAction.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	function IssueAction() {
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
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$issue =& $issueDao->getIssueByArticleId($params['articleId']);
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
			$journal =& Request::getJournal();
		}
		if (!$journal || $journal->getId() !== $issue->getJournalId()) {
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal =& $journalDao->getById($issue->getJournalId());
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
	function allowedPrePublicationAccess(&$journal, &$article) {
		if (IssueAction::_roleAllowedPrePublicationAccess($journal)) return true;

		$user =& Request::getUser();
		if ($user && $journal) {
			$journalId = $journal->getId();
			$userId = $user->getId();

			if (Validation::isAuthor($journalId)) {
				if ($article && $article->getUserId() == $userId) return true;
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				if ($article) $publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($article->getId(), null, true);
				if (isset($publishedArticle) && $publishedArticle && $publishedArticle->getUserId() == $userId) return true;
			}
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
		return IssueAction::_roleAllowedPrePublicationAccess($journal);
	}

	/**
	 * Checks if user has subscription
	 * @return bool
	 */
	function subscribedUser(&$journal, $issueId = null, $articleId = null) {
		$user =& Request::getUser();
		$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId, null, true);
		$result = false;
		if (isset($user) && isset($journal)) {
			if (IssueAction::allowedPrePublicationAccess($journal, $publishedArticle)) {
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
					$issueDao =& DAORegistry::getDAO('IssueDAO');
					$issue =& $issueDao->getIssueById($issueId);
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
	function subscribedDomain(&$journal, $issueId = null, $articleId = null) {
		$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$result = false;
		if (isset($journal)) {
			$result = $subscriptionDao->isValidInstitutionalSubscription(Request::getRemoteDomain(), Request::getRemoteAddr(), $journal->getId());

			// If no valid subscription, check if there is an expired subscription
			// that was valid during publication date of requested content
			if (!$result && $journal->getSetting('subscriptionExpiryPartial')) {
				if (isset($articleId)) {
					$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
					$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId, null, true);
					if (isset($publishedArticle)) {
						import('classes.subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidInstitutionalSubscription(Request::getRemoteDomain(), Request::getRemoteAddr(), $journal->getId(), SUBSCRIPTION_DATE_END, $publishedArticle->getDatePublished());
					}
				} else if (isset($issueId)) {
					$issueDao =& DAORegistry::getDAO('IssueDAO');
					$issue =& $issueDao->getIssueById($issueId);
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
	 * builds the issue options pulldown for published and unpublished issues
	 * @param $current bool retrieve current or not
	 * @param $published bool retrieve published or non-published issues
	 */
	function getIssueOptions() {
		$issueOptions = array();

		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$issueOptions['-100'] =  '------    ' . __('editor.issues.futureIssues') . '    ------';
		$issueIterator = $issueDao->getUnpublishedIssues($journalId);
		while (!$issueIterator->eof()) {
			$issue =& $issueIterator->next();
			$issueOptions[$issue->getId()] = $issue->getIssueIdentification();
		}
		$issueOptions['-101'] = '------    ' . __('editor.issues.currentIssue') . '    ------';
		$issuesIterator = $issueDao->getPublishedIssues($journalId);
		$issues = $issuesIterator->toArray();
		if (isset($issues[0]) && $issues[0]->getCurrent()) {
			$issueOptions[$issues[0]->getId()] = $issues[0]->getIssueIdentification();
			array_shift($issues);
		}
		$issueOptions['-102'] = '------    ' . __('editor.issues.backIssues') . '    ------';
		foreach ($issues as $issue) {
			$issueOptions[$issue->getId()] = $issue->getIssueIdentification();
		}

		return $issueOptions;
	}

	/**
	 * Checks if this user is granted access to pre-publication galleys based on role
	 * based on their roles in the journal (i.e. Manager, Editor, etc).
	 * @param $journal object
	 * @param $issue object
	 * @return bool
	 */
	function _roleAllowedPrePublicationAccess($journal) {
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$user =& Request::getUser();
		if ($user && $journal) {
			$journalId = $journal->getId();
			$userId = $user->getId();
			$subscriptionAssumedRoles = array(
				ROLE_ID_JOURNAL_MANAGER,
				ROLE_ID_EDITOR,
				ROLE_ID_SECTION_EDITOR,
				ROLE_ID_LAYOUT_EDITOR,
				ROLE_ID_COPYEDITOR,
				ROLE_ID_PROOFREADER,
				ROLE_ID_SUBSCRIPTION_MANAGER
			);

			$roles =& $roleDao->getRolesByUserId($userId, $journalId);
			foreach ($roles as $role) {
				if (in_array($role->getRoleId(), $subscriptionAssumedRoles)) return true;
			}
		}
		return false;
	}

}

?>
