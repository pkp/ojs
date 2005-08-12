<?php

/**
 * IssueAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue
 *
 * IssueAction class.
 *
 * $Id$
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
	function subscriptionRequired($issue) {
		$journal = &Request::getJournal();
		if (!$journal) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getJournal($issue->getJournalId());
		}
		return ($journal->getSetting('enableSubscriptions') && ($issue->getAccessStatus() == SUBSCRIPTION && strtotime($issue->getOpenAccessDate()) > time()));
	}

	/**
	 * Checks if user has subscription
	 * @return bool
	 */
	function subscribedUser() {
		$user = &Request::getUser();
		$journal = &Request::getJournal();
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');
		if (isset($user) && isset($journal)) {
			// If the user is a journal manager, editor, section editor,
			// layout editor, copyeditor, or proofreader, it is assumed
			// that they are allowed to view the journal as a subscriber.
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			$subscriptionAssumedRoles = array(
				ROLE_ID_JOURNAL_MANAGER,
				ROLE_ID_EDITOR,
				ROLE_ID_SECTION_EDITOR,
				ROLE_ID_LAYOUT_EDITOR,
				ROLE_ID_COPYEDITOR,
				ROLE_ID_PROOFREADER
			);
			$roles = &$roleDao->getRolesByUserId($user->getUserId(), $journal->getJournalId());
			foreach ($roles as $role) {
				if (in_array($role->getRoleId(), $subscriptionAssumedRoles)) return true;
			}

			return $subscriptionDao->isValidSubscription(null, null, $user->getUserId(), $journal->getJournalId());
		}
		return false;
	}
	
	/**
	 * Checks if remote client domain or ip is allowed
	 * @return bool
	 */
	function subscribedDomain() {
		$journal = &Request::getJournal();
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');
		return $subscriptionDao->isValidSubscription(Request::getRemoteDomain(), Request::getRemoteAddr(), null, $journal->getJournalId());
	}

}

?>
