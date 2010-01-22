<?php

/**
 * @file classes/issue/IssueAction.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueAction
 * @ingroup issue
 * @see Issue
 *
 * @brief IssueAction class.
 */

// $Id$


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
				$issueDao = &DAORegistry::getDAO('IssueDAO');
				$issue = &$issueDao->getIssueByArticleId($params['articleId']);
				if ($issue != null) {
					return $issue->getIssueIdentification();
				}
			}
		}
	}

	/**
	 * Checks if subscription is required for viewing the issue
	 * @param $issue
	 * @return bool
	 */
	function subscriptionRequired(&$issue) {
		$currentJournal =& Request::getJournal();
		if (!$issue) return false;
		if (!$currentJournal || $currentJournal->getJournalId() !== $issue->getJournalId()) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal =& $journalDao->getJournal($issue->getJournalId());
		} else {
			$journal =& $currentJournal;
		}

		$result = $journal->getSetting('enableSubscriptions') && 
			$issue->getAccessStatus() != OPEN_ACCESS &&
			$issue->getOpenAccessDate() &&
			strtotime($issue->getOpenAccessDate()) > time();

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
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$user =& Request::getUser();
		if ($user && $journal) {
			$journalId = $journal->getJournalId();
			$userId = $user->getUserId();
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

			if (Validation::isAuthor($journalId)) {
				if ($article && $article->getUserId() == $userId) return true;
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				if ($article) $publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($article->getArticleId());
				if (isset($publishedArticle) && $publishedArticle && $publishedArticle->getUserId() == $userId) return true;
			}
		}

		return false;
	}

	/**
	 * Checks if user has subscription
	 * @return bool
	 */
	function subscribedUser(&$journal, $issueId = null, $articleId = null) {
		$user =& Request::getUser();
		$subscriptionDao =& DAORegistry::getDAO('SubscriptionDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId); 
		$result = false;
		if (isset($user) && isset($journal)) {
			if (IssueAction::allowedPrePublicationAccess($journal, $publishedArticle)) {
				 $result = true;
			} else {
				$result = $subscriptionDao->isValidSubscription(null, null, $user->getUserId(), $journal->getJournalId());
			}

			// If no valid subscription, check if there is an expired subscription
			// that was valid during publication date of requested content
			if (!$result && $journal->getSetting('subscriptionExpiryPartial')) {
				if (isset($articleId)) {
					if (isset($publishedArticle)) {
						import('subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidSubscription(null, null, $user->getUserId(), $journal->getJournalId(), SUBSCRIPTION_DATE_END, $publishedArticle->getDatePublished());
					}
				} else if (isset($issueId)) {
					$issueDao = &DAORegistry::getDAO('IssueDAO');
					$issue = &$issueDao->getIssueById($issueId);
					if (isset($issue) && $issue->getPublished()) {
						import('subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidSubscription(null, null, $user->getUserId(), $journal->getJournalId(), SUBSCRIPTION_DATE_END, $issue->getDatePublished());
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
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');
		$result = false;
		if (isset($journal)) {
			$result = $subscriptionDao->isValidSubscription(Request::getRemoteDomain(), Request::getRemoteAddr(), null, $journal->getJournalId());

			// If no valid subscription, check if there is an expired subscription
			// that was valid during publication date of requested content
			if (!$result && $journal->getSetting('subscriptionExpiryPartial')) {
				if (isset($articleId)) {
					$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
					$publishedArticle = &$publishedArticleDao->getPublishedArticleByArticleId($articleId); 
					if (isset($publishedArticle)) {
						import('subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidSubscription(Request::getRemoteDomain(), Request::getRemoteAddr(), null, $journal->getJournalId(), SUBSCRIPTION_DATE_END, $publishedArticle->getDatePublished());
					}
				} else if (isset($issueId)) {
					$issueDao = &DAORegistry::getDAO('IssueDAO');
					$issue = &$issueDao->getIssueById($issueId);
					if (isset($issue) && $issue->getPublished()) {
						import('subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidSubscription(Request::getRemoteDomain(), Request::getRemoteAddr(), null, $journal->getJournalId(), SUBSCRIPTION_DATE_END, $issue->getDatePublished());
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

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$issueDao = &DAORegistry::getDAO('IssueDAO');

		$issueOptions['-100'] =  '------    ' . Locale::translate('editor.issues.futureIssues') . '    ------';
		$issueIterator = $issueDao->getUnpublishedIssues($journalId);
		while (!$issueIterator->eof()) {
			$issue = &$issueIterator->next();
			$issueOptions[$issue->getIssueId()] = $issue->getIssueIdentification();
		}
		$issueOptions['-101'] = '------    ' . Locale::translate('editor.issues.currentIssue') . '    ------';
		$issuesIterator = $issueDao->getPublishedIssues($journalId);
		$issues = $issuesIterator->toArray();
		if (isset($issues[0]) && $issues[0]->getCurrent()) {
			$issueOptions[$issues[0]->getIssueId()] = $issues[0]->getIssueIdentification();
			array_shift($issues);
		}
		$issueOptions['-102'] = '------    ' . Locale::translate('editor.issues.backIssues') . '    ------';
		foreach ($issues as $issue) {
			$issueOptions[$issue->getIssueId()] = $issue->getIssueIdentification();
		}

		return $issueOptions;
	}

}

?>
